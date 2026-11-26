<?php

return [
    /*
    | BARGO product lines used in the Consolidated BARGO Products Report
    | (mirrors the Order of Payment / Excel workbook product list).
    */
    'products' => [
        'Alumni ID',
        'Bamboo',
        'CISA Services',
        'Coconut',
        'Crops',
        'Examination Booklet',
        'Fruits',
        'Gate Pass Sticker',
        'Livestock',
        'Merchandise',
        'Organic Fertilizer',
        'Others',
        'Rental',
        'Rice',
        'Uniform',
        'Wood Charcoal',
        'Vegetables',
    ],

    /*
    | Map inventory category names (case-insensitive) to a BARGO product line.
    */
    'category_map' => [
        'alumni id' => 'Alumni ID',
        'bamboo' => 'Bamboo',
        'cisa services' => 'CISA Services',
        'cisa' => 'CISA Services',
        'coconut' => 'Coconut',
        'coconuts' => 'Coconut',
        'crops' => 'Crops',
        'agricultural products' => 'Crops',
        'examination booklet' => 'Examination Booklet',
        'fruits' => 'Fruits',
        'vegetables and fruits' => 'Vegetables',
        'gate pass sticker' => 'Gate Pass Sticker',
        'livestock' => 'Livestock',
        'livestock-related inventory' => 'Livestock',
        'merchandise' => 'Merchandise',
        'organic fertilizer' => 'Organic Fertilizer',
        'rental' => 'Rental',
        'rice' => 'Rice',
        'rice and grains' => 'Rice',
        'uniform' => 'Uniform',
        'wood charcoal' => 'Wood Charcoal',
        'vegetables' => 'Vegetables',
        'office supplies' => 'Others',
        'equipment' => 'Others',
    ],

    'signatories' => [
        'prepared_by_title' => env('BARGO_PREPARED_BY_TITLE', 'Office Assistant'),
        'approved_by_title' => env('BARGO_APPROVED_BY_TITLE', 'BARGO, Head'),
    ],

    /*
    | Payment Order (ORDER-PAYMENT.xlsx) signatory block.
    | Coordinator name is taken from the active administrator account;
    | coordinator_name is only a fallback if no active admin exists.
    */
    'payment_order' => [
        'coordinator_name' => env('BARGO_COORDINATOR_NAME', ''),
        'coordinator_title' => env('BARGO_COORDINATOR_TITLE', 'Coordinator, BARGO'),
        'default_collection_type' => env('BARGO_DEFAULT_COLLECTION_TYPE', 'Non-Institutional'),
    ],
];
