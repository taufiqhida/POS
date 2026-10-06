# Deploy ke VPS (Docker)

Panduan memasang Jelly Potter Kasir di VPS **Ubuntu 22.04 / 24.04** memakai Docker.
Semua perintah dijalankan di VPS lewat SSH (`ssh root@IP-VPS`).

Isi yang berjalan:

| Container | Fungsi |
|---|---|
| `app` | Aplikasi (Nginx + PHP 8.3), migrasi database otomatis saat start |
| `scheduler` | Laporan harian ke HP owner jam 22:00 |
| `db` | MySQL 8 (data di volume `dbdata`) |
| `backup` | Backup database harian ke folder `backups/`, disimpan 14 hari |
| `caddy` | HTTPS otomatis (Let's Encrypt) |

Spesifikasi minimal: 1 vCPU, **2 GB RAM**, 20 GB disk.

---

## 1. Siapkan domain (disarankan)

Di pengelola DNS domain, buat **A record**: `kasir.domainanda.com → IP-VPS`.
Tanpa domain tetap bisa (lewat `http://IP-VPS`), tapi tanpa HTTPS.

## 2. Pasang Docker & firewall

```bash
apt update && apt upgrade -y
curl -fsSL https://get.docker.com | sh
ufw allow OpenSSH && ufw allow 80 && ufw allow 443 && ufw --force enable
```

## 3. Ambil kode

```bash
git clone https://github.com/taufiqhida/POS.git /opt/jelly-potter
cd /opt/jelly-potter
```

## 4. Isi konfigurasi

```bash
cp .env.production.example .env
echo "APP_KEY=base64:$(openssl rand -base64 32)"
echo "DB_PASSWORD=$(openssl rand -hex 20)"
echo "DB_ROOT_PASSWORD=$(openssl rand -hex 20)"
nano .env
```

Salin tiga nilai yang tampil ke `.env`, lalu isi:

- `APP_DOMAIN` → `kasir.domainanda.com`
- `APP_URL` → `https://kasir.domainanda.com`

**Belum punya domain?** Isi `APP_DOMAIN=:80`, `APP_URL=http://IP-VPS`, dan `SESSION_SECURE_COOKIE=false`.

Simpan: `Ctrl+O`, Enter, `Ctrl+X`.

## 5. Jalankan

```bash
docker compose up -d --build
docker compose logs -f app
```

Build pertama ±5–10 menit. Tunggu sampai log menampilkan `INFO  Nothing to migrate` / migrasi selesai, lalu `Ctrl+C`.

## 6. Isi data awal & buat akun asli

```bash
docker compose exec -u www-data app php artisan db:seed --class=ProductionSeeder --force
docker compose exec -u www-data app php artisan jp:user
```

`ProductionSeeder` mengisi outlet Tembalang & Grafika dan semua menu — **tanpa akun demo**.
Jalankan `jp:user` sekali untuk owner, lalu ulangi untuk setiap pegawai
(Grafika: Eka, Pasya — Tembalang: Elsya, Mia). Pegawai juga bisa ditambah nanti dari panel admin.

## 7. Selesai

- Kasir: `https://kasir.domainanda.com/kasir`
- Owner: `https://kasir.domainanda.com/admin`

Di panel admin → **Pengaturan**, isi nomor WhatsApp owner.

---

## Perawatan

**Update setelah ada perubahan di GitHub**

```bash
cd /opt/jelly-potter
git pull
docker compose up -d --build
```

Migrasi database berjalan otomatis. Jangan jalankan `MenuSeeder` lagi di server yang sudah dipakai
kecuali memang ingin menyetel ulang resep — ubah harga/menu lewat panel admin.

**Lihat log**

```bash
docker compose logs -f app
docker compose exec app tail -n 100 storage/logs/laravel-$(date +%F).log
```

**Backup manual & pulihkan**

```bash
ls -lh backups/
docker compose restart backup          # memicu backup baru sekarang
gunzip < backups/NAMA-FILE.sql.gz | docker compose exec -T db sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
```

Salin isi folder `backups/` ke tempat lain secara berkala (Google Drive, komputer kantor) —
backup di VPS yang sama tidak menolong bila VPS-nya rusak.

**Status & restart**

```bash
docker compose ps
docker compose restart app
```

---

## Memakai MySQL yang sudah ada di VPS

Kalau VPS sudah punya container MySQL milik project lain, Jelly Potter bisa menumpang di server MySQL
itu dengan **database & user sendiri** (jangan memakai database project lain — tabel `users` dan
`sessions` akan bentrok). Varian ini juga tidak memakai Caddy, karena port 80/443 biasanya sudah
dipakai web server yang ada.

**a. Cari nama container & network MySQL yang sudah ada**

```bash
docker ps --format '{{.Names}}\t{{.Image}}' | grep -iE 'mysql|maria'
docker inspect -f '{{range $k, $v := .NetworkSettings.Networks}}{{$k}} {{end}}' NAMA_CONTAINER_MYSQL
```

**b. Buat database & user khusus Jelly Potter** (minta password root MySQL saat diminta)

```bash
JP_DB_PASS=$(openssl rand -hex 20); echo "DB_PASSWORD=$JP_DB_PASS"
docker exec -it NAMA_CONTAINER_MYSQL mysql -u root -p -e "
  CREATE DATABASE IF NOT EXISTS jelly_potter_kasir CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS 'jellypotter'@'%' IDENTIFIED BY '$JP_DB_PASS';
  GRANT ALL PRIVILEGES ON jelly_potter_kasir.* TO 'jellypotter'@'%';
  FLUSH PRIVILEGES;"
```

**c. Isi `.env`** (langkah 4 di atas), dengan bagian database seperti ini:

```
DB_HOST=NAMA_CONTAINER_MYSQL
DB_DATABASE=jelly_potter_kasir
DB_USERNAME=jellypotter
DB_PASSWORD=<hasil langkah b>
SHARED_DB_NETWORK=<nama network dari langkah a>
APP_PORT=8084
```

**d. Jalankan**

```bash
docker compose -f docker-compose.shared-db.yml up -d --build
docker compose -f docker-compose.shared-db.yml exec -u www-data app php artisan db:seed --class=ProductionSeeder --force
docker compose -f docker-compose.shared-db.yml exec -u www-data app php artisan jp:user
```

**e. Arahkan domain ke aplikasi** — tambahkan di web server yang sudah ada. Contoh Nginx di host
(`/etc/nginx/sites-available/jellypotter`):

```nginx
server {
    server_name kasir.domainanda.com;
    client_max_body_size 10M;
    location / {
        proxy_pass http://127.0.0.1:8084;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
ln -s /etc/nginx/sites-available/jellypotter /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
certbot --nginx -d kasir.domainanda.com
```

Semua perintah perawatan di atas tetap berlaku, tambahkan `-f docker-compose.shared-db.yml`.
