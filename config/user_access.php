<?php

/*
|--------------------------------------------------------------------------
| User Access (Modules, Module Fields, Locations)
|--------------------------------------------------------------------------
| - Admin (user_role = 1) always has full access.
| - Other users get only the modules, fields and locations ticked on the
|   User form.
|
| modules.*.paths : URL prefixes (lower-case, no leading slash) that belong
|                   to the module. Requests to these paths are blocked for
|                   users without the module (web + api).
| modules.*.fields: optional field-level permissions inside the module.
*/

return [

    'modules' => [
        'all-stock-details' => [
            'label' => 'ALL STOCK DETAILS',
            'image' => 'ALL STOCK DETAILS.jpg',
            'icon' => 'bi-boxes',
            'route' => 'stock.list',
            'paths' => ['stock', 'api/stock'],
        ],
        'item-stock' => [
            'label' => 'ITEM STOCK',
            'image' => 'ITEM STOCK.jpg',
            'icon' => 'bi-box-seam',
            'route' => 'itemstock.list',
            'paths' => ['itemstock', 'api/itemstock'],
            'fields' => [
                'supplier_name' => 'Supplier Name',
                'supplier_mobile' => 'Supplier Mobile',
                'contact_person' => 'Contact Person',
                'markup' => 'Markup (%)',
                'markdown' => 'Markdown (%)',
                'discount' => 'Discount (+ Save Discount)',
                'dp_exclusive' => 'DP Exclusive',
                'mrp' => 'MRP (+ Save MRP)',
                'image_upload' => 'Image Upload',
            ],
        ],
        'vendor-invoice' => [
            'label' => 'UPLOAD GATE ENTRY BILL',
            'image' => 'UPLOAD BILL.png',
            'icon' => 'bi-receipt',
            'route' => 'uploadgateentrybill.list',
            'paths' => ['uploadgateentrybill', 'api/uploadgateentrybill'],
        ],
        'home-stay' => [
            'label' => 'HOME STAY',
            'image' => 'HOMESTAY.jpg',
            'icon' => 'bi-house-heart',
            'route' => 'homeStay.homeStay',
            'paths' => [
                'homestay', 'pendingrent', 'studentdetails', 'bedoccupancy', 'newstudent',
                'api/pendingrent', 'api/studentdetails', 'api/bedoccupancy', 'api/newstudent', 'api/homestay',
            ],
        ],
        'gate-entry' => [
            'label' => 'GATE ENTRY',
            'image' => 'GATE ENTRY.jpg',
            'icon' => 'bi-door-open',
            'route' => 'gateEntry.list',
            'paths' => ['gateentry', 'api/gateentry'],
        ],
        'grn' => [
            'label' => 'GRN',
            'image' => 'GRN.png',
            'icon' => 'bi-clipboard-check',
            'route' => 'grn.list',
            'paths' => ['grn', 'api/grn'],
        ],
        'attendance' => [
            'label' => 'ATTENDANCE',
            'image' => 'ATTENDANCE.jpg',
            'icon' => 'bi-people',
            'route' => 'attendance.list',
            'paths' => ['attendance', 'api/attendance'],
        ],
        'document-download' => [
            'label' => 'DOCUMENT DOWNLOAD',
            'image' => 'DOWNLOAD DOCUMENT.jpg',
            'icon' => 'bi-file-earmark-arrow-down',
            'route' => 'documentDownload.list',
            'paths' => ['documentdownload', 'api/documentdownload'],
        ],
        'daily-collection' => [
            'label' => 'DAILY COLLECTION',
            'image' => 'DAILY COLLECTION.jpg',
            'icon' => 'bi-cash-stack',
            'route' => 'dailyCollection.list',
            'paths' => ['dailycollection', 'api/daily-collection'],
        ],
        'pearl-stay' => [
            'label' => 'PEARL STAY',
            'image' => 'PEARL STAY.jpg',
            'icon' => 'bi-house-door',
            'route' => null,
            'paths' => [],
        ],
        'floor-wise-sales' => [
            'label' => 'FLOOR WISE SALES',
            'image' => 'FLOOR WISE SALES.jpg',
            'icon' => 'bi-graph-up',
            'route' => 'floorWiseSales.list',
            'paths' => ['floorwisesales', 'api/floor-wise-sales'],
        ],
    ],

    // Paths every logged-in user may call even without the owning module
    // (e.g. item images are served through the stock image proxy).
    'shared_paths' => [
        'stock/image-proxy',
    ],
];