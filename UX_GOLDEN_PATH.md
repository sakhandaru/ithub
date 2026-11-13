# sitop UX Golden Path

Status: Baseline interaksi sebelum redesign layar inti

Golden path sitop adalah alur utama untuk mengubah pekerjaan yang perlu dilakukan menjadi pekerjaan yang memiliki PIC, status, bukti, dan hasil yang dapat ditelusuri.

```text
Dashboard Workspace
    → pilih pekerjaan yang perlu perhatian
    → buka Project
    → triage melalui Task List atau Board
    → buka Side Peek atau Task Detail
    → lengkapi konteks dan PIC
    → kerjakan dan perbarui status
    → beri komentar atau bukti
    → review
    → Done
```

## 1. Persona utama

### Contributor

Datang untuk mengetahui pekerjaan yang harus dikerjakan, memperbarui status, berkomentar, dan menambahkan bukti.

### Project Lead

Datang untuk melakukan triage, menetapkan PIC, memeriksa risiko, dan memastikan pekerjaan bergerak.

### Reviewer

Datang untuk memeriksa hasil, memberi komentar, dan membantu keputusan Done atau Reopen.

## 2. Aturan umum interaksi

- Setiap layar memiliki satu primary action.
- Primary action berubah mengikuti konteks dan status Task.
- Pengguna tidak boleh kehilangan Workspace, Project, filter, atau posisi saat berpindah halaman.
- Setiap mutation memiliki processing, success, validation error, authorization error, dan network error state.
- Aksi yang mengubah status harus menjelaskan konsekuensi dan alasan yang diperlukan.
- Done harus memiliki catatan penyelesaian atau bukti.
- Blocked, Canceled, dan Reopen harus memiliki alasan.
- Objek archived tetap dapat dibaca tetapi tidak dapat dimutasi.

## 3. Step 1 — Dashboard Workspace

### Tujuan pengguna

Menentukan pekerjaan mana yang harus ditangani lebih dahulu.

### Informasi yang harus terlihat

- Task saya;
- Task terlambat;
- Task Blocked;
- Project yang perlu perhatian;
- perubahan terbaru;
- jumlah pekerjaan aktif.

### Primary action

Membuka daftar pekerjaan yang perlu ditangani.

### Interaksi

1. Pengguna melihat ringkasan prioritas.
2. Pengguna memilih salah satu fokus: Task saya, Terlambat, atau Blocked.
3. Sistem membuka daftar Task lintas Project dengan fokus yang dipilih.
4. Pengguna dapat membuka Task langsung dari daftar.

### Feedback

- Focus aktif terlihat melalui label dan state visual.
- Daftar menampilkan jumlah hasil.
- Empty state menjelaskan bahwa tidak ada pekerjaan dalam fokus tersebut.
- Reset fokus mengembalikan Dashboard default.

### Failure state

- Workspace tidak ditemukan → tampilkan not-found dan jalur ke Workspace yang tersedia.
- Tidak ada akses → jangan tampilkan data Project atau Task.
- Network gagal → tampilkan retry tanpa menghapus focus state.

### Acceptance criteria

- Pengguna dapat membuka Task terlambat maksimal dalam dua interaksi dari Dashboard.
- Klik statistik tidak hanya membuka halaman tanpa filter.
- Task yang ditampilkan mengikuti permission pengguna.
- Kembali ke Dashboard mempertahankan Workspace aktif.

## 4. Step 2 — Project Overview

### Tujuan pengguna

Memahami tujuan, kondisi, dan pekerjaan penting dalam Project sebelum masuk ke detail Task.

### Informasi yang harus terlihat

- nama dan identifier Project;
- tujuan Project;
- status dan kesehatan;
- Project Lead;
- anggota;
- progress Task;
- overdue dan Blocked;
- milestone atau target terdekat;
- activity penting.

### Primary action

Membuka pekerjaan yang perlu perhatian atau Task List.

### Interaksi

1. Pengguna membaca ringkasan Project.
2. Sistem menampilkan context navigation yang konsisten: Ikhtisar, Task, Board, Dashboard.
3. Pengguna memilih Task List untuk triage detail atau Board untuk melihat aliran status.
4. Jika ada risiko, pengguna dapat langsung membuka daftar terfilter.

