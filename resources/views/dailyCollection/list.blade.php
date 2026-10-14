@extends('layouts.app')
@section('title', 'Daily Collection')

@section('content')
<div class="dc-container">

    <!-- Header -->
    <div class="dc-header">
        <div>
            <h2 class="dc-title">Daily Collection</h2>
            <span class="dc-updated" id="dcUpdated"></span>
        </div>
        <button type="button" class="dc-refresh-btn" id="dcRefreshBtn">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>

    <!-- Summary -->
    <div class="dc-summary">
        <div class="dc-summary-box dc-total">
            <span class="lbl">Total Collection</span>
            <strong id="sumTotal">&#8377;0.00</strong>
        </div>
        <div class="dc-summary-box">
            <span class="lbl">Cash</span>
            <strong id="sumCash">&#8377;0.00</strong>
        </div>
        <div class="dc-summary-box">
            <span class="lbl">Credit Card</span>
            <strong id="sumCard">&#8377;0.00</strong>
        </div>
        <div class="dc-summary-box">
            <span class="lbl">Cheque</span>
            <strong id="sumCheque">&#8377;0.00</strong>
        </div>
    </div>

    <!-- Search + Reset -->
    <div class="dc-search-row">
        <div class="dc-search">
            <input type="text" id="dcSearch" placeholder="Search location...">
            <i class="bi bi-search"></i>
        </div>
        <button type="button" class="dc-reset-btn" id="dcResetBtn">
            <i class="bi bi-x-circle"></i> Reset
        </button>
    </div>

    <!-- Table (desktop) -->
    <div class="dc-table-wrapper">
        <div class="dc-table-scroll">
            <table class="dc-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Location</th>
                        <th class="num">Cash</th>
                        <th class="num">Credit Card</th>
                        <th class="num">Cheque</th>
                        <th class="num">Total Amount</th>
                        <th class="num">Disc %</th>
                    </tr>
                </thead>
                <tbody id="dcTableBody">
                    <tr>
                        <td colspan="7" class="dc-state">
                            <div class="spinner"></div>
                            <p>Loading Daily Collection...</p>
                        </td>
                    </tr>
                </tbody>
                <tfoot id="dcTableFoot"></tfoot>
            </table>
        </div>
    </div>

    <!-- Cards (mobile) -->
    <div class="dc-cards" id="dcCards">
        <div class="dc-card dc-state">
            <div class="spinner"></div>
            <p>Loading Daily Collection...</p>
        </div>
    </div>
</div>

