# SIM TI — Dokumen Serah Terima Project

Dokumen ini adalah paket tunggal untuk memindahkan konsep SIM TI ke project baru. Ia menjadi sumber konteks produk sebelum pekerjaan desain, backend, dan frontend dimulai.

## 1. Definisi produk

SIM TI adalah ruang kerja operasional internal untuk mengelola pekerjaan TI, pengetahuan, aset, dokumen, kontrak, lisensi, dan kepatuhan dalam satu konteks kerja.

Project management adalah fondasi pertama. Setiap pekerjaan dapat dihubungkan dengan komentar, bukti, aset, dokumen, keputusan, dan riwayat perubahan.

SIM TI bukan pengganti otomatis semua aplikasi existing. Untuk setiap domain, tentukan lebih dahulu sistem mana yang menjadi sumber data utama. SIM TI dapat menyimpan tautan, status, bukti, atau hasil sinkronisasi.

## 2. Masalah yang ingin diselesaikan

- Pekerjaan tersebar di chat, email, spreadsheet, dan banyak aplikasi.
- PIC, status, tenggat, alasan tertunda, dan bukti penyelesaian sulit ditelusuri.
- Pengetahuan project hilang ketika anggota tim berganti.
- Data aset, dokumen, kontrak, lisensi, dan warranty tidak memiliki konteks pekerjaan.
- Pimpinan harus meminta laporan manual untuk memahami kondisi.
- Auditor membutuhkan riwayat perubahan dan bukti yang jelas.

## 3. Posisi dan prinsip produk

> SIM TI adalah pusat kerja dan keterlacakan operasional TI internal.

Prinsip yang harus dijaga:

- mulai dari pekerjaan nyata;
- satu konteks kerja untuk pekerjaan, bukti, dokumen, dan keputusan;
- sumber data setiap domain harus jelas;
- akses mengikuti workspace, project, unit, peran, dan kepemilikan;
- audit dicatat otomatis untuk perubahan penting;
- status selesai harus dapat didukung bukti;
- fitur dibangun bertahap melalui pilot;
- integrasikan sistem existing sebelum menggantinya.

## 4. Struktur produk

```text
SIM TI
└── Workspace
    ├── Pekerjaan
    ├── Pengetahuan
    ├── Operasional
    ├── Kepatuhan
    ├── Laporan
    └── Pengaturan
```

## 5. Navigasi dua tingkat

### Bilah tingkat pertama

Bilah tetap di sisi paling kiri pada desktop:

```text
[Logo]

Pekerjaan
Pengetahuan
Operasional
Kepatuhan
Laporan

Pengaturan
```

Bilah ini hanya untuk berpindah area bisnis.

### Bilah tingkat kedua

Bilah konteks menampilkan:

- logo dan nama SIM TI;
- pemilih workspace;
- pencarian global;
- notifikasi;
- menu area aktif;
- daftar project atau objek terkait;
- pengaturan workspace;
- profil dan keluar.

### Menu Pekerjaan

```text
Pekerjaan
├── Beranda
├── Pekerjaan saya
├── Semua pekerjaan
├── Ditugaskan kepada saya
├── Terlambat
└── Draf

Workspace
├── Projects
├── Inisiatif
├── Dasbor
├── Tampilan
└── Lainnya
```

### Menu di dalam project

```text
Project
├── Ikhtisar
├── Daftar pekerjaan
├── Papan
├── Kalender
├── Siklus
├── Modul
├── Tampilan
├── Halaman
├── Pembaruan project
└── Pengaturan project
```

Menu fitur yang belum aktif tidak boleh menjadi tombol palsu.

Pada ponsel, bilah kedua menjadi menu geser. Item aktif harus dapat dikenali tanpa mengandalkan warna saja.

## 6. Modul produk

### 6.1 Workspace dan tim

Workspace, anggota, unit, peran, permission, undangan, keamanan akun, dan notifikasi.

### 6.2 Project management

Project overview, daftar pekerjaan, Kanban, kalender, work item, status, workflow, PIC, prioritas, label, deadline, subtugas, checklist, komentar, mention, lampiran, aktivitas, siklus, modul, milestone, release, project update, dan saved view.

### 6.3 Pengetahuan dan permintaan

Halaman, wiki, keputusan, catatan serah terima, intake request, project update, pencarian, dan tautan ke pekerjaan atau bukti.

### 6.4 Operasional TI

Inventaris aset, kode aset, serial number, lokasi, kondisi, pemegang, mutasi, histori, lisensi, alokasi seat, masa berlaku, dokumen pendukung, dan warranty claim.

### 6.5 Dokumen dan kontrak

