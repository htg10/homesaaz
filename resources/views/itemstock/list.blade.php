@extends('layouts.app')
@section('title', 'Item Stock Report')

@section('content')
@php
    // Field access for this user (admin = all). Set on the User form.
    $itemAccess = $itemAccess ?? \App\Services\UserAccessService::fieldMap('item-stock');
@endphp
<div class="container-fluid">

    <!-- Search Section -->
    <div class="row mb-2">
        <div class="col-12">
            <h2 class="mb-2" style="font-size: 1.4rem;">Item Stock Report</h2>
            <form id="searchForm" class="d-flex flex-column flex-md-row gap-2">
                <input type="text" id="itemCode" name="itemCode" class="form-control form-control-sm" placeholder="Search by Item Code">
                <button type="submit" class="btn btn-primary" style="min-width: 90px; padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                    <i class="bi bi-search"></i> Search
                </button>
                <a href="#" id="pdfBtn" class="btn btn-danger" style="display: none; min-width: 75px; padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                    <i class="bi bi-file-pdf"></i> PDF
                </a>
                <button type="button" class="btn btn-secondary" id="resetBtn" style="min-width: 75px; padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </button>
            </form>
        </div>
    </div>

    <!-- No Data Alert -->
    <div class="alert alert-warning alert-lg" role="alert" id="noDataMessage" style="display: block;">
        <i class="bi bi-exclamation-triangle"></i> <strong>No item data found.</strong> Please search for an item code to view details.
    </div>

    <!-- Upload Image Section -->
    <div class="row mb-4" id="detailsSection" style="display: none;">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5>Item Image</h5>
                    <div class="text-center mb-3">
                        <img id="itemImagePreview" src="" alt="Item Image" class="item-image-preview" style="display:none;">
                        <div id="itemImagePlaceholder" class="item-image-placeholder">
                            <i class="bi bi-image"></i>
                            <div style="font-size: 12px; margin-top: 6px;">No image available</div>
                        </div>
                    </div>
                    @if (!empty($itemAccess['image_upload']))
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" type="button" id="cameraBtn">
                            <i class="bi bi-camera"></i> Take Photo from Camera
                        </button>
                        <div class="text-center">OR</div>
                        <button class="btn btn-primary" type="button" id="galleryBtn">
                            <i class="bi bi-image"></i> Choose from Gallery
                        </button>
                        <button class="btn btn-success" type="button" id="saveImageBtn" style="display:none;">
                            <i class="bi bi-check-circle"></i> Save Image
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Item Details Grid -->
    <div class="row mb-4" id="detailsSection" style="display: none;">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <div class="col-4"><strong>ITEM CODE</strong></div>
                                <div class="col-8">
                                    <span class="badge bg-primary" id="detailItemCode">-</span>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-4"><strong>COMPANY NAME</strong></div>
                                <div class="col-8" id="detailCompanyName">-</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-4"><strong>ITEM NAME</strong></div>
                                <div class="col-8" id="detailItemName">-</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-4"><strong>DESIGN NAME</strong></div>
                                <div class="col-8" id="detailDesignName">-</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-4"><strong>AVAILABLE QTY</strong></div>
                                <div class="col-8">
                                    <span class="badge bg-success" id="detailStockQty">-</span>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-4"><strong>COLOR</strong></div>
                                <div class="col-8" id="detailColor">-</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-4"><strong>SIZE</strong></div>
                                <div class="col-8" id="detailSize">-</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-4"><strong>UNIT</strong></div>
                                <div class="col-8" id="detailUnit">-</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-4"><strong>SECTION NAME</strong></div>
                                <div class="col-8" id="detailSectionName">-</div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="row mb-3">
                                <div class="col-4"><strong>QUALITY NAME</strong></div>
                                <div class="col-8" id="detailQualityName">-</div>
                            </div>

                            @if (!empty($itemAccess['supplier_name']))
                            <div class="row mb-3">
                                <div class="col-4"><strong>SUPPLIER NAME</strong></div>
                                <div class="col-8" id="detailSupplierName">-</div>
                            </div>
                            @endif

                            @if (!empty($itemAccess['supplier_mobile']))
                            <div class="row mb-3">
                                <div class="col-4"><strong>SUPPLIER MOBILE</strong></div>
                                <div class="col-8" id="detailSupplierMobile">-</div>
                            </div>
                            @endif

                            @if (!empty($itemAccess['contact_person']))
                            <div class="row mb-3">
                                <div class="col-4"><strong>CONTACT PERSON</strong></div>
                                <div class="col-8" id="detailContactPerson">-</div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-4"><strong>HSN CODE</strong></div>
                                <div class="col-8" id="detailHSNCode">-</div>
                            </div>

                            {{-- <div class="row mb-3">
                                <div class="col-4"><strong>ACCOUNT ID</strong></div>
                                <div class="col-8" id="detailAccountID">-</div>
                            </div> --}}

                            @if (!empty($itemAccess['markup']))
                            <div class="row mb-3">
                                <div class="col-4"><strong>MU</strong></div>
                                <div class="col-8" id="detailMU">-</div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-4"><strong>COLLECTION NAME</strong></div>
                                <div class="col-8" id="detailCollectionName">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Section -->
    <div class="row mb-4" id="detailsSection" style="display: none;">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Pricing & Discount Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            @if (!empty($itemAccess['mrp']))
                            <div class="row mb-3">
                                <div class="col-4"><strong>MRP</strong></div>
                                <div class="col-8">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.01" class="form-control form-control-sm" id="mrp" value="">
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- <div class="row mb-3">
                                <div class="col-4"><strong>RATE</strong></div>
                                <div class="col-8" id="rateDisplay">₹ -</div>
                            </div> --}}

                            <div class="row mb-3">
                                <div class="col-4"><strong>COST PRICE</strong></div>
                                <div class="col-8" id="costPriceDisplay">₹ -</div>
                            </div>

                            @if (!empty($itemAccess['markup']))
                            <div class="row mb-3">
                                <div class="col-4"><strong>MARKUP (%)</strong></div>
                                <div class="col-8" id="markupDisplay">-</div>
                            </div>
                            @endif
                             @if (!empty($itemAccess['markdown']))
                             <div class="row mb-3">
                                <div class="col-4"><strong>MARKDOWN (%)</strong></div>
                                <div class="col-8" id="markdownDisplay">-</div>
                            </div>
                             @endif
                        </div>

                        <div class="col-md-6">
                           

                            @if (!empty($itemAccess['discount']))
                            <div class="row mb-3">
                                <div class="col-4"><strong>DISCOUNT (%)</strong></div>
                                <div class="col-8">
                                    <input type="number" step="0.01" class="form-control form-control-sm" id="discount" value="">
                                </div>
                            </div>
                            @endif

                            @if (!empty($itemAccess['dp_exclusive']))
                            <div class="row mb-3">
                                <div class="col-4"><strong>DP INCLUSIVE</strong></div>
                                <div class="col-8" id="dpExclusiveDisplay">-</div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-4"><strong>GST (%)</strong></div>
                                <div class="col-8" id="gstDisplay">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Branch-wise Stock Distribution -->
    <div class="row mb-4" id="detailsSection" style="display: none;">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Branch-wise Stock Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-center" id="branchStockTable">
                            <thead class="table-light" id="branchStockHead">
                                <!-- Generated dynamically -->
                            </thead>
                            <tbody id="branchStockBody">
                                <!-- Generated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row" id="detailsSection" style="display: none;">
        <div class="col-md-12">
            @if (!empty($itemAccess['mrp']))
            <button class="btn btn-primary btn-lg me-2" id="saveMrpBtn">
                <i class="bi bi-save"></i> Save MRP
            </button>
            @endif
            @if (!empty($itemAccess['discount']))
            <button class="btn btn-success btn-lg" id="saveDiscountBtn">
                <i class="bi bi-save"></i> Save Discount
            </button>
            @endif
        </div>
    </div>

