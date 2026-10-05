<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\Recipe;
use App\Models\Topping;
use App\Models\TransactionItem;
use Illuminate\Database\Seeder;

/**
 * Menu sesuai poster:
 *  - "Daftar Menu Jelly Potter" + "Special Medium Series"  (semua outlet)
 *  - "Varian Menu Es Teh JelPot"                            (semua outlet)
 *  - "de dua Delicious Waffle", "Dedua Croffle", "Kebab / Burger / Maryam"  (hanya Grafika)
 *
 * Aman dijalankan ulang di database yang sudah berisi transaksi:
 *   php artisan db:seed --class=MenuSeeder
 * Menu/topping dicocokkan berdasarkan nama; menu lama yang tidak ada di poster dinonaktifkan (tidak dihapus).
 *
 * CATATAN: takaran resep & stok awal adalah perkiraan — sesuaikan di Admin › Menu & Resep / Stok Bahan.
 */
class MenuSeeder extends Seeder
{
    /** Jelly Potter reguler: "Mulai dari 13.000, sudah termasuk free 1 topping". */
    private const JP_PRICE = 13000;

    /** Special Medium Series: "harga spesial free topping". */
    private const MEDIUM_PRICE = 10000;

    /** Mix Yakult: "Extra +3rb". */
    private const YAKULT_PRICE = 16000;

    /** Topping minuman tambahan setelah topping gratis pertama. */
    private const DRINK_TOPPING_PRICE = 3000;

    private const ONLY_GRAFIKA = ['Grafika'];

    private const JP_PACK = [['Tutup Cup', 1], ['Sealer', 1], ['Sedotan', 1], ['Jelly', 40]];

    private const TEH_PACK = [['Cup Es Teh', 1], ['Sealer', 1], ['Sedotan', 1], ['Es Batu', 150]];

    /** Menu yang juga dijual dalam ukuran Medium (poster Special Medium Series). */
    private const MEDIUM = [
        'Grape Squash', 'Lychee Squash', 'Mango Squash', 'Melon Squash', 'Mocca Squash',
        'Orange Squash', 'Lemon Squash', 'Sirsak Squash', 'Nanas Squash', 'Strawberry Squash',
        'Matcha Flavor', 'Red Velvet Flavor', 'Kopi Vietnam', 'Taro Flavor',
        'Milo Choco', 'Coklat Lava', 'SilverQueen Choco',
    ];

    /** Mocca Squash hanya ada di poster Medium. */
    private const MEDIUM_ONLY = ['Mocca Squash'];

    public function run(): void
    {
        $toppings = $this->toppings();
        foreach ($toppings as $name => [$group, $price, $ing, $qty]) {
            $t = Topping::updateOrCreate(['name' => $name], ['group' => $group, 'price' => $price, 'is_active' => true]);
            $t->recipes()->delete();
            foreach ($ing ? [[$ing, $qty]] : [] as [$i, $q]) {
                $t->recipes()->create(['ingredient_name' => $i, 'qty' => $q]);
            }
        }
        Topping::whereNotIn('name', array_keys($toppings))->update(['is_active' => false]);

        $menus = $this->menus();
        foreach (Outlet::all() as $outlet) {
            $mine = array_filter($menus, fn ($m) => ! $m['outlets'] || in_array($outlet->name, $m['outlets']));
            foreach ($mine as $m) {
                $menu = Menu::updateOrCreate(['outlet_id' => $outlet->id, 'name' => $m['name']], [
                    'category' => $m['category'], 'base_price' => $m['price'], 'free_toppings' => $m['free'],
                    'topping_group' => $m['group'], 'is_active' => true,
                ]);
                $variants = $this->syncVariants($menu, $m['sizes']);
                $menu->recipes()->delete();
                foreach ($m['recipe'] as $row) {
                    [$ingredient, $qty] = $row;
                    $size = $row[2] ?? null; // bahan khusus ukuran tertentu (mis. cup)
                    Recipe::create([
                        'menu_id' => $menu->id,
                        'variant_id' => $size ? $variants[$size] : null,
                        'ingredient_id' => $this->ingredient($outlet, $ingredient)->id,
                        'qty' => $qty,
                    ]);
                }
            }
            // Bahan topping hanya untuk grup yang dijual di outlet ini.
            $groups = array_unique(array_column($mine, 'group'));
            foreach ($toppings as [$group, , $ing]) {
                if ($ing && in_array($group, $groups)) {
                    $this->ingredient($outlet, $ing);
                }
            }
            Menu::where('outlet_id', $outlet->id)->whereNotIn('name', array_column($mine, 'name'))->update(['is_active' => false]);
        }
    }