<style>
    :root {
        --primary-blue: #0052cc;
        --light-gray: #f5f5f5;
        --border-gray: #ddd;
        --text-dark: #333;
        --text-light: #666;
    }

    .dc-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #f8f9fa 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .dc-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .dc-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .dc-updated {
        font-size: 12px;
        color: var(--text-light);
    }

    .dc-refresh-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        background: var(--primary-blue);
        color: #fff;
        border: none;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }

    .dc-refresh-btn:hover {
        background: #0045b3;
    }

    .dc-refresh-btn:disabled {
        opacity: 0.6;
        cursor: wait;
    }

    .dc-summary {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .dc-summary-box {
        background: #fff;
        border-radius: 8px;
        padding: 14px 16px;
        border-left: 4px solid #9ca3af;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .dc-summary-box.dc-total {
        border-left-color: var(--primary-blue);
    }

    .dc-summary-box .lbl {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-light);
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .dc-summary-box strong {
        font-size: 20px;
        color: var(--text-dark);
    }

    .dc-search-row {
        display: flex;
        gap: 12px;
        align-items: stretch;
        margin-bottom: 20px;
    }

    .dc-search {
        position: relative;
        flex: 1;
    }

    .dc-reset-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0 20px;
        background: #6c757d;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }

    .dc-reset-btn:hover {
        background: #5a6268;
    }

    .dc-search input {
        width: 100%;
        padding: 12px 15px 12px 40px;
        border: 1px solid var(--border-gray);
        border-radius: 6px;
        font-size: 14px;
    }

    .dc-search i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-light);
    }

    .dc-table-wrapper {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .dc-table-scroll {
        width: 100%;
        overflow-x: auto;
    }

    .dc-table {
        width: 100%;
        border-collapse: collapse;
    }

    .dc-table th {
        background: var(--light-gray);
        padding: 15px;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        text-align: left;
        white-space: nowrap;
        color: var(--text-dark);
        border-bottom: 2px solid var(--border-gray);
    }

    .dc-table td {
        padding: 14px 15px;
        font-size: 14px;
        color: var(--text-dark);
        border-bottom: 1px solid var(--border-gray);
        white-space: nowrap;
    }

    .dc-table tbody tr:hover {
        background-color: rgba(0, 82, 204, 0.03);
    }

    .dc-table .num {
        text-align: right;
    }

    .dc-table tfoot td {
        background: #eef3fb;
        font-weight: 700;
        border-top: 2px solid var(--border-gray);
    }

    .dc-loc {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .dc-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #ddd;
        flex: 0 0 12px;
    }

    .dc-zero {
        color: #9ca3af;
    }

    .dc-state {
        text-align: center !important;
        padding: 40px 15px !important;
        color: var(--text-light);
        white-space: normal !important;
    }

    .dc-error {
        color: #dc3545 !important;
    }

    .spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto 10px;
        border: 3px solid var(--light-gray);
        border-top-color: var(--primary-blue);
        border-radius: 50%;
        animation: dc-spin 0.8s linear infinite;
    }

    @keyframes dc-spin {
        to {
            transform: rotate(360deg);
        }
    }

    .dc-cards {
        display: none;
        flex-direction: column;
        gap: 15px;
    }

    .dc-card {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        border-left: 4px solid var(--primary-blue);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .dc-card-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
        color: var(--text-dark);
    }

    .dc-card-row {
        display: flex;
        justify-content: space-between;
        padding: 7px 0;
        border-bottom: 1px solid var(--light-gray);
        font-size: 14px;
    }

    .dc-card-row:last-child {
        border-bottom: none;
    }

    .dc-card-row .lbl {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-light);
    }

    .dc-card-row .val {
        font-weight: 500;
        color: var(--text-dark);
    }

    .dc-card.dc-card-total {
        background: #eef3fb;
    }

    @media (max-width: 992px) {
        .dc-summary {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .dc-title {
            font-size: 22px;
        }

        .dc-refresh-btn {
            width: 100%;
            justify-content: center;
        }

        .dc-search-row {
            flex-direction: column;
        }

        .dc-reset-btn {
            padding: 10px 20px;
            justify-content: center;
        }

        .dc-table-wrapper {
            display: none;
        }

        .dc-cards {
            display: flex;
        }
    }
</style>

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let dcRows = [];
        let dcRequest = null;

        $(function() {
            loadDailyCollection();

            $('#dcRefreshBtn').on('click', loadDailyCollection);

            // Reset: clear search and load fresh data
            $('#dcResetBtn').on('click', function() {
                $('#dcSearch').val('');
                loadDailyCollection();
            });

            // Search is done in the browser (only a few locations)
            let searchTimer;
            $('#dcSearch').on('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(renderAll, 150);
            });
        });

        function loadDailyCollection() {
            if (dcRequest && dcRequest.readyState !== 4) {
                dcRequest.abort();
            }

            $('#dcRefreshBtn').prop('disabled', true);
            showState('<div class="spinner"></div><p>Loading Daily Collection...</p>');

            dcRequest = $.ajax({
                url: '{{ route('dailyCollection.data') }}',
                type: 'GET',
                success: function(response) {
                    if (response.success === false) {
                        showState(safe(response.message || 'Unable to load Daily Collection data.'), true);
                        return;
                    }
                    dcRows = response.data || [];
                    $('#dcUpdated').text(response.updatedAt ? 'Last updated: ' + response.updatedAt : '');
                    renderAll();
                },
                error: function(xhr, status) {
                    if (status === 'abort') return;
                    let message = 'Error loading Daily Collection data (HTTP ' + xhr.status + ').';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    showState(safe(message), true);
                },
                complete: function() {
                    $('#dcRefreshBtn').prop('disabled', false);
                }
            });
        }

        function filteredRows() {
            const q = $('#dcSearch').val().trim().toLowerCase();
            if (!q) return dcRows;
            return dcRows.filter(function(r) {
                return String(r.Location || '').toLowerCase().indexOf(q) !== -1;
            });
        }

        function totalsOf(rows) {
            const t = { Cash: 0, CreditCard: 0, Cheque: 0, TAmount: 0 };
            rows.forEach(function(r) {
                t.Cash += parseFloat(r.Cash) || 0;
                t.CreditCard += parseFloat(r.CreditCard) || 0;
                t.Cheque += parseFloat(r.Cheque) || 0;
                t.TAmount += parseFloat(r.TAmount) || 0;
            });
            return t;
        }

        function renderAll() {
            const rows = filteredRows();
            const t = totalsOf(rows);

            $('#sumTotal').html('&#8377;' + money(t.TAmount));
            $('#sumCash').html('&#8377;' + money(t.Cash));
            $('#sumCard').html('&#8377;' + money(t.CreditCard));
            $('#sumCheque').html('&#8377;' + money(t.Cheque));

            if (!rows.length) {
                showState(dcRows.length ? 'No location matches your search.' : 'No Daily Collection data found.');
                return;
            }

            // Table
            let body = '';
            rows.forEach(function(r, i) {
                body += `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${locationCell(r)}</td>
                        <td class="num">${amountCell(r.Cash)}</td>
                        <td class="num">${amountCell(r.CreditCard)}</td>
                        <td class="num">${amountCell(r.Cheque)}</td>
                        <td class="num"><strong>${amountCell(r.TAmount)}</strong></td>
                        <td class="num">${pct(r.DiscPCT)}</td>
                    </tr>`;
            });
            $('#dcTableBody').html(body);
            $('#dcTableFoot').html(`
                <tr>
                    <td></td>
                    <td>TOTAL (${rows.length})</td>
                    <td class="num">${money(t.Cash)}</td>
                    <td class="num">${money(t.CreditCard)}</td>
                    <td class="num">${money(t.Cheque)}</td>
                    <td class="num">${money(t.TAmount)}</td>
                    <td class="num">-</td>
                </tr>`);

            // Cards
            let cards = '';
            rows.forEach(function(r) {
                cards += `
                    <div class="dc-card" style="border-left-color:${locColor(r)}">
                        <div class="dc-card-title">${locationCell(r)}</div>
                        <div class="dc-card-row"><span class="lbl">Cash</span><span class="val">${amountCell(r.Cash)}</span></div>
                        <div class="dc-card-row"><span class="lbl">Credit Card</span><span class="val">${amountCell(r.CreditCard)}</span></div>
                        <div class="dc-card-row"><span class="lbl">Cheque</span><span class="val">${amountCell(r.Cheque)}</span></div>
                        <div class="dc-card-row"><span class="lbl">Total Amount</span><span class="val"><strong>${amountCell(r.TAmount)}</strong></span></div>
                        <div class="dc-card-row"><span class="lbl">Disc %</span><span class="val">${pct(r.DiscPCT)}</span></div>
                    </div>`;
            });
            cards += `
                <div class="dc-card dc-card-total">
                    <div class="dc-card-title">TOTAL (${rows.length})</div>
                    <div class="dc-card-row"><span class="lbl">Cash</span><span class="val">${money(t.Cash)}</span></div>
                    <div class="dc-card-row"><span class="lbl">Credit Card</span><span class="val">${money(t.CreditCard)}</span></div>
                    <div class="dc-card-row"><span class="lbl">Cheque</span><span class="val">${money(t.Cheque)}</span></div>
                    <div class="dc-card-row"><span class="lbl">Total Amount</span><span class="val"><strong>${money(t.TAmount)}</strong></span></div>
                </div>`;
            $('#dcCards').html(cards);
        }

        // Location colour (from Locations page) as a dot before the code
        function locColor(r) {
            return /^#[0-9A-Fa-f]{3,6}$/.test(r.LocationColor || '') ? r.LocationColor : '#9CA3AF';
        }

        function locationCell(r) {
            const title = r.LocationName ? ` title="${safe(r.LocationName)}"` : '';
            return `<span class="dc-loc"${title}><span class="dc-dot" style="background-color:${locColor(r)}"></span><strong>${safe(r.Location)}</strong></span>`;
        }

        function showState(html, isError) {
            const cls = isError ? 'dc-state dc-error' : 'dc-state';
            $('#dcTableBody').html(`<tr><td colspan="7" class="${cls}">${html}</td></tr>`);
            $('#dcTableFoot').empty();
            $('#dcCards').html(`<div class="dc-card ${cls}">${html}</div>`);
        }

        // Indian number format: 1,52,539.00
        function money(value) {
            const n = parseFloat(value) || 0;
            return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function amountCell(value) {
            const n = parseFloat(value) || 0;
            return n === 0 ? '<span class="dc-zero">0.00</span>' : money(n);
        }

        function pct(value) {
            const n = parseFloat(value) || 0;
            return n === 0 ? '<span class="dc-zero">0.00%</span>' : n.toFixed(2) + '%';
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
@endpush
@endsection