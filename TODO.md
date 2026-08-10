# EasyLyrics — Desktop App Roadmap

> **Update (Agustus 2026):** Web app PHP di-revamp total → MySQL + fixed-URL OBS feed.
> File baru: `config.php` (kredensial), `db.php` (PDO + auto-migrate), `api.php` (JSON API),
> `presenter.php` (operator, fixed URL), `projector.php` (fixed URL, poll 500ms, OBS-ready).
> Search = local MySQL dulu; unlimitedworship cuma fallback (sekarang kena Cloudflare, jadi 0 hasil).
> Lagu manual masuk MySQL `songs` (source=manual). Ayat Alkitab cache di `bible_verses`.
> State proyeksi di tabel `state` (type/title/ref/slide/slides) — `projector.php` poll `state_get`.
> Path desktop/ (Wails Go) tetap ada sebagai track terpisah.

Target: aplikasi desktop stand-alone mirip EasyWorship/ProPresenter (text first, background/advanced belakangan).

## Arsitektur

- **Bahasa**: Go + Wails (UI tetap HTML/CSS/JS)
- **DB**: SQLite (1 file `.db`, bisa di-copy/backup ke komputer lain)
- **Multi-window**: operator window + proyektor window (layar kedua, fullscreen)
- **Search**: hybrid — local DB + REST API backend (fase 2)
- **Scraping lirik**: lewat REST API backend (fase 2 terakhir)

---

## FASE 1 — Kerangka Aplikasi Desktop

- [x] Inisialisasi project Wails (Go) dengan UI template kosong
- [x] Dua window: operator + proyektor (fullscreen di display kedua)
- [x] Komunikasi antar-window (IPC Wails, ganti BroadcastChannel)
- [x] Dark theme UI dasar untuk operator

### Testing Fase 1

```bash
cd desktop
wails3 dev          # development mode (hot reload)
```

- [x] App jalan, 2 window muncul (operator + proyektor)
- [x] Tombol "Proyektor" pindah ke display kedua fullscreen
- [x] Pesan IPC antar-window terkirim (cek di console/log)

## FASE 2 — Local Database (SQLite)

- [x] Setup SQLite (pure Go, `modernc.org/sqlite` — tanpa C compiler)
- [x] Schema tabel:
  - [x] `songs` (id, title, slug, lyric, chord, metadata JSON, created_at)
  - [x] `bible_verses` (id, book, chapter, verse, text, version, created_at)
- [x] CRUD dasar untuk songs & bible_verses
- [x] Import/export DB (copy file `.db` = backup/pindah komputer)
- [x] Search di local DB (title/lyric `LIKE` query)
- [x] DB terbuka otomatis saat app start (`~/Library/Application Support/EasyLyrics/easilyrics.db`)

### Testing Fase 2

```bash
cd desktop && go test ./...
```

- [x] `TestInsertSong` — insert song → query balik → isi cocok
- [x] `TestUpdateSong` — update lyric → terubah di DB
- [x] `TestDeleteSong` — delete → tidak ketemu lagi
- [x] `TestSearchLike` — search "kasih" ketemu "Kasihmu" (case-insensitive)
- [x] `TestSearchNoResult` — search kata yang tidak ada → hasil kosong
- [x] `TestCopyDB` — copy file `.db` → buka instance baru → data masih ada
- [x] `TestBibleVersesCRUD` — insert + search ayat per book/chapter
- [x] `TestBibleDifferentVersions` — versi beda (tb/bis) tidak bentrok
- [x] `TestGetChapterSafeMissing` — pasal belum di-cache → ErrNoVerses

## FASE 3 — Presentasi Teks (kayak EasyWorship text aja)

- [x] Pilih lagu dari library → slide lirik (2 baris, pause slide)
- [x] Pilih kitab+pasal → slide ayat
- [x] Presenter mode: prev/next, slide list (click-to-jump), counter
- [x] Proyektor mode: fullscreen, navigasi keyboard
- [x] Navigasi: arrow keys, click, swipe (reuse dari web app sekarang)
- [x] Font auto-fit biar ayat panjang gak kepotong (fit-text)

### Testing Fase 3

```bash
cd desktop && go test ./...   # unit test logika
wails3 dev                    # test UI manual
```

- [x] `TestSplitLyrics` — port dari `splitLyrics` PHP: slide max 2 baris, pause antar section, tidak ada pause di awal/akhir
- [x] `TestSplitLyricsEmpty` — lyric kosong → slide kosong, tidak error
- [x] `TestFitTextCalc` — fungsi hitung font-size: teks panjang → size lebih kecil, teks pendek → size besar
- [x] `TestFitTextBoundary` — teks pas muat di size maksimum → tidak berkurang
- [x] `TestBibleSlides` — 1 ayat = 1 slide, bernomor
- [x] `TestGetChapter` — fetch beeble (mock server): title verse diskip, content verse diambil
- [x] `TestGetChapterServerError` — 500 → error, tidak crash
- [ ] Manual: ganti slide di presenter → proyektor ikut (IPC)
- [ ] Manual: navigasi keyboard di proyektor (arrow, Esc)
- [ ] Manual: navigasi click kiri/kanan di proyektor
- [ ] Manual: font auto-fit: buka ayat panjang (misal Yoh 3) → tidak kepotong

## FASE 4 — Integrasi Backend REST API (terakhir)

- [ ] Backend REST (bungkus scraper PHP yang sudah ada jadi API)
- [ ] Search hybrid: query local DB + call REST API, gabung & dedupe hasil
- [ ] Auto-download: lagu tidak ada di DB → fetch REST → simpan ke local
- [ ] Cache ayat alkitab dari beeble ke local DB

### Testing Fase 4

```bash
go test ./...      # dengan httptest mock server
```

Unit test (dibuat saat fitur dibuat):

- [ ] `TestParseSongResponse` — response scraper → mapping ke struct Song
- [ ] `TestHybridSearchDedupe` — lagu ada di local & REST → hasil tampil 1x
- [ ] `TestHybridSearchLocalOnly` — REST mati (mock return 500) → tetap tampil hasil local
- [ ] `TestAutoDownloadSuccess` — lagu tidak ada di DB → fetch → tersimpan
- [ ] `TestAutoDownloadServerDown` — server error → tidak crash, ada pesan error
- [ ] `TestBibleCache` — fetch pasal → tersimpan → fetch lagi pakai cache (mock hitung jumlah request)

Manual:

- [ ] Search lagu baru → muncul dari REST → otomatis tersimpan di DB
- [ ] Matikan internet → search lagu yang sudah pernah di-download → tetap muncul

## FASE 5 — (Nanti) Dekorasi & Animasi

- [ ] Background (solid/gambar/video)
- [ ] Transisi & animasi slide (CSS/GSAP/WebGL)
- [ ] Dll — belum dikerjakan sekarang

### Testing Fase 5

- [ ] Unit test untuk logika transisi (durasi, easing) jika ada
- [ ] Manual: animasi berjalan mulus (60fps) di proyektor — cek dengan devtools performance

---

## Global (berjalan terus di setiap fase)

```bash
go vet ./...       # static check
go test ./...      # semua test
wails build        # pastikan build sukses di Mac & Windows (GOOS=windows)
```

- [ ] Backup: copy `.db` → hapus app → restore → data lengkap
- [ ] Setiap fase selesai: semua test hijau sebelum lanjut ke fase berikutnya
