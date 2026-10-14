@extends('layouts.app')
@section('title', 'Gate Entry Details')


@section('content')
@php
    // Item columns in display order: key => [label, type]
    $itemColumns = [
        'SrNo' => ['Sr No', 'text'],
        'ItemCode' => ['Code', 'text'],
        'CompanyName' => ['Company', 'text'],
        'ItemName' => ['Item Name', 'text'],
        'DesignName' => ['Design', 'text'],
        'Color' => ['Color', 'text'],
        'Size' => ['Size', 'text'],
        'Unit' => ['Unit', 'text'],
        'QualityName' => ['Quality', 'text'],
        'Qty' => ['Qty', 'qty'],
        'CostPrice' => ['Cost Price', 'money'],
        'MRP' => ['MRP', 'money'],
        'Amount' => ['Amount', 'money'],
        'TaxRate' => ['Tax %', 'money'],
        'SupplierName' => ['Supplier', 'text'],
        'AccName' => ['Acc Name', 'text'],
        'RollNo' => ['Roll No', 'text'],
        'DocNo' => ['Doc No', 'text'],
        'ChallanNo' => ['Challan No', 'text'],
        'GBillNo' => ['Bill No', 'text'],
        'Checker' => ['Checker', 'text'],
    ];

    $items = $items ?? [];

    // Keep only columns that have data in at least one item (Qty & Amount always shown)
    $visibleColumns = array_filter($itemColumns, function ($meta, $key) use ($items) {
        if (in_array($key, ['Qty', 'Amount'], true)) {
            return true;
        }
        foreach ($items as $it) {
            $v = $it->{$key} ?? null;
            if ($v === null || $v === '') {
                continue;
            }
            if ($meta[1] === 'money' && (float) $v == 0) {
                continue;
            }
            return true;
        }
        return false;
    }, ARRAY_FILTER_USE_BOTH);

    $totalQty = 0;
    $totalAmount = 0;
    foreach ($items as $it) {
        $totalQty += (float) ($it->Qty ?? 0);
        $totalAmount += (float) ($it->Amount ?? 0);
    }

    $humanize = function ($key) {
        $key = str_replace('_', ' ', (string) $key);
        $key = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $key);
        $key = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1 $2', $key);
        return trim($key);
    };

    $formatValue = function ($key, $value) {
        if ($value === null || $value === '') {
            return '-';
        }
        if (stripos($key, 'date') !== false) {
            try {
                return \Carbon\Carbon::parse($value)->format('d-m-Y');
            } catch (\Throwable $e) {
                return $value;
            }
        }
        if (preg_match('/amount|amt|mrp|rate|price|cost|value|total|tax|gst|disc/i', $key) && is_numeric($value)) {
            return number_format((float) $value, 2);
        }
        return $value;
    };

    // Summary: Gate Entry row if available, otherwise header fields from the first item
    $summary = [];
    if (!empty($entry)) {
        foreach ((array) $entry as $key => $value) {
            // GRN ID is not shown on this page
            if (strtolower(preg_replace('/[^a-z0-9]/i', '', $key)) === 'grnid') {
                continue;
            }
            $summary[$humanize($key)] = $formatValue($key, $value);
        }
    } elseif (!empty($items)) {
        $first = $items[0];
        foreach ([
            'AccName' => 'Acc Name',
            'SupplierName' => 'Supplier',
            'EntryDate' => 'Entry Date',
            'ChallanNo' => 'Challan No',
            'GBillNo' => 'Bill No',
            'DocNo' => 'Doc No',
            'Checker' => 'Checker',
        ] as $key => $label) {
            $v = $first->{$key} ?? null;
            if ($v !== null && $v !== '') {
                $summary[$label] = $formatValue($key, $v);
            }
        }
    }

    $locationColor = !empty($location->Color ?? null) ? $location->Color : '#000000';
