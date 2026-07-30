@php
    $segments = collect($segments ?? []);
    $modalId = $modalId ?? 'segmentModal';
    $title = $title ?? 'Product Category Report';
    $amountLabel = $amountLabel ?? 'Amount';
    $parties = $segments->flatMap(fn($row) => collect($row['items'])->pluck('party'))->filter()->unique()->sort()->values();
    $states = $segments->flatMap(fn($row) => collect($row['items'])->pluck('state'))->filter()->unique()->sort()->values();
    $districts = $segments->flatMap(fn($row) => collect($row['items'])->pluck('district'))->filter()->unique()->sort()->values();
    $cities = $segments->flatMap(fn($row) => collect($row['items'])->pluck('city'))->filter()->unique()->sort()->values();
    $categories = $segments->flatMap(fn($row) => collect($row['items'])->pluck('category'))->filter()->unique()->sort()->values();
    $productTypes = $segments->flatMap(fn($row) => collect($row['items'])->pluck('product_type'))->filter(fn($value) => filled($value) && $value !== '-')->unique()->sort()->values();
@endphp

<div class="modal fade pro-modal segment-report-modal" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header segment-light-head">
                <div><h5 class="modal-title mb-0">{{ $title }}</h5><small>Filter: {{ $from }} to {{ $to }}</small></div>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="segment-filter-grid">
                    <select class="form-control segment-filter segment-product-filter" data-filter="category" data-placeholder="All Categories"><option value="">All Categories</option>@foreach($categories as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach</select>
                    <select class="form-control segment-filter segment-product-filter" data-filter="product_type" data-placeholder="All Product Types"><option value="">All Product Types</option>@foreach($productTypes as $productType)<option value="{{ $productType }}">{{ $productType }}</option>@endforeach</select>
                    <select class="form-control segment-filter segment-party-filter" data-filter="party" data-placeholder="All Parties"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party }}">{{ $party }}</option>@endforeach</select>
                    <select class="form-control segment-filter segment-location-filter" data-filter="state" data-placeholder="All States"><option value="">All States</option>@foreach($states as $state)<option value="{{ $state }}">{{ $state }}</option>@endforeach</select>
                    <select class="form-control segment-filter segment-location-filter" data-filter="district" data-placeholder="All Districts"><option value="">All Districts</option>@foreach($districts as $district)<option value="{{ $district }}">{{ $district }}</option>@endforeach</select>
                    <select class="form-control segment-filter segment-location-filter" data-filter="city" data-placeholder="All Cities"><option value="">All Cities</option>@foreach($cities as $city)<option value="{{ $city }}">{{ $city }}</option>@endforeach</select>
                </div>

                @include('admin.partials.segment-viz-body', ['segments' => $segments, 'amountLabel' => $amountLabel])
            </div>
        </div>
    </div>
</div>
