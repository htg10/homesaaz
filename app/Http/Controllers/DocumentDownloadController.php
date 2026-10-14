<?php

namespace App\Http\Controllers;

use App\Services\LiveDocumentPrintService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Document Download v2.
 *
 * Does NOT use the DocumentPrint / DocType tables anymore. Every document
 * type (Invoice, Credit Card Invoice, Cash Memo, Quotation, Book Receipt,
 * Receipt, Debit Note, Credit Note, Performa) is read LIVE from its own
 * source table, on the SELECTED LOCATION's own database
 * (Location.DatabaseName) — same pattern as Floor Wise Sales.
 *
 * Flow: pick a Location -> pick a Document Type -> (date range + optional
 * search) -> list loads from that location's database -> click a row to
 * see the full header + line items.
 */
class DocumentDownloadController extends Controller
{
    public function list()
    {
        $locations = LiveDocumentPrintService::locations();
        $docTypes = LiveDocumentPrintService::docTypes();

        return view('documentDownload.list', compact('locations', 'docTypes'));
    }

    /**
     * AJAX: ?locationID=8&docType=invoice&fromDate=2026-09-01&toDate=2026-09-30&search=...
     */
    public function getListData(Request $request)
    {
        // Large date ranges (e.g. Book Receipt) can run longer than PHP's 30s default.
        @set_time_limit(120);

        $locationID = (int) $request->input('locationID');
        $docType = trim((string) $request->input('docType', ''));
        $fromDate = trim((string) $request->input('fromDate', ''));
        $toDate = trim((string) $request->input('toDate', ''));
        $search = trim((string) $request->input('search', $request->input('search.value', '')));
        $start = max((int) $request->input('start', 0), 0);
        $length = min(max((int) $request->input('length', 10), 1), 100);

        if (!$locationID) {
            return response()->json(['success' => false, 'message' => 'Please select a Location.'], 422);
        }

        if ($docType === '' || !array_key_exists($docType, LiveDocumentPrintService::docTypes())) {
            return response()->json(['success' => false, 'message' => 'Please select a valid Document Type.'], 422);
        }

        if ($fromDate === '' || $toDate === '') {
            return response()->json(['success' => false, 'message' => 'Please select a From Date and To Date.'], 422);
        }

        try {
            $service = new LiveDocumentPrintService();
            $result = $service->list($docType, $locationID, $fromDate, $toDate, $search, $start, $length);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'database' => $result['database'],
                'start' => $start,
                'length' => $length,
                'recordsTotal' => $result['recordsTotal'],
                'recordsFiltered' => $result['recordsFiltered'],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('DocumentDownload getListData SQL error', [
                'locationID' => $locationID, 'docType' => $docType, 'error' => $e->getMessage(),
            ]);
            $message = config('app.debug')
                ? 'SQL Error: '.mb_substr($e->getMessage(), 0, 400)
                : 'Unable to load documents for this location. Please try again.';
            return response()->json(['success' => false, 'message' => $message], 500);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('DocumentDownload getListData error', [
                'locationID' => $locationID, 'docType' => $docType, 'error' => $e->getMessage(),
            ]);
            $message = config('app.debug')
                ? 'Error: '.$e->getMessage()
                : 'Unable to load documents. Please try again.';
            return response()->json(['success' => false, 'message' => $message], 500);
        }
    }

    /**
     * AJAX: ?locationID=8&docType=invoice&key=CM00123
     * `key` is whatever list() returned as DocKey for that row (CMNo for
     * most types, CaMoID for Book Receipt, ChequeID for Debit Note, CrId
     * for Credit Note).
     */
    public function liveDetail(Request $request)
    {
        $locationID = (int) $request->input('locationID');
        $docType = trim((string) $request->input('docType', ''));
        $key = trim((string) $request->input('key', ''));

        if (!$locationID || $docType === '' || $key === '') {
            return response()->json([
                'success' => false,
                'message' => 'locationID, docType and key are all required.',
            ], 422);
        }

        if (!array_key_exists($docType, LiveDocumentPrintService::docTypes())) {
            return response()->json(['success' => false, 'message' => 'Unknown document type.'], 422);
        }

        try {
            $service = new LiveDocumentPrintService();
            $rows = $service->detail($docType, $locationID, $key);

            return response()->json([
                'success' => true,
                'docType' => $docType,
                'key' => $key,
                'rowCount' => count($rows),
                'data' => $rows,
            ]);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('DocumentDownload liveDetail error', [
                'locationID' => $locationID, 'docType' => $docType, 'key' => $key, 'error' => $e->getMessage(),
            ]);
            $message = config('app.debug')
                ? 'Error: '.$e->getMessage()
                : 'Unable to load document detail. Please try again.';
            return response()->json(['success' => false, 'message' => $message], 500);
        }
    }

