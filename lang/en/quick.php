<?php

return [
    'failed' => 'Could not save. Check your connection and try again.',

    // Shown to customers
    'closed' => [
        'paused' => 'New orders are paused right now.',
        'hours' => 'Ordering is closed right now.',
    ],
    'opens_today' => 'Ordering opens today at :time.',
    'opens_on' => 'Ordering opens on :day at :time.',

    'today' => [
        'title' => 'Today',
        'subtitle' => 'Quick changes you can make from your phone.',
    ],

    'pause' => [
        'title' => 'Taking orders',
        'open' => 'Customers can order now',
        'stop_label' => 'Pause new orders',
        'hint' => 'Your menu stays visible, but customers cannot send orders while this is off.',
        'on' => 'Orders are paused. Customers can still see your menu.',
        'off' => 'Orders are open again.',
    ],

    'products' => ['title' => 'Products'],

    'edit' => [
        'open' => 'Edit :name',
        'compare' => 'Old price (for a sale)',
        'compare_hint' => 'Leave the old price empty for a normal price. Add a higher old price to show a sale.',
        'compare_error' => 'The old price must be higher than the price.',
        'more' => 'More options',
        'saved' => 'Saved. The menu is updated.',
    ],

    'sale' => [
        'label' => 'On sale',
        'end' => 'End sale',
        'ended' => 'Sale ended.',
    ],

    'promos' => [
        'title' => 'Promotions',
        'on' => 'On',
        'off' => 'Off',
        'hint' => 'Offers are set up under each product, in "Suggestions". Here you can switch them on or off.',
    ],

    'hours' => [
        'link' => 'Ordering hours',
        'on' => 'on',
        'off' => 'always open',
        'title' => 'Ordering hours',
        'subtitle' => 'Choose when customers can send orders.',
        'limit_label' => 'Limit ordering to set hours',
        'limit_hint' => 'Outside these hours the menu stays visible but orders are closed.',
        'timezone' => 'Time zone',
        'from' => 'From',
        'to' => 'To',
        'closed_day' => 'Closed',
        'overnight_hint' => 'A closing time earlier than the opening time means the shop closes after midnight.',
        'display_hint' => 'This controls when orders are accepted. The opening hours shown to customers are written in',
        'saved' => 'Ordering settings saved.',
        'errors' => [
            'times' => ':day: add the opening and closing time.',
            'same' => ':day: opening and closing time cannot be the same.',
            'none_open' => 'Open at least one day, or turn off the ordering hours.',
        ],
    ],

    'days' => [
        0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
    ],
];
