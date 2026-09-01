# Panduan Deployment — CBT-SYNC

Panduan menjalankan **CBT-SYNC** di **lokal** dan **server** (termasuk deploy di
**domain/subfolder**, mis. `https://beoulve-dev.biz.id/cbt-sync`).

> **Inti soal subfolder:** seluruh URL di aplikasi ini memakai helper Laravel
> (`route()`, `url()`, `asset()`) — **tidak ada path keras** seperti `/admin/...`.
> Selama folder **`public/` disajikan pada path subfolder**, Laravel otomatis
> mendeteksi base path sehingga **setiap menu & aksi tetap membawa prefiks
> subfolder** (tidak hilang). Kunci utamanya ada di konfigurasi web server + `APP_URL`.

---

## 1. Prasyarat

- PHP **8.2+** (disarankan 8.3) + ekstensi: `pdo_pgsql`, `mbstring`, `zip`, `gd`, `xml`, `curl`, `fileinfo`, `openssl`
- **Composer** 2.x
- **PostgreSQL** (proyek ini memakai PostgreSQL)
- Git

---

## 2. Menjalankan di LOKAL (Windows / XAMPP)

### Opsi A — `php artisan serve` (paling mudah)
```bash
cd C:/xampp/htdocs/myProject/cbt-sync
php artisan serve --port=8888
# buka http://127.0.0.1:8888
```
`.env` lokal:
```
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8888     # samakan dengan port serve di atas
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=cbt_sync              # DB terpisah dari lms_sync
DB_USERNAME=postgres
DB_PASSWORD=xxxx
```
> Jalankan di **port berbeda** dari lms-sync bila keduanya aktif bersamaan.

### Opsi B — XAMPP subfolder (`http://localhost/cbt-sync`)
Karena `public/` adalah entry point, buat penunjuk dari `htdocs/cbt-sync` ke
`public/`. Cara termudah — buat `C:/xampp/htdocs/cbt-sync/index.php`:
```php
<?php require __DIR__.'/../myProject/cbt-sync/public/index.php';
```
dan salin `public/.htaccess`-nya, **atau** pakai Alias (lihat bagian server).
Set `APP_URL=http://localhost/cbt-sync`.

---

## 3. Deploy ke SERVER — domain/subfolder

Contoh target: **`https://beoulve-dev.biz.id/cbt-sync`** (pola sama dengan lms-sync).

### 3.1 Ambil kode
```bash
cd /var/www           # atau folder aplikasi Anda (di LUAR public_html bila bisa)
git clone https://github.com/rendyirawann/cbt-sync.git
cd cbt-sync
```
Update berikutnya cukup: `git pull origin main`.

### 3.2 Dependency
```bash
composer install --no-dev --optimize-autoloader
```

### 3.3 Konfigurasi `.env`
Buat `.env` (salin dari `.env.example` bila perlu), lalu isi:
```
APP_NAME="CBT-SYNC"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://beoulve-dev.biz.id/cbt-sync     # WAJIB termasuk subfolder

SEO_TITLE="CBT-SYNC"

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432                                    # sesuaikan
DB_DATABASE=cbt_sync
DB_USERNAME=xxxx
DB_PASSWORD=xxxx
```
```bash
php artisan key:generate        # hanya bila APP_KEY masih kosong
```

### 3.4 Database
```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force   # role (Superadmin/Admin/Guru/Siswa/Kepala Sekolah)
# opsional data demo (sekolah/guru/siswa/kepsek@lms.com):
# php artisan db:seed --class=DemoAccountSeeder --force
```

### 3.5 Symlink storage & izin folder
```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
# pastikan user web server (www-data) punya akses tulis ke storage & bootstrap/cache
```

### 3.6 Arahkan subfolder ke `public/`  ← **kunci agar subfolder tidak hilang**
Pilih **salah satu**:

**A. Apache Alias** (di vhost / conf):
```apache
Alias /cbt-sync /var/www/cbt-sync/public
<Directory /var/www/cbt-sync/public>
    AllowOverride All
    Require all granted
</Directory>
```

**B. Symlink** (bila docroot = `public_html`):
```bash
ln -s /var/www/cbt-sync/public /home/user/public_html/cbt-sync
```