    /**
     * Download one document's data as a PDF.
     *
     * Shows EVERY column from detail() for EVERY row, in one table — no
     * column is hidden or assumed to be "header-only", because several
     * genuinely different line items can legitimately share the same
     * ItemCode/Rate/HSN etc. (e.g. the same product sold in separate
     * lots), and an earlier version wrongly collapsed those into a
     * single header row and lost the per-line data. Document-level
     * fields (CMNo, CMDate, party name, address, ...) just repeat down
     * every row — that's normal for a flat detail query and far safer
     * than guessing what's "the same for a reason" vs "same for this
     * particular document".
     *
     * This works generically across all 9 doc types without a hand-built
     * layout per type. It's a working, complete PDF — not a formatted
     * GST invoice/credit-note design. Once the live data for each type
     * is confirmed correct, a proper per-type print layout (header once
     * at top, line items below) can replace this.
     */
    public function downloadPdf(Request $request)
    {
        // PDF generation (live SQL detail query + dompdf layout) can exceed
        // PHP's default 30s limit on large invoices or slow locations.
        @set_time_limit(180);
        $startedAt = microtime(true);

        $locationID = (int) $request->input('locationID');
        $docType = trim((string) $request->input('docType', ''));
        $key = trim((string) $request->input('key', ''));

        if (!$locationID || $docType === '' || $key === '') {
            abort(422, 'locationID, docType and key are all required.');
        }

        $docTypes = LiveDocumentPrintService::docTypes();
        if (!array_key_exists($docType, $docTypes)) {
            abort(422, 'Unknown document type.');
        }

        try {
            $service = new LiveDocumentPrintService();
            $rows = $service->detail($docType, $locationID, $key);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            abort(422, $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('DocumentDownload downloadPdf error', [
                'locationID' => $locationID, 'docType' => $docType, 'key' => $key, 'error' => $e->getMessage(),
            ]);
            abort(500, 'Unable to generate PDF. Please try again.');
        }

        if (empty($rows)) {
            abort(404, 'Document not found.');
        }

        $rowsArr = array_map(fn ($r) => (array) $r, $rows);
        $safeKey = preg_replace('/[^A-Za-z0-9_-]+/', '_', $key) ?: 'document';
        $fileName = $docTypes[$docType].'_'.$safeKey.'.pdf';

        // Branded HomeSaaz letterhead layout for Invoice / Credit Card
        // Invoice / Cash Memo — matches the pre-printed paper format.
        // ⚠️ This letterhead (logo, branch list, category footer) is
        // specific to the HomeSaaz brand — it will show on every
        // location using this doc type, including non-HomeSaaz-branded
        // locations like AVENUE37. If AVENUE37 (or others) use a
        // different letterhead, tell me and I'll make this
        // location-aware instead of a single fixed design.
        // Full A4 letterhead of the location: Invoice, Quotation, Debit Note, Performa.
        // Small landscape slip of the location: Cash Memo, Credit Card Invoice,
        // Book Receipt, Receipt, Credit Note.
        $fullPageTypes = ['invoice', 'quotation', 'debitNote', 'performa'];
        $slipTypes = ['cashMemo', 'creditCardInvoice', 'bookReceipt', 'receipt', 'creditNote'];

        if (in_array($docType, $fullPageTypes, true) || in_array($docType, $slipTypes, true)) {
            $isSlip = in_array($docType, $slipTypes, true);
            $header = $rowsArr[0];

            // Pick the location's printed artwork (background + data positions).
            $lh = config('invoice_letterheads');
            if ($isSlip) {
                $tplKey = $lh['slip_map'][$locationID] ?? $lh['slip_default'];
                $tpl = $lh['slip_templates'][$tplKey] ?? $lh['slip_templates'][$lh['slip_default']];
            } else {
                $tplKey = $lh['location_map'][$locationID] ?? $lh['default'];
                $tpl = $lh['templates'][$tplKey] ?? $lh['templates'][$lh['default']];
            }

            // Background embedded as base64 so dompdf needs no file access.
            $bgPath = public_path('assets/'.$tpl['background']);
            $bg = file_exists($bgPath)
                ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($bgPath))
                : null;

            Log::info('DocumentDownload letterhead PDF', [
                'locationID' => $locationID, 'docType' => $docType, 'key' => $key,
                'template' => $tplKey, 'slip' => $isSlip, 'background_found' => $bg !== null,
                'seconds' => round(microtime(true) - $startedAt, 2),
            ]);

            // mm -> points for dompdf custom paper size
            $widthPt = $tpl['page'][0] * 72 / 25.4;
            $heightPt = $tpl['page'][1] * 72 / 25.4;

            $pdf = Pdf::loadView('documentDownload.invoice-letterhead', [
                'docTypeLabel' => strtoupper($docTypes[$docType]),
                'showItemCode' => true,
                'docKey' => $key,
                'header' => $header,
                'items' => $rowsArr,
                'tpl' => $tpl,
                'bg' => $bg,
            ])->setPaper([0, 0, $widthPt, $heightPt]);

            return $pdf->download($fileName);
        }

        $columns = array_keys($rowsArr[0]);

        // CamelCase column name -> "Camel Case" label, same idea as the
        // JS prettifier used in the list page.
        $prettify = function (string $key) {
            $label = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $key);
            $label = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1 $2', $label);
            return trim($label);
        };

        $pdf = Pdf::loadView('documentDownload.pdf', [
            'docTypeLabel' => $docTypes[$docType],
            'docKey' => $key,
            'columns' => $columns,
            'rows' => $rowsArr,
            'prettify' => $prettify,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }
}