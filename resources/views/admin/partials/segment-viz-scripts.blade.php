<script>
function dashMoney(value){return 'Rs '+(Number(value)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});}
$(document).on('click','.sales-viz-tab',function(){
    const pane = $(this).data('sales-viz');
    const $shell = $(this).closest('.sales-viz-shell');
    $shell.find('.sales-viz-tab').removeClass('active');
    $(this).addClass('active');
    $shell.find('.sales-viz-pane').removeClass('active');
    $shell.find(`[data-sales-pane="${pane}"]`).addClass('active');
    replaySegmentAnimations($(this).closest('.segment-report-modal'));
});
function segmentModalItems($modal) {
    return $modal.find('.segment-card-filterable').map(function(){
        return ($(this).data('segment') || {}).items || [];
    }).get().flat();
}

function segmentUniqueValues(items, key) {
    return [...new Set((items || []).map(item => item[key]).filter(Boolean))].sort((a, b) => String(a).localeCompare(String(b)));
}

function segmentSetOptions($select, values, selected) {
    const placeholder = $select.data('placeholder') || 'All';
    const validSelected = selected && values.includes(selected) ? selected : '';
    $select.html(`<option value="">${placeholder}</option>` + values.map(value => `<option value="${String(value).replace(/"/g,'&quot;')}">${value}</option>`).join(''));
    $select.val(validSelected);
}

function refreshSegmentFilterOptions($modal, changedFilter) {
    const allItems = segmentModalItems($modal);
    const categorySelect = $modal.find('[data-filter="category"]');
    const productTypeSelect = $modal.find('[data-filter="product_type"]');
    const party = $modal.find('[data-filter="party"]').val();
    const stateSelect = $modal.find('[data-filter="state"]');
    const districtSelect = $modal.find('[data-filter="district"]');
    const citySelect = $modal.find('[data-filter="city"]');
    const currentCategory = categorySelect.val();
    const currentProductType = productTypeSelect.val();
    const currentState = stateSelect.val();
    const currentDistrict = districtSelect.val();
    const currentCity = citySelect.val();

    segmentSetOptions(categorySelect, segmentUniqueValues(allItems, 'category'), currentCategory);
    const category = categorySelect.val();
    const categoryItems = category ? allItems.filter(item => item.category === category) : allItems;
    segmentSetOptions(productTypeSelect, segmentUniqueValues(categoryItems, 'product_type').filter(value => value !== '-'), changedFilter === 'category' ? '' : currentProductType);
    const productType = productTypeSelect.val();
    const productItems = productType ? categoryItems.filter(item => item.product_type === productType) : categoryItems;

    const partyItems = party ? productItems.filter(item => item.party === party) : productItems;
    segmentSetOptions(stateSelect, segmentUniqueValues(partyItems, 'state'), changedFilter === 'party' ? '' : currentState);

    const state = stateSelect.val();
    const stateItems = state ? partyItems.filter(item => item.state === state) : partyItems;
    segmentSetOptions(districtSelect, segmentUniqueValues(stateItems, 'district'), ['party','state'].includes(changedFilter) ? '' : currentDistrict);

    const district = districtSelect.val();
    const districtItems = district ? stateItems.filter(item => item.district === district) : stateItems;
    segmentSetOptions(citySelect, segmentUniqueValues(districtItems, 'city'), ['party','state','district'].includes(changedFilter) ? '' : currentCity);
}

function replaySegmentAnimations($modal) {
    const $animated = $modal.find('.category-pie,.category-meter span,.candle-body,.bar-fill,.segment-wave-path');
    $animated.each(function(){
        this.style.animation = 'none';
        void this.offsetHeight;
        this.style.animation = '';
    });
}

