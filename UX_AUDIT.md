# sitop UX Audit

Status: Draft baseline

Dokumen ini menjadi baseline sebelum rombak UX total. Audit ini memeriksa struktur halaman, alur, konteks, aksi utama, state, responsive behavior, dan konsistensi interaction pattern berdasarkan implementasi aplikasi saat ini.

Browser automation belum tersedia pada sesi audit ini. Temuan visual yang membutuhkan screenshot harus divalidasi kembali melalui walkthrough browser sebelum implementasi redesign final.

## 1. Tujuan audit

Audit ini menjawab empat pertanyaan:

1. Apakah pengguna tahu harus mulai dari mana?
2. Apakah pengguna dapat menemukan pekerjaan yang perlu dilakukan?
3. Apakah pengguna dapat menyelesaikan pekerjaan tanpa kehilangan konteks?
4. Apakah sistem memberi feedback yang cukup pada setiap tindakan penting?

## 2. Skenario utama

Skenario yang menjadi acuan audit:

```text
Dashboard Workspace
    → pilih pekerjaan yang perlu perhatian
    → buka Project
    → pilih List atau Board
    → buka Task
    → assign PIC
    → ubah status
    → tambah komentar atau bukti
    → review
    → Done
```

## 3. Ringkasan temuan

| Prioritas | Temuan | Dampak |
| --- | --- | --- |
| P0 | Task Detail belum menjadi command center yang kuat | Pengguna belum memiliki satu tempat kerja utama untuk menyelesaikan Task |
| P0 | List, Board, side peek, dan full detail belum terasa sebagai satu alur | Pengguna kehilangan konteks saat berpindah tampilan |
| P1 | Dashboard masih lebih mirip ringkasan data daripada triage workspace | Pengguna melihat angka tetapi belum mendapat arahan tindakan yang kuat |
| P1 | Navigasi Project belum sepenuhnya konsisten di semua context | Pengguna dapat merasa berpindah halaman, bukan berpindah mode kerja |
| P1 | Filter belum menjadi state kerja yang persisten dan mudah dipahami | Triage Task menjadi lebih lambat pada dataset besar |
| P1 | Aksi utama Task masih tersebar dalam beberapa bagian halaman | Pengguna perlu mencari cara untuk melanjutkan Task |
| P2 | State loading, network failure, conflict, dan not-found belum menjadi pola eksplisit di semua layar | Feedback saat kondisi non-ideal belum konsisten |
| P2 | Search dan Notification sudah tersedia tetapi belum menjadi pusat navigasi kerja | Pengguna belum memiliki jalur cepat yang kuat untuk kembali ke pekerjaan |
| P2 | Responsive behavior perlu diuji dengan data padat | Layout yang terlihat baik pada data sedikit dapat menjadi sulit digunakan pada data nyata |

## 4. Audit per layar

### 4.1 Dashboard Workspace

#### Yang sudah baik

- Menampilkan Task saya, Task terlambat, Blocked, Task aktif, dan Project.
- Statistik penting sudah dapat membuka focus mode.
- Task pada daftar mengarah langsung ke detail Task.
- Empty state sudah tersedia.

#### Masalah UX

- Hierarki informasi belum membedakan dengan kuat antara pekerjaan yang harus dilakukan sekarang dan informasi referensi.
- “Task aktif” hanya menjadi angka dan tidak memiliki jalur tindakan langsung.
- Daftar Project belum menunjukkan kesehatan, risiko, atau pekerjaan yang membutuhkan perhatian.
- Dashboard belum memiliki satu primary action yang jelas.
- Focus mode masih terasa sebagai filter teknis, bukan mode kerja yang diberi konteks.
- Belum ada indikator perubahan terbaru lintas Project yang membantu pengguna memahami apa yang berubah.

#### Arah perbaikan

- Jadikan “Perlu tindakan” sebagai blok utama.
- Gunakan urutan prioritas: overdue/blocked, Task saya, Project berisiko, aktivitas terbaru.
- Beri setiap ringkasan sebuah next action yang jelas.
- Tampilkan alasan sebuah Project masuk kategori “perlu perhatian”.

### 4.2 Project Overview

#### Yang sudah baik

- Menampilkan tujuan Project, status, visibility, lead, anggota, dan jumlah Task.
- Menyediakan akses ke Task dan pengaturan.
- Archive dan restore sudah memiliki feedback visual.
- Activity Log Project sudah tersedia.

#### Masalah UX

