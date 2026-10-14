<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GRN Report</title>

    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
        }

        h1 {
            text-align: center;
            color: #0052cc;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .header-table td {
            border: 1px solid #999;
            padding: 6px 8px;
        }

        .header-table .label {
            width: 15%;
            font-weight: bold;
            background: #f2f2f2;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #999;
            padding: 5px 6px;
            font-size: 9px;
        }

        .items-table th {
            background: #0052cc;
            color: #fff;
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .note {
            font-size: 8px;
            color: #666;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>

<body>

<h1>GRN Report</h1>

@if($grn)
<table class="header-table">
    <tr>
        {{-- FIXED: Now shows GRNID instead of GmNo --}}
        <td class="label">GRN ID</td>
        <td><strong>{{ $grn->GRNID ?? '-' }}</strong></td>
        <td class="label">Floor</td>
        <td>{{ $grn->FloorName ?? $grn->floorName ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">GRN Date</td>
        <td>{{ isset($grn->GrnDate) ? date('d-M-Y H:i', strtotime($grn->GrnDate)) : '-' }}</td>
        <td class="label">Billing Ref</td>
        {{-- Also show GmNo as billing reference --}}
        <td>{{ $grn->GmNo ?? $grn->gmNo ?? '-' }}</td>
    </tr>
    <tr>
        {{-- FIXED: Use header supplier consistently --}}
        <td class="label">Supplier</td>
        <td><strong>{{ $grn->Supplier ?? $grn->supplier ?? '-' }}</strong></td>
        <td class="label">Bill Amount</td>
        <td>Rs. {{ number_format(floatval($grn->BillAmount ?? $grn->billAmount ?? 0), 2) }}</td>
    </tr>
    <tr>
        <td class="label">Billing</td>
        <td>{{ $grn->Billno ?? $grn->Billno ?? '-' }}</td>
        <td class="label">GRN Amount</td>
        <td>Rs. {{ number_format(floatval($grn->GrnAmount ?? $grn->grnAmount ?? 0), 2) }}</td>
    </tr>
</table>
@else
<p style="color:red; text-align:center;">GRN data not found</p>
@endif

<table class="items-table">
    <thead>
        <tr>
            <th>Code</th>
            <th>Company</th>
            <th>Item Name</th>
            <th>Design Name</th>
            <th>Color</th>
            <th>Sr No</th>
            <th>Unit</th>
            <th>Quality</th>
            <th>Qty</th>
            <th>Size</th>
            <th>Cost Price</th>
            <th>Amount</th>
            <th>Tax Rate</th>
            {{-- FIXED: Only show item supplier if different from header --}}
            <th>Item Supplier</th>
        </tr>
    </thead>
    <tbody>
        @if($items && count($items) > 0)
            @foreach($items as $item)
            <tr>
                <td>{{ $item->ItemCode ?? $item->itemCode ?? '-' }}</td>
                <td>{{ $item->CompanyName ?? $item->companyName ?? '-' }}</td>
                <td>{{ $item->ItemName ?? $item->itemName ?? '-' }}</td>
                <td>{{ $item->DesignName ?? $item->designName ?? '-' }}</td>
                <td>{{ $item->Color ?? $item->color ?? '-' }}</td>
                <td class="text-center">{{ $item->SrNo ?? $item->srNo ?? '-' }}</td>
                <td class="text-center">{{ $item->Unit ?? $item->unit ?? '-' }}</td>
                <td>{{ $item->QualityName ?? $item->qualityName ?? '-' }}</td>
                <td class="text-end">{{ $item->Qty ?? $item->qty ?? '-' }}</td>
                <td class="text-center">{{ $item->Size ?? $item->size ?? '-' }}</td>
                <td class="text-end">Rs. {{ number_format(floatval($item->CostPrice ?? $item->costPrice ?? 0), 2) }}</td>
                <td class="text-end">Rs. {{ number_format(floatval($item->Amount ?? $item->amount ?? 0), 2) }}</td>
                <td class="text-end">{{ $item->TaxRate ?? $item->taxRate ?? '-' }}</td>
                {{-- FIXED: Show item supplier (may differ from header) --}}
                <td>
                    @if(($item->SupplierName ?? $item->supplierName ?? '') !== ($headerSupplier ?? ''))
                        <strong style="color: #cc0000;">{{ $item->SupplierName ?? $item->supplierName ?? '-' }}</strong>
                    @else
                        {{ $item->SupplierName ?? $item->supplierName ?? '-' }}
                    @endif
                </td>
            </tr>
            @endforeach
        @else
        <tr>
            <td colspan="14" class="text-center">No items found for this GRN.</td>
        </tr>
        @endif
    </tbody>
</table>

<div class="note">
    <strong>Note:</strong> GRN ID is the unique internal identifier. Billing Ref (GmNo) is the reference number used for billing purposes. 
    @if($items && count($items) > 0)
        Items supplier shown in red if different from header supplier.
    @endif
</div>

</body>
</html>