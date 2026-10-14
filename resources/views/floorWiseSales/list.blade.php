@extends('layouts.app')
@section('title', 'Floor Wise Sales')

@section('content')
<div class="fs-container">

    <div class="fs-header">
        <h2 class="fs-title">Floor Wise Sales</h2>
        <span class="fs-sub" id="fsSub"></span>
    </div>

    <!-- Filters -->
    <div class="fs-filters">
        <div class="fs-group">
            <label for="fsLocation">Location</label>
            <select id="fsLocation" class="fs-input">
                <option value="">-- Select Location --</option>
                @foreach ($locations as $loc)
                    <option value="{{ $loc['id'] }}" data-color="{{ $loc['color'] ?? '#9CA3AF' }}">{{ $loc['name'] }}</option>
                @endforeach
            </select>
        </div>
        <!-- Location Color Badge -->
        <div class="fs-badge-wrap" id="fsBadge" style="display:none;">
            <span class="fs-badge">
                <span class="fs-dot" id="fsDot"></span>
                <span id="fsBadgeName"></span>
                <span class="fs-badge-code" id="fsBadgeCode"></span>
            </span>
        </div>
        <div class="fs-group">
            <label for="fsFrom">From Date</label>
            <input type="date" id="fsFrom" class="fs-input" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="fs-group">
            <label for="fsTo">To Date</label>
            <input type="date" id="fsTo" class="fs-input" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="fs-actions">
            <button type="button" class="fs-btn fs-btn-primary" id="fsApply">Apply Filter</button>
            <button type="button" class="fs-btn fs-btn-grey" id="fsReset">Reset</button>
        </div>
    </div>

    <!-- Summary -->
    <div class="fs-summary">
        <div class="fs-card fs-card-blue"><span class="lbl">Net Sales</span><strong id="sumNet">&#8377;0.00</strong></div>
        <div class="fs-card"><span class="lbl">Gross Amount</span><strong id="sumGross">&#8377;0.00</strong></div>
        <div class="fs-card"><span class="lbl">Discount</span><strong id="sumDisc">&#8377;0.00</strong></div>
        <div class="fs-card"><span class="lbl">Tax</span><strong id="sumTax">&#8377;0.00</strong></div>
    </div>

    <!-- Table (desktop) -->
    <div class="fs-table-wrapper">
        <div class="fs-table-scroll">
            <table class="fs-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Floor</th>
                        <th class="num">Gross Amt</th>
                        <th class="num" title="Items sold at 40% or more discount">Gross Amt (40%+ Disc)</th>
                        <th class="num">Discount</th>
                        <th class="num">Add. Discount</th>
                        <th class="num">Tax</th>
                        <th class="num">Net Amount</th>
                        <th class="num">Share</th>
                    </tr>
                </thead>
                <tbody id="fsBody">
                    <tr><td colspan="9" class="fs-state">Please select Location to load Floor Wise Sales.</td></tr>
                </tbody>
                <tfoot id="fsFoot"></tfoot>
            </table>
        </div>
    </div>

    <!-- Cards (mobile) -->
    <div class="fs-cards" id="fsCards">
        <div class="fs-mcard fs-state">Please select Location to load Floor Wise Sales.</div>
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

    .fs-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #f8f9fa 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .fs-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }

    .fs-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .fs-sub {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-light);
    }

    .fs-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .fs-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        min-width: 170px;
    }

    .fs-group label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-dark);
    }

    .fs-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border-gray);
        border-radius: 4px;
        font-size: 14px;
        background: #fff;
    }

    .fs-input:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
    }

    .fs-actions {
        display: flex;
        gap: 10px;
    }

    .fs-badge-wrap {
        align-self: flex-end;
    }

    .fs-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: var(--light-gray);
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }

    .fs-dot {
        display: inline-block;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid #ddd;
        background: #9CA3AF;
    }

    .fs-badge-code {
        color: #999;
        font-size: 12px;
    }

    .fs-btn {
        padding: 10px 18px;
        border: none;
        border-radius: 4px;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }

    .fs-btn:disabled { opacity: 0.6; cursor: wait; }
    .fs-btn-primary { background: var(--primary-blue); }
    .fs-btn-grey { background: #6c757d; }

    .fs-summary {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .fs-card {
        background: #fff;
        border-radius: 8px;
        padding: 14px 16px;
        border-left: 4px solid #9ca3af;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .fs-card-blue { border-left-color: var(--primary-blue); }

    .fs-card .lbl {
        display: block;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-light);
        margin-bottom: 4px;
    }

    .fs-card strong {
        font-size: 20px;
        color: var(--text-dark);
    }

    .fs-table-wrapper {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .fs-table-scroll {
        width: 100%;
        overflow-x: auto;
    }

    .fs-table {
        width: 100%;
        border-collapse: collapse;
    }

    .fs-table th {
        background: var(--light-gray);
        padding: 14px 15px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        text-align: left;
        white-space: nowrap;
        border-bottom: 2px solid var(--border-gray);
    }

    .fs-table td {
        padding: 12px 15px;
        font-size: 14px;
        color: var(--text-dark);
        border-bottom: 1px solid var(--border-gray);
        white-space: nowrap;
    }

    .fs-table tbody tr:hover { background: rgba(0, 82, 204, 0.03); }
    .fs-table .num { text-align: right; }

    .fs-table tfoot td {
        background: #eef3fb;
        font-weight: 700;
        border-top: 2px solid var(--border-gray);
    }

    .fs-share {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        justify-content: flex-end;
    }

    .fs-bar {
        width: 60px;
        height: 6px;
        background: #e5e7eb;
        border-radius: 3px;
        overflow: hidden;
    }

    .fs-bar span {
        display: block;
        height: 100%;
        background: var(--primary-blue);
    }

    .fs-neg { color: #c62828; }
    .fs-zero { color: #9ca3af; }

    .fs-state {
        text-align: center !important;
        padding: 40px 15px !important;
        color: var(--text-light);
        white-space: normal !important;
    }

    .fs-error { color: #dc3545 !important; }

    .spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto 10px;
        border: 3px solid var(--light-gray);
        border-top-color: var(--primary-blue);
        border-radius: 50%;
        animation: fs-spin 0.8s linear infinite;
    }

    @keyframes fs-spin { to { transform: rotate(360deg); } }

    .fs-cards {
        display: none;
        flex-direction: column;
        gap: 15px;
    }

    .fs-mcard {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        border-left: 4px solid var(--primary-blue);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .fs-mcard.total { background: #eef3fb; }

    .fs-mcard-title {
        display: flex;
        justify-content: space-between;
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .fs-mrow {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        border-bottom: 1px solid var(--light-gray);
        font-size: 14px;
    }

    .fs-mrow:last-child { border-bottom: none; }

    .fs-mrow .lbl {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-light);
    }

    @media (max-width: 992px) {
        .fs-summary { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
        .fs-group { min-width: 100%; }
        .fs-badge-wrap { width: 100%; }
        .fs-actions { width: 100%; }
        .fs-actions .fs-btn { flex: 1; }
        .fs-table-wrapper { display: none; }
        .fs-cards { display: flex; }
    }
</style>

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const FS_TODAY = '{{ now()->format('Y-m-d') }}';
        let fsRequest = null;

        $(function() {
            // Only one location allowed -> select it and load straight away
            if ($('#fsLocation option').length === 2) {
                $('#fsLocation').prop('selectedIndex', 1);
                updateBadge();
                loadFloorSales();
            }

            $('#fsLocation').on('change', function() {
                updateBadge();
                if ($(this).val()) {
                    loadFloorSales();
                } else {
                    resetView();
                }
            });

            $('#fsApply').on('click', function() {
                if (!$('#fsLocation').val()) {
                    alert('Please select Location.');
                    return;
                }
                if ($('#fsFrom').val() > $('#fsTo').val()) {
                    alert('From Date cannot be greater than To Date.');
                    return;
                }
                loadFloorSales();
            });

            $('#fsReset').on('click', function() {
                $('#fsLocation').val('');
                $('#fsBadge').hide();
                $('#fsFrom').val(FS_TODAY);
                $('#fsTo').val(FS_TODAY);
                resetView();
            });
        });

        // Location colour badge (same as Gate Entry)
        function updateBadge() {
            const opt = $('#fsLocation option:selected');
            if (!opt.val()) {
                $('#fsBadge').hide();
                return;
            }
            const color = opt.data('color') || '#9CA3AF';
            $('#fsDot').css('background-color', color);
            $('#fsBadgeName').text(opt.text().trim());
            $('#fsBadgeCode').text(color);
            $('#fsBadge').show();
        }

        function resetView() {
            if (fsRequest && fsRequest.readyState !== 4) fsRequest.abort();
            setSummary({ NetAmt: 0, GrossAmt: 0, DisAmount: 0, AddDiscAmt: 0, TaxAmt: 0 });
            $('#fsSub').text('');
            showState('Please select Location to load Floor Wise Sales.');
        }

        function loadFloorSales() {
            if (fsRequest && fsRequest.readyState !== 4) fsRequest.abort();

            $('#fsApply').prop('disabled', true);
            showState('<div class="spinner"></div><p>Loading Floor Wise Sales...</p>');

            fsRequest = $.ajax({
                url: '{{ route('floorWiseSales.data') }}',
                type: 'GET',
                timeout: 180000,
                data: {
                    locationID: $('#fsLocation').val(),
                    dateFrom: $('#fsFrom').val(),
                    dateTo: $('#fsTo').val()
                },
                success: function(res) {
                    if (res.success === false) {
                        showState(safe(res.message || 'Unable to load data.'), true);
                        return;
                    }
                    const loc = $('#fsLocation option:selected').text().trim();
                    const period = res.dateFrom === res.dateTo
                        ? fmtDate(res.dateFrom)
                        : fmtDate(res.dateFrom) + ' to ' + fmtDate(res.dateTo);
                    $('#fsSub').text(loc + ' | ' + period);
                    setSummary(res.totals || {});
                    render(res.data || [], res.totals || {});
                },
                error: function(xhr, status) {
                    if (status === 'abort') return;
                    let msg = status === 'timeout'
                        ? 'The report took too long. Please try a smaller date range.'
                        : 'Error loading Floor Wise Sales (HTTP ' + xhr.status + ').';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    showState(safe(msg), true);
                },
                complete: function() {
                    $('#fsApply').prop('disabled', false);
                }
            });
        }

        function setSummary(t) {
            $('#sumNet').html('&#8377;' + money(t.NetAmt));
            $('#sumGross').html('&#8377;' + money((parseFloat(t.GrossAmt) || 0) + (parseFloat(t.GrossAmt1) || 0)));
            $('#sumDisc').html('&#8377;' + money((parseFloat(t.DisAmount) || 0) + (parseFloat(t.AddDiscAmt) || 0)));
            $('#sumTax').html('&#8377;' + money(t.TaxAmt));
        }

        function render(rows, t) {
            if (!rows.length) {
                showState('No sales found for the selected location and dates.');
                return;
            }

            const netTotal = parseFloat(t.NetAmt) || 0;
            let body = '';
            let cards = '';

            rows.forEach(function(r, i) {
                const share = netTotal > 0 ? Math.max(0, (r.NetAmt / netTotal) * 100) : 0;

                body += `
                    <tr>
                        <td>${i + 1}</td>
                        <td><strong>${safe(r.FloorName)}</strong></td>
                        <td class="num">${cell(r.GrossAmt)}</td>
                        <td class="num">${cell(r.GrossAmt1)}</td>
                        <td class="num">${cell(r.DisAmount)}</td>
                        <td class="num">${cell(r.AddDiscAmt)}</td>
                        <td class="num">${cell(r.TaxAmt)}</td>
                        <td class="num"><strong>${cell(r.NetAmt)}</strong></td>
                        <td class="num">
                            <span class="fs-share">
                                <span class="fs-bar"><span style="width:${Math.min(share, 100).toFixed(1)}%"></span></span>
                                ${share.toFixed(1)}%
                            </span>
                        </td>
                    </tr>`;

                cards += `
                    <div class="fs-mcard">
                        <div class="fs-mcard-title"><span>${safe(r.FloorName)}</span><span>${cell(r.NetAmt)}</span></div>
                        <div class="fs-mrow"><span class="lbl">Gross Amt</span><span>${cell(r.GrossAmt)}</span></div>
                        <div class="fs-mrow"><span class="lbl">Gross Amt (40%+ Disc)</span><span>${cell(r.GrossAmt1)}</span></div>
                        <div class="fs-mrow"><span class="lbl">Discount</span><span>${cell(r.DisAmount)}</span></div>
                        <div class="fs-mrow"><span class="lbl">Add. Discount</span><span>${cell(r.AddDiscAmt)}</span></div>
                        <div class="fs-mrow"><span class="lbl">Tax</span><span>${cell(r.TaxAmt)}</span></div>
                        <div class="fs-mrow"><span class="lbl">Share</span><span>${share.toFixed(1)}%</span></div>
                    </div>`;
            });

            $('#fsBody').html(body);
            $('#fsFoot').html(`
                <tr>
                    <td></td>
                    <td>TOTAL</td>
                    <td class="num">${money(t.GrossAmt)}</td>
                    <td class="num">${money(t.GrossAmt1)}</td>
                    <td class="num">${money(t.DisAmount)}</td>
                    <td class="num">${money(t.AddDiscAmt)}</td>
                    <td class="num">${money(t.TaxAmt)}</td>
                    <td class="num">${money(t.NetAmt)}</td>
                    <td class="num">100%</td>
                </tr>`);

            cards += `
                <div class="fs-mcard total">
                    <div class="fs-mcard-title"><span>TOTAL</span><span>${money(t.NetAmt)}</span></div>
                    <div class="fs-mrow"><span class="lbl">Gross Amt</span><span>${money(t.GrossAmt)}</span></div>
                    <div class="fs-mrow"><span class="lbl">Gross Amt (40%+ Disc)</span><span>${money(t.GrossAmt1)}</span></div>
                    <div class="fs-mrow"><span class="lbl">Discount</span><span>${money(t.DisAmount)}</span></div>
                    <div class="fs-mrow"><span class="lbl">Add. Discount</span><span>${money(t.AddDiscAmt)}</span></div>
                    <div class="fs-mrow"><span class="lbl">Tax</span><span>${money(t.TaxAmt)}</span></div>
                </div>`;
            $('#fsCards').html(cards);
        }

        function showState(html, isError) {
            const cls = isError ? 'fs-state fs-error' : 'fs-state';
            $('#fsBody').html(`<tr><td colspan="9" class="${cls}">${html}</td></tr>`);
            $('#fsFoot').empty();
            $('#fsCards').html(`<div class="fs-mcard ${cls}">${html}</div>`);
        }

        // Indian format: 1,52,539.00 ; negatives (returns) in red
        function money(v) {
            const n = parseFloat(v) || 0;
            return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function cell(v) {
            const n = parseFloat(v) || 0;
            if (n === 0) return '<span class="fs-zero">0.00</span>';
            return n < 0 ? `<span class="fs-neg">${money(n)}</span>` : money(n);
        }

        function fmtDate(d) {
            const m = String(d || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
            return m ? m[3] + '-' + m[2] + '-' + m[1] : d;
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