</div>

<style>
    .item-image-preview {
        max-width: 220px;
        max-height: 220px;
        width: auto;
        height: auto;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
        background: #f8f9fa;
    }

    .item-image-placeholder {
        width: 220px;
        height: 220px;
        max-width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px dashed #ccc;
        background: #f8f9fa;
        color: #adb5bd;
        font-size: 2.5rem;
        margin: 0 auto;
    }

    .form-control-sm {
        font-size: 0.875rem;
        padding: 0.35rem 0.6rem;
    }

    .badge {
        font-size: 0.9rem;
        padding: 0.4rem 0.6rem;
    }

    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1.1rem;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 8px;
        }

        h2 {
            font-size: 1.3rem !important;
            margin-bottom: 10px !important;
        }

        .form-control, .btn {
            font-size: 13px !important;
            padding: 0.45rem 0.6rem !important;
        }

        .btn-lg {
            padding: 0.45rem 0.8rem !important;
            font-size: 0.9rem !important;
        }

        .card {
            margin-bottom: 12px;
        }

        .row {
            margin-bottom: 12px !important;
        }

        .col-md-6 {
            margin-bottom: 15px;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.4rem;
        }

        .table {
            font-size: 0.8rem;
        }

        .table td {
            padding: 5px !important;
        }
    }

    @media (max-width: 576px) {
        .container-fluid {
            padding: 5px;
        }

        h2 {
            font-size: 1.1rem !important;
            margin-bottom: 8px !important;
        }

        .form-control, .btn {
            font-size: 11px !important;
            padding: 0.35rem 0.5rem !important;
        }

        .btn-lg {
            padding: 0.35rem 0.6rem !important;
            font-size: 0.85rem !important;
            min-width: auto !important;
        }

        .col-md-6 {
            margin-bottom: 12px;
        }

        .row {
            margin-bottom: 8px !important;
        }

        .row mb-3 {
            margin-bottom: 8px !important;
        }

        .badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.3rem;
        }

        .table {
            font-size: 0.7rem;
        }

        .table td, .table th {
            padding: 3px !important;
        }

        input[type="number"] {
            font-size: 11px !important;
        }

        .card-header {
            padding: 0.5rem !important;
        }

        .card-body {
            padding: 0.75rem !important;
        }
    }

    #branchStockHead .branch-location-header {
        min-width: 120px;
        vertical-align: middle !important;
        font-weight: 700;
        border-color: rgba(0, 0, 0, 0.15) !important;
        background-image: none !important;
        box-shadow: none !important;
    }

    .branch-location-code {
        font-weight: 700;
        line-height: 1.2;
        padding: 3px 0;
    }

