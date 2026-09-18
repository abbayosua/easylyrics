# EasyLyrics — User Flows

Alur yang biasa dipakai user di web app (Apache, `http://localhost/easylyrics`).
Setiap flow punya file E2E Playwright terkait di `tests/e2e/` — dijalankan
**SATU PER SATU, tidak pernah paralel** (`workers: 1`, `fullyParallel: false`).

## Flow 1 — Cari Lagu
1. Buka `presenter.php` (tab **Lagu** default)
2. Ketik kata kunci → klik **Cari** (atau Enter)
3. Hasil: **local MySQL dulu** (manual + yang sudah tersimpan);
   3 grup remote (unlimitedworship, jrchord, liriklagukristen) **tidak auto-masuk DB** —
   hanya preview
4. Klik hasil local → lagu tampil di preview (bisa di-**Edit**).
   Klik hasil remote → preview sementara (tombol Edit mati).
   Klik **+Simpan** pada hasil remote → lirik penuh di-fetch, disimpan ke DB,
   lalu tampil sebagai lagu tersimpan (bisa di-Edit)
5. Lagu yang sudah tersimpan muncul di grup local pada pencarian berikutnya

E2E: `search.spec.ts`

## Flow 2 — Presentasi Lagu
1. Setelah klik lagu (Flow 1): lirik terpecah jadi slide **2 baris**, jeda antar bagian
2. Kontrol: `⌂ ◀ [n / total] ▶ ⌃`, **daftar slide** (klik untuk lompat), **keyboard** (←/↑ prev, →/↓ next)
3. Counter menampilkan posisi saat ini
4. Buka **Proyektor** → lirik muncul fullscreen

E2E: `song.spec.ts`

## Flow 3 — Tambah Lagu Manual
1. Klik **+ Tambah Lagu** → modal
2. Isi judul (wajib) + lirik (wajib, baris kosong = jeda) + chord (opsional)
3. **Simpan & Tampilkan** → langsung presentasi + tersimpan di MySQL (`source=manual`)
4. Lagu bisa dicari lagi dari Flow 1

E2E: `addsong.spec.ts`

## Flow 4 — Presentasi Alkitab
1. Tab **Alkitab** → pilih kitab → pilih pasal → **Tampilkan Pasal**
2. Ayat tampil 1 ayat per slide, bernomor, counter `n / total`
3. Pasal ke-fetch dari beeble (TB) **sekali**, lalu ter-cache di MySQL —
   kunjungan berikutnya tanpa jaringan
4. Navigasi sama seperti Flow 2

E2E: `bible.spec.ts`

## Flow 5 — Proyektor (OBS)
1. Tombol **Proyektor** di header → buka `projector.php` (window baru)
2. `projector.php` **URL-nya FIXED** (tanpa parameter) → aman diumpankan ke
   OBS Browser Source; konten di-poll dari server tiap 500ms; hanya teks lirik/ayat
3. Presenter & proyektor **sinkron dua arah**: navigasi presenter → proyektor ikut;
   navigasi proyektor (klik kiri/kanan, swipe, keyboard) → presenter ikut
4. Proyektor hanya menampilkan teks (tanpa judul, nomor ayat, dan counter)

E2E: `projector.spec.ts`

## Flow 6 — Fullscreen Proyektor
1. Tombol **Proyektor** membuka window 1280x720
2. Window bisa dipindah ke display kedua (EasyWorship-style)
3. (Desktop app Wails punya tombol Fullscreen native — track terpisah)

Belum ada E2E khusus (window popup OS-level).