    /** @return array<string, array{0:string,1:int,2:?string,3:float}> nama => [grup, harga, bahan, takaran] */
    private function toppings(): array
    {
        $t = [
            'Jelly Cendol' => ['minuman', self::DRINK_TOPPING_PRICE, 'Jelly Cendol', 30],
            'Choco Chips' => ['minuman', self::DRINK_TOPPING_PRICE, 'Choco Chips', 10],
            'Boba' => ['minuman', self::DRINK_TOPPING_PRICE, 'Boba', 40],
            'Rodeo' => ['minuman', self::DRINK_TOPPING_PRICE, 'Rodeo', 10],

            'Isian Chocochips' => ['waffle', 4000, 'Choco Chips', 15],
            'Isian Keju' => ['waffle', 4000, 'Keju Parut', 15],
            'Isian Oreo' => ['waffle', 4000, 'Oreo Crumble', 15],

            'Extra Telur' => ['makanan', 3000, 'Telur', 1],
            'Extra Keju' => ['makanan', 2000, 'Keju Slice', 1],
            'Extra Sosis' => ['makanan', 2000, 'Sosis', 1],
        ];
        foreach (['Cokelat', 'Strawberry', 'Tiramisu', 'Matcha'] as $o) {
            $t["Olesan {$o}"] = ['waffle', 5000, "Olesan {$o}", 20];
        }
        foreach (['Blueberry', 'Cokelat Crunchy', 'Cheese', 'Chocomaltine', 'Strawberry Buah', 'Selai Kacang'] as $o) {
            $t["Olesan {$o}"] = ['waffle', 8000, "Olesan {$o}", 20];
        }

        return $t;
    }

