@php $pageW = $tpl['page'][0]; $pageH = $tpl['page'][1]; @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $docTypeLabel }} - {{ $docKey }}</title>
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; color: #222; }
        .page { position: relative; width: {{ $pageW }}mm; height: {{ $pageH }}mm; overflow: hidden; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .bg { position: absolute; left: 0; top: 0; width: {{ $pageW }}mm; height: {{ $pageH }}mm; }
        .abs { position: absolute; }
        .cell { position: absolute; overflow: hidden; white-space: nowrap; font-size: 7.5pt; padding: 0 1mm; box-sizing: border-box; }
        .cell.r { padding-right: 2mm; }
        .it td { padding: 0 1mm; font-size: 7.5pt; white-space: nowrap; overflow: hidden; vertical-align: middle; }
        .c { text-align: center; } .l { text-align: left; } .r { text-align: right; }
        .box { font-size: 8pt; line-height: 1.35; }
        .cmp { font-size: 6.8pt; line-height: 1.12; }
        .lbl { color: #555; font-size: 7pt; }
        .tot { position: absolute; width: 100%; border-collapse: collapse; font-size: 8pt; }
        .tot td { padding: 0.6mm 1mm; }
        .tot .net td { font-weight: bold; font-size: 9pt; border-top: 0.4mm solid #333; }
        .tot.gst td { padding: 0.4mm 1mm; font-size: 7.5pt; text-align: left; }
        .tot.gst .net td { font-size: 8pt; }
        /* plain details page (after the letterhead pages) */
        .plain { font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; color: #222; padding: 12mm; }
        .plain h3 { font-size: 10pt; margin: 4mm 0 2mm; background: #eee; padding: 2mm; }
        .kv { width: 100%; border-collapse: collapse; }
        .kv td { border-bottom: 0.2mm solid #ddd; padding: 1mm; font-size: 7.5pt; word-wrap: break-word; }
        .kv .k { width: 22%; color: #7A1430; font-weight: bold; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid th, .grid td { border: 0.2mm solid #ddd; padding: 1mm; font-size: 7pt; }
        .grid th { background: #f3f3f3; text-align: left; }
    </style>
</head>
<body>

@php
    // Only the party fields this sheet actually has (config: party_fields). Default: all.
    $partyFields = $tpl['party_fields'] ?? ['name', 'address', 'city', 'cell', 'gstin'];
    $pageW = $tpl['page'][0];
    $pageH = $tpl['page'][1];
    $it = $tpl['items'];
    $cols = $it['columns'];
    $rowH = $it['row_h'];
    $top = $it['top'];
    $bottom = $it['bottom'];
    $perPage = max(1, (int) floor(($bottom - $top) / $rowH));

    // Credit note (and other docs) use different column aliases than the cash memo / invoice keys.
    // Map them onto the keys this sheet reads, without overriding values that already exist.
    $items = array_map(function ($r) {
        $r['Quantity'] = $r['Quantity'] ?? $r['Qty'] ?? null;
        $r['TaxPrate'] = $r['TaxPrate'] ?? $r['TaxPRate'] ?? null;
        $r['Amount']   = $r['Amount'] ?? $r['Amt'] ?? null;
        $r['HSNCODE']  = $r['HSNCODE'] ?? $r['HsnCode'] ?? null;
        return $r;
    }, $items);
    if (empty($header['AccName']) && !empty($header['CustName'])) { $header['AccName'] = $header['CustName']; }
    if (empty($header['CMDate']) && !empty($header['CrDate'])) { $header['CMDate'] = $header['CrDate']; }
    if (!isset($header['NetAmt']) && isset($header['CrNetAmt'])) { $header['NetAmt'] = $header['CrNetAmt']; }
    // Show the user-facing document number (CrNo / CMNo), not the internal key (CrId).
    $displayNo = $header['CMNo'] ?? $header['CrNo'] ?? $header['ChequeNo'] ?? $docKey;
    if (empty($header['CMDate']) && !empty($header['ChequeDate'])) { $header['CMDate'] = $header['ChequeDate']; }
    // Debit notes carry only a header amount (CAmount) and no item lines: show it as one line.
    $hasItemAmount = count(array_filter(array_column($items, 'Amount'), fn ($v) => $v !== null && $v !== '')) > 0;
    if (!$hasItemAmount && isset($header['CAmount']) && $header['CAmount'] !== null && $header['CAmount'] !== '') {
        $items = [['ItemName' => 'Debit Note', 'Amount' => $header['CAmount']]];
        if (!isset($header['NetAmt'])) { $header['NetAmt'] = $header['CAmount']; }
    }


    // Tax inclusive or exclusive. Credit notes carry TaxType (INC/EX); other documents carry
    // IncTax / ExTax flags on the detail rows. Unknown -> exclusive (the old behaviour).
    $flagOn = fn ($v) => in_array(strtoupper((string) $v), ['1', 'Y', 'YES', 'TRUE', 'T'], true);
    $taxType = null;
    foreach ($items as $r) {
        if (!empty($r['TaxType'])) { $taxType = strtoupper(trim((string) $r['TaxType'])) === 'INC' ? 'INC' : 'EX'; break; }
        if ($flagOn($r['ExTax'] ?? null)) { $taxType = 'EX'; break; }
        if ($flagOn($r['IncTax'] ?? null)) { $taxType = 'INC'; break; }
    }
    $isInclusive = $taxType === 'INC';
    // Inclusive: the printed Amount already contains GST, so back-calculate it when the stored TaxAmt is missing.
    if ($isInclusive) {
        $items = array_map(function ($r) {
            $rt = (float) ($r['TaxPrate'] ?? 0);
            if (empty($r['TaxAmt']) && $rt > 0 && isset($r['Amount'])) {
                $r['TaxAmt'] = round((float) $r['Amount'] * $rt / (100 + $rt), 2);
            }
            return $r;
        }, $items);
    }

    // Receipts have no item lines: put the payment details (pay mode, cheque no, cheque date) in the description cell.
    if (isset($header['RecNo'])) {
        $recParts = array_filter([
            trim((string) ($header['PayMode'] ?? '')),
            !empty($header['CheqNo']) ? 'No ' . trim((string) $header['CheqNo']) : null,
            !empty($header['CheqDate']) ? \Carbon\Carbon::parse($header['CheqDate'])->format('d-M-y') : null,
        ]);
        $recText = $recParts ? implode(' | ', $recParts) : 'Receipt';
        if (!count($items)) {
            $items = [['Amount' => $header['Amount'] ?? null]];
        }
        foreach ($items as $i => $row) {
            if (empty($row['ItemName'])) { $items[$i]['ItemName'] = $recText; }
        }
        if (!isset($header['NetAmt']) && isset($header['Amount'])) { $header['NetAmt'] = $header['Amount']; }
    }

    $grossAmount = array_sum(array_column($items, 'Amount'));
    $discAmt = $header['AddDiscAmt'] ?? $header['DiscAmt'] ?? null;
    $taxAmt = array_sum(array_column($items, 'TaxAmt'));
    $roundDisc = $header['RoundDisc'] ?? null;
    // Inclusive bills: tax is already inside the gross, so it is not added again.
    $netAmt = $header['NetAmt'] ?? ($grossAmount + ($isInclusive ? 0 : $taxAmt) - (float) ($discAmt ?? 0));

    $chunks = count($items) ? array_chunk($items, $perPage, true) : [[]];
    $lastCount = count(end($chunks));
    $totalsY = $tpl['totals'][1];
    $totalsOwnPage = ($top + $lastCount * $rowH) > ($totalsY - 2);
    if ($totalsOwnPage) { $chunks[] = []; }

    $fmt = fn ($v) => $v === null || $v === '' ? '-' : number_format((float) $v, 2);
    $shortDate = !empty($header['CMDate']) ? \Carbon\Carbon::parse($header['CMDate'])->format('d-M-Y') : '-';
    // Cash memo customer name/address can sit in TCustName / TCustAdd when no account is linked.
    $partyName = !empty($header['AccName']) ? $header['AccName'] : (!empty($header['TCustName']) ? $header['TCustName'] : ($header['PartyName'] ?? ''));
    $partyAddr = !empty($header['Address']) ? $header['Address'] : ($header['TCustAdd'] ?? '');
    $cityLine = trim(($header['CityName'] ?? '') . ' ' . ($header['State'] ?? '') . ' ' . ($header['PIN'] ?? ''));

    $shownHeader = ['AccName','PartyName','Address','CityName','State','PIN','Cell','CSTNO','CMDate','EWAYBILL','QRImage'];
    $shownItem = ['ItemCode','HSNCODE','ItemName','ItemCatName','TaxPrate','Quantity','UnitName','UnitName2','UnitName1','Rate','ORate','Amount','TaxAmt','QRImage'];
    $extraHeader = array_filter($header, fn ($v, $k) => !in_array($k, $shownHeader, true) && $v !== null && $v !== '' && !is_object($v), ARRAY_FILTER_USE_BOTH);
    $extraItemKeys = [];
    foreach ($items as $row) {
        foreach (array_keys($row) as $k) {
            if (!in_array($k, $shownItem, true) && !in_array($k, $extraItemKeys, true) && $k !== 'QRImage') {
                $extraItemKeys[] = $k;
            }
        }
    }
    $prettyLabel = function (string $k) {
        $l = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $k);
        return trim(preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1 $2', $l));
    };
@endphp

{{-- ===================== LETTERHEAD PAGES ===================== --}}
@foreach ($chunks as $pageNo => $chunk)
<div class="page">
    <img class="bg" src="{{ $bg }}" alt="">

    {{-- Column titles for sheets whose artwork has the table header band but no printed labels --}}
    @foreach (($tpl['header_labels'] ?? []) as $hl)
        <div class="cell {{ $hl[3] === 'center' ? 'c' : ($hl[3] === 'right' ? 'r' : 'l') }}"
             style="left: {{ $hl[1] }}mm; width: {{ $hl[2] - $hl[1] }}mm; top: {{ $tpl['header_labels_y'] ?? 49 }}mm; height: 3mm; line-height: 3mm; font-size: 7pt; font-weight: bold;">{{ $hl[0] }}</div>
    @endforeach

    @if ($pageNo === 0)
        {{-- Party --}}
        <div class="abs box {{ !empty($tpl['compact']) ? 'cmp' : '' }}" style="left: {{ $tpl['party'][0] }}mm; top: {{ $tpl['party'][1] }}mm; width: {{ $tpl['party'][2] }}mm; height: {{ $tpl['party'][3] }}mm;">
            @if(empty($tpl['compact']))<span class="lbl">{{ $docTypeLabel }} To</span><br>@endif
            @if(in_array('name', $partyFields))<strong>{{ $partyName }}</strong><br>@endif
            @if(in_array('address', $partyFields) && !empty($partyAddr)){{ $partyAddr }}<br>@endif
            @if(in_array('city', $partyFields) && $cityLine !== ''){{ $cityLine }}<br>@endif
            @if(in_array('cell', $partyFields) && !empty($header['Cell']))Ph: {{ $header['Cell'] }}<br>@endif
            @if(in_array('gstin', $partyFields) && !empty($header['CSTNO']))GSTIN: {{ $header['CSTNO'] }}@endif
        </div>

        {{-- Document details --}}
        @if (!empty($tpl['invoice']))
        <div class="abs box {{ !empty($tpl['compact']) ? 'cmp' : '' }}" style="left: {{ $tpl['invoice'][0] }}mm; top: {{ $tpl['invoice'][1] }}mm; width: {{ $tpl['invoice'][2] }}mm; height: {{ $tpl['invoice'][3] }}mm;">
            <span class="lbl">No.</span> <strong>{{ $displayNo }}</strong><br>
            <span class="lbl">Date</span> {{ $shortDate }}<br>
            @if(!empty($header['EWAYBILL']))<span class="lbl">E-Way Bill</span> {{ $header['EWAYBILL'] }}@endif
        </div>
        @endif

        {{-- Templates with values printed beside labels (Live n Style) --}}
        @foreach (($tpl['header_values'] ?? []) as $hv)
            <div class="abs box" style="left: {{ $hv['x'] }}mm; top: {{ $hv['y'] }}mm; width: 60mm;">{{ $hv['key'] === 'CMDate' ? $shortDate : ((($header[$hv['key']] ?? '') !== '') ? (string) $header[$hv['key']] : '-') }}</div>
        @endforeach
    @endif

    {{-- Items: each cell is absolutely positioned on the sheet's own columns --}}
        @foreach ($chunk as $idx => $item)
            @php
                $serial = ($pageNo * $perPage) + $loop->index + 1;
                $rateVal = $item['Rate'] ?? $item['ORate'] ?? null;
                $unitVal = $item['UnitName'] ?? $item['UnitName2'] ?? $item['UnitName1'] ?? '-';
                $descVal = (!empty($item['ItemCatName']) ? $item['ItemCatName'] : ($item['ItemName'] ?? '-'));
                // When the only name we have is the HSN code itself, don't repeat it as the description.
                if (($item['ItemName'] ?? null) !== null && (string) $item['ItemName'] === (string) ($item['HSNCODE'] ?? $item['HsnCode'] ?? '')) { $descVal = '-'; }
                $cellMap = [
                    'sno' => $serial,
                    'item_code' => $item['ItemCode'] ?? '-',
                    'hsn' => $item['HSNCODE'] ?? '-',
                    'desc' => $descVal,
                    'gst' => $item['TaxPrate'] ?? '-',
                    'qty' => $item['Quantity'] ?? '-',
                    'unit' => $unitVal,
                    'rate' => $fmt($rateVal),
                    'amount' => $fmt($item['Amount'] ?? null),
                ];
                $rowTop = $top + $loop->index * $rowH;
                // Long descriptions wrap onto a second line inside the same row (smaller font),
                // so nothing is cut off. Two lines fit in one row of ~6mm.
                // Width-based: how many characters fit the description column at normal size (~1.7mm per char).
                $descColW = 0;
                foreach ($cols as $dc) { if ($dc[0] === 'desc') { $descColW = $dc[3] - $dc[2]; } }
                $descCap = (int) floor(max(1, $descColW - 2) / 1.7);
                $descLen = mb_strlen((string) $cellMap['desc']);
                $descWrap = $descLen > $descCap
                    ? ' font-size: 5pt; white-space: normal; word-break: break-word; line-height: 2.4mm; padding-top: 0.6mm; height: ' . number_format($rowH, 2, '.', '') . 'mm;'
                    : '';
            @endphp
            @foreach ($cols as $c)
                @if ($c[0] === 'item_code' && !$showItemCode) @continue @endif
                <div class="cell {{ $c[4] === 'center' ? 'c' : ($c[4] === 'right' ? 'r' : 'l') }}"
                     style="left: {{ $c[2] }}mm; width: {{ $c[3] - $c[2] }}mm; top: {{ $rowTop }}mm; height: {{ $rowH }}mm; line-height: {{ $rowH }}mm;{{ $c[0] === 'desc' ? $descWrap : '' }}">{{ isset($cellMap[$c[0]]) ? $cellMap[$c[0]] : '' }}</div>
            @endforeach
        @endforeach

    {{-- Totals on the last letterhead page --}}
    @if ($pageNo === count($chunks) - 1)
        <table class="tot" style="left: {{ $tpl['totals'][0] }}mm; top: {{ $tpl['totals'][1] }}mm; width: {{ $tpl['totals'][2] }}mm;">
            <tr><td>Gross Amount</td><td class="r">{{ $fmt($grossAmount) }}</td></tr>
            @if($discAmt !== null)<tr><td>Discount</td><td class="r">{{ $fmt($discAmt) }}</td></tr>@endif
            @if($roundDisc !== null)<tr><td>Round Off</td><td class="r">{{ $fmt($roundDisc) }}</td></tr>@endif
            <tr class="net"><td>Net Amount</td><td class="r">{{ $fmt($netAmt) }}</td></tr>
        </table>

        {{-- GST breakdown, printed on the left of the totals (like the printed bills) --}}
        @php
            // Intra-state -> CGST + SGST (half each); inter-state (TaxName has IGST) -> IGST.
            $taxNames = array_unique(array_filter(array_map(fn ($r) => strtoupper((string) ($r['TaxName'] ?? '')), $items)));
            $isIgst = count($taxNames) && str_contains(implode(' ', $taxNames), 'IGST');
            // Tax grouped by rate, e.g. 5% and 18% lines each get their own CGST/SGST (or IGST) row.
            $byRate = [];
            foreach ($items as $r) {
                $rt = (float) ($r['TaxPrate'] ?? 0);
                if ($rt <= 0) continue;
                $k = (string) $rt;
                $byRate[$k] = ['rate' => $rt, 'amt' => ($byRate[$k]['amt'] ?? 0) + (float) ($r['TaxAmt'] ?? 0)];
            }
            ksort($byRate, SORT_NUMERIC);
            $fmtRate = fn ($r) => rtrim(rtrim(number_format($r, 2, '.', ''), '0'), '.');
            // GST box position per artwork, matched by artwork file name (works whatever the folder path is).
            $gstPos = [
                'hsd_full' => [6.3, 212.0],
                'ljp_full' => [7.6, 200.0],
                'hsd_slip' => [4.0, 74.0],
                'ljp_slip' => [4.0, 74.0],
                'av_full'  => [8.0, 150.0],
                'av_slip'  => [8.0, 92.0],
                'rjc_slip' => [4.0, 86.0],
                'ls_slip'  => [6.0, 100.0],
                'ls_full'  => [6.3, 226.0],
            ];
            $gstFix = null;
            foreach ($gstPos as $needle => $pos) {
                if (stripos((string) ($tpl['background'] ?? ''), $needle) !== false) { $gstFix = $pos; break; }
            }
            $gstBoxX = $gstFix[0] ?? ($tpl['gst_box'][0] ?? ($tpl['totals'][0] - 56));
            $gstBoxY = $gstFix[1] ?? ($tpl['gst_box'][1] ?? $tpl['totals'][1]);
        @endphp
        <table class="tot gst" style="left: {{ $gstBoxX }}mm; top: {{ $gstBoxY }}mm; width: 52mm;">
            @if ($taxType)
                <tr><td><em>GST {{ $isInclusive ? 'Inclusive' : 'Exclusive' }}</em></td></tr>
            @endif
            @foreach ($byRate as $g)
                @if ($isIgst)
                    <tr><td>IGST {{ $fmtRate($g['rate']) }}% = {{ $fmt($g['amt']) }}</td></tr>
                @else
                    <tr><td>SGST {{ $fmtRate($g['rate'] / 2) }}% = {{ $fmt($g['amt'] / 2) }}</td></tr>
                    <tr><td>CGST {{ $fmtRate($g['rate'] / 2) }}% = {{ $fmt($g['amt'] / 2) }}</td></tr>
                @endif
            @endforeach
            <tr class="net"><td>Total = {{ $fmt($taxAmt) }}</td></tr>
        </table>
    @endif
</div>
@endforeach

</body>
</html>