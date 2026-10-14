<?php

return [

    // OLD table/field permission system. Replaced by config/user_access.php
    // (modules + module fields + locations). Keep false; set
    // FIELD_PERMISSIONS_ENABLED=true in .env only to bring the old behaviour back.
    'enabled' => env('FIELD_PERMISSIONS_ENABLED', false),

    // Route prefix => source table(s). Both web and API paths are covered.
    'routes' => [
        'stock' => ['HSStock', 'ItemDet'],
        'api/stock' => ['HSStock', 'ItemDet'],

        'itemstock' => ['HSStock', 'ItemDet', 'ItemStockUpdateDis'],
        'api/itemstock' => ['HSStock', 'ItemDet', 'ItemStockUpdateDis'],

        'uploadgateentrybill' => ['VendorInvoices'],
        'api/uploadgateentrybill' => ['VendorInvoices'],

        'pendingrent' => ['PendingRent'],
        'api/pendingrent' => ['PendingRent'],
        'api/homestay/pending-rent' => ['PendingRent'],

        'studentdetails' => ['StudentDetails'],
        'api/studentdetails' => ['StudentDetails'],
        'api/homestay/student-details' => ['StudentDetails'],

        'bedoccupancy' => ['BedOccupancy'],
        'api/bedoccupancy' => ['BedOccupancy'],
        'api/homestay/bed-occupancy' => ['BedOccupancy'],

        'newstudent' => ['StudentNewAdmission'],
        'api/newstudent' => ['StudentNewAdmission'],
        'api/homestay/new-student' => ['StudentNewAdmission'],

        // documentdownload v2 reads live from CashMemo/TblQutation/etc. on
        // each location's own database — the old field-permission rules
        // for DocumentPrint/DocType no longer apply here.

        'locations' => ['Location'],
        'api/locations' => ['Location'],

        // GRN/Gate Entry use stored procedures/remote DB. They are exposed as virtual permission modules.
        'grn' => ['GRN'],
        'api/grn' => ['GRN'],
        'gateentry' => ['GateEntry'],
        'api/gateentry' => ['GateEntry'],
    ],

    // Stored-procedure result fields. No DB inserts are required.
    'virtual_fields' => [
        'GRN' => [
            'GRNID','GmNo','Supplier','FloorName','GmDate','GmAmount','BillAmount','TotMRP','LocationID',
            'ItemCode','ItemName','Qty','Rate','MRP','Amount','DesignName','Color','Size','Unit','HSNCode','GST'
        ],
        'GateEntry' => [
            'GRNID','GmNo','Supplier','FloorName','GmDate','GmAmount','BillAmount','TotMRP','LocationID',
            'ItemCode','ItemName','Qty','Rate','MRP','Amount','DesignName','Color','Size','Unit','HSNCode','GST'
        ],
    ],

    // Response/display aliases for DB fields renamed by controllers.
    'aliases' => [
        'HSStock' => [
            'ProductName' => ['Product'],
            'DesignName' => ['Design'],
            'StkQty' => ['Quantity','Qty','StockQty'],
            'ACost' => ['Cost'],
            'HSNCODE' => ['HSN Code','HSNCode'],
            'SectionName' => ['Section'],
            'CompanyName' => ['Company'],
        ],
        'ItemDet' => [
            'QualityName' => ['Quality'],
            'SerialNo' => ['Serial No'],
            'SupplierName' => ['Supplier'],
            'SupplierMobileNo' => ['Supplier Mobile'],
            'ContactPerson' => ['Contact Person'],
        ],
        'PendingRent' => [
            'LocationName' => ['Location'],
            'RentAmount' => ['Rent Amount'],
            'RecAmount' => ['Received Amount','Rec Amount'],
            'BalAmount' => ['Balance Amount','Bal Amount'],
            'RentDate' => ['Rent Date'],
            'StudentImage' => ['Image'],
        ],
        'StudentDetails' => [
            'StudentName' => ['Student Name','Name'],
            'RoomNo' => ['Room No'],
            'BedNo' => ['Bed No'],
            'RentAmt' => ['Rent Amount','Rent'],
            'RentDate' => ['Rent Date'],
            'MobileNo' => ['Mobile','Mobile No'],
            'FatherName' => ['Father Name'],
            'StartingDate' => ['Starting Date','Start Date'],
            'EndingDate' => ['Ending Date','End Date'],
            'StudentImage' => ['Image'],
        ],
        'BedOccupancy' => [
            'LocationName' => ['Location'],
            'Letout' => ['Occupied','Let Out'],
            'Vacant' => ['Vacant'],
            'TOTALBED' => ['Total Bed','Total Beds'],
        ],
        'StudentNewAdmission' => [
            'FirstName' => ['First Name'],
            'LastName' => ['Last Name'],
            'MobileNo' => ['Mobile','Mobile No'],
            'FatherName' => ['Father Name'],
            'MotherName' => ['Mother Name'],
            'FatherMobileNo' => ['Father Mobile','Father Mobile No'],
            'MotherMobileNo' => ['Mother Mobile','Mother Mobile No'],
        ],
        'VendorInvoices' => [
            'ImagePath' => ['Image'],
            'ApprovalStatus' => ['Status'],
            'ApprovedByAdminID' => ['Approved By Admin ID'],
            'CreatedDate' => ['Created Date'],
            'ApprovalNotes' => ['Notes','Approval Notes'],
        ],
        'DocumentPrint' => [
            'DocID' => ['id'],
            'DocNo' => ['BillNo','docNo','Document No','Bill No'],
            'DocType' => ['DocTypeName','docType','Document Type'],
            'VendorName' => ['AccountName','vendorName','Vendor Name','Account Name'],
            'ItemCode' => ['itemCode','Item Code'],
            'DocDate' => ['DocumentDate','docDate','Document Date'],
            'LOCATION' => ['Location','location'],
            'Docpdf' => ['hasPdf','PDF'],
        ],
        'DocType' => [
            'DocTypeID' => ['Document Type ID'],
            'DocTypeName' => ['Document Type'],
        ],
        'Location' => [
            'LocationID' => ['Location ID'],
            'Locationcode' => ['Location Code','Code'],
            'LocationName' => ['Location Name','Name'],
            'LocationAddress' => ['Address'],
            'QRCODE' => ['QR Code'],
        ],
    ],

    // Technical/presentation keys required for paging/actions must remain available.
    'always_visible_keys' => [
        'success','status','message','error','draw','recordsTotal','recordsFiltered','total','current_page','last_page','per_page',
        'data','items','stats','locationCodes','activeLocationCodes','allowed_fields','is_admin','_field_permissions',
        'DT_RowIndex','RowNum','Action','actions','url','route','download_url',
        'ID','Id','id','GRNID','GmID','ItemDetID','HsStockID','RentPendingID','StudentdetID','BedOccID','NewAdmissionID','DocID','LocationID'
    ],
];