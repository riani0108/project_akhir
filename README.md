# SISTEM INFORMASI PENENTUAN TITIK LOKASI TOWER BTS

## Deskripsi

Aplikasi untuk PEMETAAN LOKASI TOWER BTS dengan perhitungan Line of Sight (LOS) BTS berdasarkan elevasi, fresnel zone, dan earth bulge.

## Tech Stack

- Laravel 10
- PHP 8.2
- MySQL
- JavaScript
- Leaflet.js
- Bootstrap

## Requirements

- PHP >= 8.1
- Composer
- MySQL / MariaDB
- Node.js & NPM
- Web Server (Apache / Nginx)

## Installation

1. Clone repository
    ```bash
    git clone https://github.com/riani0108/project_akhir.git
    ```
2. cd project_akhir
3. composer install
4. npm install
5. cp .env.example .env
6. Atur konfigurasi database di file .env
   DB_DATABASE=nama_database
   DB_USERNAME=username
   DB_PASSWORD=password
7. php artisan key:generate
8. php artisan migrate : migrasi database
9. php artisan serve : jalankan aplikasi

## Third-Party Services

Aplikasi ini menggunakan beberapa layanan pihak ketiga, antara lain:

- OpenStreetMap sebagai penyedia peta dasar
- Leaflet.js untuk visualisasi peta interaktif
- API Elevasi pihak ketiga untuk mendapatkan data ketinggian tanah (jika koneksi internet tersedia)

Seluruh perhitungan LOS, Fresnel Zone, dan Earth Bulge dilakukan secara lokal di sisi server/klien tanpa ketergantungan layanan eksternal.
