<?php
// EasyLyrics — Operator (fixed URL, all state via AJAX to api.php)
$books = [
    ['Kej','Kejadian',50],['Kel','Keluaran',40],['Ima','Imamat',27],['Bil','Bilangan',36],
    ['Ula','Ulangan',34],['Yos','Yosua',24],['Hak','Hakim-hakim',21],['Rut','Rut',4],
    ['1 Sam','1 Samuel',31],['2 Sam','2 Samuel',24],['1 Raj','1 Raja-Raja',22],['2 Raj','2 Raja-Raja',25],
    ['1 Taw','1 Tawarikh',29],['2 Taw','2 Tawarikh',36],['Ezr','Ezra',10],['Neh','Nehemia',13],
    ['Est','Ester',10],['Ayb','Ayub',42],['Maz','Mazmur',150],['Ams','Amsal',31],
    ['Pkh','Pengkhotbah',12],['Kid','Kidung Agung',8],['Yes','Yesaya',66],['Yer','Yeremia',52],
    ['Rat','Ratapan',5],['Yeh','Yehezkiel',48],['Dan','Daniel',12],['Hos','Hosea',14],
    ['Yoe','Yoel',3],['Amo','Amos',9],['Oba','Obaja',1],['Yun','Yunus',4],
    ['Mik','Mikha',7],['Nah','Nahum',3],['Hab','Habakuk',3],['Zef','Zefanya',3],
    ['Hag','Hagai',2],['Zak','Zakharia',14],['Mal','Maleakhi',4],['Mat','Matius',28],
    ['Mar','Markus',16],['Luk','Lukas',24],['Yoh','Yohanes',21],['Kis','Kisah Para Rasul',28],
    ['Rom','Roma',16],['1 Kor','1 Korintus',16],['2 Kor','2 Korintus',13],['Gal','Galatia',6],
    ['Efe','Efesus',6],['Flp','Filipi',4],['Kol','Kolose',4],['1 Tes','1 Tesalonika',5],
    ['2 Tes','2 Tesalonika',3],['1 Tim','1 Timotius',6],['2 Tim','2 Timotius',4],['Tit','Titus',3],
    ['Flm','Filemon',1],['Ibr','Ibrani',13],['Yak','Yakobus',5],['1 Pet','1 Petrus',5],
    ['2 Pet','2 Petrus',3],['1 Yoh','1 Yohanes',5],['2 Yoh','2 Yohanes',1],['3 Yoh','3 Yohanes',1],
    ['Yud','Yudas',1],['Wah','Wahyu',22],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>EasyLyrics — Operator</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #111; color: #eee;
            display: flex; flex-direction: column;
        }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 16px; background: #1a1a2e; border-bottom: 1px solid #333;
        }
        .header-title { font-size: 17px; font-weight: 700; color: #a78bfa; }
        .header-right { display: flex; gap: 8px; align-items: center; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background: #555; }
        .status-dot.on { background: #22c55e; }
        .btn {
            padding: 7px 14px; border: none; border-radius: 6px; font-size: 13px;
            font-weight: 600; cursor: pointer; transition: background .15s;
        }
        .btn-primary { background: #a78bfa; color: #1a1a2e; }
        .btn-primary:hover { background: #c4b5fd; }
        .btn-dark { background: #222; color: #ccc; border: 1px solid #333; }
        .btn-dark:hover { background: #333; color: #fff; }
        .btn-add { background: #22c55e; color: #052e16; }
        .btn-add:hover { background: #4ade80; }

        .main { flex: 1; display: flex; min-height: 0; }
        .library {
            width: 300px; background: #0d0d0d; border-right: 1px solid #222;
            display: flex; flex-direction: column; flex-shrink: 0;
        }
        .tabs { display: flex; border-bottom: 1px solid #222; }
        .tab {
            flex: 1; padding: 9px; background: #161616; border: none; color: #888;
            font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .tab.active { background: #1e1b4b; color: #a78bfa; }
        .lib-panel { flex: 1; display: flex; flex-direction: column; min-height: 0; }
        .lib-search { display: flex; gap: 6px; padding: 8px; border-bottom: 1px solid #222; }
        .lib-search input, .lib-search select {
            flex: 1; min-width: 0; padding: 7px 10px; background: #222; color: #eee;
            border: 1px solid #444; border-radius: 6px; font-size: 13px;
        }
        .lib-search select:focus, .lib-search input:focus { outline: none; border-color: #a78bfa; }
        .lib-list { flex: 1; overflow-y: auto; }
        .lib-item { padding: 9px 14px; border-bottom: 1px solid #1a1a1a; cursor: pointer; }
        .lib-item:hover { background: #1a1a1a; }
        .lib-item.active { background: #1e1b4b; border-left: 3px solid #a78bfa; }
        .lib-item-title { font-size: 14px; font-weight: 500; color: #ccc; }
        .lib-item-sub { font-size: 11px; color: #555; margin-top: 2px; }
        .lib-empty { padding: 14px; color: #555; font-size: 13px; line-height: 1.5; }

        .stage { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .preview {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; padding: 30px; text-align: center;
            position: relative;
        }
        .preview-title {
            position: absolute; top: 14px; left: 50%; transform: translateX(-50%);
            font-size: 13px; color: #a78bfa; font-weight: 700; white-space: nowrap; max-width: 90%; overflow: hidden; text-overflow: ellipsis;
        }
        .preview-ref { font-size: 15px; color: #a78bfa; font-weight: 700; margin-bottom: 14px; }
        .preview-line { font-family: Georgia, serif; font-size: clamp(24px, 3vw, 42px); line-height: 1.5; color: #fff; }
        .preview-line + .preview-line { margin-top: 10px; }
        .preview-pause { opacity: .35; }
        .pause-dot { width: 10px; height: 10px; border-radius: 50%; background: #888; margin: 5px auto; }
        .pause-dot.wide { width: 160px; height: 2px; border-radius: 0; }
        .empty-hint { color: #555; font-size: 16px; }

        .controls {
            display: flex; justify-content: center; gap: 4px; padding: 8px;
            background: #161616; border-top: 1px solid #222;
        }
        .controls button {
            padding: 8px 18px; background: #222; color: #ccc; border: 1px solid #333;
            border-radius: 6px; cursor: pointer; font-size: 14px;
        }
        .controls button:hover { background: #333; color: #fff; }
        .counter { padding: 8px 14px; color: #888; font-size: 13px; font-variant-numeric: tabular-nums; }

        .slide-list { display: flex; gap: 6px; padding: 8px 12px; border-top: 1px solid #222; overflow-x: auto; background: #0d0d0d; }
        .sl-item {
            flex-shrink: 0; min-width: 60px; max-width: 140px; padding: 6px 8px;
            background: #1a1a1a; border-radius: 6px; cursor: pointer; text-align: center;
            font-size: 11px; color: #888; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .sl-item.active { background: #1e1b4b; color: #a78bfa; border: 1px solid #a78bfa; }

        /* modal */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 100;
            display: none; align-items: center; justify-content: center;
        }
        .modal {
            width: 560px; max-width: 92vw; max-height: 88vh; overflow-y: auto;
            background: #1a1a2e; border: 1px solid #333; border-radius: 10px; padding: 20px;
        }
        .modal h3 { color: #a78bfa; margin-bottom: 14px; }
        .modal input, .modal textarea {
            width: 100%; padding: 9px 12px; background: #222; color: #eee;
            border: 1px solid #444; border-radius: 6px; font-size: 14px;
            margin-bottom: 10px; font-family: inherit; resize: vertical;
        }
        .modal label { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #888; margin-bottom: 4px; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-title">EasyLyrics — Operator</div>
    <div class="header-right">
        <span class="status-dot" id="statusDot" title="Proyektor terkoneksi?"></span>
        <button class="btn btn-dark" onclick="openProjector()">&#9654; Proyektor</button>
        <button class="btn btn-add" onclick="openModal()">+ Tambah Lagu</button>
    </div>
</div>

<div class="main">
    <div class="library">
        <div class="tabs">
            <button class="tab active" id="tabSongs" data-testid="tabSongs" onclick="switchTab('songs')">Lagu</button>
            <button class="tab" id="tabBible" data-testid="tabBible" onclick="switchTab('bible')">Alkitab</button>
        </div>

        <div class="lib-panel" id="panelSongs">
            <div class="lib-search">
                <input id="q" placeholder="Cari lagu..." data-testid="songSearch" onkeydown="if(event.key==='Enter')doSearch()"/>
                <button class="btn btn-primary" data-testid="btnSearch" onclick="doSearch()">Cari</button>
            </div>
            <div class="lib-list" id="songList"><div class="lib-empty">Ketik untuk mencari, atau tambah lagu manual.</div></div>
        </div>

        <div class="lib-panel" id="panelBible" style="display:none">
            <div class="lib-search">
                <select id="bookSel" data-testid="bookSel"><option value="">-- Kitab --</option>
                    <?php foreach ($books as $b): ?>
                    <option value="<?= htmlspecialchars($b[0]) ?>"><?= htmlspecialchars($b[1]) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="chapSel" data-testid="chapSel"><option value="">Pasal</option></select>
            </div>
            <div class="lib-search">
                <button class="btn btn-primary" style="width:100%" data-testid="btnLoadChapter" onclick="loadChapter()">Tampilkan Pasal</button>
            </div>
            <div class="lib-empty">Pilih kitab & pasal. Ayat ter-cache otomatis di MySQL.</div>
        </div>
    </div>

    <div class="stage">
        <div class="preview" id="preview">
            <div class="preview-title" id="previewTitle"></div>
            <div class="empty-hint" id="emptyHint" data-testid="emptyHint">Pilih lagu atau pasal untuk memulai</div>
            <div class="preview-ref" id="previewRef" style="display:none" data-testid="previewRef"></div>
            <div id="previewLines" data-testid="previewLines"></div>
        </div>
        <div class="slide-list" id="slideList"></div>
        <div class="controls">
            <button data-testid="btnFirst" onclick="goTo(0)">&#8962;</button>
            <button data-testid="btnPrev" onclick="nav(-1)">&#9664;</button>
            <span class="counter" id="counter" data-testid="counter">- / -</span>
            <button data-testid="btnNext" onclick="nav(1)">&#9654;</button>
            <button data-testid="btnLast" onclick="goTo(total-1)">&#8963;</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="addModal" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <h3>Tambah Lagu Manual</h3>
        <label>Judul</label>
        <input id="mTitle" placeholder="Judul lagu"/>
        <label>Lirik (baris kosong = jeda antar bagian)</label>
        <textarea id="mLyric" style="min-height:150px" placeholder="Copy-paste lirik di sini..."></textarea>
        <label>Chord (opsional)</label>
        <textarea id="mChord" style="min-height:60px" placeholder="Chord..."></textarea>
        <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:6px">
            <button class="btn btn-dark" onclick="closeModal()">Batal</button>
            <button class="btn btn-primary" onclick="saveSong()">Simpan & Tampilkan</button>
        </div>
    </div>
</div>

<script>
const $ = id => document.getElementById(id);

let slides = [];
let total = 0;
let current = 0;

// ---------------- helpers ----------------
function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

async function api(action, params = {}, method = 'GET') {
    const url = 'api.php?action=' + action + (method === 'GET' && params ? '&' + new URLSearchParams(params) : '');
    const opt = { method };
    if (method === 'POST') {
        opt.headers = { 'Content-Type': 'application/json' };
        opt.body = JSON.stringify(params);
    }
    const r = await fetch(url, opt);
    return r.json();
}

// ---------------- presentation state ----------------
function setSlide(n, send) {
    if (total === 0) return;
    n = Math.max(0, Math.min(n, total - 1));
    current = n;
    render();
    if (send) api('state_nav', { slide: n }, 'POST');
}

function nav(d) { setSlide(current + d, true); }
function goTo(n) { setSlide(n, true); }

function present(title, ref, newSlides) {
    slides = newSlides;
    total = slides.length;
    current = 0;
    $('previewTitle').textContent = title;
    api('state_set', { type: 'song', title, ref, slides }, 'POST');
    render();
}

function render() {
    $('emptyHint').style.display = 'none';
    const s = slides[current];
    $('previewRef').style.display = (s && s.number) ? '' : 'none';
    $('previewRef').textContent = (s && s.number) ? s.number : '';
    if (!s || s.type === 'pause') {
        $('previewLines').innerHTML = '<div class="preview-pause"><div class="pause-dot"></div><div class="pause-dot wide"></div><div class="pause-dot"></div></div>';
    } else {
        $('previewLines').innerHTML = s.lines.map(l => `<div class="preview-line">${esc(l)}</div>`).join('');
    }
    $('counter').textContent = `${current + 1} / ${total}`;

    const list = $('slideList');
    list.innerHTML = '';
    slides.forEach((s, i) => {
        const el = document.createElement('div');
        el.className = 'sl-item' + (i === current ? ' active' : '');
        el.textContent = s.type === 'pause' ? '· · ·' : (s.number || (s.lines[0] || '').slice(0, 24));
        el.title = s.lines ? s.lines.join(' ') : '';
        el.onclick = () => goTo(i);
        list.appendChild(el);
    });
    const act = list.querySelector('.sl-item.active');
    if (act) act.scrollIntoView({ block: 'nearest', inline: 'nearest' });
}

// ---------------- search (local DB; unlimitedworship hanya fallback) ----------------
async function doSearch() {
    const q = $('q').value.trim();
    const list = $('songList');
    if (!q) return;
    list.innerHTML = '<div class="lib-empty">Mencari...</div>';
    const r = await api('search', { q });
    if (!r.items || r.items.length === 0) {
        list.innerHTML = '<div class="lib-empty">Tidak ditemukan. Coba kata lain atau tambah manual.</div>';
        return;
    }
    list.innerHTML = '';
    r.items.forEach(item => {
        const el = document.createElement('div');
        el.className = 'lib-item';
        el.innerHTML = `<div class="lib-item-title">${esc(item.title)}</div>
                        <div class="lib-item-sub">${esc(item.lyric || '')} · ${esc(item.source || '')}</div>`;
        el.onclick = async () => {
            list.querySelectorAll('.lib-item').forEach(x => x.classList.remove('active'));
            el.classList.add('active');
            const song = await api('get_song', { id: item.id });
            present(song.title, song.title, song.slides);
        };
        list.appendChild(el);
    });
}

// ---------------- bible ----------------
const BOOKS = <?= json_encode($books, JSON_UNESCAPED_UNICODE) ?>;
$('bookSel').addEventListener('change', () => {
    const b = BOOKS.find(x => x[0] === $('bookSel').value);
    const sel = $('chapSel');
    sel.innerHTML = '';
    if (!b) return;
    for (let i = 1; i <= b[2]; i++) {
        const o = document.createElement('option');
        o.value = i; o.textContent = 'Pasal ' + i;
        sel.appendChild(o);
    }
});

async function loadChapter() {
    const book = $('bookSel').value, chap = $('chapSel').value;
    if (!book || !chap) return;
    const r = await api('get_chapter', { book, chapter: chap });
    if (r.error) { alert(r.error); return; }
    present(r.ref, r.ref, r.slides);
}

// ---------------- manual add ----------------
function openModal() { $('addModal').style.display = 'flex'; $('mTitle').focus(); }
function closeModal() { $('addModal').style.display = 'none'; }

async function saveSong() {
    const title = $('mTitle').value.trim();
    const lyric = $('mLyric').value.trim();
    const chord = $('mChord').value.trim();
    if (!title || !lyric) { alert('Judul dan lirik wajib diisi.'); return; }
    const r = await api('add_song', { title, lyric, chord }, 'POST');
    if (r.error) { alert(r.error); return; }
    closeModal();
    const song = await api('get_song', { id: r.id });
    present(song.title, song.title, song.slides);
    // isi input dengan judul baru lalu cari supaya lagu muncul di daftar
    $('q').value = title;
    doSearch();
}

// ---------------- tabs ----------------
function switchTab(which) {
    $('tabSongs').classList.toggle('active', which === 'songs');
    $('tabBible').classList.toggle('active', which === 'bible');
    $('panelSongs').style.display = which === 'songs' ? '' : 'none';
    $('panelBible').style.display = which === 'bible' ? '' : 'none';
}

// ---------------- projector ----------------
function openProjector() {
    // opens projector.php (URL FIXED — aman diumpankan ke OBS browser source);
    // window bisa dipindah ke display kedua
    const w = window.open('projector.php', 'proyektor', 'width=1280,height=720');
    if (w) $('statusDot').className = 'status-dot on';
}

document.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); nav(-1); }
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); nav(1); }
    if (e.key === 'Escape') closeModal();
});

// ---- sinkron balik dari proyektor (proyektor bisa navigasi sendiri) ----
let stateKey = '';
async function pollState() {
    try {
        const s = await api('state_get');
        if (s.type === 'idle' || !s.slides || s.slides.length === 0) return;
        if (s.updated_at !== stateKey) {
            // konten presentasi baru dari pihak lain (mis. tab presenter kedua)
            stateKey = s.updated_at;
            slides = s.slides;
            total = s.slides.length;
            $('previewTitle').textContent = s.title || '';
        }
        if (typeof s.slide === 'number' && s.slide !== current && s.slide >= 0 && s.slide < total) {
            setSlide(s.slide, false);
        }
    } catch (e) { /* server mati: diam */ }
}
setInterval(pollState, 1000);
pollState();
</script>
</body>
</html>
