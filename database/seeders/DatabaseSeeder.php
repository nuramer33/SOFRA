<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use App\Support\Color;
use App\Support\ImageStore;
use App\Tenancy\CurrentShop;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

class DatabaseSeeder extends Seeder
{
    /**
     * A realistic demo café in three languages, for local development.
     * Log in with demo@sofra.test / password
     */
    public function run(): void
    {
        $shop = Shop::create([
            'name' => ['ar' => 'بيت القهوة', 'tr' => 'Kahve Evi', 'en' => 'The Coffee House'],
            'tagline' => [
                'ar' => 'قهوة مختصة وحلويات شرقية منذ ٢٠١٢',
                'tr' => '2012\'den beri nitelikli kahve ve şark tatlıları',
                'en' => 'Specialty coffee & eastern sweets since 2012',
            ],
            'slug' => 'demo',
            'address' => ['ar' => 'شارع الاستقلال 12، بيوغلو، إسطنبول', 'tr' => 'İstiklal Caddesi No:12, Beyoğlu, İstanbul', 'en' => '12 Istiklal Street, Beyoglu, Istanbul'],
            'hours' => ['ar' => 'يومياً من 8 صباحاً حتى 11 مساءً', 'tr' => 'Her gün 08:00 – 23:00', 'en' => 'Daily 8:00 – 23:00'],
            'maps_url' => 'https://maps.google.com/?q=Istiklal+Caddesi+Istanbul',
            'instagram' => 'kahveevi',
            'delivery_fee' => 25,
            'min_order' => 150,
            'brand_color' => '#A8462A',
            'whatsapp_number' => '905550000000',
            'currency' => 'TRY',
            'default_locale' => 'ar',
            'enabled_locales' => ['ar', 'tr', 'en'],
        ]);
        $shop->startTrial();
        $shop->save();

        $owner = new User(['name' => 'سارة الأحمد', 'email' => 'demo@sofra.test', 'password' => 'password', 'locale' => 'ar']);
        $owner->shop()->associate($shop);
        $owner->email_verified_at = now();
        $owner->save();

        // Local platform admin (no shop): admin@sofra.test / password → /admin
        // On a real server use `php artisan sofra:admin you@example.com` instead.
        $admin = new User(['name' => 'Sofra Admin', 'email' => 'admin@sofra.test', 'password' => 'password', 'locale' => 'ar']);
        $admin->is_admin = true;
        $admin->email_verified_at = now();
        $admin->save();

        // A few more shops so the admin panel has something to show.
        $this->extraShops();

        // Act "as" this shop, exactly like a dashboard request would:
        // BelongsToShop fills in shop_id for us.
        app(CurrentShop::class)->set($shop);

        $images = app(ImageStore::class);
        $palette = $this->foodColors();

        foreach ($this->menu() as $sort => [$categoryName, $products]) {
            $category = $shop->categories()->create(['name' => $categoryName, 'sort_order' => $sort]);

            foreach ($products as $i => [$name, $description, $price]) {
                $category->products()->create([
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    // One "offer" so the strike-through price shows up in the demo.
                    'compare_at_price' => $name['en'] === 'Pistachio baklava' ? 150 : null,
                    'sort_order' => $i,
                    'is_featured' => $i === 0,
                    'image_path' => $images->store($this->illustration(array_shift($palette)), $shop, 'product'),
                ]);
            }
        }

        app(CurrentShop::class)->forget();
    }

    private function extraShops(): void
    {
        $shops = [
            ['Lavanta Parfüm', 'lavanta-parfum', '#6b3a5e', 'tr', 'ayse@lavanta.test', 'Ayşe Demir', '905321234567'],
            ['حلويات الشام', 'halawiyat-alsham', '#b7791f', 'ar', 'khaled@sham.test', 'خالد العلي', '963944123456'],
            ['Olive & Thyme', 'olive-thyme', '#56653a', 'en', 'omar@olive.test', 'Omar Haddad', null],
        ];

        foreach ($shops as [$name, $slug, $color, $locale, $email, $ownerName, $whatsapp]) {
            $shop = Shop::create([
                'name' => [$locale => $name],
                'slug' => $slug,
                'brand_color' => $color,
                'whatsapp_number' => $whatsapp,
                'template' => $slug === 'lavanta-parfum' ? 'luxe' : 'classic', // the perfume shop shows the Luxe look
                'default_locale' => $locale,
                'enabled_locales' => [$locale],
            ]);
            $shop->startTrial();
            $shop->save();
            $owner = new User(['name' => $ownerName, 'email' => $email, 'password' => 'password', 'locale' => $locale]);
            $owner->shop()->associate($shop);
            $owner->save();
        }
    }

