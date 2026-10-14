@extends('layouts.app') @section('title', 'Gate Entry') @section('content') <div class="rent-container">
    <div class="rent-header">
        <h2 class="rent-title">Gate Entry Details</h2>
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
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search across all fields...">
        <i class="bi bi-search"></i>
    </div>
    <!-- Progressive loading status -->
    <div class="load-progress" id="loadProgress" style="display:none;">
        <div class="load-progress-bar"><span id="loadProgressBar"></span></div>
        <span class="load-progress-text" id="loadProgressText"></span>
    </div>
    <!-- Table -->
    <div class="rent-table-wrapper">
        <div class="table-scroll">
            <table class="rent-table" id="rentTable">
                <thead>
                    <tr id="tableHeadRow">
                        <th>GATE ENTRY DETAILS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="initial-row">
                        <td colspan="1" class="loading-cell">
                            <p>Please select Location to load Gate Entry details.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Mobile Cards -->
    <div class="rent-cards-wrapper" id="rentCardsContainer">
        <div class="loading-card">
            <p>Please select Location to load Gate Entry details.</p>
        </div>
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

    .rent-table thead th,
    .rent-table tbody td {
        white-space: nowrap;
    }

    .rent-table thead th.num-col,
    .rent-table tbody td.num-col {
        text-align: right;
    }

    .rent-table tbody td {
        padding: 14px 15px;
        font-size: 14px;
        color: var(--text-dark);
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
        text-align: right;
        word-break: break-word;
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
    /* ---------- Progressive loading ---------- */
    .load-progress {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #eef4ff;
        border: 1px solid #cfe0ff;
        border-radius: 6px;
        padding: 10px 14px;
        margin: -10px 0 20px;
        font-size: 13px;
        color: var(--text-dark);
    }

    .load-progress-bar {
        flex: 0 0 180px;
        height: 6px;
        background: #d6e4ff;
        border-radius: 3px;
        overflow: hidden;
    }

    .load-progress-bar span {
        display: block;
        height: 100%;
        width: 0;
        background: var(--primary-blue);
        transition: width 0.2s;
    }

    .load-progress.warn {
        background: #fff4e5;
        border-color: #ffd8a8;
    }

    @media (max-width: 768px) {
        .load-progress {
            flex-direction: column;
            align-items: stretch;
        }

        .load-progress-bar {
            flex-basis: auto;
        }
    }

    /* ---------- GRN link + modal ---------- */
    .grn-link {
        color: var(--primary-blue);
        font-weight: 600;
        text-decoration: underline;
        cursor: pointer;
    }

    .grn-link:hover {
        color: #003d99;
    }
</style>
@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let allData = [];        // rows of the current page (used by renderTable/renderCards)
        let currentPage = 1;
        let pageSize = 10;
        let totalRecords = 0;
        let searchQuery = '';
        let activeSearchRequest = null;
        let filterDateFrom = '';
        let filterDateTo = '';
        let filterLocationID = '';
        let hasFilter = false;
        const DEFAULT_DAYS = 30;

        function toInputDate(d) {
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return d.getFullYear() + '-' + m + '-' + day;
        }

        function setDefaultDates() {
            const to = new Date();
            const from = new Date();
            from.setDate(from.getDate() - DEFAULT_DAYS);
            $('#fromDate').val(toInputDate(from));
            $('#toDate').val(toInputDate(to));
        }

        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Dates start empty (user picks them); restored only when coming back from details page
            $('#fromDate').val('');
            $('#toDate').val('');

            // Coming back from the details page -> restore the same filters
            const qs = new URLSearchParams(window.location.search);
            if (qs.get('dateFrom')) $('#fromDate').val(qs.get('dateFrom'));
            if (qs.get('dateTo')) $('#toDate').val(qs.get('dateTo'));
            if (qs.get('locationID') && $('#locationSelect option[value="' + qs.get('locationID') + '"]').length) {
                $('#locationSelect').val(qs.get('locationID'));
                setTimeout(function() { $('#locationSelect').trigger('change'); }, 0);
            }

            // Location change - Display Color Badge + load data
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

                if (filterLocationID) {
                    loadGrnData();
                } else {
                    hasFilter = false;
                    showInitialMessage();
                }
            });

            // Apply Filter button
            $('#applyFilterBtn').on('click', function() {
                filterLocationID = $('#locationSelect').val();
                filterDateFrom = $('#fromDate').val();
                filterDateTo = $('#toDate').val();

                if (!filterLocationID) {
                    alert('Please select Location.');
                    return;
                }

                if (filterDateFrom && filterDateTo && filterDateFrom > filterDateTo) {
                    alert('From Date cannot be greater than To Date.');
                    return;
                }

                currentPage = 1;
                loadGrnData();
            });

            // Reset Filter
            $('#resetFilterBtn').on('click', function() {
                loadToken++;
                abortMonthRequests();
                isLoadingMore = false;
                entries = [];
                $('#loadProgress').hide();
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

            // Search - runs in the browser, no server call
            let searchTimeout;
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                searchQuery = $(this).val().trim();
                if (!hasFilter) {
                    return;
                }
                searchTimeout = setTimeout(function() {
                    currentPage = 1;
                    applyClientView();
                }, 200);
            });

            // Pagination - instant, no server call
            $('#prevBtn').on('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    applyClientView();
                }
            });

            $('#nextBtn').on('click', function() {
                const totalPages = Math.ceil(totalRecords / pageSize);
                if (currentPage < totalPages) {
                    currentPage++;
                    applyClientView();
                }
            });
        });

        /* ================================================================
         |  PROGRESSIVE LOADING (month by month)
         |  The stored procedure is fast for one month (~2s) but very slow for
         |  many years. So the page asks the server for ONE month at a time,
         |  newest month first, and shows rows as soon as the first month
         |  arrives. Older months keep loading in the background.
         * ================================================================ */
        const BASE_MONTH = '{{ substr(\App\Models\GateEntry::BASE_DATE_FROM, 0, 7) }}';
        const PARALLEL_REQUESTS = 3;
        const monthCache = {};      // "loc|YYYY-MM" -> rows (kept for this visit)
        let entries = [];           // [{ r: row, t: searchText, d: sortDate }]
        let loadToken = 0;          // increases on every new load -> old loads stop
        let isLoadingMore = false;  // true while older months are still loading
        let monthRequests = [];

        function ymOf(dateStr) {
            return String(dateStr).substring(0, 7);
        }

        function currentYm() {
            const d = new Date();
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        }

        function nextYm(ym) {
            let y = parseInt(ym.substring(0, 4), 10);
            let m = parseInt(ym.substring(5, 7), 10) + 1;
            if (m > 12) { m = 1; y++; }
            return y + '-' + String(m).padStart(2, '0');
        }

        // Months to load for the selected dates, NEWEST FIRST
        function monthsToLoad() {
            let from = filterDateFrom ? ymOf(filterDateFrom) : BASE_MONTH;
            let to = filterDateTo ? ymOf(filterDateTo) : currentYm();
            if (from < BASE_MONTH) from = BASE_MONTH;
            if (to > currentYm()) to = currentYm();
            const list = [];
            for (let ym = from; ym <= to; ym = nextYm(ym)) list.push(ym);
            return list.reverse();
        }

        function dateColOf(cols) {
            return cols.find(function(c) { return /date/i.test(c); }) || null;
        }

        function inSelectedRange(row, dateCol) {
            if (!dateCol) return true;
            const d = String(row[dateCol] || '').substring(0, 10);
            if (!d) return true;
            if (filterDateFrom && d < filterDateFrom) return false;
            if (filterDateTo && d > filterDateTo) return false;
            return true;
        }

        function makeEntry(row, dateCol) {
            let text = '';
            for (const key in row) {
                const v = row[key];
                if (v !== null && v !== undefined) text += ' ' + String(v).toLowerCase();
                if (v && /date/i.test(key)) text += ' ' + formatDate(v);
            }
            return { r: row, t: text, d: dateCol ? String(row[dateCol] || '') : '' };
        }

        // Add one month's rows, keep newest first, refresh the visible page
        function addMonthRows(rows, respColumns) {
            if (!columns.length) {
                const cols = (respColumns && respColumns.length) ? respColumns : (rows.length ? Object.keys(rows[0]) : []);
                if (cols.length) columns = moveDetailsAfterEntryNo(cols);
            }
            const dateCol = dateColOf(columns);
            rows.forEach(function(row) {
                if (inSelectedRange(row, dateCol)) entries.push(makeEntry(row, dateCol));
            });
            if (dateCol) {
                entries.sort(function(a, b) { return a.d < b.d ? 1 : (a.d > b.d ? -1 : 0); });
            }
        }

        function applyClientView() {
            const q = searchQuery.toLowerCase();
            const list = q ? entries.filter(function(e) { return e.t.indexOf(q) !== -1; }) : entries;

            totalRecords = list.length;
            const totalPages = Math.max(Math.ceil(totalRecords / pageSize), 1);
            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * pageSize;
            allData = list.slice(start, start + pageSize).map(function(e) { return e.r; });

            renderTable();
            renderCards();
            updatePagination();
        }

        function showProgress(done, total, failed, finished) {
            const box = $('#loadProgress');
            if (finished && !failed) {
                box.hide();
                return;
            }
            const pct = total ? Math.round((done / total) * 100) : 0;
            $('#loadProgressBar').css('width', pct + '%');
            box.toggleClass('warn', finished && failed > 0);
            if (finished) {
                $('#loadProgressText').html(`${failed} month(s) could not be loaded. <a href="#" id="retryFailed">Retry</a>`);
            } else {
                $('#loadProgressText').text(`Loading older data... ${done} of ${total} months loaded (${entries.length} records so far). You can search and browse meanwhile.`);
            }
            box.show();
        }

        function abortMonthRequests() {
            monthRequests.forEach(function(x) { if (x && x.readyState !== 4) x.abort(); });
            monthRequests = [];
        }

        function fetchMonth(ym, token) {
            const key = filterLocationID + '|' + ym;
            if (monthCache[key]) {
                return $.Deferred().resolve({ columns: [], data: monthCache[key] }).promise();
            }
            const xhr = $.ajax({
                url: '{{ route('gateEntry.datatable') }}',
                type: 'GET',
                data: { month: ym, locationID: filterLocationID },
                timeout: 120000
            });
            monthRequests.push(xhr);
            return xhr.then(function(response) {
                if (token !== loadToken) return $.Deferred().reject('stale').promise();
                if (response.success === false) {
                    return $.Deferred().reject(response.message || 'error').promise();
                }
                monthCache[key] = response.data || [];
                return response;
            });
        }

        // Show the "Details" (GRN ID) column right after "Entry No"
        function moveDetailsAfterEntryNo(cols) {
            const grnCol = cols.find(function(c) { return isGrnCol(c); });
            const entryCol = cols.find(function(c) {
                return String(c).toLowerCase().replace(/[^a-z0-9]/g, '') === 'entryno';
            });
            if (!grnCol || !entryCol) return cols;
            const result = cols.filter(function(c) { return c !== grnCol; });
            result.splice(result.indexOf(entryCol) + 1, 0, grnCol);
            return result;
        }

        function loadGrnData(onlyMonths) {
            if (!filterLocationID) {
                hasFilter = false;
                showInitialMessage();
                return;
            }

            if (filterDateFrom && filterDateTo && filterDateFrom > filterDateTo) {
                showError('From Date cannot be greater than To Date.');
                return;
            }

            hasFilter = true;
            const token = ++loadToken;
            abortMonthRequests();

            const queue = onlyMonths ? onlyMonths.slice() : monthsToLoad();
            if (!onlyMonths) {
                entries = [];
                columns = [];
                currentPage = 1;
            }

            const total = queue.length;
            let done = 0;
            let failed = [];
            let firstShown = false;

            isLoadingMore = total > 0;
            if (!total) {
                applyClientView();
                return;
            }
            if (!onlyMonths) showLoading();

            function finishOne() {
                done++;
                if (token !== loadToken) return;
                isLoadingMore = done < total;
                // First month on screen immediately; later months refresh the view
                if (!firstShown || done % 3 === 0 || done === total) {
                    firstShown = true;
                    applyClientView();
                }
                showProgress(done, total, failed.length, done === total);
            }

            function worker() {
                if (token !== loadToken || !queue.length) return;
                const ym = queue.shift();
                fetchMonth(ym, token).then(function(response) {
                    if (token !== loadToken) return;
                    addMonthRows(response.data || [], response.columns || []);
                    finishOne();
                    worker();
                }, function(err, status) {
                    if (token !== loadToken || status === 'abort' || err === 'stale') return;
                    failed.push(ym);
                    finishOne();
                    worker();
                });
            }

            for (let i = 0; i < Math.min(PARALLEL_REQUESTS, total); i++) worker();

            // Retry button for failed months
            $(document).off('click.retryFailed').on('click.retryFailed', '#retryFailed', function(e) {
                e.preventDefault();
                const retry = failed.slice();
                failed = [];
                loadGrnData(retry);
            });
        }

        // ---------- Dynamic columns (from ProcWHGoodsDetails) ----------
        let columns = [];

        function colCount() {
            return Math.max(columns.length, 1);
        }

        // "GmNo" -> "Gm No", "BillAmount" -> "Bill Amount", "GRNNo" -> "GRN No"
        function humanize(col) {
            return String(col)
                .replace(/_/g, ' ')
                .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
                .replace(/([A-Z]+)([A-Z][a-z])/g, '$1 $2')
                .trim();
        }

        function isDateCol(col) {
            return /date/i.test(col);
        }

        function isAmountCol(col) {
            return /amount|amt|mrp|rate|price|cost|value|total|tax|gst|disc/i.test(col);
        }

        function formatCell(col, value) {
            if (value === null || value === undefined || value === '') return '-';
            if (isDateCol(col)) return formatDate(value);
            if (isAmountCol(col) && value !== '' && !isNaN(value)) return amount(value);
            return safe(value);
        }

        function renderHeader() {
            const row = $('#tableHeadRow');
            row.empty();
            if (!columns.length) {
                row.append('<th>GATE ENTRY DETAILS</th>');
                return;
            }
            columns.forEach(function(col) {
                const cls = isAmountCol(col) ? ' class="num-col"' : '';
                row.append(`<th${cls}>${safe(colLabel(col))}</th>`);
            });
        }

        function renderTable() {
            renderHeader();
            const tbody = $('#rentTable tbody');
            tbody.empty();
            if (!allData.length) {
                tbody.html(`
                    <tr>
                        <td colspan="${colCount()}" style="text-align:center; padding:30px; color:#666;">
                            ${isLoadingMore ? 'Loading Gate Entry details...' : 'No Gate Entry details found.'}
                        </td>
                    </tr>
                `);
                return;
            }

            allData.forEach(function(item, idx) {
                const tr = $('<tr></tr>');
                columns.forEach(function(col) {
                    const cls = isAmountCol(col) ? ' class="num-col"' : '';
                    tr.append(`<td${cls}>${cellHtml(col, item, idx)}</td>`);
                });
                tbody.append(tr);
            });
        }

        function renderCards() {
            const container = $('#rentCardsContainer');
            container.empty();

            if (!allData.length) {
                container.html(`
                    <div class="loading-card">
                        ${isLoadingMore ? 'Loading Gate Entry details...' : 'No Gate Entry details found.'}
                    </div>
                `);
                return;
            }

            allData.forEach(function(item, idx) {
                let rows = '';
                columns.forEach(function(col) {
                    rows += `
                        <div class="card-row">
                            <span class="card-label">${safe(colLabel(col))}</span>
                            <span class="card-value">${cellHtml(col, item, idx)}</span>
                        </div>`;
                });
                container.append(`<div class="rent-card">${rows}</div>`);
            });
        }

        function updatePagination() {
            const totalPages = Math.ceil(totalRecords / pageSize);
            const start = totalRecords === 0 ? 0 : ((currentPage - 1) * pageSize) + 1;
            const end = Math.min(currentPage * pageSize, totalRecords);

            $('#paginationInfo').text(`Showing ${start} to ${end} of ${totalRecords}`);
            $('#pageInfo').text(`Page ${currentPage} of ${Math.max(totalPages, 1)}`);
            $('#prevBtn').prop('disabled', !hasFilter || currentPage <= 1);
            $('#nextBtn').prop('disabled', !hasFilter || currentPage >= totalPages || totalPages === 0);
        }

        function showInitialMessage() {
            columns = [];
            renderHeader();
            $('#rentTable tbody').html(`
                <tr>
                    <td colspan="1" class="loading-cell">
                        <p>Please select Location to load Gate Entry details.</p>
                    </td>
                </tr>
            `);
            $('#rentCardsContainer').html(`
                <div class="loading-card">
                    <p>Please select Location to load Gate Entry details.</p>
                </div>
            `);
        }

        function showLoading() {
            $('#rentTable tbody').html(`
                <tr>
                    <td colspan="${colCount()}" class="loading-cell">
                        <div class="spinner"></div>
                        <p>Loading Gate Entry details...</p>
                    </td>
                </tr>
            `);
            $('#rentCardsContainer').html(`
                <div class="loading-card">
                    <div class="spinner"></div>
                    <p>Loading Gate Entry details...</p>
                </div>
            `);
        }

        function showError(message) {
            $('#rentTable tbody').html(`
                <tr>
                    <td colspan="${colCount()}" style="color:#dc3545; text-align:center; padding:25px;">
                        ${safe(message)}
                    </td>
                </tr>
            `);
            $('#rentCardsContainer').html(`
                <div class="loading-card" style="color:#dc3545;">
                    ${safe(message)}
                </div>
            `);
        }

        // ---------- GRN ID click -> Gate Entry details page ----------
        const detailsBaseUrl = '{{ url('gateEntry/details') }}';

        function isGrnCol(col) {
            // Location 8 returns the GRN id as "gmID", others as "GrnID"
            return ['grnid', 'gmid'].indexOf(String(col).toLowerCase().replace(/[^a-z0-9]/g, '')) !== -1;
        }

        function detailsUrl(grnId) {
            const params = new URLSearchParams();
            params.set('locationID', filterLocationID);
            if (filterDateFrom) params.set('dateFrom', filterDateFrom);
            if (filterDateTo) params.set('dateTo', filterDateTo);
            return detailsBaseUrl + '/' + encodeURIComponent(grnId) + '?' + params.toString();
        }

        // GRN ID column is shown as "Details"
        function colLabel(col) {
            return isGrnCol(col) ? 'Details' : humanize(col);
        }

        function cellHtml(col, item, idx) {
            const value = item[col];
            if (isGrnCol(col) && value !== null && value !== undefined && value !== '' && String(value) !== '0') {
                return `<a href="${detailsUrl(value)}" class="grn-link" title="View details (GRN ${safe(value)})">Details</a>`;
            }
            return formatCell(col, value);
        }

        function formatDate(date) {
            if (!date) return '-';
            // SQL Server strings like "2026-09-01 00:00:00.000" - parse without timezone shifts
            const m = String(date).match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (m) return m[3] + '-' + m[2] + '-' + m[1];
            let d = new Date(date);
            if (isNaN(d)) return date;
            let day = String(d.getDate()).padStart(2, '0');
            let month = String(d.getMonth() + 1).padStart(2, '0');
            let year = d.getFullYear();
            return day + '-' + month + '-' + year;
        }

        function amount(value) {
            if (value === null || value === undefined || value === '') return '0.00';
            const number = parseFloat(value);
            if (isNaN(number)) return safe(value);
            return number.toFixed(2);
        }

        function safe(value) {
            if (value === null || value === undefined || value === '') return '-';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
    @endpush @endsection