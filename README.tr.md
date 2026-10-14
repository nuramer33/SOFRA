# Sofra

[English](README.md) · [العربية](README.ar.md) · **Türkçe**

Sofra; kafeler, restoranlar, parfüm ve aksesuar mağazaları ve Instagram işletmeleri gibi küçük işletmeler için dijital menü ve WhatsApp sipariş platformudur.

İşletme sahibi kayıt olur, kategorilerini ve ürünlerini fotoğraf ve fiyatlarla ekler ve kendi bağlantısını ve QR kodunu alır. Müşteri menüyü telefonundan açar, siparişini oluşturur ve WhatsApp ile gönderir. **Sofra müşteri siparişlerini saklamaz**: sipariş, müşteri ile işletme arasındaki sohbette kalır.

- Üç dil: Arapça (sağdan sola, varsayılan), Türkçe ve İngilizce. Menü müşterinin telefon dilini izler.
- Çok kiracılı: her işletmenin verisi veritabanı sorgusu düzeyinde ayrıdır.
- Abonelik: 60 gün ücretsiz, sonra aylık plan (elle veya Stripe ile otomatik ödeme).

## Özellikler

**Müşteriler için**
- Menüye göz atma, arama, ürün ayrıntıları ve kendi tarayıcısında saklanan sepet.
- Mekânda (isteğe bağlı masa numarasıyla), gel-al veya teslimat seçimi.
- Hazır bir WhatsApp mesajıyla sipariş gönderme. Fiyatlar her zaman veritabanından alınır, tarayıcıdan asla.
- "Yanına çok yakışır" önerileri ve teklifler. Sepete otomatik hiçbir şey eklenmez.
- İşletme sahibine özel puanlamalar.

**İşletme sahipleri için**
- Kategoriler, ürünler, fotoğraflar (WebP olarak sıkıştırılır), fiyatlar ve indirimli fiyatlar.
- Telefondan hızlı değişiklikler için **Bugün** sayfası: yeni siparişleri durdurma, "bugün tükendi", fiyat, ad, açıklama veya fotoğraf düzenleme, teklifleri açıp kapatma.
- Saat dilimiyle sipariş saatleri; gece yarısını aşan vardiyalar dahil.
- İşletme sahibinin belirlediği öneriler ve indirimler; indirimleri sunucu hesaplar.
- Gizliliğe saygılı analiz: menü görüntüleme, ürün görüntüleme, sepete ekleme ve WhatsApp düğmesi tıklamaları. Yalnızca sayaçlar, kişisel veri yok. Bir WhatsApp tıklaması asla onaylanmış sipariş olarak gösterilmez.
- İşletme başına tek QR kod (marka rengi, baskıya hazır PNG/SVG ve A6 masa kartı). `?table=5` masa numarasını önceden doldurur; masa başına ayrı QR yoktur.
- İki menü görünümü: **Klasik** (kafe, restoran) ve **Lüks** (parfüm, aksesuar, hediyelik).
- Teslimat ücreti ve minimum sipariş, adres, çalışma saatleri, harita ve Instagram bağlantıları.

**Platform yöneticisi için**
- Arama ve filtrelerle işletme listesi, CSV dışa aktarma.
- Ödeme kaydı, ücretsiz gün verme, askıya alma ve yeniden etkinleştirme; işlem geçmişiyle.
- Abonelik durumları: deneme, yakında bitiyor, aktif, gecikmiş, süresi dolmuş, elle askıya alınmış. Süre dolduktan sonra herkese açık menünün ne yapacağı ayarlanabilir.

## Teknoloji

Laravel 13 · PHP 8.3 · MySQL · Blade · Tailwind CSS v4 · Alpine.js · Vite

## Hızlı başlangıç

Gereksinimler: PHP 8.3 (GD veya Imagick eklentisiyle), Composer, Node.js, MySQL.

```bash
git clone <bu deponun adresi> sofra
cd sofra
composer install
cp .env.example .env
php artisan key:generate
```

Bir MySQL veritabanı oluşturun, `.env` içindeki `DB_*` değerlerini ayarlayın, sonra:

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

http://localhost:8000 adresini açın.

Yerel demo hesapları (seeder oluşturur, yalnızca geliştirme için):

| Rol | E-posta | Şifre |
| --- | --- | --- |
| İşletme sahibi | `demo@sofra.test` | `password` |
| Yönetici | `admin@sofra.test` | `password` |

Herkese açık demo menü `/demo` adresindedir. Gerçek bir sunucuda yöneticiyi `php artisan sofra:admin you@example.com` ile oluşturun ve demo hesaplarını asla kullanmayın.

## Yapılandırma

Ürün ayarları `config/sofra.php` içindedir; gizli bilgiler ve ortama özel değerler `.env` içine yazılır (bkz. `.env.example`).

| Değişken | Amaç |
| --- | --- |
| `SOFRA_MONTHLY_PRICE` | İşletme sahibine gösterilen fiyat metni, örneğin `$12` |
| `SOFRA_SUPPORT_WHATSAPP`, `SOFRA_SUPPORT_EMAIL` | İşletme sahiplerinin yenilemek için size ulaştığı yer |
| `SOFRA_EXPIRED_MENU` | Süre dolunca herkese açık menü: `visible`, `read_only` (varsayılan) veya `suspended` |
| `STRIPE_SECRET`, `STRIPE_PRICE_ID`, `STRIPE_WEBHOOK_SECRET` | Stripe ile otomatik aylık ödeme. Ödemeleri elle tutmak için boş bırakın |
| `MAIL_*` | Puanlamalar ve abonelik hatırlatmaları için SMTP. Test: `php artisan sofra:mail-test you@example.com` |

### Stripe ile otomatik ödeme (isteğe bağlı)

1. Stripe'ta yinelenen aylık bir Fiyat oluşturun ve kimliğini (`price_...`) kopyalayın.
2. Müşteri portalını (kart güncelleme ve iptal) etkinleştirin.
3. `https://your-domain/stripe/webhook` adresine şu olaylar için bir webhook ekleyin: `checkout.session.completed`, `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated`, `customer.subscription.deleted`.
4. Anahtarları `.env` dosyasına yazın.

İşletme sahipleri kartlarını Stripe'ın kendi sayfasında girer; Sofra kartı asla görmez ve saklamaz. Ödenmiş süre yalnızca imzası doğrulanmış webhook ile verilir.

## Zamanlanmış görevler

Sunucuya tek bir cron satırı ekleyin:

```
* * * * * cd /path/to/sofra && php artisan schedule:run >> /dev/null 2>&1
```

Günlük yedeği (`sofra:backup`), abonelik hatırlatmalarını (`sofra:subscription-reminders`) ve analiz temizliğini (`sofra:prune-analytics`) çalıştırır.

## Testler

```bash
php artisan test --compact
vendor/bin/pint --format agent
```

## Gizlilik ve güvenlik notları

- Sunucu müşteri siparişlerini, adlarını, telefon numaralarını veya adreslerini saklamaz. Analiz yalnızca günlük sayaçları tutar; saklama süresini her işletme seçer.
- Kiracı yalıtımı, fiyat ve indirimlerin sunucuda hesaplanması, herkese açık uç noktalarda hız sınırı, doğrulanmış yüklemeler ve imzalı Stripe webhook'u.
- Gizlilik ve kullanım koşulları sayfaları taslaktır; yayından önce bir avukat tarafından gözden geçirilmelidir.

## Katkı

Sorunlar ve pull request'ler memnuniyetle karşılanır. Pull request açmadan önce testleri ve Pint'i çalıştırın.
