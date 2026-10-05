# Stok Otomatis

Stok bahan dan kemasan berkurang sendiri setiap ada penjualan, jadi tidak perlu dicatat ulang.

## Spesifikasi

### Tujuan
Stok bahan dan kemasan berkurang sendiri setiap ada penjualan, sehingga pegawai tidak perlu mencatat ulang dan jumlah stok di sistem selalu mendekati kondisi nyata.

### Selesai bila
- Setiap bahan dan kemasan tiap outlet terdaftar lengkap dengan jumlah stok saat ini dan batas minimumnya.
- Setiap transaksi penjualan langsung mengurangi jumlah stok bahan dan kemasan terkait tanpa aksi tambahan dari pegawai.
- Bahan atau kemasan yang tinggal sedikit atau di bawah batas minimum ditandai jelas, dan tanda itu muncul di dashboard owner.
- Pegawai dapat membandingkan jumlah stok di sistem dengan hasil hitungan fisik, dan selisihnya terlihat.
- Riwayat pemakaian bahan per outlet bisa dilihat kapan saja, termasuk keterangan perubahan berasal dari penjualan, penambahan, koreksi, atau waste.
- Data stok Tembalang dan Grafika tampil terpisah dan tidak pernah tercampur.

## Sub-fitur: Daftar Stok Bahan

Semua bahan dan kemasan outlet terdaftar dengan jumlahnya.

### Tujuan
Menampilkan daftar semua bahan dan kemasan tiap outlet beserta jumlah stoknya, agar pegawai dan owner tahu persediaan nyata di sistem.

### Selesai bila
- Daftar bahan dan kemasan tiap outlet menampilkan nama, satuan (gram/pcs/ml), jumlah stok saat ini, dan batas minimum.
- Daftar bisa difilter per outlet dan dicari berdasarkan nama bahan.
- Owner/manager bisa menambah bahan baru, mengubah jumlah stok, dan mengubah batas minimumnya.
- Data bahan Tembalang dan Grafika terpisah dan tidak tercampur.

## Sub-fitur: Potong Stok Otomatis

Setiap penjualan langsung mengurangi stok bahan dan kemasan terkait.

### Tujuan
Memastikan setiap penjualan langsung mengurangi stok bahan dan kemasan yang terpakai, sehingga pencatatan stok tidak perlu dilakukan manual.

### Selesai bila
- Setiap transaksi tersimpan membuat pengurangan stok bahan dan kemasan terkait secara otomatis, tanpa aksi tambahan pegawai.
- Jumlah pengurangan mengikuti takaran standar tiap menu (termasuk ukuran dan topping yang dipilih).
- Setelah transaksi selesai, jumlah stok terbaru langsung tampak berubah di daftar stok.
- Setiap pengurangan tercatat sebagai riwayat pemakaian dengan keterangan berasal dari penjualan.

## Sub-fitur: Peringatan Stok Kritis

Sistem memberi tanda saat bahan atau kemasan hampir habis.

### Tujuan
Memberi tanda saat bahan atau kemasan hampir habis, agar outlet bisa segera restock sebelum kehabisan.

### Selesai bila
- Bahan atau kemasan dengan jumlah stok sama dengan atau di bawah batas minimum otomatis ditandai sebagai stok kritis.
- Tanda stok kritis terlihat jelas di daftar stok (misalnya warna/label khusus) beserta nama bahan dan sisa jumlahnya.
- Daftar stok kritis bisa dilihat terpisah per outlet, dan tanda yang sama muncul di dashboard owner.

## Sub-fitur: Cek Stok Fisik vs Sistem

Pegawai bisa membandingkan jumlah di sistem dengan hitungan fisik.

### Tujuan
Memungkinkan pegawai membandingkan jumlah stok di sistem dengan hasil hitungan fisik, sehingga selisih dan indikasi waste ketahuan.

### Selesai bila
- Pegawai bisa memasukkan jumlah hasil hitungan fisik untuk tiap bahan atau kemasan yang dipilih.
- Sistem menampilkan selisih antara jumlah sistem dan jumlah fisik per bahan (dengan tanda plus/minus dan jumlahnya).
- Selisih yang dicatat tersimpan sebagai riwayat dengan keterangan koreksi, dan jumlah stok sistem mengikuti hasil penyesuaian.

## Sub-fitur: Riwayat Pemakaian Bahan

Riwayat bahan yang terpakai bisa dilihat kapan saja.

### Tujuan
Menyediakan catatan pemakaian bahan dan kemasan dari waktu ke waktu, agar owner bisa menelusuri ke mana stok bergerak.

### Selesai bila
- Riwayat menampilkan nama bahan, jumlah perubahan (bertambah/berkurang), keterangan alasan (penjualan, penambahan, koreksi, waste), dan waktu kejadian.
- Riwayat bisa difilter per outlet, per bahan, dan per rentang tanggal.
- Riwayat bisa dilihat oleh owner/manager kapan saja tanpa perlu membuka laporan terpisah.

## Task

### 1. Buat halaman daftar stok bahan

### 2. Tambah filter outlet dan pencarian bahan

### 3. Buat form tambah dan ubah bahan

### 4. Tandai bahan stok kritis di daftar

### 5. Buat tampilan daftar stok kritis per outlet

### 6. Buat halaman cek stok fisik vs sistem

### 7. Buat halaman riwayat pemakaian bahan

### 8. Buat skema tabel bahan dan stok outlet

### 9. Buat skema tabel riwayat pergerakan stok

### 10. Buat skema resep BOM menu ke bahan

### 11. Buat service potong stok otomatis transaksi

### 12. Buat API daftar dan CRUD bahan

### 13. Buat API deteksi stok kritis outlet

### 14. Buat API penyesuaian stok fisik

### 15. Buat API riwayat pemakaian bahan

### 16. Sediakan agregat stok kritis dashboard owner
