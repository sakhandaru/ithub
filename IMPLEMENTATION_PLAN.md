# sitop Implementation Plan

## 1. Tujuan dokumen

Dokumen ini membagi implementasi `PRD.md` menjadi fase yang dapat dikerjakan, diuji, dan diterima secara bertahap. `DESIGN.md` tetap menjadi acuan visual dan interaksi. `AGENTS.md` tetap menjadi acuan cara kerja teknis.

Rencana dimulai dari baseline aplikasi pada 10% sampai seluruh scope MVP Core dan MVP Follow-up di PRD selesai.

Angka 100% dalam dokumen ini berarti:

- seluruh MVP Core selesai;
- seluruh MVP Follow-up selesai;
- Definition of Done pada PRD terpenuhi;
- kriteria keberhasilan pilot dapat diukur;
- fitur di bagian `Di luar MVP` tidak termasuk perhitungan.

Persentase adalah ukuran kematangan kemampuan produk, bukan jumlah file, migration, halaman, atau baris kode. Persentase dapat dikoreksi setelah evaluasi akhir setiap fase, tetapi urutan dependensi tidak boleh dilompati tanpa keputusan tertulis.

## 2. Kondisi saat ini

Status saat ini: **97%**.

Yang sudah tersedia:

- autentikasi dan session dari starter kit;
- kewajiban verifikasi email dapat dimatikan melalui konfigurasi;
- nama aplikasi menggunakan `sitop`;
- application shell dengan navigasi dua tingkat;
- primary rail untuk Pekerjaan dan Pengaturan;
- context sidebar yang dapat dilipat;
- pola drawer mobile dari komponen sidebar;
- dashboard awal dengan empty state tanpa data palsu;
- halaman pengaturan akun bawaan;
- fondasi dark mode, focus state, dan pointer affordance;
- `PRD.md` dan `DESIGN.md` sebagai sumber keputusan;
- enum dasar role, visibility, status, dan priority;
- request context untuk actor, request ID, dan timezone;
- konfigurasi attachment privat dan batas file;
- keputusan teknis fondasi domain untuk fase berikutnya;
- Workspace, Unit, membership, role, dan active Workspace;
- halaman onboarding dan pengaturan Workspace;
- authorization backend dan isolasi direct-object;
- Audit Log minimum untuk perubahan membership dan Unit.

Yang belum tersedia:

- komentar, attachment, bukti, Activity Log, dan layar Audit Log lengkap;
- List, Board, side peek, serta detail Project dan Task;
- dashboard berbasis data;
- Global Search, filter lanjutan, dan notifikasi;
- seluruh kemampuan MVP Follow-up.

## 3. Aturan eksekusi setiap fase

Setiap fase wajib mengikuti urutan berikut:

1. Baca kembali bagian PRD dan DESIGN yang terkait.
2. Periksa konvensi project dan komponen yang sudah tersedia.
3. Tetapkan model data, permission matrix, route, dan state UI yang diperlukan.
4. Implementasikan aturan bisnis dan authorization di backend.
5. Implementasikan UI hanya untuk aksi yang sudah memiliki backend nyata.
6. Tambahkan test untuk happy path, validation, permission, dan object access.
7. Jalankan formatter, type check, test terfokus, dan production build.
8. Periksa desktop, tablet, mobile, keyboard, focus, empty state, dan error state.
9. Perbarui checklist fase dan catat keputusan yang mengubah PRD atau DESIGN.

Satu fase belum selesai jika hanya frontend atau backend yang tersedia. Fase dianggap selesai setelah exit criteria-nya terpenuhi.

## 4. Ringkasan fase

| Fase | Hasil akhir | Progress kumulatif |
| --- | --- | ---: |
| 0 | Baseline dan application shell | 10% |
| 1 | Fondasi domain dan kualitas teknis | 15% |
| 2 | Workspace, Unit, membership, dan role | 25% |
| 3 | Project dan lifecycle Project | 40% |
| 4 | Task engine dan workflow | 58% |
| 5 | List, detail, dan side peek Task | 70% |
| 6 | Kolaborasi, bukti, Activity Log, dan Audit Log | 80% |
| 7 | Board, dashboard Project, hardening, dan pilot Core | 85% |
| 8 | Sub-task, relasi, dan milestone | 91% |
| 9 | Dashboard Workspace, search, filter, dan notifikasi | 97% |
| 10 | State lengkap, archive lanjutan, optimasi, dan pilot akhir | 100% |

MVP Core selesai pada akhir Fase 7. MVP Follow-up selesai pada akhir Fase 10.

## 5. Fase 0: Baseline dan application shell

Status: **selesai untuk baseline awal**.

Progress: **0% sampai 10%**.

### Scope

- autentikasi dan session dasar;
- konfigurasi verifikasi email;
- identitas produk sitop;
- navigasi dua tingkat;
- shell desktop dan mobile;
- context sidebar yang dapat dilipat;
- dashboard empty state;
- route Pengaturan nyata;
- baseline aksesibilitas dan responsive layout.

### Exit criteria

- user dapat login tanpa dipaksa melakukan verifikasi email saat konfigurasi dimatikan;
- dashboard hanya dapat diakses oleh user yang login;
- primary rail dan context sidebar dapat digunakan;
- fitur roadmap tidak muncul sebagai tombol palsu;
- type check, build, dan test autentikasi lulus.

