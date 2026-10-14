<?php

/*
|--------------------------------------------------------------------------
| Document letterheads (built from the 5 template PDFs)
|--------------------------------------------------------------------------
| Each location has two artworks taken from its own template PDF:
|   full  : full A4 letterhead  -> Invoice, Credit Card Invoice,
|           Quotation, Debit Note, Performa
|   slip  : landscape slip       -> Cash Memo / Retail Invoice slip
| Background images live in public/assets/letterheads/.
| All values are mm, origin = top-left of the page.
|   items : table. top = first row, bottom = last row limit, row_h = row height.
|           columns = [key, label, x_from, x_to, align]
|   party / invoice : [x, y, width, height] boxes
|   totals : [x, y, width]
|   compact : smaller text for tight boxes (slips)
*/

return [

    // LocationID => key in 'full' / 'slip' below
    'location_map' => [
        8  => 'ljp',   // HOME SAAZ LJP
        10 => 'rjc',   // RAJCO HANDLOOM HOUSE
        17 => 'ls',    // LIVE n STYLE
        26 => 'hsd',   // HOMESAAZ DECORATORS
        29 => 'av',    // 37 AVENUE
    ],
    'default' => 'ljp',

    'slip_map' => [
        8  => 'ljp',
        10 => 'rjc',
        17 => 'ls',
        26 => 'hsd',
        29 => 'av',
    ],
    'slip_default' => 'ljp',

    'templates' => [

        'ljp' => [
            'name' => 'HomeSaaz (Lajpat Nagar)',
            'background' => 'images/letterheads/ljp_full.jpg',
            'page' => [210, 297],
            'items' => [
                'top' => 91.0, 'bottom' => 240.0, 'row_h' => 6.0,
                'columns' => [
                    ['sno', 'S.No', 4.6, 13.5, 'center'],
                    ['item_code', 'Item Code', 13.5, 36.1, 'left'],
                    ['hsn', 'HSN Code', 36.1, 57.1, 'center'],
                    ['desc', 'Description', 57.1, 98.5, 'left'],
                    ['gst', 'GST%', 98.5, 114.3, 'center'],
                    ['qty', 'Quantity', 114.3, 135.6, 'center'],
                    ['unit', 'Unit', 135.6, 152.9, 'center'],
                    ['rate', 'Rate', 152.9, 178.7, 'right'],
                    ['amount', 'Amount', 178.7, 197.5, 'right'],
                ],
            ],
            'party' => [7.6, 44.0, 94, 36],
            'invoice' => [119.4, 44.0, 86, 36],
            'totals' => [122, 189.0, 74],
            'gst_box' => [7.6, 200.0],
        ],

        'rjc' => [
            'name' => 'Rajco Handloom House',
            'background' => 'images/letterheads/rjc_full.jpg',
            'page' => [210, 297],
            'items' => [
                'top' => 88.6, 'bottom' => 248.5, 'row_h' => 6.2,
                'columns' => [
                    ['sno', 'S.No.', 6.3, 19.0, 'center'],
                    ['item_code', 'Item Code', 19.0, 39.9, 'left'],
                    ['hsn', 'HSN Code', 39.9, 61.7, 'center'],
                    ['desc', 'Description', 61.7, 101.3, 'left'],
                    ['gst', 'GST%', 101.3, 116.6, 'center'],
                    ['qty', 'Quantity', 116.6, 136.9, 'center'],
                    ['unit', 'Unit', 136.9, 155.7, 'center'],
                    ['rate', 'Rate', 155.7, 178.3, 'right'],
                    ['amount', 'Amount', 178.3, 203.9, 'right'],
                ],
            ],
            'party' => [10.2, 44.5, 96, 33],
            'invoice' => [121.9, 44.5, 81, 33],
            'totals' => [136, 222, 68],
        ],

        'ls' => [
            'name' => 'Live n Style',
            'background' => 'images/letterheads/ls_full.jpg',
            'page' => [210, 297],
            'items' => [
                'top' => 88.0, 'bottom' => 256.0, 'row_h' => 6.2,
                'columns' => [
                    ['sno', 'S.No', 6.3, 19.6, 'center'],
                    ['item_code', 'Item Code', 19.6, 44.4, 'left'],
                    ['desc', 'Description', 44.4, 97.7, 'left'],
                    ['qty', 'Quantity', 97.7, 121.3, 'center'],
                    ['unit', 'Unit', 121.3, 140.4, 'center'],
                    ['rate', 'Rate', 140.4, 169.1, 'right'],
                    ['amount', 'Amount', 169.1, 202.0, 'right'],
                ],
            ],
            'party' => [6.3, 44.5, 100, 33],
            'header_values' => [
                ['key' => 'CMNo', 'x' => 142.2, 'y' => 44.6],
                ['key' => 'CMDate', 'x' => 142.2, 'y' => 52.0],
                ['key' => 'SalManID', 'x' => 142.2, 'y' => 59.4],
                ['key' => 'SiteManName', 'x' => 142.2, 'y' => 66.7],
                ['key' => 'AuthBY', 'x' => 142.2, 'y' => 73.8],
            ],
            'totals' => [135, 226, 68],
            'gst_box' => [6.3, 226.0],
        ],

        'hsd' => [
            'name' => 'HomeSaaz Decorators',
            'background' => 'images/letterheads/hsd_full.jpg',
            'page' => [210, 297],
            'items' => [
                'top' => 77.0, 'bottom' => 246.5, 'row_h' => 6.0,
                'columns' => [
                    ['sno', 'S.No', 6.3, 16.8, 'center'],
                    ['item_code', 'Item Code', 16.8, 35.6, 'left'],
                    ['hsn', 'HSN Code', 35.6, 60.9, 'center'],
                    ['desc', 'Description', 60.9, 103.0, 'left'],
                    ['gst', 'GST%', 103.0, 116.5, 'center'],
                    ['qty', 'Quantity', 116.5, 136.4, 'center'],
                    ['unit', 'Unit', 136.4, 156.7, 'center'],
                    ['rate', 'Rate', 156.7, 179.3, 'right'],
                    ['amount', 'Amount', 179.3, 201.5, 'right'],
                ],
            ],
            'compact' => true,
            'party' => [6.3, 40.5, 100, 25],
            'invoice' => [119.4, 40.5, 86, 25],
            'totals' => [123, 212, 74],
            'gst_box' => [6.3, 212.0],
        ],

        // 37 Avenue: landscape sheet (A5) carries the table
        'av' => [
            'name' => '37 Avenue',
            'background' => 'images/letterheads/av_full.jpg',
            'page' => [297, 198],
            'items' => [
                'top' => 64.0, 'bottom' => 160.0, 'row_h' => 5.8,
                'columns' => [
                    ['sno', 'S.No', 8.0, 20.0, 'center'],
                    ['hsn', 'HSN Code', 20.0, 50.0, 'center'],
                    ['desc', 'Description', 50.0, 135.0, 'left'],
                    ['gst', 'GST%', 135.0, 152.0, 'center'],
                    ['qty', 'Quantity', 152.0, 172.0, 'center'],
                    ['unit', 'Unit', 172.0, 188.0, 'center'],
                    ['rate', 'Rate', 188.0, 212.0, 'right'],
                    ['amount', 'Amount', 212.0, 240.0, 'right'],
                ],
            ],
            'compact' => true,
            'party_fields' => [],
            'party' => [8.0, 56.0, 110, 22],
            // No./Date box removed for this sheet
            'totals' => [170, 150.0, 68],
            'gst_box' => [8.0, 150.0],
        ],
    ],

    'slip_templates' => [

        'ljp' => [
            'name' => 'HomeSaaz LJP slip',
            'background' => 'images/letterheads/ljp_slip.jpg',
            'page' => [207.4, 137.1],
            'items' => [
                'top' => 28.9, 'bottom' => 82.0, 'row_h' => 5.5,
                'columns' => [
                    ['sno', 'S.No', 0.0, 10.2, 'center'],
                    ['hsn', 'HSN Code', 10.2, 43.2, 'left'],
                    ['desc', 'Description', 43.2, 82.1, 'left'],
                    ['gst', 'GST%', 82.1, 93.1, 'center'],
                    ['qty', 'Quantity', 93.1, 110.9, 'center'],
                    ['unit', 'Unit', 110.9, 131.2, 'center'],
                    ['rate', 'Rate', 131.2, 154.9, 'right'],
                    ['amount', 'Amount', 154.9, 175.3, 'right'],
                ],
            ],
            'compact' => true,
            'party' => [24.0, 8.4, 72.0, 12.2],
            'invoice' => [98.0, 8.4, 30.0, 12.2],
            'totals' => [122, 86.0, 52],
            'gst_box' => [4.0, 74.0],
        ],

        'rjc' => [
            'name' => 'Rajco slip',
            'background' => 'images/letterheads/rjc_slip.jpg',
            'page' => [209.9, 140.2],
            'items' => [
                'top' => 44.0, 'bottom' => 82.0, 'row_h' => 5.5,
                'columns' => [
                    ['sno', 'Sr.No', 0.0, 17.5, 'center'],
                    ['item_code', 'Item Code', 17.5, 35.2, 'left'],
                    ['desc', 'Description', 35.2, 87.3, 'left'],
                    ['qty', 'Qty', 87.3, 104.2, 'center'],
                    ['unit', 'Unit', 104.2, 118.3, 'center'],
                    ['rate', 'Rate', 118.3, 149.5, 'right'],
                    ['amount', 'Amount', 149.5, 177.3, 'right'],
                ],
            ],
            'compact' => true,
            'party' => [6.7, 35.0, 100, 9.0],
            'party_fields' => ['name', 'address', 'city'],
            'totals' => [116, 86.0, 62],
            'gst_box' => [4.0, 86.0],
        ],

        'ls' => [
            'name' => 'Live n Style slip',
            'background' => 'images/letterheads/ls_slip.jpg',
            'page' => [209.9, 140.2],
            'items' => [
                'top' => 47.5, 'bottom' => 96.0, 'row_h' => 5.5,
                'columns' => [
                    ['sno', 'S.No', 0.0, 13.0, 'center'],
                    ['hsn', 'HSN Code', 13.0, 35.7, 'left'],
                    ['desc', 'Description', 35.7, 87.3, 'left'],
                    ['gst', 'GST%', 87.3, 102.9, 'center'],
                    ['qty', 'Quantity', 102.9, 120.1, 'center'],
                    ['unit', 'Unit', 120.1, 135.7, 'center'],
                    ['rate', 'Rate', 135.7, 160.0, 'right'],
                    ['amount', 'Amount', 160.0, 180.5, 'right'],
                ],
            ],
            'compact' => true,
            'party' => [6.7, 31.5, 100, 14.5],
            'invoice' => [111.0, 31.5, 60, 14.5],
            'totals' => [127, 100.0, 56],
            'gst_box' => [6.0, 100.0],
        ],

        'hsd' => [
            'name' => 'HomeSaaz Decorators slip',
            'background' => 'images/letterheads/hsd_slip.jpg',
            'page' => [209.9, 140.2],
            'items' => [
                'top' => 42.0, 'bottom' => 82.0, 'row_h' => 5.5,
                'columns' => [
                    ['sno', 'S.No', 4.2, 15.2, 'center'],
                    ['hsn', 'HSN Code', 15.2, 49.1, 'left'],
                    ['desc', 'Description', 49.1, 84.6, 'left'],
                    ['gst', 'GST%', 84.6, 95.6, 'center'],
                    ['qty', 'Quantity', 95.6, 113.4, 'center'],
                    ['unit', 'Unit', 113.4, 133.7, 'center'],
                    ['rate', 'Rate', 133.7, 157.4, 'right'],
                    ['amount', 'Amount', 157.4, 176.1, 'right'],
                ],
            ],
            'compact' => true,
            'party' => [6.7, 33.5, 101.6, 8.0],
            'party_fields' => ['name', 'city'],
            'totals' => [118.5, 84.0, 52],
            'gst_box' => [4.0, 74.0],
        ],

        'av' => [
            'name' => '37 Avenue slip',
            'background' => 'images/letterheads/av_slip.jpg',
            'page' => [210, 140.2],
            'items' => [
                'top' => 42.0, 'bottom' => 100.0, 'row_h' => 5.6,
                'columns' => [
                    ['sno', 'S.No', 0.0, 12.5, 'center'],
                    ['hsn', 'HSN Code', 12.5, 40.0, 'left'],
                    ['desc', 'Description', 40.0, 73.0, 'left'],
                    ['gst', 'GST%', 73.0, 96.3, 'center'],
                    ['qty', 'Quantity', 96.3, 111.8, 'center'],
                    ['unit', 'Unit', 111.8, 129.3, 'center'],
                    ['rate', 'Rate', 129.3, 150.8, 'right'],
                    ['amount', 'Amount', 150.8, 172.0, 'right'],
                ],
            ],
            'compact' => true,
            'party' => [3.0, 37.5, 110, 11],
            'party_fields' => ['name'],
            // No./Date box removed for this sheet
            'totals' => [110, 92.0, 60],
            'gst_box' => [8.0, 92.0],
        ],
    ],
];