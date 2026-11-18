# ithub Design

## 1. Tujuan dan arah

Dokumen ini menjadi sumber keputusan untuk visual, layout, interaction, responsive behavior, accessibility, dan frontend component architecture ithub.

PRD.md mengatur scope dan aturan produk. Dokumen ini mengatur cara user melihat dan menggunakan fitur. Detail teknis backend tetap mengikuti konvensi Laravel project.

### Arah produk

ithub menggunakan pola Unit dan Project management yang terinspirasi Plane:

- Unit menjadi ruang organisasi utama.
- Project menjadi konteks utama kumpulan Task.
- Task menjadi unit pekerjaan.
- List, Board, Calendar, dan Gantt menampilkan data Task yang sama.
- Activity Log dan Audit Log menjaga keterlacakan.
- Bukti dan catatan penyelesaian mendukung status Done.

ithub tidak menyalin branding Plane. Yang diadopsi hanya pola informasi dan interaksi yang cocok untuk pekerjaan TI internal.

### Prinsip visual

- Informasi kerja lebih penting daripada dekorasi.
- Kepadatan data harus nyaman untuk pekerjaan harian.
- Status, priority, deadline, PIC, dan risiko harus mudah dipindai.
- Konteks tampil sebelum tindakan.
- Warna mendukung makna, bukan menjadi satu-satunya penanda.
- Aksi utama terlihat. Aksi sekunder dan destruktif berada dalam menu atau dialog.
- Bahasa UI menggunakan bahasa Indonesia dan terminologi standar.
- Fitur yang belum aktif tidak tampil sebagai menu atau tombol palsu.

### Token visual dasar

- Surface memakai neutral cool yang ringan pada mode terang dan charcoal blue pada mode gelap.
- Primary action memakai accent indigo sebagai identitas kerja, bukan sebagai dekorasi di seluruh halaman.
- Border harus halus dan membantu grouping. Jangan memberi border pada setiap elemen kecil jika hierarchy sudah cukup jelas.
- Radius default memakai bentuk compact. Container utama memakai radius sedang, sedangkan control kecil memakai radius lebih kecil.
- Shadow dipakai untuk elevation yang nyata seperti popover, dialog, dan sheet. Card biasa mengandalkan surface dan border.
- Typography memakai Instrument Sans dengan ukuran body yang nyaman dan label yang rapat untuk data operasional.
- Focus ring menggunakan warna accent yang terlihat pada mode terang dan gelap.

## 2. Terminologi

| Gunakan | Definisi |
| --- | --- |
| Unit | Ruang organisasi yang dapat dibuat dan dikelola sendiri |
| Project | Wadah dan konteks kumpulan Task |
| Task | Satu pekerjaan individual |
| Project Lead | Penanggung jawab pelaksanaan Project |
| Project Admin | Pengelola permission dan konfigurasi Project |
| PIC Task | Orang yang mengerjakan atau bertanggung jawab atas Task |
| Pemohon | Orang yang mengajukan Task |
| Activity Log | Riwayat aktivitas pada Project atau Task |
| Audit Log | Catatan perubahan penting untuk pelacakan |
| Admin | Role login untuk pengelolaan sistem, User, Unit, dan membership |
| User | Akun operasional dengan akses berdasarkan membership |
| Auditor | Role login read-only untuk pemeriksaan dan bukti audit |
| Bukti | Catatan, attachment, atau URL pendukung penyelesaian |
| Catatan penyelesaian | Penjelasan hasil Task sebelum Done |
| Sub-task | Task anak dari Task lain |

Istilah teknis seperti assignee, work_items, dan audit_events hanya dipakai dalam kode, bukan sebagai istilah UI.

## 3. Shell dan navigasi

ithub menggunakan navigasi dua tingkat.

    Application Shell
    ├── Primary Rail
    ├── Context Sidebar
    └── Main Content

### Primary Rail

Primary Rail berada paling kiri pada desktop dan hanya berpindah area bisnis.

    [Logo ithub]

    Pekerjaan
    Pengetahuan
    Operasional
    Kepatuhan
    Laporan

    Pengaturan

