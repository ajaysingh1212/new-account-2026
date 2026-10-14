const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

// Execute the actual Blade script with a small jQuery adapter. PHP feature
// tests cannot catch missing JavaScript declarations introduced by a merge.
function form() {
    const template = fs.readFileSync(path.join(__dirname, '../../resources/views/admin/sales-returns/create.blade.php'), 'utf8');
    const script = template.match(/<script>([\s\S]*?)<\/script>/)[1]
        .replace(/^const INVOICES=.*$/m, 'const INVOICES={};')
        .replace(/^const AVAILABILITY_URL=.*$/m, 'const AVAILABILITY_URL="/admin/sales-returns/create";');
    const widgets = new Map(), requests = [];
    function $(selector) {
        if (!widgets.has(selector)) {
            const widget = {
                value: '', content: '', props: {}, dataValues: {}, handlers: {}, length: 1,
                on(event, ...args) { this.handlers[event] = args.at(-1); return this; },
                val(value) { if (value === undefined) return this.value; this.value = value; return this; },
                html(value) { this.content = value; return this; },
                empty() { this.content = ''; return this; },
                text(value) { this.content = value; return this; },
                prop(key, value) { this.props[key] = value; return this; },
                data(key, value) { if (value === undefined) return this.dataValues[key]; this.dataValues[key] = value; return this; },
                modal() { return this; },
                trigger(event) { this.handlers[event]?.call({ value: this.value }); return this; },
            };
            widgets.set(selector, widget);
        }
        return widgets.get(selector);
    }
    $.ajax = options => {
        const request = {
            options, aborted: false,
            done(callback) { this.success = callback; return this; },
            fail(callback) { this.failure = callback; return this; },
            abort() { this.aborted = true; this.failure?.({}, 'abort'); },
        };
        requests.push(request);
        return request;
    };
    const context = vm.createContext({ $, document: {}, alert() {} });
    vm.runInContext(script, context);
    return { $, requests, context, select(id) { $('#sourceInvoice').val(id).trigger('change'); } };
}

const unit = { key: 'UNIT-1', sku: 'GPS-1', vts_sim: 'SIM-1' };
const line = {
    id: 1, item: 'GPS', qty: 20, already_returned: 0, remaining_qty: 1,
    unit: 'PCS', price: 100, inter_company: true, sold_units: [unit], available_units: [unit], returned_keys: [],
};

test('invoice selection loads fresh stock and enables posting after success', () => {
    const f = form();
    f.select('1');
    assert.equal(f.requests[0].options.url, '/admin/sales-returns/create');
    assert.equal(f.requests[0].options.data.sales_invoice_id, '1');
    assert.equal(f.$('#postSalesReturn').props.disabled, true);
    f.requests[0].success({ lines: [line] });
    assert.equal(f.$('#postSalesReturn').props.disabled, false);
    assert.match(f.$('#returnLines').content, /Available in merged company: 1/);
});

test('switching invoices cancels the old request and ignores its response', () => {
    const f = form();
    f.select('1');
    f.select('2');
    assert.equal(f.requests[0].aborted, true);
    f.requests[0].success({ lines: [line] });
    assert.equal(f.$('#postSalesReturn').props.disabled, true);
    f.requests[1].success({ lines: [] });
    assert.equal(f.$('#postSalesReturn').props.disabled, true);
});

test('failed stock request keeps posting disabled and shows retry instructions', () => {
    const f = form();
    f.select('1');
    f.requests[0].failure({}, 'error');
    assert.equal(f.$('#postSalesReturn').props.disabled, true);
    assert.match(f.$('#returnLines').content, /Select the invoice again to retry/);
});

test('serial picker shows SKU and separates unavailable stock from completed returns', () => {
    const f = form();
    f.$('.return-row[data-index="0"]').data('line', {
        ...line, sold_units: [unit, { key: 'UNIT-2', sku: 'GPS-2' }], available_units: [], returned_keys: ['UNIT-2'],
    });
    vm.runInContext('activeLineIndex=0; renderSerialModal();', f.context);
    assert.match(f.$('#serialGrid').content, /SKU GPS-1/);
    assert.match(f.$('#serialGrid').content, /Not currently in merged company stock/);
    assert.match(f.$('#serialGrid').content, /Already returned/);
    assert.equal(vm.runInContext('unitLabel({sku:"SKU-ONLY"})', f.context), 'SKU-ONLY');
});

test('purchase returns explain stock already sent to the source company', () => {
    const f = form();
    const returned = { ...line, remaining_qty: 0, already_returned: 1,
        purchase_returned_qty: 1, purchase_return_refs: ['PR-00020'],
        purchase_returned_keys: [unit.key], available_units: [] };
    f.select('1');
    f.requests[0].success({ lines: [returned] });
    assert.match(f.$('#returnLines').content, /Purchase return to source: 1/);
    assert.match(f.$('#returnLines').content, /PR-00020/);
    vm.runInContext('activeLineIndex=0; renderSerialModal();', f.context);
    assert.match(f.$('#serialGrid').content, /Already sent to source by purchase return/);
    assert.equal(f.$('#postSalesReturn').props.disabled, true);
});
