<?php

return [
    '403' => ['title' => 'Bu alan size açık değil', 'text' => 'Bu sayfayı açma yetkiniz yok.'],
    '419' => ['title' => 'Sayfanın süresi doldu', 'text' => 'Sayfa çok uzun süre açık kaldı. Sayfayı yenileyip tekrar deneyin.', 'retry' => 'Yenile'],
    '429' => ['title' => 'Çok fazla deneme', 'text' => 'Lütfen bir dakika bekleyip tekrar deneyin.', 'retry' => 'Tekrar dene'],
    '500' => ['title' => 'Bizim tarafımızda bir sorun oluştu', 'text' => 'Üzgünüz. Sorun kaydedildi ve inceleyeceğiz. Lütfen biraz sonra tekrar deneyin.', 'retry' => 'Tekrar dene'],
    '503' => ['title' => 'Hemen döneceğiz', 'text' => 'Sofra güncelleniyor. Birkaç dakika sürer. Lütfen kısa süre sonra tekrar gelin.', 'retry' => 'Yenile'],
];
