@extends('layouts.app')
@section('title', 'Document Download')

@section('content')
<div class="dd-container">

    <div class="dd-header">
        <h2 class="dd-title">Document Download</h2>
        <span class="dd-sub" id="ddSub"></span>
    </div>

    <!-- Filters -->
    <div class="dd-filters">
        <div class="dd-group">
            <label for="ddLocation">Location</label>
            <select id="ddLocation" class="dd-input">
                <option value="">-- Select Location --</option>
                @foreach ($locations as $loc)
                    <option value="{{ $loc['id'] }}" data-color="{{ $loc['color'] ?? '#9CA3AF' }}">{{ $loc['name'] }}</option>
                @endforeach
            </select>
        </div>

        <!-- Location Color Badge -->
        <div class="dd-badge-wrap" id="ddBadge" style="display:none;">
            <span class="dd-badge">
                <span class="dd-dot" id="ddDot"></span>
                <span id="ddBadgeName"></span>
            </span>
        </div>

        <div class="dd-group">
            <label for="ddDocType">Document Type</label>
            <select id="ddDocType" class="dd-input">
                <option value="">-- Select Type --</option>
                @foreach ($docTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="dd-group">
            <label for="ddFrom">From Date</label>
            <input type="date" id="ddFrom" class="dd-input" value="{{ now()->startOfMonth()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
        </div>

        <div class="dd-group">
            <label for="ddTo">To Date</label>
            <input type="date" id="ddTo" class="dd-input" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
        </div>


        <div class="dd-actions">
            <button type="button" class="dd-btn dd-btn-primary" id="ddApply">Apply Filter</button>
            <button type="button" class="dd-btn dd-btn-grey" id="ddReset">Reset</button>
        </div>
    </div>

    <!-- Table (desktop) -->
    <div class="dd-table-wrapper">
        <div class="dd-table-scroll">
            <table class="dd-table">
                <thead id="ddHead">
                    <tr>
                        <th>#</th>
                        <th>Doc No</th>
                        <th>Date</th>
                        <th>Party / Salesman</th>
                        <th class="num">Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="ddBody">
                    <tr><td colspan="20" class="dd-state">Select a Location and Document Type, then Apply Filter.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cards (mobile) -->
    <div class="dd-cards" id="ddCards">
        <div class="dd-mcard dd-state">Select a Location and Document Type, then Apply Filter.</div>
    </div>

    <!-- Pagination -->
    <div class="dd-pagination" id="ddPagination" style="display:none;">
        <div class="dd-pagination-info">
            <span id="ddPaginationInfo">Showing 0 of 0</span>
        </div>
        <div class="dd-pagination-buttons">
            <button type="button" class="dd-pagination-btn" id="ddPrevBtn">&larr; Previous</button>
            <span class="dd-page-info" id="ddPageInfo">Page 0 of 0</span>
            <button type="button" class="dd-pagination-btn" id="ddNextBtn">Next &rarr;</button>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div id="ddModalOverlay" class="dd-modal-overlay" style="display:none;">
    <div class="dd-modal">
        <div class="dd-modal-header">
            <h3 id="ddModalTitle">Document Detail</h3>
            <button type="button" class="dd-modal-close" onclick="ddCloseModal()">&times;</button>
        </div>
        <div class="dd-modal-body" id="ddModalBody">
            <div class="dd-state">Loading...</div>
        </div>
    </div>
</div>

<style>
    :root {
        --dd-primary: #0052cc;
        --dd-light-gray: #f5f5f5;
        --dd-border: #ddd;
        --dd-text-dark: #333;
        --dd-text-light: #666;
    }

    .dd-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #f8f9fa 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .dd-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }

    .dd-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--dd-text-dark);
        margin: 0;
    }

    .dd-sub {
        font-size: 13px;
        font-weight: 600;
        color: var(--dd-text-light);
    }

    .dd-filters {
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

    .dd-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        min-width: 170px;
    }

    .dd-group label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--dd-text-dark);
    }

    .dd-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--dd-border);
        border-radius: 4px;
        font-size: 14px;
        background: #fff;
    }

    .dd-input:focus {
        outline: none;
        border-color: var(--dd-primary);
        box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
    }

    .dd-badge-wrap { align-self: flex-end; margin-bottom: 2px; }

    .dd-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 12px;
        background: #f5f5f5;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
    }

    .dd-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #9CA3AF;
        border: 2px solid #ddd;
    }

    .dd-actions { display: flex; gap: 10px; }

    .dd-btn {
        padding: 10px 18px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }

    .dd-btn-primary { background: var(--dd-primary); color: #fff; }
    .dd-btn-primary:hover { background: #0045b3; }
    .dd-btn-grey { background: #eee; color: var(--dd-text-dark); }
    .dd-btn-grey:hover { background: #ddd; }

    .dd-table-wrapper {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        display: none;
    }
    .dd-table-wrapper.show { display: block; }

    .dd-table-scroll { overflow-x: auto; }

    .dd-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .dd-table th {
        background: var(--dd-primary);
        color: #fff;
        padding: 12px;
        text-align: left;
        font-size: 12px;
        white-space: nowrap;
    }
    .dd-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #eee;
    }
    .dd-table tr:hover td { background: #f9fbff; }
    .dd-table .num { text-align: right; }
    .dd-state { text-align: center; padding: 30px; color: var(--dd-text-light); }

    .dd-spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto 10px;
        border: 3px solid var(--dd-light-gray);
        border-top-color: var(--dd-primary);
        border-radius: 50%;
        animation: dd-spin 0.8s linear infinite;
    }
    @keyframes dd-spin {
        to { transform: rotate(360deg); }
    }
    .dd-loading-cell { text-align: center !important; padding: 40px 15px !important; }
    .dd-loading-cell p, .dd-loading-card p { margin: 0; color: var(--dd-text-light); }

    .dd-doc-link {
        color: var(--dd-primary);
        text-decoration: none;
        font-weight: 600;
        cursor: pointer;
    }
    .dd-doc-link:hover { text-decoration: underline; }

    .dd-view-btn {
        background: var(--dd-primary);
        color: #fff;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
    }
    .dd-view-btn:hover { background: #0045b3; }

    .dd-cards { display: none; }
    .dd-mcard {
        background: #fff;
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 10px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        font-size: 13px;
    }
    .dd-mcard .row { display: flex; justify-content: space-between; padding: 4px 0; }
    .dd-mcard .lbl { color: var(--dd-text-light); }

    @media (max-width: 768px) {
        .dd-table-wrapper { display: none !important; }
        .dd-cards.show { display: block; }
    }

    /* Pagination */
    .dd-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        background: #fff;
        border-radius: 8px;
        padding: 12px 16px;
        margin-top: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    .dd-pagination-info { font-size: 13px; color: var(--dd-text-light); }
    .dd-pagination-buttons { display: flex; align-items: center; gap: 12px; }
    .dd-pagination-btn {
        padding: 8px 14px;
        border: 1px solid var(--dd-border);
        background: #fff;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
    }
    .dd-pagination-btn:hover:not(:disabled) { background: var(--dd-light-gray); }
    .dd-pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .dd-page-info { font-size: 13px; color: var(--dd-text-dark); font-weight: 600; }

    /* Modal */
    .dd-modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.5);
        display: flex; align-items: center; justify-content: center;
        z-index: 2000; padding: 20px;
    }
    .dd-modal {
        background: #fff; border-radius: 10px; width: 100%; max-width: 900px;
        max-height: 85vh; display: flex; flex-direction: column; overflow: hidden;
    }
    .dd-modal-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 16px 20px; border-bottom: 1px solid #eee;
    }
    .dd-modal-header h3 { margin: 0; font-size: 18px; color: var(--dd-text-dark); }
    .dd-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }
    .dd-modal-body { padding: 16px 20px; overflow-y: auto; }

    .dd-warning {
        font-size: 12px; color: #b45309; background: #fffbeb;
        border: 1px solid #fde68a; border-radius: 6px; padding: 8px 10px; margin-bottom: 12px;
    }

    .dd-detail-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .dd-detail-table th {
        background: #f5f5f5; color: var(--dd-text-dark); padding: 8px; text-align: left;
        border: 1px solid #eee; white-space: nowrap; position: sticky; top: 0;
    }
    .dd-detail-table td { padding: 8px; border: 1px solid #eee; white-space: nowrap; }
</style>
@endsection

@push('scripts')
<script>
var ddLocationID = '';
var ddDocType = '';
var ddFrom = '';
var ddTo = '';
var ddSearch = '';
var ddRows = [];
var ddPageSize = 10;
var ddCurrentPage = 1;
var ddTotalRecords = 0;

function ddHasRequiredFilters() {
    return ddLocationID !== '' && ddDocType !== '' && ddFrom !== '' && ddTo !== '';
}

function ddShowState(message) {
    $('#ddBody').html('<tr><td colspan="20" class="dd-state">' + message + '</td></tr>');
    $('#ddCards').html('<div class="dd-mcard dd-state">' + message + '</div>');
    // The wrapper is hidden by CSS unless it has .show, so the message must
    // be shown through the same class (otherwise "Data not found" never appears).
    $('.dd-table-wrapper').addClass('show');
    $('#ddCards').addClass('show');
    $('#ddPagination').hide();
}

function ddShowLoading(message) {
    message = message || 'Loading documents...';
    $('.dd-table-wrapper').addClass('show');
    $('#ddCards').addClass('show');
    $('#ddBody').html(
        '<tr><td colspan="20" class="dd-loading-cell">' +
            '<div class="dd-spinner"></div>' +
            '<p>' + message + '</p>' +
        '</td></tr>'
    );
    $('#ddCards').html(
        '<div class="dd-mcard dd-loading-card" style="text-align:center;">' +
            '<div class="dd-spinner"></div>' +
            '<p>' + message + '</p>' +
        '</div>'
    );
    $('#ddPagination').hide();
}

$(document).ready(function() {
    ddShowState('Select a Location and Document Type, then Apply Filter.');

    $('#ddLocation').change(function() {
        var selected = this.options[this.selectedIndex];
        ddLocationID = $(this).val();

        if (!ddLocationID) {
            $('#ddBadge').hide();
        } else {
            $('#ddDot').css('backgroundColor', selected.getAttribute('data-color'));
            $('#ddBadgeName').text(selected.text.trim());
            $('#ddBadge').show();
        }
    });

    $('#ddDocType').change(function() {
        ddDocType = $(this).val();
    });

    $('#ddApply').click(function() {
        ddLocationID = $('#ddLocation').val();
        ddDocType = $('#ddDocType').val();
        ddFrom = $('#ddFrom').val();
        ddTo = $('#ddTo').val();
        ddSearch = '';

        if (!ddLocationID) {
            Swal.fire('Location Required', 'Please select a Location.', 'info');
            return;
        }
        if (!ddDocType) {
            Swal.fire('Document Type Required', 'Please select a Document Type.', 'info');
            return;
        }
        if (!ddFrom || !ddTo) {
            Swal.fire('Date Range Required', 'Please select From Date and To Date.', 'info');
            return;
        }
        if (ddFrom > ddTo) {
            Swal.fire('Invalid Range', 'From Date cannot be greater than To Date.', 'info');
            return;
        }

        ddCurrentPage = 1;
        ddLoadData();
    });

    $('#ddReset').click(function() {
        $('#ddLocation').val('');
        $('#ddDocType').val('');
        $('#ddFrom').val('{{ now()->startOfMonth()->format('Y-m-d') }}');
        $('#ddTo').val('{{ now()->format('Y-m-d') }}');
        $('#ddBadge').hide();
        ddLocationID = '';
        ddDocType = '';
        ddFrom = '';
        ddTo = '';
        ddSearch = '';
        ddRows = [];
        ddCurrentPage = 1;
        ddTotalRecords = 0;
        ddShowState('Select a Location and Document Type, then Apply Filter.');
        $('#ddSub').text('');
    });

    // Pagination buttons
    $('#ddPrevBtn').click(function() {
        if (ddCurrentPage > 1) {
            ddCurrentPage--;
            ddLoadData();
        }
    });

    $('#ddNextBtn').click(function() {
        var totalPages = Math.max(Math.ceil(ddTotalRecords / ddPageSize), 1);
        if (ddCurrentPage < totalPages) {
            ddCurrentPage++;
            ddLoadData();
        }
    });

    // View buttons are injected dynamically (table rows + mobile cards),
    // so this is a delegated handler bound once on page load — it works
    // for every row rendered later, no onclick-in-HTML needed.
    $(document).on('click', '.dd-view-btn', function() {
        var key = $(this).attr('data-key');
        ddLoadDetail(key);
    });

    // Doc No click -> download PDF for that document.
    $(document).on('click', '.dd-doc-link', function(e) {
        e.preventDefault();
        var key = $(this).attr('data-key');
        ddDownloadPdf(key);
    });
});

function ddLoadData() {
    ddShowLoading('Loading documents...');

    var start = (ddCurrentPage - 1) * ddPageSize;

    $.ajax({
        url: '{{ route('documentDownload.getListData') }}',
        type: 'GET',
        data: {
            locationID: ddLocationID,
            docType: ddDocType,
            fromDate: ddFrom,
            toDate: ddTo,
            search: ddSearch,
            start: start,
            length: ddPageSize
        },
        success: function(response) {
            if (!response.success) {
                ddShowState(response.message || 'Could not load documents.');
                return;
            }

            ddRows = response.data || [];
            ddTotalRecords = response.recordsFiltered || 0;
            $('#ddSub').text(response.database ? ('Database: ' + response.database) : '');

            if (ddTotalRecords === 0) {
                ddShowState('Data not found');
                return;
            }

            ddRenderRows();
            ddUpdatePagination();
        },
        error: function(xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'Failed to load documents.';
            ddShowState(message);
        }
    });
}

function ddUpdatePagination() {
    var totalPages = Math.max(Math.ceil(ddTotalRecords / ddPageSize), 1);
    var start = (ddCurrentPage - 1) * ddPageSize;
    var shownFrom = ddTotalRecords === 0 ? 0 : start + 1;
    var shownTo = Math.min(start + ddRows.length, ddTotalRecords);

    $('#ddPaginationInfo').text('Showing ' + shownFrom + '-' + shownTo + ' of ' + ddTotalRecords);
    $('#ddPageInfo').text('Page ' + ddCurrentPage + ' of ' + totalPages);
    $('#ddPrevBtn').prop('disabled', ddCurrentPage <= 1);
    $('#ddNextBtn').prop('disabled', ddCurrentPage >= totalPages);
    $('#ddPagination').show();
}

function ddFormatDate(raw) {
    if (!raw) return '-';
    var datePart = String(raw).substring(0, 10);
    var parts = datePart.split('-');
    if (parts.length === 3 && parts[0].length === 4) {
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }
    return String(raw);
}

function ddFormatAmount(val) {
    if (val === null || val === undefined || val === '') return '-';
    var num = parseFloat(val);
    if (isNaN(num)) return ddEscape(String(val));
    return num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function ddEscape(value) {
    return $('<div>').text(value == null ? '' : value).html();
}

// Friendly header labels for known keys — anything not listed here is
// auto-prettified (CamelCase -> "Camel Case") so a new column added to a
// list*() query just works without a blade change.
var ddFriendlyLabels = {
    DocKey: 'Doc No',
    DocDate: 'Date',
    PartyName: 'Party / Salesman',
    Amount: 'Amount',
    RefDocNo: 'Ref Doc No',
    EWAYBILL: 'E-Way Bill',
    IRN: 'IRN',
    CAmount: 'Amount'
};

function ddPrettifyLabel(key) {
    if (ddFriendlyLabels[key]) return ddFriendlyLabels[key];
    // CamelCase / PascalCase -> spaced words
    return key.replace(/([a-z0-9])([A-Z])/g, '$1 $2')
              .replace(/([A-Z]+)([A-Z][a-z])/g, '$1 $2')
              .trim();
}

function ddIsAmountColumn(key) {
    return /amt|amount|disc/i.test(key) && key !== 'DocKey';
}

function ddRenderRows() {
    $('.dd-table-wrapper').addClass('show');
    $('#ddCards').addClass('show');

    if (ddRows.length === 0) return;

    // Columns come from whatever the list*() query returned — shown in
    // that order, plus a leading # and a trailing Action column.
    var cols = Object.keys(ddRows[0]);

    var theadHtml = '<tr><th>#</th>';
    cols.forEach(function(c) {
        theadHtml += '<th' + (ddIsAmountColumn(c) ? ' class="num"' : '') + '>' + ddEscape(ddPrettifyLabel(c)) + '</th>';
    });
    theadHtml += '<th>Action</th></tr>';
    $('#ddHead').html(theadHtml);

    var tbodyHtml = '';
    var cardsHtml = '';
    var baseIndex = (ddCurrentPage - 1) * ddPageSize;

    ddRows.forEach(function(row, index) {
        var key = row.DocKey;
        var serialNo = baseIndex + index + 1;

        var rowHtml = '<tr><td>' + serialNo + '</td>';
        var cardRows = '';

        cols.forEach(function(c) {
            var raw = row[c];
            var display;
            if (c === 'DocDate') {
                display = ddFormatDate(raw);
            } else if (ddIsAmountColumn(c)) {
                display = ddFormatAmount(raw);
            } else {
                display = ddEscape(raw === null || raw === undefined || raw === '' ? '-' : raw);
            }

            // Doc No is clickable — downloads the PDF for this document.
            if (c === 'DocKey') {
                display = '<a href="#" class="dd-doc-link" data-key="' + ddEscape(String(key)) + '" title="Download PDF">' + display + '</a>';
            }

            rowHtml += '<td' + (ddIsAmountColumn(c) ? ' class="num"' : '') + '>' + display + '</td>';
            cardRows += '<div class="row"><span class="lbl">' + ddEscape(ddPrettifyLabel(c)) + '</span><span>' + display + '</span></div>';
        });

        var keyAttr = ddEscape(String(key));
        rowHtml += '<td><button type="button" class="dd-view-btn" data-key="' + keyAttr + '">View</button></td></tr>';
        tbodyHtml += rowHtml;

        cardsHtml += '<div class="dd-mcard">' + cardRows +
            '<div style="text-align:right; margin-top:8px;">' +
                '<button type="button" class="dd-view-btn" data-key="' + keyAttr + '">View</button>' +
            '</div>' +
        '</div>';
    });

    $('#ddBody').html(tbodyHtml);
    $('#ddCards').html(cardsHtml);
}

// ========== DETAIL MODAL ==========
// Doc No click -> builds the PDF URL for this document and navigates to
// it. The server responds with Content-Disposition: attachment, so the
// browser downloads the file without leaving this page.
function ddDownloadPdf(key) {
    var url = '{{ route('documentDownload.pdf') }}'
        + '?locationID=' + encodeURIComponent(ddLocationID)
        + '&docType=' + encodeURIComponent(ddDocType)
        + '&key=' + encodeURIComponent(key);
    window.location.href = url;
}

function ddLoadDetail(key) {
    $('#ddModalTitle').text('Loading...');
    $('#ddModalBody').html('<div class="dd-state"><div class="dd-spinner"></div><p>Loading document...</p></div>');
    $('#ddModalOverlay').css('display', 'flex');

    $.ajax({
        url: '{{ route('documentDownload.live') }}',
        type: 'GET',
        data: {
            locationID: ddLocationID,
            docType: ddDocType,
            key: key
        },
        success: function(response) {
            if (!response.success) {
                $('#ddModalBody').html('<div class="dd-state">' + ddEscape(response.message || 'Could not load document.') + '</div>');
                return;
            }

            $('#ddModalTitle').text('Document ' + key);
            $('#ddModalBody').html(ddBuildDetailTable(response.data));
        },
        error: function(xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'Failed to load document detail.';
            $('#ddModalBody').html('<div class="dd-state">' + ddEscape(message) + '</div>');
        }
    });
}

// Renders whatever columns the detail query returned as a generic table —
// line-item-per-row, since the shape differs per document type and the
// print layout for each type hasn't been built yet (see note below).
function ddBuildDetailTable(rows) {
    if (!rows || rows.length === 0) {
        return '<div class="dd-state">No rows returned for this document.</div>';
    }

    var cols = Object.keys(rows[0]);

    var html = '<div class="dd-warning">⚠️ Raw data view — please verify this against a real printed copy. A proper print layout for each document type will replace this once verified.</div>';
    html += '<div style="overflow:auto; max-height:60vh;">';
    html += '<table class="dd-detail-table"><thead><tr>';
    cols.forEach(function(c) { html += '<th>' + ddEscape(c) + '</th>'; });
    html += '</tr></thead><tbody>';

    rows.forEach(function(row) {
        html += '<tr>';
        cols.forEach(function(c) {
            html += '<td>' + ddEscape(row[c]) + '</td>';
        });
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    return html;
}

function ddCloseModal() {
    $('#ddModalOverlay').css('display', 'none');
}
</script>
@endpush