</style>
@endsection

@push('scripts')
<script>
// Holds the file picked via Camera/Gallery until "Save Image" is pressed —
// declared at top level so both displayStockData() (reset on new search)
// and the upload handlers (inside DOMContentLoaded below) can use it.
let pendingImageFile = null;

// Display stock data from API response
function displayStockData(stock, locationCodes = [], locations = [], locationColors = {}) {
    console.log('Displaying Stock:', stock);
    
    // Helper to set text safely
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.innerText = value || '-';
    };
    
    // Set basic info
    setText('detailItemCode', stock.ItemCode || '-');
    setText('detailCompanyName', stock.CompanyName || '-');
    setText('detailItemName', stock.ItemName || '-');
    setText('detailDesignName', stock.DesignName || '-');
    setText('detailColor', stock.Color || '-');
    setText('detailSize', stock.Size || '-');
    setText('detailUnit', stock.Unit || '-');
    setText('detailSectionName', stock.SectionName || '-');
    setText('detailQualityName', stock.QualityName || '-');
    setText('detailSupplierName', stock.SupplierName || '-');
    setText('detailSupplierMobile', stock.SupplierMobileNo || '-');
    setText('detailContactPerson', stock.ContactPerson || '-');
    setText('detailHSNCode', stock.HSNCODE || '-');
    setText('detailAccountID', stock.AccountID || '-');
    setText('detailMU', stock.MU || '-');
    setText('detailCollectionName', stock.COLLECTIONNAME || '-');

    // Item image (shown according to the searched Item Code)
    // Reset any pending (not-yet-saved) image pick from a previous item
    // search, so it can never accidentally get attached to this new item.
    pendingImageFile = null;
    const saveImageBtnEl = document.getElementById('saveImageBtn');
    if (saveImageBtnEl) {
        saveImageBtnEl.style.display = 'none';
        saveImageBtnEl.disabled = false;
        saveImageBtnEl.innerHTML = '<i class="bi bi-check-circle"></i> Save Image';
    }

    const imgEl = document.getElementById('itemImagePreview');
    const imgPlaceholder = document.getElementById('itemImagePlaceholder');
    if (imgEl && imgPlaceholder) {
        if (stock.ImageUrl) {
            imgEl.src = stock.ImageUrl;
            imgEl.style.display = 'inline-block';
            imgPlaceholder.style.display = 'none';
            imgEl.onerror = function () {
                imgEl.style.display = 'none';
                imgPlaceholder.style.display = 'flex';
            };
        } else {
            imgEl.style.display = 'none';
            imgEl.src = '';
            imgPlaceholder.style.display = 'flex';
        }
    }
    
    // Set numeric values
    setText('detailStockQty', stock.StkQty || '-');
    setText('rateDisplay', stock.Rate ? '₹ ' + parseFloat(stock.Rate).toFixed(2) : '₹ -');
    setText('costPriceDisplay', stock.ACost ? '₹ ' + parseFloat(stock.ACost).toFixed(2) : '₹ -');
    setText('markupDisplay', stock.Markup ? parseFloat(stock.Markup).toFixed(2) + '%' : '-');
    setText('markdownDisplay', stock.MD || '-');
    // DP Exclusive = Cost + GST amount on that cost (e.g. Cost 100, GST 5% => 100 + 5 = 105).
    if (stock.ACost !== null && stock.ACost !== undefined && stock.ACost !== '') {
        const costValue = parseFloat(stock.ACost) || 0;
        const gstPercent = (stock.GST !== null && stock.GST !== undefined && stock.GST !== '') ? (parseFloat(stock.GST) || 0) : 0;
        const gstAmount = costValue * (gstPercent / 100);
        const dpExclusive = costValue + gstAmount;
        setText('dpExclusiveDisplay', '₹ ' + dpExclusive.toFixed(2));
    } else {
        setText('dpExclusiveDisplay', '-');
    }
    setText('gstDisplay', (stock.GST !== null && stock.GST !== undefined && stock.GST !== '') ? parseFloat(stock.GST).toFixed(2) : '-');
    
    // Set input values (MRP shown with 4 digits after decimal)
    const mrpInput = document.getElementById('mrp');
    if (mrpInput) {
        const mrpValue = stock.MRP !== null && stock.MRP !== undefined && stock.MRP !== ''
            ? parseFloat(stock.MRP).toFixed(2)
            : '';
        mrpInput.value = mrpValue;
        mrpInput.setAttribute('data-original', mrpValue);
    }
    
    const discountInput = document.getElementById('discount');
    if (discountInput) {
        const discountValue = stock.Discount !== null && stock.Discount !== undefined && stock.Discount !== ''
            ? parseFloat(stock.Discount).toFixed(2)
            : '';
        discountInput.value = discountValue;
        discountInput.setAttribute('data-original', discountValue);
    }
    
    // Set branch warehouse stock dynamically
    generateBranchStockTable(stock, locationCodes, locations, locationColors);
    
    // Show details section, hide no-data message
    document.querySelectorAll('#detailsSection').forEach(el => el.style.display = 'block');
    document.getElementById('noDataMessage').style.display = 'none';
    
    // Show PDF button
    const pdfBtn = document.getElementById('pdfBtn');
    if (pdfBtn) {
        pdfBtn.href = '#';
        pdfBtn.onclick = function(e) {
            e.preventDefault();
            const itemCode = stock.ItemCode;
            fetch('{{ route("itemstock.search") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                },
                body: JSON.stringify({ itemCode: itemCode })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    window.open('{{ route("itemstock.downloadPdf") }}?itemCode=' + encodeURIComponent(itemCode), '_blank');
                } else {
                    alert('Error refreshing data');
                }
            })
            .catch(err => console.error(err));
        };
        pdfBtn.style.display = 'inline-block';
    }
}

