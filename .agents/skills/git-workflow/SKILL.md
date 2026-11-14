---
name: git-workflow
description: Aturan untuk git status, add, commit, dan push, termasuk format pesan commit (feat, fix, docs, refactor, dan lainnya). Baca sebelum membuat commit atau push.
languange: Indonesian languange only.
---

# Git Workflow

Skill ini mengatur cara agent menjalankan `git status`, `git add`, `git commit`, dan `git push` di repository ini. Ikuti urutan dan format di bawah. Jika user meminta sesuatu yang bertentangan dengan aturan ini, tanyakan dulu sebelum melakukannya.

## Alur Standar

1. Cek kondisi repository:
   ```bash
   git status
   git branch --show-current
   ```
2. Tinjau perubahan sebelum staging:
   ```bash
   git diff
   ```
3. Stage file secara spesifik:
   ```bash
   git add path/ke/file1 path/ke/file2
   ```
   Hindari `git add .` atau `git add -A` kecuali sudah memastikan semua perubahan memang harus masuk commit.
4. Commit dengan format pesan di bawah.
5. Push:
   ```bash
   git push -u origin <nama-branch>
   ```
   Gunakan `-u` hanya jika branch belum punya upstream. Cek dengan `git status` (akan muncul info tracking).
6. Jika user meminta PR, gunakan GitHub CLI:
   ```bash
   gh pr create --title "<judul>" --body "<deskripsi>"
   ```

## Format Pesan Commit (Conventional Commits)

```
<type>(<scope>): <deskripsi singkat>

<body opsional>

<footer opsional>
```

`scope` opsional. Contoh scope: nama modul, folder, atau komponen (`auth`, `api`, `ui`).

### Tipe yang Dipakai

| Type | Kapan dipakai |
|---|---|
| `feat` | Menambah fitur baru untuk user |
| `fix` | Memperbaiki bug |
| `docs` | Hanya perubahan dokumentasi (README, komentar dokumentasi, skill, AGENTS.md) |
| `style` | Format saja (spasi, titik koma, indentasi). Tidak mengubah logika |
| `refactor` | Mengubah struktur kode tanpa mengubah perilaku atau menambah fitur/memperbaiki bug |
| `perf` | Perubahan yang meningkatkan performa |
| `test` | Menambah atau memperbaiki test |
| `build` | Perubahan sistem build atau dependency (`package.json`, `requirements.txt`, Dockerfile) |
| `ci` | Perubahan konfigurasi CI (GitHub Actions, dan sejenisnya) |
| `chore` | Pekerjaan rutin yang tidak masuk kategori lain (update config kecil, cleanup) |
| `revert` | Membatalkan commit sebelumnya |

Jika perubahan masuk lebih dari satu tipe, pisahkan menjadi beberapa commit.

### Aturan Deskripsi

- Gunakan bahasa Inggris dalam bentuk imperatif: `add`, `fix`, `remove`, bukan `added`, `fixes`, `removed`.
- Huruf kecil di awal.
- Tanpa titik di akhir.
- Maksimal 72 karakter untuk baris pertama.
- Jelaskan *apa* yang berubah. Jelaskan *mengapa* di body jika perlu.

### Breaking Change

Tambahkan `!` setelah type/scope, atau footer `BREAKING CHANGE:`:

```
feat(api)!: change auth response format

BREAKING CHANGE: field `token` diganti menjadi `access_token`.
```

### Contoh Benar

```
feat(auth): add google oauth login
fix(cart): prevent negative quantity on update
docs: update setup instructions for windows
refactor(user): extract validation into separate module
test(payment): add unit tests for refund flow
chore: remove unused dependencies
```

### Contoh Salah

```
update               → tidak ada type, tidak jelas
Fixed bug.           → tidak ada type, kapital, ada titik
feat: Added login    → past tense, kapital
feat: lots of changes → terlalu umum, pecah menjadi beberapa commit
```

## Penamaan Branch

Format: `<type>/<deskripsi-singkat>`, huruf kecil, pisah dengan `-`.

```
feat/google-oauth
fix/cart-negative-quantity
docs/setup-windows
```

Jangan commit langsung ke `main` atau `master` kecuali user secara eksplisit memintanya.

## Larangan dan Pengamanan

- Jangan `git push --force` ke branch bersama. Jika benar-benar diperlukan, gunakan `--force-with-lease` dan minta konfirmasi user dulu.
- Jangan force push ke `main` atau `master` dalam kondisi apa pun.
- Jangan commit file rahasia: `.env`, kunci API, token, kredensial, `*.pem`, `*.key`. Jika ditemukan dalam staging, keluarkan dengan `git reset <file>` dan beri tahu user.
- Jangan commit file besar yang tidak relevan (binary, `node_modules/`, `dist/`, `__pycache__/`). Pastikan sudah ada di `.gitignore`.
- Jangan `git commit --amend` atau `git rebase` pada commit yang sudah di-push tanpa konfirmasi user.
- Jangan bypass hook dengan `--no-verify` kecuali user memintanya.
- Jika `git push` gagal karena konflik atau branch tertinggal, jalankan `git status` dan laporkan ke user. Jangan langsung menyelesaikan konflik tanpa persetujuan.

## Ringkasan Perintah

| Tujuan | Perintah |
|---|---|
| Cek status | `git status` |
| Lihat perubahan | `git diff` / `git diff --staged` |
| Stage file | `git add <file>` |
| Unstage file | `git reset <file>` |
| Commit | `git commit -m "<type>(<scope>): <deskripsi>"` |
| Push pertama kali | `git push -u origin <branch>` |
| Push berikutnya | `git push` |
| Buat PR | `gh pr create` |
| Lihat PR | `gh pr view` |