    private function menus(): array
    {
        $rows = [];
        $add = function (string $name, string $category, int $price, array $recipe, array $opt = []) use (&$rows) {
            $rows[] = [
                'name' => $name, 'category' => $category, 'price' => $price, 'recipe' => $recipe,
                'free' => $opt['free'] ?? 0, 'group' => $opt['group'] ?? 'minuman',
                'sizes' => $opt['sizes'] ?? [], 'outlets' => $opt['outlets'] ?? [],
            ];
        };
        $jp = function (string $name, string $cat, array $extra, int $price = self::JP_PRICE) use ($add) {
            $medium = in_array($name, self::MEDIUM);
            $sizes = $medium ? (in_array($name, self::MEDIUM_ONLY) ? ['Medium' => self::MEDIUM_PRICE]
                : ['Reguler' => $price, 'Medium' => self::MEDIUM_PRICE]) : [];
            $cups = $sizes
                ? array_map(fn ($s) => [$s === 'Medium' ? 'Cup Medium' : 'Cup Jelly Potter', 1, $s], array_keys($sizes))
                : [['Cup Jelly Potter', 1]];
            $add($name, $cat, $sizes['Reguler'] ?? $sizes['Medium'] ?? $price, [...$cups, ...self::JP_PACK, ...$extra], ['free' => 1, 'sizes' => $sizes]);
        };
        $teh = fn (string $name, string $cat, int $price, array $extra) => $add($name, $cat, $price, [...self::TEH_PACK, ...$extra]);

        // ================= JELLY POTTER =================
        $choco = [
            'Coklat Lava' => [['Powder Coklat', 30]],
            'Milo Choco' => [['Bubuk Milo', 30]],
            'Magnum Choco' => [['Powder Coklat', 20], ['Coklat Magnum', 15]],
            'Hazelnut Choco' => [['Powder Coklat', 25], ['Sirup Hazelnut', 15]],
            'SilverQueen Choco' => [['Powder Coklat', 20], ['Coklat SilverQueen', 15]],
            'Black Choco Oreo' => [['Powder Black Choco', 25], ['Oreo Crumble', 15]],
            'Nutella Choco' => [['Powder Coklat', 20], ['Selai Nutella', 15]],
            'Belgian Choco' => [['Powder Belgian Choco', 30]],
        ];
        foreach ($choco as $n => $r) {
            $jp($n, 'Chocolate Series', [...$r, ['Susu Cair', 120]]);
        }

        foreach (['Strawberry', 'Lychee', 'Nanas', 'Lemon', 'Melon', 'Orange', 'Grape', 'Sirsak', 'Mango'] as $f) {
            $jp("{$f} Squash", 'Fruit Squash', [['Sirup '.$f, 30], ['Air Soda', 150]]);
        }
        $jp('Mocca Squash', 'Fruit Squash', [['Bubuk Kopi', 10], ['Powder Coklat', 10], ['Air Soda', 150]]);

        $coffee = [
            'Cappucino Latte' => [['Bubuk Kopi', 15], ['Susu Cair', 120]],
            'Kopi Susu Gula Aren' => [['Bubuk Kopi', 15], ['Susu Cair', 120], ['Gula Aren Cair', 25]],
            'Kopi Vietnam' => [['Bubuk Kopi', 18], ['Susu Kental Manis', 30]],
            'Kopi Espresso' => [['Bubuk Kopi', 20]],
            'Kopi Mocca' => [['Bubuk Kopi', 15], ['Powder Coklat', 15], ['Susu Cair', 100]],
        ];
        foreach ($coffee as $n => $r) {
            $jp($n, 'Special Coffee', $r);
        }

        $mix = [
            'Brown Sugar' => [['Gula Aren Cair', 30], ['Susu Cair', 150]],
            'Choco Lava Avocado' => [['Powder Avocado', 25], ['Powder Coklat', 15], ['Susu Cair', 120]],
            'Cookies & Cream' => [['Powder Cookies & Cream', 30], ['Oreo Crumble', 10], ['Susu Cair', 120]],
            'Taro Flavor' => [['Powder Taro', 30], ['Susu Cair', 120]],
            'Original Avocado' => [['Powder Avocado', 30], ['Susu Cair', 120]],
            'Red Velvet Flavor' => [['Powder Red Velvet', 30], ['Susu Cair', 120]],
            'Matcha Flavor' => [['Powder Matcha', 25], ['Susu Cair', 120]],
        ];
        foreach ($mix as $n => $r) {
            $jp($n, 'Special Mix Series', $r);
        }

        foreach (['Grape', 'Lemon', 'Mango', 'Lychee', 'Melon', 'Nanas', 'Sirsak', 'Strawberry', 'Orange'] as $f) {
            $jp("{$f} BlueOcean Mix", 'BlueOcean Mix Series', [['Sirup Blue Ocean', 20], ['Sirup '.$f, 20], ['Air Soda', 130]]);
        }

        foreach (['Nanas', 'Lychee', 'Melon', 'Lemon', 'Mango', 'Sirsak', 'Strawberry', 'Orange', 'Grape'] as $f) {
            $jp("{$f} Mix Yakult", 'Mix Yakult', [['Yakult', 1], ['Sirup '.$f, 30]], self::YAKULT_PRICE);
        }

        // ================= ES TEH JELPOT =================
        $tea = [
            'Jasmine Tea' => [['Teh Melati Seduh', 250]],
            'Apple Tea' => [['Teh Seduh', 230], ['Sirup Apple', 20]],
            'Mango Tea' => [['Teh Seduh', 230], ['Sirup Mango', 20]],
            'Black Tea Gula Batu' => [['Teh Hitam Seduh', 230], ['Gula Batu', 20]],
            'Lychee Tea' => [['Teh Seduh', 230], ['Sirup Lychee', 20]],
            'Lemon Honey Tea' => [['Teh Seduh', 220], ['Sirup Lemon', 15], ['Madu', 15]],
            'Blackcurrant Tea' => [['Teh Seduh', 230], ['Sirup Blackcurrant', 20]],
            'Vanilla Tea' => [['Teh Seduh', 230], ['Sirup Vanilla', 20]],
            'Peach Tea' => [['Teh Seduh', 230], ['Sirup Peach', 20]],
            'Lemon Tea' => [['Teh Seduh', 230], ['Sirup Lemon', 20]],
        ];
        foreach ($tea as $n => $r) {
            $teh($n, 'Es Teh · Teh Series', 5000, $r);
        }

        $fresh = [
            'Pink Berry' => 'Sirup Pink Berry', 'Green Apple' => 'Sirup Green Apple', 'King Mango' => 'Sirup Mango',
            'Jambu Ceria' => 'Sirup Jambu', 'BlueOcean Lemonade' => 'Sirup Blue Ocean', 'Cerise Lemonade' => 'Sirup Cerise',
            'Yuzu' => 'Sirup Yuzu', 'Galaxy Lemonade' => 'Sirup Galaxy', 'Sakura Lemonade' => 'Sirup Sakura',
        ];
        foreach ($fresh as $n => $syrup) {
            $extra = str_contains($n, 'Lemonade') ? [['Sirup Lemon', 15]] : [];
            $teh($n, 'Es Teh · Fresh Series', 6000, [[$syrup, 30], ...$extra, ['Air Mineral', 200]]);
        }

        $classic = [
            'Black Coffee' => [['Bubuk Kopi', 15], ['Air Mineral', 200]],
            'Milk Tea' => [['Teh Hitam Seduh', 180], ['Susu Kental Manis', 30]],
            'Cokelat Klasik' => [['Powder Coklat', 30], ['Susu Cair', 150]],
            'Cokelat Durian' => [['Powder Coklat', 20], ['Powder Durian', 15], ['Susu Cair', 150]],
            'Durian Montong' => [['Powder Durian', 30], ['Susu Cair', 150]],
            'Klepon Mania' => [['Powder Klepon', 30], ['Susu Cair', 150]],
        ];
        foreach ($classic as $n => $r) {
            $teh($n, 'Es Teh · Klasik Series', 8000, $r);
        }

        // ================= WAFFLE (de dua) — Grafika =================
        $waffleOpt = ['group' => 'waffle', 'outlets' => self::ONLY_GRAFIKA];
        foreach (['Vanila', 'Black Forest', 'Pandan', 'Red Velvet', 'Blueberry', 'Matcha', 'Strawberry', 'Taro'] as $f) {
            $add("Waffle {$f}", 'Waffle', 17000, [['Adonan Waffle', 120], ["Pasta {$f}", 5], ['Kertas Waffle', 1]], $waffleOpt);
        }

        // ================= CROFFLE (Dedua Croffle) — Grafika =================
        $croffleOpt = ['group' => 'croffle', 'outlets' => self::ONLY_GRAFIKA];
        $croffle = [
            'Original Croffle' => [8000, []],
            'Choco Croffle' => [11000, [['Olesan Cokelat', 20]]],
            'Matcha Croffle' => [11000, [['Olesan Matcha', 20]]],
            'Blueberry Croffle' => [11000, [['Olesan Blueberry', 20]]],
            'Strawberry Croffle' => [11000, [['Olesan Strawberry', 20]]],
            'Crunchy Choco Croffle' => [11000, [['Olesan Cokelat', 20], ['Kacang Cincang', 10]]],
        ];
        foreach ($croffle as $n => [$price, $extra]) {
            $add($n, 'Croffle', $price, [['Adonan Croffle', 1], ['Kertas Bungkus', 1], ...$extra], $croffleOpt);
        }

        // ================= KEBAB / BURGER / MARYAM — Grafika =================
        $foodOpt = ['group' => 'makanan', 'outlets' => self::ONLY_GRAFIKA];
        $kebab = [
            'Beef Kebab Kecil' => 12000, 'Beef Kebab Kecil Extra Telur' => 14000, 'Beef Kebab Kecil Extra Sosis' => 13000,
            'Beef Kebab Kecil Extra Keju' => 13000, 'Beef Kebab Sedang' => 14000, 'Beef Kebab Sedang Extra Telur' => 17000,
            'Beef Kebab Sedang Extra Sosis' => 16000, 'Beef Kebab Sedang Extra Keju' => 16000, 'Beef Kebab Sedang Komplit' => 18000,
            'Beef Kebab Sedang Special' => 19000, 'Beef Kebab Besar' => 16000, 'Beef Kebab Besar Extra Telur' => 19000,
            'Beef Kebab Besar Extra Sosis' => 18000, 'Beef Kebab Besar Extra Keju' => 18000, 'Kebab Besar Patties' => 17000,
            'Beef Kebab Besar Extra Patties' => 22000, 'Beef Kebab Besar Komplit' => 21000, 'Beef Kebab Besar Special' => 24000,
        ];
        foreach ($kebab as $n => $price) {
            $add($n, 'Kebab', $price, $this->kebabRecipe($n), $foodOpt);
        }

        $burger = [
            'Burger Double Patties' => 18000, 'Burger Double Patties Extra Telur' => 21000, 'Burger Double Patties Extra Keju' => 20000,
            'Burger Double Patties Extra Telur & Keju' => 23000, 'Burger Single Patties' => 14000, 'Burger Single Patties Extra Telur' => 17000,
            'Burger Single Patties Extra Keju' => 16000, 'Burger Single Patties Extra Telur & Keju' => 19000,
            'Burger Telur Mata Sapi' => 13000, 'Burger Telur Orak Arik' => 13000,
        ];
        foreach ($burger as $n => $price) {
            $add($n, 'Burger', $price, $this->burgerRecipe($n), $foodOpt);
        }

        $maryam = [
            'Maryam Original' => 8000, 'Maryam Coklat Meses' => 10000, 'Maryam Coklat Meses Susu' => 11000,
            'Maryam Coklat Meses Keju Susu' => 13000, 'Maryam Chocochip' => 10000, 'Maryam Chocochip Susu' => 11000,
            'Maryam Chocochip Keju Susu' => 13000, 'Maryam Coklat Glaze' => 10000, 'Maryam Coklat Glaze Susu' => 11000,
            'Maryam Coklat Glaze Keju Susu' => 13000, 'Maryam Coklat Crunchy' => 11000, 'Maryam Coklat Crunchy Susu' => 12000,
            'Maryam Coklat Crunchy Keju Susu' => 14000, 'Maryam Susu' => 11000, 'Maryam Keju' => 12000, 'Maryam Keju Susu' => 13000,
            'Maryam Selai' => 10000, 'Maryam Selai + Susu' => 11000, 'Maryam Selai + Keju' => 13000, 'Maryam Selai + Keju + Susu' => 14000,
            'Maryam Matcha' => 10000, 'Maryam Gula Halus' => 10000, 'Maryam Daging Sapi' => 11000, 'Maryam Daging Sapi + Keju' => 13000,
            'Maryam Mayonaise' => 10000, 'Maryam Salad (Selada & Mayonaise)' => 11000, 'Maryam Sosis (Potong Memanjang)' => 13000,
            'Maryam Special (Coklat, Keju, Selai, Chocochip)' => 15000,
        ];
        foreach ($maryam as $n => $price) {
            $add($n, 'Maryam', $price, $this->maryamRecipe($n), $foodOpt);
        }

        return $rows;
    }