// Dynamically generate branch-wise stock distribution table
function generateBranchStockTable(stock, locationCodes = [], locations = [], locationColors = {}) {
    /*
     * Branch code comes from Location.Locationcode.
     * Branch color comes from Location.Color.
     * Only location codes that also exist as HSStock columns are displayed.
     */
    const excludedColumns = new Set([
        'ItemCode', 'ItemDetID', 'CompanyName', 'ItemName', 'ProductName',
        'DesignName', 'Color', 'Size', 'SectionName', 'Unit', 'MRP', 'Rate',
        'ACost', 'GST', 'HSNCODE', 'AccountID', 'MU', 'COLLECTIONNAME',
        'Discount', 'Markup', 'QualityName', 'SupplierName',
        'SupplierMobileNo', 'ContactPerson', 'ItemImage', 'IImage',
        'ImageUrl', 'StkQty', 'HsStockID', 'TEST_BRANCH', 'DPEX',
        'RJCACCOUNTID'
    ]);

    const preferredOrder = locationCodes.length > 0 ? locationCodes : [];
    const stockKeys = Object.keys(stock).filter(key => !excludedColumns.has(key));
    const availableColumns = preferredOrder.filter(key => stockKeys.includes(key));

    // Map branch code -> configured Location.Color.
    // Prefer explicit locationColors from backend, then fall back to locations[].
    const locationColorMap = {};

    Object.entries(locationColors || {}).forEach(([code, color]) => {
        if (code) {
            locationColorMap[String(code).trim().toUpperCase()] = color;
        }
    });

    (locations || []).forEach(location => {
        if (!location) return;

        const code = location.code ?? location.Locationcode ?? '';
        const color = location.color ?? location.Color ?? '';

        if (code && !locationColorMap[String(code).trim().toUpperCase()]) {
            locationColorMap[String(code).trim().toUpperCase()] = color;
        }
    });

    // Supports values like:
    // Yellow, #FFFF00, FFFF00, rgb(...), rgba(...), hsl(...)
    const normalizeCssColor = (value) => {
        if (value === null || value === undefined) return '';

        let color = String(value).trim();
        if (!color) return '';

        // 6/3/8 digit hex stored without #
        if (/^[0-9a-fA-F]{3}$/.test(color) ||
            /^[0-9a-fA-F]{6}$/.test(color) ||
            /^[0-9a-fA-F]{8}$/.test(color)) {
            color = '#' + color;
        }

        return color;
    };

    const headRow = document.querySelector('#branchStockHead');
    const bodyRow = document.querySelector('#branchStockBody');
    if (!headRow || !bodyRow) return;

    headRow.innerHTML = '';
    bodyRow.innerHTML = '';

    if (availableColumns.length === 0) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 1;
        td.className = 'text-muted';
        td.textContent = 'No stock distribution fields found';
        tr.appendChild(td);
        bodyRow.appendChild(tr);
        return;
    }

    const isCssColor = (value) => {
        if (!value || typeof value !== 'string') return false;

        const test = document.createElement('span');
        test.style.color = '';
        test.style.color = value.trim();

        return test.style.color !== '';
    };

    // Header row: WHQTY + Location.Color directly as the branch header background.
    const headerTr = document.createElement('tr');

    // Warehouse quantity from HSStock.WHQTY
    const whQtyTh = document.createElement('th');
    whQtyTh.className = 'branch-location-header';
    whQtyTh.style.setProperty('background-color', '#f8f9fa', 'important');
    whQtyTh.style.setProperty('color', '#000', 'important');
    const whQtyCodeDiv = document.createElement('div');
    whQtyCodeDiv.className = 'branch-location-code';
    whQtyCodeDiv.textContent = 'WHQTY';
    whQtyTh.appendChild(whQtyCodeDiv);
    headerTr.appendChild(whQtyTh);

    // Pick readable text color for hex/rgb backgrounds.
    const getReadableTextColor = (backgroundColor) => {
        const probe = document.createElement('span');
        probe.style.color = backgroundColor;
        probe.style.display = 'none';
        document.body.appendChild(probe);

        const rgb = getComputedStyle(probe).color;
        probe.remove();

        const match = rgb.match(/\d+/g);
        if (!match || match.length < 3) {
            return '#000';
        }

        const r = parseInt(match[0], 10);
        const g = parseInt(match[1], 10);
        const b = parseInt(match[2], 10);

        // WCAG-style relative brightness approximation.
        const brightness = (r * 299 + g * 587 + b * 114) / 1000;

        return brightness > 150 ? '#000' : '#fff';
    };

    availableColumns.forEach(col => {
        const th = document.createElement('th');
        th.className = 'branch-location-header';

        const rawBranchColor =
            locationColorMap[String(col).trim().toUpperCase()] || '';
        const branchColor = normalizeCssColor(rawBranchColor);

        if (branchColor && isCssColor(branchColor)) {
            const textColor = getReadableTextColor(branchColor);

            // Use inline style with !important so Bootstrap table styles
            // cannot override the configured branch color.
            th.style.setProperty('background-color', branchColor, 'important');
            th.style.setProperty('color', textColor, 'important');
        }

        const codeDiv = document.createElement('div');
        codeDiv.className = 'branch-location-code';
        codeDiv.textContent = col;
        th.appendChild(codeDiv);

        headerTr.appendChild(th);
    });

    headRow.appendChild(headerTr);

    // Quantity row
    const dataTr = document.createElement('tr');

    // Show WHQTY value from HSStock.WHQTY
    const whQtyTd = document.createElement('td');
    const whQtyBadge = document.createElement('span');
    whQtyBadge.className = 'badge bg-secondary';
    const whQtyValue = stock.WHQTY;
    if (whQtyValue !== null && whQtyValue !== undefined && whQtyValue !== '' && !isNaN(Number(whQtyValue))) {
        whQtyBadge.textContent = Number(whQtyValue).toFixed(2);
    } else {
        whQtyBadge.textContent = (whQtyValue === null || whQtyValue === undefined || whQtyValue === '') ? '-' : String(whQtyValue);
    }
    whQtyTd.appendChild(whQtyBadge);
    dataTr.appendChild(whQtyTd);

    availableColumns.forEach(col => {
        const td = document.createElement('td');
        const badge = document.createElement('span');
        const value = stock[col];

        if (value !== null && value !== undefined && value !== '' && !isNaN(Number(value))) {
            badge.className = 'badge bg-secondary';
            badge.textContent = Number(value).toFixed(2);
        } else {
            badge.className = 'badge bg-secondary';
            badge.textContent =
                value === null || value === undefined || value === ''
                    ? '-'
                    : String(value);
        }

        td.appendChild(badge);
        dataTr.appendChild(td);
    });

    bodyRow.appendChild(dataTr);
}

