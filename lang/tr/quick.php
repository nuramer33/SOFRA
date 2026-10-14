<?php

return [
    'failed' => 'Kaydedilemedi. Bağlantınızı kontrol edip tekrar deneyin.',

    'closed' => [
        'paused' => 'Yeni siparişler şu anda durdurulmuş.',
        'hours' => 'Sipariş şu anda kapalı.',
    ],
    'opens_today' => 'Sipariş bugün saat :time\'de açılıyor.',
    'opens_on' => 'Sipariş :day günü saat :time\'de açılıyor.',

    'today' => [
        'title' => 'Bugün',
        'subtitle' => 'Telefonunuzdan yapabileceğiniz hızlı değişiklikler.',
    ],

    'pause' => [
        'title' => 'Sipariş alma',
        'open' => 'Müşteriler şu anda sipariş verebilir',
        'stop_label' => 'Yeni siparişleri durdur',
        'hint' => 'Menünüz görünür kalır ancak bu kapalıyken müşteriler sipariş gönderemez.',
        'on' => 'Siparişler durduruldu. Müşteriler menünüzü görmeye devam eder.',
        'off' => 'Siparişler yeniden açıldı.',
    ],

    'products' => ['title' => 'Ürünler'],

    'edit' => [
        'open' => ':name düzenle',
        'compare' => 'Eski fiyat (indirim için)',
        'compare_hint' => 'Normal fiyat için eski fiyatı boş bırakın. İndirim göstermek için daha yüksek bir eski fiyat girin.',
        'compare_error' => 'Eski fiyat, fiyattan yüksek olmalıdır.',
        'more' => 'Daha fazla seçenek',
        'saved' => 'Kaydedildi. Menü güncellendi.',
    ],

    'sale' => [
        'label' => 'İndirimde',
        'end' => 'İndirimi bitir',
        'ended' => 'İndirim sona erdi.',
    ],

    'promos' => [
        'title' => 'Kampanyalar',
        'on' => 'Açık',
        'off' => 'Kapalı',
        'hint' => 'Teklifler her ürünün altında "Öneriler" bölümünde hazırlanır. Burada açıp kapatabilirsiniz.',
    ],

    'hours' => [
        'link' => 'Sipariş saatleri',
        'on' => 'açık',
        'off' => 'her zaman açık',
        'title' => 'Sipariş saatleri',
        'subtitle' => 'Müşterilerin ne zaman sipariş gönderebileceğini seçin.',
        'limit_label' => 'Siparişi belirli saatlerle sınırla',
        'limit_hint' => 'Bu saatlerin dışında menü görünür kalır ancak sipariş kapalıdır.',
        'timezone' => 'Saat dilimi',
        'from' => 'Başlangıç',
        'to' => 'Bitiş',
        'closed_day' => 'Kapalı',
        'overnight_hint' => 'Kapanış saati açılış saatinden erkense, mağaza gece yarısından sonra kapanır.',
        'display_hint' => 'Bu ayar siparişlerin ne zaman kabul edileceğini belirler. Müşterilere gösterilen çalışma saatleri şurada yazılır:',
        'saved' => 'Sipariş ayarları kaydedildi.',
        'errors' => [
            'times' => ':day: açılış ve kapanış saatini ekleyin.',
            'same' => ':day: açılış ve kapanış saati aynı olamaz.',
            'none_open' => 'En az bir günü açın veya sipariş saatlerini kapatın.',
        ],
    ],

    'days' => [
        0 => 'Pazar', 1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi',
    ],
];
