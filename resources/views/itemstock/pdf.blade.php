<!DOCTYPE html> 
<html> 
<head> 
    <meta charset="utf-8"> 
    <title>Item Stock Report</title> 

    <style> 
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            font-size: 14px; 
        } 

        h1 { 
            text-align: center; 
            color: #0052cc; 
            margin-bottom: 30px; 
        } 

        table { 
            width: 100%; 
            border-collapse: collapse; 
        } 

        td { 
            border: 1px solid #999; 
            padding: 10px; 
        } 

        .label { 
            width: 35%; 
            font-weight: bold; 
            background: #f2f2f2; 
        } 

        .value { 
            width: 65%; 
        } 

        .section { 
            margin-top: 20px; 
            background: #0052cc; 
            color: white; 
            padding: 8px; 
            font-weight: bold; 
        } 

        .branch-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        } 

        .branch-table td { 
            border: 1px solid #999; 
            padding: 8px; 
            text-align: center; 
        } 

        .branch-header { 
            background: #e8e8e8; 
            font-weight: bold; 
        } 

        .dynamic-branch-table { 
            width: 100%; 
            table-layout: fixed; 
            page-break-inside: auto; 
        } 

        .dynamic-branch-table tr { 
            page-break-inside: avoid; 
            page-break-after: auto; 
        } 

        .dynamic-branch-table td { 
            vertical-align: top; 
            word-wrap: break-word; 
            overflow-wrap: break-word; 
        } 

        .dynamic-branch-table .field-name { 
            width: 45%; 
            font-weight: bold; 
            background: #f7f7f7; 
        } 

        .dynamic-branch-table .field-value { 
            width: 55%; 
        } 
    </style> 
</head> 

<body> 
@php $access = $access ?? \App\Services\UserAccessService::fieldMap('item-stock'); @endphp

<h1>Item Stock Report</h1> 

<div class="section">Item Information</div> 

<table> 
    <tr> 
        <td class="label">Item Code</td> 
        <td class="value">{{ $stock->ItemCode ?? $stock->Itemcode ?? '-' }}</td> 
    </tr> 
    <tr> 
        <td class="label">Item Name</td> 
        <td class="value">{{ trim($stock->ItemName ?? '-') }}</td> 
    </tr> 
    <tr> 
        <td class="label">Company Name</td> 
        <td class="value">{{ $stock->CompanyName ?? '-' }}</td> 
    </tr> 
    <tr> 
        <td class="label">Design Name</td> 
        <td class="value">{{ trim($stock->DesignName ?? '-') }}</td> 
    </tr> 
</table> 

<div class="section">Stock Details</div> 

<table> 
    <tr> 
        <td class="label">Available Qty</td> 
        <td class="value">{{ $stock->StkQty ?? $stock->StockQty ?? '-' }}</td> 
    </tr>
    <tr>
        <td class="label">WHQTY</td>
        <td class="value">
            @if(isset($stock->WHQTY) && $stock->WHQTY !== null && $stock->WHQTY !== '')
                {{ is_numeric($stock->WHQTY) ? number_format((float) $stock->WHQTY, 2) : $stock->WHQTY }}
            @else
                -
            @endif
        </td>
    </tr>
    <tr> 
        <td class="label">Color</td> 
        <td class="value">{{ $stock->Color ?? '-' }}</td> 
    </tr> 
    <tr> 
        <td class="label">Size</td> 
        <td class="value">{{ $stock->SizeName ?? $stock->Size ?? '-' }}</td> 
    </tr> 
    <tr> 
        <td class="label">Unit</td> 
        <td class="value">{{ trim($stock->unitname ?? $stock->Unit ?? '-') }}</td> 
    </tr> 
    <tr> 
        <td class="label">Section Name</td> 
        <td class="value">{{ $stock->SectionName ?? '-' }}</td> 
    </tr> 
</table> 

<div class="section">Pricing Details</div> 

<table> 
    @if(!empty($access['mrp']))
    <tr> 
        <td class="label">MRP</td> 
        <td class="value">Rs. {{ number_format($stock->MRP ?? 0, 2) }}</td> 
    </tr>
    @endif 
    <tr> 
    </tr> 
    <tr> 
        <td class="label">Cost Price</td> 
        <td class="value">Rs. {{ number_format($stock->ACost ?? 0, 2) }}</td> 
    </tr> 
    @if(!empty($access['markup']))
    <tr> 
        <td class="label">Markup (%)</td> 
        <td class="value"> 
            @if($stock->MRP && $stock->ACost && $stock->ACost > 0) 
                {{ number_format((($stock->MRP - $stock->ACost) / $stock->ACost) * 100, 2) }}% 
            @else 
                - 
            @endif 
        </td> 
    </tr>
    @endif 
    @if(!empty($access['markdown']))
    <tr> 
        <td class="label">Markdown (%)</td> 
        <td class="value">{{ $stock->MD ?? '-' }}</td> 
    </tr>
    @endif 
    @if(!empty($access['discount']))
    <tr> 
        <td class="label">Discount (%)</td> 
        <td class="value">{{ $stock->Discount ?? '-' }}</td> 
    </tr>
    @endif 
    @if(!empty($access['dp_exclusive']))
    <tr> 
        <td class="label">DP Inclusive</td> 
        <td class="value"> 
            @php 
                $dpCost = $stock->ACost ?? 0; 
                $dpGst = $stock->GST ?? 0; 
                $dpExclusive = $dpCost + ($dpCost * ($dpGst / 100)); 
            @endphp 
            @if($dpCost > 0) 
                Rs. {{ number_format($dpExclusive, 2) }} 
            @else 
                - 
            @endif 
        </td> 
    </tr>
    @endif 