Aturan:

- area aktif memiliki indikator bentuk dan label, bukan warna saja;
- Pengaturan terpisah dari area bisnis;
- fitur yang belum aktif tidak menjadi link;
- pada mobile, Primary Rail menjadi menu geser atau menu area.

### Context Sidebar

Context Sidebar menampilkan konteks yang sedang dipakai:

    Logo dan nama ithub
    Unit switcher
    Global Search
    Notifikasi
    Menu area aktif
    Unit atau Project terkait
    Pengaturan Unit
    Administrasi (Admin)
    Profil dan keluar

#### Shell Pekerjaan

Sidebar Pekerjaan mengikuti pola Plane yang disesuaikan dengan istilah ithub. Shell ini selalu menyediakan peta navigasi lengkap, sementara fitur yang belum tersedia tampil non-interaktif dengan penanda `Segera hadir`.

    Pekerjaan
    ├── Task baru
    ├── Beranda
    └── Tugas saya

    Unit
    ├── Projects
    ├── Aktivitas
    ├── Dasbor
    ├── Analitik
    ├── Arsip
    └── Pengaturan Unit

    Projects
    └── [Project aktif]
        ├── Task
        ├── Views
        ├── Pages
        ├── Cycles
        ├── Modules
        ├── Intake
        ├── Releases
        ├── Milestone
        ├── Pembaruan Project
        ├── Activity Log
        └── Pengaturan Project

Aturan shell:

- daftar Project berasal dari Project yang dapat dilihat user pada Unit aktif;
- Project aktif membuka submenu pada item Project yang sama;
- Task adalah istilah ithub untuk work item;
- item `Segera hadir` bukan link, tidak dapat difokuskan sebagai aksi, dan tidak menjanjikan fitur yang belum ada;
- item aktif tetap memiliki label, bentuk, dan indikator kontras, bukan warna saja;
- sidebar tetap dapat discroll pada mobile tanpa memotong label atau status.

Saat Admin membuka Administrasi, Context Sidebar menampilkan:

    Manajemen User
    Manajemen Unit
    Role & Akses
    Audit Log
    Pengaturan Sistem

Saat berada di Project:

    Project
    ├── Ikhtisar
    ├── Daftar Task
    ├── Papan
    ├── Calendar
    ├── Gantt
    ├── Milestone
    ├── Activity Log
    └── Pengaturan Project

Menu yang tersedia dapat menampilkan peta fitur masa depan, tetapi hanya fitur aktif yang menjadi link. Fitur yang belum tersedia harus tampil sebagai `Segera hadir` tanpa aksi palsu.

Menu Project dikelompokkan agar user dapat membedakan konteks, pekerjaan harian, insight, dan konfigurasi:

- Project: Ikhtisar;
- Ruang kerja: Task, Board, Calendar, dan Gantt;
- Insight: Dashboard, Milestone, dan Activity Log;
- Pengaturan: Pengaturan Project.

Pada mobile, grouping berubah menjadi satu baris navigasi horizontal yang dapat di-scroll. Active state tetap memakai label, bentuk, dan accent color.

### Konteks aktif

Setiap halaman harus membuat user dapat menjawab:

1. Saya berada di Unit atau Project mana?
2. Data apa yang sedang saya lihat?
3. Aksi apa yang dapat saya lakukan?
4. Apa dampak aksi tersebut?

## 4. Responsive behavior

### Desktop

- Primary Rail dan Context Sidebar tampil berdampingan.
- Main Content memiliki lebar yang cukup untuk table atau board.
- Detail Task dapat memakai layout dua kolom.
- Sidebar konteks dapat diciutkan jika ruang content terbatas.

### Tablet

- Primary Rail dapat berubah menjadi rail icon dengan tooltip.
- Context Sidebar dapat dibuka dan diciutkan.
- Table, board, Calendar, dan Gantt memakai overflow terkontrol.
- Toolbar boleh membungkus ke baris kedua.

