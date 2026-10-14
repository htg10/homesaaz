<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Floor Wise Sales: runs the query in query() on the sales
 * database of the selected location (Location.DatabaseName - set on the
 * Locations page, so new locations work without code changes).
 */
class FloorWiseSalesService
{
    /**
     * DYNAMIC locations: every Location row with a DatabaseName, limited to
     * the locations this user may see.
     *
     * @return array<int, array{id:int, name:string, database:string}>
     */
    public static function locations(): array
    {
        // Read live from the Location table (names / colours / database always current)
        $all = (function () {
            if (!Schema::hasColumn('Location', 'DatabaseName')) {
                Log::warning('Floor Wise Sales: Location.DatabaseName column is missing');
                return [];
            }

            return DB::table('Location')
                ->select('LocationID', 'LocationName', 'DatabaseName', 'Color')
                ->whereNotNull('DatabaseName')
                ->where('DatabaseName', '<>', '')
                ->orderBy('LocationName')
                ->get()
                ->map(fn ($l) => [
                    'id' => (int) $l->LocationID,
                    'name' => trim((string) $l->LocationName),
                    'database' => strtoupper(trim((string) $l->DatabaseName)),
                    'color' => trim((string) ($l->Color ?? '')) ?: '#9CA3AF',
                ])
                ->filter(fn ($l) => preg_match('/^[A-Z0-9_]+$/', $l['database']))
                ->values()
                ->all();
        })();

        $allowed = UserAccessService::allowedLocationIds();
        if ($allowed === null) {
            return $all;
        }

        return array_values(array_filter($all, fn ($l) => in_array($l['id'], $allowed, true)));
    }

    public static function databaseFor(int $locationId): ?string
    {
        foreach (self::locations() as $loc) {
            if ($loc['id'] === $locationId) {
                return $loc['database'];
            }
        }
        return null;
    }

    /** Does the database exist on the server? (cached 10 min) */
    private static function databaseExists(string $database): bool
    {
        return Cache::remember('floor_sales.dbexists.' . $database, 600, function () use ($database) {
            $row = DB::selectOne('SELECT DB_ID(?) AS DbId', [$database]);
            return !empty($row->DbId);
        });
    }

