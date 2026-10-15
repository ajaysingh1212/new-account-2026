const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

test('each invoice expands and collapses independently with accessible button state', () => {
    const template = fs.readFileSync(path.join(__dirname, '../../resources/views/admin/sales/index.blade.php'), 'utf8');
    const start = template.indexOf('function setInvoiceRowExpanded(');
    const script = template.slice(start, template.indexOf('function updateSalesList()', start));
    const context = vm.createContext({});
    vm.runInContext(script, context);
    function row() {
        const classes = new Set();
        const button = { textContent: 'See more', attributes: {}, setAttribute(name, value) { this.attributes[name] = value; } };
        return { classes, button, classList: { toggle(name, on) { on ? classes.add(name) : classes.delete(name); } }, querySelector: () => button };
    }
    const first = row(), second = row();
    context.setInvoiceRowExpanded(first, true);
    assert.equal(first.classes.has('sale-row-expanded'), true);
    assert.equal(first.button.textContent, 'See less');
    assert.equal(first.button.attributes['aria-expanded'], 'true');
    assert.equal(second.classes.size, 0);
    assert.equal(second.button.textContent, 'See more');
    context.setInvoiceRowExpanded(first, false);
    assert.equal(first.classes.size, 0);
    assert.equal(first.button.attributes['aria-expanded'], 'false');
    assert.equal(first.button.textContent, 'See more');
});

function drawer({ query = '', filter = 'available', selected = [] } = {}) {
    const template = fs.readFileSync(path.join(__dirname, '../../resources/views/admin/sales/form.blade.php'), 'utf8');
    const script = template.slice(template.indexOf('function renderDrawer(){'), template.indexOf('function openDrawer('));
    const units = [
        { key: 'one', serial_no: 'SER-001', vts_sim: 'SIM-ONE', sku: 'GPS-RED', batch_no: 'B-1' },
        { key: 'two', serial_no: 'SER-002', vts_sim: 'SIM-TWO', sku: 'GPS-BLUE', batch_no: 'B-2', sold: true },
        { key: 'three', serial_no: '<script>bad()</script>', sku: 'GPS-GREEN', production_batch_no: 'PROD-9' },
    ];
    const widgets = new Map();
    function $(selector) {
        if (!widgets.has(selector)) widgets.set(selector, {
            content: '', val: () => selector === '#unitSearch' ? query : 'item',
            text(value) { if (value === undefined) return ''; this.content = value; return this; },
            html(value) { this.content = value; return this; },
            not() { return this; }, each() {},
        });
        return widgets.get(selector);
    }
    const activeRow = { find: () => ({ val: () => 'item', text: () => 'GPS' }) };
    let highlighted;
    const context = vm.createContext({
        $, activeRow, activeFilter: filter, UNIT_POOL: { item: units }, rowSelected: () => selected,
        money: n => String(n || 0), document: { getElementById: id => id },
        window: { AppSearchHighlight: { apply: (root, term) => { highlighted = term; } } },
    });
    vm.runInContext(script + '\nrenderDrawer();', context);
    return { html: $('#unitList').content, count: $('#unitSearchCount').content, selected, highlighted };
}

test('serial search is case insensitive and preserves selected units outside results', () => {
    const selected = [{ key: 'one' }];
    const result = drawer({ query: 'prod-9', selected });
    assert.match(result.html, /GPS-GREEN/);
    assert.doesNotMatch(result.html, /SER-001/);
    assert.equal(result.count, '1 of 3 units · 1 selected');
    assert.deepEqual(selected, [{ key: 'one' }]);
    assert.equal(result.highlighted, 'prod-9');
});

test('search respects availability and finds sold units using All filter', () => {
    assert.match(drawer({ query: 'sim-two' }).html, /No units found/);
    const all = drawer({ query: 'sim-two', filter: 'all' });
    assert.match(all.html, /SER-002/);
    assert.match(all.html, /disabled/);
});

test('selected filter supports SKU searches without changing selections', () => {
    const result = drawer({ query: 'GPS-RED', filter: 'selected', selected: [{ key: 'one' }] });
    assert.match(result.html, /checked/);
    assert.match(result.html, /SER-001/);
    assert.equal(result.count, '1 of 3 units · 1 selected');
});

test('drawer displays record text safely and empty search restores available units', () => {
    const result = drawer();
    assert.equal(result.count, '2 of 3 units · 0 selected');
    assert.match(result.html, /&lt;script&gt;/);
    assert.doesNotMatch(result.html, /<script>/);
});

test('month filter separates years, supports all months and leaves other tables alone', () => {
    const template = fs.readFileSync(path.join(__dirname, '../../resources/views/admin/sales/index.blade.php'), 'utf8');
    const start = template.indexOf('$.fn.dataTable.ext.search.push');
    const script = template.slice(start, template.indexOf('const salesTable =', start));
    const filters = [];
    const month = { value: '2026-10' };
    const $ = { fn: { dataTable: { ext: { search: filters } } } };
    vm.runInNewContext(script, { $, document: { getElementById: () => month } });
    const settings = { nTable: { id: 'salesTable' }, aoData: [
        { nTr: { dataset: { month: '2026-10' } } },
        { nTr: { dataset: { month: '2025-10' } } },
        { nTr: { dataset: { month: '2026-09' } } },
    ] };
    assert.equal(filters[0](settings, [], 0), true);
    assert.equal(filters[0](settings, [], 1), false);
    assert.equal(filters[0](settings, [], 2), false);
    month.value = '2026-09';
    assert.equal(filters[0](settings, [], 2), true);
    month.value = '';
    assert.equal(filters[0](settings, [], 1), true);
    assert.equal(filters[0]({ nTable: { id: 'otherTable' } }, [], 0), true);
});
