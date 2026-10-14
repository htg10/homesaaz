@extends('layouts.app')
@section('title', 'GRN Details')

@section('content')
    <div class="grn-detail-container">

        <!-- Header -->
        <div class="grn-detail-header">
            <div>
                <a href="{{ route('grn.list') }}" class="back-link"><i class="bi bi-arrow-left"></i> Back to GRN List</a>
                <h2 class="grn-detail-title">GRN Details</h2>
            </div>
            <a href="{{ route('grn.pdf', [
                'id' => $grnId,
                'locationID' => $locationID,
                'date' => $date,
            ]) }}"
                class="pdf-btn">

                <i class="bi bi-file-earmark-pdf"></i>
                Download PDF

            </a>

        </div>

        <!-- GRN Header Info -->
        <div class="grn-info-box" id="grnInfoBox">
            @if ($grnHeader)
                <div class="grn-info-item">
                    <span class="grn-info-label">GRN No</span>
                    <span class="grn-info-value"
                        id="infoGmNo">{{ $grnHeader->GrnNo ?? ($grnHeader->GRNNo ?? ($grnHeader->GmNo ?? '-')) }}</span>
                </div>
                <div class="grn-info-item">
                    <span class="grn-info-label">Floor</span>
                    <span class="grn-info-value" id="infoFloor">{{ $grnHeader->FloorName ?? '-' }}</span>
                </div>
                <div class="grn-info-item">
                    <span class="grn-info-label">GRN Date</span>
                    <span class="grn-info-value" id="infoDate">{{ $grnHeader->GrnDate ?? '-' }}</span>
                </div>
                <div class="grn-info-item">
                    <span class="grn-info-label">Supplier</span>
                    <span class="grn-info-value" id="infoSupplier">{{ $grnHeader->Supplier ?? '-' }}</span>
                </div>
                <div class="grn-info-item">
                    <span class="grn-info-label">Billing</span>
                    <span class="grn-info-value" id="infoBilling">{{ $grnHeader->Billno ?? '-' }}</span>
                </div>
            @else
                <div style="padding: 20px; text-align: center; color: #dc3545;">
                    GRN header not found. Please ensure the GRN exists for the selected date and location.
                </div>
            @endif
        </div>

       

        <!-- Items Table -->
        <div class="grn-items-wrapper">
            <div class="table-scroll">
                <table class="grn-items-table" id="grnItemsTable">
                    <thead>
                        <tr>
                            <th>ITEM IMAGE</th>
                            <th>CODE</th>
                            <th>COMPANY</th>
                            <th>ITEM NAME</th>
                            <th>DESIGN NAME</th>
                            <th>COLOR</th>
                            <th>SR NO</th>
                            <th>UNIT</th>
                            <th>QUALITY</th>
                            <th class="text-end">QTY</th>
                            <th>SIZE</th>
                            <th class="text-end">COST PRICE</th>
                            <th class="text-end">AMOUNT</th>
                            <th class="text-end">TAX RATE</th>
                            <th>SUPPLIER NAME</th>
                        </tr>
                    </thead>
                    <tbody id="grnItemsBody">
                        <tr>
                            <td colspan="15" class="loading-cell">
                                <div class="spinner"></div>
                                <p>Loading GRN items...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
 <!-- GRN Entry Total -->
        <div class="grn-entry-total">
            Total Entries:
            <strong id="grnTotalEntries">0</strong>
        </div>
    </div>

    <!-- Image Zoom Modal -->
    <div class="img-zoom-overlay" id="imgZoomOverlay">
        <span class="img-zoom-close" id="imgZoomClose">&times;</span>
        <img id="imgZoomTarget" src="" alt="Item Image">
    </div>

    <style>
        .grn-detail-container {
            background: linear-gradient(135deg, #f5f7fa 0%, #f8f9fa 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .grn-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 6px;
            font-size: 13px;
            color: #0052cc;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .grn-detail-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .pdf-btn {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: fit-content;
        }

        .pdf-btn:hover {
            background: #c82333;
            color: #fff;
        }

        .grn-info-box {
            background: #fff;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
        }

        .grn-info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 120px;
        }

        .grn-info-label {
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
        }

        .grn-info-value {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .grn-entry-total{
            background:#fff;
            padding:15px 20px;
            margin-bottom:20px;
            border-radius:8px;
            box-shadow:0 2px 8px rgba(0,0,0,.1);
            font-size:15px;
            font-weight:600;
        }

        .grn-entry-total strong{
            color:#0052cc;
            font-size:18px;
        }

        .grn-items-wrapper {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .grn-items-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1300px;
        }

        .grn-items-table thead {
            background-color: #f5f5f5;
            border-bottom: 2px solid #ddd;
        }

        .grn-items-table thead th {
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            color: #333;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .grn-items-table tbody tr {
            border-bottom: 1px solid #eee;
        }

        .grn-items-table tbody tr:hover {
            background-color: rgba(0, 82, 204, 0.03);
        }

        .grn-items-table tbody td {
            padding: 10px;
            font-size: 13px;
            color: #333;
            vertical-align: middle;
        }

        .text-end {
            text-align: right;
        }

        .loading-cell {
            text-align: center !important;
            padding: 40px 15px !important;
        }

        .spinner {
            width: 36px;
            height: 36px;
            margin: 0 auto 10px;
            border: 3px solid #f5f5f5;
            border-top-color: #0052cc;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .item-thumb {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
            cursor: zoom-in;
            background: #f8f9fa;
        }

        .item-thumb-placeholder {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            color: #adb5bd;
        }

        .item-code-link {
            color: #0052cc;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .item-code-link:hover {
            text-decoration: underline;
        }

        /* Image Zoom Modal */
        .img-zoom-overlay {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            align-items: center;
            justify-content: center;
        }

        .img-zoom-overlay.active {
            display: flex;
        }

        .img-zoom-overlay img {
            max-width: 90%;
            max-height: 85%;
            border-radius: 6px;
        }

        .img-zoom-close {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 36px;
            color: #fff;
            cursor: pointer;
            line-height: 1;
        }

        @media (max-width: 768px) {
            .grn-detail-container {
                padding: 10px;
            }

            .grn-detail-title {
                font-size: 18px;
            }
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const grnId = {{ (int) $grnId }};

            // Escape helper
            function escapeHtml(value) {
                if (value === null || value === undefined || value === '') return '-';
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function money(value) {
                if (value === null || value === undefined || value === '') return '0.00';
                const n = parseFloat(value);
                return isNaN(n) ? '-' : n.toFixed(2);
            }

            function formatDate(date) {
                if (!date) return '-';
                const d = new Date(date);
                if (isNaN(d)) return date;
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                return day + '-' + month + '-' + d.getFullYear();
            }

            // Image zoom modal
            const zoomOverlay = document.getElementById('imgZoomOverlay');
            const zoomTarget = document.getElementById('imgZoomTarget');
            document.getElementById('imgZoomClose').addEventListener('click', function() {
                zoomOverlay.classList.remove('active');
            });
            zoomOverlay.addEventListener('click', function(e) {
                if (e.target === zoomOverlay) zoomOverlay.classList.remove('active');
            });

            function openZoom(src) {
                zoomTarget.src = src;
                zoomOverlay.classList.add('active');
            }

            // Click on item code -> Item Stock screen, prefilled + auto-searched
            function goToItemStock(itemCode) {
                if (!itemCode || itemCode === '-') return;
                window.location.href = '{{ route('itemstock.list') }}?itemCode=' + encodeURIComponent(itemCode);
            }

            function renderHeader(grn) {
                document.getElementById('infoGmNo').innerText = grn.GrnNo || grn.GRNNo || grn.GmNo || '-';
                document.getElementById('infoFloor').innerText = grn.FloorName || '-';
                document.getElementById('infoDate').innerText = formatDate(grn.GrnDate);
                document.getElementById('infoSupplier').innerText = grn.Supplier || '-';
                document.getElementById('infoBilling').innerText = grn.Billno || '-';
            }

            function renderItems(items) {
                const tbody = document.getElementById('grnItemsBody');
                tbody.innerHTML = '';

                if (!items || !items.length) {
                    tbody.innerHTML = `
                <tr>
                    <td colspan="15" style="text-align:center; padding:30px; color:#666;">
                        No items found for this GRN.
                    </td>
                </tr>`;
                    return;
                }

                items.forEach(function(item) {
                    const itemCode = item.ItemCode || '-';

                    // Build image URL from web server: http://web.homesaaz.in:85/{ItemCode}.JPG
                    let imageUrl = itemCode && itemCode !== '-' ?
                        'http://web.homesaaz.in:85/' + encodeURIComponent(itemCode) + '.JPG' :
                        '';

                    const imageCell = imageUrl ?
                        `<img src="${imageUrl}" class="item-thumb" alt="${escapeHtml(itemCode)}" onclick="window.__grnZoom && window.__grnZoom('${imageUrl}')" onerror="this.style.display='none';" style="cursor:zoom-in;"><div class="item-thumb-placeholder" style="display:none;"><i class="bi bi-image"></i></div>` :
                        `<div class="item-thumb-placeholder"><i class="bi bi-image"></i></div>`;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                <td>${imageCell}</td>
                <td><a class="item-code-link" onclick="window.__grnGoToItemStock && window.__grnGoToItemStock('${escapeHtml(itemCode)}')">${escapeHtml(itemCode)}</a></td>
                <td>${escapeHtml(item.CompanyName)}</td>
                <td>${escapeHtml(item.ItemName)}</td>
                <td>${escapeHtml(item.DesignName)}</td>
                <td>${escapeHtml(item.Color)}</td>
                <td>${escapeHtml(item.SrNo)}</td>
                <td>${escapeHtml(item.Unit)}</td>
                <td>${escapeHtml(item.QualityName)}</td>
                <td class="text-end">${escapeHtml(item.Qty)}</td>
                <td>${escapeHtml(item.Size)}</td>
                <td class="text-end">${money(item.CostPrice)}</td>
                <td class="text-end">${money(item.Amount)}</td>
                <td class="text-end">${escapeHtml(item.TaxRate)}</td>
                <td>${escapeHtml(item.SupplierName)}</td>
            `;
                    tbody.appendChild(tr);
                });
            }

            // Expose for inline onclick handlers
            window.__grnZoom = openZoom;
            window.__grnGoToItemStock = goToItemStock;

            const grnShowUrl = '{{ route('grn.show', '__ID__') }}'.replace('__ID__', grnId) +
                '?locationID={{ urlencode($locationID) }}&date={{ urlencode($date) }}';

            // Backend linked-server query can genuinely take a while, so give it a
            // longer window (matches set_time_limit(90) on the server) and retry
            // once silently before showing any error to the user.
            const FETCH_TIMEOUT_MS = 90000;
            const MAX_ATTEMPTS = 2;

            function fetchGrnItems(attempt) {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), FETCH_TIMEOUT_MS);

                if (attempt > 1) {
                    // Keep the spinner up, just note it's retrying (no scary message)
                    document.getElementById('grnItemsBody').innerHTML = `
                <tr>
                    <td colspan="15" class="loading-cell">
                        <div class="spinner"></div>
                        <p>Still loading, please wait...</p>
                    </td>
                </tr>`;
                }

                fetch(grnShowUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        signal: controller.signal
                    })
                    .then(response => {
                        clearTimeout(timeoutId);
                        return response.json();
                    })
                    .then(response => {
                        if (!response.success) {
                            document.getElementById('grnItemsBody').innerHTML = `
                        <tr><td colspan="15" style="text-align:center; padding:30px; color:#dc3545;">
                            ${escapeHtml(response.message || 'Unable to load GRN items.')}
                        </td></tr>`;
                            return;
                        }
                        const grnItems = response.items || [];

                        document.getElementById('grnTotalEntries').innerText = grnItems.length;

                        renderItems(grnItems);
                    })
                    .catch(err => {
                        clearTimeout(timeoutId);
                        console.error('GRN Detail fetch error (attempt ' + attempt + '):', err);

                        if (attempt < MAX_ATTEMPTS) {
                            // Retry silently instead of showing an error straight away
                            fetchGrnItems(attempt + 1);
                            return;
                        }

                        let errorMsg = 'Error loading GRN details. Please try again.';
                        if (err.name === 'AbortError') {
                            errorMsg =
                                'This GRN is taking longer than usual to load. Please try again in a moment.';
                        }
                        document.getElementById('grnItemsBody').innerHTML = `
                    <tr><td colspan="15" style="text-align:center; padding:30px; color:#dc3545;">
                        ${errorMsg}
                    </td></tr>`;
                    });
            }

            fetchGrnItems(1);
        });
    </script>
@endpush