### Catatan lanjutan

- Workspace switcher belum boleh ditampilkan sebelum Workspace tersedia.
- Search dan notifikasi belum boleh ditampilkan sebelum backend tersedia.
- Area Pengetahuan, Operasional, Kepatuhan, dan Laporan tetap disembunyikan.

## 6. Fase 1: Fondasi domain dan kualitas teknis

Progress: **10% sampai 15%**.

Status: **selesai**.

### Tujuan

Menyiapkan batas teknis yang akan dipakai semua domain agar Workspace, Project, dan Task tidak dibangun dengan pola yang saling bertentangan.

### Backend

- tetapkan penggunaan UUID, ULID, atau integer untuk primary key sesuai konvensi project;
- tetapkan strategi archive, restore, soft delete, dan permanent delete;
- buat kontrak actor, request ID, timezone, dan metadata perubahan;
- siapkan action atau service untuk operasi bisnis yang memiliki lebih dari satu perubahan data;
- siapkan transaction boundary agar request gagal tidak meninggalkan perubahan parsial;
- siapkan pola Policy dan query scope untuk Workspace, Unit, Project, serta Task;
- siapkan storage privat untuk attachment;
- tetapkan batas ukuran dan tipe file melalui konfigurasi;
- tetapkan enum dasar untuk role, visibility, status, dan priority;
- tetapkan pola factory dan test helper domain untuk dipakai bersama model nyata pertama.

### Frontend

- tetapkan pola page header, toolbar, dialog, sheet, empty state, loading state, dan error state;
- tetapkan komponen status yang tidak mengandalkan warna saja;
- tetapkan pola toast dan feedback penyimpanan;
- tetapkan pola responsive untuk tabel, side peek, dan Board;
- lanjutkan penggunaan Wayfinder untuk seluruh route frontend.

### Test minimum

- enum dasar memiliki nilai persisted yang diharapkan;
- file privat tidak tersedia melalui public URL;
- request context memakai request ID buatan aplikasi serta membawa actor dan timezone;
- transaction rollback dan authorization default diuji pada operasi domain nyata pertama di Fase 2.

### Exit criteria

- keputusan identifier, archive, timezone, audit metadata, dan storage tercatat di kode atau dokumentasi yang relevan;
- pola action, Policy, Form Request, dan test helper dapat dipakai fase berikutnya;
- tidak ada dependency baru tanpa persetujuan.

### Keputusan Fase 1

- Model domain baru memakai ULID sebagai primary key. `users.id` tetap memakai integer agar autentikasi yang sudah ada tidak dirombak tanpa kebutuhan.
- Identifier manusia untuk Task, misalnya `DEV-123`, terpisah dari primary key database.
- Waktu disimpan dalam UTC. Tampilan mengikuti timezone Workspace dengan default `Asia/Jakarta` sebelum Workspace tersedia.
- Archive memakai metadata eksplisit seperti `archived_at` dan `archived_by`. Archive tidak diperlakukan sebagai delete.
- Soft delete hanya dipakai pada objek yang memang mendukung penghapusan oleh pengguna dan perlu dipulihkan. Permanent delete harus menjadi operasi terpisah dan berizin khusus.
- Operasi bisnis lintas beberapa perubahan data ditempatkan pada action final dengan method `handle()` dan transaction boundary di dalam action. Tidak dibuat base action generik.
- Policy dan query scope akan dibuat eksplisit untuk setiap domain. Akses lintas Workspace atau Project harus ditolak di backend; keberadaan objek dapat disamarkan dengan respons tidak ditemukan bila diperlukan.
- Attachment memakai disk `attachments` yang privat, tidak disajikan langsung oleh web server, berukuran maksimum awal 10 MiB, dan dibatasi pada tipe dokumen serta gambar yang dikonfigurasi.
- Request context menyediakan `request_id`, `actor_id`, dan `timezone`. Request ID selalu dibuat oleh aplikasi dan dikembalikan melalui header `X-Request-ID`.
- Enum awal memakai nilai persisted yang stabil untuk Workspace role, Project role, Project visibility, Project status, Task status, dan Task priority.
- Komponen antarmuka yang sudah ada menjadi pola dasar header, dialog, sheet, empty state, loading, error, toast, focus, dark mode, dan responsive layout. Komponen baru dibuat ketika kebutuhan domain nyata muncul.
- Factory domain dibuat bersama model pertamanya pada fase terkait agar tidak menghasilkan abstraksi tanpa perilaku.
- Pengujian rollback transaction dan default-deny Policy dilakukan pada action dan Policy domain nyata pertama di Fase 2. Pengujian buatan yang hanya menguji framework tidak ditambahkan.

## 7. Fase 2: Workspace, Unit, membership, dan role

Progress: **15% sampai 25%**.

Status: **selesai**.

### Tujuan

Membuat scope akses utama sebelum data Project dan Task dibuat.

### Model data

- Workspace;
- Workspace membership;
- Unit;
- Unit membership;
- Workspace role: Owner, Workspace Admin, Member, Guest;
- active Workspace per session atau user preference;
- timezone Workspace;
- invitation atau proses penambahan anggota minimum yang dibutuhkan pilot.

### Aturan bisnis

