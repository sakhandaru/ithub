# sitop — Design

## 1. Tujuan dokumen

Dokumen ini menerjemahkan PRD sitop menjadi arahan desain produk dan antarmuka. Isinya mencakup struktur navigasi, hierarki layout, layar MVP, pola komponen, interaksi, responsif, aksesibilitas, dan keadaan UI.

Dokumen ini bukan spesifikasi implementasi frontend. Detail teknis tetap mengikuti PRD dan konvensi project.

## 2. Arah desain

sitop menggunakan pola produk yang **Plane-like**: workspace sebagai rumah kerja, Project sebagai konteks utama, Task sebagai unit kerja, sidebar kontekstual, toolbar kerja yang ringkas, serta layout List dan Board yang menampilkan data yang sama.

sitop tidak menyalin branding atau identitas Plane. Yang diadopsi adalah pola informasi dan interaksinya, lalu disesuaikan dengan istilah, Unit, workflow, bukti, Activity Log, dan Audit Log sitop.

Antarmuka perlu membantu user memahami tiga hal dengan cepat:

1. sedang berada di Workspace, Unit, atau Project mana;
2. pekerjaan apa yang perlu diperhatikan;
3. tindakan apa yang dapat dilakukan user.

### Prinsip visual

- Prioritaskan kejelasan informasi daripada dekorasi.
- Gunakan kepadatan informasi yang nyaman untuk pekerjaan operasional harian.
- Status, priority, dan kondisi risiko harus mudah dipindai.
- Gunakan warna sebagai pendukung, bukan satu-satunya penanda makna.
- Tampilkan konteks sebelum tindakan.
- Sediakan feedback langsung setelah tindakan berhasil atau gagal.
- Gunakan bahasa antarmuka Indonesia dengan istilah produk yang sudah distandarkan.
- Jangan menampilkan fitur yang belum aktif sebagai tombol palsu.

### Pola Plane yang diadopsi

- Workspace switcher di bagian atas sidebar konteks.
- Search global yang dapat dibuka dari top bar atau shortcut.
- Daftar Project yang dikelompokkan berdasarkan Unit.
- Project Overview sebagai pintu masuk Project.
- Toolbar layout untuk berpindah antara List dan Board.
- Task ID yang terlihat jelas dan dapat disalin.
- Detail Task dengan meta row untuk status, priority, PIC, dan deadline.
- Sidebar properties untuk metadata Task yang lebih jarang diubah.
- Activity Log sebagai timeline kolaborasi.
- Archive sebagai cara utama mengurangi data aktif tanpa menghilangkan histori.

## 3. Terminologi antarmuka

Gunakan istilah berikut secara konsisten:

| Gunakan | Jangan gunakan sebagai istilah UI utama |
| --- | --- |
| Task | Work item, Issue |
| PIC | Assignee |
| Pemohon | Requester |
| Project Lead | Project Owner |
| Project Admin | Owner bila maksudnya permission |
| Activity Log | Aktivitas bila berdiri sebagai nama fitur |
| Audit Log | Audit event |
| Bukti | Evidence |
| Catatan penyelesaian | Completion note |
| Sub-task | Sub-work item |

Istilah teknis seperti `work_items`, `assignee`, atau `audit_events` boleh digunakan dalam kode, tetapi tidak pada antarmuka user.

## 4. Arsitektur navigasi

sitop memakai **navigasi dua tingkat**.

### 4.1 Bilah tingkat pertama

Bilah tetap di sisi paling kiri pada desktop. Fungsinya hanya berpindah antar-area bisnis.

```text
[Logo sitop]

Pekerjaan
Pengetahuan
Operasional
Kepatuhan
Laporan

Pengaturan
```

Aturan:

- lebar tetap dan lebih sempit daripada bilah konteks;
- item aktif memiliki indikator bentuk dan warna;
- ikon selalu disertai label pada ukuran desktop utama;
- item yang belum aktif tidak ditampilkan sebagai navigasi yang dapat diklik;
- `Pengaturan` berada terpisah secara visual dari area bisnis.

### 4.2 Bilah tingkat kedua

Bilah konteks berada di sebelah bilah tingkat pertama. Isinya mengikuti area dan objek aktif.

Bagian umum:

```text
Logo dan nama sitop
Pemilih Workspace
Global Search
Notifikasi
Menu area aktif
Project atau objek terkait
Pengaturan Workspace
Profil dan keluar
```

### 4.3 Konteks Project

Saat user berada di dalam Project, bilah konteks menampilkan:

```text
Project
├── Ikhtisar
├── Daftar Task
├── Papan
├── Milestone
├── Activity Log
└── Pengaturan Project
```

Fitur yang belum aktif tidak perlu ditampilkan. Jika fitur dibuat tersedia kemudian, item ditambahkan tanpa mengubah pola navigasi utama.

### 4.4 Struktur Plane-like sitop

```text
Primary Rail
└── Area bisnis sitop

Context Sidebar
├── Workspace switcher
├── Search
├── Pekerjaan saya
├── Semua Project
│   ├── Development
│   ├── Infra
│   └── Operasional
├── Saved views (jika aktif)
└── Workspace settings

Project Context
├── Ikhtisar
├── Task
│   ├── List
│   └── Board
├── Milestone (jika aktif)
├── Activity Log
└── Project settings
```

Pola ini mengikuti cara Plane menempatkan Workspace, Project, Work items, Your work, dan layout kerja dalam satu shell. Plane memisahkan konteks Workspace dari resource Project, lalu menyediakan layout List, Board, Calendar, Table, dan Timeline sesuai fitur yang aktif. Untuk MVP sitop, hanya List dan Board yang aktif terlebih dahulu. ([Plane Workspace](https://docs.plane.so/workspaces/overview), [Plane Organize and View Work](https://docs.plane.so/introduction/tutorials/organize-and-view-work))

### 4.5 Konteks Unit

Saat user melihat Unit, tampilkan:

```text
Unit
├── Ikhtisar
├── Project
├── Task
└── Activity Log
```

Unit tidak memiliki role permission khusus pada MVP. Karena itu, jangan membuat menu administrasi Unit yang seolah-olah sudah aktif.

## 5. Responsif

### Desktop

- Dua bilah navigasi terlihat berdampingan.
- Bilah tingkat pertama tetap terlihat.
- Bilah tingkat kedua dapat diciutkan jika ruang konten sempit.
- Konten utama memakai layout satu kolom untuk detail dan layout dua kolom untuk dashboard atau halaman overview.

### Tablet

- Bilah tingkat pertama dapat berubah menjadi rail dengan ikon dan tooltip.
- Bilah tingkat kedua tetap dapat dibuka atau diciutkan.
- Table dan board menyediakan horizontal scrolling yang terkontrol.

### Mobile

- Bilah tingkat pertama menjadi menu geser atau bottom navigation untuk area utama.
- Bilah tingkat kedua menjadi drawer konteks.
- Project aktif, Workspace, dan Search tetap mudah ditemukan.
- Filter dan sort dibuka melalui sheet.
- Detail Task menggunakan satu kolom.
- Aksi utama tetap terlihat, sedangkan aksi sekunder berada dalam menu.
- Item aktif harus dapat dikenali melalui label, bentuk, dan indikator; tidak hanya warna.

## 6. Hierarki layout umum

```text
Application Shell
├── Primary Navigation
├── Context Navigation
└── Main Content
    ├── Page Header
    ├── Context Controls
    └── Content Area
```

### Page Header

Page Header berisi:

- breadcrumb atau konteks lokasi;
- judul halaman;
- deskripsi singkat bila diperlukan;
- status atau metadata utama;
- primary action;
- secondary actions dalam menu.

Jangan meletakkan terlalu banyak tombol sejajar di header. Tindakan utama harus terlihat, sedangkan tindakan destruktif atau jarang digunakan berada di menu.

### Context Controls

Context Controls digunakan untuk:

- search;
- filter;
- sort;
- group;
- layout switcher;
- saved view jika sudah aktif.

Control harus menjelaskan scope-nya, misalnya `Filter Task Project ini`, bukan hanya `Filter`.

## 7. Layar MVP Core

### 7.1 Dashboard Workspace

Tujuan: menjawab apa yang perlu diperhatikan lintas Project.

Struktur:

```text
Header: Dashboard Workspace
├── Ringkasan Task aktif
├── Task saya
├── Task terlambat
├── Task terblokir
├── Project perlu perhatian
├── Beban kerja per PIC
└── Activity Log terbaru
```

Kartu ringkasan harus dapat diklik untuk membuka daftar yang sudah terfilter.

### 7.2 Daftar Project

Menampilkan:

- nama Project;
- Project ID;
- Unit utama;
- Project Lead;
- status Project;
- progress Task;
- target selesai;
- visibility.

Filter minimum:

- Unit;
- status;
- Project Lead;
- visibility.

### 7.3 Ikhtisar Project

Menampilkan:

- nama dan Project ID;
- tujuan atau deskripsi;
- Unit;
- Project Lead dan Project Admin;
- status Project;
- progress Task;
- Task terlambat dan terblokir;
- milestone jika aktif;
- Activity Log terbaru;
- anggota Project.

### 7.4 Daftar Task

Kolom atau informasi minimum:

- Task ID;
- Title;
- status;
- PIC;
- Pemohon;
- priority;
- deadline;
- label;
- indikator bukti atau catatan penyelesaian;
- indikator Sub-task bila ada.

Interaksi:

- inline update untuk field yang diizinkan;
- multi-select untuk aksi bulk bila tersedia;
- filter dan sort;
- layout switcher `List` dan `Board` berada di toolbar yang sama;
- quick add membuat Task dari Title saja;
- buka detail Task tanpa kehilangan konteks daftar.

### 7.5 Papan

Papan mengelompokkan Task berdasarkan status.

```text
Backlog | Todo | In Progress | In Review | Done
```

Aturan:

- perpindahan kartu menjalankan aturan backend;
- perpindahan ke `Blocked`, `Canceled`, `Reopen`, atau `Done` membuka form alasan/bukti yang relevan;
- jika backend menolak perubahan, kartu kembali ke posisi semula dan menampilkan feedback;
- keyboard dan non-drag alternative harus tersedia untuk mengubah status.

### 7.6 Detail Task

Struktur yang disarankan:

```text
Task Header
├── Task ID dan Title
├── Status
├── Priority
├── PIC
├── Deadline
└── More actions

Task Body
├── Description
├── Catatan penyelesaian
├── Bukti dan Attachment
├── Sub-task
├── Relasi
├── Komentar
└── Activity Log

Task Sidebar
├── Project
├── Pemohon
├── Label
├── Milestone
├── Created by
└── Created/updated date
```

Audit Log ditampilkan kepada role yang berwenang, melalui tab atau panel terpisah dari Activity Log.

Detail Task mengikuti pola Plane-like:

- Task ID berada di atas Title dan dapat disalin;
- meta row menampilkan status, priority, PIC, start date, dan deadline;
- description menjadi area utama untuk konteks pekerjaan;
- toolbar di bawah description menyediakan Sub-task, relasi, dependency, attachment, dan link;
- properties yang jarang diubah berada di sidebar yang dapat diciutkan;
- detail dapat dibuka sebagai halaman penuh atau panel dari List/Board tanpa kehilangan konteks.

### 7.7 Pengaturan Project

Bagian minimum:

- General: nama, Project ID, deskripsi, Unit, visibility, status, tanggal;
- Members: anggota dan Project role;
- Workflow: status standar dan default status;
- Labels;
- Default Assignee;
- Archive.

Menu fitur lanjutan hanya tampil ketika fitur tersebut sudah aktif.

Pengaturan Project mengikuti pola feature toggle per Project: fitur seperti Milestone, Cycle, Module, Page, View, atau Project Update hanya menambahkan menu ketika diaktifkan. Untuk MVP, menu yang tampil hanya fitur yang benar-benar tersedia.

## 8. Komponen dan pola interaksi

### Status badge

Status harus memiliki:

- label teks;
- warna pendukung;
- bentuk atau ikon yang membedakan state penting.

Jangan menggunakan warna merah/hijau sebagai satu-satunya informasi.

### Priority

Gunakan label teks dan indikator visual yang konsisten:

```text
Urgent | High | Medium | Low | None
```

### Avatar dan PIC

- Tampilkan nama melalui tooltip atau accessible label.
- Jika belum ada PIC, tampilkan `Belum ditugaskan`, bukan avatar kosong tanpa penjelasan.
- Klik avatar membuka pilihan user yang memiliki role Project Admin atau Contributor.

### Empty state

Empty state harus menjelaskan:

- apa yang kosong;
- mengapa belum ada data jika relevan;
- tindakan utama yang dapat dilakukan.

Contoh:

```text
Belum ada Task di Project ini.
Tambahkan Task pertama untuk mulai mencatat pekerjaan.
[Tambah Task]
```

### Confirmation

Konfirmasi wajib digunakan untuk:

- archive;
- restore bila berdampak pada banyak data;
- delete permanen;
- perubahan yang menyebabkan hilangnya akses;
- perubahan status yang memerlukan alasan.

Dialog harus menjelaskan konsekuensi, bukan hanya bertanya “Yakin?”.

### Feedback penyimpanan

- Gunakan feedback dekat dengan objek yang berubah.
- Gunakan toast untuk konfirmasi singkat.
- Gunakan inline error untuk validasi field.
- Jangan mengandalkan toast saja untuk error penting.

## 9. Form dan validasi

Form pembuatan Task mengikuti pola quick capture:

- Title menjadi field utama dan wajib;
- Project mengikuti konteks aktif;
- field lain dapat dilengkapi kemudian;
- tombol simpan memiliki state processing;
- error backend ditampilkan pada field atau konteks yang tepat.

Form perubahan status menampilkan field tambahan sesuai status:

| Status tujuan | Input wajib |
| --- | --- |
| Blocked | Alasan |
| Canceled | Alasan |
| Reopen | Alasan |
| Done | Catatan penyelesaian atau bukti |

## 10. Activity Log dan Audit Log di UI

### Activity Log

Activity Log tampil kronologis, newest first, dengan:

- avatar atau identitas actor;
- deskripsi aktivitas;
- waktu relatif dan waktu lengkap melalui tooltip;
- link ke objek yang relevan.

### Audit Log

Audit Log memakai tampilan yang lebih terstruktur:

```text
Actor | Action | Object | Before | After | Timestamp
```

Nilai sebelum dan sesudah dapat dibuka dalam detail agar daftar utama tetap ringkas.

## 11. Aksesibilitas

- Semua aksi utama dapat diakses dengan keyboard.
- Focus state terlihat jelas.
- Dialog mengembalikan focus ke trigger setelah ditutup.
- Form memiliki label yang jelas dan error yang terhubung ke field.
- Drag-and-drop di Board memiliki alternatif menu atau keyboard.
- Status dan permission tidak disampaikan hanya melalui warna.
- Kontras teks dan kontrol memenuhi standar aksesibilitas yang relevan.
- `aria-label` digunakan untuk ikon tanpa teks.
- Loading state memberi informasi kepada screen reader bila relevan.

## 12. Respons dan feedback error

### Tidak diizinkan

- Jangan menampilkan action yang jelas-jelas tidak boleh dilakukan.
- Jika user mengakses URL langsung, tampilkan keadaan tidak diizinkan atau tidak ditemukan sesuai kebijakan.
- Jelaskan bahwa akses dibatasi tanpa membocorkan data objek.

### Jaringan gagal

- Pertahankan input yang belum tersimpan bila memungkinkan.
- Tampilkan tombol `Coba lagi`.
- Jangan menganggap perubahan berhasil sebelum backend mengonfirmasi.

### Konflik data

- Beri tahu user bahwa data berubah di tempat lain.
- Tawarkan reload atau perbandingan bila tersedia.
- Jangan diam-diam menimpa perubahan user.

## 13. Responsif dan kepadatan data

- List tetap dapat dipindai pada layar kecil dengan menyembunyikan kolom sekunder secara progresif.
- Informasi inti yang dipertahankan pada mobile: Task ID, Title, status, PIC, dan deadline.
- Filter kompleks dibuka dalam sheet.
- Detail Task tidak boleh memerlukan horizontal scrolling.
- Board mobile boleh menggunakan horizontal scrolling per kolom, tetapi kartu tetap memiliki target sentuh yang nyaman.

## 14. Status fitur

Fitur berikut tidak boleh tampil sebagai menu aktif sebelum tersedia:

- Pengetahuan;
- Operasional aset;
- Dokumen dan kontrak;
- Kepatuhan;
- Integrasi besar;
- Workflow bebas;
- Approval bertingkat.

Jika area utama sudah ditampilkan sebagai bagian dari shell, area yang belum aktif harus memiliki treatment yang jelas atau tidak ditampilkan sama sekali. Jangan menampilkan halaman kosong yang menyerupai fitur siap pakai.

## 15. Definition of Done desain

Desain layar atau komponen dianggap siap untuk implementasi jika:

- konteks Workspace, Unit, atau Project terlihat jelas;
- primary action dan permission action dapat dibedakan;
- happy path dan failure path sudah ditentukan;
- empty, loading, validation, unauthorized, archived, dan not-found state relevan sudah memiliki rancangan;
- status tidak hanya dibedakan dengan warna;
- desktop, tablet, dan mobile memiliki perilaku yang jelas;
- keyboard dan focus behavior sudah dipikirkan;
- copy memakai terminologi standar sitop;
- fitur yang belum aktif tidak muncul sebagai tombol palsu.

## 16. Referensi keputusan

- Scope dan aturan produk: `PRD.md`.
- Konteks produk dan roadmap besar: `docs/SIM_TI_PROJECT_HANDOFF.md`.

## 17. Spesifikasi Plane-like per layar

Bagian ini menjadi acuan visual dan interaksi yang lebih konkret. Prinsipnya adalah mengikuti pola Plane, bukan menyalin identitas visualnya.

### 17.1 Application Shell

```text
┌──────────────┬─────────────────────────┬──────────────────────────────┐
│ Primary Rail │ Context Sidebar         │ Main Content                 │
│              │                         │                              │
│ Logo         │ Workspace switcher      │ Top bar                      │
│ Area bisnis  │ Search                  │ Breadcrumb / context         │
│              │ Pekerjaan saya          │ Page title                   │
│              │ Semua Project           │ Toolbar                      │
│ Pengaturan   │ Project aktif           │ Content                      │
└──────────────┴─────────────────────────┴──────────────────────────────┘
```

Perilaku:

- Primary Rail berpindah area bisnis tanpa mengubah sesi atau Workspace aktif.
- Context Sidebar berubah mengikuti area aktif.
- Main Content tidak boleh mengulang seluruh navigasi sidebar.
- Workspace switcher menampilkan nama Workspace, avatar atau logo, dan daftar Workspace yang dapat diakses.
- Search dapat dibuka dari top bar dan shortcut `Cmd/Ctrl + K`.
- Notifikasi dan profil berada di top bar atau area bawah Context Sidebar.
- Sidebar dapat diciutkan, tetapi context aktif tetap terlihat melalui breadcrumb atau header.

### 17.2 Workspace Home / Dashboard

Workspace Home adalah titik masuk setelah login. Polanya mengikuti dashboard `Your Work` Plane, tetapi metrik sitop ditambah ringkasan lintas Unit dan Project.

```text
Header
├── Sapaan dan tanggal
├── Workspace switcher
└── Primary action: Tambah Task / Buka Project

Summary strip
├── Task saya
├── Task terlambat
├── Task terblokir
└── Project perlu perhatian

Main grid
├── Task saya berdasarkan status
├── Beban kerja per PIC
├── Progress Project
└── Activity Log terbaru
```

Setiap summary card harus berfungsi sebagai filter shortcut. Contoh, klik `Task terlambat` membuka daftar Task dengan filter `deadline < hari ini`.

### 17.3 Context Sidebar Project

Saat Project aktif, header sidebar menampilkan:

```text
[Project icon] Nama Project
DEV · Development
Project Lead: Nama
```

Menu:

```text
Ikhtisar
Task
├── List
└── Board
Milestone              (jika aktif)
Activity Log
──────────────
Project settings       (jika berwenang)
```

Menu fitur tidak aktif disembunyikan. Jika Project memiliki fitur yang diaktifkan kemudian, item baru muncul di bawah konteks Project tanpa mengubah struktur global.

### 17.4 Project Overview

Plane menjadikan Overview sebagai pintu depan Project. Sitop mengikuti struktur ini:

```text
Project header
├── Icon / cover sederhana
├── Nama Project dan Project ID
├── Status Project
├── Unit
├── Project Lead
└── Menu Project

Overview content
├── Deskripsi / tujuan / success metrics
├── Progress per status
├── Needs attention
│   ├── Overdue
│   ├── Blocked
│   └── Unassigned
├── Milestones               (jika aktif)
└── Activity Log terbaru

Right panel
├── Properties
├── Updates                  (Follow-up)
└── Activity
```

Deskripsi Project menggunakan rich text ringan dan mendukung link serta attachment bila fitur tersebut tersedia. Properties ditampilkan sebagai panel, bukan kartu besar yang menghabiskan ruang utama.

### 17.5 Task List

Task List adalah tampilan default karena paling efisien untuk triage dan operasi harian.

```text
Page header
├── Breadcrumb Project
├── Task
├── Add Task
└── More actions

Work toolbar
├── List / Board
├── Filter
├── Sort
├── Group
├── Display
└── Search within Project

Task table/list
├── ID
├── Title
├── Status
├── Priority
├── PIC
├── Deadline
├── Labels
└── Sub-task progress
```

Perilaku Plane-like:

- menampilkan Task berdasarkan group status secara default;
- group dapat diubah ke Priority, PIC, Label, Milestone, atau tanpa group bila fitur tersedia;
- kolom dapat ditampilkan atau disembunyikan melalui Display;
- klik baris membuka side peek, bukan selalu berpindah halaman;
- klik ID menyalin identifier;
- quick add berada di akhir group atau melalui tombol `Tambah Task`;
- perubahan inline menyimpan ke backend dan menampilkan feedback;
- filter yang aktif ditampilkan sebagai chip yang dapat dihapus satu per satu.

### 17.6 Board

Board menggunakan Task yang sama dengan List, hanya cara penyajiannya yang berbeda.

```text
Toolbar
├── List
├── Board aktif
├── Filter
├── Sort
└── Display

Board
├── Backlog
├── Todo
├── In Progress
├── In Review
└── Done
```

Perilaku:

- kartu menampilkan ID, Title, priority, PIC, deadline, label, dan indikator Sub-task;
- drag-and-drop menjalankan validasi backend;
- status yang memerlukan alasan membuka dialog sebelum perpindahan diterapkan;
- perpindahan yang gagal mengembalikan kartu ke kolom awal;
- kartu dapat dibuka melalui side peek;
- setiap kolom memiliki quick add;
- kolom kosong menampilkan drop target dan empty state yang ringkas;
- mobile menggunakan horizontal scroll atau pemilih kolom, bukan memaksa semua kolom mengecil.

### 17.7 Side Peek dan Full Task Page

Plane membuka detail Task sebagai side peek secara default dan menyediakan mode full screen. Sitop mengikuti pola ini.

```text
List / Board
      ↓ klik Task
Side peek
├── Header ringkas
├── ID dan Title
├── Meta row
├── Description
├── Sub-task / Relasi / Bukti
├── Comment composer
└── Activity Log
      ↓ Open full Task
Full Task page
├── Breadcrumb Project
├── Header actions
├── ID di atas Title
├── Meta row
├── Description
├── Widget toolbar
├── Activity / Comments
└── Properties sidebar
```

Header Task:

- breadcrumb Project;
- Task ID yang dapat disalin;
- Title yang dapat diedit sesuai permission;
- status, priority, PIC, start date, dan deadline sebagai meta row;
- Subscribe atau ikuti Task;
- Copy link;
- menu `...` untuk archive, restore, dan aksi lain sesuai permission.

Widget toolbar:

- Tambah Sub-task;
- Tambah dependency;
- Tambah relasi;
- Tambah link;
- Tambah attachment;
- Tambah bukti;
- Buka Activity Log.

Properties sidebar dikelompokkan:

```text
Details
├── Parent Task
├── Label
└── Priority tambahan

Project structure
├── Milestone
└── Project

Metadata
├── Pemohon
├── Created by
├── Created at
├── Updated at
└── Completed at
```

### 17.8 Task Description dan Comment

Description adalah area konteks utama Task. Gunakan editor rich text ringan untuk heading, list, checklist, link, gambar, dan `@mention`.

Komentar berada setelah konten utama dan memakai thread kronologis:

```text
Comment composer
├── Mention
├── Attachment
└── Comment

Comment thread
├── Avatar / name
├── Time
├── Rich text
├── Attachment
├── Edit / Delete own comment
└── Reply (Follow-up)
```

### 17.9 Search dan command entry

Search dibuka dari top bar atau `Cmd/Ctrl + K`.

Hasil MVP dikelompokkan:

```text
All
├── Projects
└── Tasks
```

Hasil berikutnya dapat ditambahkan:

```text
Comments | Activity Log | Pages | Milestones | Views
```

Search mendukung:

- Task ID seperti `DEV-123`;
- nama Project atau Project ID;
- Title Task;
- keyword pada deskripsi Task;
- hasil yang hanya berasal dari scope yang boleh diakses user.

### 17.10 Project Settings

Project Settings menggunakan tab atau menu vertikal seperti Plane:

```text
General
├── Name, Project ID, description
├── Unit, visibility, status
├── dates dan timezone
└── archive

Members
├── Project Lead
├── Default Assignee
├── Project subscribers (Follow-up)
└── Member list dan Project role

Work structure
├── States
├── Labels
└── Milestones

Features
├── Milestones
├── Views (Follow-up)
├── Pages (Follow-up)
└── Project Updates (Follow-up)

Audit Log
```

Project Admin melihat seluruh tab. Role lain hanya melihat tab yang diizinkan. Project arsip tidak dapat diedit sebelum di-restore.

### 17.11 Archive dan restore

Archive bukan delete.

```text
Active Project
      ↓ Project Admin: Archive
Confirmation dialog
      ↓
Archived Project
      ↓ Project Admin: Restore
Active Project
```

Saat Project diarsipkan:

- Project dihapus dari sidebar aktif;
- Task dan resource Project ikut diarsipkan;
- data tetap utuh;
- Project tidak muncul di search aktif;
- item tersedia di area `Archived Projects`;
- hanya Project Admin yang dapat restore;
- delete permanen memakai dialog berisi konsekuensi eksplisit.

## 18. Kontrak perilaku yang mengikuti Plane

| Area | Perilaku sitop |
| --- | --- |
| Workspace | Satu sesi login bekerja di satu Workspace aktif pada satu waktu |
| Project | Project menjadi konteks utama Task dan resource kerja |
| Task | Title adalah input wajib; Project dan Task ID mengikuti konteks |
| Identifier | Project ID menjadi prefix Task, misalnya `DEV-123` |
| Layout | List dan Board menampilkan data Task yang sama |
| Detail | Side peek menjadi default; full page tersedia |
| Properties | Field umum berada di meta row; field sekunder di sidebar |
| Membership | Workspace role dan Project role terpisah |
| Lead | Project Lead adalah designation, bukan permission role |
| Default Assignee | Opsional di Project |
| Archive | Data dipertahankan dan dapat di-restore |
| Feature menu | Feature toggle menambah atau menyembunyikan menu Project |
| Search | Hanya menampilkan objek yang boleh diakses |

Adaptasi khusus sitop:

- Unit menjadi konteks organisasi tambahan.
- Bahasa dan istilah memakai bahasa Indonesia.
- `Done` wajib memiliki catatan penyelesaian atau bukti.
- `Blocked`, `Canceled`, dan `Reopen` wajib memiliki alasan.
- Audit Log lebih menonjol karena kebutuhan keterlacakan operasional.
- MVP hanya mengaktifkan fitur yang masuk PRD MVP Core dan Follow-up.

## 19. Checklist detail sebelum implementasi UI

Setiap layar harus menjawab:

- Workspace dan Project apa yang sedang aktif?
- User melihat data dalam scope apa?
- User dapat melakukan aksi apa?
- Apakah action tersebut membutuhkan Project Admin, Contributor, Commenter, atau Viewer?
- Apa yang terjadi ketika data kosong?
- Apa yang terjadi ketika request gagal?
- Apakah perubahan status membutuhkan alasan atau bukti?
- Apakah Activity Log dan Audit Log perlu diperbarui?
- Apakah side peek mempertahankan konteks daftar?
- Bagaimana perilakunya di mobile?
- Apa alternatif non-drag untuk Board?
- Apa yang terjadi setelah archive, restore, atau delete?