> **Jangan** mengarahkan subfolder ke root aplikasi — arahkan ke **`public/`**.
> Dengan cara ini Laravel membaca base path `/cbt-sync` dari request, sehingga
> `route()`/`asset()`/`url()` otomatis menambahkan `/cbt-sync` di **semua** menu & aksi.

Bila CSS/JS/route 404 setelah rewrite, tambahkan `RewriteBase /cbt-sync` pada
`public/.htaccess` (biasanya tidak perlu dengan Alias di atas).

### 3.7 HTTPS di belakang proxy (opsional tapi umum)
Jika TLS diterminasi reverse proxy dan URL jadi `http://`, percayai proxy di
`bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');   // atau IP proxy Anda
    // ...alias yang sudah ada tetap...
})
```
`APP_URL` yang `https://...` juga membantu link absolut (email dsb).

### 3.8 Optimasi cache produksi
```bash
php artisan optimize        # config + route + view cache
# atau granular:
# php artisan config:cache && php artisan route:cache && php artisan view:cache
```
> Jalankan **setelah** `.env` final. Bila ubah `.env`/route/menu, ulangi
> `php artisan optimize:clear` lalu `php artisan optimize`.

---

## 4. Ringkasan perintah deploy (server)
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan storage:link            # sekali saja
php artisan optimize:clear
php artisan optimize
```

---

## 5. Kenapa subfolder tetap aman (untuk menu & aksi)

- **Semua tautan** dibuat via `route('nama.route')`, form `action="{{ route(...) }}"`,
  aset `asset('assets/...')`, dan redirect `redirect()->route(...)`.
- Helper ini menghasilkan URL relatif terhadap **base path request**. Karena
  server menyajikan `public/` di `/cbt-sync`, base path = `/cbt-sync` → prefiks
  ikut di setiap URL, **baik menu maupun aksi (store/update/delete)**.
- Di lokal (root, tanpa subfolder) base path = `/`, jadi tetap benar tanpa ubah kode.

**Aturan agar tetap aman ke depan:**
- ❌ Jangan tulis path keras: `href="/admin/exams"`, `fetch("/login")`, `action="/admin/..."`.
- ✅ Selalu pakai: `{{ route('exams.index') }}`, `{{ url('/...') }}`, `{{ asset('...') }}`,
  dan untuk JS ambil dari atribut/`route()` yang di-render Blade.

---

## 6. Menjalankan berdampingan dengan LMS-SYNC
- Folder terpisah, **repo terpisah** (`cbt-sync`), **database terpisah** (`cbt_sync`).
- Subfolder berbeda: `/lms-sync` dan `/cbt-sync` → masing-masing Alias ke `public/`-nya.
- `.env` masing-masing dengan `APP_URL` sesuai subfolder.

---

## 7. Troubleshooting

| Gejala | Penyebab & Solusi |
|---|---|
| Halaman 500 kosong | `APP_DEBUG=false` menyembunyikan detail — cek `storage/logs/laravel.log`. Pastikan `storage/` & `bootstrap/cache/` writable, `APP_KEY` terisi. |
| CSS/JS/gambar 404, subfolder hilang di URL | Subfolder tidak diarahkan ke `public/`. Perbaiki Alias/symlink (bagian 3.6). Jangan cache route sebelum `.env` benar. |
| Link/menu mengarah ke root (tanpa `/cbt-sync`) | Sama seperti di atas — app tidak disajikan dari `public/` di subfolder. Setelah benar, `php artisan optimize:clear`. |
| Gambar soal/opsi tidak muncul | Jalankan `php artisan storage:link`; pastikan folder `storage/app/public` ada & permission benar. |
| URL jadi `http://` di halaman HTTPS | Set `APP_URL=https://...` + `trustProxies` (bagian 3.7). |
| Role/menu Kepala Sekolah tak muncul | Jalankan `db:seed --class=RolePermissionSeeder --force`. |

---

## 8. Dependensi khusus yang perlu ada di server
Sudah tercantum di `composer.json` (ter-install via `composer install`):
- `phpoffice/phpspreadsheet` — ekspor/impor Excel & template soal
- `phpoffice/phpword` — template soal Word (butuh ekstensi **zip** & **xml**)
- `dompdf/dompdf` — pembuatan PDF

Pastikan ekstensi PHP `zip`, `gd`, `xml`, `pdo_pgsql` aktif di server.
