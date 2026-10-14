<?php

return [
    'updated_label' => 'Son güncelleme',
    'updated' => 'Ekim 2026',
    'contact_title' => 'İletişim',
    'contact_text' => 'Bu metinler veya verilerinizle ilgili sorularınız mı var? Bize buradan ulaşın:',
    'contact_none' => 'Web sitemizde yer alan iletişim bilgilerini kullanın.',
    'footer' => ['privacy' => 'Gizlilik politikası', 'terms' => 'Kullanım koşulları', 'contact' => 'İletişim'],

    'privacy' => [
        'title' => 'Gizlilik politikası',
        'intro' => 'Sofra, işletmelerin dijital menü yayınlamasına ve WhatsApp üzerinden sipariş almasına yardımcı olur. Bu sayfa hangi kişisel verileri neden işlediğimizi açıklar. Hizmetin gerektirdiği asgari verilerle yetiniriz.',
        'sections' => [
            ['title' => 'Neleri topluyoruz', 'body' => [
                'İşletme sahipleri: adınız, e-posta adresiniz, şifrelenmiş şifreniz (düz metin olarak görmeyiz ve saklamayız) ve girdiğiniz işletme bilgileri: ad, logo, ürünler, fiyatlar, fotoğraflar ve WhatsApp numarası.',
                'Menüyü görüntüleyen müşteriler: hesap gerekmez ve göz atmak için kişisel veri istemeyiz. Müşteri yorum bırakırsa puan ve not kaydedilir; ad, telefon/e-posta ve fotoğraf yalnızca müşteri eklemeyi seçerse kaydedilir.',
                'Siparişler: müşteri sipariş gönderdiğinde mesaj hazırlanır ve WhatsApp’ta açılır. Sofra sipariş içeriklerini saklamaz.',
                'Analiz: Sofra yalnızca menünün, bir ürünün açılma, sepete eklenme veya WhatsApp düğmesine basılma sayısını, mağaza ve gün bazında sayar. Ziyaretçi kimliği, IP adresi veya sepet içeriği saklanmaz; "Do Not Track" gönderenler sayılmaz. Mağaza sahibi bu sayaçların saklama süresini seçer ve süre dolunca otomatik silinir.',
                'Abonelik ödemeleri: kartla öderseniz ödeme, Stripe\'ın kendi güvenli sayfasında Stripe tarafından işlenir. Sofra kart numaranızı asla görmez ve saklamaz; yalnızca Stripe müşteri ve abonelik referansını ve ödemelerinizin başarılı olup olmadığını tutarız.',
                'Teknik veriler: oturum çerezi, güvenlik jetonu ve kötüye kullanımı önlemek için (ör. istek sınırları) ziyaretçinin IP adresi.',
            ]],
            ['title' => 'Nasıl kullanıyoruz', 'body' => [
                'Hesabınızı ve menünüzü işletmek, yorumları e-posta ve panel aracılığıyla işletme sahibine iletmek, hizmeti güvende tutmak ve aboneliğiniz veya önemli değişiklikler hakkında sizinle iletişim kurmak için.',
                'Kişisel verileri satmayız ve reklam göstermeyiz.',
            ]],
            ['title' => 'Kimler görebilir', 'body' => [
                'İşletme sahibi yalnızca kendi işletmesine bırakılan yorumları görür. Ekibimiz destek sağlamak veya kötüye kullanımı önlemek için verilere erişebilir.',
                'Barındırma ve e-posta gönderimi için hizmet sağlayıcılardan yararlanırız. WhatsApp ise kendi şirketi tarafından, kendi politikasıyla işletilen ayrı bir hizmettir.',
            ]],
            ['title' => 'Çerezler', 'body' => [
                'Yalnızca zorunlu çerezleri kullanırız: oturumunuzu açık tutmak, formları korumak ve dil tercihinizi hatırlamak için. Reklam veya izleme çerezi kullanmayız.',
            ]],
            ['title' => 'Saklama ve silme', 'body' => [
                'Hesap ve işletme verilerini hesabınız aktif olduğu sürece saklarız. Hesabınızın silinmesini isterseniz işletmenizi, ürünlerinizi, görsellerinizi ve yorumlarınızı sileriz. İşletme sahipleri tek tek yorumları istedikleri zaman silebilir.',
            ]],
            ['title' => 'Haklarınız', 'body' => [
                'Geçerli mevzuat, özellikle 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) ve ilgili olduğunda GDPR kapsamında kişisel verilerinize erişmeyi, düzeltmeyi, silmeyi veya işlenmesine itiraz etmeyi talep edebilirsiniz. Aşağıdaki iletişim bilgilerinden bize ulaşın.',
            ]],
            ['title' => 'Değişiklikler', 'body' => [
                'Bu politikada önemli bir değişiklik yaparsak yukarıdaki tarihi güncelleriz ve gerektiğinde sizi bilgilendiririz.',
            ]],
        ],
    ],

    'terms' => [
        'title' => 'Kullanım koşulları',
        'intro' => 'Hesap oluşturarak veya Sofra’yı kullanarak bu koşulları kabul etmiş olursunuz. Lütfen okuyun; kısadırlar.',
        'sections' => [
            ['title' => 'Hizmet', 'body' => [
                'Sofra, bir işletmenin bağlantı ve QR kodlu dijital menü yayınlamasını ve müşterilerin WhatsApp’ta açılan bir sipariş hazırlamasını sağlar. Sofra siparişlerin tarafı değildir ve işletmeler ile müşterileri arasındaki ödemeleri yönetmez.',
            ]],
            ['title' => 'Hesabınız', 'body' => [
                'Verdiğiniz bilgilerin doğruluğundan, şifrenizin güvenliğinden ve hesabınız altındaki tüm faaliyetlerden siz sorumlusunuz. Kaydettiğiniz işletmeyi yönetme yetkiniz olmalıdır.',
            ]],
            ['title' => 'Ücretsiz deneme ve abonelik', 'body' => [
                'Yeni işletmelere ücretsiz bir deneme süresi tanınır. Süre bittikten sonra abonelik aylık :price tutarındadır ve ekibimizle anlaşarak elden ödenir. Fiyat değişikliğinden önce sizi bilgilendiririz; değişiklik önceden ödediğiniz bir döneme uygulanmaz.',
                'Deneme bittiğinde menünüz otomatik olarak kapatılmaz; ancak sizinle iletişime geçtikten sonra aboneliği ödenmeyen bir işletmeyi askıya alabiliriz.',
            ]],
            ['title' => 'İçeriğiniz', 'body' => [
                'Yüklediğiniz içerik (adlar, fotoğraflar, açıklamalar, fiyatlar) size aittir. Hizmetin işletilmesi için gerektiği ölçüde saklamamıza ve göstermemize izin verirsiniz. İçerikten siz sorumlusunuz: hukuka uygun, doğru olmalı ve kimsenin haklarını ihlal etmemelidir.',
                'Müşteri yorumlarını ve içindeki kişisel verileri nasıl ele aldığınızdan siz sorumlusunuz.',
            ]],
            ['title' => 'Kabul edilebilir kullanım', 'body' => [
                'Hizmeti kötüye kullanmayın: hukuka aykırı veya yanıltıcı içerik, güvenliği aşma, platformu aşırı yükleme veya veri çekme girişimi ve yorum formu üzerinden istenmeyen mesaj olmaz.',
            ]],
            ['title' => 'Erişilebilirlik', 'body' => [
                'Sofra’yı erişilebilir tutmaya çalışırız ancak kesintisiz hizmet vaat edemeyiz. Özellikleri istediğimiz zaman güncelleyebilir veya geliştirebiliriz.',
            ]],
            ['title' => 'Askıya alma ve sona erdirme', 'body' => [
                'Sofra’yı istediğiniz zaman bırakabilir ve hesabınızın silinmesini isteyebilirsiniz. Bu koşulları veya hukuku ihlal eden hesapları askıya alabilir veya kapatabiliriz.',
            ]],
            ['title' => 'Sorumluluk', 'body' => [
                'Kanunun izin verdiği ölçüde Sofra “olduğu gibi” sunulur; dolaylı zararlardan, kaçan satışlardan veya işletme ile müşterileri arasındaki uyuşmazlıklardan sorumlu değiliz.',
            ]],
            ['title' => 'Uygulanacak hukuk ve değişiklikler', 'body' => [
                'Bu koşullara Türkiye Cumhuriyeti hukuku uygulanır. Koşulları güncelleyebiliriz; güncellemeden sonra Sofra’yı kullanmaya devam etmeniz yeni sürümü kabul ettiğiniz anlamına gelir.',
            ]],
        ],
    ],
];
