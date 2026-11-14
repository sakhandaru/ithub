# sitop — PRD MVP

## 1. Ringkasan

sitop adalah workspace operasional internal untuk mengelola pekerjaan TI lintas unit dan project. MVP berfokus pada pengelolaan Project dan Task dari pencatatan sampai selesai, dengan konteks PIC, status, deadline, komentar, bukti, Activity Log, dan Audit Log.

Dokumen ini menurunkan keputusan implementasi dari `SIM_TI_PROJECT_HANDOFF.md`. Handoff tetap menjadi dokumen visi, konteks produk, dan roadmap besar. Dokumen ini menjadi acuan scope MVP dan acceptance criteria.

## 2. Outcome produk

### Outcome utama

Tim Development, Infra, dan Operasional dapat mengelola seluruh pekerjaan Project dari permintaan sampai selesai dalam satu tempat.

### Outcome pendukung

- Pimpinan dapat melihat status, PIC, deadline, Task terlambat, dan Task terblokir tanpa meminta laporan manual.
- Setiap Task memiliki konteks lengkap berupa Project, Pemohon, PIC, status, deadline, komentar, bukti, dan riwayat perubahan.

## 3. Prinsip produk

- Mulai dari pekerjaan nyata.
- Project menjadi konteks utama kumpulan Task.
- Akses mengikuti Workspace, Unit, Project, role, dan membership.
- Authorization selalu diperiksa di backend.
- Task yang selesai harus memiliki catatan penyelesaian atau bukti.
- Perubahan penting dicatat otomatis.
- Fitur yang belum aktif tidak ditampilkan sebagai tombol palsu.
- Fitur lanjutan dibangun setelah alur inti terbukti melalui pilot.

## 4. Terminologi standar

| Istilah | Definisi |
| --- | --- |
| Workspace | Ruang kerja utama sitop |
| Unit | Area kerja seperti Development, Infra, dan Operasional |
| Project | Wadah dan konteks yang berisi kumpulan Task |
| Task | Satu pekerjaan individual yang perlu dikelola sampai selesai |
| Project Lead | Penanggung jawab pelaksanaan keseluruhan Project |
| Project Admin | Pengelola permission dan konfigurasi Project |
| PIC Task | Orang yang mengerjakan atau bertanggung jawab atas Task |
| Pemohon | Orang yang mengajukan Task |
| Reviewer | Orang yang memeriksa Task bila diperlukan; bukan role wajib |
| Activity Log | Riwayat aktivitas pada Project atau Task |
| Audit Log | Catatan perubahan penting untuk pelacakan dan audit |
| Bukti | Catatan, attachment, atau URL yang mendukung penyelesaian Task |
| Catatan penyelesaian | Penjelasan hasil Task sebelum status `Done` |

## 5. Struktur akses

### 5.1 Workspace role

- **Owner** — pemilik utama Workspace.
- **Workspace Admin** — mengelola anggota, Unit, Project, permission, dan konfigurasi Workspace.
- **Member** — anggota internal yang dapat bekerja di Project yang diikuti.
- **Guest** — akses terbatas untuk pengguna eksternal atau akses khusus.

### 5.2 Project role

- **Project Admin** — mengelola konfigurasi Project, anggota, workflow, label, dan seluruh Task.
- **Contributor** — membuat, mengubah, assign, dan mengerjakan Task.
- **Commenter** — melihat Task serta menambahkan komentar dan attachment.
- **Viewer** — hanya membaca data yang diizinkan.

Satu user memiliki satu Workspace role dan dapat memiliki Project role yang berbeda pada setiap Project.

### 5.3 Penugasan yang bukan role

- Project Lead adalah penanggung jawab pelaksanaan Project.
- PIC Task adalah penanggung jawab Task tertentu.
- Pemohon adalah pengaju Task.
- Reviewer adalah kemampuan atau penugasan untuk memeriksa hasil Task.
- Creator adalah user yang membuat catatan Project atau Task.

