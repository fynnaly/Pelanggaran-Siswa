---
name: git-workflow
description: Panduan agent untuk git status, git add, git commit, dan git push, termasuk format pesan commit (feat, fix, docs, refactor, dll), penamaan branch, dan pembuatan PR dengan GitHub CLI (gh). Baca file ini sebelum menjalankan perintah git apa pun yang mengubah repository.
---

# Git Workflow

## Bahasa

Semua pesan commit, judul PR, dan deskripsi PR ditulis dalam bahasa Indonesia.
Kata kunci tipe (`feat`, `fix`, `docs`, dst.) tetap dalam bahasa Inggris karena merupakan standar.

## Tujuan Skill

Skill ini membuat agent:
1. Mengecek kondisi repository sebelum mengubah apa pun.
2. Hanya memasukkan perubahan yang relevan ke commit.
3. Menulis pesan commit yang konsisten.
4. Push dengan aman.
5. Membuat PR jika diminta.

## Langkah Wajib (Urutan)

Jalankan langkah ini berurutan. Jangan lompati.

### 1. Cek kondisi repository

```bash
git status
git branch --show-current
git remote -v
```

- Pastikan berada di branch yang benar.
- Jika branch aktif adalah `main` atau `master`, jangan commit langsung. Buat branch baru (lihat bagian Penamaan Branch) kecuali user memintanya secara eksplisit.

### 2. Tinjau perubahan

```bash
git diff
git diff --staged
```

Baca perubahan. Pastikan tidak ada:
- Kode debug (`console.log`, `print` sementara, `debugger`).
- Kode yang dikomentari tanpa alasan.
- Kredensial atau data sensitif.

### 3. Stage file secara spesifik

```bash
git add path/ke/file1 path/ke/file2
```

Aturan:
- Sebutkan file satu per satu atau per folder yang relevan.
- Jangan pakai `git add .` atau `git add -A` kecuali sudah memeriksa setiap file di `git status`.
- Jika tidak yakin apakah sebuah file harus masuk, tanyakan user.

### 4. Commit

```bash
git commit -m "<type>(<scope>): <deskripsi>"
```

Untuk pesan dengan body panjang, gunakan:

```bash
git commit -F pesan.txt
```

Setelah commit, hapus `pesan.txt` jika file itu dibuat sementara.

### 5. Push

Push pertama kali untuk branch baru:

```bash
git push -u origin <nama-branch>
```

Push berikutnya:

```bash
git push
```

Gunakan `-u` hanya jika branch belum punya upstream. Cek dengan:

```bash
git status
```

Jika ada tulisan `Your branch is ahead of 'origin/...'`, berarti commit sudah siap di-push.

### 6. Buat Pull Request (jika diminta)

```bash
gh pr create --title "<judul>" --body "<deskripsi>"
```

Lihat PR yang sudah ada:

```bash
gh pr view
gh pr status
```

## Format Pesan Commit

Format ini mengikuti Conventional Commits v1.0.0.

```
<type>(<scope>): <deskripsi>

<body opsional>

<footer opsional>
```

### Komponen

| Bagian | Wajib | Keterangan |
|---|---|---|
| `type` | Ya | Kategori perubahan. Lihat tabel di bawah. |
| `scope` | Tidak | Modul atau area yang berubah, misalnya `auth`, `api`, `ui`, `cart`. Gunakan huruf kecil. |
| `deskripsi` | Ya | Ringkasan singkat dalam bahasa Indonesia. |
| `body` | Tidak | Penjelasan detail: apa yang berubah dan mengapa. Pisah dari baris pertama dengan satu baris kosong. |
| `footer` | Tidak | Metadata seperti `BREAKING CHANGE:` atau nomor issue (`Closes #12`). |

### Tipe Commit

| Type | Gunakan saat |
|---|---|
| `feat` | Menambah fitur baru yang dapat dipakai user |
| `fix` | Memperbaiki bug |
| `docs` | Hanya dokumentasi: README, komentar dokumentasi, skill, AGENTS.md |
| `style` | Hanya format: spasi, indentasi, titik koma. Logika tidak berubah |
| `refactor` | Mengubah struktur kode tanpa mengubah perilaku dan tanpa memperbaiki bug |
| `perf` | Meningkatkan performa |
| `test` | Menambah atau memperbaiki test |
| `build` | Sistem build atau dependency: `package.json`, `requirements.txt`, Dockerfile |
| `ci` | Konfigurasi CI/CD: GitHub Actions, GitLab CI, dll. |
| `chore` | Pekerjaan rutin lain: update config kecil, cleanup, bump versi |
| `revert` | Membatalkan commit sebelumnya |

Jika satu perubahan mencakup lebih dari satu tipe, pecah menjadi beberapa commit.

### Aturan Deskripsi

