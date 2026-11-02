# Royalty "Jelly Point" (SUDAH DIKERJAKAN)

## Cara kerja
- Member = nomor HP + nama + PIN 4 angka (diketik pelanggan sendiri). Tanpa aplikasi, tanpa kartu.
- Kasir mengisi **No. HP member** di layar bayar (opsional). Nomor baru → form daftar (nama, PIN, centang setuju dihubungi WA).
- Cap otomatis masuk saat pembayaran dikonfirmasi. **Cap digabung di Tembalang & Grafika.**
- **20 cap = 1 minuman gratis acak** (angka 20 bisa diubah di Admin › Pengaturan › Jelly Point).
- Tukar hadiah: kasir tekan "Tukar … cap", **pelanggan mengetik PIN-nya**, sistem mengundi minuman dari menu bertanda 🎁 (per outlet). Item hadiah Rp0, hanya 1 per pesanan.

## Cap per item (diatur owner di Admin › Menu, kolom "Cap Jelly Point per item")
- Otomatis awal: Waffle/Kebab/Burger/Maryam = 3, minuman Rp10.000 ke atas = 2, selebihnya = 1.
- Topping tidak menambah cap. Item gratis promo dan item hadiah tidak dapat cap.
- Pesanan GoFood/GrabFood/ShopeeFood tidak dapat cap.

## Aturan
- Karyawan (ditandai owner di Admin › Member): tidak dapat cap, tidak bisa menukar hadiah.
- Void/refund **disetujui**: cap yang didapat ditarik, cap yang dipakai untuk hadiah dikembalikan. Selama masih menunggu verifikasi, saldo belum berubah.
- Cap hangus bila 6 bulan tidak berkunjung (dihitung saat member dipakai lagi).
- PIN salah 5× → PIN terkunci; owner/manager reset di Admin › Member › Reset PIN setelah mencocokkan nomor HP.
- Tiap perubahan cap tercatat di `stamp_logs` (earn / redeem / reverse / expire).

## WhatsApp: hanya manual
- Admin › Penjualan › Member Jelly Point: filter **Perlu dihubungi** (setuju dihubungi, tidak datang 30 hari atau cap hampir penuh, belum dihubungi 14 hari terakhir).
- Tombol **Kirim WA** membuka wa.me dengan pesan terisi, dikirim manual satu per satu, lalu tekan **Sudah dihubungi**. Tidak ada kirim massal / gateway. Saran: maksimal ±20 pesan per hari.

## Keputusan
1. Stempel digabung untuk dua outlet.
2. Langsung berlaku di kedua outlet.
3. Hadiah satu tingkat: minuman acak dari daftar pilihan owner.
4. Karyawan tidak dapat cap. Jatah minuman karyawan & level member ditunda.

## Belum dibuat (ide lanjutan)
- Level member, hari cap ganda, hadiah ulang tahun, ajak teman.
- Laporan cap/penukaran per pegawai, QR cek cap di struk.
- Menambahkan nomor HP ke transaksi yang sudah selesai (lupa nomor).