- Informasi Project dan navigasi kerja belum cukup menyatu.
- Jumlah Task belum memberi ringkasan status atau risiko.
- Primary action masih berupa “Buka Task”, padahal pengguna mungkin perlu memilih List, Board, atau Dashboard.
- Project Overview belum menjawab “apa yang perlu dilakukan berikutnya?”.
- Activity Log belum membantu pengguna memahami perubahan penting secara ringkas.

#### Arah perbaikan

- Jadikan tab context Project sebagai bagian tetap dari header.
- Tambahkan ringkasan progress, Blocked, overdue, dan tanpa PIC.
- Sediakan CTA “Lihat pekerjaan yang perlu perhatian”.
- Bedakan informasi orientasi Project dari aksi operasional.

### 4.3 Task List

#### Yang sudah baik

- Quick add tersedia.
- Search, status, priority, PIC, Milestone, tanpa deadline, dan tanpa bukti tersedia.
- Filter aktif sekarang tampil sebagai chip.
- Side peek tersedia melalui query URL.
- Link ke full detail tersedia.

#### Masalah UX

- Toolbar terlalu padat ketika filter bertambah.
- Quick add hanya menerima judul sehingga pengguna harus membuka detail untuk melengkapi konteks.
- Tabel belum mendukung inline editing untuk properti yang sering berubah.
- Belum ada preset filter seperti “Task saya”, “Terlambat”, “Blocked”, atau “Tanpa PIC”.
- Side peek masih terasa sebagai preview, bukan ruang kerja ringan.
- State filter belum sepenuhnya dipertahankan pada semua link internal.
- Belum ada bulk action atau grouping untuk dataset besar.

#### Arah perbaikan

- Pisahkan primary search, preset, dan advanced filter.
- Tambahkan filter preset yang dapat dipakai satu klik.
- Jadikan kolom status, PIC, priority, dan deadline dapat diedit inline.
- Pertahankan query filter saat membuka side peek dan kembali ke list.
- Tambahkan grouping berdasarkan status, PIC, atau Milestone secara bertahap.

### 4.4 Board

#### Yang sudah baik

- Task dikelompokkan berdasarkan status.
- Drag-and-drop memiliki optimistic update dan rollback.
- Select fallback tersedia untuk keyboard atau perangkat tanpa drag.
- Project archived ditampilkan sebagai read-only.

#### Masalah UX

- Board belum memiliki filter dan search yang sama dengan List.
- Card belum memberi cukup konteks untuk triage cepat pada dataset besar.
- Perubahan ke Done, Blocked, Canceled, atau Reopen masih menggunakan prompt browser.
- Prompt browser memutus ritme dan tidak konsisten dengan bahasa visual aplikasi.
- Tidak ada feedback yang cukup dekat dengan kartu ketika transisi gagal.
- Kolom yang panjang membutuhkan strategi density dan collapse.

#### Arah perbaikan

- Gunakan dialog/sheet aplikasi untuk alasan dan bukti transisi.
- Samakan filter Board dengan List.
- Tambahkan card density mode dan jumlah Task per kolom.
- Gunakan inline status menu sebagai alternatif drag.
- Pastikan error transisi menjelaskan aturan workflow yang gagal.

### 4.5 Task Detail

#### Yang sudah baik

- Description, status, priority, deadline, komentar, attachment, Activity Log, Sub-task, Milestone, dan relasi sudah tersedia.
- Authorization backend sudah menjadi sumber aturan.
- Archived Project diberi tanda read-only.
- Form action memiliki processing state.

#### Masalah UX

- Detail Task masih terasa seperti form panjang, bukan command center.
- Primary action tidak selalu terlihat berdasarkan status Task saat ini.
- Status transition, edit detail, assignment, komentar, dan attachment berada dalam blok terpisah tanpa prioritas yang kuat.
- Properti utama belum terasa inline dan cepat diedit.
- Informasi “siapa yang harus melakukan apa berikutnya” belum cukup menonjol.
- Sub-task dan relasi belum membantu pengguna memahami konteks pekerjaan secara visual.
- Side peek dan full detail belum berbagi struktur informasi yang cukup konsisten.

#### Arah perbaikan

- Buat header Task sticky dengan identifier, status, PIC, priority, deadline, dan primary action.
- Ubah properti utama menjadi inline property controls.
- Tampilkan next action kontekstual berdasarkan status.
- Susun detail menjadi dua zona: work area dan context rail.
- Jadikan komentar dan Activity Log sebagai timeline yang mudah dipindai.
- Beri hierarchy yang jelas untuk Sub-task, bukti, dan relasi.

