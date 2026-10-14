@extends('layouts.app') @section('title', 'GRN') @section('content') <div class="rent-container">
    <div class="rent-header">
        <h2 class="rent-title">GRN Details</h2>
    </div>
    <!-- Location & Date Filters -->
    <div class="filter-box">
        <div class="filter-row">
            <div class="filter-group">
                <label for="locationFilter">Location:</label>
                <select id="locationSelect" class="form-control">
                    <option value="">-- Select Location --</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->LocationID }}" data-color="{{ $loc->Color ?? '#000000' }}">
                            {{ $loc->LocationName }}
                        </option>
                    @endforeach
                </select>
            </div>
            <!-- Location Color Badge -->
            <div id="locationBadge" style="display: none; align-self: flex-end; margin-bottom: 0;">
                <span
                    style="
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                    padding: 8px 12px;
                    background: #f5f5f5;
                    border-radius: 6px;
                    font-size: 13px;
                    font-weight: 600;
                ">
                    <span
                        style="
                        display: inline-block;
                        width: 14px;
                        height: 14px;
                        border-radius: 50%;
                        background-color: #9CA3AF;
                        border: 2px solid #ddd;
                    "
                        id="colorDot"></span>
                    <span id="locationNameDisplay"></span>
                    <span id="colorCode" style="color: #999; font-size: 12px;"></span>
                </span>
            </div>
            <div class="filter-group">
                <label for="fromDate">From Date:</label>
                <input type="date" id="fromDate" class="filter-input">
            </div>
            <div class="filter-group">
                <label for="toDate">To Date:</label>
                <input type="date" id="toDate" class="filter-input">
            </div>
            <button id="applyFilterBtn" class="apply-filter-btn">Apply Filter</button>
            <button id="resetFilterBtn" class="reset-filter-btn">Reset</button>
        </div>
    </div>
    <!-- Search -->
    {{-- <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search by GRN No, Supplier, Floor...">
        <i class="bi bi-search"></i>
    </div> --}}
    <!-- Table -->
    <div class="rent-table-wrapper">
        <div class="table-scroll">
            <table class="rent-table" id="rentTable">
                <thead>
                    <tr>
                        <th>FLOOR NAME</th>
                        <th>GRN DATE</th>
                        <th>DETAIL</th>
                        <th>BILL NO.</th>
                        <th>SUPPLIER</th>
                        <th>GRN AMOUNT</th>
                        <th>BILL AMOUNT</th>
                        <th>TOTAL MRP</th>
                        <th>GRN NO</th>

                    </tr>
                </thead>
                <tbody>
                    <tr class="initial-row">
                        <td colspan="9" class="loading-cell">
                            <p>Please select Location or Date to load GRN details.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Mobile Cards -->
    <div class="rent-cards-wrapper" id="rentCardsContainer">
        <div class="loading-card">
            <p>Please select Location or Date to load GRN details.</p>
        </div>
    </div>
    <!-- Total Location Entries -->
    <div class="grn-total-footer">
        Total Entries: <strong id="totalEntries">0</strong>
    </div>

    <!-- Pagination -->
    <div class="rent-pagination">
        <div class="pagination-info">
            <span id="paginationInfo">Showing 0 of 0</span>
        </div>
        <div class="pagination-buttons">
            <button class="pagination-btn" id="prevBtn">← Previous</button>
            <span class="page-info" id="pageInfo">Page 1</span>
            <button class="pagination-btn" id="nextBtn">Next →</button>
        </div>
    </div>