### Failure state

- Project archived → tampilkan banner read-only dan sembunyikan mutation.
- Project tidak ditemukan → tampilkan not-found tanpa membocorkan objek lain.
- Project tidak memiliki Task → tampilkan empty state dengan CTA buat Task jika pengguna berwenang.

### Acceptance criteria

- Pengguna memahami tujuan Project tanpa membuka Settings.
- Pengguna dapat masuk ke List, Board, atau Dashboard tanpa kembali ke sidebar Workspace.
- Risiko Project memiliki link ke daftar Task yang sesuai.

## 5. Step 3 — Task List triage

### Tujuan pengguna

Menemukan, mengelompokkan, dan menentukan Task yang perlu ditindaklanjuti.

### Primary action

Buat Task atau buka Task yang perlu tindakan.

### Toolbar hierarchy

```text
[Buat Task]   [Search]   [Preset filter]   [Advanced filter]   [View]
```

### Preset filter minimum

- Semua Task;
- Task saya;
- Terlambat;
- Blocked;
- Tanpa PIC;
- Tanpa deadline;
- Tanpa bukti.

### Interaksi

1. Pengguna memilih preset atau memasukkan search.
2. Sistem memperbarui hasil dan menampilkan filter aktif sebagai chip.
3. Pengguna membuka side peek tanpa kehilangan filter.
4. Pengguna dapat mengubah status, PIC, priority, atau deadline secara inline.
5. Pengguna dapat membuka full detail jika membutuhkan konteks lebih luas.

### Filter state

Filter harus tersimpan pada URL dan dipertahankan ketika:

- membuka side peek;
- membuka full detail;
- kembali ke List;
- berpindah antara List dan Board jika filter kompatibel.

### Empty state

Empty state harus membedakan:

- Project memang belum memiliki Task;
- filter tidak menghasilkan Task;
- pengguna tidak memiliki Task yang ditugaskan;
- pencarian tidak menemukan hasil.

### Acceptance criteria

- Pengguna dapat menemukan Task berdasarkan identifier, judul, status, PIC, dan deadline.
- Filter aktif dapat dihapus satu per satu.
- Membuka dan menutup side peek tidak mereset filter.
- Task yang tidak boleh dilihat tidak muncul melalui search atau filter.

## 6. Step 4 — Board workflow

### Tujuan pengguna

Memahami aliran pekerjaan dan memindahkan Task ke status berikutnya.

### Primary action

Memindahkan Task sesuai aturan workflow.

### Interaksi

1. Pengguna melihat jumlah Task per kolom.
2. Pengguna membaca card: identifier, judul, PIC, priority, deadline, dan label.
3. Pengguna drag card atau memakai status menu.
4. Jika perubahan membutuhkan alasan atau bukti, sistem membuka dialog aplikasi.
5. Sistem menampilkan optimistic update.
6. Jika backend gagal, card dikembalikan ke posisi awal dan error dijelaskan.

### Status transition

```text
Request → Backlog → Todo → In Progress → In Review → Done
                         ↘ Blocked
Status aktif → Canceled
Done → Reopen → In Progress
```

### Acceptance criteria

- Board dan List menunjukkan status yang sama setelah perubahan.
- Transisi ilegal tidak mengubah posisi card.
- Alasan dan bukti tidak diminta melalui browser prompt.
- Project archived membuat Board read-only.

## 7. Step 5 — Side Peek

### Tujuan pengguna

Memeriksa dan melakukan perubahan cepat tanpa meninggalkan triage context.

### Informasi minimum

- identifier dan judul;
- status;
- priority;
- PIC;
- deadline;
- requester;
- deskripsi singkat;
- Sub-task summary;
- link ke full detail.

### Primary action

Membuka full detail atau memperbarui properti cepat.

### Interaksi

- Side peek terbuka melalui URL state.
- Close mengembalikan List dengan filter dan scroll context bila memungkinkan.
- Escape menutup side peek pada desktop.
- Pada mobile, side peek berubah menjadi full-width sheet.
- Mutation dari side peek memberi feedback tanpa menutup peek secara tidak terduga.

### Acceptance criteria