Project Admin dan Project Lead dapat merupakan orang yang sama, tetapi keduanya memiliki makna berbeda.

## 6. Navigasi dua tingkat

sitop wajib menggunakan navigasi dua tingkat:

- **Bilah tingkat pertama** untuk berpindah antar-area bisnis seperti Pekerjaan, Pengetahuan, Operasional, Kepatuhan, dan Laporan.
- **Bilah tingkat kedua** untuk menampilkan konteks Workspace, Unit, Project, pencarian, notifikasi, menu area aktif, pengaturan, dan profil.

Aturan:

- navigasi tingkat kedua berubah mengikuti konteks aktif;
- menu fitur yang belum aktif tidak ditampilkan sebagai tombol palsu;
- pada mobile, bilah tingkat kedua berubah menjadi drawer atau menu geser;
- item aktif harus dapat dikenali tanpa mengandalkan warna saja;
- detail layout, hierarchy, komponen, dan perilaku interaksi ditentukan di `DESIGN.md`.

## 7. Unit dan membership

MVP memiliki tiga Unit awal:

- Development;
- Infra;
- Operasional.

Aturan:

- Unit berfungsi sebagai struktur organisasi, grouping, dan filter.
- Satu Project memiliki satu Unit utama.
- Anggota Project dapat berasal dari Unit mana pun.
- User boleh menjadi anggota beberapa Unit dan Project.
- Workspace Admin mengelola membership Unit.
- Project Admin mengelola membership Project.
- Menambahkan user ke Project tidak otomatis menambahkan user ke Unit.
- Unit Admin atau Unit Lead menjadi fitur lanjutan.

## 8. Visibility Project

Project memiliki tiga pilihan visibility:

- **Workspace** — terlihat oleh semua anggota Workspace.
- **Unit** — terlihat oleh anggota Unit terkait.
- **Private** — hanya terlihat oleh user yang diundang.

Default Project adalah `Unit`.

## 9. Project

### 8.1 Pembuatan Project

Secara default, hanya Owner dan Workspace Admin yang dapat membuat Project. Permission tambahan untuk mengizinkan Member membuat Project dapat ditambahkan kemudian.

Project memiliki:

- nama;
- Project ID unik, misalnya `DEV`;
- Unit utama;
- deskripsi atau tujuan;
- Project Admin;
- Project Lead;
- anggota;
- visibility;
- status;
- tanggal mulai dan target selesai;
- Default Assignee opsional;
- Activity Log dan Audit Log.

Project ID menjadi prefix Task, misalnya `DEV-123`.

### 8.2 Status Project

```text
Draft → Planned → Active → Completed
                    ↓
                 On Hold
                    ↓
                 Canceled
```

Project yang sudah `Completed` atau `Canceled` dapat di-archive dan di-restore.

### 8.3 Lifecycle Project

- Project yang diarsipkan tidak tampil di daftar aktif atau pencarian biasa.
- Data, Task, Sub-task, Activity Log, dan Audit Log tetap dipertahankan.
- Project arsip bersifat read-only sampai dipulihkan.
- Project Admin dapat mengarsipkan Project.
- Project Admin atau Workspace Admin dapat memulihkan Project.
- Penghapusan permanen dibatasi untuk permission tinggi dan memerlukan konfirmasi.

### 8.4 Project Admin dan Project Lead

- Project Admin mengelola permission, anggota, workflow, label, dan konfigurasi.
- Project Lead menjadi kontak utama dan penanggung jawab pelaksanaan Project.
- Creator Project menjadi Project Admin dan dapat menjadi Project Lead secara default.

## 10. Task

### 9.1 Pembuatan Task

Project Admin dan Contributor dapat membuat Task. Commenter dapat melihat dan memberi komentar. Viewer hanya dapat membaca.

Saat membuat Task, user cukup mengisi Title. Project mengikuti konteks Project yang sedang dibuka.

