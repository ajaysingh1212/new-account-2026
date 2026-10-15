(function () {
    'use strict';

    // Work on text nodes only: search terms and record values never become HTML.
    function apply(root, query) {
        if (!root) return;
        root.querySelectorAll('mark.app-search-match').forEach(mark => {
            const parent = mark.parentNode;
            mark.replaceWith(document.createTextNode(mark.textContent));
            parent.normalize();
        });
        const terms = [...new Set(String(query || '').trim().split(/\s+/).filter(Boolean))];
        if (!terms.length) return;
        const escaped = terms.sort((a, b) => b.length - a.length).map(term => term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
        const pattern = new RegExp(escaped.join('|'), 'gi');
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                return !node.parentElement?.closest('script,style,textarea,select,option,input,button,mark,[contenteditable],.dataTables_filter,.sales-toolbar') && node.textContent.trim()
                    ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            },
        });
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(node => {
            pattern.lastIndex = 0;
            const matches = [...node.textContent.matchAll(pattern)];
            if (!matches.length) return;
            const fragment = document.createDocumentFragment();
            let offset = 0;
            matches.forEach(match => {
                fragment.append(document.createTextNode(node.textContent.slice(offset, match.index)));
                const mark = document.createElement('mark');
                mark.className = 'app-search-match';
                mark.textContent = match[0];
                fragment.append(mark);
                offset = match.index + match[0].length;
            });
            fragment.append(document.createTextNode(node.textContent.slice(offset)));
            node.replaceWith(fragment);
        });
    }

    window.AppSearchHighlight = { apply };
    if (!window.jQuery) return;
    const $ = window.jQuery;
    $(document).on('draw.dt', function (event, settings) {
        const table = new $.fn.dataTable.Api(settings);
        const query = [table.search(), ...table.columns().search().toArray()].filter(Boolean).join(' ');
        apply(table.table().body(), query);
    });

    // Also support custom search fields in cards, drawers and filtered lists.
    $(document).on('input change', 'input', function () {
        const input = this;
        if (input.closest('.dataTables_filter') || input.id === 'salesSearch' || input.id === 'unitSearch') return;
        const identity = [input.id, input.name, input.placeholder, input.getAttribute('aria-label')].join(' ');
        if (input.type !== 'search' && !/search|\bfind\b/i.test(identity)) return;
        const controlled = document.getElementById(input.getAttribute('aria-controls'));
        const root = controlled || (input.classList.contains('select2-search__field')
            ? document.querySelector('.select2-container--open .select2-results')
            : input.closest('aside,[role="dialog"],.modal,.card,.content-wrapper'));
        setTimeout(() => apply(root, input.value), 0);
    });
    $(function () {
        const params = new URLSearchParams(window.location.search);
        const query = params.get('search') || params.get('q') || params.get('query');
        if (query) apply(document.querySelector('.content-wrapper'), query);
    });
})();