### Mobile

- Context Sidebar menjadi drawer.
- Filter dan sort dibuka melalui Sheet.
- Detail Task menjadi satu kolom.
- Aksi utama tetap terlihat, aksi sekunder berada di menu.
- Board memakai horizontal scroll per kolom atau pemilih kolom.
- Table menyembunyikan kolom sekunder secara progresif.
- Tidak ada content penting yang tertutup fixed navigation.
- Target sentuh minimal 44 x 44 px.
- Active state tetap terlihat melalui label, bentuk, atau indikator.

## 5. Layout dan visual hierarchy

Pola umum halaman:

    Page
    ├── Breadcrumb atau context label
    ├── Page header
    │   ├── Title
    │   ├── Description atau metadata
    │   └── Primary and secondary actions
    ├── Context toolbar
    │   ├── Search
    │   ├── Filter
    │   ├── Sort
    │   ├── Group
    │   ├── Layout switcher
    │   └── Saved View
    └── Content area

### Page header

- Title menjelaskan objek yang sedang dilihat.
- Primary action memakai kata kerja spesifik, seperti Tambah Task.
- Aksi destruktif tidak diletakkan sebagai primary action.
- Jangan memenuhi header dengan tombol yang jarang digunakan.

### Context toolbar

Control harus menjelaskan scope, misalnya Filter Task Project ini, bukan hanya Filter.

Toolbar harus menangani:

- loading atau processing;
- filter aktif sebagai chip;
- reset filter;
- empty result;
- error request;
- permission untuk setiap action.

## 6. Component system

Frontend menggunakan component-based architecture.

    Bits UI primitives
        ↓
    components/ui design system lokal
        ↓
    Komponen domain
        ↓
    Page composition

### Primitive behavior

Bits UI menjadi standar untuk behavior primitive:

- Dialog;
- Dropdown Menu;
- Select;
- Checkbox;
- Collapsible;
- Sheet;
- Tooltip;
- primitive Bits UI lain yang diperlukan.

Bits UI menangani behavior, focus management, keyboard navigation, portal, dan accessibility. Styling tetap berasal dari token visual dan Tailwind ithub.

Halaman tidak mengimpor primitive Bits UI langsung jika wrapper lokal sudah tersedia.

### Komponen lokal

Komponen pada resources/js/components/ui menjadi API visual resmi ithub.

Aturan:

- API props harus bertipe dan memiliki nama yang jelas;
- state bindable harus eksplisit;
- variasi visual memakai props atau data attributes;
- composition memakai snippets Svelte 5;
- komponen tidak bergantung pada page tertentu;
- komponen satu kali pakai tidak dibuat sebagai abstraction;
- state loading, disabled, error, dan empty hanya ditambahkan jika relevan;
- komponen interaktif harus memiliki behavior nyata.

### Komponen domain

Komponen domain memahami istilah dan data ithub, contohnya:

- TaskTable;
- TaskCard;
- ProjectHeader;
- ActivityTimeline;
- AuditLogTable;
- StatusBadge;
- AssigneePicker;
- SavedViewPicker.

Komponen domain boleh menerima data dan callback dari page, tetapi tidak boleh menggantikan authorization atau aturan workflow backend.

### Page composition

Page pada resources/js/pages bertanggung jawab untuk:

- menerima props Inertia;
- menyusun layout;
- menghubungkan component contract;
- memanggil route melalui Wayfinder;
- menampilkan state halaman;
- meneruskan aksi ke backend.

Page tidak mengulang markup komponen yang sudah ada.

## 7. State, data, dan form

Bedakan tiga jenis state:

| Jenis | Contoh | Sumber |
| --- | --- | --- |
| Server state | Project, Task, membership, Activity Log | Props Inertia dan response backend |
| URL state | Search, filter, sort, grouping, layout | Query parameter atau Saved View |
| UI state | Dialog, Sheet, selected row, expanded section, drag preview | State lokal komponen |

Aturan:

