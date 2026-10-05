# Dashboard Dua Outlet

Owner cukup buka HP untuk melihat kondisi Tembalang dan Grafika dalam satu layar.

## Spesifikasi

### Tujuan
Memberi owner satu layar di HP untuk langsung mengetahui kondisi kedua outlet (Tembalang & Grafika) tanpa membuka banyak laporan terpisah.

### Selesai bila
- Begitu dashboard dibuka, owner langsung melihat ringkasan hari ini (total penjualan) tanpa mengatur apa pun.
- Penjualan Tembalang dan Grafika tampil terpisah dan berdampingan dalam satu layar, dengan angka rupiah berformat jelas (mis. Rp1.250.000).
- Produk terlaris hari ini, peringatan stok kritis, dan selisih kas per shift tampil di dashboard.
- Angka tiap outlet tidak tercampur; owner bisa membaca tiap outlet sendiri maupun melihat total gabungan.
- Owner bisa mengunduh laporan penjualan dan kas kapan saja dari dashboard.
- Tampilan nyaman dibaca dari layar HP (tombol dan teks cukup besar, mudah digulir).

## Sub-fitur: Ringkasan Hari Ini

Total penjualan hari ini langsung terlihat begitu dashboard dibuka.

### Tujuan
Menampilkan total penjualan hari ini secara langsung di bagian atas dashboard agar owner tahu kondisi hari itu sekali buka.

### Selesai bila
- Total penjualan hari ini tampil otomatis begitu dashboard dibuka, tanpa perlu memilih tanggal.
- Angka ditampilkan dalam format rupiah yang jelas (mis. Rp1.250.000) dan posisinya paling menonjol di layar.
- Bila belum ada penjualan hari ini, tetap tampil angka Rp0 dengan keterangan yang jelas (bukan kosong/error).

## Sub-fitur: Penjualan per Outlet

Penjualan Tembalang dan Grafika ditampilkan terpisah dan berdampingan.

### Tujuan
Memperlihatkan penjualan Tembalang dan Grafika secara terpisah namun berdampingan agar owner bisa membandingkan kedua outlet sekaligus.

### Selesai bila
- Penjualan Tembalang dan Grafika muncul masing-masing sebagai angka tersendiri (mis. label "Tembalang" dan "Grafika").
- Keduanya tampil berdampingan dalam satu layar tanpa perlu berpindah halaman.
- Angka tiap outlet tidak tercampur, dan owner juga bisa melihat total gabungan keduanya.

## Sub-fitur: Produk Terlaris

Menu paling laku hari ini ditampilkan otomatis.

### Tujuan
Menampilkan menu paling laku hari ini secara otomatis sebagai gambaran cepat produk andalan.

### Selesai bila
- Produk terlaris hari ini tampil otomatis (mis. nama menu beserta jumlah terjual).
- Bila ada beberapa produk laku, ditampilkan urutan dari yang paling banyak terjual.
- Bila belum ada penjualan, tampil keterangan yang jelas bahwa belum ada data produk terlaris.

## Sub-fitur: Stok Kritis & Selisih Kas

Peringatan stok menipis dan selisih kas muncul di dashboard.

### Tujuan
Memunculkan peringatan stok menipis dan selisih kas agar owner langsung tahu hal yang perlu ditindak.

### Selesai bila
- Bahan atau kemasan yang hampir habis tampil sebagai peringatan stok kritis di dashboard (mis. nama bahan + sisa jumlahnya).
- Selisih kas tiap shift tampil dengan tanda jelas, misalnya kurang/lebih Rp25.000.
- Bila tidak ada stok kritis atau selisih kas, dashboard menampilkan keterangan aman/tidak ada peringatan.

## Sub-fitur: Unduh Laporan

Owner bisa mengunduh laporan penjualan dan kas kapan pun.

### Tujuan
Memberi owner kemampuan mengunduh laporan penjualan dan kas kapan saja langsung dari dashboard.

### Selesai bila
- Ada tombol nyata untuk mengunduh laporan penjualan dan laporan kas dari dashboard.
- Hasil unduhan berisi data kedua outlet dan bisa dibedakan per outlet.
- Owner bisa memilih periode laporan (mis. hari ini atau rentang tanggal) sebelum mengunduh.

## Task

### 1. Bangun halaman dashboard dua outlet data tiruan

### 2. Buat kartu ringkasan total penjualan hari ini

### 3. Buat kartu penjualan per outlet berdampingan

### 4. Buat daftar produk terlaris hari ini

### 5. Buat panel stok kritis dan selisih kas

### 6. Buat tombol unduh laporan dan pilih periode

### 7. Rancang skema outlet transaksi stok kas

### 8. Buat endpoint ringkasan penjualan hari ini

### 9. Buat endpoint penjualan tiap outlet dan total

### 10. Buat endpoint produk terlaris hari ini

### 11. Buat endpoint stok kritis dan selisih kas

### 12. Buat endpoint unduh laporan penjualan dan kas