function applySegmentFilters() {
    const $modal = $(this).closest('.segment-report-modal');
    const filters = {};
    $modal.find('.segment-filter').each(function(){ filters[$(this).data('filter')] = this.value; });
    let modalTotal = 0;
    let modalAbsTotal = 0;
    const matches = item => Object.keys(filters).every(key => !filters[key] || item[key] === filters[key]);
    const segmentAmount = segment => (segment.items || []).filter(matches).reduce((sum,item) => sum + (Number(item.amount)||0), 0);
    const segmentQty = segment => (segment.items || []).filter(matches).reduce((sum,item) => sum + (Number(item.qty)||0), 0);
    const chartRows = [];
    $modal.find('.segment-card-filterable').each(function(){
        const segment = $(this).data('segment') || {};
        const items = segment.items || [];
        const matching = items.filter(matches);
        const amount = matching.reduce((sum,item) => sum + (Number(item.amount)||0), 0);
        const qty = matching.reduce((sum,item) => sum + (Number(item.qty)||0), 0);
        modalTotal += amount;
        modalAbsTotal += Math.abs(amount);
        chartRows.push({segment, amount, qty});
        $(this).toggle(matching.length > 0 || amount !== 0);
        $(this).find('.segment-top strong').text(dashMoney(amount));
        $(this).find('.segment-card-meta').text(`${qty.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})} qty`);
        $(this).find('tbody tr[data-item]').each(function(){
            const item = $(this).data('item') || {};
            $(this).toggle(matches(item));
        });
    });
    const maxAmount = Math.max(1, ...$modal.find('.segment-bar').map(function(){ return Math.abs(segmentAmount($(this).data('segment') || {})); }).get());
    $modal.find('.segment-bar,.segment-candle,.segment-content-row,.segment-legend,.segment-wave-point').each(function(){
        const segment = $(this).data('segment') || {};
        const amount = segmentAmount(segment);
        const qty = segmentQty(segment);
        const pct = modalAbsTotal > 0 ? (Math.abs(amount) / modalAbsTotal * 100) : 0;
        const height = Math.max(8, Math.abs(amount) / maxAmount * 230);
        $(this).toggle(amount !== 0);
        $(this).css({'--h': `${height}px`, '--wick': `${Math.min(260, height + 46)}px`, '--w': `${Math.min(100, pct)}%`});
        $(this).find('.chart-label').html(`${segment.label}<br>${$(this).hasClass('segment-candle') ? pct.toFixed(1) + '%' : dashMoney(amount)}`);
        $(this).find('.segment-legend-amount').html(`<b>${pct.toFixed(2)}%</b><br><small>${dashMoney(amount)}</small>`);
        $(this).find('strong').last().text(dashMoney(amount));
        $(this).find('small').first().text(`${qty.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})} qty | ${pct.toFixed(2)}%`);
    });
    let cursor = 0;
    const pieParts = chartRows.filter(row => row.amount !== 0).map(row => {
        const pct = modalAbsTotal > 0 ? Math.abs(row.amount) / modalAbsTotal * 100 : 0;
        const part = `${row.segment.color || '#64748b'} ${cursor}% ${Math.min(100, cursor + pct)}%`;
        cursor += pct;
        return part;
    });
    $modal.find('.segment-pie').css('--pie-gradient', pieParts.length ? `conic-gradient(${pieParts.join(',')})` : 'conic-gradient(#e2e8f0 0 100%)');
    $modal.find('.category-pie-center').html(`${modalAbsTotal > 0 ? '100%' : '0%'}<br><span style="font-size:11px;color:#64748b">${$modal.find('.segment-total-label').text().replace('Total ', '')}</span>`);
    const visibleRows = chartRows.filter(row => row.amount !== 0);
    const pointDenominator = Math.max(1, visibleRows.length - 1);
    const wavePoints = visibleRows.map((row, index) => {
        const x = 35 + (index * (690 / pointDenominator));
        const y = 285 - ((Math.abs(row.amount) / maxAmount) * 220);
        return `${x},${y}`;
    }).join(' ');
    $modal.find('.segment-wave-path').attr('d', wavePoints ? `M ${wavePoints}` : 'M 35,285');
    let waveIndex = 0;
    $modal.find('.segment-wave-point').each(function(){
        const segment = $(this).data('segment') || {};
        const amount = segmentAmount(segment);
        if (amount === 0) return;
        const row = visibleRows[waveIndex];
        if (!row) return;
        const index = waveIndex++;
        const x = 35 + (index * (690 / pointDenominator));
        const y = 285 - ((Math.abs(row.amount) / maxAmount) * 220);
        $(this).find('circle').attr({cx:x, cy:y, fill:row.segment.color || '#64748b'});
        $(this).find('text').attr({x:x}).text(String(row.segment.label || '').slice(0, 10));
    });
    $modal.find('.segment-total-value').text(dashMoney(modalTotal));
    replaySegmentAnimations($modal);
}

$(document).on('change','.segment-report-modal .segment-filter',function(){
    const $modal = $(this).closest('.segment-report-modal');
    refreshSegmentFilterOptions($modal, $(this).data('filter'));
    applySegmentFilters.call(this);
});
$('.segment-report-modal').on('shown.bs.modal', function(){
    const $modal = $(this);
    refreshSegmentFilterOptions($modal, null);
    applySegmentFilters.call($modal.find('.segment-filter').first()[0] || this);
});
$(document).ready(function(){
    $('.segment-report-inline').each(function(){
        const $modal = $(this);
        refreshSegmentFilterOptions($modal, null);
        applySegmentFilters.call($modal.find('.segment-filter').first()[0] || this);
    });
});
</script>