Field dan nilai otomatis:

- Task ID otomatis, misalnya `DEV-123`;
- Project dari konteks aktif;
- Creator dari user yang membuat;
- Pemohon default sama dengan Creator;
- status awal dari default Project, yaitu `Backlog`;
- PIC boleh kosong;
- Priority boleh kosong atau memakai default Project;
- Deadline, label, deskripsi, milestone, dan attachment bersifat opsional saat dibuat.

### 9.2 Default Assignee

Default Assignee bersifat opsional pada Project.

- Jika tersedia, Task tanpa PIC otomatis diberikan kepada Default Assignee.
- Jika tidak tersedia, Task tetap tanpa PIC sampai dilakukan triage.
- Project Admin dapat mengatur atau menghapus Default Assignee.
- Default Assignee harus memiliki role Project Admin atau Contributor.

### 9.3 Status Task

Workflow standar MVP:

```text
Permintaan → Backlog → Todo → In Progress → In Review → Done
```

Status tambahan:

```text
In Progress → Blocked
Status aktif → Canceled
Done → Reopen → In Progress
```

Aturan:

- Contributor dapat mengubah status Task yang menjadi tanggung jawabnya.
- Project Admin dapat mengubah status semua Task.
- Commenter dan Viewer tidak dapat mengubah status.
- `Blocked` wajib memiliki alasan.
- `Canceled` wajib memiliki alasan.
- `Reopen` wajib memiliki alasan.
- `Done` wajib memiliki catatan penyelesaian atau minimal satu bukti.
- Perubahan status masuk ke Activity Log dan Audit Log.

### 9.4 PIC dan Pemohon

- PIC Task dapat dipilih dari Project Admin atau Contributor.
- PIC Task adalah penanggung jawab Task, bukan penanggung jawab Project.
- Project Lead adalah penanggung jawab keseluruhan Project.
- Pemohon dapat berbeda dari Creator dan PIC.

### 9.5 Sub-task

Task mendukung Sub-task.

- Sub-task memiliki PIC, status, deadline, komentar, attachment, Activity Log, dan Audit Log sendiri.
- Parent Task menampilkan daftar dan progress Sub-task.
- Parent Task boleh menjadi `Done` meskipun masih ada Sub-task yang belum selesai.
- Setiap Sub-task yang menjadi `Done` tetap wajib memiliki catatan penyelesaian atau bukti.

### 9.6 Relasi antar-Task

MVP mendukung:

- `Related to`;
- `Duplicate of`;
- `Blocks`;
- `Blocked by`.

Relasi tidak mengubah status Task secara otomatis. Penambahan dan penghapusan relasi dicatat dalam Activity Log dan Audit Log.

### 9.7 Milestone

Milestone bersifat opsional di level Project.

- Milestone memiliki judul dan target date opsional.
- Task dapat berada di satu milestone atau tanpa milestone.
- Progress milestone dihitung dari Task yang terkait.
- Milestone dapat digunakan untuk grouping dan filtering.

## 11. Komentar, attachment, dan bukti

- Project Admin dan Contributor dapat menambahkan komentar serta attachment.
- Commenter dapat menambahkan komentar serta attachment.
- Viewer hanya dapat membaca.
- User dapat mengedit atau menghapus komentar miliknya sendiri.
- Attachment dapat berupa catatan teks, file, atau URL.
- Satu Task dapat memiliki lebih dari satu bukti.
- Bukti dapat ditambahkan selama Task masih aktif.
- Attachment privat hanya dapat diunduh user yang memiliki akses ke Task.
- Tipe file berbahaya ditolak.
- Ukuran file memiliki batas konfigurasi.
- Semua perubahan penting pada komentar, attachment, dan bukti dicatat.

## 12. Activity Log dan Audit Log

### Activity Log

Activity Log dapat dilihat user yang memiliki akses ke Project atau Task. Contohnya:

