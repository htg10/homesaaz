<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\LiveDocumentPrintService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Document Download API (live).
 *
 * Reads every document LIVE from the selected location's own sales database
 * (the same source as the web Document Download page). The DocumentPrint /
 * DocType tables are not used anymore.
 */
class DocumentDownloadController extends Controller
{
    private const DOC_TYPE_RULE_PREFIX = 'in:';

    /** GET /api/documentdownload/locations */
    public function getLocations()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => LiveDocumentPrintService::locations(),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError('getLocations', $e, 'Error loading locations.');
        }
    }

    /** GET /api/documentdownload/doc-types */
    public function getDocTypes()
    {
        return response()->json([
            'success' => true,
            'data' => LiveDocumentPrintService::docTypes(),
        ]);
    }

    /**
     * GET /api/documentdownload/data
     *   ?locationID=10&docType=cashMemo&fromDate=2026-10-01&toDate=2026-10-10
     *   &search=ICICI&start=0&length=10
     */
    public function getListData(Request $request)
    {
        @set_time_limit(120);

        $validated = $request->validate([
            'locationID' => 'required|integer|min:1',
            'docType' => 'required|string|' . self::DOC_TYPE_RULE_PREFIX . implode(',', array_keys(LiveDocumentPrintService::docTypes())),
            'fromDate' => 'required|date_format:Y-m-d',
            'toDate' => 'required|date_format:Y-m-d|after_or_equal:fromDate',
            'search' => 'nullable|string|max:100',
            'start' => 'nullable|integer|min:0',
            'length' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $result = (new LiveDocumentPrintService())->list(
                $validated['docType'],
                (int) $validated['locationID'],
                $validated['fromDate'],
                $validated['toDate'],
                trim((string) ($validated['search'] ?? '')),
                (int) ($validated['start'] ?? 0),
                (int) ($validated['length'] ?? 10)
            );

            return response()->json(['success' => true] + $result);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->serverError('getListData', $e, 'Error fetching data.');
        }
    }

    /** GET /api/documentdownload/detail?locationID=10&docType=cashMemo&key=754 */
    public function detail(Request $request)
    {
        $validated = $this->validateDocument($request);

        try {
            $rows = (new LiveDocumentPrintService())->detail(
                $validated['docType'],
                (int) $validated['locationID'],
                trim($validated['key'])
            );

            if (empty($rows)) {
                return response()->json(['success' => false, 'message' => 'Document not found'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => array_map(fn ($r) => (array) $r, $rows),
            ]);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->serverError('detail', $e, 'Error loading document.');
        }
    }

    /**
     * GET /api/documentdownload/pdf?locationID=10&docType=cashMemo&key=754[&inline=1]
     * Letterhead PDF (attachment by default, inline with inline=1).
     */
    public function download(Request $request)
    {
        @set_time_limit(180);

        $validated = $this->validateDocument($request, true);

        try {
            $result = $this->renderLetterhead(
                (int) $validated['locationID'],
                $validated['docType'],
                trim($validated['key'])
            );
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\OutOfBoundsException $e) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        } catch (\Throwable $e) {
            return $this->serverError('download', $e, 'Unable to generate PDF.');
        }

        return $request->boolean('inline')
            ? $result['pdf']->stream($result['fileName'])
            : $result['pdf']->download($result['fileName']);
    }

    /**
     * Builds the letterhead PDF for one document using LiveDocumentPrintService
     * for the data and the Blade letterhead layout for the print.
     *
     * @return array{pdf: \Barryvdh\DomPDF\PDF, fileName: string}
     */
    private function renderLetterhead(int $locationID, string $docType, string $key): array
    {
        $docTypes = LiveDocumentPrintService::docTypes();

        $rows = (new LiveDocumentPrintService())->detail($docType, $locationID, $key);
        $rowsArr = array_map(fn ($r) => (array) $r, $rows);
        if (empty($rowsArr)) {
            throw new \OutOfBoundsException('Document not found.');
        }

        // Full A4 letterhead: invoice, quotation, debitNote, performa.
        // Small landscape slip: cashMemo, creditCardInvoice, bookReceipt, receipt, creditNote.
        $slipTypes = ['cashMemo', 'creditCardInvoice', 'bookReceipt', 'receipt', 'creditNote'];
        $isSlip = in_array($docType, $slipTypes, true);

        $lh = config('invoice_letterheads');
        if ($isSlip) {
            $tplKey = $lh['slip_map'][$locationID] ?? $lh['slip_default'];
            $tpl = $lh['slip_templates'][$tplKey] ?? $lh['slip_templates'][$lh['slip_default']];
        } else {
            $tplKey = $lh['location_map'][$locationID] ?? $lh['default'];
            $tpl = $lh['templates'][$tplKey] ?? $lh['templates'][$lh['default']];
        }

        // Background embedded as base64 so dompdf needs no file access.
        $bgPath = public_path('assets/' . $tpl['background']);
        $bg = file_exists($bgPath)
            ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($bgPath))
            : null;

        // mm -> points for dompdf custom paper size
        $widthPt = $tpl['page'][0] * 72 / 25.4;
        $heightPt = $tpl['page'][1] * 72 / 25.4;

        $pdf = Pdf::loadView('documentDownload.invoice-letterhead', [
            'docTypeLabel' => strtoupper($docTypes[$docType]),
            'showItemCode' => true,
            'docKey' => $key,
            'header' => $rowsArr[0],
            'items' => $rowsArr,
            'tpl' => $tpl,
            'bg' => $bg,
        ])->setPaper([0, 0, $widthPt, $heightPt]);

        $safeKey = preg_replace('/[^A-Za-z0-9_-]+/', '_', $key) ?: 'document';

        return [
            'pdf' => $pdf,
            'fileName' => $docTypes[$docType] . '_' . $safeKey . '.pdf',
        ];
    }

    /** Shared validation for detail and pdf endpoints. */
    private function validateDocument(Request $request, bool $withInline = false): array
    {
        $rules = [
            'locationID' => 'required|integer|min:1',
            'docType' => 'required|string|' . self::DOC_TYPE_RULE_PREFIX . implode(',', array_keys(LiveDocumentPrintService::docTypes())),
            'key' => 'required|string|max:100',
        ];
        if ($withInline) {
            $rules['inline'] = 'nullable|boolean';
        }

        return $request->validate($rules);
    }

    private function serverError(string $where, \Throwable $e, string $message)
    {
        Log::error('DocumentDownload API ' . $where . ' error', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json(['success' => false, 'message' => $message], 500);
    }
}