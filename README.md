# ROMANTIK Web

Aplikasi web prototipe untuk pemeriksaan formulir rekomendasi kegiatan statistik ROMANTIK BPS menggunakan model **Hybrid AI** yang mengintegrasikan **Rule-Based System (RBS)** dan **fine-tuned Large Language Model (LLM)**.

Aplikasi ini adalah antarmuka pengguna untuk [ROMANTIK AI API](https://github.com/miraisrg/romantik-ai-api), yang menjalankan pemeriksaan RBS dan generasi catatan oleh LLM.

Repositori ini merupakan produk skripsi:

> **Pengembangan Model Hybrid AI Berbasis Fine-Tuned LLM dan Rule-Based System untuk Otomatisasi Pemeriksaan Formulir Kegiatan Statistik (Studi Kasus: ROMANTIK BPS)**
>
> Amir Mumtaz Siregar (222212493), D-IV Komputasi Statistik, Politeknik Statistika STIS
> Dosen pembimbing: Prof. Setia Pramana, S.Si., M.Sc., Ph.D.

> **Catatan:** Sistem ini merupakan *proof of concept* alat bantu pemeriksa, bukan pengganti keputusan pemeriksa.

## Fitur

- Unggah formulir ROMANTIK dalam format JSON (maksimal 10 MB)
- Pemeriksaan formulir melalui ROMANTIK AI API
- Penyajian temuan RBS beserta *evidence*
- Informasi aturan yang tidak dapat dievaluasi (*not_evaluable*)
- Penyajian catatan pemeriksaan Hybrid AI
- Katalog aturan pemeriksaan
- Unduh hasil pemeriksaan dalam format PDF

## Teknologi

- Laravel 13 (PHP 8.3+)
- Blade, Tailwind CSS 4, dan Vite
- barryvdh/laravel-dompdf untuk ekspor PDF
- Pest untuk pengujian

## Struktur Utama

```
app/
├── Http/Controllers/
│   ├── ReviewController.php       # Unggah, proses, reset, unduh PDF
│   └── RuleCatalogController.php  # Katalog aturan
├── Services/
│   ├── RomantikAiClient.php       # Komunikasi dengan ROMANTIK AI API
│   ├── ReviewHtmlFormatter.php    # Format catatan Hybrid AI
│   ├── RbsNotEvaluableFormatter.php
│   └── RuleCatalogService.php
└── Exceptions/                    # Error dan timeout API
resources/views/
├── review/                        # Halaman pemeriksaan dan template PDF
└── rules/                         # Halaman katalog aturan
storage/app/data/rule_catalog.json # Data katalog aturan
```

## Instalasi Lokal

Kebutuhan: PHP 8.3+, Composer, Node.js, dan npm.

```bash
git clone https://github.com/miraisrg/romantik-web.git
cd romantik-web

composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate

npm run build
```

## Konfigurasi

Atur alamat ROMANTIK AI API pada file `.env`:

```env
ROMANTIK_AI_API_URL=https://alamat-api-anda
ROMANTIK_AI_TIMEOUT=120
```

`ROMANTIK_AI_TIMEOUT` adalah batas waktu tunggu (detik) untuk setiap permintaan pemeriksaan. Generasi catatan oleh LLM dapat memakan waktu cukup lama, sehingga nilai ini sebaiknya tidak terlalu kecil.

## Menjalankan Aplikasi

```bash
php artisan serve
```

Buka `http://localhost:8000`.

## Halaman

| Method | Route | Keterangan |
|---|---|---|
| GET | `/` | Halaman utama pemeriksaan |
| POST | `/review/upload` | Unggah formulir JSON |
| POST | `/review/process` | Kirim formulir ke ROMANTIK AI API |
| GET/POST | `/review/reset` | Mulai pemeriksaan baru |
| GET | `/review/download/pdf` | Unduh hasil pemeriksaan (PDF) |
| GET | `/rules` | Katalog aturan |

## Pengujian

```bash
php artisan test
```

Pengujian komunikasi API menggunakan respons tiruan, sehingga tidak memerlukan ROMANTIK AI API yang aktif.

## Deployment

Repositori menyediakan dua opsi deployment:

- **cPanel**: menggunakan `.cpanel.yml`. Aset frontend sudah di-*build* ke `public/build` karena cPanel tidak menjalankan npm.
- **Docker / Render**: menggunakan `Dockerfile` dan `render.yaml`.

Pada kedua opsi, atur `APP_KEY`, `APP_URL`, dan `ROMANTIK_AI_API_URL` sebagai *environment variable*.