    private function kebabRecipe(string $n): array
    {
        $beef = str_contains($n, 'Kecil') ? 40 : (str_contains($n, 'Sedang') ? 60 : 80);
        $r = [['Kulit Tortilla', 1], ['Sayur & Saus', 30], ['Kertas Bungkus', 1]];
        if (! str_contains($n, 'Kebab Besar Patties')) {
            $r[] = ['Daging Kebab', $beef + (str_contains($n, 'Special') ? 20 : 0)];
        }
        $full = str_contains($n, 'Komplit') || str_contains($n, 'Special');
        if ($full || str_contains($n, 'Telur')) {
            $r[] = ['Telur', 1];
        }
        if ($full || str_contains($n, 'Sosis')) {
            $r[] = ['Sosis', 1];
        }
        if ($full || str_contains($n, 'Keju')) {
            $r[] = ['Keju Slice', 1];
        }
        if (str_contains($n, 'Patties')) {
            $r[] = ['Patties', str_contains($n, 'Extra Patties') ? 2 : 1];
        }

        return $r;
    }

    private function burgerRecipe(string $n): array
    {
        $r = [['Roti Burger', 1], ['Sayur & Saus', 20], ['Kertas Bungkus', 1]];
        if (str_contains($n, 'Patties')) {
            $r[] = ['Patties', str_contains($n, 'Double') ? 2 : 1];
        }
        if (str_contains($n, 'Telur')) {
            $r[] = ['Telur', 1];
        }
        if (str_contains($n, 'Keju')) {
            $r[] = ['Keju Slice', 1];
        }

        return $r;
    }