Arsip, klasifikasi, versi, status draft/final/arsip, pemilik, masa berlaku, vendor, kontrak, perpanjangan, relasi objek, dan penguncian dokumen final.

### 6.6 Kepatuhan dan tata kelola

Kerangka kerja, kontrol, pemilik kontrol, periode penilaian, tenggat, bukti, temuan, rencana perbaikan, pemeriksaan, dan audit trail.

### 6.7 Integrasi dan automasi

CSV import/export, webhook, event delivery, API, sinkronisasi, template project, notifikasi otomatis, dan integrasi HRIS, SSO, helpdesk, monitoring, atau backup setelah source of truth ditetapkan.

### 6.8 Dashboard dan laporan

Pekerjaan saya, pekerjaan terlambat, pekerjaan terblokir, beban kerja, kesehatan project, kondisi aset, lisensi kedaluwarsa, kontrak/warranty, posisi kepatuhan, dan aktivitas audit.

## 7. Model objek

```text
Workspace (backend dapat memakai Team)
├── Members
├── Units
├── Projects
├── Pages
├── Assets
├── Documents
├── Contracts
├── Licenses
├── Compliance frameworks
└── Audit events

Project
├── Members
├── Statuses
├── Labels
├── Cycles
├── Modules
├── Milestones
├── Releases
├── Views
├── Pages
├── Updates
└── Work items

Work item
├── Assignee
├── Requester
├── Subtasks
├── Comments
├── Attachments
├── Relations
├── Activity
├── Linked assets
├── Linked documents
└── Audit events
```

## 8. Peran pengguna

| Peran | Kebutuhan |
| --- | --- |
| Anggota | Mengerjakan pekerjaan, berkomentar, dan mengunggah bukti |
| Pemohon | Mengajukan permintaan dan memantau status |
| PIC | Menjalankan pekerjaan dan menjaga data tetap lengkap |
| Reviewer/lead | Memeriksa hasil dan memberi keputusan |
| Admin project | Mengatur anggota, status, label, tampilan, dan konfigurasi project |
| Admin workspace | Mengatur anggota, unit, role, permission, dan konfigurasi workspace |
| Pengelola aset | Menjaga inventaris, pemegang, lokasi, dan mutasi |
| Pengelola dokumen | Menjaga versi, status, masa berlaku, dan akses |
| Auditor | Membaca audit trail dan bukti sesuai scope |
| Pimpinan | Membaca ringkasan, risiko, beban kerja, dan laporan |

Authorization wajib diperiksa di backend. Menyembunyikan tombol di frontend bukan kontrol akses.

## 9. Alur kerja

### Pekerjaan

```text
Permintaan → Backlog → Todo → In Progress → In Review → Done
```

Pengecualian:

```text
In Progress → Blocked
Status aktif → Canceled
Done → Reopen → In Progress
```

Perubahan ke Blocked, Canceled, Reopen, dan Done dapat meminta alasan atau bukti.

### Aset

```text
Didaftarkan → Divalidasi → Tersedia → Ditugaskan
→ Dipindahkan/Diperbaiki → Dikembalikan → Dipensiunkan
```

### Dokumen

```text
Draft → Ditinjau → Final → Kedaluwarsa/Diarsipkan
```

Dokumen final dibuatkan versi baru jika berubah.

### Kepatuhan

```text
Belum dimulai → Berjalan → Menunggu bukti
→ Terpenuhi/Tidak terpenuhi → Rencana perbaikan
```

## 10. Fitur versi pertama

### Masuk

- workspace dan membership;
- project dan membership;
- work item;
- status, prioritas, PIC, label, dan tenggat;
- tampilan daftar dan Kanban;
- komentar, subtugas, lampiran, dan aktivitas;
- pencarian dan filter;
- dashboard dasar;
- permission dasar;
- audit event perubahan penting.

### Belum masuk

- inventaris aset lengkap;
- kontrak, lisensi, warranty, dan kepatuhan formal;
- approval bertingkat;
- SLA dan eskalasi;
- integrasi eksternal besar;
- tanda tangan elektronik;
- analitik lanjutan;
- automasi workflow bebas.

## 11. Kebutuhan layar utama

### Dashboard workspace

Menjawab pekerjaan yang perlu dilakukan, pekerjaan terblokir/terlambat, project yang perlu perhatian, dan perubahan terbaru.

### Ikhtisar project

Menampilkan tujuan, kesehatan, pekerjaan terbaru, milestone, anggota, dan tautan tampilan kerja.

### Daftar pekerjaan

Untuk mencari, memfilter, mengurutkan, serta mengubah PIC, status, prioritas, dan tenggat.

### Papan