- Task dibuat;
- komentar ditambahkan;
- attachment ditambahkan;
- PIC berubah;
- status berubah;
- Task masuk atau keluar dari milestone;
- relasi Task berubah.

### Audit Log

Audit Log dapat dilihat:

- Workspace Admin untuk scope Workspace;
- Project Admin untuk scope Project.

Audit Log menyimpan actor, action, timestamp, object, request ID, nilai sebelum, dan nilai sesudah bila relevan. Perubahan status, PIC, priority, deadline, role, permission, attachment privat, dan archive/restore wajib dicatat.

## 13. Dashboard, search, dan filter

### 12.1 Dashboard Project

- Task berdasarkan status;
- Task terlambat;
- Task terblokir;
- Task tanpa PIC;
- Task tanpa deadline;
- progress Project;
- Activity Log terbaru.

### 12.2 Dashboard Workspace

- Task saya;
- Task terlambat;
- Task terblokir;
- Project yang perlu perhatian;
- progress setiap Project;
- perubahan terbaru lintas Project;
- beban kerja per PIC;
- jumlah Task aktif, terlambat, dan terblokir per PIC.

Semua data Dashboard mengikuti permission user.

### 12.3 Search dan filter

MVP Core menyediakan Global Search untuk Project dan Task. Search lanjutan untuk komentar, Activity Log, halaman, dokumen, dan objek operasional menjadi tahap berikutnya.

Filter Task mencakup Project, Unit, status, PIC, Pemohon, priority, label, deadline, milestone, kelengkapan bukti, PIC, dan deadline.

### 12.4 Saved View

Saved View menjadi fitur MVP Follow-up. User dapat menyimpan kombinasi filter, sorting, grouping, dan density pada halaman Task sebagai view yang dapat digunakan kembali.

Ketentuan awal:

- Saved View tersimpan di backend dan terhubung ke user serta Project;
- Saved View pribadi hanya dapat dilihat dan diubah oleh pembuatnya;
- nama Saved View wajib diisi dan unik untuk user dalam Project yang sama;
- menghapus Saved View memerlukan konfirmasi;
- Saved View yang merujuk pada status, PIC, label, atau milestone yang sudah tidak tersedia tetap dapat dibuka dengan filter yang diabaikan dan feedback yang jelas;
- kemampuan membagikan Saved View ke anggota Project menjadi keputusan lanjutan.

## 14. Notifikasi

MVP menggunakan notifikasi in-app untuk:

- penugasan PIC;
- `@mention`;
- komentar baru pada Task yang diikuti;
- perubahan status;
- deadline mendekat atau terlewat;
- Task menjadi `Blocked`;
- Task selesai atau dibuka kembali.

Email, WhatsApp, Slack, dan kanal eksternal lainnya ditunda.

## 15. Keadaan UI

Setiap layar menangani keadaan yang relevan, mengikuti pola Plane:

- loading;
- kosong;
- berhasil;
- validasi gagal;
- tidak diizinkan;
- jaringan gagal;
- berhasil menyimpan;
- konflik data bila relevan;
- diarsipkan;
- tidak ditemukan.

Tidak semua layar wajib menampilkan seluruh keadaan tersebut. Tombol fitur yang belum aktif tidak boleh ditampilkan sebagai tombol palsu.

## 16. Keamanan dan kebutuhan non-fungsional

- Authorization selalu diperiksa di backend.
- Akses Workspace, Unit, Project, Task, dan attachment mengikuti membership dan role.
- Attachment privat tidak boleh diakses melalui URL publik tanpa authorization.
- Perubahan penting masuk Audit Log.
- Waktu disimpan konsisten dan ditampilkan mengikuti timezone Workspace.
- Task menggunakan identifier unik berbasis Project ID.
- Objek penting menggunakan archive/restore atau soft delete.
- Request gagal tidak boleh meninggalkan perubahan parsial.
- Akses langsung ke object tanpa permission menghasilkan `403` atau `404` sesuai kebijakan.
- Tampilan usable pada desktop dan mobile.
- List dan Dashboard tetap dapat digunakan ketika jumlah data bertambah.
- Navigasi dan status dapat dikenali tanpa mengandalkan warna saja.

