# Introduction

REST API backend untuk aplikasi DonorYuks — platform digital yang menghubungkan pendonor darah dengan penerima/pasien secara real-time dan berbasis lokasi.

Fitur utama: autentikasi (Sanctum), profil & kartu donor digital, geo-matching permintaan darah (Haversine), booking event donor, gamifikasi reward, dan AI Chatbot kesehatan "Bloody".

<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>

Selamat datang di dokumentasi API **DonorYuks**.

<aside>Hampir seluruh endpoint membutuhkan autentikasi **Bearer Token** (Sanctum). Dapatkan token dengan memanggil `POST /api/login` atau `POST /api/register`, lalu gunakan `Authorization: Bearer {token}`. Token juga berlaku sebagai kunci untuk mencoba endpoint dari halaman dokumentasi ini.</aside>

