# 📦 Sistem Pengajuan Peminjaman Barang Milik Negara (BMN)

## 1. Project Overview

Sistem Pengajuan Peminjaman Barang Milik Negara (BMN) merupakan aplikasi berbasis web yang dikembangkan untuk mendigitalisasi proses pengajuan peminjaman aset atau Barang Milik Negara di lingkungan instansi. Sistem ini membantu proses pengajuan menjadi lebih terstruktur, terdokumentasi, dan mudah dipantau dibandingkan proses manual.

Melalui sistem ini pengguna dapat melakukan pengajuan peminjaman BMN, memilih barang yang tersedia sesuai periode peminjaman, mengunggah dokumen pendukung, melihat status pengajuan, hingga melakukan upload bukti serah dan bukti terima barang.

Repository ini berisi source code utama, dokumentasi teknis, dokumentasi Quality Assurance (QA), serta beberapa screenshot tampilan sistem sebagai bagian dari portofolio pengembangan aplikasi.

---

# 2. Latar Belakang

Proses pengajuan peminjaman BMN pada banyak instansi masih dilakukan secara manual menggunakan formulir atau komunikasi melalui pesan singkat sehingga sering menimbulkan beberapa kendala, seperti:

- Data pengajuan tidak terdokumentasi dengan baik.
- Sulit mengetahui ketersediaan barang pada periode tertentu.
- Risiko terjadinya benturan jadwal peminjaman.
- Proses pencarian riwayat peminjaman memerlukan waktu yang lama.
- Status peminjaman tidak dapat dipantau secara langsung.
- Dokumentasi serah terima barang belum terintegrasi.

Untuk mengatasi permasalahan tersebut dikembangkan sistem berbasis web yang mampu mengelola seluruh proses peminjaman BMN secara digital.

---

# 3. Tujuan Pengembangan

Pengembangan sistem ini bertujuan untuk:

- Mendigitalisasi proses pengajuan peminjaman BMN.
- Mempermudah proses pencarian dan pemilihan barang yang tersedia.
- Mengurangi kesalahan administrasi selama proses peminjaman.
- Mencatat seluruh aktivitas peminjaman secara terpusat.
- Menyediakan monitoring status pengajuan secara real-time.
- Mendukung proses serah terima barang secara terdokumentasi.
- Mempermudah proses pelaporan dan rekapitulasi data peminjaman.

---

# 4. Fitur Utama

### Pengajuan BMN

- Form pengajuan peminjaman barang
- Pencarian pegawai berdasarkan nama atau NIP
- Pengisian Unit Kerja otomatis
- Pemilihan tanggal pinjam dan tanggal kembali
- Perhitungan lama peminjaman otomatis
- Validasi tanggal peminjaman
- Input nomor WhatsApp
- Input keterangan peminjaman
- Upload dokumen pendukung

### Manajemen Barang

- Pencarian BMN berdasarkan:
  - Nama Barang
  - Kode Barang
  - NUP
  - Merk
  - Spesifikasi
- Filter barang yang tersedia sesuai periode peminjaman
- Multi select BMN
- Validasi barang yang sedang dipinjam

### Dashboard

- Daftar seluruh pengajuan
- Pencarian data pengajuan
- Filter data
- Pagination
- Monitoring status pengajuan

### Detail Pengajuan

- Informasi lengkap peminjaman
- Detail barang yang dipinjam
- Preview dokumen pendukung
- Preview bukti serah
- Preview bukti terima

### Edit Pengajuan

- Mengubah data pengajuan
- Mengubah tanggal peminjaman
- Mengganti dokumen pendukung

### Pembatalan Pengajuan

- Konfirmasi pembatalan
- Perubahan status menjadi **Batal**

### Upload Bukti

- Upload bukti serah barang
- Upload bukti terima barang
- Perubahan status otomatis sesuai proses bisnis

### Export Data

- Export data pengajuan ke Microsoft Excel
- Export mengikuti hasil filter pencarian

---

# 5. Perubahan dari Sistem Sebelumnya

Sistem ini merupakan **pengembangan baru (new development)** dan bukan hasil modifikasi dari aplikasi sebelumnya.

Seluruh proses bisnis, struktur database, modul utama, serta antarmuka dirancang dan dikembangkan secara khusus untuk mendukung proses pengajuan peminjaman Barang Milik Negara (BMN) secara digital sesuai kebutuhan instansi.

---

# 6. Teknologi yang Digunakan

## Frontend

- HTML5
- CSS3
- Bootstrap
- JavaScript
- jQuery

## Backend

- PHP Native

## Database

- MySQL

## Library

- DataTables
- SweetAlert
- Select2

## Tools

- XAMPP
- Visual Studio Code
- Git
- GitHub
- Microsoft Excel

---

# 7. Struktur Repository

```
BMN-Loan-System/
│
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── vendor/
│
├── config/
│
├── controllers/
│
├── models/
│
├── views/
│
├── uploads/
│   ├── documents/
│   ├── bukti-serah/
│   └── bukti-terima/
│
├── database/
│   └── database.sql
│
├── documentation/
│   ├── Technical Documentation.pdf
│   ├── Flowchart.pdf
│   └── User Manual.pdf
│
├── qa/
│   ├── Test Summary.xlsx
│   ├── Requirement Mapping.xlsx
│   ├── RTM.xlsx
│   ├── Test Scenario.xlsx
│   ├── Test Case.xlsx
│   ├── Bug Report.xlsx
│   ├── UAT.xlsx
│   └── Test Metrics.xlsx
│
├── screenshots/
│
├── index.php
│
└── README.md
```

---

# 9. Dokumentasi QA

Repository ini juga menyertakan dokumentasi Quality Assurance sebagai bagian dari portofolio pengujian sistem.

Dokumentasi meliputi:

- Test Summary
- Functional Requirement
- Non Functional Requirement
- Requirement Traceability Matrix (RTM)
- Test Scenario
- Test Case
- Bug Report
- User Acceptance Testing (UAT)
- Test Metrics

Dokumentasi tersebut dibuat untuk memastikan setiap kebutuhan sistem telah tervalidasi melalui proses pengujian yang terdokumentasi dengan baik.

---

# 10. Catatan Pengembangan

Beberapa hal yang diterapkan selama pengembangan sistem:

- Validasi data pada sisi client dan server.
- Validasi ketersediaan BMN berdasarkan periode peminjaman.
- Validasi format dan ukuran dokumen upload.
- Perhitungan otomatis lama peminjaman.
- Penyimpanan transaksi menggunakan mekanisme yang menjaga konsistensi data.
- Monitoring status pengajuan berdasarkan alur proses bisnis.
- Struktur kode dibuat modular agar mudah dipelihara dan dikembangkan.

---

# 11. Pengembang

**Developer**

Pinka Ananda

---

Repository ini dibuat sebagai bagian dari portofolio pengembangan sistem informasi dan dokumentasi Quality Assurance.