- backend menjadi sumber kebenaran untuk permission, workflow, validasi, audit, dan persistence;
- jangan menyalin server state ke store lokal tanpa kebutuhan yang jelas;
- mutation yang optimistis wajib memiliki rollback;
- state final setelah mutation mengikuti response atau reload props;
- loading dan processing berasal dari request nyata;
- request gagal tidak menghapus input yang masih dapat dipertahankan;
- filter dan sort harus dapat dipulihkan melalui URL atau Saved View.

### Route dan form

- Semua route frontend ke Laravel menggunakan Wayfinder.
- Form menggunakan Inertia Form atau pola form yang sudah ada.
- Error backend ditampilkan dekat field atau objek yang bermasalah.
- Setiap mutation memiliki feedback success, validation error, unauthorized, network error, dan rollback bila relevan.
- Permission tetap diperiksa di backend walaupun action disembunyikan di frontend.

## 8. Standar library frontend

| Kebutuhan | Standar | Tanggung jawab |
| --- | --- | --- |
| UI primitive | Bits UI melalui wrapper lokal | Behavior, focus, keyboard, portal, accessibility |
| Styling | Tailwind dan token visual lokal | Color, spacing, typography, density, radius, state |
| Board drag-and-drop | @dnd-kit/svelte | Sensor, sortable item, drop target, preview optimistis |
| Data table | @tanstack/svelte-table | Column definition, row model, sorting, filtering, selection |
| Calendar | fullcalendar | Render event dan view Calendar |
| Project timeline | frappe-gantt | Render timeline dan view mode |
| Ikon | Lucide Svelte | Ikon fungsional dan accessible label |
| Toast | Sonner atau pola feedback lokal | Konfirmasi singkat dan status request |

Tidak ada library yang menjadi sumber aturan bisnis. Laravel tetap mengatur permission, workflow, validasi, audit, dan persistence.

### DND Kit

Board memakai DragDropProvider dan komponen sortable yang stabil.

- Kolom kosong tetap menjadi drop target.
- Drag hanya memberi preview dan optimistic state.
- Perubahan status dikirim melalui endpoint workflow backend.
- Jika backend menolak, kartu kembali ke posisi semula.
- Select status atau menu action menjadi alternatif keyboard dan mobile.
- Status Blocked, Canceled, Reopen, dan Done tetap mengikuti form alasan atau bukti.

### TanStack Table

Semua data table memakai @tanstack/svelte-table melalui shell table bersama.

- Definisi kolom tetap berada di komponen domain atau page yang memiliki kebutuhan data.
- Rendering cell memakai komponen UI lokal.
- Sorting, filtering, pagination, dan selection mengikuti source of truth backend bila data berada di URL atau endpoint.
- Permission action dihitung dari data dan authorization, bukan dari table.
- Mobile memakai kolom inti atau layout alternatif yang tetap memakai data table yang sama.
- Fitur table diaktifkan sesuai kebutuhan agar bundle tetap ringan.

### FullCalendar

Calendar dibuat melalui lifecycle Svelte.

- Instance dibuat saat page aktif dan dihancurkan saat unmount.
- Event berasal dari Task yang memiliki deadline.
- View awal bulan, dengan alternatif minggu dan list.
- Klik event membuka detail Task melalui Inertia.
- Task tanpa deadline tidak dibuat sebagai event palsu.
- Calendar read-only sampai mutation deadline memiliki endpoint, authorization, audit, dan failure feedback.
- Styling library mengikuti token visual ithub.

### Frappe Gantt

Gantt dibuat melalui lifecycle Svelte.

- Timeline menampilkan Task yang memiliki deadline dan milestone yang memiliki target.
- Task memakai tanggal dibuat sebagai start sementara selama model belum memiliki start_date.
- View awal Week, dengan pilihan Day, Month, dan Year.
- Klik bar Task membuka detail Task.
- Milestone memiliki visual berbeda dan tidak membuka detail Task.
- Timeline read-only sampai drag, resize, progress, dan dependency memiliki model data serta endpoint backend.
- Styling library mengikuti token visual ithub.

