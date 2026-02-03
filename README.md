# 📄 e-Struk – Sistem Indexing & Pencarian Struk

## 📌 Gambaran Umum

**e-Struk** adalah sistem **indexing struk berbasis Laravel + SQLite** yang dirancang untuk:

* Mengindeks **file struk TXT** dalam jumlah besar
* Mendukung **incremental indexing** untuk tahun berjalan
* Mendukung **arsip tahunan** yang bisa di‑rebuild kapan saja
* Menyediakan **pencarian cepat** berdasarkan nomor, tanggal, kassa, dan isi struk

Folder fisik struk dianggap sebagai **source of truth**. Database hanya berperan sebagai **index & cache terstruktur**.

---

## 🧠 Konsep Arsitektur

### 1️⃣ Source of Truth

* File struk **TXT** di filesystem (`estruk`, `estruk2025`, dll)
* File **tidak pernah dihapus otomatis**

### 2️⃣ Database (SQLite)

* Tabel `struk_index`
* Menyimpan metadata:

  * tahun
  * key (kassa.nomor)
  * mtime
  * path file
  * content_index (opsional, untuk search isi)

Database **boleh dihapus / dibangun ulang kapan saja**.

### 3️⃣ Mode Indexing

| Mode        | Tahun          | Cara                                     |
| ----------- | -------------- | ---------------------------------------- |
| Incremental | Tahun berjalan | Jalan otomatis tiap jam (cooldown 1 jam) |
| Arsip       | Tahun lama     | Full build sekali / manual               |

---

## ⚙️ Teknologi

* PHP 8.x
* Laravel 12
* SQLite (index)
* Filesystem lokal
* Laravel Scheduler & Artisan Command

---

## 📂 Struktur Direktori Penting

```
app/
 ├── Services/
 │    └── StrukIndexService.php   # Core indexing logic
 └── Console/Commands/
      ├── StrukIndexCommand.php   # Entry point indexing
      ├── StrukBuildContentIndex.php
      ├── StrukFillKeyPrefix.php
      └── SqliteMaintenance.php

storage/
 └── app/struk/
      ├── 2026.index.json
      ├── 2026.meta.json
      └── 2026.status.json
```

---

## ▶️ Cara Menjalankan Project

### 1️⃣ Instalasi

```bash
git clone <repository-url>
cd e-struk
composer install
cp .env.example .env
php artisan key:generate
```

Pastikan database SQLite sudah dikonfigurasi di `.env`.

---

## 🚀 Indexing Struk

### ▶ Tahun Berjalan (Incremental)

```bash
php artisan struk:index
```

Ciri:

* Maksimal jalan **1x per jam**
* Aman dipanggil scheduler
* Hanya memproses file baru / berubah

---

### ▶ Tahun Arsip (Full Build)

```bash
php artisan struk:index 2024
```

Ciri:

* Build ulang total
* Biasanya hanya 1x atau saat recovery

---

## 🔍 Content Index (Search Isi Struk)

Digunakan untuk search kata kunci di dalam struk.

```bash
php artisan struk:build-content-index
```

* Jalan bertahap (chunk)
* Aman dihentikan & dilanjutkan

---

## 🧹 Cleanup Tahunan (DATABASE ONLY)

Saat pergantian tahun, **data lama di database dihapus**, tetapi **file fisik tetap ada**.

```bash
php artisan struk:cleanup-db
```

Aturan:

* Tahun ≤ (tahun sekarang − 2) → dihapus dari DB
* Tahun lalu → dipertahankan

File TXT tetap bisa di‑index ulang kapan saja.

---

## ⏱️ Scheduler Produksi (Rekomendasi)

```php
Schedule::command('struk:cleanup-db')
    ->yearlyOn(1, 1, '00:05')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('struk:index')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('struk:build-content-index')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('sqlite:maintenance')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping()
    ->runInBackground();
```

---

## 📝 Logging

Semua proses dicatat ke channel:

```
struk_index
```

Log mencakup:

* Batch processing
* Insert / update
* Cleanup tahunan
* Error & recovery

---

## 🛡️ Prinsip Keamanan Data

* File fisik **tidak pernah dihapus otomatis**
* Database bisa direbuild kapan saja
* Scheduler aman dari double-run
* Cocok untuk sistem jangka panjang

---

## 👨‍💻 Author

Dikembangkan oleh **Aslam Abdika**
Untuk kebutuhan internal & sistem arsip jangka panjang.

---

## 📄 Lisensi

Internal / Private – disesuaikan dengan kebutuhan organisasi.
