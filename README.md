# Jelly Potter Smart Cashier

Aplikasi kasir dan kontrol outlet untuk Jelly Potter (Tembalang & Grafika), dibangun sesuai [PRD.md](PRD.md).
Stack: **Laravel 11 + Filament 3 (panel owner) + Livewire/Tailwind (layar kasir) + MySQL**.

## Deploy ke VPS

Pakai Docker — langkah lengkap di [DEPLOY.md](DEPLOY.md).

## Menjalankan (Laragon)

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate   # DB: jelly_potter_kasir (MySQL)
php artisan migrate --seed
php artisan serve
```

- Layar kasir: `/kasir`: pilih outlet → nama → PIN
- Panel owner/manager: `/admin`

### Akun percobaan (dari seeder, **ganti sebelum dipakai sungguhan**)

| Akun | Login | PIN |
|---|---|---|
| Owner | owner@jellypotter.test / `password` | 1234 |
| Manager | manager@jellypotter.test / `password` | 5678 |
| Eka, Elsya (Tembalang) | kasir | 1111, 2222 |
| Mia, Pasya (Grafika) | kasir | 3333, 4444 |

## Fitur per fase PRD

| Fase | Fitur | Lokasi |
|---|---|---|
| 1 | Kasir: menu, ukuran, topping, promo otomatis, tunai/QRIS/debit/digital, struk cetak & digital (link bertanda tangan) | `/kasir` · `app/Livewire/Pos.php` · `app/Services/PosService.php` |
| 1 | Kunci harga / diskon manual / void / refund dengan PIN owner/manager, tercatat atas nama pegawai | modal 🔒 di kasir · `audit_logs` |
| 1 | Buka shift + modal awal (wajib), pengeluaran, tutup shift: cash seharusnya vs fisik → selisih | tab *Kas & Shift* · `ShiftService` |
| 2 | Potong stok otomatis per resep (menu, ukuran, topping), stok kritis, cek fisik vs sistem, riwayat | tab *Stok* · Admin › Stok |
| 2 | Resep standar per menu/ukuran, deteksi waste | Admin › Menu & Resep · Laporan |
| 3 | Dashboard 2 outlet, produk terlaris, stok kritis, selisih kas, unduh CSV | `/admin` |
| 3 | Akun & PIN pegawai, performa & selisih per pegawai, riwayat void/refund/diskon | Admin › Pegawai · Laporan |
| 3 | Laporan harian ke HP owner (format PRD), otomatis 22:00 & tiap tutup shift | Admin › Laporan Harian · `php artisan report:daily` |
| 4 | Outlet, hak ubah harga, struk & identitas toko | Admin › Pengaturan |
| 5 | Kanal pesanan (offline/GoFood/GrabFood/ShopeeFood, input manual), rekap omzet per kanal, prediksi stok | dropdown kanal di kasir · Laporan |

### Notifikasi otomatis
Jalankan scheduler (`php artisan schedule:work`, atau cron `* * * * * php artisan schedule:run`).
Untuk kirim ke WhatsApp otomatis, isi *URL webhook* di Pengaturan (mis. `https://api.fonnte.com/send`) dan `OWNER_NOTIFY_TOKEN` di `.env`.
Tanpa webhook, ringkasan tetap dicatat di `storage/logs` dan bisa dikirim manual lewat tombol WhatsApp.

## Tes

```bash
php artisan test   # memakai database MySQL jelly_potter_kasir_test
```