### 4.6 Search

#### Yang sudah baik

- Project dan Task dikelompokkan.
- Query dibatasi oleh permission dan active Workspace.
- Shortcut Cmd/Ctrl+K sudah diarahkan ke Search.

#### Masalah UX

- Belum ada keyboard navigation pada hasil.
- Belum ada loading state yang terlihat saat pencarian berlangsung.
- Hasil belum menunjukkan alasan kecocokan atau metadata status yang cukup.
- Search belum menjadi command menu untuk aksi cepat.

#### Arah perbaikan

- Jadikan Search sebagai command palette.
- Tambahkan recent search, keyboard navigation, dan grouped result metadata.
- Bedakan navigation result dari action result.

### 4.7 Notifications

#### Yang sudah baik

- Unread count tersedia.
- Notification mengarah ke objek nyata.
- Mark as read tersedia.
- Data mengikuti recipient yang menerima notifikasi.

#### Masalah UX

- Notifikasi belum dikelompokkan berdasarkan urgency atau jenis tindakan.
- Belum ada aksi langsung dari notification item.
- Belum ada state “semua sudah dibaca” yang membantu orientasi.
- Notification belum menjelaskan perubahan secara cukup kontekstual.

#### Arah perbaikan

- Kelompokkan menjadi perlu tindakan, update, dan informasi.
- Tambahkan CTA kontekstual seperti buka review atau lihat Task.
- Sediakan mark all as read dan filter unread.

### 4.8 Mobile

#### Risiko utama

- Task List dengan banyak filter berpotensi menjadi toolbar yang terlalu panjang.
- Board horizontal perlu affordance bahwa konten dapat digeser.
- Side peek harus berubah menjadi sheet yang tidak menutup context penting.
- Project context navigation harus tetap bisa dipindai pada layar sempit.
- Form detail Task berpotensi terlalu panjang jika semua section terbuka.

#### Arah perbaikan

- Gunakan filter sheet pada mobile.
- Gunakan sticky compact header untuk Task Detail.
- Gunakan progressive disclosure pada metadata dan Activity Log.
- Uji dengan dataset 100+ Task, bukan hanya empty state.

## 5. Masalah lintas produk

### Context persistence

Filter, sort, focus, dan tampilan harus tetap ada ketika pengguna:

- membuka side peek;
- membuka full detail;
- kembali ke List;
- berpindah List ke Board;
- kembali dari komentar atau attachment.

### Primary action

Setiap layar harus memiliki satu primary action yang menjawab tujuan pengguna. Aksi sekunder tidak boleh memiliki visual weight yang sama.

### Feedback

Setiap mutation penting harus memberi:

- processing;
- success;
- validation error;
- authorization error;
- network error;
- rollback atau recovery ketika relevan.

### Density

Dataset sitop sekarang cukup padat untuk menguji density. Tampilan harus mendukung pemindaian cepat tanpa mengorbankan identifier, status, PIC, dan deadline.

## 6. Prioritas perbaikan

### P0 — Wajib sebelum UX dianggap matang

1. Redesign Task Detail sebagai command center.
2. Satukan side peek, full detail, List, dan Board sebagai satu alur.
3. Jadikan primary action Task bergantung pada status.
4. Pertahankan context filter dan navigasi.

### P1 — Perbaikan besar setelah P0

1. Redesign Dashboard sebagai triage workspace.
2. Inline property editing pada Task List dan Task Detail.
3. Filter preset dan advanced filter sheet.
4. Board dengan dialog transition dan filter konsisten.

### P2 — Polish dan scale

1. Command palette penuh.
2. Notification grouping dan bulk read.
3. Saved view dan grouping.
4. Density mode dan optimasi mobile.

## 7. Keputusan audit

- Tidak menambah modul produk baru sebelum golden path UX stabil.
- Task Detail menjadi pusat redesign pertama.
- Dashboard diperlakukan sebagai triage workspace, bukan halaman statistik.
- List dan Board harus berbagi filter, context, dan model navigasi.
- Prompt browser akan diganti dengan dialog/sheet aplikasi.
- Perubahan UX akan diuji menggunakan dataset demo padat.

## 8. Langkah berikutnya

Fase berikutnya adalah membuat sitemap dan navigation map final, lalu menyusun golden path yang lebih detail sebelum perubahan UI besar dimulai.