- satu user memiliki satu role pada satu Workspace;
- user dapat menjadi anggota beberapa Unit;
- tiga Unit awal tersedia: Development, Infra, dan Operasional;
- Workspace Admin mengelola anggota dan Unit;
- Guest tidak memperoleh akses di luar scope yang diberikan;
- menghapus membership tidak boleh menghapus Activity Log atau Audit Log lama;
- akses selalu diperiksa di backend.

### UI

- Workspace switcher nyata pada context sidebar;
- daftar dan detail anggota Workspace;
- daftar Unit dan anggotanya;
- pengaturan timezone Workspace;
- unauthorized, empty, loading, dan validation state;
- mobile drawer menampilkan Workspace aktif tanpa memenuhi layar dengan kontrol sekunder.

### Test minimum

- Owner dan Workspace Admin dapat mengelola membership sesuai aturan;
- Member dan Guest tidak dapat menaikkan role sendiri;
- user di luar Workspace tidak dapat membaca Workspace melalui direct URL;
- perubahan role dan membership tercatat;
- active Workspace tidak dapat dipilih jika user bukan anggota.

### Exit criteria

- seluruh request domain memiliki Workspace scope;
- role Workspace bekerja di backend dan UI;
- tiga Unit awal tersedia tanpa bergantung pada data palsu;
- seluruh test permission dan direct-object reference lulus.

### Implementasi dan keputusan Fase 2

- User tanpa Workspace diarahkan ke onboarding dan dapat membuat Workspace sendiri sebagai Owner.
- Pembuatan Workspace memakai satu transaction boundary dan langsung membuat Unit `Development`, `Infra`, dan `Operasional`.
- Anggota Workspace ditambahkan dari user yang sudah terdaftar. Undangan email eksternal belum dibuat pada fase ini.
- Owner dan Workspace Admin dapat mengelola anggota serta Unit. Unit tidak memiliki role khusus pada MVP.
- Owner adalah satu-satunya role yang dapat memberikan atau mengubah role Owner. Owner terakhir tidak dapat diturunkan atau dihapus.
- Workspace yang tidak diikuti user tidak dikonfirmasi keberadaannya melalui direct URL dan mengembalikan `404`.
- Workspace aktif disimpan pada preferensi user dan ditampilkan di context sidebar tingkat kedua.
- Pengaturan Workspace menggabungkan General, anggota, role, Unit, dan timezone dalam satu halaman responsif.
- Audit Log minimum mencatat pembuatan Workspace, perubahan Workspace, perubahan role, penambahan/penghapusan anggota, serta perubahan Unit.

## 8. Fase 3: Project dan lifecycle Project

Progress: **25% sampai 40%**.

Status: **selesai**.

### Tujuan

Menyediakan konteks utama tempat Task dan resource kerja dikelola.

### Model data

- Project;
- Project membership;
- Project role: Project Admin, Contributor, Commenter, Viewer;
- Project Lead sebagai designation;
- Unit utama;
- visibility: Workspace, Unit, Private;
- status: Draft, Planned, Active, On Hold, Completed, Canceled;
- Project ID unik dalam Workspace;
- Default Assignee opsional;
- tanggal mulai dan target selesai;
- archive dan restore metadata.

### Aturan bisnis

- hanya Owner dan Workspace Admin yang membuat Project secara default;
- creator menjadi Project Admin;
- creator dapat menjadi Project Lead secara default;
- visibility default adalah Unit;
- Project ID tidak boleh berubah tanpa aturan migrasi identifier yang jelas;
- Default Assignee harus Project Admin atau Contributor;
- Project arsip bersifat read-only sampai dipulihkan;
- archive tidak menghapus data atau histori;
- hanya role yang berwenang dapat mengelola anggota dan konfigurasi.

### UI

- daftar Project aktif dan arsip;
- form pembuatan Project;
- Project overview;
- context sidebar Project;
- Project settings untuk General dan Members;
- status, visibility, Unit, Project Lead, dan tanggal;
- archive dan restore dengan confirmation dialog;
- tampilan berbeda untuk read-only Project arsip.

### Test minimum

- Project ID unik per Workspace;
- visibility Workspace, Unit, dan Private membatasi query dan direct URL;
- Project role berbeda pada setiap Project;
- Project Lead tidak otomatis memperoleh permission Project Admin;
- archive dan restore mempertahankan membership serta histori;
- user tanpa permission tidak dapat mengubah Project melalui request langsung.

### Exit criteria

- Project dapat dibuat, dibaca, diubah, diarsipkan, dan dipulihkan;
- Project list dan overview memakai data nyata;
- context sidebar berubah mengikuti Project aktif;
- seluruh aturan visibility dan role diuji.

### Implementasi dan keputusan Fase 3

- Project memakai ULID dan Project ID yang unik dalam satu Workspace.
- Creator otomatis menjadi Project Admin dan Project Lead. Project Lead adalah designation, bukan permission tambahan.
- Default Assignee boleh kosong; jika diisi, user harus anggota Workspace dan otomatis ditambahkan sebagai Contributor bila belum menjadi anggota Project.
- Visibility diterapkan pada query scope dan route binding backend untuk Workspace, Unit, dan Private. Akses lintas scope menghasilkan `404`.
- Project Admin adalah role membership yang mengelola konfigurasi dan anggota. Project Lead tidak otomatis menjadi Project Admin.
- Project yang diarsipkan hanya boleh berstatus Completed atau Canceled, menyimpan metadata archive, dan menjadi read-only sampai dipulihkan.
- Project membership melindungi Project Admin terakhir agar tidak dapat dihapus atau diturunkan.
- Project list, create, overview, settings, members, archive, dan restore sudah memakai data nyata serta Wayfinder.
- Audit Log minimum mencatat pembuatan, perubahan, archive, restore, dan perubahan membership Project.