@endphp
<div class="ged-container">

    <!-- Header -->
    <div class="ged-header">
        <div>
            <a href="{{ $backUrl }}" class="ged-back"><i class="bi bi-arrow-left"></i> Back to Gate Entry List</a>
            <h2 class="ged-title">Gate Entry Details</h2>
            @if ($location)
                <span class="ged-location">
                    <span class="ged-dot" style="background-color: {{ $locationColor }};"></span>
                    {{ $location->LocationName ?? '' }}
                </span>
            @endif
        </div>
        @if (!$error && !empty($items) && !empty($pdfUrl))
            <a href="{{ $pdfUrl }}" class="ged-pdf-btn">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </a>
        @endif
    </div>

    @if (session('error'))
        <div class="ged-alert">
            <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    @if ($error)
        <!-- Error state -->
        <div class="ged-message ged-message-error">
            <i class="bi bi-exclamation-triangle"></i>
            <p>{{ $error }}</p>
        </div>
    @elseif (empty($items))
        <!-- No data state -->
        <div class="ged-message">
            <i class="bi bi-inbox"></i>
            <h3>No details found</h3>
            <p>No details are available for GRN #{{ $grnId }} at this location.</p>
            <a href="{{ $backUrl }}" class="ged-back-btn">Back to Gate Entry List</a>
        </div>
    @else
        <!-- Summary -->
        @if (!empty($summary))
            <div class="ged-summary">
                @foreach ($summary as $label => $value)
                    <div class="ged-summary-item">
                        <span class="lbl">{{ $label }}</span>
                        <span class="val">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Totals -->
        <div class="ged-totals">
            <div class="ged-total-box"><span>Items</span><strong>{{ count($items) }}</strong></div>
            <div class="ged-total-box"><span>Total Qty</span><strong>{{ rtrim(rtrim(number_format($totalQty, 2, '.', ''), '0'), '.') }}</strong></div>
            <div class="ged-total-box"><span>Total Amount</span><strong>&#8377;{{ number_format($totalAmount, 2) }}</strong></div>
        </div>

        <!-- Items table (desktop) -->
        <div class="ged-table-wrapper">
            <div class="ged-table-scroll">
                <table class="ged-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            @foreach ($visibleColumns as $key => $meta)
                                <th class="{{ $meta[1] !== 'text' ? 'num-col' : '' }}">{{ $meta[0] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $it)
                            <tr>
                                <td>
                                    @if (!empty($it->ItemCode))
                                        <a href="http://web.homesaaz.in:85/{{ rawurlencode($it->ItemCode) }}.JPG" target="_blank" rel="noopener">
                                            <img src="http://web.homesaaz.in:85/{{ rawurlencode($it->ItemCode) }}.JPG"
                                                class="ged-thumb" alt="{{ $it->ItemCode }}" loading="lazy"
                                                onerror="this.style.visibility='hidden';">
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                @foreach ($visibleColumns as $key => $meta)
                                    @php $v = $it->{$key} ?? null; @endphp
                                    <td class="{{ $meta[1] !== 'text' ? 'num-col' : '' }}">
                                        @if ($meta[1] === 'money')
                                            {{ number_format((float) $v, 2) }}
                                        @elseif ($meta[1] === 'qty')
                                            {{ $v === null || $v === '' ? '0' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.') }}
                                        @else
                                            {{ $v === null || $v === '' ? '-' : $v }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Items cards (mobile) -->
        <div class="ged-cards">
            @foreach ($items as $it)
                <div class="ged-card">
                    @if (!empty($it->ItemCode))
                        <div class="ged-card-img">
                            <img src="http://web.homesaaz.in:85/{{ rawurlencode($it->ItemCode) }}.JPG"
                                alt="{{ $it->ItemCode }}" loading="lazy"
                                onerror="this.parentNode.style.display='none';">
                        </div>
                    @endif
                    @foreach ($visibleColumns as $key => $meta)
                        @php $v = $it->{$key} ?? null; @endphp
                        <div class="ged-card-row">
                            <span class="lbl">{{ $meta[0] }}</span>
                            <span class="val">
                                @if ($meta[1] === 'money')
                                    {{ number_format((float) $v, 2) }}
                                @elseif ($meta[1] === 'qty')
                                    {{ $v === null || $v === '' ? '0' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.') }}
                                @else
                                    {{ $v === null || $v === '' ? '-' : $v }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
    :root {
        --primary-blue: #0052cc;
        --light-gray: #f5f5f5;
        --border-gray: #ddd;
        --text-dark: #333;
        --text-light: #666;
    }

    .ged-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #f8f9fa 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .ged-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 15px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .ged-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: var(--primary-blue);
        text-decoration: none;
        margin-bottom: 8px;
    }

    .ged-title {
        font-size: 26px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0 0 8px;
    }

    .ged-grn {
        color: var(--primary-blue);
    }

    .ged-location {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        background: #fff;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    }

    .ged-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #ddd;
        display: inline-block;
    }

    .ged-pdf-btn,
    .ged-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        background: var(--primary-blue);
        color: #fff;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
    }

    .ged-pdf-btn:hover,
    .ged-back-btn:hover {
        background: #0045b3;
        color: #fff;
    }

    .ged-alert {
        background: #fdecea;
        color: #b02a37;
        border: 1px solid #f5c2c7;
        border-radius: 6px;
        padding: 12px 15px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 500;
    }

    .ged-message {
        background: #fff;
        border-radius: 8px;
        padding: 50px 20px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        color: var(--text-light);
    }

    .ged-message i {
        font-size: 42px;
        display: block;
        margin-bottom: 12px;
        color: #9ca3af;
    }

    .ged-message h3 {
        font-size: 20px;
        color: var(--text-dark);
        margin-bottom: 6px;
    }

    .ged-message p {
        margin-bottom: 18px;
    }

    .ged-message-error,
    .ged-message-error i {
        color: #dc3545;
    }

    .ged-summary {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: 12px;
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .ged-summary-item {
        background: var(--light-gray);
        border-radius: 6px;
        padding: 10px 12px;
    }

    .ged-summary-item .lbl,
    .ged-total-box span {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-light);
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .ged-summary-item .val {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-dark);
        word-break: break-word;
    }

    .ged-totals {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .ged-total-box {
        background: #fff;
        border-radius: 8px;
        padding: 14px 16px;
        border-left: 4px solid var(--primary-blue);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .ged-total-box strong {
        font-size: 20px;
        color: var(--text-dark);
    }

    .ged-table-wrapper {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .ged-table-scroll {
        width: 100%;
        overflow-x: auto;
    }

    .ged-table {
        width: 100%;
        border-collapse: collapse;
    }

    .ged-table th {
        background: var(--light-gray);
        padding: 14px 15px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        text-align: left;
        white-space: nowrap;
        color: var(--text-dark);
        border-bottom: 2px solid var(--border-gray);
    }

    .ged-table td {
        padding: 12px 15px;
        font-size: 14px;
        color: var(--text-dark);
        border-bottom: 1px solid var(--border-gray);
        white-space: nowrap;
        vertical-align: middle;
    }

    .ged-table tbody tr:hover {
        background-color: rgba(0, 82, 204, 0.03);
    }

    .ged-table .num-col {
        text-align: right;
    }

    .ged-thumb {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid var(--border-gray);
    }

    .ged-cards {
        display: none;
        flex-direction: column;
        gap: 15px;
    }

    .ged-card {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        border-left: 4px solid var(--primary-blue);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .ged-card-img {
        text-align: center;
        margin-bottom: 10px;
    }

    .ged-card-img img {
        max-width: 100%;
        max-height: 180px;
        border-radius: 6px;
    }

    .ged-card-row {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid var(--light-gray);
    }

    .ged-card-row:last-child {
        border-bottom: none;
    }

    .ged-card-row .lbl {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-light);
    }

    .ged-card-row .val {
        font-size: 14px;
        font-weight: 500;
        color: var(--text-dark);
        text-align: right;
        word-break: break-word;
    }

    @media (max-width: 768px) {
        .ged-title {
            font-size: 21px;
        }

        .ged-totals {
            grid-template-columns: 1fr;
        }

        .ged-table-wrapper {
            display: none;
        }

        .ged-cards {
            display: flex;
        }

        .ged-pdf-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
@endpush
@endsection