document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    // Reusable: search by item code (used by form submit AND by URL prefill)
    function searchItemByCode(itemCode) {
        if (!itemCode) return;

        fetch('{{ route("itemstock.search") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ itemCode: itemCode })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Search Response:', data);
            if (data.success && data.data) {
                displayStockData(data.data, data.locationCodes || [], data.locations || [], data.locationColors || {});
                document.getElementById('itemCode').value = itemCode;
            } else {
                alert(data.message || 'Item not found');
                document.querySelectorAll('#detailsSection').forEach(el => el.style.display = 'none');
                document.getElementById('noDataMessage').style.display = 'block';
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            alert('Error searching item');
        });
    }

    // Search form submit
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const itemCode = document.getElementById('itemCode').value.trim();
            
            if (!itemCode) {
                alert('Please enter Item Code');
                return;
            }

            searchItemByCode(itemCode);
        });
    }

    // Auto-fill + auto-search when opened with ?itemCode=XXXX
    // (used by GRN Details page "Code" click → prefilled Item Stock screen)
    const urlParams = new URLSearchParams(window.location.search);
    const prefillItemCode = urlParams.get('itemCode');
    if (prefillItemCode) {
        document.getElementById('itemCode').value = prefillItemCode;
        searchItemByCode(prefillItemCode.trim());
    }

    // Camera button
    const cameraBtn = document.getElementById('cameraBtn');
    if (cameraBtn) {
        cameraBtn.addEventListener('click', function () {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.capture = 'environment';
            input.onchange = function(e) {
                selectImageForUpload(e.target.files[0]);
            };
            input.click();
        });
    }

    // Gallery button
    const galleryBtn = document.getElementById('galleryBtn');
    if (galleryBtn) {
        galleryBtn.addEventListener('click', function () {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = function(e) {
                selectImageForUpload(e.target.files[0]);
            };
            input.click();
        });
    }

    // Called when a file is picked from either Camera or Gallery — just
    // previews it locally and reveals the Save button. No upload yet.
    function selectImageForUpload(file) {
        if (!file) return;

        const itemCode = document.getElementById('detailItemCode').innerText;
        if (!itemCode || itemCode === '-') {
            alert('Please search for an item first');
            return;
        }

        pendingImageFile = file;

        const imgEl = document.getElementById('itemImagePreview');
        const imgPlaceholder = document.getElementById('itemImagePlaceholder');
        const saveBtn = document.getElementById('saveImageBtn');

        if (imgEl && imgPlaceholder) {
            const reader = new FileReader();
            reader.onload = function (e) {
                imgEl.src = e.target.result;
                imgEl.style.display = 'inline-block';
                imgPlaceholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }

        if (saveBtn) {
            saveBtn.style.display = 'block';
        }
    }

    // Save button — this is what actually uploads the picked file.
    const saveImageBtn = document.getElementById('saveImageBtn');
    if (saveImageBtn) {
        saveImageBtn.addEventListener('click', function () {
            if (!pendingImageFile) {
                alert('Please choose a photo first');
                return;
            }
            uploadImage(pendingImageFile);
        });
    }

    // Upload image function
    function uploadImage(file) {
        if (!file) return;

        const itemCode = document.getElementById('detailItemCode').innerText;
        if (!itemCode || itemCode === '-') {
            alert('Please search for an item first');
            return;
        }

        const saveBtn = document.getElementById('saveImageBtn');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
        }

        const formData = new FormData();
        formData.append('itemCode', itemCode);
        formData.append('image', file);

        fetch('{{ route("itemstock.uploadImage") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Image uploaded successfully');
                // Refresh the preview immediately with the newly saved image.
                const imgEl = document.getElementById('itemImagePreview');
                const imgPlaceholder = document.getElementById('itemImagePlaceholder');
                if (imgEl && imgPlaceholder && data.url) {
                    imgEl.src = data.url + '?t=' + Date.now();
                    imgEl.style.display = 'inline-block';
                    imgPlaceholder.style.display = 'none';
                }
                pendingImageFile = null;
                if (saveBtn) {
                    saveBtn.style.display = 'none';
                }
            } else {
                alert(data.message || 'Error uploading image');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Save Image';
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error uploading image');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Save Image';
            }
        });
    }

    // Reset button
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('itemCode').value = '';
            document.getElementById('noDataMessage').style.display = 'block';
            document.querySelectorAll('#detailsSection').forEach(el => el.style.display = 'none');
            document.getElementById('pdfBtn').style.display = 'none';
            document.getElementById('itemCode').focus();
        });
    }

    // Save MRP
    const saveMrpBtn = document.getElementById('saveMrpBtn');
    if (saveMrpBtn) {
        saveMrpBtn.addEventListener('click', function () {
            const itemCode = document.getElementById('detailItemCode').innerText;
            const mrp = document.getElementById('mrp').value;
            const original = document.getElementById('mrp').getAttribute('data-original');

            if (!itemCode || itemCode === '-') {
                alert('Please search for an item first');
                return;
            }

            if (mrp == original) {
                alert('No changes to save');
                return;
            }

            fetch('{{ route("itemstock.updateMrp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ 
                    itemCode: itemCode,
                    mrp: mrp
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('MRP updated successfully');
                    document.getElementById('mrp').setAttribute('data-original', mrp);
                } else {
                    alert(data.message || 'Error updating MRP');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error updating MRP');
            });
        });
    }

    // Save Discount
    const saveDiscountBtn = document.getElementById('saveDiscountBtn');
    if (saveDiscountBtn) {
        saveDiscountBtn.addEventListener('click', function () {
            const itemCode = document.getElementById('detailItemCode').innerText;
            const discount = document.getElementById('discount').value;
            const original = document.getElementById('discount').getAttribute('data-original');

            if (!itemCode || itemCode === '-') {
                alert('Please search for an item first');
                return;
            }

            if (discount == original) {
                alert('No changes to save');
                return;
            }

            fetch('{{ route("itemstock.updateDiscount") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ 
                    itemCode: itemCode,
                    discount: discount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Discount updated successfully');
                    document.getElementById('discount').setAttribute('data-original', discount);
                } else {
                    alert(data.message || 'Error updating Discount');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error updating Discount');
            });
        });
    }
});
</script>
@endpush