    /**
     * Connection to another database on the same server (copy of the default
     * connection with a different database name).
     */
    private static function connectionFor(string $database): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new \InvalidArgumentException('Invalid database name.');
        }

        $name = 'floor_sales_' . strtolower($database);

        if (!config("database.connections.$name")) {
            $base = config('database.connections.' . config('database.default'));
            $base['database'] = $database;
            config(["database.connections.$name" => $base]);
        }

        return $name;
    }

    /**
     * Floor Wise Sales query (runs on the location's own database).
     * {{DATE_FROM}} / {{DATE_TO}} are replaced with YYYYMMDD in report().
     * Keep the column names of the first SELECT (FloorName, GrossAmt, GrossAmt1,
     * DisAmount, AddDiscAmt, TaxAmt, NetAmt) - the code reads them by name.
     */
    private static function query(): string
    {
        return <<<'SQL'
select FloorName, Sum(GrossAmt) AS GrossAmt, Sum(GrossAmt1) AS GrossAmt1 ,  Isnull(Sum(DisAmount),0) AS DisAmount
 , iSnULL(Sum(AddDiscAmt),0) AddDiscAmt ,  Sum(TaxAmt) AS TaxAmt, Sum(NetAmt) as NetAmt
  From  (  Select '' CMDate, '' CMNo, Sum(NetAmt) as NetAmt,  Sum(AddDiscAmt) AddDiscAmt,  null FCashMemo, null FInvoice  , sum(CartAmt) CartAmt, 0 CaMoID, FloorName,  
  Sum(DisAmount) AS DisAmount,  0 FloorID, 0 SalManID, 0 siteManID,  Sum(GrossAmt) AS GrossAmt,  Sum(TaxAmt) AS TaxAmt  ,0 PayModeCCard,Sum(TaxAmt1) AS TaxAmt1 ,sum(NetTaxAmt) as NetTaxAmt 
  ,Sum(GrossAmt1) AS GrossAmt1  from  (SELECT  '' CMDate, '' CMNo,  Sum((CashMemoDet.Amount-((Case When DiscNotAllow=0 then IsNull(Cashmemo.AddDiscPct,0) else 0 end )*(Cashmemodet.Amount)/100)))
   as NetAmt,  Sum((Case When DiscNotAllow=0 then IsNull(Cashmemo.AddDiscPct,0) else 0 end )*(Cashmemodet.Amount)/100) AddDiscAmt,  null FCashMemo, null FInvoice,  (CashMemo.CartAmt) CartAmt
  ,  0 CaMoID, IsNull(Floor_1.FloorName, IsNull(Floor.FloorName,Cashmemo.Floor))  FloorName,  Sum((Case when cashmemodet.DiscPct<40 then CashMemoDet.DiscountAmount else 0 end)) AS DisAmount,  
  0 FloorID, 0 SalManID, 0 siteManID ,Sum( Case when (Cashmemodet.DiscPct<40  AND  Cashmemo.AddDiscPct<40 ) then (Cashmemodet.Amount + CashMemoDet.DiscountAmount) else 0 end) AS GrossAmt,  
  Sum(CashMemoDet.TaxAmt) AS TaxAmt  ,0 PayModeCCard, Sum(Case  when CashMemoDet.ExTax=1 then 0 else CashMemoDet.TaxAmt  end) AS TaxAmt1,Sum(CashMemoDet.NetTax) AS NetTaxAmt  ,
  Sum( Case when (Cashmemodet.DiscPct>=40 Or Cashmemo.AddDiscPct>=40 ) then (Cashmemodet.Amount) else 0 end) AS GrossAmt1  From CashMemo  
  LEFT JOIN CashMemoDet ON CashMemo.CaMoID = CashMemoDet.CaMoID  left join Itemdet on cashmemodet.itemdetid=itemdet.itemdetid  left join floor on Itemdet.floorId=Floor.floorID 
   left join floor  as Floor_1 on Cashmemodet.SfloorId=Floor_1.floorID  left join Accounts on CashMemo.AccountID=Accounts.AccountID  where  Convert(Varchar(8), Cashmemo.CMDate, 112) Between '{{DATE_FROM}}' 
   And '{{DATE_TO}}' And IsNull(RelId,0)=0 and IsNull(Itemdet.Cutpiece,0)=0 And IsNull(DMRP,0)=0 GROUP BY IsNull(Floor_1.FloorName, IsNull(Floor.FloorName,Cashmemo.Floor)),CashMemo.CartAmt  
   Union All  
   SELECT  '' CMDate, '' CMNo,  Sum((CashMemoDet.Amount-((Case When DiscNotAllow=0 then IsNull(Cashmemo.AddDiscPct,0) else 0 end )*(Cashmemodet.Amount)/100))) as NetAmt,  
   Sum((Case When DiscNotAllow=0 then IsNull(Cashmemo.AddDiscPct,0) else 0 end )*(Cashmemodet.Amount)/100) AddDiscAmt,  null FCashMemo, null FInvoice,  (CashMemo.CartAmt) CartAmt, 0 CaMoID,
   ( Case When IsNull(Floor_1.FloorName, IsNull(Floor.FloorName,Cashmemo.Floor))='FURNSHG' then  case when ORate in (200,150) Then 'CUTPIECE' ELSE 'FURNSHG' END ELSE  
   IsNull(Floor_1.FloorName, IsNull(Floor.FloorName,Cashmemo.Floor)) END ) FloorName,  Sum((Case when cashmemodet.DiscPct<40 then CashMemoDet.DiscountAmount else 0 end)) AS DisAmount,  0 FloorID, 
   0 SalManID, 0 siteManID ,Sum( Case when (Cashmemodet.DiscPct<40  AND  Cashmemo.AddDiscPct<40 ) then (Cashmemodet.Amount + CashMemoDet.DiscountAmount) else 0 end)   AS GrossAmt,  
   Sum(CashMemoDet.TaxAmt) AS TaxAmt  ,0 PayModeCCard, Sum(Case  when CashMemoDet.ExTax=1 then 0 else CashMemoDet.TaxAmt  end) AS TaxAmt1,Sum(CashMemoDet.NetTax) AS NetTaxAmt  
   ,Sum( Case when (Cashmemodet.DiscPct>=40 Or Cashmemo.AddDiscPct>=40 ) then (Cashmemodet.Amount) else 0 end) AS GrossAmt1  From CashMemo  
   LEFT JOIN CashMemoDet ON CashMemo.CaMoID = CashMemoDet.CaMoID  left join Itemdet on cashmemodet.itemdetid=itemdet.itemdetid  left join floor on Itemdet.floorId=Floor.floorID  
   left join floor  as Floor_1 on Cashmemodet.SfloorId=Floor_1.floorID  left join Accounts on CashMemo.AccountID=Accounts.AccountID  where  Convert(Varchar(8), Cashmemo.CMDate, 112) 
   Between '{{DATE_FROM}}' And '{{DATE_TO}}'   and convert(varchar(8),cmdate,112)<'20180912' And IsNull(RelId,0)=0 and IsNull(Itemdet.Cutpiece,0)=1 And IsNull(DMRP,0)=0
    GROUP BY ( Case When IsNull(Floor_1.FloorName, IsNull(Floor.FloorName,Cashmemo.Floor))='FURNSHG' then  case when ORate in (200,150) Then 'CUTPIECE' ELSE 'FURNSHG' END ELSE  
    IsNull(Floor_1.FloorName, IsNull(Floor.FloorName,Cashmemo.Floor)) END ) ,CashMemo.CartAmt  
    Union All  SELECT  '' CMDate, '' CMNo,  Sum((CashMemoDet.Amount-((Case When DiscNotAllow=0 then IsNull(Cashmemo.AddDiscPct,0) else 0 end )*(Cashmemodet.Amount)/100))) as NetAmt,  
    Sum((Case When DiscNotAllow=0 then IsNull(Cashmemo.AddDiscPct,0) else 0 end )*(Cashmemodet.Amount)/100) AddDiscAmt,  null FCashMemo, null FInvoice,  (CashMemo.CartAmt) CartAmt, 0 CaMoID
    ,( Case When IsNull(Floor_1.FloorName, IsNull(Floor.FloorName,Cashmemo.Floor))='FURNSHG' then  case when ORate in (200,150) Then 'CUTPIECE' ELSE 'FURNSHG' END ELSE  
    IsNull(Floor_1.FloorName, IsNull(Floor.FloorName,Cashmemo.Floor)) END )  FloorName,  Sum((Case when cashmemodet.DiscPct<40 then CashMemoDet.DiscountAmount else 0 end)) AS DisAmount, 
     0 FloorID, 0 SalManID, 0 siteManID ,Sum( Case when (Cashmemodet.DiscPct<40  AND  Cashmemo.AddDiscPct<40 ) then (Cashmemodet.Amount + CashMemoDet.DiscountAmount) else 0 end)  AS GrossAmt, 
      Sum(CashMemoDet.TaxAmt) AS TaxAmt  ,0 PayModeCCard, Sum(Case  when CashMemoDet.ExTax=1 then 0 else CashMemoDet.TaxAmt  end) AS TaxAmt1,Sum(CashMemoDet.NetTax) AS NetTaxAmt  
      ,Sum( Case when (Cashmemodet.DiscPct>=40 Or Cashmemo.AddDiscPct>=40 ) then (Cashmemodet.Amount) else 0 end) AS GrossAmt1  From CashMemo  
      LEFT JOIN CashMemoDet ON CashMemo.CaMoID = CashMemoDet.CaMoID  left join Itemdet on cashmemodet.itemdetid=itemdet.itemdetid  left join floor on Itemdet.floorId=Floor.floorID  
      left join floor  as Floor_1 on Cashmemodet.SfloorId=Floor_1.floorID  left join Accounts on CashMemo.AccountID=Accounts.AccountID  where  Convert(Varchar(8), Cashmemo.CMDate, 112) Between '{{DATE_FROM}}'
       And '{{DATE_TO}}'   and convert(varchar(8),cmdate,112)>='20180912' And IsNull(RelId,0)=0 and IsNull(Itemdet.Cutpiece,0)=1 GROUP BY ( Case When IsNull(Floor_1.FloorName,
        IsNull(Floor.FloorName,Cashmemo.Floor))='FURNSHG' then  case when ORate in (200,150) Then 'CUTPIECE' ELSE 'FURNSHG' END ELSE  IsNull(Floor_1.FloorName,
         IsNull(Floor.FloorName,Cashmemo.Floor)) END ) ,CashMemo.CartAmt  UNION ALL SELECT  '' CMDate, '' CMNo,  Sum((CashMemoDet.Amount-((Case When DiscNotAllow=0
          then IsNull(Cashmemo.AddDiscPct,0) else 0 end )*(Cashmemodet.Amount)/100))) as NetAmt,  Sum((Case When DiscNotAllow=0 then IsNull(Cashmemo.AddDiscPct,0) else 0 end )
          *(Cashmemodet.Amount)/100) AddDiscAmt,  null FCashMemo, null FInvoice,  (CashMemo.CartAmt) CartAmt, 0 CaMoID,IsNull(Floor_1.FloorName, IsNull(Floor.FloorName,Cashmemo.Floor)) FloorName,  
          Sum((Case when cashmemodet.DiscPct<40 then CashMemoDet.DiscountAmount else 0 end)) AS DisAmount,  0 FloorID, 0 SalManID, 0 siteManID ,Sum( Case when (Cashmemodet.DiscPct<40  AND  
          Cashmemo.AddDiscPct<40 ) then (Cashmemodet.Amount + CashMemoDet.DiscountAmount) else 0 end)  AS GrossAmt,  Sum(CashMemoDet.TaxAmt) AS TaxAmt  ,0 PayModeCCard, 
          Sum(Case  when CashMemoDet.ExTax=1 then 0 else CashMemoDet.TaxAmt  end) AS TaxAmt1,Sum(CashMemoDet.NetTax) AS NetTaxAmt  ,Sum( Case when (Cashmemodet.DiscPct>=40 
          Or Cashmemo.AddDiscPct>=40 ) then (Cashmemodet.Amount) else 0 end) AS GrossAmt1  From CashMemoREL CashMemo  LEFT JOIN CashMemoDetREL CashMemoDet 
          ON CashMemo.CaMoID = CashMemoDet.CaMoID  left join Itemdet on cashmemodet.itemdetid=itemdet.itemdetid  left join floor on Itemdet.floorId=Floor.floorID  
          left join floor  as Floor_1 on Cashmemodet.SfloorId=Floor_1.floorID  left join Accounts on CashMemo.AccountID=Accounts.AccountID  JOIN CashMemo CashMemo_1 
           ON CashMemo.Relid=CashMemo_1.CAMOID Where IsNull(CashMemo_1.PerformaId, 0) = 0 and   Convert(Varchar(8), Cashmemo.CMDate, 112) Between '{{DATE_FROM}}' And '{{DATE_TO}}' 
           And IsNull(DMRP,0)=0 GROUP BY IsNull(Floor_1.FloorName, IsNull(Floor.FloorName,Cashmemo.Floor)),CashMemo.CartAmt  ) P  Group by FloorName  
           Union All  Select '' CMDate, '' CMNo, -Sum(NetAmt) as NetAmt,  -Sum(AddDiscAmt) AddDiscAmt,  null FCashMemo, null FInvoice  , -sum(CartAmt) CartAmt, 0 CaMoID, FloorName,  
           -Sum(DisAmount) AS DisAmount,  0 FloorID, 0 SalManID, 0 siteManID,  -Sum(GrossAmt) AS GrossAmt,  -Sum(TaxAmt) AS TaxAmt  ,0 PayModeCCard,-Sum(TaxAmt1) AS TaxAmt1 
           ,-sum(NetTaxAmt) as NetTaxAmt,0 GrossAmt1  from  (SELECT  '' CMDate, '' CMNo,  Sum(CreditNoteDet.Amt-CreditNoteDet.Amt*CreditNote.Spldisc/100) as NetAmt,  
           Sum(IsNull(CreditNote.Spldisc,0)*(CreditNoteDet.Amt)/100) AddDiscAmt,  null FCashMemo, null FInvoice,  (CreditNote.Cartage) CartAmt, 0 CaMoID, Floor.FloorName FloorName,  
           Sum(creditnotedet.Disc) AS DisAmount,  0 FloorID, 0 SalManID, 0 siteManID,sum(CreditNoteDet.Amt) AS GrossAmt,    
           Sum(creditnotedet.TaxAmt -(creditnotedet.TaxAmt*IsNull(CreditNote.Spldisc,0)/100)) AS TaxAmt   ,0 PayModeCCard, 0 TaxAmt1,Sum(creditnotedet.TaxAmt) AS NetTaxAmt From CreditNote  
           LEFT JOIN CreditNoteDet ON CreditNote.CrId= CreditNoteDet.CrId  left join Itemdet on CreditNoteDet.itemdetid=itemdet.itemdetid  left join floor on Itemdet.floorId=Floor.floorID  
           LEFT JOIN Accounts ON CreditNote.CrAccID= Accounts.AccountID where  Convert(Varchar(8),  CreditNote.CrDate, 112) Between '{{DATE_FROM}}' And '{{DATE_TO}}' And IsNull(DMRP,0)=0 
           GROUP BY Floor.FloorName,CreditNote.Cartage ) P  Group by FloorName ) as PA  group by FloorName 
           Union All  
           SELECT 'UnAdj Rec' FloorName,SUM((Receipt.Amount-IsNull(AdjRec.Adamt,IsNull(AdjustedAmt,0)))
            /(cASE WHEN IsNull(recadddisc,0)>0 then (1-(IsNull(recadddisc,0)/100)) else 1 end))  GrossAmt,0,SUM((Receipt.Amount-IsNull(AdjRec.Adamt,IsNull(AdjustedAmt,0)))/(cASE WHEN IsNull(recadddisc,0)>0  then (1-(IsNull(recadddisc,0)/100)) else 1 end)) 
            -SUM(Receipt.Amount) -SUM( IsNull(AdjRec.Adamt,IsNull(AdjustedAmt,0)))  DisAmountt, 0 AddDiscAmt,0 TaxAmt1,SUM(Receipt.Amount) -SUM( IsNull(AdjRec.Adamt,IsNull(AdjustedAmt,0)))  Netamt
            FROM (((((Receipt  Left Outer join Voucher ON Receipt.RecID = Voucher.RecID)  Left Outer Join Voucherdet ON Voucher.VouchID=VoucherDet.VouchID )  
            Left Outer Join Accounts on Voucherdet.AccountId=Accounts.AccountID)  Left Outer Join Address on Accounts.AccountId=Address.AccountID)  Left Outer Join City on Address.CityID=City.CityID) 
             Left Outer Join (Select Sum(IsNull(Amount,0)) As AdAmt,AdvouchdetID From Adjustment  Group by AdVouchdetID) As AdjRec on Voucherdet.VouchdetID=AdjRec.AdvouchdetID  
             Left outer join Employee on Receipt.SaleManID=Employee.EmployeeID  Where Voucherdet.Amount < 0  And Voucher.VouchType='R'  And Receipt.ChkCan=0  
              and Accounts.AccName not like 'Visiting%'  And Accounts.Customer=1 AND ISNULL(Receipt.RecID,0) not in (Select IsNull(ORecID,0) from QutRelation) and IsNull(Receipt.PerformaID,0)=0   
              And (Receipt.Amount-IsNull(AdjRec.Adamt,IsNull(AdjustedAmt,0)))>0 and Narration not like'%Return Rec %'  and CONVERT(varchar(8),recdate,112)between '{{DATE_FROM}}' and '{{DATE_TO}}' 
              Union All  
              select 'CARTAGE' FloorName,SUM(CartAmt)   GrossAmt,0,0 DisAmount, 0 AddDiscAmt,0 TaxAmt1,SUM(CartAmt)  NeTamt
              From CASHMEMO where  CONVERT(varchar(8),CMDATE,112)between '{{DATE_FROM}}' and '{{DATE_TO}}' 
              Union All  select 'INTERIOR' FloorName,0 grossamt,0,0 discountamt,0 adddisc,0 taxamt,SUM(Amount)  NeTamt
              From Receipt where Visiting = 1  and CONVERT(varchar(8),recdate,112)between '{{DATE_FROM}}' and '{{DATE_TO}}'
SQL;
    }

    /**
     * Floor wise sales rows + totals.
     */
    public static function report(int $locationId, string $dateFrom, string $dateTo): array
    {
        $database = self::databaseFor($locationId);
        if (!$database) {
            throw new \RuntimeException('No Sales Database is set for this location. Please set it on the Locations page.');
        }
        if (!self::databaseExists($database)) {
            throw new \RuntimeException("Sales database {$database} was not found on the server. Please check the Locations page.");
        }

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->startOfDay();

        if ($from->greaterThan($to)) {
            throw new \InvalidArgumentException('From Date cannot be greater than To Date.');
        }

        $maxDays = (int) config('floor_sales.max_days', 92);
        if ($from->diffInDays($to) + 1 > $maxDays) {
            throw new \InvalidArgumentException("Please select a range of maximum {$maxDays} days.");
        }

        $isToday = $to->isToday() || $to->isFuture();
        $ttl = $isToday ? (int) config('floor_sales.cache_seconds_today', 120) : (int) config('floor_sales.cache_seconds', 600);
        $key = 'floor_sales.' . $database . '.' . $from->format('Ymd') . '.' . $to->format('Ymd');

        $rows = Cache::remember($key, $ttl, function () use ($database, $from, $to) {
            @set_time_limit(180);

            // Dates are formatted by Carbon (digits only) -> safe to place in the SQL
            $sql = str_replace(
                ['{{DATE_FROM}}', '{{DATE_TO}}'],
                [$from->format('Ymd'), $to->format('Ymd')],
                self::query()
            );

            return array_map(fn ($r) => (array) $r, DB::connection(self::connectionFor($database))->select($sql));
        });

        $data = [];
        $totals = ['GrossAmt' => 0.0, 'GrossAmt1' => 0.0, 'DisAmount' => 0.0, 'AddDiscAmt' => 0.0, 'TaxAmt' => 0.0, 'NetAmt' => 0.0];

        foreach ($rows as $row) {
            // Column names come from the first SELECT of the query
            $row = array_change_key_case($row, CASE_LOWER);

            // Raw values (full precision) - used for the totals, so totals match
            // SQL exactly (rounding each row first can be 1 paisa off).
            $raw = [
                'GrossAmt' => (float) ($row['grossamt'] ?? 0),
                'GrossAmt1' => (float) ($row['grossamt1'] ?? 0),
                'DisAmount' => (float) ($row['disamount'] ?? 0),
                'AddDiscAmt' => (float) ($row['adddiscamt'] ?? 0),
                'TaxAmt' => (float) ($row['taxamt'] ?? 0),
                'NetAmt' => (float) ($row['netamt'] ?? 0),
            ];

            $item = ['FloorName' => trim((string) ($row['floorname'] ?? '')) ?: '-'];
            foreach ($raw as $k => $v) {
                $item[$k] = round($v, 2);
            }

            // Skip completely empty rows (e.g. INTERIOR with no receipts)
            if ($item['GrossAmt'] == 0 && $item['GrossAmt1'] == 0 && $item['NetAmt'] == 0
                && $item['DisAmount'] == 0 && $item['TaxAmt'] == 0) {
                continue;
            }

            foreach ($totals as $k => $v) {
                $totals[$k] += $raw[$k];
            }
            $data[] = $item;
        }

        usort($data, fn ($a, $b) => $b['NetAmt'] <=> $a['NetAmt']);

        foreach ($totals as $k => $v) {
            $totals[$k] = round($v, 2);
        }

        return [
            'data' => $data,
            'totals' => $totals,
            'database' => $database,
            'dateFrom' => $from->format('Y-m-d'),
            'dateTo' => $to->format('Y-m-d'),
        ];
    }
}