</div>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --primary-blue: #0052cc;
        --light-gray: #f5f5f5;
        --border-gray: #ddd;
        --text-dark: #333;
        --text-light: #666;
    }

    .rent-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #f8f9fa 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .rent-header {
        margin-bottom: 25px;
    }

    .rent-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
    }

    .filter-box {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .filter-row {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        min-width: 150px;
    }

    .filter-group label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-dark);
        text-transform: uppercase;
    }

    .filter-input {
        padding: 10px 12px;
        border: 1px solid var(--border-gray);
        border-radius: 4px;
        font-size: 14px;
    }

    .filter-input:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
    }

    .apply-filter-btn,
    .reset-filter-btn {
        padding: 10px 20px;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
    }

    .apply-filter-btn {
        background: var(--primary-blue);
    }

    .apply-filter-btn:hover {
        background: #0045b3;
    }

    .reset-filter-btn {
        background: #6c757d;
    }

    .reset-filter-btn:hover {
        background: #5a6268;
    }

    .search-box {
        position: relative;
        margin-bottom: 25px;
    }

    .search-box input {
        width: 100%;
        padding: 12px 15px 12px 40px;
        border: 1px solid var(--border-gray);
        border-radius: 6px;
        font-size: 14px;
    }

    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-light);
    }

    .rent-table-wrapper {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .table-scroll {
        width: 100%;
        overflow-x: auto;
    }

    .rent-table {
        width: 100%;
        border-collapse: collapse;
    }

    .rent-table thead {
        background-color: var(--light-gray);
        border-bottom: 2px solid var(--border-gray);
    }

    .rent-table thead th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        color: var(--text-dark);
        text-transform: uppercase;
    }

    .rent-table tbody tr {
        border-bottom: 1px solid var(--border-gray);
    }

    .rent-table tbody tr:hover {
        background-color: rgba(0, 82, 204, 0.02);
    }

    .rent-table tbody td {
        padding: 14px 15px;
        font-size: 14px;
        color: var(--text-dark);
    }

    .grn-no-link {
        color: var(--primary-blue);
        font-weight: 600;
        text-decoration: none;
    }

    .grn-no-link:hover {
        text-decoration: underline;
    }
    .details-link {
        color: #0052cc;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }

    .details-link:hover {
        text-decoration: underline;
    }

    .loading-row {
        text-align: center;
    }

    .loading-cell {
        text-align: center !important;
        vertical-align: middle !important;
        padding: 40px 15px !important;
    }


    .loading-cell p {
        margin: 0;
        text-align: center;
        width: 100%;
    }

    .spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto 10px;
        border: 3px solid var(--light-gray);
        border-top-color: var(--primary-blue);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .rent-cards-wrapper {
        display: none;
        gap: 15px;
        margin-bottom: 20px;
    }

    .rent-card {
        background: white;
        border-radius: 8px;
        padding: 15px;
        border-left: 4px solid var(--primary-blue);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .card-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding: 8px 0;
        border-bottom: 1px solid var(--light-gray);
    }

    .card-row:last-child {
        border-bottom: none;
    }

    .card-label {
        font-weight: 600;
        color: var(--text-light);
        font-size: 12px;
    }

    .card-value {
        color: var(--text-dark);
        font-size: 14px;
        font-weight: 500;
    }

    .loading-card {
        text-align: center;
        padding: 40px 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .grn-total-footer{
        background:#fff;
        padding:15px;
        margin-bottom:15px;
        border-radius:8px;
        box-shadow:0 2px 8px rgba(0,0,0,.1);
        font-size:15px;
        font-weight:600;
    }

    .grn-total-footer strong{
        color:#0052cc;
        font-size:18px;
    }

    .rent-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }


    .pagination-buttons {
        display: flex;
        align-items: center;
        gap: 8px;
    }


    .pagination-btn {
        padding: 8px 16px;
        background: var(--primary-blue);
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
    }


    @media(max-width:768px) {

        .rent-pagination {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }


        .pagination-info {
            width: 100%;
            text-align: center;
        }


        .pagination-buttons {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            gap: 8px;
            width: 100%;
        }


        .page-info {
            min-width: auto;
            white-space: nowrap;
        }


        .pagination-btn {
            padding: 8px 12px;
            white-space: nowrap;
        }

    }

    @media (max-width: 768px) {
        .rent-table-wrapper {
            display: none;
        }

        .rent-cards-wrapper {
            display: flex;
            flex-direction: column;
        }

        .filter-group {
            min-width: 100%;
        }

        #locationBadge {
            min-width: 100% !important;
        }
    }
