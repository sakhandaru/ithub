# sitop UX Navigation Map

Status: Baseline final untuk redesign UX

Dokumen ini menetapkan cara pengguna berpindah di sitop. Ia melengkapi `UX_AUDIT.md`, `PRD.md`, dan `DESIGN.md` sebelum redesign layar inti dimulai.

## 1. Prinsip navigasi

1. Pengguna selalu mengetahui Workspace dan Project yang sedang aktif.
2. Level pertama hanya berpindah area bisnis.
3. Level kedua hanya menampilkan konteks area yang sedang aktif.
4. Task tidak menjadi area global terpisah; Task selalu berada dalam konteks Project.
5. Search dan Notification dapat membawa pengguna ke objek nyata tanpa kehilangan scope akses.
6. Back navigation mempertahankan filter, sort, focus, dan tampilan sebelumnya.
7. Menu fitur yang belum aktif tidak ditampilkan sebagai tombol palsu.
8. Active state harus dapat dikenali melalui label, posisi, dan affordance—bukan warna saja.

## 2. Struktur informasi

```text
sitop
└── Workspace aktif
    ├── Pekerjaan
    │   ├── Beranda Workspace
    │   ├── Task saya
    │   ├── Semua Task
    │   ├── Terlambat
    │   ├── Blocked
    │   ├── Projects
    │   └── Search
    ├── Pengetahuan             [aktif jika modul tersedia]
    ├── Operasional             [aktif jika modul tersedia]
    ├── Kepatuhan               [aktif jika modul tersedia]
    ├── Laporan                 [aktif jika modul tersedia]
    └── Pengaturan
        ├── Workspace
        ├── Anggota dan Unit
        └── Akun
```

## 3. Dua level sidebar

### Level pertama: Primary rail

Primary rail tetap dan hanya berisi area bisnis:

```text
[Logo sitop]

[Pekerjaan]
[Pengetahuan]
[Operasional]
[Kepatuhan]
[Laporan]

[Pengaturan]
```

Aturan:

- Pekerjaan aktif selama pengguna berada di Dashboard, Project, List, Board, atau Task.
- Pengaturan aktif untuk Workspace Settings dan Account Settings.
- Area yang belum tersedia tidak ditampilkan.
- Pada desktop rail dapat collapsed secara visual, tetapi label tersedia melalui tooltip dan accessible name.
- Pada mobile primary rail menjadi bagian dari drawer.

### Level kedua: Context sidebar

Context sidebar berubah berdasarkan area dan konteks:

```text
Workspace header
├── Logo dan nama sitop
├── Workspace switcher
├── Global Search
├── Notifications
└── Profil
```

Untuk area Pekerjaan:

```text
Pekerjaan
├── Beranda
├── Task saya
├── Semua Task
├── Terlambat
├── Blocked
└── Draf

Workspace
├── Projects
├── Inisiatif          [aktif jika tersedia]
├── Dasbor
└── Tampilan           [aktif jika tersedia]
```

Jika pengguna masuk ke Project, context sidebar menambahkan konteks Project:

```text
Project
├── Ikhtisar
├── Task
├── Board
├── Kalender           [aktif jika tersedia]
├── Siklus             [aktif jika tersedia]
├── Modul              [aktif jika tersedia]
├── Tampilan           [aktif jika tersedia]
├── Halaman            [aktif jika tersedia]
├── Pembaruan Project  [aktif jika tersedia]
└── Pengaturan Project
```

## 4. Model konteks

### Workspace context

Workspace context diperlukan untuk:

- Dashboard Workspace;
- Project List;
- Global Search;
- Notifications;
- anggota dan Unit;
- laporan lintas Project.

Workspace switcher harus:

- hanya menampilkan Workspace yang dapat diakses pengguna;
- mengubah active Workspace secara eksplisit;
- membawa pengguna ke Dashboard Workspace baru setelah berpindah;
- tidak mengubah Project aktif secara diam-diam jika Project tidak tersedia di Workspace baru.

### Project context

Project context diperlukan untuk:

- Ikhtisar Project;
- Task List;
- Board;
- Dashboard Project;
- Task Detail;
- Milestone dan Project Settings.

Saat Project aktif:

- nama dan identifier Project selalu terlihat di header atau context sidebar;
- navigasi Project selalu tersedia;
- Task List, Board, dan Dashboard memakai navigasi yang sama;
- filter Task tetap berada dalam Project yang sama;
- Project archived tetap dapat dibaca tetapi semua mutation menjadi read-only.

### Task context

Task context tidak membuat level sidebar baru. Ia ditampilkan melalui:

- breadcrumb;
- identifier Task;
- judul Task;
- status dan PIC;
- side peek atau full detail.

Task Detail harus selalu menyediakan jalan kembali ke Task List dengan filter sebelumnya.

## 5. Route map

