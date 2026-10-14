@extends('layouts.app')
@section('title', 'All Stock Details')

@section('content')
    <!-- ✅ SELECT2 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--single {
            height: 38px !important;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px !important;
            padding-left: 12px;
            color: #495057;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 7px !important;
            right: 8px !important;
        }
        .select2-results__option--highlighted {
            background-color: #667eea !important;
        }
    </style>

    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="mb-0">
                    All Stock Details
                </h2>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <!-- Row 1 -->
                        <div class="row g-3 mb-3">

                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="bi bi-geo-alt"></i> Location
                                </label>
                                <div class="location-select-wrap position-relative">
                                    <select class="form-select select2-dropdown" id="locationFilter">
                                        <option value="">-- All Locations --</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->Locationcode }}"
                                            data-color="{{ $location->Color ?? '' }}"
                                            {{ !$location->linked ? 'disabled' : '' }}
                                            @if(!empty($location->Color))
                                                style="background-color: {{ $location->Color }}; color: #000;"
                                            @endif>
                                            {{ $location->LocationName }}
                                            ({{ $location->Locationcode }})
                                            {{ !$location->linked ? ' - not linked to stock' : '' }}
                                        </option>
                                    @endforeach
                                    </select>
                                    <span id="locationColorPreview"
                                          class="location-color-preview"
                                          title="Location color"></span>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="bi bi-diagram-2"></i> Section
                                </label>
                                <select class="form-select select2-dropdown" id="sectionFilter">
                                    <option value="">-- All Sections --</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section }}">{{ $section }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="bi bi-building"></i> Company
                                </label>
                                <select class="form-select select2-dropdown" id="companyFilter">
                                    <option value="">-- All Companies --</option>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company }}">{{ $company }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">
                                    <i class="bi bi-search"></i> Item Code / Item Name / Search
                                </label>
                                <input type="text" class="form-control" id="searchInput"
                                    placeholder="Item Code, Item Name...">
                            </div>

                        </div>

                        <!-- Row 2 -->
                        <div class="row g-3 mb-3">


                            <div class="col-md-3">
                                <label class="form-label">Supplier</label>
                                <select class="form-select select2-dropdown" id="supplierFilter">
                                    <option value="">-- All Suppliers --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier }}">{{ $supplier }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Serial No</label>
                                <select class="form-select select2-dropdown" id="serialNoFilter">
                                    <option value="">-- All Serial Nos --</option>
                                    @foreach ($serialNos as $serialNo)
                                        <option value="{{ $serialNo }}">{{ $serialNo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Color</label>
                                <select class="form-select select2-dropdown" id="colorFilter">
                                    <option value="">-- All Colors --</option>
                                    @foreach ($colors as $color)
                                        <option value="{{ $color }}">{{ $color }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Size</label>
                                <select class="form-select select2-dropdown" id="sizeFilter">
                                    <option value="">-- All Sizes --</option>
                                    @foreach ($sizes as $size)
                                        <option value="{{ $size }}">{{ $size }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        <!-- Row 3 -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Quantity</label>
                                <select class="form-select select2-dropdown" id="quantityFilter">
                                    <option value="">-- All Quantity --</option>
                                    @foreach ($quantities as $quantity)
                                        @if($quantity >= 0)
                                            <option value="{{ $quantity }}">{{ $quantity }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Unit</label>
                                <select class="form-select select2-dropdown" id="unitFilter">
                                    <option value="">-- All Units --</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit }}">{{ $unit }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Quality</label>
                                <select class="form-select select2-dropdown" id="qualityFilter">
                                    <option value="">-- All Quality --</option>
                                    @foreach ($qualities as $quality)
                                        <option value="{{ $quality }}">{{ $quality }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Product</label>
                                <select class="form-select select2-dropdown" id="productFilter">
                                    <option value="">-- All Products --</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product }}">{{ $product }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        <!-- Actions -->
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" id="applyFilterBtn">
                                <i class="bi bi-funnel"></i> Search
                            </button>

                            <button type="button" class="btn btn-secondary" id="clearFilterBtn">
                                <i class="bi bi-x-circle"></i> Clear
                            </button>
                        </div>

                        <div id="filterMessage" class="mt-2"></div>

                    </div>
                </div>
            </div>
        </div>

       

        <!-- Table Section -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="stockTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 80px;">
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th style="width: 60px;" data-field="ItemImage">Image</th>
                                        <th data-field="ItemCode">Item Code</th>
                                        <th data-field="ItemName">Item Name</th>
                                        <th data-field="ProductName">Product</th>
                                        <th data-field="DesignName">Design</th>
                                        <th data-field="Color">Color</th>
                                        <th data-field="Size">Size</th>
                                        <th class="text-end" data-field="StkQty">Quantity</th>
                                        <th class="text-end" data-field="WHQTY">WHQTY</th>
                                        <th data-field="Unit">Unit</th>
                                        <th class="text-end" data-field="MRP">MRP</th>
                                        <th class="text-end" data-field="Rate">Rate</th>
                                        <th class="text-end" data-field="ACost">Cost</th>
                                        <th data-field="HSNCODE">HSN Code</th>
                                        <th data-field="GST">GST</th>
                                        <th data-field="SectionName">Section</th>
                                        <th data-field="CompanyName">Company</th>
                                        <th data-field="QualityName">Quality</th>
                                        <th data-field="SerialNo">Serial No</th>
                                        <th data-field="SupplierName">Supplier</th>
                                        <th data-field="SupplierMobileNo">Supplier Mobile</th>
                                        <th data-field="ContactPerson">Contact Person</th>
                                        
                                        @foreach($locationCodes as $locationCode)
                                            <th class="text-end" data-field="{{ $locationCode }}">{{ $locationCode }}</th>
                                        @endforeach
                                        
                                        <th style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <tr>
                                        <td colspan="{{ 24 + count($locationCodes ?? []) }}" class="text-center text-muted py-5">
                                            Please select at least one filter (Location, Section, Company, or others) and
                                            click Search.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light">         <!-- Stats -->
        <div class="row mb-3">
            <!-- Total Quantity -->
            <div class="col-md-4">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Quantity</h6>
                        <h3 class="mb-0 text-primary" id="totalQuantity">0</h3>
                    </div>
                </div>
            </div>

            <!-- Total Stock Value -->
            <div class="col-md-4">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Stock Value</h6>
                        <h3 class="mb-0 text-success" id="totalStockValue">₹0.00</h3>
                    </div>
                </div>
            </div>

            <!-- Allocation Stock -->
            <div class="col-md-4">
                <div class="card text-center shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Allocation Stock</h6>
                        <h3 class="mb-0 text-info" id="allocationStock">0.00</h3>
                    </div>
                </div>
            </div>
        </div>
<div class="row mb-3">
                            <div class="col-12">
                                <strong>Total:</strong>
                                Quantity: <span id="footerQty">0</span> |
                                WHQTY: <span id="footerWHQTY">0</span> |
                                MRP: ₹<span id="footerMRP">0</span> |
                                Rate: ₹<span id="footerRate">0</span> |
                                Cost: ₹<span id="footerCost">0</span>
                            </div>
                        </div>
                        <div class="align-items-center">
                            <button class="btn btn-primary" id="prevBtn">
                                <i class="bi bi-chevron-left"></i> Previous
                            </button>

                            <span id="pageInfo" class="fw-bold">
                                Page 1 of 1
                            </span>

                            <button class="btn btn-primary" id="nextBtn">
                                Next <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Stock Item Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <div class="text-center">
                        <div class="spinner-border"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Supplier Locations Modal -->
    <div class="modal fade" id="supplierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Stock Locations for Supplier: <span id="supplierName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="supplierContent">
                    <div class="text-center">
                        <div class="spinner-border"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .table thead th {
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 2px solid #dee2e6;
        }

        .table tbody tr {
            transition: background-color 0.2s;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
        }

        .card {
            border: 1px solid #e0e0e0;
        }

        .btn-sm {
            padding: 0.35rem 0.6rem;
            font-size: 0.85rem;
        }

        .form-control,
        .form-select {
            border-radius: 0.375rem;
            font-size: 0.9rem;
        }

        .text-muted {
            font-size: 0.9rem;
        }

        .location-select-wrap {
            position: relative;
        }

        .location-color-preview {
            display: none;
            position: absolute;
            right: 32px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1px #adb5bd;
            pointer-events: none;
            z-index: 2;
        }

        .location-select-wrap.has-color .form-select {
            padding-right: 58px;
        }

        .color-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.85rem;
            min-width: 50px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .thumb-img {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: #f8f9fa;
        }

        .thumb-placeholder {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            color: #adb5bd;
            font-size: 1.1rem;
        }

        /* Stock Detail Modal Styling */
        .stock-detail-container {
            padding: 10px 0;
        }

        .detail-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-left: 3px solid #667eea;
            padding-left: 10px;
        }

        .stock-detail-container p {
            margin-bottom: 8px;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .stock-detail-container strong {
            color: #333;
            display: inline-block;
            min-width: 120px;
        }

        .stock-detail-container .badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
        }

        @media (max-width: 768px) {
            .stock-detail-container p {
                font-size: 0.85rem;
            }

            .section-title {
                font-size: 0.95rem;
            }

            .stock-detail-container strong {
                min-width: 100px;
                font-size: 0.9rem;
            }

            .detail-section {
                margin-bottom: 15px;
                padding-bottom: 12px;
            }

        }
    </style>

    @push('scripts')
        <!-- ✅ SELECT2 JS CDN -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <script>
            $(document).ready(function() {

                // ✅ Initialize Select2 on all dropdowns
                function initializeSelect2() {
                    const selectElements = [
                        '#quantityFilter',
                        '#sectionFilter',
                        '#companyFilter',
                        '#colorFilter',
                        '#sizeFilter',
                        '#supplierFilter',
                        '#serialNoFilter',
                        '#unitFilter',
                        '#qualityFilter',
                        '#productFilter'
                    ];

                    selectElements.forEach(selector => {
                        if ($(selector).data('select2')) {
                            $(selector).select2('destroy');
                        }
                        
                        $(selector).select2({
                            placeholder: 'Search & Select...',
                            allowClear: true,
                            width: '100%'
                        });
                    });
                }

                // Initialize Select2 on page load
                initializeSelect2();

                // ✅ SELECT2 WITH COLOR FORMATTING
                $('#locationFilter').select2({
                    placeholder: 'Search & Select...',
                    allowClear: true,
                    width: '100%',
                    templateResult: function(data) {
                        if (!data.id) return data.text;
                        
                        var color = $(data.element).attr('data-color') || '';
                        if (color && /^#?[0-9A-F]{6}$/i.test(color)) {
                            const bgColor = color.startsWith('#') ? color : '#' + color;
                            return $('<span style="background-color: ' + bgColor + '; padding: 8px 12px; border-radius: 4px; display: block; color: #000; font-weight: 500;">' + data.text + '</span>');
                        }
                        return data.text;
                    },
                    templateSelection: function(data) {
                        return data.text;
                    }
                });

                let currentPage = 1;
                let lastPage = 1;

                // Images are proxied through local Laravel route to avoid mixed-content
                // and firewall issues. The route fetches from remote server and serves locally.
                const IMAGE_BASE_URL = '/stock/image-proxy/';

                function escapeHtml(value) {
                    if (value === null || value === undefined) return '';
                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function showMessage(message, type = 'warning') {
                    $('#filterMessage').html(
                        `<div class="alert alert-${type} py-2 mb-0">${escapeHtml(message)}</div>`
                    );
                }

                function clearMessage() {
                    $('#filterMessage').html('');
                }

                function getQuantityBadge(quantity) {
                    const qty = parseFloat(quantity) || 0;

                    if (qty <= 10) {
                        return `<span class="badge bg-danger">${qty}</span>`;
                    }

                    if (qty <= 30) {
                        return `<span class="badge bg-warning text-dark">${qty}</span>`;
                    }

                    return `<span class="badge bg-success">${qty}</span>`;
                }

                function getColorBadge(color) {
                    if (!color || color === '-' || String(color).toUpperCase() === 'NULL') {
                        return '<span class="text-muted">-</span>';
                    }

                    const value = escapeHtml(color);

                    if (/^#?[0-9A-F]{6}$/i.test(String(color))) {
                        const bgColor = String(color).startsWith('#') ?
                            String(color) :
                            '#' + String(color);

                        const textColor =
                            bgColor.toUpperCase() === '#FFFFFF' ?
                            '#000' :
                            '#fff';

                        return `
                <span class="color-badge"
                      style="background-color:${bgColor};color:${textColor};">
                    ${value}
                </span>
            `;
                    }

                    return `<span>${value}</span>`;
                }

                function fillSelect(selector, values, placeholder) {
                    let html = `<option value="">${placeholder}</option>`;

                    (values || []).forEach(value => {
                        if (value !== null && value !== undefined && String(value).trim() !== '') {
                            const safe = escapeHtml(value);
                            html += `<option value="${safe}">${safe}</option>`;
                        }
                    });

                    $(selector).html(html);
                    
                    // ✅ Re-initialize Select2 after updating
                    if ($(selector).data('select2')) {
                        $(selector).select2('destroy');
                    }
                    $(selector).select2({
                        placeholder: 'Search & Select...',
                        allowClear: true,
                        width: '100%'
                    });
                }

                // Full, unfiltered lists as loaded on page load — used to restore the
                // dropdowns whenever Location / Section is cleared back to "All".
                const ALL_SECTIONS = @json($sections);
                const ALL_COMPANIES = @json($companies);

                // ---------------------------------------------
                // Location -> Section (narrowed to sections that
                // actually have stock at this location), and reset
                // Company back to the full list until a Section is
                // chosen. Location itself never depends on anything.
                // ---------------------------------------------
                $('#locationFilter').on('change', function() {

                    const location = $(this).val();
                    const selectedOption = $(this).find('option:selected');
                    const locationColor = selectedOption.attr('data-color') || '';
                    const colorPreview = $('#locationColorPreview');

                    if (locationColor && /^#?[0-9A-F]{6}$/i.test(locationColor)) {
                        const normalizedColor = locationColor.startsWith('#')
                            ? locationColor
                            : '#' + locationColor;

                        colorPreview.css('background-color', normalizedColor).show();
                        $(this).closest('.location-select-wrap').addClass('has-color');
                    } else {
                        colorPreview.hide().css('background-color', '');
                        $(this).closest('.location-select-wrap').removeClass('has-color');
                    }

                    if (!location) {
                        fillSelect('#sectionFilter', ALL_SECTIONS, '-- All Sections --');
                        fillSelect('#companyFilter', ALL_COMPANIES, '-- All Companies --');
                        return;
                    }

                    $.ajax({
                        url: '{{ route('stock.sectionsByLocation') }}',
                        type: 'GET',
                        data: {
                            location: location
                        },

                        success: function(response) {
                            if (!response.success) {
                                showMessage(response.message || 'Unable to load sections.',
                                    'danger');
                                return;
                            }
                            fillSelect('#sectionFilter', response.data,
                                '-- All Sections (at this location) --');
                            fillSelect('#companyFilter', ALL_COMPANIES, '-- All Companies --');
                        },

                        error: function(xhr) {
                            console.error(xhr);
                            showMessage('Unable to load sections for this location.', 'danger');
                        }
                    });
                });

                // ---------------------------------------------
                // Section -> Company (narrowed to companies in this
                // Section, and further narrowed to the current
                // Location if one is selected).
                // ---------------------------------------------
                $('#sectionFilter').on('change', function() {

                    const section = $(this).val();
                    const location = $('#locationFilter').val();

                    if (!section) {
                        fillSelect('#companyFilter', ALL_COMPANIES, '-- All Companies --');
                        return;
                    }

                    $.ajax({
                        url: '{{ route('stock.companiesBySection') }}',
                        type: 'GET',
                        data: {
                            section: section,
                            location: location
                        },

                        success: function(response) {
                            if (!response.success) {
                                showMessage(response.message || 'Unable to load companies.',
                                    'danger');
                                return;
                            }
                            fillSelect('#companyFilter', response.data, '-- All Companies --');
                        },

                        error: function(xhr) {
                            console.error(xhr);
                            showMessage('Unable to load companies.', 'danger');
                        }
                    });
                });


                // ---------------------------------------------
                // Load stock data
                // ---------------------------------------------
                function loadStockData(page = 1) {

                    const location = $('#locationFilter').val();
                    const section = $('#sectionFilter').val();
                    const company = $('#companyFilter').val();
                    const search = $('#searchInput').val().trim();
                    const itemName = ($('#itemNameFilter').val() || '').trim();
                    const design = ($('#designFilter').val() || '').trim();
                    const color = $('#colorFilter').val();
                    const size = $('#sizeFilter').val();
                    const supplier = $('#supplierFilter').val();
                    const serialNo = $('#serialNoFilter').val();
                    const unit = $('#unitFilter').val();
                    const quality = $('#qualityFilter').val();
                    const product = $('#productFilter').val();
                    const quantity = $('#quantityFilter').val();

                    // Location, Section, Company, and every other field are all
                    // independent — any single one of them is enough to search.
                    const hasAnyFilter =
                        location ||
                        section ||
                        company ||
                        search ||
                        itemName ||
                        design ||
                        color ||
                        size ||
                        supplier ||
                        serialNo ||
                        unit ||
                        quality ||
                        product ||
                        quantity;

                    if (!hasAnyFilter) {
                        showMessage(
                            'Please select at least one filter (Location, Section, Company, or others) and click Search.'
                        );
                        return;
                    }

                    clearMessage();

                    $('#tableBody').html(`
            <tr>
                <td colspan="{{ 24 + count($locationCodes ?? []) }}" class="text-center py-5">
                    <div class="spinner-border spinner-border-sm"></div>
                    Loading...
                </td>
            </tr>
        `);

                    $.ajax({
                        url: '{{ route('stock.datatable') }}',
                        type: 'GET',

                        data: {
                            page: page,
                            per_page: 50,

                            location: location,
                            section: section,
                            company: company,
                            product: product,
                            search: search,
                            item_name: itemName,
                            design: design,
                            color: color,
                            size: size,
                            supplier: supplier,
                            serial_no: serialNo,
                            unit: unit,
                            quality: quality,
                            quantity: quantity
                        },

                        success: function(response) {

                            if (!response.success) {
                                showMessage(
                                    response.message || 'No data found.',
                                    'warning'
                                );

                                $('#tableBody').html(`
                        <tr>
                            <td colspan="${24 + visibleLocationCodes.length}"                                class="text-center text-muted py-5">
                                No stock items found.
                            </td>
                        </tr>
                    `);

                                return;
                            }

                            currentPage = response.current_page || page;
                            lastPage = response.last_page || 1;
                            visibleLocationCodes = response.locationCodes || visibleLocationCodes;

                            renderTable(response.data || []);

                            if (response.stats) {

                                $('#totalQuantity').text(
                                    Number(response.stats.total_quantity ?? 0).toFixed(2)
                                );

                                $('#totalStockValue').text(
                                    '₹' + Number(response.stats.total_value ?? 0).toFixed(2)
                                );

                                $('#allocationStock').text(
                                    Number(
                                        response.stats.total_allocation_stock ??
                                        response.stats.totalAllocationStock ??
                                        0
                                    ).toFixed(2)
                                );

                                $('#footerQty').text(
                                    Number(response.stats.total_quantity ?? 0).toFixed(2)
                                );

                                $('#footerWHQTY').text(
                                    Number(
                                        response.stats.total_whqty ??
                                        response.stats.total_WHQTY ??
                                        0
                                    ).toFixed(2)
                                );

                                $('#footerMRP').text(Number(response.stats.total_mrp ?? 0).toFixed(2));
                                $('#footerRate').text(Number(response.stats.total_rate ?? 0).toFixed(2));
                                $('#footerCost').text(Number(response.stats.total_cost ?? 0).toFixed(2));
                            }

                            $('#pageInfo').text(
                                'Page ' + currentPage + ' of ' + lastPage
                            );

                            $('#prevBtn').prop(
                                'disabled',
                                currentPage <= 1
                            );

                            $('#nextBtn').prop(
                                'disabled',
                                currentPage >= lastPage
                            );
                        },

                        error: function(xhr) {

                            console.error(xhr);

                            let message = 'Error loading stock data.';

                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }

                            showMessage(message, 'danger');

                            $('#tableBody').html(`
                    <tr>
                        <td colspan="${24 + visibleLocationCodes.length}"                            class="text-center text-danger py-5">
                            ${escapeHtml(message)}
                        </td>
                    </tr>
                `);
                        }
                    });
                }


                // Permission-aware location columns supplied by StockController.
                let visibleLocationCodes = @json($locationCodes ?? []);

                // ---------------------------------------------
                // Render table
                // ---------------------------------------------
                function renderTable(stocks) {

                    if (!stocks || stocks.length === 0) {

                        $('#tableBody').html(`
                <tr>
                    <td colspan="${24 + visibleLocationCodes.length}"                        class="text-center text-muted py-5">
                        <i class="bi bi-inbox"
                           style="font-size:2rem;"></i>
                        <p class="mb-0">No stock items found</p>
                    </td>
                </tr>
            `);

                        return;
                    }

                    let html = '';

                    stocks.forEach(function(stock) {

                        const itemDetID = stock.ItemDetID;

                        if (!itemDetID) return;

                        const itemCode = escapeHtml(
                            stock.ItemCode || 'N/A'
                        );

                        const itemName = escapeHtml(
                            stock.ItemName || 'N/A'
                        );

                        const productName = escapeHtml(
                            stock.ProductName || '-'
                        );

                        const designName = escapeHtml(
                            stock.DesignName || '-'
                        );

                        const sectionName = escapeHtml(
                            stock.SectionName || '-'
                        );

                        const companyName = escapeHtml(
                            stock.CompanyName || '-'
                        );

                        const supplierName = escapeHtml(
                            stock.SupplierName || '-'
                        );

                        // TEMP DEBUG: title attribute shows the raw ImageName + the
                        // exact URL being tried, so hovering over the placeholder/
                        // broken image tells us immediately whether ImageName came
                        // back empty from the backend, or came back but the URL is
                        // wrong. Remove the title attributes once images work.
                        const debugImageName = stock.ImageName ?
                            `ImageName: "${stock.ImageName}"` :
                            'ImageName: (empty/null from backend)';

                        const rawImageName = String(stock.ImageName || '').trim();

                        const imageUrl = stock.ImageUrl
                            ? String(stock.ImageUrl)
                            : (
                                rawImageName
                                    ? `${IMAGE_BASE_URL}${encodeURIComponent(rawImageName.replace(/^.*[\\\/]/, ''))}`
                                    : ''
                              );

                        const imageCell = imageUrl ?
                            `<img src="${escapeHtml(imageUrl)}"
                        class="thumb-img"
                        alt="${itemCode}"
                        title="${escapeHtml(debugImageName + ' | URL: ' + imageUrl)}"
                        onerror="this.outerHTML='<div class=&quot;thumb-placeholder&quot; title=&quot;' + this.title.replace(/&quot;/g, '&amp;quot;') + ' (failed to load)&quot;'+'><i class=&quot;bi bi-image&quot;></i></div>'">` :
                            `<div class="thumb-placeholder" title="${escapeHtml(debugImageName)}"><i class="bi bi-image"></i></div>`;

                        const quantity =
                            stock.StkQty ??
                            stock.StKQty ??
                            0;

                        // WHQTY is supplied by the backend from hsstock.
                        const whQty =
                            stock.WHQTY ??
                            stock.WhQty ??
                            stock.WHQty ??
                            0;

                        const mrp = Number(
                            stock.MRP || 0
                        ).toFixed(2);

                        const rate = Number(
                            stock.Rate || 0
                        ).toFixed(2);

                        // ---- Same fields as the details popup ----
                        const size = escapeHtml(stock.Size || '-');
                        const unit = escapeHtml(stock.Unit || '-');
                        const cost = Number(stock.ACost || 0).toFixed(2);
                        const hsnCode = escapeHtml(stock.HSNCODE || '-');
                        const gst = escapeHtml(stock.GST || '-');
                        const qualityName = escapeHtml(stock.QualityName || '-');
                        const serialNo = escapeHtml(stock.SerialNo || '-');
                        const supplierMobile = escapeHtml(stock.SupplierMobileNo || '-');
                        const contactPerson = escapeHtml(stock.ContactPerson || '-');
                        
                        // Dynamic Location Codes
                        const locationCodes = visibleLocationCodes;
                        const locationValues = {};
                        locationCodes.forEach(code => {
                            locationValues[code] = escapeHtml(stock[code] ?? '0.00');
                        });

                        html += `
                <tr data-id="${itemDetID}">

                    <td>
                        <input type="checkbox"
                               class="form-check-input item-checkbox"
                               value="${itemDetID}">
                    </td>

                    <td>
                        ${imageCell}
                    </td>

                    <td>
                        <span class="badge bg-primary item-code-badge viewBtn"
                              data-id="${itemDetID}"
                              title="Click to view details"
                              style="cursor:pointer;">
                            ${itemCode}
                        </span>
                    </td>

                    <td>
                        <strong>${itemName}</strong>
                    </td>

                    <td>${productName}</td>

                    <td>${designName}</td>

                    <td>
                        ${getColorBadge(stock.Color)}
                    </td>

                    <td>${size}</td>

                    <td class="text-end">
                        ${getQuantityBadge(quantity)}
                    </td>

                    <td class="text-end">
                        ${getQuantityBadge(whQty)}
                    </td>

                    <td>${unit}</td>

                    <td class="text-end">
                        ₹${mrp}
                    </td>

                    <td class="text-end">
                        ₹${rate}
                    </td>

                    <td class="text-end">
                        ₹${cost}
                    </td>

                    <td>${hsnCode}</td>

                    <td>${gst}</td>

                    <td>
                        <small>${sectionName}</small>
                    </td>

                    <td>
                        <small>${companyName}</small>
                    </td>

                    <td>${qualityName}</td>
                    <td>${serialNo}</td>

                    <td>
                        <small>
                            ${supplierName !== '-' ? `<a href="#" class="supplier-link" data-supplier="${escapeHtml(supplierName)}" data-itemcode="${escapeHtml(itemCode)}" style="color: #0066cc; cursor: pointer; text-decoration: underline;">${supplierName}</a>` : supplierName}
                        </small>
                    </td>

                    <td>${supplierMobile}</td>

                    <td>${contactPerson}</td>

                    ${locationCodes.map(code => `<td class="text-end">${locationValues[code]}</td>`).join('')}

                    <td>
                        <button type="button"
                                class="btn btn-sm btn-outline-info viewBtn"
                                data-id="${itemDetID}"
                                title="View Details">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>

                </tr>
            `;
                    });

                    $('#tableBody').html(html);
                }


                // ---------------------------------------------
                // Supplier link click - show locations for THIS ITEM ONLY
                // ---------------------------------------------
                $(document).on('click', '.supplier-link', function(e) {
                    e.preventDefault();

                    const supplier = $(this).data('supplier');
                    const itemCode = $(this).data('itemcode');

                    const modal = new bootstrap.Modal(
                        document.getElementById('supplierModal')
                    );

                    $('#supplierName').text(escapeHtml(supplier) + ' - Item: ' + escapeHtml(itemCode));
                    $('#supplierContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border"></div>
            </div>
        `);

                    modal.show();

                    $.ajax({
                        url: '{{ route('stock.itemLocations') }}',
                        type: 'GET',
                        data: {
                            supplier: supplier,
                            itemCode: itemCode
                        },

                        success: function(response) {

                            if (!response.success) {
                                $('#supplierContent').html(
                                    `<div class="alert alert-warning">
                            ${escapeHtml(response.message || 'No data found')}
                        </div>`
                                );
                                return;
                            }

                            const item = response.data;

                            if (!item) {
                                $('#supplierContent').html(
                                    `<div class="alert alert-info">
                            No stock found for this item.
                        </div>`
                                );
                                return;
                            }

                            let html = `
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Item Code:</strong> ${escapeHtml(item.ItemCode || '-')}</p>
                                    <p><strong>Item Name:</strong> ${escapeHtml(item.ItemName || '-')}</p>
                                    <p><strong>Section:</strong> ${escapeHtml(item.SectionName || '-')}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Rate:</strong> ₹${Number(item.Rate || 0).toFixed(2)}</p>
                                    <p><strong>Total Stock:</strong> ${Number(item.StkQty || 0).toFixed(0)} units</p>
                                </div>
                            </div>
                            
                            <hr>
                            <h6 class="mb-3"><strong>Warehouse Distribution:</strong></h6>
                            
                            <table class="table table-sm table-bordered">
                                <tbody>
                `;

                            // Show each warehouse location - All 13 codes
                            const locations = [
                                { name: 'AV', value: item.AV },
                                { name: 'AVWH', value: item.AVWH },
                                { name: 'FBD', value: item.FBD },
                                { name: 'GGN', value: item.GGN },
                                { name: 'HSD', value: item.HSD },
                                { name: 'HSN', value: item.HSN },
                                { name: 'KAVERI', value: item.KAVERI },
                                { name: 'KRN', value: item.KRN },
                                { name: 'LJP', value: item.LJP },
                                { name: 'LS', value: item.LS },
                                { name: 'MG', value: item.MG },
                                { name: 'RJC', value: item.RJC },
                            ];

                            locations.forEach(function(loc) {
                                const qty = Number(loc.value || 0);
                                const badge = qty > 0 ?
                                    `<span class="badge bg-success">${qty.toFixed(2)}</span>` :
                                    `<span class="badge bg-secondary">0</span>`;
                                html += `
                        <tr>
                            <td style="width: 30%;"><strong>${loc.name}</strong></td>
                            <td style="width: 70%; text-align: right;">${badge}</td>
                        </tr>
                    `;
                            });

                            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;

                            $('#supplierContent').html(html);
                        },

                        error: function(xhr) {
                            console.error(xhr);
                            $('#supplierContent').html(`
                    <div class="alert alert-danger">
                        Error loading item locations.
                    </div>
                `);
                        }
                    });
                });


                // ---------------------------------------------
                // View details
                // ---------------------------------------------
                $(document).on('click', '.viewBtn', function() {

                    const id = $(this).data('id');

                    if (!id) {
                        alert('Invalid ID');
                        return;
                    }

                    const modal = new bootstrap.Modal(
                        document.getElementById('detailModal')
                    );

                    $('#detailContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border"></div>
            </div>
        `);

                    modal.show();

                    $.ajax({
                        url: '{{ url('/stock') }}/' + id,
                        type: 'GET',

                        success: function(response) {

                            if (!response.success) {
                                $('#detailContent').html(
                                    `<div class="alert alert-danger">
                            ${escapeHtml(response.message || 'Data not found')}
                        </div>`
                                );
                                return;
                            }

                            const stock = response.data;

                            const quantity =
                                stock.StkQty ??
                                stock.StKQty ??
                                0;

                            const html = `
                    <div class="stock-detail-container">

                        <div class="detail-section">

                            <h6 class="section-title">
                                📦 Basic Information
                            </h6>

                            <div class="row">

                                <div class="col-md-6">

                                    <p>
                                        <strong>Item Code:</strong>
                                        <span class="badge bg-primary">
                                            ${escapeHtml(stock.ItemCode || '-')}
                                        </span>
                                    </p>

                                    <p>
                                        <strong>Item Name:</strong>
                                        ${escapeHtml(stock.ItemName || '-')}
                                    </p>

                                    <p>
                                        <strong>Product Name:</strong>
                                        ${escapeHtml(stock.ProductName || '-')}
                                    </p>

                                    <p>
                                        <strong>Design Name:</strong>
                                        ${escapeHtml(stock.DesignName || '-')}
                                    </p>

                                </div>

                                <div class="col-md-6">

                                    <p>
                                        <strong>Company:</strong>
                                        ${escapeHtml(stock.CompanyName || '-')}
                                    </p>

                                    <p>
                                        <strong>Section:</strong>
                                        ${escapeHtml(stock.SectionName || '-')}
                                    </p>

                                    <p>
                                        <strong>Color:</strong>
                                        ${getColorBadge(stock.Color)}
                                    </p>

                                    <p>
                                        <strong>Size:</strong>
                                        ${escapeHtml(stock.Size || '-')}
                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="detail-section">

                            <h6 class="section-title">
                                💰 Stock & Pricing
                            </h6>

                            <div class="row">

                                <div class="col-md-4">

                                    <p>
                                        <strong>Stock Qty:</strong>
                                        ${getQuantityBadge(quantity)}
                                    </p>

                                    <p>
                                        <strong>WHQTY:</strong>
                                        ${getQuantityBadge(
                                            stock.WHQTY ??
                                            stock.WhQty ??
                                            stock.WHQty ??
                                            0
                                        )}
                                    </p>

                                    <p>
                                        <strong>MRP:</strong>
                                        ₹${Number(stock.MRP || 0).toFixed(2)}
                                    </p>

                                    <p>
                                        <strong>Rate:</strong>
                                        ₹${Number(stock.Rate || 0).toFixed(2)}
                                    </p>

                                </div>

                                <div class="col-md-4">

                                    <p>
                                        <strong>Unit:</strong>
                                        ${escapeHtml(stock.Unit || '-')}
                                    </p>

                                    <p>
                                        <strong>Cost:</strong>
                                        ₹${Number(stock.ACost || 0).toFixed(2)}
                                    </p>

                                    <p>
                                        <strong>HSN Code:</strong>
                                        ${escapeHtml(stock.HSNCODE || '-')}
                                    </p>

                                </div>

                                <div class="col-md-4">

                                    <p>
                                        <strong>GST:</strong>
                                        ${escapeHtml(stock.GST || '-')}
                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="detail-section">

                            <h6 class="section-title">
                                👤 Item Details
                            </h6>

                            <div class="row">

                                <div class="col-md-6">

                                    <p>
                                        <strong>Quality:</strong>
                                        ${escapeHtml(stock.QualityName || '-')}
                                    </p>

                                    <p>
                                        <strong>Supplier:</strong>
                                        <span class="badge bg-secondary">
                                            ${escapeHtml(stock.SupplierName || '-')}
                                        </span>
                                    </p>

                                </div>

                                <div class="col-md-6">

                                    <p>
                                        <strong>Supplier Mobile:</strong>
                                        ${escapeHtml(stock.SupplierMobileNo || '-')}
                                    </p>

                                    <p>
                                        <strong>Contact Person:</strong>
                                        ${escapeHtml(stock.ContactPerson || '-')}
                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="detail-section">

                            <h6 class="section-title">
                                🏢 Warehouse Stock
                            </h6>

                            <div class="row">
                            
                                ${
                                    (() => {
                                        const locationCodes = visibleLocationCodes;
                                        return locationCodes.map(code => `
                                            <div class="col-md-3">
                                                <p><strong>${code}:</strong> ${escapeHtml(stock[code] ?? '-')}</p>
                                            </div>
                                        `).join('');
                                    })()
                                }
                                
                            </div>

                        </div>


                        ${
                            stock.ItemImage || stock.IImage
                            ? `
                                                <div class="detail-section">

                                                    <h6 class="section-title">
                                                        🖼 Item Image
                                                    </h6>

                                                    <img src="${escapeHtml(
                                                        stock.ItemImage || stock.IImage
                                                    )}"
                                                         class="img-fluid rounded"
                                                         style="max-height:250px;"
                                                         onerror="this.style.display='none';">

                                                </div>
                                            `
                            : ''
                        }

                    </div>
                `;

                            $('#detailContent').html(html);
                        },

                        error: function(xhr) {

                            console.error(xhr);

                            $('#detailContent').html(`
                    <div class="alert alert-danger">
                        Error loading item details.
                    </div>
                `);
                        }
                    });
                });


                // ---------------------------------------------
                // Search button
                // ---------------------------------------------
                $('#applyFilterBtn').on('click', function() {
                    loadStockData(1);
                });


                // ---------------------------------------------
                // Clear
                // ---------------------------------------------
                $('#clearFilterBtn').on('click', function() {

                    $('#searchInput').val('');
                    $('#locationFilter').val('').trigger('change');
                    fillSelect('#sectionFilter', ALL_SECTIONS, '-- All Sections --');
                    fillSelect('#companyFilter', ALL_COMPANIES, '-- All Companies --');
                    $('#colorFilter').val('').trigger('change');
                    $('#sizeFilter').val('').trigger('change');
                    $('#supplierFilter').val('').trigger('change');
                    $('#serialNoFilter').val('').trigger('change');
                    $('#qualityFilter').val('').trigger('change');
                    $('#productFilter').val('').trigger('change');
                    $('#itemNameFilter').val('');
                    $('#designFilter').val('');
                    $('#unitFilter').val('').trigger('change');
                    $('#quantityFilter').val('').trigger('change');

                    clearMessage();

                    $('#tableBody').html(`
            <tr>
                <td colspan="${24 + visibleLocationCodes.length}"                    class="text-center text-muted py-5">
                    Please select at least one filter (Location, Section, Company, or others) and click Search.
                </td>
            </tr>
        `);

                    $('#totalQuantity').text('0');
                    $('#totalStockValue').text('₹0.00');
                    $('#allocationStock').text('0.00');
                    $('#footerQty').text('0');
                    $('#footerWHQTY').text('0');
                    $('#footerMRP').text('0.00');
                    $('#footerRate').text('0.00');
                    $('#footerCost').text('0.00');
                    $('#pageInfo').text('Page 1 of 1');

                    currentPage = 1;
                    lastPage = 1;
                });


                // ---------------------------------------------
                // Enter = Search
                // ---------------------------------------------
                $('#searchInput, #itemNameFilter, #designFilter')
                    .on('keypress', function(e) {

                        if (e.which === 13) {
                            loadStockData(1);
                            return false;
                        }
                    });


                // ---------------------------------------------
                // Pagination
                // ---------------------------------------------
                $('#prevBtn').on('click', function() {

                    if (currentPage > 1) {
                        loadStockData(currentPage - 1);
                    }
                });

                $('#nextBtn').on('click', function() {

                    if (currentPage < lastPage) {
                        loadStockData(currentPage + 1);
                    }
                });


                // ---------------------------------------------
                // Select all
                // ---------------------------------------------
                $('#selectAll').on('change', function() {

                    $('.item-checkbox').prop(
                        'checked',
                        $(this).is(':checked')
                    );
                });


                $(document).on(
                    'change',
                    '.item-checkbox',
                    function() {

                        const total =
                            $('.item-checkbox').length;

                        const checked =
                            $('.item-checkbox:checked').length;

                        $('#selectAll').prop(
                            'checked',
                            total > 0 && total === checked
                        );
                    }
                );


                // ---------------------------------------------
                // Initial state — Location, Section and Company
                // are already populated independently on page
                // load (see blade above), nothing to cascade here.
                // ---------------------------------------------
                $('#tableBody').html(`
        <tr>
            <td colspan="${24 + visibleLocationCodes.length}"                class="text-center text-muted py-5">
                Please select at least one filter (Location, Section, Company, or others) and click Search.
            </td>
        </tr>
    `);

            });
        </script>
    @endpush
@endsection