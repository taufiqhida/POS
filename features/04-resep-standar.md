# Resep Standar

Tiap menu punya resep baku, sehingga kebutuhan bahan bisa dihitung dari jumlah penjualan.

## Spesifikasi

### Tujuan
Memberi setiap menu resep baku, supaya kebutuhan bahan bisa dihitung otomatis dari jumlah penjualan tanpa catatan manual.
### Selesai bila
- Owner/manager bisa menyimpan daftar bahan baku untuk tiap menu di satu tempat.
- Setiap bahan di resep punya takaran per porsi dengan satuan yang jelas (gram, pcs, ml).
- Sistem bisa menampilkan total bahan yang seharusnya terpakai dari jumlah penjualan pada periode tertentu.
- Owner bisa melihat selisih antara kebutuhan resep dan stok fisik sebagai tanda waste.
- Menu yang belum punya resep ditandai jelas agar tidak terlewat.

## Sub-fitur: Resep per Menu

Setiap menu disimpan bersama daftar bahan bakunya.

### Tujuan
Menyimpan setiap menu bersama daftar bahan bakunya, jadi jelas menu itu terbuat dari apa.
### Selesai bila
- Resep bisa dibuka dari halaman menu dan menampilkan daftar bahan baku menu tersebut.
- Bahan bisa ditambah atau dihapus dari resep sebuah menu, lalu tersimpan.
- Menu yang belum punya resep ditandai jelas supaya mudah dilengkapi.

## Sub-fitur: Takaran per Porsi

Jumlah tiap bahan dicatat, misalnya 10 gram topping coco crunchy per cup.

### Tujuan
Mencatat jumlah tiap bahan per porsi, agar kebutuhan bahan bisa dihitung tepat dari penjualan.
### Selesai bila
- Tiap bahan di resep punya kolom takaran dan satuan (gram/pcs/ml).
- Takaran bisa diisi angka desimal, misalnya 10 gram coco crunchy per cup.
- Perubahan takaran tersimpan dan langsung dipakai untuk perhitungan berikutnya.

## Sub-fitur: Hitung Kebutuhan Bahan

Sistem menghitung total bahan yang seharusnya terpakai dari penjualan.

### Tujuan
Menghitung total bahan yang seharusnya terpakai berdasarkan resep dan jumlah penjualan.
### Selesai bila
- Owner bisa memilih periode dan melihat daftar total bahan yang seharusnya terpakai.
- Tiap bahan ditampilkan dengan jumlah dan satuan yang sama seperti di resep.
- Hasil hitungan bisa dilihat per outlet (Tembalang/Grafika) maupun digabung.

## Sub-fitur: Deteksi Waste & Selisih

Selisih antara kebutuhan resep dan stok fisik membantu mendeteksi waste.

### Tujuan
Menunjukkan selisih antara kebutuhan resep dan stok fisik, supaya waste bisa dideteksi.
### Selesai bila
- Owner bisa memasukkan stok fisik lalu melihat selisihnya terhadap kebutuhan resep.
- Selisih besar ditandai jelas (misalnya label atau warna "perlu dicek").
- Riwayat pengecekan bisa dilihat kembali per periode dan per outlet.

## Task

### 1. Bangun halaman detail resep menu dengan data tiruan

### 2. Buat form tambah hapus bahan resep

### 3. Buat input takaran dan satuan per bahan

### 4. Tandai menu tanpa resep di daftar menu

### 5. Bangun halaman hitung kebutuhan bahan per periode

### 6. Bangun halaman selisih stok fisik dan waste

### 7. Tampilkan riwayat pengecekan waste per outlet

### 8. Buat tabel resep dan bahan resep dengan migrasi

### 9. Buat endpoint CRUD resep per menu

### 10. Buat endpoint daftar menu tanpa resep

### 11. Validasi satuan dan takaran desimal bahan resep

### 12. Buat service kalkulasi kebutuhan bahan dari penjualan

### 13. Buat endpoint kebutuhan bahan per periode dan outlet

### 14. Buat tabel stok fisik dan selisih dengan migrasi

### 15. Buat endpoint stok fisik hitung selisih dan riwayat