## 17. Definition of Done

Sebuah fitur MVP dianggap selesai jika:

- backend dan frontend berjalan;
- permission backend telah diterapkan;
- validasi berhasil dan gagal ditangani;
- Activity Log dan Audit Log tersedia bila relevan;
- loading, empty, error, unauthorized, dan archived state tersedia bila relevan;
- test untuk alur penting dan permission berhasil;
- responsive pada desktop dan mobile;
- navigasi serta status dapat dikenali tanpa mengandalkan warna saja;
- aksi destruktif memiliki konfirmasi dan authorization;
- tidak ada tombol fitur palsu atau fitur yang belum aktif.

## 18. Scope delivery

### 17.1 MVP Core

- login dan session;
- Workspace dan membership;
- Unit Development, Infra, dan Operasional;
- Workspace role dan Project role;
- visibility Project;
- pembuatan dan pengelolaan Project;
- Project ID, Project Admin, dan Project Lead;
- archive/restore Project;
- pembuatan dan pengelolaan Task;
- Task ID, status, PIC, Pemohon, priority, deadline, dan label;
- list, Kanban, dan detail Task;
- komentar dan attachment;
- catatan penyelesaian dan bukti;
- Activity Log dan Audit Log;
- authorization backend.

### 17.2 MVP Follow-up

- Sub-task;
- relasi antar-Task;
- milestone;
- Dashboard Workspace lintas Project;
- workload per PIC;
- Global Search dan filter lanjutan;
- Saved View backend per user dan Project;
- notifikasi in-app;
- archive/restore yang lebih lengkap;
- seluruh keadaan UI yang relevan pada setiap layar.

Pembagian ini adalah urutan delivery. Model data dan permission harus tetap disiapkan agar MVP Follow-up tidak memerlukan perubahan fondasi besar.

### 17.3 Di luar MVP

- inventaris aset lengkap;
- kontrak, lisensi, dan warranty;
- compliance formal;
- workflow bebas per Project;
- approval bertingkat;
- SLA dan eskalasi;
- integrasi eksternal besar;
- tanda tangan elektronik;
- analitik lanjutan;
- automasi workflow bebas;
- Unit Admin atau Unit Lead khusus.

## 19. Kriteria keberhasilan pilot

### MVP Core

- minimal 80% pekerjaan aktif pilot tercatat di sitop;
- minimal 90% Task aktif memiliki PIC;
- minimal 80% Task aktif memiliki deadline atau alasan belum memiliki deadline;
- 100% Task berstatus `Done` memiliki catatan penyelesaian atau bukti;
- 100% perubahan penting tercatat di Audit Log;
- 0 akses tidak sah berhasil melewati authorization backend;
- pimpinan dapat menemukan status Project dan Task tanpa meminta laporan manual.

### MVP Follow-up

- Dashboard Workspace menampilkan kondisi lintas Unit dan Project;
- Task terlambat dan terblokir dapat ditemukan lebih awal;
- beban kerja PIC dapat dilihat;
- notifikasi in-app yang relevan berjalan;
- Search Project dan Task menghasilkan data sesuai permission.
- Saved View dapat dibuat, dibuka kembali, diubah, dan dihapus lintas sesi pada Project yang sama.

## 20. Keputusan lanjutan

- Workflow bebas per Project dapat ditambahkan setelah workflow standar terbukti.
- Unit Admin atau Unit Lead dapat ditambahkan ketika pengelolaan lintas Unit membutuhkan delegasi.
- Modul Pengetahuan, Operasional, Dokumen, Kepatuhan, Integrasi, dan Automasi mengikuti roadmap pada handoff.