## 9. Fase 4: Task engine dan workflow

Progress: **40% sampai 58%**.

Status: **selesai**.

### Tujuan

Menyelesaikan aturan bisnis inti Task sebelum membangun tampilan List dan Board yang kompleks.

### Model data

- Task;
- counter identifier per Project;
- status Task;
- priority;
- PIC Task;
- Pemohon;
- creator;
- deadline dan completion timestamp;
- label dan pivot Task label;
- alasan Blocked, Canceled, dan Reopen;
- catatan penyelesaian;
- bukti minimum berbentuk catatan atau URL sebelum file attachment tersedia.

### Aturan bisnis

- pembuatan Task hanya membutuhkan Title;
- Project berasal dari konteks route;
- identifier berbentuk `{PROJECT_ID}-{NUMBER}`;
- Pemohon default sama dengan creator;
- status awal adalah Backlog;
- Default Assignee diterapkan bila tersedia;
- PIC hanya dapat berasal dari Project Admin atau Contributor;
- transition status mengikuti workflow standar PRD;
- Blocked, Canceled, dan Reopen wajib memiliki alasan;
- Done wajib memiliki catatan penyelesaian atau bukti;
- Contributor hanya mengubah Task sesuai permission dan tanggung jawabnya;
- setiap transition divalidasi backend, termasuk request dari Board nanti.

### API dan action

- create Task;
- update property Task;
- assign dan unassign PIC;
- update status melalui action transition khusus;
- add dan remove label;
- update deadline, priority, dan Pemohon;
- complete, cancel, block, reopen;
- endpoint memakai Form Request dan Policy.

### Test minimum

- identifier berurutan dan tidak bentrok saat request bersamaan;
- Title wajib, field lain opsional saat membuat Task;
- transition legal berhasil dan transition ilegal ditolak;
- alasan serta bukti wajib diterapkan;
- Default Assignee mengikuti konfigurasi Project;
- PIC dari role yang tidak valid ditolak;
- akses lintas Project dan Workspace ditolak;
- operasi gagal tidak meninggalkan perubahan parsial.

### Exit criteria

- seluruh workflow Task dapat dijalankan melalui backend;
- aturan Done, Blocked, Canceled, dan Reopen tidak dapat dilewati dari request langsung;
- test permission dan concurrency identifier lulus.

### Implementasi dan keputusan Fase 4

- Task memakai ULID sebagai primary key dan nomor integer berurutan yang dikunci pada row Project saat pembuatan.
- Identifier Task berbentuk `{PROJECT_ID}-{NUMBER}` dan tidak memakai counter global lintas Project.
- Task baru selalu berstatus `Backlog`, requester default adalah creator, dan Default Assignee Project diterapkan bila tersedia.
- PIC hanya dapat berasal dari Project Admin atau Contributor pada Project yang sama. Assignment memiliki endpoint khusus untuk assign dan unassign.
- Workflow legal divalidasi dalam action transition terpisah. `Blocked`, `Canceled`, dan reopen ke `In Progress` membutuhkan alasan.
- Status `Done` membutuhkan completion note atau evidence URL dan mengisi `completed_at`.
- Contributor dapat membuat Task dan mengubah Task yang ditugaskan atau dibuat olehnya. Project Admin dapat mengelola seluruh Task.
- Label Project dan relasi Task-Label sudah tersedia melalui create/update payload; CRUD UI Label ditunda sampai kebutuhan layar kerja digunakan di fase berikutnya.
- Custom route binding memastikan Task tidak dapat diakses lintas Project, Workspace, atau visibility scope.
- Audit Log minimum mencatat pembuatan Task, perubahan Task, assignment, dan transition status.

## 10. Fase 5: List, detail, dan side peek Task

Progress: **58% sampai 70%**.

Status: **selesai**.

### Tujuan

Menyediakan alur harian create, assign, work, review, dan done melalui UI Plane-like.

### UI List

- daftar Task dalam konteks Project;
- kolom ID, Title, status, priority, PIC, deadline, dan label;
- group status sebagai default;
- filter dasar status, PIC, priority, label, dan deadline;
- sort dasar;
- quick add Task;
- inline update untuk properti yang aman;
- feedback menyimpan, gagal, dan conflict;
- pagination atau cursor strategy yang tetap stabil pada data besar.

### UI detail

- side peek sebagai detail default dari List;
- full Task page;
- breadcrumb Project;
- copy Task ID dan copy link;
- Title dan description;
- meta row untuk status, priority, PIC, dan deadline;
- properties untuk Pemohon, creator, created at, updated at, dan completed at;
- dialog alasan dan catatan penyelesaian;
- mode read-only untuk user dan Project arsip.

### Responsive

