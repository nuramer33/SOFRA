<?php

return [
    'nav' => 'Insights',
    'title' => 'Insights',
    'subtitle' => 'How customers use your menu. Counts only, no personal data.',

    'range' => ['7' => '7 days', '30' => '30 days', '90' => '90 days'],
    'group' => ['label' => 'Group by', 'day' => 'Days', 'week' => 'Weeks', 'month' => 'Months'],

    'kpi' => [
        'menu_view' => 'Menu views',
        'product_view' => 'Product views',
        'add_to_cart' => 'Added to cart',
        'whatsapp_click' => 'WhatsApp button clicks',
    ],
    'kpi_hint' => [
        'menu_view' => 'Times your menu was opened',
        'product_view' => 'Times a product was opened',
        'add_to_cart' => 'Times a product was added to a cart',
        'whatsapp_click' => 'Times the order button opened WhatsApp',
    ],

    'filter' => 'Filter insights',
    'period' => 'Period',
    'interface_language' => 'Dashboard language',
    'interface_language_hint' => 'The language this dashboard is shown in, for you only.',

    'trend' => 'Over time',
    'top_viewed' => 'Most viewed products',
    'top_added' => 'Most added to cart',
    'times' => ':count×',
    'empty_title' => 'Nothing to show yet',
    'empty_text' => 'Once customers open your menu, you will see their interest here.',
    'empty_list' => 'No data in this period.',

    'honesty_title' => 'What these numbers mean',
    'honesty_text' => 'A WhatsApp click means a customer pressed the order button. It is not a confirmed order or a sale: Sofra never sees what happens in the chat.',

    'privacy_title' => 'Privacy',
    'privacy_text' => 'Sofra only counts events (menu opened, product opened, added to cart, WhatsApp button pressed). It never records names, phone numbers, addresses, order contents, IP addresses or device identifiers. Visitors who send a "Do Not Track" signal are not counted. Counters are deleted automatically after your retention period.',

    'retention' => [
        'label' => 'Keep insights for',
        'hint' => 'Older counters are deleted automatically.',
        'days' => ':days days',
    ],
    'section_title' => 'Insights and privacy',
    'section_text' => 'Choose how long anonymous counters are kept.',
];
