@extends('layouts.admin')
@section('title','Item Master')

@push('styles')
<style>
/* ══════════════════════════════════════════
   Item Master — Enhanced Index
══════════════════════════════════════════ */
.im-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.im-header-left{display:flex;align-items:center;gap:14px}
.im-header-icon{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#7c3aed,#5b21b6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;box-shadow:0 6px 18px rgba(124,58,237,.3)}
.im-header h2{margin:0;font-size:22px;font-weight:800;color:#1f2937}
.im-header p{margin:0;color:#9ca3af;font-size:13px}
.im-add-btn{background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff!important;border:0;padding:10px 20px;border-radius:12px;font-weight:700;font-size:13px;display:inline-flex;align-items:center;gap:7px;box-shadow:0 4px 14px rgba(124,58,237,.3);transition:.2s}
.im-add-btn:hover{opacity:.9;transform:translateY(-1px)}

/* Stat chips */
.im-stats{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.im-stat{background:#fff;border:1px solid #f0eaf8;border-radius:12px;padding:10px 18px;display:flex;align-items:center;gap:10px;flex:1;min-width:140px}
.im-stat-icon{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.im-stat small{font-size:10px;text-transform:uppercase;letter-spacing:.6px;font-weight:700;color:#9ca3af;display:block}
.im-stat b{font-size:18px;font-weight:800;color:#1f2937}

/* Filter bar */
.im-filters{background:#fff;border:1px solid #f0eaf8;border-radius:14px;padding:14px 18px;margin-bottom:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.im-filters input,.im-filters select{border:1px solid #e5e7eb;border-radius:10px;padding:6px 12px;font-size:13px;color:#374151}
.im-filters input:focus,.im-filters select:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1)}

/* Table card */
.im-card{background:#fff;border:1px solid #f0eaf8;border-radius:16px;overflow:hidden;box-shadow:0 2px 12px rgba(124,58,237,.06)}
.im-table thead tr{background:linear-gradient(135deg,#f5f3ff,#faf5ff)}
.im-table thead th{font-size:10px;text-transform:uppercase;letter-spacing:.7px;font-weight:700;color:#7c3aed;padding:12px 14px;border-bottom:1px solid #f0eaf8;white-space:nowrap}
.im-table tbody tr{transition:background .15s;border-bottom:1px solid #faf5ff}
.im-table tbody tr:hover{background:#fefcff}
.im-table td{padding:11px 14px;vertical-align:middle;font-size:13px;color:#374151}
.im-table td:last-child{white-space:nowrap}

/* Item name cell */
.item-name{font-weight:700;color:#1f2937;font-size:14px;margin-bottom:2px}
.item-sub{font-size:11px;color:#9ca3af}
.item-code-badge{display:inline-block;background:#f3f0ff;color:#5b21b6;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:700;font-family:monospace}

/* Nature badges */
.nb{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;white-space:nowrap}
.nb-finished{background:#fef3c7;color:#92400e}
.nb-raw{background:#dbeafe;color:#1e40af}
.nb-readymade{background:#d1fae5;color:#065f46}
.nb-service{background:#fce7f3;color:#9d174d}

/* Stock cell */
.stock-cell b{font-size:15px;font-weight:800;color:#1f2937}
.stock-cell small{font-size:11px;color:#9ca3af}
.low-badge{display:inline-flex;align-items:center;gap:3px;background:#fef3c7;color:#92400e;border-radius:6px;padding:2px 8px;font-size:10px;font-weight:700;animation:pulseWarn 1.5s infinite}
@keyframes pulseWarn{0%,100%{opacity:1}50%{opacity:.6}}
.zero-badge{background:#fee2e2;color:#dc2626}

/* Price cell */
.price-purchase{font-size:13px;font-weight:600;color:#374151}
.price-sale{font-size:13px;font-weight:700;color:#059669}

/* Status */
.status-active{display:inline-flex;align-items:center;gap:4px;background:#d1fae5;color:#065f46;border-radius:8px;padding:3px 10px;font-size:11px;font-weight:700}
.status-active::before{content:'';width:6px;height:6px;background:#10b981;border-radius:50%}
.status-inactive{display:inline-flex;align-items:center;gap:4px;background:#fee2e2;color:#991b1b;border-radius:8px;padding:3px 10px;font-size:11px;font-weight:700}
.status-inactive::before{content:'';width:6px;height:6px;background:#ef4444;border-radius:50%}

/* Creator cell */
.creator-name{font-size:13px;font-weight:600;color:#374151}
.creator-role{font-size:10px;color:#9ca3af;margin-top:1px}

/* Action buttons */
.btn-edit-sm{background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:8px;padding:5px 10px;font-size:12px;transition:.15s}
.btn-edit-sm:hover{background:#fde68a;color:#78350f}
.btn-del-sm{background:#fee2e2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;padding:5px 10px;font-size:12px;transition:.15s}
.btn-del-sm:hover{background:#fecaca;color:#991b1b}
.read-only-badge{display:inline-block;background:#f3f4f6;color:#9ca3af;border-radius:8px;padding:4px 10px;font-size:11px;font-weight:600}

/* BOM indicator */
.bom-dot{display:inline-flex;align-items:center;gap:4px;font-size:11px;color:#7c3aed;background:#f3f0ff;border-radius:6px;padding:2px 8px;font-weight:600}

/* Empty state */
.im-empty{text-align:center;padding:60px 20px;color:#9ca3af}
.im-empty i{font-size:48px;margin-bottom:16px;display:block;opacity:.3}
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="im-header">
    <div class="im-header-left">
        <div class="im-header-icon"><i class="fas fa-boxes"></i></div>
        <div>
            <h2>Item Master</h2>
            <p>{{ $items->count() }} item(s) in your inventory</p>
        </div>
    </div>
    @can('items.create')
        <a href="{{ route('admin.items.create') }}" class="im-add-btn">
            <i class="fas fa-plus"></i> Add New Item
        </a>
    @endcan
</div>

{{-- Quick stats --}}
@php
    $totalActive   = $items->where('status','active')->count();
    $totalProducts = $items->where('item_type','product')->count();
    $totalServices = $items->where('item_type','service')->count();
    $lowStock      = $items->filter(fn($i) => $i->low_stock_qty && $i->current_stock <= $i->low_stock_qty && $i->current_stock > 0)->count();
    $zeroStock     = $items->where('item_type','product')->filter(fn($i) => (float)$i->current_stock <= 0)->count();
    $bomItems      = $items->where('is_bom_enabled',true)->count();
@endphp
<div class="im-stats">
    <div class="im-stat">
        <div class="im-stat-icon" style="background:#f3f0ff;color:#7c3aed"><i class="fas fa-check-circle"></i></div>
        <div><small>Active Items</small><b>{{ $totalActive }}</b></div>
    </div>
    <div class="im-stat">
        <div class="im-stat-icon" style="background:#dbeafe;color:#1d4ed8"><i class="fas fa-cube"></i></div>
        <div><small>Products</small><b>{{ $totalProducts }}</b></div>
    </div>
    <div class="im-stat">
        <div class="im-stat-icon" style="background:#fce7f3;color:#9d174d"><i class="fas fa-tools"></i></div>
        <div><small>Services</small><b>{{ $totalServices }}</b></div>
    </div>
    @if($lowStock)
    <div class="im-stat" style="border-color:#fde68a;background:#fffbeb">
        <div class="im-stat-icon" style="background:#fef3c7;color:#92400e"><i class="fas fa-exclamation-triangle"></i></div>
        <div><small>Low Stock</small><b style="color:#d97706">{{ $lowStock }}</b></div>
    </div>
    @endif
    @if($zeroStock)
    <div class="im-stat" style="border-color:#fecaca;background:#fff5f5">
        <div class="im-stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-times-circle"></i></div>
        <div><small>Zero Stock</small><b style="color:#dc2626">{{ $zeroStock }}</b></div>
    </div>
    @endif
    @if($bomItems)
    <div class="im-stat" style="border-color:#e9d5ff">
        <div class="im-stat-icon" style="background:#f3f0ff;color:#7c3aed"><i class="fas fa-project-diagram"></i></div>
        <div><small>With BOM</small><b>{{ $bomItems }}</b></div>
    </div>
    @endif
</div>

{{-- Table card --}}
<div class="im-card">
    @if($items->isEmpty())
        <div class="im-empty">
            <i class="fas fa-box-open"></i>
            <b style="font-size:16px;color:#6b7280;display:block;margin-bottom:8px">No items yet</b>
            <a href="{{ route('admin.items.create') }}" class="im-add-btn" style="display:inline-flex">
                <i class="fas fa-plus"></i> Add First Item
            </a>
        </div>
    @else
    <div class="table-responsive" style="padding:20px;">
        <table id="itemsTable" class="table im-table mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Item</th>
                    <th>Category / Nature</th>
                    <th>Purchase</th>
                    <th>Sale</th>
                    <th>Stock</th>
                    <th>Created By</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($items as $item)
                @php
                    $canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $item);
                    $nature    = $item->productType?->nature;
                    $natureMap = [
                        'finished_goods' => ['cls'=>'nb-finished','icon'=>'fa-industry',    'label'=>'Finished Goods'],
                        'raw_material'   => ['cls'=>'nb-raw',     'icon'=>'fa-cubes',       'label'=>'Raw Material'],
                        'readymade'      => ['cls'=>'nb-readymade','icon'=>'fa-check-circle','label'=>'Readymade'],
                        'service'        => ['cls'=>'nb-service', 'icon'=>'fa-tools',       'label'=>'Service'],
                    ];
                    $nm = $nature ? ($natureMap[$nature] ?? ['cls'=>'nb-raw','icon'=>'fa-tag','label'=>ucfirst($nature)]) : null;
                    $isLow  = $item->low_stock_qty && (float)$item->current_stock <= (float)$item->low_stock_qty && (float)$item->current_stock > 0;
                    $isZero = $item->item_type === 'product' && (float)$item->current_stock <= 0;
                @endphp
                <tr>
                    {{-- Code --}}
                    <td>
                        <span class="item-code-badge">{{ $item->item_code }}</span>
                        @if($item->hsn_code)
                            <div class="item-sub mt-1">HSN: {{ $item->hsn_code }}</div>
                        @endif
                    </td>

                    {{-- Item name --}}
                    <td style="max-width:220px">
                        <div class="item-name">{{ $item->name }}</div>
                        <div class="item-sub">
                            {{ $item->barcode }}
                            @if($item->brand) · {{ $item->brand }} @endif
                            @if($item->model) · {{ $item->model }} @endif
                        </div>
                        @if($item->is_bom_enabled)
                            <span class="bom-dot mt-1"><i class="fas fa-project-diagram" style="font-size:9px"></i> BOM</span>
                        @endif
                    </td>

                    {{-- Category / Nature --}}
                    <td>
                        @if($item->productType)
                            <div style="font-weight:600;font-size:13px;color:#374151;margin-bottom:4px">{{ $item->productType->name }}</div>
                        @endif
                        @if($nm)
                            <span class="nb {{ $nm['cls'] }}"><i class="fas {{ $nm['icon'] }}" style="font-size:9px"></i> {{ $nm['label'] }}</span>
                        @elseif($item->item_type === 'service')
                            <span class="nb nb-service"><i class="fas fa-tools" style="font-size:9px"></i> Service</span>
                        @else
                            <span class="nb nb-raw"><i class="fas fa-cube" style="font-size:9px"></i> Product</span>
                        @endif
                    </td>

                    {{-- Purchase --}}
                    <td>
                        <div class="price-purchase">₹ {{ number_format((float)$item->purchase_price,2) }}</div>
                        @if($item->purchase_gst_percent)
                            <div class="item-sub">GST {{ $item->purchase_gst_percent }}%</div>
                        @endif
                    </td>

                    {{-- Sale --}}
                    <td>
                        <div class="price-sale">₹ {{ number_format((float)$item->sale_price,2) }}</div>
                        @if($item->sale_gst_percent)
                            <div class="item-sub">GST {{ $item->sale_gst_percent }}%</div>
                        @endif
                    </td>

                    {{-- Stock --}}
                    <td class="stock-cell">
                        @if($item->item_type === 'service')
                            <span class="item-sub">N/A</span>
                        @else
                            <div>
                                <b>{{ number_format((float)$item->current_stock,3) }}</b>
                                <small> {{ $item->unit }}</small>
                            </div>
                            @if($isZero && $item->track_stock)
                                <span class="low-badge zero-badge"><i class="fas fa-times" style="font-size:9px"></i> Zero Stock</span>
                            @elseif($isLow)
                                <span class="low-badge"><i class="fas fa-exclamation-triangle" style="font-size:9px"></i> Low</span>
                            @endif
                        @endif
                    </td>

                    {{-- Created by --}}
                    <td>
                        <div class="creator-name">{{ $item->creator?->name ?? 'System' }}</div>
                        <div class="creator-role">{{ $item->creator?->rolesForCompany($item->company_id)->pluck('name')->join(', ') ?: 'No role' }}</div>
                    </td>

                    {{-- Status --}}
                    <td>
                        @if($item->status === 'active')
                            <span class="status-active">Active</span>
                        @else
                            <span class="status-inactive">Inactive</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td>
                        @if($canManage)
                            <div style="display:flex;gap:6px">
                                @can('items.edit')
                                    <a class="btn-edit-sm" href="{{ route('admin.items.edit',$item) }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                                @can('items.delete')
                                    <form method="POST" action="{{ route('admin.items.destroy',$item) }}" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn-del-sm btn-delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        @else
                            <span class="read-only-badge"><i class="fas fa-eye" style="margin-right:4px"></i>Read only</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
$('#itemsTable').DataTable({
    pageLength: 25,
    order: [[0,'desc']],
    columnDefs: [{ orderable: false, targets: 8 }],
    language: { search: '', searchPlaceholder: '🔍 Search items…' },
});
</script>
@endpush