</table> 

@php 
    $displayedFields = [ 
        'ItemCode', 'Itemcode', 'ItemName', 'CompanyName', 'ProductName', 
        'DesignName', 'Color', 'SizeName', 'Size', 'Unit', 'unitname', 
        'SectionName', 'StkQty', 'StockQty', 'WHQTY', 'MRP', 'ACost', 
        'Markup', 'Discount', 'GST', 'HSNCODE', 'AccountID', 
        'MU', 'COLLECTIONNAME', 'QualityName', 'SupplierName', 
        'SupplierMobileNo', 'ContactPerson', 'ItemImage', 'IImage', 
        'ImageUrl', '__ItemStockUpdateDiscount' 
    ]; 

    $stockData = is_object($stock) 
        ? get_object_vars($stock) 
        : (array) $stock; 

    $dynamicFields = []; 

    foreach ($stockData as $fieldName => $fieldValue) { 
        if (in_array($fieldName, $displayedFields, true)) { 
            continue; 
        } 

        if (str_starts_with((string) $fieldName, '__')) { 
            continue; 
        } 

        $dynamicFields[$fieldName] = $fieldValue; 
    } 
@endphp 

<div class="section">Branch-wise Stock Distribution</div> 

@php 
    $displayedFields = [ 
        'ItemCode', 'Itemcode', 'ItemName', 'CompanyName', 'ProductName', 
        'DesignName', 'Color', 'SizeName', 'Size', 'Unit', 'unitname', 
        'SectionName', 'StkQty', 'StockQty', 'WHQTY', 'MRP', 'ACost', 
        'Markup', 'Discount', 'GST', 'HSNCODE', 'AccountID', 
        'MU', 'COLLECTIONNAME', 'QualityName', 'SupplierName', 
        'SupplierMobileNo', 'ContactPerson', 'ItemImage', 'IImage', 
        'ImageUrl', '__ItemStockUpdateDiscount' 
    ]; 

    $stockData = is_object($stock) 
        ? get_object_vars($stock) 
        : (array) $stock; 

    $dynamicFields = []; 

    $locationCodes = $locationCodes ?? []; 

    foreach ($locationCodes as $locationCode) { 
        if (isset($stockData[$locationCode])) { 
            $dynamicFields[$locationCode] = $stockData[$locationCode]; 
        } 
    } 
@endphp 

@if(count($dynamicFields)) 
    <table class="branch-table dynamic-branch-table"> 
        <thead> 
            <tr> 
                <td class="branch-header" style="width: 45%;">Field</td> 
                <td class="branch-header" style="width: 55%;">Value</td> 
            </tr> 
        </thead> 
        <tbody> 
            @foreach($dynamicFields as $fieldName => $fieldValue) 
                <tr> 
                    <td class="field-name"> 
                        {{ $fieldName }} 
                    </td> 
                    <td class="field-value"> 
                        @if(is_null($fieldValue) || $fieldValue === '') 
                            - 
                        @elseif(is_numeric($fieldValue)) 
                            {{ number_format((float) $fieldValue, 2) }} 
                        @else 
                            {{ $fieldValue }} 
                        @endif 
                    </td> 
                </tr> 
            @endforeach 
        </tbody> 
    </table> 
@else 
    <table class="branch-table dynamic-branch-table"> 
        <tr> 
            <td>-</td> 
        </tr> 
    </table> 
@endif 

<div class="section">Additional Details</div> 

<table> 
    <tr> 
        <td class="label">Quality Name</td> 
        <td class="value">{{ $stock->QualityName ?? '-' }}</td> 
    </tr> 
    @if(!empty($access['supplier_name']))
    <tr> 
        <td class="label">Supplier Name</td> 
        <td class="value">{{ $stock->SupplierName ?? '-' }}</td> 
    </tr>
    @endif 
    <tr> 
        <td class="label">HSN Code</td> 
        <td class="value">{{ trim($stock->HSNCODE ?? '-') }}</td> 
    </tr> 
    <tr> 
        <td class="label">GST (%)</td> 
        <td class="value">{{ $stock->GST ?? '-' }}</td> 
    </tr> 
    @if(!empty($access['markup']))
    <tr> 
        <td class="label">MU</td> 
        <td class="value">{{ $stock->MU ?? '-' }}</td> 
    </tr>
    @endif 
</table> 

</body> 
</html>