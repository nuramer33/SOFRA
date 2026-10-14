<?php

return [
    'nav' => 'Analiz',
    'title' => 'Analiz',
    'subtitle' => 'Müşterilerin menünüzü nasıl kullandığı. Yalnızca sayılar, kişisel veri yok.',

    'range' => ['7' => '7 gün', '30' => '30 gün', '90' => '90 gün'],
    'group' => ['label' => 'Grupla', 'day' => 'Günler', 'week' => 'Haftalar', 'month' => 'Aylar'],

    'kpi' => [
        'menu_view' => 'Menü görüntüleme',
        'product_view' => 'Ürün görüntüleme',
        'add_to_cart' => 'Sepete eklenen',
        'whatsapp_click' => 'WhatsApp düğmesi tıklaması',
    ],
    'kpi_hint' => [
        'menu_view' => 'Menünüzün açılma sayısı',
        'product_view' => 'Bir ürünün açılma sayısı',
        'add_to_cart' => 'Bir ürünün sepete eklenme sayısı',
        'whatsapp_click' => 'Sipariş düğmesinin WhatsApp\'ı açma sayısı',
    ],

    'filter' => 'Analizi filtrele',
    'period' => 'Dönem',
    'interface_language' => 'Panel dili',
    'interface_language_hint' => 'Bu panelin yalnızca sizin için görüntüleneceği dil.',

    'trend' => 'Zaman içinde',
    'top_viewed' => 'En çok görüntülenen ürünler',
    'top_added' => 'En çok sepete eklenenler',
    'times' => ':count×',
    'empty_title' => 'Henüz gösterilecek bir şey yok',
    'empty_text' => 'Müşteriler menünüzü açtıkça ilgilerini burada göreceksiniz.',
    'empty_list' => 'Bu dönemde veri yok.',

    'honesty_title' => 'Bu sayılar ne anlama geliyor',
    'honesty_text' => 'WhatsApp tıklaması, müşterinin sipariş düğmesine bastığı anlamına gelir. Onaylanmış bir sipariş ya da satış değildir: Sofra sohbette olanları görmez.',

    'privacy_title' => 'Gizlilik',
    'privacy_text' => 'Sofra yalnızca olayları sayar (menü açıldı, ürün açıldı, sepete eklendi, WhatsApp düğmesine basıldı). İsim, telefon numarası, adres, sipariş içeriği, IP adresi veya cihaz kimliği asla kaydedilmez. "Do Not Track" sinyali gönderen ziyaretçiler sayılmaz. Sayaçlar saklama sürenizin sonunda otomatik silinir.',

    'retention' => [
        'label' => 'Analiz verilerini sakla',
        'hint' => 'Eski sayaçlar otomatik olarak silinir.',
        'days' => ':days gün',
    ],
    'section_title' => 'Analiz ve gizlilik',
    'section_text' => 'Anonim sayaçların ne kadar süre saklanacağını seçin.',
];