</style>
@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let allData = [];
        let currentPage = 1;
        let pageSize = 10;
        let totalRecords = 0;
        let searchQuery = '';
        let activeSearchRequest = null;
        let filterDateFrom = '';
        let filterDateTo = '';
        let filterLocationID = '';
        let hasFilter = false;
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            filterDateFrom = '';
            filterDateTo = '';
            filterLocationID = '';
            hasFilter = false;

            // Location Change - Display Color Badge
            $('#locationSelect').on('change', function() {
                const selectedOption = this.options[this.selectedIndex];

                if (!selectedOption.value) {
                    $('#locationBadge').hide();
                    filterLocationID = '';
                } else {
                    const color = selectedOption.getAttribute('data-color');
                    const locationName = selectedOption.text;

                    $('#colorDot').css('backgroundColor', color);
                    $('#locationNameDisplay').text(locationName);
                    $('#colorCode').text(color);
                    $('#locationBadge').show();

                    filterLocationID = $(this).val();
                }

                filterDateFrom = $('#fromDate').val();
                filterDateTo = $('#toDate').val();
                currentPage = 1;
                loadGrnData();
            });

            $('#applyFilterBtn').on('click', function() {
                filterLocationID = $('#locationSelect').val();
                filterDateFrom = $('#fromDate').val();
                filterDateTo = $('#toDate').val();
                if (!filterLocationID && !filterDateFrom && !filterDateTo) {
                    alert('Please select Location or Date.');
                    return;
                }
                if (filterDateFrom && filterDateTo && filterDateFrom > filterDateTo) {
                    alert('From Date cannot be greater than To Date.');
                    return;
                }
                currentPage = 1;
                loadGrnData();
            });
            $('#resetFilterBtn').on('click', function() {
                $('#locationSelect').val('');
                $('#fromDate').val('');
                $('#toDate').val('');
                $('#searchInput').val('');
                $('#locationBadge').hide();
                filterLocationID = '';
                filterDateFrom = '';
                filterDateTo = '';
                searchQuery = '';
                currentPage = 1;
                totalRecords = 0;
                allData = [];
                hasFilter = false;
                showInitialMessage();
                updatePagination();
            });
            let searchTimeout;
            $('#searchInput').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchQuery = $(this).val().trim();
                if (!hasFilter) {
                    return;
                }
                searchTimeout = setTimeout(function() {
                    currentPage = 1;
                    loadGrnData();
                }, 650);
            });
            $('#prevBtn').on('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    loadGrnData();
                }
            });
            $('#nextBtn').on('click', function() {
                const totalPages = Math.ceil(totalRecords / pageSize);
                if (currentPage < totalPages) {
                    currentPage++;
                    loadGrnData();
                }
            });
        });

        function loadGrnData() {
            if (!filterLocationID && !filterDateFrom && !filterDateTo) {
                hasFilter = false;
                showInitialMessage();
                return;
            }
            if (filterDateFrom && filterDateTo && filterDateFrom > filterDateTo) {
                showError('From Date cannot be greater than To Date.');
                return;
            }
            hasFilter = true;
            showLoading();
            if (activeSearchRequest && activeSearchRequest.readyState !== 4) {
                activeSearchRequest.abort();
            }

            activeSearchRequest = $.ajax({
                url: '{{ route('grn.datatable') }}',
                type: 'GET',
                data: {
                    draw: currentPage,
                    start: (currentPage - 1) * pageSize,
                    length: pageSize,
                    'search[value]': searchQuery,
                    locationID: filterLocationID,
                    dateFrom: filterDateFrom,
                    dateTo: filterDateTo
                },
                success: function(response) {
                    console.log('GRN Response:', response);
                    if (response.success === false) {
                        showError(response.message || 'Unable to load GRN data.');
                        return;
                    }
                    allData = response.data || [];
                    totalRecords = parseInt(response.recordsFiltered ?? response.recordsTotal ?? 0);

                    $('#totalEntries').text(
                        response.totals?.totalEntries ?? totalRecords
                    );

                    renderTable();
                    renderCards();
                    updatePagination();
                },
                error: function(xhr, status) {
                    if (status === 'abort') {
                        return;
                    }
                    console.error('GRN AJAX Error:', xhr.responseText);
                    let message = 'Error loading GRN data.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    showError(message);
                }
            });
        }

        function renderTable() {
            const tbody = $('#rentTable tbody');
            tbody.empty();
            if (!allData.length) {
                tbody.html(`
            <tr>
                <td colspan="9"
                    style="text-align:center; padding:30px; color:#666;">
                    No GRN details found.
                </td>
            </tr>
        `);
                return;
            }
            allData.forEach(function(item) {
                const tr = $('<tr></tr>');
                tr.append(`<td>${safe(item.FloorName)}</td>`);
                tr.append(`<td>${formatDate(item.GrnDate ?? item.GRNDate ?? item.EntryDate ?? item.Date)}</td>`);
                const grnLinkId = item.GRNID ?? item.gmID ?? item.GMID;
                const grnNoLabel = safe(item.GmNo ?? item.GRNNo ?? item.EntryNo ?? item.GRNID);
                const grnDateVal = item.GrnDate ?? item.GRNDate ?? item.EntryDate ?? item.Date ?? '';
                const grnLinkUrl = grnLinkId ?
                    `{{ url('grn') }}/${encodeURIComponent(grnLinkId)}/view?locationID=${encodeURIComponent(filterLocationID)}&date=${encodeURIComponent(grnDateVal)}` :
                    '';

                // Show "Details" in the GRNID column instead of displaying the ID.
                // Clicking Details opens the existing GRN detail page.
                tr.append(grnLinkUrl
                    ? `<td><a href="${grnLinkUrl}" class="details-link">Details</a></td>`
                    : `<td>-</td>`);
                tr.append(`<td>${safe(item.Billing ?? item.BillNo ?? item.Billno)}</td>`);
                tr.append(`<td>${safe(item.Supplier ?? item.SupplierName ?? item.AccName)}</td>`);



                tr.append(`
            <td>
                ${amount(
                    item.GrnAmount ??
                    item.GmAmount ??
                    item.GRNAmount
                )}
            </td>
        `);



                tr.append(`
            <td>
                ${amount(
                    item.BillAmount ??
                    item.BillAmt
                )}
            </td>
        `);



                tr.append(`
            <td>
                ${amount(
                    item.TotMRP ??
                    item.TotalMRP
                )}
            </td>
        `);



                tr.append(`
            <td>
                ${safe(
                    item.GrnNo ??
                    item.GrnNo ??
                    item.GrnNo
                )}
            </td>
        `);



                tbody.append(tr);

            });

        }
        /*
        |--------------------------------------------------------------------------
        | MOBILE CARDS
        |--------------------------------------------------------------------------
        */
        function renderCards() {
            const container = $('#rentCardsContainer');
            container.empty();
            if (!allData.length) {
                container.html(`
            
						<div class="loading-card">
                No GRN details found.
            </div>
        `);
                return;
            }
            allData.forEach(function(item) {
                const grnLinkId = item.GRNID ?? item.gmID ?? item.GMID;
                const grnDateVal = item.GrnDate ?? item.GRNDate ?? item.EntryDate ?? item.Date ?? '';
                const grnLinkUrl = grnLinkId ?
                    `{{ url('grn') }}/${encodeURIComponent(grnLinkId)}/view?locationID=${encodeURIComponent(filterLocationID)}&date=${encodeURIComponent(grnDateVal)}` :
                    '';
                const detailsLink = grnLinkUrl
                    ? `<a href="${grnLinkUrl}" class="details-link">Details</a>`
                    : '-';
                const card = `

        
						<div class="rent-card">
							<div class="card-row">
								<span class="card-label">
                    Floor
                </span>
								<span class="card-value">
                    ${safe(item.FloorName)}
                </span>
							</div>
							<div class="card-row">
								<span class="card-label">
                    GRN Date
                </span>
								<span class="card-value">
                   ${formatDate(item.GrnDate)}
                </span>
							</div>
							<div class="card-row">
								<span class="card-label">
                    GRN No
                </span>
								<span class="card-value">
                    ${safe(
                        item.GmNo ??
                        item.GRNNo ??
                        item.EntryNo ??
                        item.GRNID
                    )}
                </span>
							</div>
							<div class="card-row">
								<span class="card-label">
                    Details
                </span>
								<span class="card-value">
                    ${detailsLink}
                </span>
							</div>
							<div class="card-row">
								<span class="card-label">
                    Billing
                </span>
								<span class="card-value">
                    ${safe(
                        item.Billing ??
                        item.BillNo ??
                        item.Billno
                    )}
                </span>
							</div>
							<div class="card-row">
								<span class="card-label">
                    Supplier
                </span>
								<span class="card-value">
                    ${safe(
                        item.Supplier ??
                        item.SupplierName ??
                        item.AccName
                    )}
                </span>
							</div>
							<div class="card-row">
								<span class="card-label">
                    GRN Amount
                </span>
								<span class="card-value">
                    ${amount(
                        item.GmAmount ??
                        item.GRNAmount ??
                        item.GmAmt ??
                        item.Amount
                    )}
                </span>
							</div>
							<div class="card-row">
								<span class="card-label">
                    Bill Amount
                </span>
								<span class="card-value">
                    ${amount(
                        item.BillAmount ??
                        item.BillAmt
                    )}
                </span>
							</div>
							<div class="card-row">
								<span class="card-label">
                    Total MRP
                </span>
								<span class="card-value">
                    ${amount(
                        item.TotMRP ??
                        item.TotalMRP
                    )}
                </span>
							</div>
							<div class="card-row">
								<span class="card-label">
                    GRN NO
                </span>
								<span class="card-value">
                    ${safe(
                        item.GrnNo ??
                        item.GrnNo ??
                        item.GrnNo
                    )}
                </span>
							</div>
						</div>

        `;
                container.append(card);
            });
        }
        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */
        function updatePagination() {
            const totalPages = Math.ceil(totalRecords / pageSize);
            const start = totalRecords === 0 ? 0 : (
                (currentPage - 1) * pageSize) + 1;
            const end = Math.min(currentPage * pageSize, totalRecords);
            $('#paginationInfo').text(`Showing ${start} to ${end} of ${totalRecords}`);
            $('#pageInfo').text(`Page ${currentPage} of ${
            Math.max(totalPages, 1)
        }`);
            $('#prevBtn').prop('disabled', !hasFilter || currentPage <= 1);
            $('#nextBtn').prop('disabled', !hasFilter || currentPage >= totalPages || totalPages === 0);
        }
        /*
        |--------------------------------------------------------------------------
        | INITIAL MESSAGE
        |--------------------------------------------------------------------------
        */
        function showInitialMessage() {
            $('#rentTable tbody').html(`

        
						<tr>
							<td
                colspan="9"
                class="loading-cell"
            >
								<p>
                    Please select Location or Date
                    to load GRN details.
                </p>
							</td>
						</tr>

    `);
            $('#rentCardsContainer').html(`

        
						<div class="loading-card">
							<p>
                Please select Location or Date
                to load GRN details.
            </p>
						</div>

    `);
        }
        /*
        |--------------------------------------------------------------------------
        | LOADING
        |--------------------------------------------------------------------------
        */
        function showLoading() {
            $('#rentTable tbody').html(`

        
						<tr>
							<td
                colspan="9"
                class="loading-cell"
            >
								<div class="spinner"></div>
								<p>
                    Loading GRN details...
                </p>
							</td>
						</tr>

    `);
            $('#rentCardsContainer').html(`

        
						<div class="loading-card">
							<div class="spinner"></div>
							<p>
                Loading GRN details...
            </p>
						</div>

    `);
        }
        /*
        |--------------------------------------------------------------------------
        | ERROR
        |--------------------------------------------------------------------------
        */
        function showError(message) {
            $('#rentTable tbody').html(`

        
						<tr>
							<td
                colspan="9"
                style="
                    color:#dc3545;
                    text-align:center;
                    padding:25px;
                "
            >

                ${safe(message)}

            </td>
						</tr>

    `);
            $('#rentCardsContainer').html(`

        
						<div
            class="loading-card"
            style="color:#dc3545;"
        >

            ${safe(message)}

        </div>

    `);
        }
        /*
        |--------------------------------------------------------------------------
        | DATE FORMAT
        |--------------------------------------------------------------------------
        */
        function formatDate(date) {
            if (!date) {
                return '-';
            }
            let d = new Date(date);
            if (isNaN(d)) {
                return date;
            }
            let day = String(d.getDate()).padStart(2, '0');
            let month = String(d.getMonth() + 1).padStart(2, '0');
            let year = d.getFullYear();
            return day + '-' + month + '-' + year;
        }
        /*
        |--------------------------------------------------------------------------
        | AMOUNT
        |--------------------------------------------------------------------------
        */
        function amount(value) {
            if (value === null || value === undefined || value === '') {
                return '0.00';
            }
            const number = parseFloat(value);
            if (isNaN(number)) {
                return safe(value);
            }
            return number.toFixed(2);
        }
        /*
        |--------------------------------------------------------------------------
        | SAFE HTML
        |--------------------------------------------------------------------------
        */
        function safe(value) {
            if (value === null || value === undefined || value === '') {
                return '-';
            }
            return String(value).replace(/&/g, '&amp;').replace(/ < /g,
                '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }
    </script>
    @endpush @endsection