1. Gunakan kalimat perintah (imperatif) dalam bahasa Indonesia: `tambah`, `perbaiki`, `hapus`, `ubah`, `pindahkan`.
2. Huruf kecil di awal.
3. Tanpa titik di akhir.
4. Maksimal 72 karakter untuk baris pertama (termasuk type dan scope).
5. Jelaskan apa yang berubah, bukan bagaimana cara mengubahnya.
6. Jika ragu soal detail, tulis detail itu di body.

### Breaking Change

Gunakan salah satu cara berikut:

Dengan tanda `!`:
```
feat(api)!: ubah format respons login
```

Dengan footer:
```
feat(api): ubah format respons login

BREAKING CHANGE: field `token` diganti menjadi `access_token`.
```

Gunakan keduanya jika perlu penjelasan lebih lanjut.

### Contoh Benar

```
feat(auth): tambah login dengan google oauth
fix(cart): cegah jumlah barang menjadi negatif
docs: perbarui panduan instalasi untuk windows
refactor(user): pindahkan validasi ke modul terpisah
test(payment): tambah unit test untuk alur refund
chore: hapus dependency yang tidak dipakai
perf(search): cache hasil query yang sering dipakai
ci: tambah workflow test otomatis di pull request
```

Contoh dengan body:

```
fix(login): hindari sesi kedaluwarsa tanpa peringatan

Sebelumnya token yang sudah kedaluwarsa tetap dianggap valid
selama 5 menit karena selisih waktu server tidak diperhitungkan.

Closes #42
```

### Contoh Salah

| Salah | Alasan |
|---|---|
| `update` | Tidak ada type, tidak jelas apa yang berubah |
| `fix bug.` | Tidak ada format, ada titik di akhir |
| `feat: Menambah login` | Kapital di awal, bentuk tidak imperatif |
| `feat: berbagai perubahan` | Terlalu umum, pecah menjadi beberapa commit |
| `Perbaiki bug login dan tambah halaman profil` | Dua perubahan dalam satu commit |

## Penamaan Branch

Format: `<type>/<deskripsi-singkat>`

- Huruf kecil semua.
- Pisahkan kata dengan `-`.
- Maksimal sekitar 50 karakter.

Contoh:
```
feat/google-oauth
fix/cart-negative-quantity
docs/setup-windows
refactor/user-validation
```

## Larangan dan Pengamanan

Agent wajib mengikuti aturan ini tanpa pengecualian kecuali user memberi konfirmasi eksplisit.

| Situasi | Tindakan |
|---|---|
| Diminta commit langsung ke `main` atau `master` | Tolak, buat branch baru, lalu tanyakan user |
| Diminta `git push --force` ke `main` atau `master` | Tolak. Tidak ada pengecualian |
| Diminta force push ke branch lain | Gunakan `git push --force-with-lease`, dan minta konfirmasi user dulu |
| File `.env`, kunci API, token, `*.pem`, `*.key`, kredensial masuk staging | Keluarkan dengan `git reset <file>`, beri tahu user |
| `node_modules/`, `dist/`, `__pycache__/`, file binary besar masuk staging | Keluarkan, pastikan ada di `.gitignore` |
| Diminta `git commit --amend` atau `git rebase` pada commit yang sudah di-push | Minta konfirmasi user dulu |
| Diminta `--no-verify` | Jangan lakukan kecuali user memintanya eksplisit |
| `git push` gagal karena konflik atau branch tertinggal | Jalankan `git status`, laporkan ke user, jangan selesaikan konflik sendiri tanpa persetujuan |
| Commit tanpa perubahan | Jangan buat commit kosong. Laporkan ke user |

## Ringkasan Perintah

| Tujuan | Perintah |
|---|---|
| Cek status | `git status` |
| Cek branch aktif | `git branch --show-current` |
| Lihat perubahan | `git diff` |
| Lihat perubahan yang sudah di-stage | `git diff --staged` |
| Stage file | `git add <file>` |
| Batalkan stage | `git reset <file>` |
| Commit | `git commit -m "<type>(<scope>): <deskripsi>"` |
| Push pertama kali | `git push -u origin <branch>` |
| Push berikutnya | `git push` |
| Buat PR | `gh pr create --title "<judul>" --body "<deskripsi>"` |
| Lihat PR | `gh pr view` |
| Daftar PR | `gh pr list` |

## Referensi

Agent boleh merujuk sumber ini jika aturan di atas tidak menjawab suatu kasus:
- Conventional Commits v1.0.0: https://www.conventionalcommits.org/en/v1.0.0/
- Angular Commit Message Guidelines: https://github.com/angular/angular/blob/main/CONTRIBUTING.md
- Dokumentasi Git: https://git-scm.com/docs
- Manual GitHub CLI: https://cli.github.com/manual/

Jika referensi bertentangan dengan file ini, ikuti file ini.