    /**
     * A simple "plate seen from above" picture, so the demo menu has images
     * to animate. Real shops upload real photos.
     *
     * @param  array{0: string, 1: string}  $food  [outer, inner] hex colors
     */
    private function illustration(array $food): UploadedFile
    {
        $size = 1000;
        $canvas = imagecreatetruecolor($size, $size);
        $color = fn (string $hex) => imagecolorallocate($canvas, ...Color::rgb($hex));

        imagefill($canvas, 0, 0, $color('#eadfcd'));
        imagefilledellipse($canvas, 515, 530, 860, 860, $color('#d9cbb4'));   // soft shadow
        imagefilledellipse($canvas, 500, 500, 840, 840, $color('#f8f2e8'));   // plate
        imagefilledellipse($canvas, 500, 500, 700, 700, $color('#efe6d7'));   // plate well

        // Food: concentric circles fading from the outer to the inner color.
        for ($step = 0; $step <= 40; $step++) {
            $diameter = (int) (560 - $step * 11);
            imagefilledellipse($canvas, 500, 500, $diameter, $diameter, $color(Color::mix($food[0], $food[1], $step / 40)));
        }

        $path = tempnam(sys_get_temp_dir(), 'dish').'.png';
        imagepng($canvas, $path);
        imagedestroy($canvas);

        return new UploadedFile($path, 'dish.png', 'image/png', null, true);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function foodColors(): array
    {
        return [
            ['#3b2418', '#9a6a48'], // turkish coffee
            ['#d9a85b', '#fbe6bd'], // saffron latte
            ['#7a3b16', '#c98a3c'], // mint tea
            ['#c8641e', '#f4b555'], // knafeh
            ['#8a9a3c', '#d9c27a'], // baklava
            ['#5c6b2e', '#b9a35a'], // za'atar manakish
            ['#a8321e', '#f08a4b'], // shakshuka
        ];
    }

    private function menu(): array
    {
        return [
            [
                ['ar' => 'مشروبات ساخنة', 'tr' => 'Sıcak içecekler', 'en' => 'Hot drinks'],
                [
                    [['ar' => 'قهوة تركية', 'tr' => 'Türk kahvesi', 'en' => 'Turkish coffee'],
                        ['ar' => 'مطحونة طازجة، بالهيل، تُحضّر على الرمل.', 'tr' => 'Taze çekilmiş, kakuleli, kumda pişirilir.', 'en' => 'Freshly ground with cardamom, brewed on hot sand.'], 85],
                    [['ar' => 'لاتيه بالزعفران', 'tr' => 'Safranlı latte', 'en' => 'Saffron latte'],
                        ['ar' => 'إسبريسو مزدوج، حليب طازج، وخيوط زعفران.', 'tr' => 'Çift espresso, taze süt ve safran.', 'en' => 'Double espresso, fresh milk and saffron threads.'], 110],
                    [['ar' => 'شاي بالنعناع', 'tr' => 'Naneli çay', 'en' => 'Mint tea'],
                        ['ar' => 'شاي أسود مع نعناع طازج.', 'tr' => 'Taze naneli siyah çay.', 'en' => 'Black tea with fresh mint.'], 45],
                ],
            ],
            [
                ['ar' => 'حلويات', 'tr' => 'Tatlılar', 'en' => 'Sweets'],
                [
                    [['ar' => 'كنافة نابلسية', 'tr' => 'Künefe', 'en' => 'Knafeh'],
                        ['ar' => 'جبنة نابلسية وقطر دافئ وفستق حلبي.', 'tr' => 'Sıcak şerbet, peynir ve Antep fıstığı.', 'en' => 'Soft cheese, warm syrup and pistachio.'], 140],
                    [['ar' => 'بقلاوة بالفستق', 'tr' => 'Fıstıklı baklava', 'en' => 'Pistachio baklava'],
                        ['ar' => 'أربع قطع، رقائق مقرمشة وفستق.', 'tr' => 'Dört dilim, çıtır yufka ve fıstık.', 'en' => 'Four pieces, crisp layers and pistachio.'], 120],
                ],
            ],
            [
                ['ar' => 'فطور', 'tr' => 'Kahvaltı', 'en' => 'Breakfast'],
                [
                    [['ar' => 'منقوشة زعتر', 'tr' => 'Zahterli manakiş', 'en' => 'Za\'atar manakish'],
                        ['ar' => 'زعتر بلدي وزيت زيتون على عجينة طازجة.', 'tr' => 'Taze hamur üzerinde zahter ve zeytinyağı.', 'en' => 'Za\'atar and olive oil on fresh dough.'], 75],
                    [['ar' => 'شكشوكة', 'tr' => 'Menemen', 'en' => 'Shakshuka'],
                        ['ar' => 'بيض مع طماطم وفلفل، تُقدّم مع الخبز.', 'tr' => 'Domates ve biberli yumurta, ekmekle.', 'en' => 'Eggs in tomato and pepper, served with bread.'], 130],
                ],
            ],
        ];
    }
}