## 9. Spesifikasi layar

### Beranda

Menjawab kondisi lintas semua Unit yang diikuti user. Beranda menampilkan Task saya, Task terlambat, Task terblokir, Project yang perlu perhatian, dan perubahan terbaru tanpa mengubah Unit aktif.

### Dashboard Unit

Menjawab kondisi lintas Project pada Unit aktif:

- Task saya;
- Task terlambat;
- Task terblokir;
- Project yang perlu perhatian;
- progress per Project;
- beban kerja per PIC;
- perubahan terbaru.

Ringkasan yang dapat ditindaklanjuti harus membuka daftar Task atau Project dengan filter yang sesuai.

### Daftar Project

Menampilkan nama, Project ID, Unit, Project Lead, status, progress, target, dan visibility.

Filter minimum:

- Unit;
- status;
- Project Lead;
- visibility.

### Ikhtisar Project

Menampilkan tujuan, Project ID, Unit, Project Lead, Project Admin, status, progress Task, risiko, milestone, anggota, Task penting, dan Activity Log terbaru.

### Daftar Task

Informasi inti:

- Task ID;
- Title;
- status;
- PIC;
- Pemohon;
- priority;
- deadline;
- label;
- bukti atau catatan penyelesaian;
- indikator Sub-task.

Interaksi:

- quick add dengan Title sebagai input wajib;
- inline update untuk field yang diizinkan;
- filter, sort, group;
- bulk action jika permission dan endpoint tersedia;
- layout switcher List, Board, Calendar, dan Gantt;
- detail dibuka tanpa kehilangan konteks daftar.

### Board

- Kolom mengikuti status workflow.
- Kartu menampilkan Task ID, Title, priority, PIC, deadline, dan label.
- Kolom kosong tetap dapat menerima kartu.
- Drag memiliki feedback saat valid, invalid, processing, success, dan failure.
- Perubahan status melalui keyboard tersedia tanpa drag.
- Project archived bersifat read-only.

### Calendar

- Event menampilkan Task ID, Title, status, dan deadline.
- Event click membuka detail Task.
- Task tanpa deadline ditampilkan melalui informasi terpisah.
- View dan filter tetap mengikuti Project aktif.

### Gantt

- Bar Task menampilkan rentang waktu yang tersedia.
- Milestone ditampilkan sebagai penanda target.
- Timeline menjelaskan jika tanggal mulai masih memakai tanggal dibuat.
- Data kosong menjelaskan cara menambahkan deadline atau target.

### Detail Task

    Task header
    ├── Task ID dan Title
    ├── Status
    ├── Priority
    ├── PIC
    ├── Deadline
    └── More actions

    Task body
    ├── Description
    ├── Catatan penyelesaian
    ├── Bukti dan attachment
    ├── Sub-task
    ├── Relasi
    ├── Komentar
    └── Activity Log

    Task properties
    ├── Project
    ├── Pemohon
    ├── Label
    ├── Milestone
    ├── Creator
    └── Created and updated date

Audit Log berada terpisah dari Activity Log dan hanya terlihat sesuai permission.

### Pengaturan Project

Bagian yang aktif:

- General;
- Members dan Project role;
- Workflow;
- Labels;
- Default Assignee;
- Milestone;
- Archive dan restore.

Fitur lanjutan tidak boleh muncul sebelum tersedia.

## 10. Interaction dan visual states

### Status dan priority

Status dan priority selalu memiliki label teks. Warna, bentuk, atau ikon menjadi pendukung.

Jika belum ada PIC, tampilkan Belum ditugaskan. Jangan menampilkan avatar kosong tanpa penjelasan.

### Empty state

Empty state menjelaskan data yang kosong dan tindakan berikutnya.

    Belum ada Task di Project ini.
    Tambahkan Task pertama untuk mulai mencatat pekerjaan.
    [Tambah Task]

### Dialog dan Sheet