    private function maryamRecipe(string $n): array
    {
        $map = [
            'Meses' => ['Meses', 15], 'Chocochip' => ['Choco Chips', 10], 'Glaze' => ['Coklat Glaze', 15],
            'Crunchy' => ['Coklat Crunchy', 15], 'Susu' => ['Susu Kental Manis', 15], 'Keju' => ['Keju Parut', 15],
            'Selai' => ['Selai', 15], 'Matcha' => ['Powder Matcha', 10], 'Gula Halus' => ['Gula Halus', 10],
            'Daging Sapi' => ['Daging Sapi', 30], 'Mayonaise' => ['Mayonaise', 15], 'Selada' => ['Selada', 10], 'Sosis' => ['Sosis', 1],
        ];
        if (str_contains($n, 'Special')) {
            $n = 'Meses Keju Selai Chocochip';
        }
        $r = [['Roti Maryam', 1], ['Kertas Bungkus', 1]];
        foreach ($map as $key => $row) {
            if (str_contains($n, $key)) {
                $r[] = $row;
            }
        }

        return $r;
    }

    /**
     * Samakan ukuran menu dengan poster. Ukuran yang sudah dipakai di transaksi tidak dihapus agar riwayat tetap utuh.
     *
     * @return array<string, string> ukuran => variant_id
     */
    private function syncVariants(Menu $menu, array $sizes): array
    {
        $ids = [];
        foreach ($sizes as $size => $price) {
            $ids[$size] = $menu->variants()->updateOrCreate(['size' => $size], ['price' => $price])->id;
        }
        $stale = $menu->variants()->whereNotIn('id', array_values($ids))->pluck('id');
        $used = TransactionItem::whereIn('variant_id', $stale)->pluck('variant_id')->unique();
        $menu->variants()->whereIn('id', $stale->diff($used))->delete();

        return $ids;
    }

    private function ingredient(Outlet $outlet, string $name): Ingredient
    {
        $unit = match (true) {
            (bool) preg_match('/^(Cup|Tutup|Sealer|Sedotan|Yakult|Kertas|Adonan Croffle|Kulit Tortilla|Roti|Telur|Sosis|Keju Slice|Patties)/', $name) => 'pcs',
            (bool) preg_match('/^(Susu|Sirup|Teh|Air|Gula Aren|Madu)/', $name) => 'ml',
            default => 'gram',
        };
        [$stock, $min] = match (true) {
            in_array($name, ['Yakult', 'Adonan Croffle', 'Telur', 'Sosis', 'Keju Slice', 'Patties', 'Roti Burger', 'Roti Maryam', 'Kulit Tortilla']) => [60, 12],
            $unit === 'pcs' => [500, 80],
            $unit === 'ml' => [5000, 800],
            default => [2000, 300],
        };

        return Ingredient::firstOrCreate(
            ['outlet_id' => $outlet->id, 'name' => $name],
            ['unit' => $unit, 'stock_current' => $stock, 'stock_min' => $min],
        );
    }
}
