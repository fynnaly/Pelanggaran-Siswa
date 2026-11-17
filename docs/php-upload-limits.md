# Batas Upload Foto Profil (PHP)

Agar foto profil sampai **15MB** bisa diunggah, batas bawaan PHP di XAMPP dan sebagainya (laragon) harus
dinaikkan. Nilai bawaan sebelumnya menolak file lebih dari 2MB sebelum request
sampai ke Laravel.

## Perubahan `D:\xampp\php\php.ini`

```ini
upload_max_filesize = 16M
post_max_size = 24M
```

Nilai sebelumnya:

```ini
upload_max_filesize = 2M
post_max_size = 8M
```

- `upload_max_filesize` harus lebih besar dari batas aplikasi (15MB).
- `post_max_size` harus lebih besar dari `upload_max_filesize` karena satu
  request juga membawa field form dan token CSRF.

## Backup

Backup file asli dibuat di:

```
D:\xampp\php\php.ini.bak-20261117
```

## Setelah Mengubah

Restart server agar konfigurasi baru terbaca:

```sh
php artisan serve
```

Jika memakai Apache XAMPP, restart Apache dari XAMPP Control Panel.

## Verifikasi

```sh
php -r "echo 'upload_max_filesize='.ini_get('upload_max_filesize').PHP_EOL.'post_max_size='.ini_get('post_max_size').PHP_EOL;"
```

Hasil yang diharapkan:

```
upload_max_filesize=16M
post_max_size=24M
```

Batas di sisi aplikasi:

- Foto profil pengguna dan siswa: `max:15360` (15MB)
- Import CSV/XLSX: `max:5120` (5MB)
