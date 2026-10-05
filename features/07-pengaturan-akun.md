# Pengaturan & Akun

Pengaturan dasar aplikasi: pengguna, outlet, harga, dan format struk.

## Spesifikasi

### Tujuan
Menyediakan satu tempat bagi owner dan manager untuk mengatur hal-hal dasar aplikasi—siapa yang bisa masuk, outlet mana saja yang berjalan, siapa yang boleh mengubah harga, dan bagaimana struk toko tampil—supaya operasional dua outlet rapi dan terkendali.

### Selesai bila
- Owner dan manager bisa masuk dengan akun masing-masing dan hanya melihat menu pengaturan yang sesuai perannya.
- Owner bisa menambah, mengubah, dan menonaktifkan data outlet (Tembalang, Grafika) beserta alamatnya.
- Ada pengaturan yang jelas siapa saja yang boleh mengubah harga menu, dan percobaan oleh yang tidak berhak otomatis ditolak.
- Nama toko, alamat, dan tampilan struk bisa diubah dan langsung terpakai saat struk dicetak atau dikirim.
- Semua perubahan pengaturan ini tercatat beserta siapa pelakunya.

## Sub-fitur: Login Owner & Manager

Owner dan manager masuk dengan akun masing-masing sesuai hak aksesnya.

### Tujuan
Memastikan hanya owner dan manager yang bisa masuk ke panel pengaturan dengan akun masing-masing, sehingga hak aksesnya sesuai peran dan data outlet tetap aman.

### Selesai bila
- Owner dan manager bisa login memakai akun masing-masing; percobaan login dengan data salah ditolak dengan pesan yang jelas.
- Setelah masuk, menu yang tampil berbeda sesuai peran (owner melihat semua, manager hanya yang diizinkan).
- Ada tombol keluar (logout) yang berfungsi dengan aman.

## Sub-fitur: Kelola Outlet

Owner bisa menambah dan mengatur data tiap outlet.

### Tujuan
Memberi owner tempat untuk menambah dan mengatur data tiap outlet (nama, alamat), supaya penjualan, stok, dan shift tiap outlet tidak tercampur.

### Selesai bila
- Owner bisa menambah outlet baru dengan mengisi nama dan alamat.
- Owner bisa mengubah dan menonaktifkan outlet yang sudah ada (misalnya Tembalang dan Grafika).
- Daftar outlet tampil rapi dan data tiap outlet tidak pernah tercampur dengan outlet lain.

## Sub-fitur: Hak Ubah Harga

Pengaturan siapa saja yang boleh mengubah harga menu.

### Tujuan
Mengatur siapa saja yang boleh mengubah harga menu (owner/manager), agar harga, promo, dan diskon tidak bisa diubah sembarangan oleh pegawai kasir.

### Selesai bila
- Ada pengaturan daftar peran yang diizinkan mengubah harga menu.
- Percobaan mengubah harga oleh pegawai yang tidak berhak ditolak dan tercatat di riwayat.
- Perubahan harga yang sah langsung terpakai di layar kasir.

## Sub-fitur: Atur Struk & Identitas Toko

Nama toko, alamat, dan tampilan struk bisa diubah sesuai kebutuhan.

### Tujuan
Memungkinkan owner mengatur nama toko, alamat, dan tampilan struk, sehingga struk yang diterima pembeli sesuai identitas Jelly Potter.

### Selesai bila
- Owner bisa mengubah nama toko dan alamat yang muncul di struk.
- Owner bisa mengatur tampilan struk (misalnya teks tambahan atau ucapan) dan melihat contoh hasilnya.
- Perubahan langsung terpakai pada struk digital maupun cetak berikutnya.

## Task

### 1. Bangun layout utama halaman pengaturan dengan data tiruan

### 2. Buat halaman login owner dan manager pakai data tiruan

### 3. Buat menu bersyarat peran dan aksi logout di UI

### 4. Buat halaman daftar outlet dengan data tiruan

### 5. Buat form tambah ubah dan nonaktifkan outlet

### 6. Buat halaman pengaturan hak ubah harga dan riwayat penolakan

### 7. Buat halaman atur identitas toko dan preview struk

### 8. Buat skema dan migrasi tabel pengguna dengan peran

### 9. Buat endpoint autentikasi login dan logout

### 10. Buat skema dan endpoint CRUD kelola outlet

### 11. Buat endpoint pengaturan hak ubah harga dan penegakan izin

### 12. Buat endpoint pengaturan identitas toko dan struk

### 13. Buat pencatatan audit semua perubahan pengaturan