- Side peek tidak menjadi halaman kedua dengan struktur yang berbeda total.
- Pengguna dapat membuka full detail tanpa kehilangan context.
- Akses keyboard tersedia untuk open, close, dan primary action.

## 8. Step 6 — Task Detail

### Tujuan pengguna

Mengerjakan Task sampai siap direview atau selesai.

### Struktur yang disarankan

```text
Task header
├── Identifier
├── Title
├── Status
├── PIC
├── Priority
├── Deadline
└── Primary action

Work area
├── Description
├── Sub-task
├── Comments / mention
├── Attachments / evidence
└── Relations

Context rail
├── Requester
├── Project
├── Milestone
├── Dates
└── Activity Log
```

### Primary action berdasarkan status

| Status | Primary action |
| --- | --- |
| Request | Terima ke Backlog |
| Backlog | Mulai Task |
| Todo | Mulai Task |
| In Progress | Kirim untuk Review |
| In Review | Tandai Done atau Kembalikan |
| Blocked | Lanjutkan setelah blocker selesai |
| Done | Reopen |
| Canceled | Tidak ada mutation default |

### Acceptance criteria

- Pengguna selalu tahu tindakan berikutnya.
- Status, PIC, priority, dan deadline dapat diubah tanpa form panjang.
- Done memerlukan catatan atau bukti.
- Blocked, Canceled, dan Reopen memerlukan alasan.
- Komentar, attachment, dan Activity Log berada dalam satu konteks Task.
- Task archived/read-only tidak menampilkan mutation.

## 9. Step 7 — Review dan feedback

### Tujuan pengguna

Reviewer memeriksa hasil dan membuat keputusan yang dapat ditelusuri.

### Interaksi

1. Reviewer membuka Task dari Board, notification, atau daftar review.
2. Reviewer melihat deskripsi, Sub-task, bukti, komentar, dan histori.
3. Reviewer memberi komentar jika ada kekurangan.
4. Reviewer memilih Done atau mengembalikan Task ke In Progress.
5. Sistem meminta catatan atau alasan yang sesuai.
6. Sistem mencatat Activity Log dan Audit Log.

### Acceptance criteria

- Reviewer tidak perlu mencari bukti di halaman lain.
- Keputusan Done atau Reopen tercatat dengan actor dan waktu.
- Contributor menerima feedback melalui notification jika memiliki akses.

## 10. Step 8 — Done dan follow-up

### Setelah Done

- Task menampilkan bukti dan completion note.
- Activity Log mencatat perubahan status.
- Dashboard dan Project progress diperbarui.
- Task tidak hilang dari histori.
- Reopen tetap tersedia bagi role yang berwenang.

### Jika Reopen

- Sistem meminta alasan.
- Status kembali ke In Progress.
- completion state lama tetap dapat dilihat dalam Activity Log.
- Notification dikirim kepada pihak yang relevan.

## 11. Error recovery

| Kondisi | Perilaku |
| --- | --- |
| Validation error | Tetap di konteks saat ini, fokus ke field yang salah |
| Unauthorized | Mutation tidak tersedia; direct request ditolak backend |
| Network error | Tampilkan retry dan pertahankan input/state |
| Conflict | Jelaskan data berubah dan sediakan refresh/review |
| Archived | Tampilkan read-only state dan link restore jika berwenang |
| Not found | Jelaskan objek tidak tersedia tanpa membocorkan scope lain |
| Transition failed | Rollback optimistic UI dan tampilkan alasan backend |

## 12. Measurement plan

Golden path dianggap membaik jika pilot menunjukkan:

- waktu dari Dashboard ke Task yang relevan menurun;
- jumlah navigasi yang tidak perlu berkurang;
- filter tidak sering diulang karena context hilang;
- lebih banyak Task memiliki PIC dan deadline;
- lebih sedikit Task tertahan tanpa alasan;
- Task Done lebih sering memiliki bukti atau completion note;
- pengguna dapat menemukan histori tanpa meminta laporan manual.

## 13. Langkah implementasi berikutnya

Dokumen ini menjadi acceptance criteria untuk redesign berikutnya. Implementasi dimulai dari Task Detail karena layar tersebut menjadi pusat kerja untuk seluruh langkah setelah triage.
