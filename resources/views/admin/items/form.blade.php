@php
    $isEdit    = $item->exists;
    $typesJson = $types->keyBy('id')->map(fn($t) => ['nature' => $t->nature, 'name' => $t->name])->toJson();
@endphp

@push('styles')
<style>
/* ══════════════════════════════════════════
   Item Form — Enhanced Wizard
══════════════════════════════════════════ */
.iw-wrap{background:#f8f7ff;border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(124,58,237,.08)}

/* Tab bar */
.iw-tabs{display:grid;grid-template-columns:repeat(4,1fr);background:#fff;border-bottom:2px solid #f0eaf8}
.iw-tab{padding:16px 12px;font-weight:700;font-size:13px;color:#9ca3af;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;border-right:1px solid #f0eaf8;transition:.2s;user-select:none;position:relative}
.iw-tab:last-child{border-right:0}
.iw-tab .tab-num{width:26px;height:26px;border-radius:50%;background:#f3f0ff;color:#7c3aed;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.2s}
.iw-tab.active{color:#5b21b6;background:linear-gradient(135deg,#faf5ff,#f3e8ff)}
.iw-tab.active .tab-num{background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff}
.iw-tab.done{color:#059669}
.iw-tab.done .tab-num{background:#d1fae5;color:#059669}
.iw-tab.active::after{content:'';position:absolute;bottom:-2px;left:0;right:0;height:2px;background:linear-gradient(90deg,#7c3aed,#5b21b6)}

/* Panes */
.iw-pane{display:none;padding:26px 28px;background:#fff}
.iw-pane.active{display:block}

/* Section headers inside pane */
.iw-section{display:flex;align-items:center;gap:10px;margin-bottom:18px;padding-bottom:10px;border-bottom:1px solid #f3f0ff}
.iw-section-icon{width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#7c3aed,#5b21b6);display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px}
.iw-section h5{margin:0;font-weight:700;color:#1f2937;font-size:14px}
.iw-section small{color:#9ca3af;font-size:12px}

/* Nature badge */
.nature-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase}
.nature-finished{background:#fef3c7;color:#92400e;border:1px solid #fde68a}
.nature-raw{background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe}
.nature-readymade{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
.nature-service{background:#fce7f3;color:#9d174d;border:1px solid #fbcfe8}

/* Calc card */
.calc-card{background:linear-gradient(135deg,#1e1b4b,#312e81);color:#fff;border-radius:14px;padding:18px;height:100%}
.calc-card small{color:#a5b4fc;font-size:11px;text-transform:uppercase;letter-spacing:.6px;display:block;margin-bottom:4px}
.calc-card .calc-val{font-size:22px;font-weight:800;color:#fbbf24;font-variant-numeric:tabular-nums}
.calc-card .calc-sub{font-size:11px;color:#818cf8;margin-top:2px}

/* Form controls */
.iw-pane .form-control,.iw-pane .custom-select{border:1px solid #e5e7eb;border-radius:10px;transition:.2s;font-size:13px}
.iw-pane .form-control:focus,.iw-pane .custom-select:focus{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.iw-pane label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;margin-bottom:4px;display:block}

/* BOM section */
.bom-wrap{background:#faf5ff;border:1px solid #e9d5ff;border-radius:16px;padding:20px;margin-top:4px}
.bom-wrap .bom-header{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.bom-wrap .bom-header h6{margin:0;font-weight:700;color:#5b21b6;font-size:14px}
.bom-table{width:100%;border-collapse:collapse}
.bom-table th{font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:#9ca3af;font-weight:700;padding:8px 10px;border-bottom:1px solid #e9d5ff}
.bom-table td{padding:8px 10px;border-bottom:1px solid #f3f0ff;vertical-align:middle}
.bom-table tbody tr:hover{background:#faf5ff}
.bom-add-btn{background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:0;padding:8px 18px;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:.2s}
.bom-add-btn:hover{opacity:.9;transform:translateY(-1px)}

/* Stock hint */
.stock-hint{display:inline-block;font-size:11px;margin-top:3px;color:#9ca3af}
.stock-ok{color:#059669}
.stock-low{color:#d97706}
.stock-zero{color:#dc2626}

/* Nature info bar */
.nature-bar{background:linear-gradient(135deg,#fef3c7,#fffbeb);border:1px solid #fde68a;border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:12px;margin-top:10px}
.nature-bar-icon{width:32px;height:32px;border-radius:8px;background:#f59e0b;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}

/* Switch styling */
.iw-switch{display:flex;align-items:center;gap:10px;padding:10px 16px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px}
.iw-switch label{margin:0;font-size:13px;font-weight:600;color:#374151;cursor:pointer;text-transform:none;letter-spacing:0}
.iw-switch .custom-control-input:checked~.custom-control-label::before{background-color:#7c3aed;border-color:#7c3aed}

/* Footer */
.iw-footer{background:#f8f7ff;border-top:1px solid #f0eaf8;padding:16px 28px;display:flex;justify-content:space-between;align-items:center}
.iw-footer .btn-save{background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:0;padding:10px 28px;border-radius:12px;font-weight:700;font-size:14px}
.iw-footer .btn-save:hover{opacity:.9}
.iw-footer .step-info{font-size:12px;color:#9ca3af;font-weight:600}

/* Stock pane info */
.stock-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px}
.stock-info-card{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:14px}
.stock-info-card small{font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:#9ca3af;font-weight:700;display:block;margin-bottom:4px}
.stock-info-card b{font-size:18px;color:#1f2937;font-weight:800}
.stock-notice{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;font-size:12px;color:#92400e;display:flex;gap:8px;align-items:flex-start;margin-top:12px}
</style>
@endpush

<form method="POST" action="{{ $isEdit ? route('admin.items.update',$item) : route('admin.items.store') }}" id="itemWizardForm">
@csrf @if($isEdit) @method('PUT') @endif

<div class="iw-wrap">

    {{-- ── Tab Bar ── --}}
    <div class="iw-tabs">
        <div class="iw-tab active" data-step="1">
            <span class="tab-num">1</span>
            <span class="d-none d-sm-inline">Identity</span>
        </div>
        <div class="iw-tab" data-step="2">
            <span class="tab-num">2</span>
            <span class="d-none d-sm-inline">Pricing</span>
        </div>
        <div class="iw-tab" data-step="3">
            <span class="tab-num">3</span>
            <span class="d-none d-sm-inline">Stock</span>
        </div>
        <div class="iw-tab" data-step="4">
            <span class="tab-num">4</span>
            <span class="d-none d-sm-inline">BOM</span>
        </div>
    </div>

    {{-- ══════════════ PANE 1: Identity ══════════════ --}}
    <div class="iw-pane active" data-pane="1">
        <div class="iw-section">
            <div class="iw-section-icon"><i class="fas fa-tag"></i></div>
            <div><h5>Item Identity</h5><small>Basic classification and codes</small></div>
        </div>

        <div class="row">
            <div class="col-md-2 form-group">
                <label>Item Type</label>
                <select name="item_type" id="item_type" class="form-control">
                    <option value="product" @selected(old('item_type',$item->item_type)==='product')>Product</option>
                    <option value="service" @selected(old('item_type',$item->item_type)==='service')>Service</option>
                </select>
            </div>
            <div class="col-md-4 form-group">
                <label>Product Type / Category</label>
                <select name="product_type_id" id="product_type_id" class="form-control select2">
                    <option value="">— Select Category —</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}"
                            data-nature="{{ $type->nature }}"
                            @selected(old('product_type_id',$item->product_type_id)==$type->id)>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                {{-- Nature badge shown after selection --}}
                <div id="natureBadgeWrap" style="margin-top:6px;display:none">
                    <span id="natureBadge" class="nature-badge"></span>
                </div>
            </div>
            <div class="col-md-2 form-group">
                <label>Item Code</label>
                <input name="item_code" class="form-control" value="{{ old('item_code',$item->item_code) }}" required>
            </div>
            <div class="col-md-2 form-group">
                <label>HSN / SAC Code</label>
                <input name="hsn_code" class="form-control" value="{{ old('hsn_code',$item->hsn_code) }}">
            </div>
            <div class="col-md-2 form-group">
                <label>Unit</label>
                <input name="unit" class="form-control" value="{{ old('unit',$item->unit ?: 'PCS') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label>Item Name</label>
            <input name="name" class="form-control" style="font-size:15px;font-weight:600" value="{{ old('name',$item->name) }}" required placeholder="Enter full item name…">
        </div>

        <div class="row">
            <div class="col-md-2 form-group">
                <label>SKU</label>
                <input name="sku" class="form-control" value="{{ old('sku',$item->sku) }}" placeholder="Optional">
            </div>
            <div class="col-md-2 form-group">
                <label>Brand</label>
                <input name="brand" class="form-control" value="{{ old('brand',$item->brand) }}">
            </div>
            <div class="col-md-2 form-group">
                <label>Model</label>
                <input name="model" class="form-control" value="{{ old('model',$item->model) }}">
            </div>
            <div class="col-md-2 form-group">
                <label>Size</label>
                <input name="size" class="form-control" value="{{ old('size',$item->size) }}">
            </div>
            <div class="col-md-2 form-group">
                <label>Color</label>
                <input name="color" class="form-control" value="{{ old('color',$item->color) }}">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label>Barcode</label>
                <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text" style="border-radius:10px 0 0 10px"><i class="fas fa-barcode text-muted"></i></span></div>
                    <input name="barcode" class="form-control" value="{{ old('barcode',$item->barcode) }}" placeholder="Auto: uses item code" style="border-radius:0 10px 10px 0">
                </div>
            </div>
            <div class="col-md-6 form-group">
                <label>QR Code</label>
                <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text" style="border-radius:10px 0 0 10px"><i class="fas fa-qrcode text-muted"></i></span></div>
                    <input name="qr_code" class="form-control" value="{{ old('qr_code',$item->qr_code) }}" placeholder="Auto generated" style="border-radius:0 10px 10px 0">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Item description, specs, notes…">{{ old('description',$item->description) }}</textarea>
        </div>
    </div>

    {{-- ══════════════ PANE 2: Pricing ══════════════ --}}
    <div class="iw-pane" data-pane="2">
        <div class="iw-section">
            <div class="iw-section-icon"><i class="fas fa-rupee-sign"></i></div>
            <div><h5>Pricing & Tax</h5><small>Purchase cost and selling price</small></div>
        </div>

        {{-- Purchase --}}
        <div style="background:#f8f7ff;border-radius:14px;padding:18px;margin-bottom:18px">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#7c3aed;margin-bottom:14px;display:flex;align-items:center;gap:6px">
                <i class="fas fa-shopping-cart"></i> Purchase / Cost
            </div>
            <div class="row align-items-end">
                <div class="col-md-3 form-group mb-md-0">
                    <label>Purchase Cost (₹)</label>
                    <input type="number" step="0.01" name="purchase_price" id="purchase_price" class="form-control" value="{{ old('purchase_price',$item->purchase_price ?? 0) }}">
                </div>
                <div class="col-md-2 form-group mb-md-0">
                    <label>GST %</label>
                    <input type="number" step="0.01" name="purchase_gst_percent" id="purchase_gst" class="form-control" value="{{ old('purchase_gst_percent',$item->purchase_gst_percent ?? 0) }}">
                </div>
                <div class="col-md-3 form-group mb-md-0">
                    <div class="iw-switch mt-2">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="purchase_tax_inclusive" name="purchase_tax_inclusive" value="1" @checked(old('purchase_tax_inclusive',$item->purchase_tax_inclusive))>
                            <label class="custom-control-label" for="purchase_tax_inclusive">GST Inclusive</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="calc-card">
                        <small>Taxable / Base Cost</small>
                        <div class="calc-val" id="purchaseCalc">₹ 0.00</div>
                        <div class="calc-sub">Amount before/after removing GST</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sale --}}
        <div style="background:#f0fdf4;border-radius:14px;padding:18px">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#059669;margin-bottom:14px;display:flex;align-items:center;gap:6px">
                <i class="fas fa-tag"></i> Selling Price
            </div>
            <div class="row align-items-end">
                <div class="col-md-3 form-group mb-md-0">
                    <label>Selling Price (₹)</label>
                    <input type="number" step="0.01" name="sale_price" id="sale_price" class="form-control" value="{{ old('sale_price',$item->sale_price ?? 0) }}">
                </div>
                <div class="col-md-2 form-group mb-md-0">
                    <label>GST %</label>
                    <input type="number" step="0.01" name="sale_gst_percent" id="sale_gst" class="form-control" value="{{ old('sale_gst_percent',$item->sale_gst_percent ?? 0) }}">
                </div>
                <div class="col-md-3 form-group mb-md-0">
                    <div class="iw-switch mt-2">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="sale_tax_inclusive" name="sale_tax_inclusive" value="1" @checked(old('sale_tax_inclusive',$item->sale_tax_inclusive))>
                            <label class="custom-control-label" for="sale_tax_inclusive">GST Inclusive</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="calc-card" style="background:linear-gradient(135deg,#064e3b,#065f46)">
                        <small style="color:#6ee7b7">Taxable / Base Sale</small>
                        <div class="calc-val" id="saleCalc" style="color:#34d399">₹ 0.00</div>
                        <div class="calc-sub" style="color:#6ee7b7">Amount before/after removing GST</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════ PANE 3: Stock ══════════════ --}}
    <div class="iw-pane" data-pane="3">
        <div class="iw-section">
            <div class="iw-section-icon"><i class="fas fa-boxes"></i></div>
            <div><h5>Stock Settings</h5><small>Opening qty, low-stock alerts, tracking</small></div>
        </div>

        <div class="stock-notice">
            <i class="fas fa-info-circle" style="color:#f59e0b;margin-top:1px;flex-shrink:0"></i>
            <span><b>Note:</b> Opening Stock value is saved for reference only. Actual stock is added via <b>Purchase entries</b> (raw materials) or <b>Production batches</b> (finished goods). No stock movement is created on item save.</span>
        </div>

        <div class="row mt-3 product-only">
            <div class="col-md-3 form-group">
                <label>Opening Stock (Reference)</label>
                <input type="number" step="0.001" name="opening_stock" class="form-control"
                    value="{{ old('opening_stock',$item->opening_stock ?? 0) }}"
                    @disabled($isEdit)
                    placeholder="0">
                @if($isEdit)<small class="text-muted">Cannot change after creation</small>@endif
            </div>
            <div class="col-md-3 form-group">
                <label>Low Stock Warning Qty</label>
                <input type="number" step="0.001" name="low_stock_qty" class="form-control"
                    value="{{ old('low_stock_qty',$item->low_stock_qty) }}" placeholder="e.g. 10">
            </div>
            <div class="col-md-3 form-group">
                <label>Weight / Qty</label>
                <input type="number" step="0.001" name="per_quantity_weight" class="form-control"
                    value="{{ old('per_quantity_weight',$item->per_quantity_weight) }}" placeholder="Optional kg per qty">
            </div>
            <div class="col-md-3 form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active"   @selected(old('status',$item->status)==='active')>Active</option>
                    <option value="inactive" @selected(old('status',$item->status)==='inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 form-group d-flex align-items-end">
                <div class="iw-switch w-100">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="track_stock" name="track_stock" value="1"
                            @checked(old('track_stock',$item->track_stock ?? true))>
                        <label class="custom-control-label" for="track_stock">Track Stock Movements</label>
                    </div>
                </div>
            </div>
        </div>

        @if($isEdit)
        <div class="stock-info-grid mt-2 product-only">
            <div class="stock-info-card">
                <small>Current Stock</small>
                <b>{{ number_format((float)$item->current_stock,3) }} {{ $item->unit }}</b>
            </div>
            <div class="stock-info-card">
                <small>Stock Value</small>
                <b>₹ {{ number_format((float)$item->stock_value,2) }}</b>
            </div>
        </div>
        @endif
    </div>

    {{-- ══════════════ PANE 4: BOM ══════════════ --}}
    <div class="iw-pane" data-pane="4">
        <div class="iw-section">
            <div class="iw-section-icon"><i class="fas fa-project-diagram"></i></div>
            <div><h5>Bill of Materials (BOM)</h5><small>Raw material composition for finished goods</small></div>
        </div>

        {{-- Shown only when product_type nature = finished_goods --}}
        <div id="bomNotFinished" style="display:none">
            <div style="background:#f3f4f6;border:1px dashed #d1d5db;border-radius:14px;padding:32px;text-align:center;color:#9ca3af">
                <i class="fas fa-info-circle" style="font-size:28px;margin-bottom:10px;display:block"></i>
                <b style="font-size:14px;color:#6b7280;display:block;margin-bottom:6px">BOM not applicable</b>
                Select a Product Type with nature <b>Finished Goods</b> in Step 1 to configure raw material composition.
            </div>
        </div>

        <div id="bomFinishedWrap" class="product-only" style="display:none">
            <div class="bom-wrap">
                <div class="bom-header">
                    <div style="width:32px;height:32px;background:linear-gradient(135deg,#7c3aed,#5b21b6);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0">
                        <i class="fas fa-layer-group" style="font-size:13px"></i>
                    </div>
                    <div>
                        <h6>Raw Material Composition</h6>
                        <small style="color:#9ca3af;font-size:11px">Define how many raw materials are used per 1 finished unit</small>
                    </div>
                    <div class="ml-auto">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_bom_enabled" name="is_bom_enabled" value="1"
                                @checked(old('is_bom_enabled',$item->is_bom_enabled))>
                            <label class="custom-control-label" for="is_bom_enabled" style="font-size:13px;font-weight:600;color:#5b21b6">Enable BOM</label>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" id="bomTableWrap">
                    <table class="bom-table" id="bomTable">
                        <thead>
                            <tr>
                                <th style="width:50%">Raw Material</th>
                                <th style="width:20%">Qty / Finished Unit</th>
                                <th style="width:20%">Available Stock</th>
                                <th style="width:10%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($item->bomMaterials ?? [] as $bom)
                            <tr>
                                <td>
                                    <select name="bom_raw_item_id[]" class="form-control select2-bom">
                                        <option value="">— Select raw material —</option>
                                        @foreach($rawItems as $raw)
                                            <option value="{{ $raw->id }}"
                                                data-stock="{{ $raw->current_stock }}"
                                                data-unit="{{ $raw->unit }}"
                                                data-low="{{ $raw->low_stock_qty ?? 0 }}"
                                                @selected($bom->raw_item_id==$raw->id)>
                                                {{ $raw->name }} ({{ $raw->unit }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.001" min="0.001" name="bom_qty_per_unit[]"
                                        class="form-control" value="{{ $bom->qty_per_unit }}" placeholder="1">
                                </td>
                                <td>
                                    <span class="bom-stock-display" style="font-size:12px;color:#9ca3af">
                                        {{ $bom->rawItem?->current_stock ?? '—' }} {{ $bom->rawItem?->unit }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm remove-row"
                                        style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;padding:4px 10px">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <button type="button" id="addBom" class="bom-add-btn mt-3">
                    <i class="fas fa-plus"></i> Add Raw Material
                </button>
            </div>
        </div>

        {{-- Service type —no BOM --}}
        <div id="serviceNoBom" style="display:none">
            <div style="background:#fdf2f8;border:1px dashed #f9a8d4;border-radius:14px;padding:24px;text-align:center;color:#9d174d;font-size:13px">
                <i class="fas fa-tools" style="font-size:24px;margin-bottom:8px;display:block"></i>
                BOM is not applicable for <b>Service</b> type items.
            </div>
        </div>

        <div class="mt-4">
            @include('admin.partials.entry-visibility', ['entry' => $item])
        </div>
    </div>

    {{-- ── Footer ── --}}
    <div class="iw-footer">
        <span class="step-info" id="stepInfo">Step 1 of 4</span>
        <div style="display:flex;gap:8px">
            <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary" style="border-radius:10px">Cancel</a>
            <button type="button" id="prevStep" class="btn btn-light" style="border-radius:10px" disabled>← Back</button>
            <button type="button" id="nextStep" class="btn btn-primary" style="border-radius:10px;background:#7c3aed;border:0">Next →</button>
            <button type="submit" id="saveBtn" class="btn btn-save d-none"><i class="fas fa-save mr-1"></i> Save Item</button>
        </div>
    </div>

</div>
</form>

{{-- BOM row template --}}
<template id="bomTemplate">
<tr>
    <td>
        <select name="bom_raw_item_id[]" class="form-control select2-bom">
            <option value="">— Select raw material —</option>
            @foreach($rawItems as $raw)
                <option value="{{ $raw->id }}"
                    data-stock="{{ $raw->current_stock }}"
                    data-unit="{{ $raw->unit }}"
                    data-low="{{ $raw->low_stock_qty ?? 0 }}">
                    {{ $raw->name }} ({{ $raw->unit }})
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="number" step="0.001" min="0.001" name="bom_qty_per_unit[]" class="form-control" value="1" placeholder="1">
    </td>
    <td>
        <span class="bom-stock-display" style="font-size:12px;color:#9ca3af">—</span>
    </td>
    <td>
        <button type="button" class="btn btn-sm remove-row"
            style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:8px;padding:4px 10px">
            <i class="fas fa-trash"></i>
        </button>
    </td>
</tr>
</template>

@push('scripts')
<script>
const TYPES = @json($types->keyBy('id')->map(fn($t) => ['nature'=>$t->nature,'name'=>$t->name]));

// ── State ─────────────────────────────────────────────────────
let step = 1;

// ── Helpers ───────────────────────────────────────────────────
function money(n){ return '₹ '+(Number(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function itemType(){ return $('#item_type').val(); }
function selectedNature(){
    const id = $('#product_type_id').val();
    return id && TYPES[id] ? TYPES[id].nature : null;
}

// ── Step nav ──────────────────────────────────────────────────
function renderStep(){
    $('.iw-tab').each(function(){
        const s = +$(this).data('step');
        $(this).removeClass('active done');
        if(s === step) $(this).addClass('active');
        else if(s < step) $(this).addClass('done');
    });
    $('.iw-pane').removeClass('active');
    $(`[data-pane="${step}"]`).addClass('active');

    $('#prevStep').prop('disabled', step === 1);
    $('#nextStep').toggleClass('d-none', step === 4);
    $('#saveBtn').toggleClass('d-none', step !== 4);
    $('#stepInfo').text('Step '+step+' of 4');

    if(step === 4) renderBomPane();
}

$('#nextStep').click(()=>{ if(step<4){step++;renderStep();} });
$('#prevStep').click(()=>{ if(step>1){step--;renderStep();} });
$('.iw-tab').click(function(){ step = +$(this).data('step'); renderStep(); });

// ── Nature badge ──────────────────────────────────────────────
const natureMeta = {
    finished_goods: { cls:'nature-finished', icon:'fa-industry',     label:'Finished Goods' },
    raw_material:   { cls:'nature-raw',      icon:'fa-cubes',        label:'Raw Material'   },
    readymade:      { cls:'nature-readymade',icon:'fa-check-circle', label:'Readymade'      },
    service:        { cls:'nature-service',  icon:'fa-tools',        label:'Service'        },
};

function updateNatureBadge(){
    const nature = selectedNature();
    if(!nature){ $('#natureBadgeWrap').hide(); return; }
    const meta = natureMeta[nature] || { cls:'nature-raw', icon:'fa-tag', label: nature };
    $('#natureBadge')
        .attr('class','nature-badge '+meta.cls)
        .html(`<i class="fas ${meta.icon}"></i> ${meta.label}`);
    $('#natureBadgeWrap').show();
    // Update is_bom_enabled visibility
    renderBomPane();
    // product-only fields
    toggleProductOnly();
}

// ── product-only visibility ───────────────────────────────────
function toggleProductOnly(){
    const isProduct = itemType() === 'product';
    $('.product-only').toggle(isProduct);
}

// ── BOM pane logic ────────────────────────────────────────────
function renderBomPane(){
    const nature  = selectedNature();
    const isProduct = itemType() === 'product';
    const isFinished = nature === 'finished_goods';

    if(!isProduct){
        $('#bomFinishedWrap').hide();
        $('#bomNotFinished').hide();
        $('#serviceNoBom').show();
        return;
    }
    $('#serviceNoBom').hide();
    if(isFinished){
        $('#bomNotFinished').hide();
        $('#bomFinishedWrap').show();
    } else {
        $('#bomFinishedWrap').hide();
        $('#bomNotFinished').show();
    }
}

// ── Pricing calc ──────────────────────────────────────────────
function calc(){
    const pp = +$('#purchase_price').val()||0;
    const pg = +$('#purchase_gst').val()||0;
    const sp = +$('#sale_price').val()||0;
    const sg = +$('#sale_gst').val()||0;
    $('#purchaseCalc').text(money($('#purchase_tax_inclusive').is(':checked') ? pp/(1+pg/100) : pp));
    $('#saleCalc').text(money($('#sale_tax_inclusive').is(':checked') ? sp/(1+sg/100) : sp));
}

// ── BOM stock display ─────────────────────────────────────────
function updateBomStockDisplay($select){
    const $opt = $select.find(':selected');
    const stock = parseFloat($opt.data('stock')) || 0;
    const unit  = $opt.data('unit') || '';
    const low   = parseFloat($opt.data('low')) || 0;
    const $td   = $select.closest('tr').find('.bom-stock-display');
    if(!$opt.val()){ $td.text('—').css('color','#9ca3af'); return; }
    let cls = 'stock-ok', icon = '✓';
    if(stock <= 0)    { cls = 'stock-zero'; icon = '✗'; }
    else if(stock <= low) { cls = 'stock-low'; icon = '⚠'; }
    $td.html(`<span class="${cls}">${icon} ${stock} ${unit}</span>`);
}

$(document).on('change','.select2-bom', function(){ updateBomStockDisplay($(this)); });

// ── Add BOM row ───────────────────────────────────────────────
$('#addBom').click(function(){
    const html = $('#bomTemplate').html();
    const $row = $(html);
    $('#bomTable tbody').append($row);
    // re-init select2 on the new select
    $row.find('.select2-bom').select2({ width:'100%', placeholder:'— Select raw material —' });
});

// ── Remove BOM row ────────────────────────────────────────────
$(document).on('click','.remove-row', function(){ $(this).closest('tr').remove(); });

// ── Event bindings ────────────────────────────────────────────
$('#product_type_id').on('change', updateNatureBadge);
$('#item_type').on('change', function(){ toggleProductOnly(); renderBomPane(); });
$('input,select').on('input change', calc);

// ── Init ──────────────────────────────────────────────────────
renderStep();
calc();
updateNatureBadge();
toggleProductOnly();

// Init select2 on existing BOM rows
$('.select2-bom').select2({ width:'100%', placeholder:'— Select raw material —' });

// Update stock display for pre-filled BOM rows
$('.select2-bom').each(function(){ updateBomStockDisplay($(this)); });
</script>
@endpush
