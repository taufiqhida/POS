# Gudang utama & kategori menu (permintaan owner)

## Kategori menu
- Owner/manager mengatur kategori di Admin > Menu & Produk > Kategori Menu: tambah, ganti nama, seret untuk mengatur urutan, hapus (hanya jika tidak dipakai menu).
- Ganti nama kategori ikut mengganti kategori di semua menu. Kategori baru juga bisa dibuat langsung dari form menu.
- Urutan tab kategori di layar kasir mengikuti urutan ini; hanya kategori yang punya menu aktif di outlet itu yang tampil.

## Stok gudang utama
- Admin > Stok > Stok Gudang Utama: tiap bahan punya kartu gudang (otomatis dibuat dari bahan outlet; stok awal 0).
- Barang masuk (dari supplier) menambah stok gudang. Koreksi fisik & catat rusak/terbuang tersedia.
- Outlet mengambil bahan dari gudang: stok gudang berkurang, stok outlet bertambah. Bisa dari layar kasir (tab Stok > Ambil dari gudang) atau dikirim owner dari panel admin.
- Ditolak bila melebihi stok gudang; satuan harus sama; dicatat atas nama pegawai di Riwayat Gudang & riwayat stok outlet.
- Pencocokan bahan gudang <-> outlet lewat NAMA bahan.

## Belum ada
- Konversi satuan (mis. gudang dus, outlet bungkus). Saat ini satuannya harus sama.

## Promo "beli item, gratis item"
- Admin > Promo & Diskon > jenis "Beli item, gratis item": pemicu (kategori/menu) + jumlah beli, hadiah (kategori/menu) + jumlah gratis.
- Contoh: beli 2 Waffle, gratis 1 Es Teh (kategori Es Teh · Teh Series). Kelipatan berlaku (beli 4 = 2 gratisan).
- Kasir: setelah syarat terpenuhi muncul banner "Dapat N gratisan", lalu memilih menu hadiah. Sebelum terpenuhi muncul petunjuk "Tambah N lagi".
- Item gratis dicatat Rp0, tetap memotong stok, tercetak "GRATIS" di struk. Tidak bisa melebihi jatah (dicek ulang di server), dan otomatis dibatalkan bila item pemicu dikurangi.
- Item gratis tidak ikut memicu promo lain dan tidak dihitung dalam diskon persen.
