<?php

namespace App\Services;

use App\Services\Concerns\ResolvesLocationDatabase;
use Illuminate\Support\Facades\Log;

/**
 * Document Download v2 — does NOT use the DocumentPrint table at all.
 *
 * Every document type is read LIVE from its own source table, on the
 * SELECTED LOCATION's own database (Location.DatabaseName) — same
 * pattern as App\Services\FloorWiseSalesService, via the
 * ResolvesLocationDatabase trait.
 *
 * Two kinds of query per document type:
 *  - list*()   — lightweight header rows for the grid (date range + search)
 *  - detail()  — full header + line items for one document (the queries
 *                you supplied), used when a row is opened
 *
 * ⚠️ IMPORTANT — the list*() queries below are NEW: you only gave us the
 * per-document DETAIL queries, not "show me every Invoice in a date
 * range" queries. I derived these from the same tables/joins as your
 * detail queries, but they are a DRAFT — please check a few rows against
 * what you'd expect to see before relying on them. Each one is marked
 * below.
 *
 * ⚠️ Also still open from before (see each method):
 *  - quotation() / performa() / receipt() detail queries had no WHERE
 *    clause in what you supplied — `CMNo` / `RecNo` was added as a guess.
 *
 * Per-location schema differences (confirmed via AVENUE37's table list,
 * plus a 'Tax' error seen on HOMESAAZ_HSN / Noida):
 *  - `ItemUnit`, `CCard`, `ItemCat`, `Country`, `SiteMan`, `TaxForm`,
 *    `TempPer`, `Tax` do not exist in every location's database —
 *    AVENUE37 has none of the first seven, HOMESAAZ_HSN has no `Tax`.
 *    Different locations are missing different tables, so every one of
 *    these is checked independently per-connection, not assumed from
 *    one location's result.
 *  - `SiteMan` and `TaxForm` were joined in your original queries but
 *    never actually selected anywhere — those joins were just removed.
 *  - `ItemUnit`, `CCard`, `ItemCat`, `Country`, `Tax` ARE used (columns
 *    are selected from them), so those joins are now OPTIONAL: see
 *    optionalJoin()/optionalColumn() below — if the table doesn't exist
 *    on the current location's database, the join is skipped and the
 *    column comes back NULL instead of the whole query erroring.
 *  - `TempPer` (performa()'s `INNER JOIN TempPer`) has been REMOVED
 *    entirely rather than made optional: it's an INNER JOIN, so on a
 *    location where it doesn't exist/doesn't match, it would silently
 *    return zero rows rather than erroring — worse than just dropping
 *    it. If TempPer turns out to matter for filtering in some location,
 *    let me know and we'll re-add it properly.
 *  - If another location is missing a DIFFERENT table than these seven,
 *    the query for that table will still error — send the error message
 *    the same way and I'll add it to this resilient list.
 */
class LiveDocumentPrintService
{
    use ResolvesLocationDatabase;

    /**
     * Some location databases don't have every table (confirmed: AVENUE37
     * has no ItemUnit and no CCard table at all — not a naming
     * difference, they're simply absent there). Rather than let the
     * whole query fail, optional joins are built through the two helpers
     * below: if the table isn't in THIS connection's database, the join
     * is skipped and the column comes back as NULL instead of erroring.
     * Cached per database so this check runs once, not once per request.
     */
    private function tableExists($connection, string $table): bool
    {
        $database = $connection->getDatabaseName();

        return \Illuminate\Support\Facades\Cache::remember(
            'live_doc.table_exists.'.$database.'.'.$table,
            3600,
            function () use ($connection, $table) {
                $row = $connection->selectOne(
                    'SELECT COUNT(*) AS Cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?',
                    [$table]
                );
                return !empty($row) && (int) $row->Cnt > 0;
            }
        );
    }

    /** `LEFT OUTER JOIN <table> AS <alias> ON <onClause>`, or '' if the table doesn't exist on this connection. */
    private function optionalJoin($connection, string $table, string $alias, string $onClause): string
    {
        if (!$this->tableExists($connection, $table)) {
            return '';
        }
        return "LEFT OUTER JOIN {$table} AS {$alias} ON {$onClause}";
    }

    /** `<alias>.<column> AS <asName>`, or `NULL AS <asName>` if the table backing $alias doesn't exist on this connection. */
    private function optionalColumn($connection, string $table, string $alias, string $column, string $asName): string
    {
        if (!$this->tableExists($connection, $table)) {
            return "NULL AS {$asName}";
        }
        return "{$alias}.{$column} AS {$asName}";
    }

    /**
     * Document types this module currently supports (key => label).
     * The key is also the method-name suffix used internally.
     */
    public static function docTypes(): array
    {
        return [
            'invoice' => 'Invoice',
            'creditCardInvoice' => 'Credit Card Invoice',
            'cashMemo' => 'Cash Memo',
            'quotation' => 'Quotation',
            'bookReceipt' => 'Book Receipt',
            'receipt' => 'Receipt',
            'debitNote' => 'Debit Note',
            'creditNote' => 'Credit Note',
            'performa' => 'Performa',
        ];
    }

    /** Locations that have a Sales Database set (for the Location dropdown). */
    public static function locations(): array
    {
        return self::locationsWithDatabase();
    }

    /* ==================================================================
     | LIST — lightweight rows for the grid
     * ================================================================== */

    /**
     * Pagination note: these queries are already bounded by the date range
     * (and optional search) on the SQL Server side, so for a typical date
     * range the result set is small enough to fetch once and page through
     * in PHP — same fallback approach GRN::getGRNData() uses. If a
     * location's date range turns out to return a huge number of rows,
     * this is the place to switch to ROW_NUMBER()-based paging on the SQL
     * side (see DocumentDownloadController's old getListData() for that
     * pattern, kept in git history).
     *
     * @return array{data: array, database: string, recordsTotal: int, recordsFiltered: int}
     */
    public function list(string $docType, int $locationId, string $dateFrom, string $dateTo, string $search = '', int $start = 0, int $length = 50): array
    {
        $connection = self::connectionForLocation($locationId);
        $database = self::databaseForLocation($locationId);

        $method = 'list'.ucfirst($docType);
        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException("Unknown document type \"{$docType}\".");
        }

