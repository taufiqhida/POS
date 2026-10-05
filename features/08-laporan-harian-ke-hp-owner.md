# Laporan Harian ke HP Owner

Owner menerima ringkasan harian penjualan dan kas langsung di HP tanpa membuka banyak laporan.

## Spesifikasi

### Tujuan
Memberi owner ringkasan harian penjualan dan kas langsung di HP-nya, sehingga ia tahu kondisi Tembalang & Grafika tanpa membuka banyak laporan.
### Selesai bila
- Owner menerima satu ringkasan harian yang memuat penjualan tiap outlet, total hari ini, produk terlaris, stok kritis, dan selisih kas dalam satu pesan siap baca.
- Ringkasan muncul otomatis di HP owner tanpa ia membuka aplikasi laporan satu per satu.
- Owner bisa melihat angka tiap outlet terpisah maupun digabung dalam ringkasan yang sama.
- Angka rupiah ditampilkan rapi (mis. "Rp1.250.000") dan selisih kas ditandai jelas (mis. "-Rp25.000").

## Sub-fitur: Ringkasan Harian Otomatis

Owner menerima rekap harian siap baca langsung di HP.

### Tujuan
Menyusun rekap harian penjualan dan kas secara otomatis agar owner langsung membaca kondisinya di HP.
### Selesai bila
- Rekap harian terbentuk sendiri dari data penjualan hari itu tanpa perlu disusun manual.
- Rekap mencakup penjualan tiap outlet dan total keseluruhan hari itu.
- Rekap bisa dibaca langsung di HP owner.

## Sub-fitur: Format Singkat

Ringkasan disusun ringkas agar mudah dibaca, berisi penjualan tiap outlet, total, produk terlaris, stok kritis, dan selisih kas.

### Tujuan
Menyajikan ringkasan yang padat dan mudah dibaca, sehingga owner tahu inti kondisi outlet dalam sekali lihat.
### Selesai bila
- Ringkasan berisi penjualan tiap outlet, total hari ini, produk terlaris, stok kritis, dan selisih kas.
- Angka ditulis singkat dan rapi (mis. "Rp1.250.000") agar cepat dipahami.
- Tampilan ringkas tanpa penjelasan panjang, cukup label dan angka.

## Sub-fitur: Pantau Terpisah / Gabungan

Owner bisa melihat angka tiap outlet secara terpisah maupun digabung.

### Tujuan
Memberi owner kebebasan melihat angka tiap outlet secara terpisah atau digabung, sesuai kebutuhan pemantauan.
### Selesai bila
- Owner bisa melihat penjualan Tembalang dan Grafika secara terpisah dalam ringkasan.
- Owner bisa melihat total gabungan dari kedua outlet dalam ringkasan yang sama.
- Perpindahan antara tampilan terpisah dan gabungan bisa dilakukan tanpa membuka laporan lain.

## Sub-fitur: Notifikasi Otomatis

Ringkasan dikirim otomatis ke HP owner, misalnya tiap tutup shift atau akhir hari.

### Tujuan
Mengirim ringkasan harian ke HP owner secara otomatis pada waktu tertentu, sehingga owner tidak perlu mengingat atau meminta laporan.
### Selesai bila
- Ringkasan terkirim otomatis ke HP owner saat tutup shift atau akhir hari sesuai pengaturan.
- Owner menerima notifikasi berisi ringkasan siap baca tanpa membuka aplikasi laporan.
- Pengiriman berjalan rutin setiap hari tanpa perlu tindakan manual owner.

## Task

### 1. Buat halaman utama laporan harian owner

### 2. Buat kartu ringkasan penjualan tiap outlet

### 3. Buat formatter rupiah dan penanda selisih kas

### 4. Buat toggle tampilan terpisah dan gabungan

### 5. Buat halaman pengaturan jadwal notifikasi

### 6. Poles tampilan mobile laporan harian

### 7. Buat tabel laporan harian dan pengaturan notifikasi

### 8. Buat service agregasi penjualan harian per outlet

### 9. Buat endpoint ringkasan harian terpisah dan gabungan

### 10. Hitung produk terlaris, stok kritis, dan selisih kas

### 11. Buat penyusun teks ringkasan format singkat

### 12. Buat penjadwal kirim notifikasi harian otomatis

### 13. Kirim notifikasi ringkasan ke HP owner