- List berubah menjadi baris ringkas atau layout yang dapat dibaca di mobile;
- side peek menjadi full-screen sheet pada mobile;
- properti tidak dipaksa dalam tabel sempit;
- semua target utama memiliki area sentuh memadai;
- tidak ada horizontal overflow pada halaman, kecuali container data yang memang dirancang untuk scroll.

### Test minimum

- halaman hanya memuat Task yang boleh dilihat user;
- quick add dan inline update mengikuti validation serta permission;
- side peek dan full page menampilkan data yang sama;
- filter tidak membocorkan data lintas scope;
- state empty, loading, validation, unauthorized, not found, dan archived dapat dicapai.

### Exit criteria

- satu Task dapat diselesaikan penuh dari UI;
- side peek mempertahankan konteks List;
- desktop dan mobile dapat menjalankan alur yang sama;
- tidak ada kontrol yang tampil tanpa backend nyata.

### Implementasi dan keputusan Fase 5

- Task List berada di bawah konteks Project dan hanya memuat Task yang lolos scope Project.
- Quick add hanya meminta Title; field lain tetap dikelola dari detail Task.
- Filter awal mencakup pencarian ID/judul, status, dan priority. Query filter dipertahankan saat side peek dibuka.
- Side peek memakai parameter query `task`, sehingga daftar tetap menjadi konteks utama dan dapat ditutup tanpa kehilangan filter.
- Pada mobile side peek menjadi panel full-screen; pada desktop tampil sebagai panel konteks di sisi kanan.
- Halaman detail penuh memakai sumber data dan action yang sama dengan side peek.
- Task List, detail, create, update, assignment, dan transition memakai Wayfinder untuk seluruh pemanggilan route.
- Project arsip menampilkan daftar/detail dalam mode read-only dan tidak menampilkan quick add atau aksi perubahan.

## 11. Fase 6: Kolaborasi, bukti, Activity Log, dan Audit Log

Progress: **70% sampai 80%**.

Status: **selesai**.

### Tujuan

Memberi Task konteks kerja dan keterlacakan yang menjadi outcome utama sitop.

### Model data

- comment;
- attachment metadata;
- evidence;
- Activity Log;
- Audit Log;
- actor, action, timestamp, object, request ID, before value, dan after value;
- subscription minimum jika diperlukan untuk notifikasi fase berikutnya.

### Aturan bisnis

- Project Admin, Contributor, dan Commenter dapat menambahkan komentar dan attachment;
- Viewer hanya membaca;
- user hanya mengedit atau menghapus komentarnya sendiri, kecuali permission administratif ditetapkan;
- attachment privat selalu melewati authorization download;
- tipe file berbahaya dan ukuran berlebih ditolak;
- perubahan status, PIC, priority, deadline, role, permission, attachment privat, archive, dan restore masuk Audit Log;
- Activity Log hanya berisi aktivitas yang berguna bagi pengguna;
- Audit Log menyimpan detail perubahan untuk kebutuhan pelacakan;
- kegagalan upload tidak boleh menyisakan record yatim.

### UI

- comment composer dan thread kronologis;
- upload progress, gagal, retry, dan remove;
- daftar attachment dan bukti;
- Activity Log pada detail Task dan Project;
- Audit Log pada Project settings sesuai permission;
- download privat dengan feedback ketika akses ditolak atau file hilang.

### Test minimum

- Commenter dapat berkomentar tetapi tidak dapat mengubah Task;
- Viewer tidak dapat mengunggah attachment;
- URL attachment tidak dapat diakses user tanpa permission;
- file berbahaya dan terlalu besar ditolak;
- perubahan penting menghasilkan Audit Log yang tepat;
- Activity Log dan Audit Log mengikuti scope user;
- actor dan request ID tercatat.

### Exit criteria

- Task memiliki komentar, attachment, bukti, dan histori nyata;
- aturan Done dapat memakai bukti file, URL, atau catatan penyelesaian;
- audit perubahan penting dapat ditelusuri ke actor dan waktu.

### Implementasi dan keputusan Fase 6

- Comment disimpan sebagai objek Task dengan actor, timestamp, dan authorization untuk create/update/delete.
- Commenter dapat berkomentar dan mengunggah attachment, tetapi tidak dapat mengubah property atau status Task.
- Attachment memakai disk privat `attachments`, metadata database, dan download melalui controller yang selalu melewati Policy.
- File dibatasi oleh konfigurasi extension, MIME yang diizinkan, dan ukuran maksimum 10 MiB. Record tidak dibuat jika validasi gagal.
- Kegagalan penyimpanan atau transaksi attachment menghapus file yang sudah terlanjur ditulis agar tidak meninggalkan file yatim.
- Activity Log memakai subject polymorphic sederhana, actor, action, description, properties, dan request ID.
- Audit Log mencatat perubahan penting Task, Project, assignment, status, attachment, membership, archive, dan restore.
- Detail Task menampilkan komentar, attachment, Activity Log, serta bukti completion. Overview Project menampilkan Activity Log dan Project settings menampilkan Audit Log sesuai permission.
- Penghapusan attachment dan komentar memakai authorization object-level serta mempertahankan histori Activity/Audit yang relevan.

## 12. Fase 7: Board, dashboard Project, hardening, dan pilot Core

Progress: **80% sampai 85%**.

Status: **selesai**.

