# Pesanan Online Terintegrasi

Pesanan dari GoFood, GrabFood, ShopeeFood, dan kasir offline masuk ke satu sistem agar omzet yang dilihat owner omzet sebenarnya.

## Spesifikasi

### Tujuan
Menjadikan semua pesanan dari GoFood, GrabFood, ShopeeFood, dan kasir offline tercatat di satu sistem, agar omzet yang dilihat owner adalah omzet yang sebenarnya.

### Selesai bila
- Setiap pesanan yang dicatat pegawai punya penanda sumber kanal (offline, GoFood, GrabFood, atau ShopeeFood) dan tetap muncul dalam satu daftar pesanan yang sama.
- Angka omzet bisa dilihat menyatu untuk semua kanal, dan tetap bisa dipecah per kanal.
- Produk terlaris dihitung dari seluruh kanal, bukan hanya dari kasir offline.
- Owner menerima peringatan penting (stok kritis, selisih kas, lonjakan penjualan) otomatis ke HP tanpa membuka laporan satu per satu.
- Tidak ada lagi laporan penjualan yang terpisah-pisah per kanal — semua berasal dari satu sumber angka yang sama.

## Sub-fitur: Satu Kanal Data

Semua pesanan online dan offline tercatat di satu sistem, bukan laporan terpisah-pisah.

### Tujuan
Menyatukan pesanan online dan offline ke dalam satu daftar pesanan yang sama supaya tidak ada angka yang tercecer di laporan berbeda.

### Selesai bila
- Daftar pesanan menampilkan pesanan offline maupun online dalam satu tampilan yang sama.
- Setiap baris pesanan punya penanda kanal asal yang mudah dikenali sekilas.
- Total penjualan dihitung dari daftar ini saja, sehingga tidak ada dua sumber angka yang bisa berbeda.

## Sub-fitur: Input Manual per Kanal

Pesanan online dicatat manual oleh pegawai di kasir lalu dipilih sumbernya: offline, GoFood, GrabFood, atau ShopeeFood.

### Tujuan
Memberi cara bagi pegawai untuk mencatat pesanan online langsung di kasir dengan memilih sumber kanalnya, tanpa perlu integrasi otomatis ke platform.

### Selesai bila
- Saat mencatat pesanan, pegawai memilih sumbernya: offline, GoFood, GrabFood, atau ShopeeFood.
- Pilihan kanal tampil jelas di layar kasir, misalnya tombol besar berlabel nama kanal.
- Pesanan online yang dicatat manual langsung masuk ke rekap omzet yang sama dengan transaksi kasir biasa.
- Pesanan tersimpan menampilkan kanal asalnya, sehingga bisa dicek ulang kapan saja.

## Sub-fitur: Rekap Omzet Gabungan

Owner melihat omzet total dari semua kanal sekaligus dan tetap bisa dipecah per kanal.

### Tujuan
Memberi owner satu angka omzet total dari semua kanal sekaligus, yang tetap bisa dipecah per kanal untuk melihat kontribusi masing-masing.

### Selesai bila
- Ada satu angka "Omzet Total" yang menggabungkan semua kanal.
- Owner bisa memilih tampilan gabungan atau terpisah per kanal (offline, GoFood, GrabFood, ShopeeFood).
- Angka omzet bisa dilihat per hari dan per outlet (Tembalang dan Grafika).
- Semua angka uang ditampilkan dalam format rupiah yang konsisten, misalnya "Rp1.250.000".

## Sub-fitur: Analisis Produk Terlaris

Produk paling laku dianalisis lintas kanal online dan offline.

### Tujuan
Menunjukkan produk paling laku dari seluruh kanal, bukan hanya dari kasir offline, agar owner tahu produk andalan yang sebenarnya.

### Selesai bila
- Daftar produk terlaris dihitung dari penjualan semua kanal, online maupun offline.
- Setiap produk terlaris ditampilkan bersama jumlah terjualnya.
- Daftar produk terlaris bisa difilter per kanal dan per outlet.

## Sub-fitur: Prediksi Kebutuhan Stok

Sistem memperkirakan kebutuhan stok dari pola penjualan.

### Tujuan
Memperkirakan kebutuhan stok ke depan dari pola penjualan, supaya outlet tidak kehabisan bahan saat ramai.

### Selesai bila
- Sistem menampilkan perkiraan kebutuhan bahan untuk periode berikutnya (misalnya besok atau minggu ini) berdasarkan pola penjualan.
- Perkiraan kebutuhan bisa dilihat per outlet.
- Bahan yang diperkirakan akan kurang ditandai dengan jelas di tampilan.

## Sub-fitur: Notifikasi Otomatis ke HP Owner

Peringatan penting seperti stok kritis, selisih kas, atau lonjakan penjualan dikirim otomatis ke HP owner.

### Tujuan
Mengirim peringatan penting langsung ke HP owner supaya owner bisa cepat bertindak tanpa harus membuka aplikasi lebih dulu.

### Selesai bila
- Owner menerima notifikasi otomatis saat ada bahan yang masuk kategori stok kritis.
- Owner menerima notifikasi otomatis saat ada selisih kas pada shift yang ditutup.
- Owner menerima notifikasi otomatis saat terjadi lonjakan penjualan yang tidak biasa.
- Isi notifikasi singkat dan jelas: nama outlet, jenis kejadian, dan angkanya.

## Task

### 1. Bangun halaman daftar pesanan terpadu

### 2. Buat form input pesanan per kanal

### 3. Buat komponen penanda kanal pesanan

### 4. Bangun halaman rekap omzet gabungan

### 5. Bangun halaman analisis produk terlaris

### 6. Bangun halaman prediksi kebutuhan stok

### 7. Bangun halaman notifikasi otomatis owner

### 8. Tambah kolom kanal pada skema pesanan

### 9. Buat API daftar pesanan semua kanal

### 10. Buat API pencatatan pesanan per kanal

### 11. Buat API agregasi omzet total per kanal

### 12. Buat API analisis produk terlaris lintas kanal

### 13. Buat service prediksi kebutuhan stok

### 14. Setup layanan kirim notifikasi ke HP

### 15. Buat pemicu notifikasi stok kritis

### 16. Buat pemicu notifikasi selisih kas

### 17. Buat pemicu notifikasi lonjakan penjualan