Menampilkan pekerjaan berdasarkan status. Perpindahan kartu harus menjalankan aturan workflow backend dan memberi umpan balik ketika gagal.

### Detail work item

Pusat description, properti, subtugas, komentar, lampiran, aktivitas, relasi, dan bukti.

### Detail objek operasional

Setiap aset, dokumen, kontrak, lisensi, dan kontrol memiliki metadata, relasi, histori, serta tindakan yang diizinkan.

## 12. Keadaan antarmuka wajib

Setiap layar harus memiliki keadaan berhasil, kosong, loading, validasi gagal, tidak diizinkan, jaringan gagal, berhasil menyimpan, konflik data, diarsipkan, dan tidak ditemukan.

## 13. Aturan data dan audit

- Setiap objek memiliki pemilik dan scope akses.
- Work item memakai identifier yang mudah dibaca, misalnya `DEV-123`.
- Objek penting diarsipkan atau memakai soft delete bila histori harus dipertahankan.
- Audit event menyimpan actor, aksi, waktu, objek, request identifier, nilai sebelum, dan nilai sesudah bila relevan.
- Lampiran privat selalu diotorisasi saat diunduh.
- Perubahan status, PIC, prioritas, deadline, role, permission, dokumen final, mutasi aset, dan approval dicatat.
- Waktu disimpan konsisten dan ditampilkan mengikuti timezone workspace.

## 14. Tahapan pengembangan

### Fase 0 — Fondasi

Rapikan navigasi dua tingkat, istilah produk, workspace/project, role minimum, dan audit event.

### Fase 1 — Inti seperti Plane

Selesaikan alur create → assign → work → review → done; list, Kanban, detail, komentar, lampiran, aktivitas, dan pilot pada satu project Development.

### Fase 2 — Pengetahuan dan permintaan

Halaman, keputusan, intake request, project update, dan pencarian lintas konteks.

### Fase 3 — Aset dan dokumen

Inventaris aset, mutasi, dokumen, kontrak, lisensi, warranty, pengingat, dan histori.

### Fase 4 — Kepatuhan dan integrasi

Kontrol, bukti, pemeriksaan, laporan audit, integrasi existing, notifikasi, dan automasi.

## 15. Ukuran keberhasilan

- Pekerjaan aktif tercatat di SIM TI.
- Pekerjaan aktif memiliki PIC, status, dan tenggat yang masuk akal.
- Status dan bukti dapat ditemukan tanpa laporan manual.
- Pekerjaan terlambat dan terblokir diketahui lebih awal.
- Perubahan penting dapat ditelusuri ke actor dan waktu.
- Objek operasional memiliki pemilik ketika modulnya aktif.
- Pilot menghasilkan keputusan perbaikan yang dapat diprioritaskan.

## 16. Keputusan yang sudah disepakati

- SIM TI adalah ruang kerja operasional internal.
- Project management adalah fondasi pertama.
- Navigasi memakai dua tingkat: area utama dan konteks aktif.
- Bahasa antarmuka utama adalah bahasa Indonesia.
- Project dan work item menjadi pusat fase pertama.
- Aset, dokumen, kontrak, lisensi, dan kepatuhan menjadi modul lanjutan.
- Sistem existing diintegrasikan sebelum digantikan.

## 17. Keputusan yang masih terbuka

- Nama final setiap area.
- Apakah Pengetahuan aktif sejak fase pertama atau setelah core stabil.
- Struktur unit organisasi dan batas akses antarunit.
- Role final workspace dan project.
- Retensi dokumen, bukti, dan audit event.
- Sistem existing yang menjadi source of truth per domain.
- Kanal notifikasi eksternal yang diizinkan.

## 18. Catatan teknis untuk project baru

Jika project baru memakai stack yang sama, pertahankan batas berikut:

- Laravel sebagai sumber aturan bisnis dan authorization;
- Inertia dan Svelte untuk halaman aplikasi;
- Wayfinder untuk pemanggilan route dari frontend;
- Form Request untuk validasi;
- Policy untuk akses objek;
- action/service untuk operasi bisnis;
- controller tipis;
- pengujian permission, direct-object reference, upload, audit, dan ekspor.

Jika stack berbeda, pertahankan perilakunya, bukan nama teknologinya.

## 19. Cara memakai dokumen ini

1. Bawa file ini ke project baru sebagai `SIM_TI_PROJECT_HANDOFF.md`.
2. Baca bagian 1–6 sebelum membuat struktur folder atau navigasi.
3. Jadikan bagian 10 sebagai batas versi pertama.
4. Tandai perubahan besar sebagai keputusan baru, lalu perbarui bagian 16–17.
5. Jangan menambahkan fitur roadmap ke versi pertama tanpa mengubah batasan scope.