### Tujuan

Menutup seluruh scope MVP Core dan menyiapkannya untuk pilot terbatas.

### Board

- kolom workflow standar;
- kartu berisi ID, Title, priority, PIC, deadline, dan label;
- drag and drop dengan validasi backend;
- rollback visual ketika perpindahan gagal;
- dialog alasan atau bukti sebelum transition tertentu;
- alternatif non-drag untuk keyboard dan mobile;
- horizontal scroll atau pemilih kolom pada mobile.

### Dashboard Project

- jumlah Task per status;
- Task terlambat;
- Task terblokir;
- Task tanpa PIC;
- Task tanpa deadline;
- progress Project;
- Activity Log terbaru;
- seluruh ringkasan dapat membuka daftar Task dengan filter terkait.

### Hardening Core

- review seluruh Policy dan query scope;
- test direct-object reference untuk Workspace, Unit, Project, Task, comment, attachment, dan log;
- review N+1 dan query dashboard;
- indeks database untuk query Project dan Task utama;
- rate limit pada operasi sensitif bila diperlukan;
- conflict handling untuk inline update dan transition;
- localization antarmuka aktif ke bahasa Indonesia;
- accessibility dan keyboard review;
- browser QA untuk desktop, tablet, dan mobile.

### Pilot Core

- gunakan satu Project pilot Development;
- masukkan pekerjaan aktif yang nyata;
- jalankan alur create, assign, work, review, done;
- catat masalah usability, permission, dan workflow;
- ukur kriteria keberhasilan MVP Core pada PRD.

### Exit criteria

- seluruh daftar MVP Core pada PRD selesai;
- tidak ada akses tidak sah yang lolos test;
- Board dan List memakai sumber data dan aturan workflow yang sama;
- seluruh Task Done memiliki catatan penyelesaian atau bukti;
- pilot dapat berjalan tanpa spreadsheet sebagai sumber status harian;
- temuan pilot memiliki keputusan: perbaiki sebelum Follow-up atau masukkan backlog.

### Implementasi dan keputusan Fase 7

- Board memakai daftar status `TaskStatus` yang sama dengan List dan action `TransitionTask` yang sama dengan detail Task.
- Drag-and-drop melakukan optimistic move. Jika request transition gagal, state kartu dikembalikan ke posisi sebelumnya.
- Select pada setiap kartu menjadi alternatif keyboard dan mobile untuk perpindahan status tanpa drag.
- Transition yang membutuhkan alasan atau bukti meminta input sebelum request dikirim.
- Dashboard Project menghitung total, progress selesai, overdue, Blocked, Task tanpa PIC, Task tanpa deadline, distribusi status, dan Activity terbaru dari database.
- Ringkasan dashboard membuka Task List dalam konteks Project yang sama.
- Board dan Dashboard Project bersifat read-only ketika Project diarsipkan.
- Query dashboard memakai indeks status, assignee, dan project yang sudah tersedia pada tabel Task.
- Direct-object binding untuk Task, Comment, Attachment, dan nested Project diuji agar objek lintas scope menghasilkan `404`.
- Pilot Core siap dijalankan pada Project Development; data pekerjaan nyata belum dimasukkan otomatis oleh aplikasi.

## 13. Fase 8: Sub-task, relasi, dan milestone

Progress: **85% sampai 91%**.

Status: **selesai**.

### Sub-task

- parent dan child Task;
- PIC, status, deadline, comment, attachment, log, dan bukti sendiri;
- progress Sub-task pada parent;
- aturan Done Sub-task tetap membutuhkan catatan penyelesaian atau bukti;
- parent boleh Done meskipun Sub-task belum selesai sesuai keputusan PRD;
- cegah hierarchy cycle dan kedalaman yang tidak terkendali.

### Relasi Task

- Related to;
- Duplicate of;
- Blocks;
- Blocked by;
- validasi relasi terbalik;
- cegah self-reference dan duplicate relation;
- relasi tidak mengubah status otomatis;
- perubahan relasi masuk Activity Log dan Audit Log.

### Milestone

- judul dan target date;
- satu Task berada pada maksimal satu milestone;
- progress dihitung dari Task terkait;
- grouping dan filtering berdasarkan milestone;
- pengaturan milestone di Project settings;
- menu hanya tampil ketika fitur milestone aktif.

### Hasil implementasi

- hierarchy Sub-task dengan `parent_task_id` dan pencegahan self-reference/cycle;
- Milestone Project dengan target date, create/delete, dan relasi ke Task;
- relasi Task `related`, `duplicate`, dan `blocks` dengan pencegahan relasi duplikat serta lintas Project;
- endpoint berscope Workspace → Project → Task dengan Policy backend;
- Audit Log dan Activity Log untuk pembuatan/penghapusan Milestone serta relasi Task;
- kontrol Sub-task, Milestone, dan relasi di halaman detail Task serta Project settings.

### Exit criteria

- hierarchy tidak dapat membentuk siklus atau menyeberangi Project;
- Milestone dan relasi hanya dapat dikelola oleh role yang memiliki izin update Project/Task;
- detail Task menampilkan dan mengelola Sub-task, Milestone, serta relasi nyata;
- data lama tidak memerlukan perubahan manual;
- permission, log, PHPStan, type check, build, dan seluruh test lulus.

