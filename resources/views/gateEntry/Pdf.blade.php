<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gate Entry Details - GRN {{ $grnId }}</title>
    <style>
        @page {
            margin: 18px 18px 30px 18px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #222;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .sub {
            font-size: 10px;
            color: #555;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            vertical-align: top;
            width: 25%;
        }

        .summary .lbl {
            display: block;
            font-size: 7px;
            color: #666;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .summary .val {
            font-size: 9px;
            font-weight: bold;
        }

        .section {
            font-size: 11px;
            font-weight: bold;
            margin: 12px 0 5px;
        }

        .items th {
            background: #0052cc;
            color: #fff;
            font-size: 8px;
            text-transform: uppercase;
            padding: 5px 4px;
            text-align: left;
            border: 1px solid #0052cc;
        }

        .items td {
            border: 1px solid #ccc;
            padding: 4px;
            font-size: 8px;
            word-wrap: break-word;
        }

        .items tr:nth-child(even) td {
            background: #f5f7fa;
        }

        .num {
            text-align: right;
        }

        .totals td {
            border: 1px solid #ccc;
            padding: 6px;
            font-size: 10px;
            font-weight: bold;
            background: #eef3fb;
        }

        .footer {
            position: fixed;
            bottom: -18px;
            left: 0;
            right: 0;
            font-size: 7px;
            color: #888;
            text-align: right;
        }
    </style>
</head>
<body>
@php
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

    $fmtQty = function ($v) {
        return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    };

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

    $summary = [];
    if (!empty($entry)) {
        foreach ((array) $entry as $key => $value) {
            $label = strtolower(preg_replace('/[^a-z0-9]/i', '', $key)) === 'grnid' ? 'GRN ID' : $humanize($key);
            $summary[$label] = $formatValue($key, $value);
        }
    } else {
        $summary['GRN ID'] = $grnId;
        $first = $items[0] ?? null;
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
    $summaryChunks = array_chunk($summary, 4, true);
@endphp

    <div class="title">Gate Entry Details - GRN #{{ $grnId }}</div>
    <div class="sub">
        {{ $location->LocationName ?? '' }}
        &nbsp;|&nbsp; Generated on {{ date('d-m-Y h:i A') }}
    </div>

    <table class="summary">
        @foreach ($summaryChunks as $chunk)
            <tr>
                @foreach ($chunk as $label => $value)
                    <td><span class="lbl">{{ $label }}</span><span class="val">{{ $value }}</span></td>
                @endforeach
                @for ($i = count($chunk); $i < 4; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>

    <div class="section">Items ({{ count($items) }})</div>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                @foreach ($visibleColumns as $key => $meta)
                    <th class="{{ $meta[1] !== 'text' ? 'num' : '' }}">{{ $meta[0] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $it)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @foreach ($visibleColumns as $key => $meta)
                        @php $v = $it->{$key} ?? null; @endphp
                        <td class="{{ $meta[1] !== 'text' ? 'num' : '' }}">
                            @if ($meta[1] === 'money')
                                {{ number_format((float) $v, 2) }}
                            @elseif ($meta[1] === 'qty')
                                {{ $v === null || $v === '' ? '0' : $fmtQty($v) }}
                            @else
                                {{ $v === null || $v === '' ? '-' : $v }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="margin-top: 8px;">
        <tr>
            <td>Total Items: {{ count($items) }}</td>
            <td>Total Qty: {{ $fmtQty($totalQty) }}</td>
            <td class="num">Total Amount: Rs. {{ number_format($totalAmount, 2) }}</td>
        </tr>
    </table>

    <div class="footer">HomeSaaz - Gate Entry Details</div>
</body>
</html>