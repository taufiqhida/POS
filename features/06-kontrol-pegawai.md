# Kontrol Pegawai

Tiap pegawai punya akun sendiri, jadi semua transaksi, void, dan selisih kas jelas siapa pelakunya.

## Spesifikasi

### Tujuan
Memberi setiap pegawai akun pribadi agar semua transaksi, void, refund, diskon, dan selisih kas bisa diketahui jelas siapa pelakunya.

### Selesai bila
- Owner bisa melihat daftar pegawai (mis. Eka, Elsya, Mia, Pasya) beserta akun dan statusnya.
- Setiap transaksi, void, refund, dan diskon tercatat atas nama pegawai yang melakukannya.
- Owner bisa melihat performa dan selisih kas tiap pegawai.
- Hanya pegawai yang sudah login dengan akun & PIN pribadinya bisa melayani transaksi.

## Sub-fitur: Akun & PIN Pegawai

Setiap pegawai punya akun dan PIN sendiri untuk masuk.

### Tujuan
Memberi tiap pegawai akun dan PIN sendiri supaya bisa masuk dan aksi sensitif tetap terkunci pada orang yang berwenang.

### Selesai bila
- Owner/manager bisa menambah, mengubah, dan menonaktifkan akun pegawai beserta PIN-nya.
- Pegawai bisa login memakai akun & PIN pribadinya, dan PIN yang salah ditolak dengan pesan yang jelas.
- Tidak ada pegawai yang bisa memakai akun pegawai lain tanpa PIN miliknya.

## Sub-fitur: Transaksi per Pegawai

Sistem mencatat transaksi dilakukan oleh pegawai yang mana.

### Tujuan
Mencatat nama pegawai yang melayani setiap transaksi supaya penjualan bisa ditelusuri per orang.

### Selesai bila
- Setiap transaksi tersimpan bersama nama pegawai yang melayaninya.
- Daftar transaksi bisa dilihat dan disaring per pegawai.
- Nama kasir yang melayani terlihat jelas di detail transaksi atau struk.

## Sub-fitur: Riwayat Void, Refund & Diskon

Pembatalan, pengembalian, dan diskon tercatat atas nama pegawai.

### Tujuan
Mencatat setiap pembatalan, pengembalian, dan diskon atas nama pegawai pelakunya agar tidak ada aksi sensitif yang tanpa jejak.

### Selesai bila
- Setiap void, refund, dan pemberian diskon tercatat lengkap dengan nama pegawai, waktu, dan alasannya.
- Owner/manager bisa melihat riwayat void, refund, dan diskon serta menyaringnya per pegawai.
- Aksi void/refund/diskon hanya jalan setelah PIN owner/manager dimasukkan, dan tetap tercatat atas nama pelakunya.

## Sub-fitur: Performa & Selisih per Pegawai

Owner bisa melihat kinerja dan selisih kas tiap pegawai, seperti Eka atau Elsya.

### Tujuan
Menampilkan kinerja dan selisih kas tiap pegawai agar owner bisa menilai siapa yang rapi dan siapa yang perlu dibina.

### Selesai bila
- Owner bisa melihat ringkasan per pegawai: jumlah transaksi, total penjualan, serta jumlah void/refund/diskon.
- Owner bisa melihat selisih kas tiap shift per pegawai, misalnya Eka -Rp25.000.
- Angka ditampilkan dalam format Rupiah dan bisa dibandingkan antar pegawai dalam satu layar.

## Task

### 1. Bangun halaman utama kontrol pegawai

### 2. Buat form tambah dan edit akun pegawai

### 3. Buat halaman login pegawai pakai PIN

### 4. Buat halaman daftar transaksi per pegawai

### 5. Buat halaman riwayat void refund diskon

### 6. Buat modal verifikasi PIN owner manager

### 7. Buat halaman performa dan selisih kas pegawai

### 8. Buat skema tabel pegawai dan migrasi

### 9. Buat endpoint CRUD akun pegawai dan PIN

### 10. Buat autentikasi login pegawai dan sesi

### 11. Tambah relasi pegawai pada tabel transaksi

### 12. Buat endpoint transaksi dengan filter pegawai

### 13. Buat tabel dan endpoint void refund diskon

### 14. Buat endpoint ringkasan performa tiap pegawai

### 15. Buat endpoint selisih kas per shift pegawai

### 16. Amankan aksi sensitif dengan verifikasi PIN owner
