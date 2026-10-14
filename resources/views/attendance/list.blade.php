@extends('layouts.app')
@section('title', 'Attendance')

@section('content')
<div class="at-container">

    <div class="at-header">
        <h2 class="at-title">Attendance</h2>
        <span class="at-date">Today: {{ now()->format('d-m-Y') }}</span>
    </div>

    <!-- Filters -->
    <div class="at-filters">
        <div class="at-filter-group">
            <label for="atLocation">Location</label>
            <select id="atLocation" class="at-input">
                @if (count($locations) !== 1)
                    <option value="">-- All Locations --</option>
                @endif
                @foreach ($locations as $loc)
                    <option value="{{ $loc['id'] }}" data-color="{{ $loc['color'] ?? '#9CA3AF' }}">{{ $loc['name'] }}</option>
                @endforeach
            </select>
        </div>
        <!-- Location Color Badge -->
        <div class="at-badge-wrap" id="atBadge" style="display:none;">
            <span class="at-loc-badge">
                <span class="at-dot" id="atDot"></span>
                <span id="atBadgeName"></span>
                <span class="at-badge-code" id="atBadgeCode"></span>
            </span>
        </div>
        <div class="at-filter-group">
            <label for="atToday">Attendance</label>
            <select id="atToday" class="at-input">
                <option value="present" selected>Present</option>
                <option value="absent">Absent</option>
                <option value="late">Late</option>
                <option value="all">All Employees</option>
            </select>
        </div>
        <div class="at-filter-group at-search-group">
            <label for="atSearch">Search</label>
            <div class="at-search">
                <i class="bi bi-search"></i>
                <input type="text" id="atSearch" class="at-input" placeholder="Name, mobile number or employee code...">
            </div>
        </div>
        <div class="at-filter-actions">
            <button type="button" class="at-btn at-btn-primary" id="atApply">Apply Filter</button>
            <button type="button" class="at-btn at-btn-grey" id="atReset">Reset</button>
        </div>
    </div>

    <!-- Summary -->
    <div class="at-summary">
        <div class="at-card at-card-blue at-card-click" data-today="all" title="Show all employees">
            <span class="lbl">Total Employees</span>
            <strong id="sumTotal">0</strong>
        </div>
        <div class="at-card at-card-green at-card-click" data-today="present" title="Show present">
            <span class="lbl">Present Today</span>
            <strong id="sumPresent">0</strong>
        </div>
        <div class="at-card at-card-red at-card-click" data-today="absent" title="Show absent">
            <span class="lbl">Absent Today</span>
            <strong id="sumAbsent">0</strong>
        </div>
        <div class="at-card at-card-orange at-card-click" data-today="late" title="Show late">
            <span class="lbl">Late</span>
            <strong id="sumLate">0</strong>
        </div>
    </div>

    <!-- Table (desktop) -->
    <div class="at-table-wrapper">
        <div class="at-table-scroll">
            <table class="at-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Employee</th>
                        <th>Mobile</th>
                        <th>Location</th>
                        <th class="center">Today</th>
                        <th class="num">Present</th>
                        <th class="num">Absent</th>
                        <th class="num">Late</th>
                        <th class="num">Half Day</th>
                    </tr>
                </thead>
                <tbody id="atBody">
                    <tr><td colspan="10" class="at-state"><div class="spinner"></div><p>Loading attendance...</p></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cards (mobile) -->
    <div class="at-cards" id="atCards">
        <div class="at-mcard at-state"><div class="spinner"></div><p>Loading attendance...</p></div>
    </div>

    <!-- Pagination -->
    <div class="at-pagination">
        <span id="atInfo">Showing 0 of 0</span>
        <div class="at-pages">
            <button type="button" class="at-btn at-btn-primary" id="atPrev">&larr; Previous</button>
            <span id="atPageInfo">Page 1 of 1</span>
            <button type="button" class="at-btn at-btn-primary" id="atNext">Next &rarr;</button>
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

    .at-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #f8f9fa 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .at-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }

    .at-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .at-date {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-light);
    }

    .at-filters {
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

    .at-filter-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        min-width: 170px;
    }

    .at-search-group {
        flex: 2;
        min-width: 240px;
    }

    .at-filter-group label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-dark);
    }

    .at-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border-gray);
        border-radius: 4px;
        font-size: 14px;
        background: #fff;
    }

    .at-input:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
    }

    .at-search {
        position: relative;
    }

    .at-search i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-light);
    }

    .at-search .at-input {
        padding-left: 36px;
    }

    .at-filter-actions {
        display: flex;
        gap: 10px;
    }

    .at-badge-wrap {
        align-self: flex-end;
    }

    .at-loc-badge {
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

    .at-dot {
        display: inline-block;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid #ddd;
        background: #9CA3AF;
        flex: 0 0 14px;
    }

    .at-dot-sm {
        width: 10px;
        height: 10px;
        border-width: 1px;
        flex-basis: 10px;
        margin-right: 6px;
        vertical-align: middle;
    }

    .at-badge-code {
        color: #999;
        font-size: 12px;
    }

    .at-btn {
        padding: 10px 18px;
        border: none;
        border-radius: 4px;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }

    .at-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .at-btn-primary {
        background: var(--primary-blue);
    }

    .at-btn-grey {
        background: #6c757d;
    }

    .at-summary {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .at-card {
        background: #fff;
        border-radius: 8px;
        padding: 14px 16px;
        border-left: 4px solid;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .at-card .lbl {
        display: block;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-light);
        margin-bottom: 4px;
    }

    .at-card strong {
        font-size: 22px;
        color: var(--text-dark);
    }

    .at-card-blue { border-left-color: var(--primary-blue); }
    .at-card-green { border-left-color: #2e7d32; }
    .at-card-red { border-left-color: #d32f2f; }
    .at-card-orange { border-left-color: #ef6c00; }

    /* Selected filter card */
    .at-card-click.active {
        background: #f3f7ff;
        box-shadow: 0 0 0 2px rgba(0, 82, 204, 0.25), 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .at-card-click {
        cursor: pointer;
    }

    .at-card-click:hover {
        background: #fafcff;
    }

    .at-table-wrapper {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .at-table-scroll {
        width: 100%;
        overflow-x: auto;
    }

    .at-table {
        width: 100%;
        border-collapse: collapse;
    }

    .at-table th {
        background: var(--light-gray);
        padding: 14px 15px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        text-align: left;
        white-space: nowrap;
        border-bottom: 2px solid var(--border-gray);
    }

    .at-table td {
        padding: 12px 15px;
        font-size: 14px;
        color: var(--text-dark);
        border-bottom: 1px solid var(--border-gray);
        white-space: nowrap;
        vertical-align: middle;
    }

    .at-table tbody tr:hover {
        background: rgba(0, 82, 204, 0.03);
    }

    .at-table .num { text-align: right; }
    .at-table .center { text-align: center; }

    .at-emp {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
    }

    .at-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #e7efff;
        color: var(--primary-blue);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        flex: 0 0 34px;
    }

    .at-phone {
        color: var(--primary-blue);
        text-decoration: none;
    }

    .at-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
    }

    .at-badge.present { background: #e6f4ea; color: #2e7d32; }
    .at-badge.absent { background: #fdecea; color: #c62828; }

    .at-red { color: #c62828; font-weight: 600; }
    .at-orange { color: #e65100; font-weight: 600; }
    .at-zero { color: #9ca3af; }

    .at-state {
        text-align: center !important;
        padding: 40px 15px !important;
        color: var(--text-light);
        white-space: normal !important;
    }

    .at-error { color: #dc3545 !important; }

    .spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto 10px;
        border: 3px solid var(--light-gray);
        border-top-color: var(--primary-blue);
        border-radius: 50%;
        animation: at-spin 0.8s linear infinite;
    }

    @keyframes at-spin { to { transform: rotate(360deg); } }

    .at-cards {
        display: none;
        flex-direction: column;
        gap: 15px;
        margin-bottom: 20px;
    }

    .at-mcard {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        border-left: 4px solid var(--primary-blue);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .at-mcard-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .at-mcard-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        border-bottom: 1px solid var(--light-gray);
        font-size: 14px;
    }

    .at-mcard-row:last-child { border-bottom: none; }

    .at-mcard-row .lbl {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-light);
    }

    .at-mcard-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
        margin-top: 8px;
        text-align: center;
    }

    .at-mcard-stats div {
        background: var(--light-gray);
        border-radius: 6px;
        padding: 6px 2px;
        font-size: 11px;
        color: var(--text-light);
    }

    .at-mcard-stats strong {
        display: block;
        font-size: 15px;
        color: var(--text-dark);
    }

    .at-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 15px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .at-pages {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    @media (max-width: 768px) {
        .at-filter-group,
        .at-search-group {
            min-width: 100%;
        }

        .at-filter-actions {
            width: 100%;
        }

        .at-badge-wrap {
            width: 100%;
        }

        .at-filter-actions .at-btn {
            flex: 1;
        }

        .at-summary {
            grid-template-columns: 1fr 1fr;
        }

        .at-table-wrapper {
            display: none;
        }

        .at-cards {
            display: flex;
        }

        .at-pagination {
            flex-direction: column;
        }
    }
</style>

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let atPage = 1;
        let atTotalPages = 1;
        const atPerPage = 20;
        let atRequest = null;

        $(function() {
            updateBadge();
            loadAttendance();

            $('#atLocation').on('change', updateBadge);

            // Location / Today change -> load immediately
            $('#atLocation, #atToday').on('change', function() {
                atPage = 1;
                loadAttendance();
            });

            $('#atApply').on('click', function() {
                atPage = 1;
                loadAttendance();
            });

            // Search while typing (name / phone / code)
            let t;
            $('#atSearch').on('input', function() {
                clearTimeout(t);
                t = setTimeout(function() {
                    atPage = 1;
                    loadAttendance();
                }, 350);
            });

            $('#atSearch').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(t);
                    atPage = 1;
                    loadAttendance();
                }
            });

            $('#atReset').on('click', function() {
                $('#atLocation').prop('selectedIndex', 0);
                updateBadge();
                $('#atToday').val('present');
                $('#atSearch').val('');
                atPage = 1;
                loadAttendance();
            });

            // Clicking Present / Absent card filters the list
            $('.at-card-click').on('click', function() {
                $('#atToday').val($(this).data('today'));
                atPage = 1;
                loadAttendance();
            });

            $('#atPrev').on('click', function() {
                if (atPage > 1) {
                    atPage--;
                    loadAttendance();
                }
            });

            $('#atNext').on('click', function() {
                if (atPage < atTotalPages) {
                    atPage++;
                    loadAttendance();
                }
            });
        });

        // Location colour badge (same as Gate Entry)
        function updateBadge() {
            const opt = $('#atLocation option:selected');
            if (!opt.val()) {
                $('#atBadge').hide();
                return;
            }
            const color = opt.data('color') || '#9CA3AF';
            $('#atDot').css('background-color', color);
            $('#atBadgeName').text(opt.text().trim());
            $('#atBadgeCode').text(color);
            $('#atBadge').show();
        }

        function locationCell(r) {
            const color = /^#[0-9A-Fa-f]{3,6}$/.test(r.LocationColor || '') ? r.LocationColor : '#9CA3AF';
            return `<span class="at-dot at-dot-sm" style="background-color:${color}"></span>${safe(r.LocationName)}`;
        }

        function loadAttendance() {
            // highlight the card of the selected filter
            $('.at-card-click').removeClass('active');
            $(`.at-card-click[data-today="${$('#atToday').val()}"]`).addClass('active');

            if (atRequest && atRequest.readyState !== 4) {
                atRequest.abort();
            }

            showState('<div class="spinner"></div><p>Loading attendance...</p>');

            atRequest = $.ajax({
                url: '{{ route('attendance.data') }}',
                type: 'GET',
                data: {
                    locationID: $('#atLocation').val(),
                    today: $('#atToday').val(),
                    search: $('#atSearch').val().trim(),
                    page: atPage,
                    perPage: atPerPage
                },
                success: function(res) {
                    if (res.success === false) {
                        showState(safe(res.message || 'Unable to load attendance data.'), true);
                        return;
                    }

                    $('#sumTotal').text(res.summary.total);
                    $('#sumPresent').text(res.summary.presentToday);
                    $('#sumAbsent').text(res.summary.absentToday);
                    $('#sumLate').text(res.summary.late || 0);

                    atTotalPages = res.totalPages || 1;
                    render(res.data || []);
                    updatePagination(res.recordsFiltered || 0);
                },
                error: function(xhr, status) {
                    if (status === 'abort') return;
                    let msg = 'Error loading attendance (HTTP ' + xhr.status + ').';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    showState(safe(msg), true);
                    updatePagination(0);
                }
            });
        }

        function render(rows) {
            if (!rows.length) {
                showState('No employees found.');
                return;
            }

            let body = '';
            let cards = '';

            rows.forEach(function(r) {
                const badge = r.Today === 1
                    ? '<span class="at-badge present">Present</span>'
                    : '<span class="at-badge absent">Absent</span>';

                body += `
                    <tr>
                        <td>${r.SrNo}</td>
                        <td>${safe(r.EmployeeCode)}</td>
                        <td><div class="at-emp"><span class="at-avatar">${initials(r.EmployeeName)}</span>${safe(r.EmployeeName)}</div></td>
                        <td>${phoneLink(r.MobileNo)}</td>
                        <td>${locationCell(r)}</td>
                        <td class="center">${badge}</td>
                        <td class="num">${num(r.TotPresent)}</td>
                        <td class="num">${num(r.TotAbsent, 'at-red')}</td>
                        <td class="num">${num(r.TotLate, 'at-orange')}</td>
                        <td class="num">${num(r.TotHalfday, 'at-orange')}</td>
                    </tr>`;

                cards += `
                    <div class="at-mcard">
                        <div class="at-mcard-head">
                            <div class="at-emp"><span class="at-avatar">${initials(r.EmployeeName)}</span>${safe(r.EmployeeName)}</div>
                            ${badge}
                        </div>
                        <div class="at-mcard-row"><span class="lbl">Code</span><span>${safe(r.EmployeeCode)}</span></div>
                        <div class="at-mcard-row"><span class="lbl">Mobile</span><span>${phoneLink(r.MobileNo)}</span></div>
                        <div class="at-mcard-row"><span class="lbl">Location</span><span>${locationCell(r)}</span></div>
                        <div class="at-mcard-stats">
                            <div><strong>${r.TotPresent}</strong>Present</div>
                            <div><strong>${r.TotAbsent}</strong>Absent</div>
                            <div><strong>${r.TotLate}</strong>Late</div>
                            <div><strong>${r.TotHalfday}</strong>Half Day</div>
                        </div>
                    </div>`;
            });

            $('#atBody').html(body);
            $('#atCards').html(cards);
        }

        function updatePagination(total) {
            const start = total === 0 ? 0 : (atPage - 1) * atPerPage + 1;
            const end = Math.min(atPage * atPerPage, total);
            $('#atInfo').text(`Showing ${start} to ${end} of ${total}`);
            $('#atPageInfo').text(`Page ${atPage} of ${atTotalPages}`);
            $('#atPrev').prop('disabled', atPage <= 1);
            $('#atNext').prop('disabled', atPage >= atTotalPages);
        }

        function showState(html, isError) {
            const cls = isError ? 'at-state at-error' : 'at-state';
            $('#atBody').html(`<tr><td colspan="10" class="${cls}">${html}</td></tr>`);
            $('#atCards').html(`<div class="at-mcard ${cls}">${html}</div>`);
        }

        // 918700877245 -> +91 87008 77245 (click to call)
        function phoneLink(mobile) {
            const d = String(mobile || '').replace(/\D/g, '');
            if (!d) return '-';
            let shown = d;
            if (d.length === 12 && d.startsWith('91')) {
                shown = '+91 ' + d.substring(2, 7) + ' ' + d.substring(7);
            } else if (d.length === 10) {
                shown = d.substring(0, 5) + ' ' + d.substring(5);
            }
            return `<a class="at-phone" href="tel:+${d.length === 10 ? '91' + d : d}">${safe(shown)}</a>`;
        }

        function initials(name) {
            const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
            if (!parts.length) return '?';
            return safe((parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase());
        }

        function num(v, cls) {
            const n = parseInt(v, 10) || 0;
            if (n === 0) return '<span class="at-zero">0</span>';
            return cls ? `<span class="${cls}">${n}</span>` : String(n);
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