| Konteks | Halaman | URL konseptual | Primary action |
| --- | --- | --- | --- |
| Auth | Login | `/login` | Masuk |
| Workspace | Dashboard | `/dashboard` | Buka pekerjaan yang perlu perhatian |
| Workspace | Projects | `/workspaces/{workspace}/projects` | Buka atau buat Project |
| Workspace | Search | `/search?q=` | Buka hasil Project atau Task |
| Workspace | Notifications | `/notifications` | Buka update yang perlu ditindaklanjuti |
| Project | Ikhtisar | `/workspaces/{workspace}/projects/{project}` | Buka Task, Board, atau Dashboard |
| Project | Task List | `/workspaces/{workspace}/projects/{project}/tasks` | Buat, filter, dan triage Task |
| Project | Board | `/workspaces/{workspace}/projects/{project}/board` | Pindahkan Task dalam workflow |
| Project | Dashboard | `/workspaces/{workspace}/projects/{project}/dashboard` | Buka risiko atau ringkasan status |
| Task | Side peek | Task List + `?task={task}` | Review cepat dan buka detail penuh |
| Task | Full detail | `/workspaces/{workspace}/projects/{project}/tasks/{task}` | Kerjakan dan selesaikan Task |
| Project | Settings | `/workspaces/{workspace}/projects/{project}/settings` | Kelola konfigurasi Project |
| Workspace | Settings | `/workspaces/{workspace}/settings` | Kelola Workspace, Unit, dan anggota |
| Account | Settings | `/settings/profile` | Kelola akun pengguna |

## 6. Breadcrumb rules

### Workspace

```text
Beranda
```

### Project

```text
Pekerjaan / Projects / {PROJECT-ID}
```

### Task List

```text
Pekerjaan / Projects / {PROJECT-ID} / Task
```

### Task Detail

```text
Pekerjaan / Projects / {PROJECT-ID} / Task / {TASK-ID}
```

Breadcrumb terakhir adalah halaman aktif dan tidak perlu menjadi link aktif. Link sebelumnya harus mempertahankan context yang relevan.

## 7. Context persistence

### Task List state

State berikut harus dipertahankan ketika membuka side peek atau kembali dari full detail:

- search;
- status;
- priority;
- PIC;
- requester;
- milestone;
- deadline filter;
- evidence filter;
- sort;
- direction;
- selected Task.

### View switching

Saat berpindah dari List ke Board:

- Project tetap sama;
- search dan filter yang kompatibel tetap dibawa;
- view aktif berubah secara eksplisit;
- pengguna dapat kembali ke List tanpa kehilangan triage state.

### Notification deep link

Notification yang mengarah ke Task harus:

- memvalidasi ulang akses backend;
- membuka Project dan Task context yang benar;
- menampilkan archived/read-only state bila diperlukan;
- tidak membuka objek yang sudah tidak dapat diakses.

## 8. Mobile navigation

Pada mobile:

- primary rail dan context sidebar menjadi satu drawer;
- Project context navigation menjadi horizontal scroll dengan active item yang tetap terlihat;
- filter Task dibuka melalui filter sheet;
- side peek berubah menjadi full-width sheet;
- Task Detail memakai compact sticky header;
- action utama tetap terlihat tanpa harus scroll ke akhir halaman;
- drawer menutup setelah navigasi berhasil;
- focus keyboard dikembalikan ke trigger saat drawer atau sheet ditutup.

## 9. Navigation states

Setiap navigasi perlu menangani:

- loading saat berpindah halaman;
- active state;
- not found;
- unauthorized;
- archived/read-only;
- network failure;
- stale atau conflict data;
- back navigation.

Tidak boleh ada link yang mengarah ke halaman yang belum tersedia hanya karena item tersebut tercantum di roadmap.

## 10. Navigation anti-pattern yang harus dihindari

- Sidebar menampilkan semua modul meskipun belum aktif.
- Primary rail berisi halaman detail atau filter spesifik.
- Task Detail memiliki navigasi Project yang berbeda dari Task List.
- Tombol “Back” membuang semua filter pengguna.
- Search membuka objek tanpa menampilkan Workspace/Project context.
- Archived Project terlihat seperti Project aktif.
- Link dengan visual button tetapi tidak memiliki state loading atau tujuan jelas.
- Mobile hanya mengecilkan desktop layout tanpa mengubah interaction model.

## 11. Acceptance criteria

Navigation map dianggap siap untuk implementasi jika:

- pengguna dapat menjelaskan posisi Workspace, Project, dan Task tanpa bantuan;
- semua halaman utama Project memiliki context navigation yang sama;
- Task List → side peek → full detail → kembali tidak kehilangan filter;
- List ↔ Board tidak kehilangan Project context;
- Search dan Notification selalu membuka objek melalui scope yang benar;
- archived state terlihat jelas dan mutation tidak tersedia;
- mobile memiliki drawer, sheet, dan horizontal context navigation yang disengaja;
- menu roadmap tidak muncul sebagai kontrol aktif.

## 12. Langkah berikutnya

Dokumen berikutnya adalah `UX_GOLDEN_PATH.md`, yang akan merinci langkah, keputusan, feedback, dan acceptance criteria untuk alur:

```text
Dashboard → Project → Task List/Board → Task Detail → Review → Done
```
