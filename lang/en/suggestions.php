<?php

return [
    // Dashboard
    'menu_link' => 'Suggestions',
    'form_card_text' => 'Offer a complementary product, with an optional discount.',
    'page_title' => 'Suggestions for :name',
    'intro' => 'Pick products that go well with this one. Customers see them as an optional "add" card and can dismiss them. Nothing is added to the cart automatically.',
    'suggestion_n' => 'Suggestion :n',
    'choose_product' => 'Choose a product…',
    'add' => 'Add a suggestion',
    'saved' => 'Suggestions saved.',
    'no_products_title' => 'Add more products first',
    'no_products_text' => 'Suggestions link this product to another one. Create at least one more product.',
    'optional_but_default' => 'optional',
    'title_placeholder' => 'e.g. Add a dessert?',
    'row_field' => 'Suggestion :row — :field',

    'fields' => [
        'product' => 'Suggested product',
        'title' => 'Title',
        'description' => 'Short text',
        'discount' => 'Discount',
        'discount_value' => 'Discount amount',
        'active' => 'Show this suggestion on the menu',
    ],

    'discount' => [
        'none' => 'No discount',
        'percent' => 'Percentage',
        'fixed' => 'Amount (:symbol)',
        'how' => 'Applies to the suggested product, one unit for each unit of this product in the same order. Customers only see a discount you set here.',
        'preview' => 'Customer pays :price for the suggested product.',
    ],

    'errors' => [
        'missing_default' => 'Suggestion :row: please also write the :field in :language, the main language of your menu.',
        'percent_max' => 'Suggestion :row: the percentage cannot be more than :max%.',
        'fixed_max' => 'Suggestion :row: the discount must be less than the product price.',
    ],

    // Customer menu
    'order' => [
        'discount' => 'Offers (already included)',
    ],
    'menu' => [
        'heading' => 'Goes well with',
        'add' => 'Add',
        'added' => 'Added',
        'dismiss' => 'Not now',
        'percent_off' => ':value% off',
        'amount_off' => ':amount off',
        'with_this' => 'when ordered with :name',
        'saved' => 'Offers',
    ],
];