### Keputusan Fase 8

- Sub-task tetap Task biasa agar status, PIC, komentar, attachment, dan Activity Log konsisten.
- Satu Task dapat memiliki satu parent dan satu Milestone; Milestone dapat memiliki banyak Task.
- Relasi disimpan berarah dengan tipe eksplisit. Relasi terbalik dengan pasangan dan tipe sama ditolak untuk mencegah duplikasi.
- Workflow bebas tetap menjadi ruang pengembangan berikutnya; Fase 8 hanya menambahkan struktur konteks Task.

## 14. Fase 9: Dashboard Workspace, search, filter, dan notifikasi

Progress: **91% sampai 97%**.

Status: **selesai**.

### Hasil implementasi

- Dashboard Workspace berbasis data untuk Task saya, overdue, Blocked, Task aktif, dan ringkasan Project;
- Global Search untuk Project dan Task dengan query scope Workspace serta permission Project;
- filter Task untuk PIC, Milestone, tanpa deadline, dan tanpa bukti, selain status, priority, dan pencarian;
- notifikasi in-app berbasis database untuk assignment, komentar baru, dan perubahan status;
- unread count pada header, daftar notifikasi, tautan objek, dan mark as read;
- test dashboard dan search, termasuk validasi scope akses.

### Dashboard Workspace

- Task saya;
- Task terlambat;
- Task terblokir;
- Project yang perlu perhatian;
- progress per Project;
- perubahan terbaru lintas Project;
- beban kerja per PIC;
- jumlah Task aktif, terlambat, dan terblokir per PIC;
- data mengikuti permission dan active Workspace.

### Global Search

- Project berdasarkan nama atau Project ID;
- Task berdasarkan identifier, Title, dan keyword description;
- hasil dikelompokkan menjadi Project dan Task;
- shortcut `Cmd/Ctrl + K`;
- hasil tidak membocorkan objek di luar permission;
- pencarian memiliki empty, loading, error, dan keyboard navigation state.

### Filter lanjutan

- Project;
- Unit;
- status;
- PIC;
- Pemohon;
- priority;
- label;
- deadline;
- milestone;
- kelengkapan bukti;
- tanpa PIC;
- tanpa deadline;
- filter aktif tampil sebagai chip yang dapat dihapus.

### Notifikasi in-app

- penugasan PIC;
- mention;
- komentar baru pada Task yang diikuti;
- perubahan status;
- deadline mendekat atau terlewat;
- Task Blocked;
- Task Done atau Reopen;
- unread count, mark as read, dan tautan ke objek;
- notifikasi tidak dikirim kepada user yang kehilangan akses.

### Exit criteria

- dashboard lintas Project dapat digunakan pimpinan tanpa laporan manual;
- search dan filter hanya menghasilkan data yang boleh diakses;
- notifikasi mengarah ke objek nyata dan menangani objek yang sudah diarsipkan;
- query tetap responsif pada volume data pilot.

## 15. Fase 10: State lengkap, archive lanjutan, optimasi, dan pilot akhir

Progress: **97% sampai 100%**.

### Penyelesaian state UI

Audit setiap layar untuk state yang relevan:

- loading;
- empty;
- success;
- validation failed;
- unauthorized;
- network failed;
- saved;
- conflict;
- archived;
- not found.

### Archive dan restore lanjutan

- Project arsip tidak muncul di daftar dan search aktif;
- resource Project tetap utuh;
- Project arsip read-only;
- restore mengembalikan akses dan navigasi secara konsisten;
- archive Task atau resource lain hanya ditambahkan bila sesuai PRD dan hasil pilot;
- permanent delete tetap dibatasi, dikonfirmasi, dan diaudit.

### Optimasi

- query profiling untuk List, Board, dashboard, search, Activity Log, dan Audit Log;
- indeks database berdasarkan query nyata;
- pagination atau cursor pada daftar besar;
- eager loading untuk mencegah N+1;
- upload dan download file diuji pada batas ukuran;
- timezone Workspace diuji pada deadline dan Activity Log;
- error logging dan observability minimum;
- backup dan recovery procedure untuk data pilot bila environment produksi sudah ditetapkan.

### Pilot akhir

- jalankan pilot lintas Development, Infra, dan Operasional;
- ukur seluruh kriteria keberhasilan MVP Core dan Follow-up;
- cek persentase Task dengan PIC dan deadline;
- cek seluruh Task Done memiliki bukti;
- audit sampel perubahan penting;
- lakukan review pimpinan untuk dashboard;
- lakukan review pengguna untuk List, Board, detail, komentar, dan notifikasi;
- tetapkan backlog pasca-MVP berdasarkan bukti pilot.

### Exit criteria

- seluruh scope MVP Core dan MVP Follow-up ditandai selesai;
- seluruh Definition of Done PRD terpenuhi;
- tidak ada critical permission, data loss, upload, atau audit defect;
- desktop, tablet, dan mobile lulus QA;
- kriteria keberhasilan pilot memiliki hasil terukur;
- fitur di luar MVP tetap tidak muncul sebagai kontrol aktif.

## 16. Gate kualitas wajib

Gate berikut berlaku pada setiap fase, bukan hanya menjelang 100%.

### Backend gate