        try {
            [$sql, $bindings] = $this->{$method}($connection, $dateFrom, $dateTo, $search);

            // Strip the trailing ORDER BY so the query can be wrapped as a
            // derived table; ordering + paging are applied in SQL Server.
            $orderPos = strripos($sql, 'ORDER BY');
            $base = $orderPos !== false ? substr($sql, 0, $orderPos) : $sql;

            $countRow = $connection->selectOne(
                "SELECT COUNT(*) AS Cnt FROM ({$base}) AS list_base",
                $bindings
            );
            $total = (int) ($countRow->Cnt ?? 0);

            // ROW_NUMBER() paging works on older SQL Server versions too
            // (OFFSET/FETCH needs SQL Server 2012+).
            $from = max(0, $start);
            $to = $from + max(1, $length);
            $pageRows = $connection->select(
                "SELECT * FROM (
                    SELECT list_base.*,
                           ROW_NUMBER() OVER (ORDER BY list_base.DocDate DESC, list_base.DocKey DESC) AS RowNum
                    FROM ({$base}) AS list_base
                 ) AS list_paged
                 WHERE list_paged.RowNum > ? AND list_paged.RowNum <= ?
                 ORDER BY list_paged.RowNum",
                array_merge($bindings, [$from, $to])
            );
        } catch (\Throwable $e) {
            Log::error("LiveDocumentPrintService::{$method} failed", [
                'locationId' => $locationId,
                'database' => $database,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        // Drop the paging helper column so it does not show up as a table column.
        foreach ($pageRows as $row) {
            unset($row->RowNum);
        }

        return [
            'data' => $pageRows,
            'database' => $database,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
        ];
    }

    /**
     * All list*() methods now return the full set of HEADER-level columns
     * (everything your detail query has at the CashMemo/TblQutation/etc.
     * header level) — not just DocNo/Date/Party/Amount. Line-item columns
     * (one row per product) are still only in detail() since a list row
     * is one document, not one line.
     *
     * DocKey/DocDate/PartyName/Amount are kept as the first 4 columns
     * (the frontend uses these 4 by name for sorting/the View button) —
     * everything after that is extra and shown as additional columns.
     */
    private function listInvoice($connection, string $from, string $to, string $search): array
    {
        $sql = "
            SELECT
                CashMemo.CMNo AS DocKey,
                CashMemo.CMDate AS DocDate,
                Accounts_1.AccName AS PartyName,
                (SELECT SUM(d.Amount) FROM CashMemoDet d WHERE d.CaMoID = CashMemo.CaMoID) AS Amount,
                Employee.EmpName AS SalesMan,
                Employee_1.EmpName AS Tailor,
                CashMemo.SalesSchemeName,
                CashMemo.AddDiscAmt,
                CashMemo.RoundDisc,
                CashMemo.CartAmt,
                CashMemo.RemarksP AS Remarks,
                CashMemo.EWAYBILL,
                CashMemo.IRN,
                City.Name AS City,
                Address.State
            FROM CashMemo
            LEFT OUTER JOIN Accounts AS Accounts_1 ON CashMemo.AccountID = Accounts_1.AccountID
            LEFT OUTER JOIN Address ON Accounts_1.AccountID = Address.AccountID
            LEFT OUTER JOIN City ON Address.CityID = City.CityID
            LEFT OUTER JOIN Employee ON CashMemo.SiteManID = Employee.EmployeeID
            LEFT OUTER JOIN Employee AS Employee_1 ON CashMemo.TailorID = Employee_1.EmployeeID
            WHERE CashMemo.FInvoice = 1 AND CashMemo.CC = 0
                AND CashMemo.CMDate BETWEEN ? AND ?
                ".$this->searchClause('CashMemo.CMNo', 'Accounts_1.AccName', $search)."
            ORDER BY CashMemo.CMDate DESC
        ";

        return [$sql, $this->bindings($from, $to, $search, 2)];
    }

    private function listCreditCardInvoice($connection, string $from, string $to, string $search): array
    {
        $sql = "
            SELECT
                CashMemo.CMNo AS DocKey,
                CashMemo.CMDate AS DocDate,
                Accounts_1.AccName AS PartyName,
                (SELECT SUM(d.Amount) FROM CashMemoDet d WHERE d.CaMoID = CashMemo.CaMoID) AS Amount,
                Employee.EmpName AS SalesMan,
                Employee_1.EmpName AS Tailor,
                CashMemo.SalesSchemeName,
                CashMemo.AddDiscAmt,
                CashMemo.RoundDisc,
                CashMemo.CartAmt,
                CashMemo.RemarksP AS Remarks,
                CashMemo.EWAYBILL,
                CashMemo.IRN,
                City.Name AS City,
                Address.State
            FROM CashMemo
            LEFT OUTER JOIN Accounts AS Accounts_1 ON CashMemo.AccountID = Accounts_1.AccountID
            LEFT OUTER JOIN Address ON Accounts_1.AccountID = Address.AccountID
            LEFT OUTER JOIN City ON Address.CityID = City.CityID
            LEFT OUTER JOIN Employee ON CashMemo.SiteManID = Employee.EmployeeID
            LEFT OUTER JOIN Employee AS Employee_1 ON CashMemo.TailorID = Employee_1.EmployeeID
            WHERE CashMemo.FInvoice = 1 AND CashMemo.CC = 1
                AND CashMemo.CMDate BETWEEN ? AND ?
                ".$this->searchClause('CashMemo.CMNo', 'Accounts_1.AccName', $search)."
            ORDER BY CashMemo.CMDate DESC
        ";

        return [$sql, $this->bindings($from, $to, $search, 2)];
    }

    private function listCashMemo($connection, string $from, string $to, string $search): array
    {
        // ⚠️ Accounts_1 here joins via BankID (same as your detail query) —
        // that's the payment/bank account, not necessarily the customer.
        // CashMemo didn't have an obvious customer-name column in what you
        // supplied. Confirm this is the right "party" to show.
        $sql = "
            SELECT
                CashMemo.CMNo AS DocKey,
                CashMemo.CMDate AS DocDate,
                Accounts_1.AccName AS PartyName,
                CashMemo.NetAmt AS Amount,
                Employee.EmpName AS SalesMan,
                CashMemo.GrossAmt,
                CashMemo.DiscAmt,
                CashMemo.TaxAmt,
                CashMemo.BillAmt,
                CashMemo.SalesSchemeName,
                CashMemo.Remarks,
                CashMemo.CCardID,
                CashMemo.SlipNo,
                CashMemo.ApprovalNo
            FROM CashMemo
            LEFT OUTER JOIN Accounts AS Accounts_1 ON CashMemo.BankID = Accounts_1.AccountID
            LEFT OUTER JOIN Employee ON CashMemo.SalManID = Employee.EmployeeID
            WHERE CashMemo.FCashMemo = 1
                AND CashMemo.CMDate BETWEEN ? AND ?
                ".$this->searchClause('CashMemo.CMNo', 'Accounts_1.AccName', $search)."
            ORDER BY CashMemo.CMDate DESC
        ";

        return [$sql, $this->bindings($from, $to, $search, 2)];
    }

    private function listQuotation($connection, string $from, string $to, string $search): array
    {
        $sql = "
            SELECT
                TblQutation.CMNo AS DocKey,
                TblQutation.CMDate AS DocDate,
                Accounts.AccName AS PartyName,
                (SELECT SUM(d.Amount) FROM TblQutationDet d WHERE d.CaMoID = TblQutation.CaMoID) AS Amount,
                Employee.EmpName AS SalesMan,
                Employee_1.EmpName AS Tailor,
                TblQutation.Attn,
                TblQutation.MobileNo,
                TblQutation.Delivery,
                TblQutation.Payment,
                TblQutation.Enquiry,
                TblQutation.RemarksP AS Remarks,
                TblQutation.AddDiscAmt,
                TblQutation.CartAmt,
                City.Name AS City
            FROM TblQutation
            LEFT OUTER JOIN Accounts ON TblQutation.AccountID = Accounts.AccountID
            LEFT OUTER JOIN Address ON Accounts.AccountID = Address.AccountID
            LEFT OUTER JOIN City ON Address.CityID = City.CityID
            LEFT OUTER JOIN Employee ON TblQutation.SiteManID = Employee.EmployeeID
            LEFT OUTER JOIN Employee AS Employee_1 ON TblQutation.TailorID = Employee_1.EmployeeID
            WHERE TblQutation.CMDate BETWEEN ? AND ?
                ".$this->searchClause('TblQutation.CMNo', 'Accounts.AccName', $search)."
            ORDER BY TblQutation.CMDate DESC
        ";

        return [$sql, $this->bindings($from, $to, $search, 2)];
    }

    private function listBookReceipt($connection, string $from, string $to, string $search): array
    {
        // Key is CaMoID (book receipt's detail query takes @CMID = CaMoID,
        // not a document number). No customer name available in the
        // original query — showing the salesman name instead.
        $sql = "
            SELECT
                BookReceipt.CaMoID AS DocKey,
                BookReceipt.CMDate AS DocDate,
                bp.EmpName AS PartyName,
                BookReceipt.NetAmt AS Amount,
                BookReceipt.GrossAmt,
                BookReceipt.AddDiscAmt,
                BookReceipt.AddDiscPct,
                BookReceipt.CartAmt,
                BookReceipt.RoundDisc,
                BookReceipt.USERNAME,
                BookReceipt.UseDate
            FROM BookReceipt
            LEFT JOIN (
                SELECT bd.CaMoID, MIN(e.EmpName) AS EmpName
                FROM BookReceiptDet bd
                LEFT JOIN Employee e ON bd.SalManID = e.EmployeeID
                GROUP BY bd.CaMoID
            ) bp ON bp.CaMoID = BookReceipt.CaMoID
            WHERE BookReceipt.CMDate BETWEEN ? AND ?
                ".$this->searchClause('CAST(BookReceipt.CaMoID AS VARCHAR(50))', null, $search)."
            ORDER BY BookReceipt.CMDate DESC
        ";

        return [$sql, $this->bindings($from, $to, $search, 1)];
    }

    private function listReceipt($connection, string $from, string $to, string $search): array
    {
        // ⚠️ Same assumption as detail(): Receipt.RecNo used as the key.
        $sql = "
            SELECT
                Receipt.RecNo AS DocKey,
                Receipt.RecDate AS DocDate,
                Accounts.AccName AS PartyName,
                Receipt.Amount AS Amount,
                Receipt.PayMode,
                Receipt.CheqNo,
                Receipt.CheqDate,
                Receipt.Advance,
                Receipt.MadeBy,
                Receipt.Remarks,
                Receipt.RecDisc,
                City.Name AS City,
                Address.State
            FROM Receipt
            LEFT OUTER JOIN Accounts ON Receipt.AccountID = Accounts.AccountID
            LEFT OUTER JOIN Address ON Accounts.AccountID = Address.AccountID
            LEFT OUTER JOIN City ON Address.CityID = City.CityID
            WHERE Receipt.RecDate BETWEEN ? AND ?
                ".$this->searchClause('Receipt.RecNo', 'Accounts.AccName', $search)."
            ORDER BY Receipt.RecDate DESC
        ";

        return [$sql, $this->bindings($from, $to, $search, 2)];
    }

    private function listDebitNote($connection, string $from, string $to, string $search): array
    {
        $sql = "
            SELECT
                Cheque.ChequeID AS DocKey,
                Cheque.ChequeDate AS DocDate,
                Accounts.AccName AS PartyName,
                Cheque.CAmount AS Amount,
                Cheque.ChequeNo,
                Cheque.Remark,
                Cheque.Cartage,
                Cheque.EWAYBILL,
                Cheque.IRN,
                City.Name AS City
            FROM Cheque
            LEFT JOIN Accounts ON Cheque.AccountID = Accounts.AccountID
            LEFT JOIN Address ON Accounts.AccountID = Address.AccountID
            LEFT JOIN City ON Address.CityID = City.CityID
            WHERE Cheque.ChequeDate BETWEEN ? AND ?
                ".$this->searchClause('CAST(Cheque.ChequeID AS VARCHAR(50))', 'Accounts.AccName', $search)."
            ORDER BY Cheque.ChequeDate DESC
        ";

        return [$sql, $this->bindings($from, $to, $search, 2)];
    }

    private function listCreditNote($connection, string $from, string $to, string $search): array
    {
        $sql = "
            SELECT
                CreditNote.CrId AS DocKey,
                CreditNote.CrDate AS DocDate,
                Accounts.AccName AS PartyName,
                CreditNote.CrNetAmt AS Amount,
                CreditNote.CrNo,
                CreditNote.CrRemarks AS Remarks,
                CreditNote.GrossAmt,
                CreditNote.RoundOff,
                CreditNote.SplDiscamt,
                CreditNote.DocNo AS RefDocNo,
                City.Name AS City
            FROM CreditNote
            LEFT JOIN Accounts ON CreditNote.CrAccID = Accounts.AccountID
            LEFT JOIN Address ON Accounts.AccountID = Address.AccountID
            LEFT JOIN City ON Address.CityID = City.CityID
            WHERE CreditNote.CrDate BETWEEN ? AND ?
                ".$this->searchClause('CreditNote.CrNo', 'Accounts.AccName', $search)."
            ORDER BY CreditNote.CrDate DESC
        ";

        return [$sql, $this->bindings($from, $to, $search, 2)];
    }

    private function listPerforma($connection, string $from, string $to, string $search): array
    {
        // Note: deliberately NOT joining TempPer here (see class docblock) —
        // a date-range listing of Performa doesn't need it. detail() still
        // keeps it since that's your original query.
        $sql = "
            SELECT
                Performa.CMNo AS DocKey,
                Performa.CMDate AS DocDate,
                Accounts_1.AccName AS PartyName,
                (SELECT SUM(d.Amount) FROM PerformaDet d WHERE d.CaMoID = Performa.CaMoID) AS Amount,
                Employee.EmpName AS SalesMan,
                Employee_1.EmpName AS Tailor,
                Performa.AddDiscAmt,
                Performa.RoundDisc,
                Performa.CartAmt,
                Performa.DelDate,
                City.Name AS City
            FROM Performa
            LEFT OUTER JOIN Accounts AS Accounts_1 ON Performa.AccountID = Accounts_1.AccountID
            LEFT OUTER JOIN Address ON Accounts_1.AccountID = Address.AccountID
            LEFT OUTER JOIN City ON Address.CityID = City.CityID
            LEFT OUTER JOIN Employee ON Performa.SiteManID = Employee.EmployeeID
            LEFT OUTER JOIN Employee AS Employee_1 ON Performa.TailorID = Employee_1.EmployeeID
            WHERE Performa.CMDate BETWEEN ? AND ?
                ".$this->searchClause('Performa.CMNo', 'Accounts_1.AccName', $search)."
            ORDER BY Performa.CMDate DESC
        ";

        return [$sql, $this->bindings($from, $to, $search, 2)];
    }

    /* ==================================================================
     | DETAIL — header + line items for ONE document (your original SQL)
     * ================================================================== */

    public function detail(string $docType, int $locationId, string $key): array
    {
        $connection = self::connectionForLocation($locationId);

        $method = 'detail'.ucfirst($docType);
        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException("Unknown document type \"{$docType}\".");
        }

        return $this->{$method}($connection, $key);
    }

    private function detailInvoice($connection, string $cmNo): array
    {
        $unitCol = $this->optionalColumn($connection, 'ItemUnit', 'ItemUnIt', 'Name', 'UnitName');
        $unitJoin = $this->optionalJoin($connection, 'ItemUnit', 'ItemUnIt', 'ItemDet.UnitID = ItemUnIt.IUID');
        $catCol = $this->optionalColumn($connection, 'ItemCat', 'ItemCat', 'Name', 'ItemCatName');
        $catJoin = $this->optionalJoin($connection, 'ItemCat', 'ItemCat', 'ItemDet.AliasID = ItemCat.ItemCatID');
        $countryCol = $this->optionalColumn($connection, 'Country', 'Country', 'CountryName', 'CountryName');
        $countryJoin = $this->optionalJoin($connection, 'Country', 'Country', 'Address.CountryID = Country.CountryID');
        $taxCol = $this->optionalColumn($connection, 'Tax', 'Tax', 'Name', 'TaxName');
        $taxJoin = $this->optionalJoin($connection, 'Tax', 'Tax', 'CashMemoDet.TaxID = Tax.TaxID');

        $sql = "
            SELECT
                CashMemo.CMNo, CashMemo.CMDate, CashMemo.SalManID, CashMemo.AuthBY, CashMemo.AddDiscAmt, CashMemo.RoundDisc, CashMemo.SalesSchemeName, CashMemo.CartAmt, CashMemo.SiteManID, CashMemo.UserName, CashMemo.RemarksP, CashMemo.EWAYBILL, CashMemo.IRN, CashMemo.QRImage, CashMemo.CCTOGSTID,
                CashMemoDet.CaMoDetID, CashMemoDet.Quantity, CashMemoDet.Rate, CashMemoDet.ORate, CashMemoDet.TaxID, CashMemoDet.TaxPrate, CashMemoDet.TaxAmt, CashMemoDet.DiscPct, CashMemoDet.DiscAmt, CashMemoDet.Amount, CashMemoDet.NetTax, CashMemoDet.IncTax, CashMemoDet.ExTax, CashMemoDet.ItemCode, CashMemoDet.DiscountAmount, CashMemoDet.Actualdisc, CashMemoDet.IUnit, CashMemoDet.TQty, CashMemoDet.Mat, CashMemoDet.DiscNotAllow,
                Employee.EmpName AS SiteManName,
                Employee_1.EmpName AS TailorName,
                Accounts_1.AccName,
                Accounts_gst.AccName AS GstAccName,
                {$taxCol},
                Title.TitleName,
                Address.Address, Address.State, Address.PIN, Address.Cell, Address.CSTNO,
                Address_gst.Address AS GstAddress, Address_gst.State AS GstState, Address_gst.Cell AS GstCell, Address_gst.CSTNO AS GstCSTNO,
                Title_gst.TitleName AS GstTitleName,
                ItemDet.HSNCODE,
                {$unitCol},
                City.Name AS CityName,
                {$countryCol},
                City_gst.Name AS GstCityName,
                AliasItem.ItemName,
                {$catCol}
            FROM
                CashMemo
                LEFT OUTER JOIN CashMemoDet ON CashMemo.CaMoID = CashMemoDet.CaMoID
                LEFT OUTER JOIN Accounts ON CashMemo.BankID = Accounts.AccountID
                LEFT OUTER JOIN AliasItem ON CashMemoDet.AliasID = AliasItem.AliasID
                LEFT OUTER JOIN ItemDet ON CashMemoDet.ItemDetID = ItemDet.ItemDetID
                LEFT OUTER JOIN AliasItem AS AliasItem_1 ON ItemDet.ItemID = AliasItem_1.AliasID
                LEFT OUTER JOIN Employee ON CashMemo.SiteManID = Employee.EmployeeID
                LEFT OUTER JOIN Employee AS Employee_1 ON CashMemo.TailorID = Employee_1.EmployeeID
                LEFT OUTER JOIN Accounts AS Accounts_1 ON CashMemo.AccountID = Accounts_1.AccountID
                LEFT OUTER JOIN Address ON Accounts_1.AccountID = Address.AccountID
                LEFT OUTER JOIN City ON Address.CityID = City.CityID
                {$taxJoin}
                LEFT OUTER JOIN Title ON Accounts_1.TitleID = Title.TitleID
                {$catJoin}
                {$unitJoin}
                {$countryJoin}
                LEFT OUTER JOIN Accounts AS Accounts_GST ON CashMemo.CCTOGSTID = Accounts_GST.AccountID
                LEFT OUTER JOIN Address AS Address_GST ON Accounts_GST.AccountID = Address_GST.AccountID
                LEFT OUTER JOIN City AS City_GST ON Address_GST.CityID = City_GST.CityID
                LEFT OUTER JOIN Title AS Title_gst ON Accounts_GST.TitleID = Title_gst.TitleId
            WHERE
                CashMemo.FInvoice = 1 AND CashMemo.CC = 0 AND CashMemo.CMNo = ?
            ORDER BY CashMemoDet.CaMoDetID ASC
        ";

        return $this->select($connection, $sql, [$cmNo]);
    }

    private function detailCreditCardInvoice($connection, string $cmNo): array
    {
        $unitCol = $this->optionalColumn($connection, 'ItemUnit', 'ItemUnIt', 'Name', 'UnitName');
        $unitJoin = $this->optionalJoin($connection, 'ItemUnit', 'ItemUnIt', 'ItemDet.UnitID = ItemUnIt.IUID');
        $catCol = $this->optionalColumn($connection, 'ItemCat', 'ItemCat', 'Name', 'ItemCatName');
        $catJoin = $this->optionalJoin($connection, 'ItemCat', 'ItemCat', 'ItemDet.AliasID = ItemCat.ItemCatID');
        $countryCol = $this->optionalColumn($connection, 'Country', 'Country', 'CountryName', 'CountryName');
        $countryJoin = $this->optionalJoin($connection, 'Country', 'Country', 'Address.CountryID = Country.CountryID');
        $taxCol = $this->optionalColumn($connection, 'Tax', 'Tax', 'Name', 'TaxName');
        $taxJoin = $this->optionalJoin($connection, 'Tax', 'Tax', 'CashMemoDet.TaxID = Tax.TaxID');

        $sql = "
            SELECT
                CashMemo.CMNo, CashMemo.CMDate, CashMemo.SalManID, CashMemo.AuthBY, CashMemo.AddDiscAmt, CashMemo.RoundDisc, CashMemo.SalesSchemeName, CashMemo.CartAmt, CashMemo.SiteManID, CashMemo.UserName, CashMemo.RemarksP, CashMemo.EWAYBILL, CashMemo.IRN, CashMemo.QRImage, CashMemo.CCTOGSTID,
                CashMemoDet.CaMoDetID, CashMemoDet.Quantity, CashMemoDet.Rate, CashMemoDet.ORate, CashMemoDet.TaxID, CashMemoDet.TaxPrate, CashMemoDet.TaxAmt, CashMemoDet.DiscPct, CashMemoDet.DiscAmt, CashMemoDet.Amount, CashMemoDet.NetTax, CashMemoDet.IncTax, CashMemoDet.ExTax, CashMemoDet.ItemCode, CashMemoDet.DiscountAmount, CashMemoDet.Actualdisc, CashMemoDet.IUnit, CashMemoDet.TQty, CashMemoDet.Mat, CashMemoDet.DiscNotAllow,
                Employee.EmpName AS SiteManName,
                Employee_1.EmpName AS TailorName,
                Accounts_1.AccName,
                Accounts_gst.AccName AS GstAccName,
                {$taxCol},
                Title.TitleName,
                Address.Address, Address.State, Address.PIN, Address.Cell, Address.CSTNO,
                Address_gst.Address AS GstAddress, Address_gst.State AS GstState, Address_gst.Cell AS GstCell, Address_gst.CSTNO AS GstCSTNO,
                Title_gst.TitleName AS GstTitleName,
                ItemDet.HSNCODE,
                {$unitCol},
                City.Name AS CityName,
                {$countryCol},
                City_gst.Name AS GstCityName,
                AliasItem.ItemName,
                {$catCol}
            FROM
                CashMemo
                LEFT OUTER JOIN CashMemoDet ON CashMemo.CaMoID = CashMemoDet.CaMoID
                LEFT OUTER JOIN Accounts ON CashMemo.BankID = Accounts.AccountID
                LEFT OUTER JOIN AliasItem ON CashMemoDet.AliasID = AliasItem.AliasID
                LEFT OUTER JOIN ItemDet ON CashMemoDet.ItemDetID = ItemDet.ItemDetID
                LEFT OUTER JOIN AliasItem AS AliasItem_1 ON ItemDet.ItemID = AliasItem_1.AliasID
                LEFT OUTER JOIN Employee ON CashMemo.SiteManID = Employee.EmployeeID
                LEFT OUTER JOIN Employee AS Employee_1 ON CashMemo.TailorID = Employee_1.EmployeeID
                LEFT OUTER JOIN Accounts AS Accounts_1 ON CashMemo.AccountID = Accounts_1.AccountID
                LEFT OUTER JOIN Address ON Accounts_1.AccountID = Address.AccountID
                LEFT OUTER JOIN City ON Address.CityID = City.CityID
                {$taxJoin}
                LEFT OUTER JOIN Title ON Accounts_1.TitleID = Title.TitleID
                {$catJoin}
                {$unitJoin}
                {$countryJoin}
                LEFT OUTER JOIN Accounts AS Accounts_GST ON CashMemo.CCTOGSTID = Accounts_GST.AccountID
                LEFT OUTER JOIN Address AS Address_GST ON Accounts_GST.AccountID = Address_GST.AccountID
                LEFT OUTER JOIN City AS City_GST ON Address_GST.CityID = City_GST.CityID
                LEFT OUTER JOIN Title AS Title_gst ON Accounts_GST.TitleID = Title_gst.TitleId
            WHERE
                CashMemo.FInvoice = 1 AND CashMemo.CC = 1 AND CashMemo.CMNo = ?
            ORDER BY CashMemoDet.CaMoDetID ASC
        ";

        return $this->select($connection, $sql, [$cmNo]);
    }

    private function detailCashMemo($connection, string $cmNo): array
    {
        $ccardCol = $this->optionalColumn($connection, 'CCard', 'CCard', 'Name', 'CCardName');
        $ccardJoin = $this->optionalJoin($connection, 'CCard', 'CCard', 'CashMemo.CCardID = CCard.CCardID');
        $catCol = $this->optionalColumn($connection, 'ItemCat', 'ItemCat', 'Name', 'ItemCatName');
        $catJoin = $this->optionalJoin($connection, 'ItemCat', 'ItemCat', 'ItemDet.AliasID = ItemCat.ItemCatID');
        // Note: the original query joins a table named "ItemUnIt" (no AS —
        // that's its real name here) AND separately "ItemUnit AS ItemUnIt_1".
        // SQL Server's default collation is case-insensitive, so these are
        // almost certainly the SAME table referenced twice with different
        // ON conditions — both covered by the one ItemUnit existence check.
        $unitAliasCol = $this->optionalColumn($connection, 'ItemUnit', 'ItemUnIt', 'Name', 'UnitName2');
        $unitAlias1Col = $this->optionalColumn($connection, 'ItemUnit', 'ItemUnIt_1', 'Name', 'UnitName1');
        $unitAliasJoin = $this->optionalJoin($connection, 'ItemUnit', 'ItemUnIt', 'AliasItem_1.UnitID = ItemUnIt.IUID');
        $unitAlias1Join = $this->optionalJoin($connection, 'ItemUnit', 'ItemUnIt_1', 'ItemDet.UnitID = ItemUnIt_1.IUID');

        $sql = "
            SELECT
                CashMemo.CMNo, CashMemo.CMDate, CashMemo.SalManID, CashMemo.GrossAmt, CashMemo.DiscAmt, CashMemo.TaxAmt, CashMemo.NetAmt, CashMemo.CCardID, CashMemo.BankID, CashMemo.BillAmt, CashMemo.SlipNo, CashMemo.ApprovalNo, CashMemo.CMCancelled, CashMemo.Remarks, CashMemo.AddDiscAmt, CashMemo.RoundDisc, CashMemo.SalesSchemeName, CashMemo.CartAmt, CashMemo.FInvoice, CashMemo.SiteManID, CashMemo.AdvancePayment, CashMemo.TCustName, CashMemo.TCustAdd, CashMemo.CCRefno,
                CashMemoDet.CaMoDetID, CashMemoDet.ItemCode, CashMemoDet.Quantity, CashMemoDet.ORate, CashMemoDet.TaxPrate, CashMemoDet.TaxAmt, CashMemoDet.DiscPct, CashMemoDet.DiscAmt, CashMemoDet.Amount, CashMemoDet.NetTax, CashMemoDet.IncTax, CashMemoDet.ExTax, CashMemoDet.BookSlipNum, CashMemoDet.DiscountAmount, CashMemoDet.Actualdisc, CashMemoDet.Mat, CashMemoDet.DiscNotAllow,
                {$ccardCol},
                Accounts_1.AccName,
                Address.Address AS Address, City.Name AS CityName, Address.CSTNO, Address.PIN,
                Employee.EmpName,
                ItemDet.HSNCODE,
                AliasItem.ItemName,
                BookSlip.FlagB,
                Floor.FloorName,
                {$catCol},
                {$unitAlias1Col},
                {$unitAliasCol}
            FROM
                CashMemo
                LEFT OUTER JOIN CashMemoDet ON CashMemo.CaMoID = CashMemoDet.CaMoID
                {$ccardJoin}
                LEFT OUTER JOIN Accounts AS Accounts_1 ON CashMemo.AccountID = Accounts_1.AccountID
                LEFT OUTER JOIN Address ON Accounts_1.AccountID = Address.AccountID
                LEFT OUTER JOIN City ON Address.CityID = City.CityID
                LEFT OUTER JOIN Employee ON CashMemo.SalManID = Employee.EmployeeID
                LEFT OUTER JOIN ItemDet ON CashMemoDet.ItemDetID = ItemDet.ItemDetID
                LEFT OUTER JOIN BookSlip ON CashMemoDet.BookSlipID = BookSlip.BookSlipID
                LEFT OUTER JOIN AliasItem ON CashMemoDet.AliasID = AliasItem.AliasID
                LEFT OUTER JOIN AliasItem AS AliasItem_1 ON ItemDet.ItemID = AliasItem_1.AliasID
                {$unitAliasJoin}
                LEFT OUTER JOIN Floor ON BookSlip.FloorID = Floor.FloorID
                {$unitAlias1Join}
                {$catJoin}
            WHERE
                CashMemo.FCashMemo = 1 AND CashMemo.CMNo = ?
            ORDER BY CashMemoDet.CaMoDetID ASC
        ";

        return $this->select($connection, $sql, [$cmNo]);
    }

    /** ⚠️ assumed `TblQutation.CMNo = ?` — confirm (see class docblock) */
    private function detailQuotation($connection, string $cmNo): array
    {
        $unitCol = $this->optionalColumn($connection, 'ItemUnit', 'ItemUnIt', 'Name', 'UnitName');
        $unitJoin = $this->optionalJoin($connection, 'ItemUnit', 'ItemUnIt', 'ItemDet.UnitID = ItemUnIt.IUID');
        $catCol = $this->optionalColumn($connection, 'ItemCat', 'ItemCat', 'Name', 'ItemCatName');
        $catJoin = $this->optionalJoin($connection, 'ItemCat', 'ItemCat', 'ItemDet.AliasID = ItemCat.ItemCatID');
        $taxCol = $this->optionalColumn($connection, 'Tax', 'Tax', 'Name', 'TaxName');
        $taxJoin = $this->optionalJoin($connection, 'Tax', 'Tax', 'TblQutationDet.TaxID = Tax.TaxID');

        $sql = "
            SELECT
                TblQutation.CMNo, TblQutation.CMDate, TblQutation.AddDiscAmt, TblQutation.CartAmt, TblQutation.RemarksP, TblQutation.Attn, TblQutation.Enquiry, TblQutation.Delivery, TblQutation.Payment, TblQutation.Created, TblQutation.MobileNo,
                Accounts.AccName,
                Employee.EmpName AS SiteManName,
                Employee_1.EmpName AS TailorName,
                TblQutationDet.CaMoDetID, TblQutationDet.ItemDetID, TblQutationDet.Quantity, TblQutationDet.Rate, TblQutationDet.ORate, TblQutationDet.TaxPrate, TblQutationDet.TaxAmt, TblQutationDet.DiscPct, TblQutationDet.Amount, TblQutationDet.NetTax, TblQutationDet.IncTax, TblQutationDet.ExTax, TblQutationDet.ItemCode, TblQutationDet.DiscountAmount, TblQutationDet.Actualdisc, TblQutationDet.IUnit, TblQutationDet.TQty, TblQutationDet.Mat, TblQutationDet.CompName,
                ItemDet.HSNCODE,
                {$taxCol},
                AliasItem.ItemName,
                Address.Address, Address.Cell, Address.CSTNO,
                Title.TitleName,
                {$unitCol},
                AliasItem_1.ItemName AS ItemName1,
                City.Name AS CityName,
                {$catCol}
            FROM
                TblQutation
                LEFT OUTER JOIN TblQutationDet ON TblQutation.CaMoID = TblQutationDet.CaMoID
                LEFT OUTER JOIN Accounts AS Accounts_1 ON TblQutation.BankID = Accounts_1.AccountID
                LEFT OUTER JOIN AliasItem ON TblQutationDet.AliasID = AliasItem.AliasID
                LEFT OUTER JOIN ItemDet ON TblQutationDet.ItemDetID = ItemDet.ItemDetID
                LEFT OUTER JOIN AliasItem AS AliasItem_1 ON ItemDet.ItemID = AliasItem_1.AliasID
                LEFT OUTER JOIN Employee ON TblQutation.SiteManID = Employee.EmployeeID
                LEFT OUTER JOIN Employee AS Employee_1 ON TblQutation.TailorID = Employee_1.EmployeeID
                LEFT OUTER JOIN Accounts ON TblQutation.AccountID = Accounts.AccountID
                LEFT OUTER JOIN Address ON Accounts.AccountID = Address.AccountID
                LEFT OUTER JOIN City ON Address.CityID = City.CityID
                {$taxJoin}
                LEFT OUTER JOIN Title ON Accounts.TitleID = Title.TitleID
                {$catJoin}
                {$unitJoin}
            WHERE
                TblQutation.CMNo = ?
            ORDER BY TblQutationDet.CaMoDetID ASC
        ";

        return $this->select($connection, $sql, [$cmNo]);
    }

    private function detailBookReceipt($connection, string $caMoId): array
    {
        // Falls back to BookReceiptDet.IUnit (already the original query's
        // own fallback) when the ItemUnit table doesn't exist here at all.
        $unitCol = $this->tableExists($connection, 'ItemUnit')
            ? 'ISNULL(ItemUnit.Name, BookReceiptDet.IUnit) AS IUnit'
            : 'BookReceiptDet.IUnit AS IUnit';
        $unitJoin = $this->optionalJoin($connection, 'ItemUnit', 'ItemUnit', 'ItemDet.UnitID = ItemUnit.IUID');
        // Same fallback pattern: AliasItem.ItemName is the original query's
        // own fallback when ItemCat has nothing / doesn't exist here.
        $catCol = $this->tableExists($connection, 'ItemCat')
            ? 'ISNULL(ItemCat.Name, AliasItem.ItemName) AS ItemName'
            : 'AliasItem.ItemName AS ItemName';
        $catJoin = $this->optionalJoin($connection, 'ItemCat', 'ItemCat', 'ItemDet.AliasID = ItemCat.ItemCatID');

        $sql = "
            SELECT
                BookReceiptDet.BookSlipNum, BookReceiptDet.AliasName,
                {$unitCol}, BookReceiptDet.Quantity, BookReceiptDet.ORate,
                BookReceiptDet.TaxAmt, BookReceiptDet.Amount,
                BookReceiptDet.DiscountAmount, BookReceiptDet.IncTax,
                BookReceiptDet.DiscPct, BookReceiptDet.TaxPrate,
                BookReceiptDet.CaMoDetID, BookReceiptDet.ItemCode, BookReceiptDet.AliasID,
                BookReceipt.CMDate, BookReceipt.GrossAmt, BookReceipt.AddDiscAmt,
                BookReceipt.AddDiscPct, BookReceipt.CartAmt, BookReceipt.RoundDisc,
                BookReceipt.NetAmt, Floor.FloorName, Employee.EmpName AS name, BookSlip.FlagB,
                {$catCol},
                BookReceipt.USERNAME, BookReceipt.UseDate,
                ((BookReceiptDet.Quantity * BookReceiptDet.ORate) * BookReceiptDet.TaxPrate / 100) AS ActualTax
            FROM
                BookReceipt
                LEFT OUTER JOIN BookReceiptDet ON BookReceipt.CAMoID = BookReceiptDet.CaMoID
                LEFT OUTER JOIN Employee ON BookReceiptDet.SalManID = Employee.EmployeeID
                LEFT OUTER JOIN BookSlip ON BookReceiptDet.BookSlipID = BookSlip.BookSlipID
                LEFT OUTER JOIN Floor ON BookSlip.FloorID = Floor.FloorID
                LEFT OUTER JOIN ItemDet ON BookReceiptDet.ItemDetID = ItemDet.ItemDetID
                LEFT OUTER JOIN AliasItem ON ItemDet.ItemID = AliasItem.AliasID
                {$catJoin}
                {$unitJoin}
            WHERE
                BookReceipt.CaMoID = ?
        ";

        return $this->select($connection, $sql, [$caMoId]);
    }

    /** ⚠️ assumed `Receipt.RecNo = ?` — confirm (see class docblock) */
    private function detailReceipt($connection, string $recNo): array
    {
        $sql = "
            SELECT
                Receipt.RecID, Receipt.RecNo, Receipt.RecDate, Receipt.PayMode, Receipt.Amount, Receipt.CheqNo, Receipt.CheqDate, Receipt.Advance, Receipt.MadeBy, Receipt.Remarks, Receipt.SalesSchemeName, Receipt.Visiting, Receipt.RecDisc, Receipt.RecAddDisc, Receipt.MO, Receipt.YR, Receipt.TempR,
                Accounts.AccName,
                Address.Address, Address.State, Address.PIN, Address.Phone, Address.Cell, Address.CSTNO,
                Title.TitleName,
                City.Name AS CityName
            FROM
                Receipt
                LEFT OUTER JOIN Accounts ON Receipt.AccountID = Accounts.AccountID
                LEFT OUTER JOIN Address ON Accounts.AccountID = Address.AccountID
                LEFT OUTER JOIN City ON Address.CityID = City.CityID
                LEFT OUTER JOIN Title ON Accounts.TitleID = Title.TitleID
            WHERE
                Receipt.RecNo = ?
        ";

        return $this->select($connection, $sql, [$recNo]);
    }

    private function detailDebitNote($connection, string $chequeId): array
    {
        $unitCol = $this->optionalColumn($connection, 'ItemUnit', 'ItemUnIt', 'Name', 'Unit');
        $unitJoin = $this->optionalJoin($connection, 'ItemUnit', 'ItemUnIt', 'ItemDet.UnitID = ItemUnIt.IUID');
        $catCol = $this->optionalColumn($connection, 'ItemCat', 'ItemCat', 'Name', 'Item');
        $catJoin = $this->optionalJoin($connection, 'ItemCat', 'ItemCat', 'ItemDet.AliasID = ItemCat.ItemCatID');

        $sql = "
            SELECT
                Cheque.ChequeID, Cheque.ChequeNo, Cheque.ChequeDate, Cheque.Remark, Cheque.Cartage,
                DebitNoteItem.ItemName, DebitNoteItem.Qty, DebitNoteItem.Rate, DebitNoteItem.Amount, DebitNoteItem.Discount, DebitNoteItem.TaxAmt, DebitNoteItem.TaxRate,
                Accounts.AccName,
                Address.Address + '  ' + City.Name + '  ' + Address.State AS Address,
                {$unitCol},
                {$catCol},
                Design.Name AS DesignName, Cheque.CAmount, Address.CSTNO AS CstNo, ItemDet.HSNCODE,
                Brand.Name AS BrandName, Address.CSTNO AS GstNo, Cheque.QRImage, Cheque.EWAYBILL, Cheque.IRN
            FROM
                Cheque
                LEFT JOIN DebitNoteItem ON Cheque.ChequeID = DebitNoteItem.ChequeID
                LEFT JOIN Accounts ON Cheque.AccountID = Accounts.AccountID
                LEFT JOIN Address ON Accounts.AccountID = Address.AccountID
                LEFT JOIN City ON Address.CityID = City.CityID
                LEFT JOIN ItemDet ON DebitNoteItem.ItemDetID = ItemDet.ItemDetID
                {$unitJoin}
                LEFT JOIN Design ON ItemDet.DesignID = Design.DesignId
                LEFT JOIN Brand ON ItemDet.BrandID = Brand.BrandID
                {$catJoin}
            WHERE
                Cheque.ChequeID = ?
            ORDER BY Cheque.ChequeID, DebitNoteItem.DebitNoteItemID ASC
        ";

        return $this->select($connection, $sql, [$chequeId]);
    }

    private function detailCreditNote($connection, string $crId): array
    {
        $unitCol = $this->optionalColumn($connection, 'ItemUnit', 'ItemUnIt', 'Name', 'ItemUnit');
        $unitJoin = $this->optionalJoin($connection, 'ItemUnit', 'ItemUnIt', 'ItemDet.UnitID = ItemUnIt.IUID');
        $catCol = $this->optionalColumn($connection, 'ItemCat', 'ItemCat', 'Name', 'ItemName');
        $catJoin = $this->optionalJoin($connection, 'ItemCat', 'ItemCat', 'ItemDet.AliasID = ItemCat.ItemCatID');
        $taxCol = $this->optionalColumn($connection, 'Tax', 'Tax', 'Name', 'TaxName');
        $taxJoin = $this->optionalJoin($connection, 'Tax', 'Tax', 'CreditNoteDet.TaxId = Tax.TaxId');

        $sql = "
            SELECT
                ISNULL(Title.TitleName, '') + ' ' + Accounts.AccName AS CustName,
                Address.Address AS Address, City.Name AS CityName, CreditNote.CrNo, CreditNote.CrDate,
                CreditNote.CrNetAmt, CreditNote.DocNo, CreditNote.CrRemarks,
                ItemDet.ItemCode, {$catCol}, Design.Name AS DesignName, {$unitCol},
                CreditNoteDet.Qty, CreditNoteDet.Rate, CreditNoteDet.TaxAmt,
                CreditNoteDet.TaxPRate, CreditNoteDet.Amt,
                CreditNote.RoundOff, CreditNote.SplDiscamt, Address.CSTNO, ItemDet.Rate AS Irate,
                ((ISNULL(CreditNoteDet.Disc, 0) / CreditNoteDet.Qty) / CreditNoteDet.ORate) * 100 AS DiscPer,
                CreditNoteDet.ORate,
                (CASE WHEN (CreditNoteDet.ORate - (CreditNoteDet.ORate * ((ISNULL(CreditNoteDet.Disc, 0) / CreditNoteDet.Qty) / CreditNoteDet.ORate))) = CreditNoteDet.Rate THEN 'EX' ELSE 'INC' END) AS TaxType,
                AliasItem.ItemName AS HsnCode, ISNULL(CreditNoteDet.Disc, 0) AS DiscAmt, CreditNoteDet.CrDetID, {$taxCol},
                CreditNote.GrossAmt, Address.PIN
            FROM
                CreditNote
                LEFT OUTER JOIN CreditNoteDet ON CreditNote.CrId = CreditNoteDet.CrId
                LEFT JOIN ItemDet ON CreditNoteDet.ItemDetid = ItemDet.ItemDetID
                LEFT OUTER JOIN AliasItem ON ItemDet.ItemID = AliasItem.AliasID
                LEFT JOIN Design ON ItemDet.DesignID = Design.DesignId
                {$catJoin}
                {$unitJoin}
                LEFT JOIN Accounts ON CreditNote.CrAccID = Accounts.AccountID
                LEFT JOIN Address ON Accounts.AccountID = Address.AccountID
                LEFT JOIN City ON Address.CityID = City.CityID
                LEFT JOIN Title ON Accounts.TitleID = Title.TitleId
                {$taxJoin}
            WHERE
                CreditNote.CrId = ?
            ORDER BY CreditNoteDet.CaMoDetID
        ";

        return $this->select($connection, $sql, [$crId]);
    }

    /** ⚠️ assumed `Performa.CMNo = ?` — confirm (see class docblock). TempPer join removed (see class docblock). */
    private function detailPerforma($connection, string $cmNo): array
    {
        $sql = "
            SELECT
                Performa.CMNo, Performa.CMDate, Performa.AddDiscAmt, Performa.RoundDisc, Performa.CartAmt, Performa.DelDate,
                Accounts_1.AccName,
                Employee.EmpName AS SiteManName,
                Employee_1.EmpName AS TailorName,
                PerformaDet.CaMoDetID, PerformaDet.Quantity, PerformaDet.Rate, PerformaDet.ORate, PerformaDet.TaxID, PerformaDet.TaxAmt, PerformaDet.DiscPct, PerformaDet.DiscAmt, PerformaDet.Amount, PerformaDet.NetTax, PerformaDet.IncTax, PerformaDet.lstChar,
                Address.Address,
                AliasItem.ItemName,
                AliasItem_1.ItemName AS ItemName1,
                City.Name AS CityName
            FROM
                Performa
                LEFT OUTER JOIN PerformaDet ON Performa.CaMoID = PerformaDet.CaMoID
                LEFT OUTER JOIN AliasItem ON PerformaDet.AliasID = AliasItem.AliasID
                LEFT OUTER JOIN ItemDet ON PerformaDet.ItemDetID = ItemDet.ItemDetID
                LEFT OUTER JOIN AliasItem AS AliasItem_1 ON ItemDet.ItemID = AliasItem_1.AliasID
                LEFT OUTER JOIN Employee ON Performa.SiteManID = Employee.EmployeeID
                LEFT OUTER JOIN Employee AS Employee_1 ON Performa.TailorID = Employee_1.EmployeeID
                LEFT OUTER JOIN Accounts AS Accounts_1 ON Performa.AccountID = Accounts_1.AccountID
                LEFT OUTER JOIN Address ON Accounts_1.AccountID = Address.AccountID
                LEFT OUTER JOIN City ON Address.CityID = City.CityID
            WHERE
                Performa.CMNo = ?
            ORDER BY PerformaDet.CaMoDetID ASC
        ";

        return $this->select($connection, $sql, [$cmNo]);
    }

    /* ------------------------------------------------------------------ */

    /**
     * [$from, $to] plus one '%search%' binding per placeholder that
     * searchClause() put in the SQL (0, 1, or 2 — must match $col2 usage
     * exactly the same way searchClause() decided it).
     */
    private function bindings(string $from, string $to, string $search, int $searchPlaceholders): array
    {
        if ($searchPlaceholders === 0 || trim($search) === '') {
            return [$from, $to];
        }

        $term = '%'.trim($search).'%';

        return array_merge([$from, $to], array_fill(0, $searchPlaceholders, $term));
    }

    /**
     * `AND (col1 LIKE ? [OR col2 LIKE ?])` when $search is non-empty, else ''.
     * Pass the SAME $search to bindings() with placeholders = (col2 ? 2 : 1)
     * (or 0 if $search is empty) so the `?` count always matches.
     */
    private function searchClause(string $col1, ?string $col2, string $search): string
    {
        if (trim($search) === '') {
            return '';
        }

        return $col2
            ? "AND ({$col1} LIKE ? OR {$col2} LIKE ?)"
            : "AND {$col1} LIKE ?";
    }

    private function select($connection, string $sql, array $bindings): array
    {
        return $connection->select($sql, $bindings);
    }
}