- Dialog menjelaskan konsekuensi action.
- Dialog mengembalikan focus ke trigger setelah ditutup.
- Sheet dipakai untuk filter, navigation mobile, dan panel sekunder.
- Escape, backdrop, dan tombol close bekerja konsisten.
- Focus tidak boleh keluar dari dialog yang sedang terbuka.

### Feedback mutation

- Processing state menonaktifkan action yang sama.
- Success feedback dekat dengan objek atau melalui toast singkat.
- Validation error berada dekat field.
- Error permission menjelaskan batas akses tanpa membocorkan data.
- Network error menyediakan Coba lagi.
- Konflik data menawarkan reload atau perbandingan bila tersedia.
- Aksi destruktif memerlukan konfirmasi.

### State checklist

Setiap layar menangani state yang relevan:

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

## 11. Accessibility

- Semua action utama dapat dijangkau keyboard.
- Focus state terlihat jelas.
- Dialog dan Sheet memiliki focus management.
- Form memiliki label dan error association.
- Ikon tanpa teks memiliki accessible label.
- Status dan permission tidak hanya dibedakan dengan warna.
- Drag-and-drop memiliki alternatif keyboard atau menu.
- Target sentuh minimal 44 x 44 px.
- Loading dan processing diumumkan kepada screen reader bila relevan.
- Kontras text dan control memenuhi standar aksesibilitas yang dipakai project.

## 12. Performance dan maintainability

- Page memakai code splitting yang tersedia melalui Inertia dan Vite.
- Instance library dibuat hanya saat page aktif dan selalu dibersihkan saat unmount.
- Data besar memakai pagination atau filtering server-side.
- Table, Board, Calendar, dan Gantt tidak me-render ulang seluruh page untuk perubahan satu item bila dapat dihindari.
- Komponen domain tetap fokus pada satu tanggung jawab.
- Abstraction baru dibuat ketika ada reuse atau behavior yang benar-benar sama.
- Library tidak diberi fitur yang tidak dibutuhkan.
- Query dan payload tidak membawa data di luar scope permission.

## 13. Definition of Done desain

Layar atau komponen siap diimplementasikan jika:

- konteks Unit atau Project terlihat;
- primary action dan permission action jelas;
- happy path dan failure path sudah ditentukan;
- state relevan sudah dirancang;
- status tidak hanya dibedakan dengan warna;
- desktop, tablet, dan mobile memiliki behavior yang jelas;
- keyboard dan focus behavior sudah ditentukan;
- route memakai Wayfinder;
- komponen memakai wrapper lokal jika tersedia;
- fitur yang belum aktif tidak muncul sebagai kontrol;
- desain tidak menambah aturan produk yang belum ada di PRD.

## 14. Checklist review sebelum merge

### Product

- Apakah behavior mengikuti PRD?
- Apakah scope permission jelas?
- Apakah fitur baru berada dalam MVP yang disepakati?

### UI

- Apakah layout memakai hierarchy yang jelas?
- Apakah komponen lokal digunakan kembali?
- Apakah loading, empty, error, unauthorized, archived, dan not-found sudah diperiksa?
- Apakah mobile tidak overflow?

### Interaction

- Apakah setiap tombol memiliki behavior nyata?
- Apakah mutation memiliki processing, success, dan failure feedback?
- Apakah aksi drag memiliki alternatif keyboard atau menu?
- Apakah dialog mengelola focus?

### Technical

- Apakah route memakai Wayfinder?
- Apakah server state tidak disalin tanpa alasan?
- Apakah instance FullCalendar atau Frappe Gantt dibersihkan?
- Apakah table memakai TanStack Table?
- Apakah Board memakai DND Kit?
- Apakah data dan permission tetap berasal dari backend?

## 15. Referensi

- Scope produk: PRD.md
- Roadmap besar: docs/SIM_TI_PROJECT_HANDOFF.md
- Pola project management: https://docs.plane.so/workspaces/overview
- Pola organisasi dan view kerja: https://docs.plane.so/introduction/tutorials/organize-and-view-work