- Form Request menangani validation;
- Policy menangani authorization objek;
- controller tetap tipis;
- operasi lintas record memakai action atau service dan transaction;
- query selalu memiliki scope Workspace atau Project yang benar;
- perubahan penting menghasilkan Audit Log;
- file privat selalu melewati authorization.

### Frontend gate

- route menggunakan Wayfinder;
- komponen memakai pola Svelte 5 yang berlaku;
- tidak ada tombol atau menu palsu;
- setiap aksi memiliki loading, disabled, success, dan error feedback yang relevan;
- state aktif tidak hanya mengandalkan warna;
- keyboard, focus, dan accessible name tersedia;
- mobile merupakan layout yang disengaja, bukan desktop yang diperkecil.

### Test gate

- happy path;
- validation failure;
- unauthorized role;
- user dari Workspace atau Project lain;
- archived object;
- direct-object reference;
- upload atau download bila fase melibatkan file;
- Activity Log dan Audit Log bila fase mengubah objek penting.

### Verification gate

Sebelum fase ditutup, jalankan sekurangnya:

```text
vendor/bin/pint --dirty --format agent
npm run types:check
php artisan test --compact [test terkait]
npm run build
```

Tambahkan pemeriksaan browser untuk route, form, dialog, keyboard, responsive layout, dan failure path yang diubah pada fase tersebut.

## 17. Urutan implementasi dalam satu fase

Untuk menghindari halaman yang tampak selesai tetapi belum aman, gunakan urutan vertikal berikut:

```text
Schema dan enum
    ↓
Model dan relation
    ↓
Policy dan query scope
    ↓
Form Request
    ↓
Action atau service
    ↓
Controller dan route
    ↓
Feature test
    ↓
Inertia page dan component
    ↓
Responsive dan accessibility QA
    ↓
Build dan browser verification
```

Satu kemampuan diselesaikan secara end-to-end sebelum berpindah ke kemampuan lain. Contoh, `Create Project` diselesaikan dari migration sampai browser verification sebelum mengerjakan `Archive Project`.

## 18. Urutan batch eksekusi yang disarankan

Setiap batch idealnya cukup kecil untuk ditinjau dan diuji secara mandiri.

### Batch A: Fondasi akses

1. Workspace dan Workspace membership.
2. Workspace role dan Policy.
3. Unit dan Unit membership.
4. Workspace switcher dan settings.

### Batch B: Project

1. Project schema, enum, factory, dan Policy.
2. Create Project.
3. Project list dan visibility.
4. Project overview dan context sidebar.
5. Members, Project role, Project Lead, dan Default Assignee.
6. Status, archive, dan restore.

### Batch C: Task engine

1. Task schema, identifier, factory, dan Policy.
2. Create dan edit Task.
3. Assignment, priority, deadline, label, dan Pemohon.
4. Workflow transition.
5. Done, Blocked, Canceled, dan Reopen rules.

### Batch D: Task UI

1. Task List dan quick add.
2. Side peek.
3. Full Task page.
4. Inline property update.
5. Filter, sort, dan group dasar.

### Batch E: Kolaborasi dan traceability

1. Comment.
2. Attachment privat.
3. Evidence dan completion note.
4. Activity Log.
5. Audit Log.

### Batch F: Penutupan Core

1. Board.
2. Dashboard Project.
3. Permission audit.
4. Performance pass.
5. Pilot MVP Core.

### Batch G: Follow-up

1. Sub-task.
2. Relasi Task.
3. Milestone.
4. Dashboard Workspace dan workload.
5. Global Search dan filter lanjutan.
6. Notifikasi in-app.
7. State, archive, performance, dan pilot akhir.

## 19. Progress checklist

- [x] Fase 0: Baseline dan application shell, 10%.
- [ ] Fase 1: Fondasi domain dan kualitas teknis, 15%.
- [ ] Fase 2: Workspace, Unit, membership, dan role, 25%.
- [ ] Fase 3: Project dan lifecycle Project, 40%.
- [ ] Fase 4: Task engine dan workflow, 58%.
- [ ] Fase 5: List, detail, dan side peek Task, 70%.
- [ ] Fase 6: Kolaborasi, bukti, Activity Log, dan Audit Log, 80%.
- [ ] Fase 7: Board, dashboard Project, hardening, dan pilot Core, 85%.
- [x] Fase 8: Sub-task, relasi, dan milestone, 91%.
- [x] Fase 9: Dashboard Workspace, search, filter, dan notifikasi, 97%.
- [ ] Fase 10: State lengkap, archive lanjutan, optimasi, dan pilot akhir, 100%.

## 20. Aturan perubahan rencana

- Perubahan urutan yang memengaruhi model data, role, permission, atau scope harus dicatat sebagai keputusan.
- Fitur baru tidak boleh dimasukkan ke MVP tanpa memperbarui PRD dan rencana ini.
- Temuan pilot boleh mengubah prioritas batch, tetapi tidak boleh melemahkan authorization, audit, bukti Done, atau data integrity.
- Workflow bebas, Unit Admin, aset, dokumen, kontrak, lisensi, kepatuhan, dan integrasi eksternal tetap di luar 100% rencana ini.
- Setelah satu fase selesai, progress checklist dan bagian kondisi awal harus diperbarui agar dokumen tetap mencerminkan kondisi aplikasi.
