<?php

return [
    // Dashboard
    'menu_link' => 'Öneriler',
    'form_card_text' => 'Tamamlayıcı bir ürün öner, istersen indirimle.',
    'page_title' => ':name için öneriler',
    'intro' => 'Bu ürünle iyi giden ürünleri seçin. Müşteriler bunları isteğe bağlı bir "ekle" kartı olarak görür ve kapatabilir. Sepete otomatik hiçbir şey eklenmez.',
    'suggestion_n' => 'Öneri :n',
    'choose_product' => 'Bir ürün seçin…',
    'add' => 'Öneri ekle',
    'saved' => 'Öneriler kaydedildi.',
    'no_products_title' => 'Önce daha fazla ürün ekleyin',
    'no_products_text' => 'Öneriler bu ürünü başka bir ürüne bağlar. En az bir ürün daha oluşturun.',
    'optional_but_default' => 'isteğe bağlı',
    'title_placeholder' => 'örn. Tatlı eklemek ister misiniz?',
    'row_field' => 'Öneri :row — :field',

    'fields' => [
        'product' => 'Önerilen ürün',
        'title' => 'Başlık',
        'description' => 'Kısa metin',
        'discount' => 'İndirim',
        'discount_value' => 'İndirim tutarı',
        'active' => 'Bu öneriyi menüde göster',
    ],

    'discount' => [
        'none' => 'İndirim yok',
        'percent' => 'Yüzde',
        'fixed' => 'Tutar (:symbol)',
        'how' => 'Önerilen ürüne uygulanır; aynı siparişteki bu üründen her adet için bir adet. Müşteriler yalnızca burada belirlediğiniz indirimi görür.',
        'preview' => 'Müşteri önerilen ürün için :price öder.',
    ],

    'errors' => [
        'missing_default' => 'Öneri :row: lütfen menünüzün ana dili olan :language için de :field yazın.',
        'percent_max' => 'Öneri :row: yüzde :max%\'ten fazla olamaz.',
        'fixed_max' => 'Öneri :row: indirim ürün fiyatından düşük olmalıdır.',
    ],

    // Customer menu
    'order' => [
        'discount' => 'Teklifler (dahil)',
    ],
    'menu' => [
        'heading' => 'Yanına çok yakışır',
        'add' => 'Ekle',
        'added' => 'Eklendi',
        'dismiss' => 'Şimdi değil',
        'percent_off' => '%:value indirim',
        'amount_off' => ':amount indirim',
        'with_this' => ':name ile birlikte sipariş edilirse',
        'saved' => 'Teklifler',
    ],
];
