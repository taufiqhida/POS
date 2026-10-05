# Kasir & Transaksi

Layar utama untuk melayani pembeli dan mencatat setiap penjualan jadi satu transaksi lengkap.

## Spesifikasi

### Tujuan
Layar utama kasir yang membuat pegawai bisa melayani pembeli dan setiap penjualan langsung tercatat lengkap, supaya penjualan, pembayaran, dan struk tidak perlu dicatat manual.

### Selesai bila
- Pegawai bisa memilih menu, ukuran, dan topping dari layar kasir, lalu melihat total belanja sebelum bayar.
- Harga, promo, dan diskon muncul otomatis di layar tanpa dihitung manual oleh pegawai.
- Pegawai bisa memilih cara bayar (tunai, QRIS, debit, digital) dan transaksi tersimpan setelah pembayaran dikonfirmasi.
- Setelah transaksi selesai, pembeli bisa menerima struk (cetak atau digital) dan transaksi tercatat atas nama pegawai yang melayani.
- Perubahan harga hanya bisa dilakukan setelah memasukkan PIN owner/manager.

## Sub-fitur: Pilih Menu & Topping

Pegawai memilih menu, ukuran, dan topping langsung dari layar.

### Tujuan
Pegawai bisa memilih menu, ukuran, dan topping langsung dari layar kasir sehingga pesanan pelanggan tersusun cepat tanpa tulis manual.

### Selesai bila
- Daftar menu tampil sebagai tombol/grid yang bisa ditekan, lengkap dengan nama dan harganya.
- Pegawai bisa memilih ukuran (mis. Regular/Large) dan menambahkan topping pada item yang dipilih.
- Item yang dipilih tampil di daftar pesanan lengkap dengan jumlah, ukuran, topping, dan subharganya, serta bisa ditambah, dikurangi, atau dihapus.

## Sub-fitur: Harga & Diskon Otomatis

Harga, promo, dan diskon terhitung sendiri tanpa dihitung manual.

### Tujuan
Supaya harga, promo, dan diskon terhitung sendiri oleh sistem, tanpa dihitung manual dan tanpa bisa diubah sembarangan.

### Selesai bila
- Total harga di layar otomatis berubah setiap kali item, ukuran, topping, atau jumlah diubah.
- Promo/diskon yang berlaku langsung terlihat sebagai potongan pada ringkasan pembayaran.
- Perubahan harga atau diskon secara manual hanya bisa dilakukan setelah memasukkan PIN owner/manager.

## Sub-fitur: Pilihan Pembayaran

Mendukung bayar tunai, QRIS, debit, dan pembayaran digital lain.

### Tujuan
Pegawai bisa mencatat cara bayar pelanggan (tunai, QRIS, debit, digital) supaya uang yang masuk terdata sesuai jenisnya.

### Selesai bila
- Layar pembayaran menampilkan pilihan: Tunai, QRIS, Debit, dan Digital.
- Untuk pembayaran tunai, pegawai mengisi uang yang diterima dan sistem menampilkan kembalian.
- Setelah pembayaran dikonfirmasi, transaksi tersimpan dengan metode bayar yang dipilih.

## Sub-fitur: Struk Digital & Cetak

Pembeli bisa terima struk tercetak atau versi digital.

### Tujuan
Pembeli bisa menerima bukti pembayaran berupa struk cetak atau versi digital setelah transaksi selesai.

### Selesai bila
- Setelah pembayaran, layar menampilkan pratinjau struk berisi daftar pesanan, total, dan cara bayar.
- Tersedia tombol untuk mencetak struk ke printer dan tombol untuk membagikan struk digital.
- Struk digital bisa dibuka lewat tautan yang bisa dikirim ke pembeli.

## Sub-fitur: Kunci Harga dengan PIN

Harga hanya bisa diubah oleh owner atau manager lewat PIN.

### Tujuan
Supaya harga menu tidak bisa diubah sembarangan dan hanya owner atau manager yang bisa mengubahnya dengan PIN.

### Selesai bila
- Aksi ubah harga di layar kasir langsung meminta PIN sebelum bisa dilanjutkan.
- PIN yang salah tidak membuka akses dan muncul pesan jelas bahwa akses ditolak.
- Setelah PIN benar, perubahan harga tersimpan dan tercatat siapa yang melakukannya.

## Task

### 1. Bangun halaman utama kasir dengan data tiruan

### 2. Buat grid menu dengan tombol nama dan harga

### 3. Buat pemilih ukuran dan topping per item

### 4. Buat daftar pesanan dengan tambah kurang hapus

### 5. Buat ringkasan total dan promo diskon otomatis

### 6. Buat layar pembayaran tunai QRIS debit digital

### 7. Buat pratinjau struk dengan tombol cetak dan bagikan

### 8. Buat modal PIN untuk ubah harga di kasir

### 9. Buat skema dan migrasi katalog menu ukuran topping

### 10. Buat skema dan migrasi transaksi serta item pesanan

### 11. Buat skema dan migrasi pembayaran serta promo diskon

### 12. Buat skema dan migrasi pengguna PIN dan log ubah harga

### 13. Buat API katalog menu ukuran dan topping

### 14. Buat API perhitungan harga promo dan diskon

### 15. Buat API simpan transaksi dan konfirmasi pembayaran

### 16. Buat API struk digital dan tautan berbagi

### 17. Buat API verifikasi PIN dan ubah harga dengan audit
