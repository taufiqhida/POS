# Buka & Tutup Shift

Setiap shift wajib dibuka dengan modal awal dan ditutup dengan hitungan kas, jadi uang outlet selalu ketahuan.

## Spesifikasi

### Tujuan
Memastikan setiap shift kerja punya catatan uang yang jelas sejak dibuka sampai ditutup, sehingga tidak ada transaksi tanpa shift aktif dan owner selalu tahu kondisi kas outlet.

### Selesai bila
- Pegawai tidak bisa melayani transaksi sebelum shift dibuka dengan modal awal yang diisi.
- Seluruh uang yang keluar selama shift tercatat pada shift yang sedang berjalan.
- Saat shift ditutup, sistem menampilkan perbandingan uang seharusnya ada dengan uang fisik, beserta selisihnya dalam rupiah (contoh: −Rp25.000).
- Setiap shift tersimpan lengkap: outlet, pegawai yang buka/tutup, waktu buka/tutup, modal awal, kas seharusnya, kas fisik, dan selisih.
- Owner dapat melihat daftar shift beserta selisih kas tiap shift per outlet.

## Sub-fitur: Buka Shift & Modal Awal

Pegawai memulai shift dengan mengisi jumlah modal awal kas.

### Tujuan
Memulai sesi kerja pegawai dengan mengisi jumlah modal awal kas, sehingga shift siap menerima transaksi dan uang awal tercatat.

### Selesai bila
- Pegawai dapat menekan "Buka Shift" dan mengisi jumlah modal awal kas dalam rupiah.
- Shift baru tersimpan dengan outlet, nama pegawai, dan waktu buka, lalu berstatus aktif.
- Modul kasir tidak bisa dipakai sebelum ada shift aktif di outlet tersebut.

### Tujuan
Mencatat semua uang yang keluar selama shift di tempat yang sama, supaya perhitungan kas akhir tidak meleset.

### Selesai bila
- Pegawai dapat menambah pengeluaran dengan keterangan dan jumlah rupiah selama shift berjalan.
- Daftar pengeluaran shift terlihat lengkap dengan total pengeluarannya.
- Tiap pengeluaran tercatat atas nama pegawai yang mencatatnya, beserta waktunya.

## Sub-fitur: Catat Pengeluaran Outlet

Semua uang yang keluar selama shift dicatat di tempat yang sama.

### Tujuan
Menghitung uang yang seharusnya ada di kasir dan membandingkannya dengan uang fisik yang dihitung pegawai, agar kondisi kas shift ketahuan.

### Selesai bila
- Sistem menampilkan angka kas seharusnya (modal awal + penjualan tunai − pengeluaran) dalam format rupiah.
- Pegawai dapat memasukkan jumlah uang fisik yang ada di kasir.
- Sistem menampilkan perbandingan kas seharusnya vs kas fisik secara berdampingan sebelum shift ditutup.

## Sub-fitur: Hitung Kas Akhir

Sistem menghitung uang yang seharusnya ada, lalu dibandingkan dengan uang fisik.

### Tujuan
Memperlihatkan selisih kas tiap shift secara langsung, supaya owner tahu kondisi uang outlet tanpa menghitung manual.

### Selesai bila
- Selisih kas ditampilkan dalam rupiah lengkap dengan tanda kurang/lebih (contoh: −Rp25.000 atau +Rp10.000).
- Selisih ditandai jelas ketika kurang maupun lebih, bukan hanya angkanya.
- Owner dapat melihat selisih kas tiap shift per outlet, beserta nama pegawai yang membuka/menutup shift tersebut.

## Sub-fitur: Lihat Selisih Kas

Owner langsung melihat selisih kas tiap shift, misalnya kurang Rp25.000.

### Tujuan
Menutup shift dengan ringkasan singkat penjualan dan kasnya, sehingga shift berakhir rapi dan tercatat.

### Selesai bila
- Pegawai menutup shift dan status shift berubah menjadi tertutup dengan waktu tutup tersimpan.
- Ringkasan shift menampilkan penjualan tunai, penjualan non-tunai, total penjualan, pengeluaran, modal awal, kas seharusnya, kas fisik, dan selisih kas.
- Setelah shift ditutup, transaksi baru tidak bisa lagi masuk ke shift tersebut.

## Sub-fitur: Tutup Shift & Ringkasan

Shift ditutup dengan ringkasan singkat penjualan dan kas shift itu.

## Task

### 1. Buat halaman utama shift dengan data tiruan

### 2. Buat form buka shift modal awal

### 3. Buat UI catat pengeluaran outlet shift

### 4. Buat UI hitung kas akhir shift

### 5. Buat UI tutup shift dan ringkasan kas

### 6. Buat halaman daftar shift dan selisih kas

### 7. Buat tabel shift dan migrasinya

### 8. Buat tabel pengeluaran shift dan migrasinya

### 9. Buat endpoint buka shift aktif

### 10. Buat guard blokir transaksi tanpa shift aktif

### 11. Buat endpoint catat pengeluaran shift

### 12. Buat endpoint hitung dan tutup shift

### 13. Buat endpoint daftar shift dan selisih kas
