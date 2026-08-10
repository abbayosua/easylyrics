<?php
// EasyLyrics — Projector (URL FIXED: aman untuk OBS browser source)
// Tidak ada parameter query. Polling state dari api.php setiap 500ms.
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>EasyLyrics — Proyektor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; overflow: hidden; }
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #000; color: #fff;
            display: flex; flex-direction: column; user-select: none;
        }
        .toolbar {
            position: fixed; top: 0; left: 0; right: 0;
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 20px; background: rgba(0,0,0,.7); z-index: 10;
            opacity: 0; transition: opacity .3s;
        }
        .toolbar:hover { opacity: 1; }
        .toolbar-title { font-size: 16px; font-weight: 600; color: #aaa; }
        .toolbar-counter { font-size: 14px; color: #888; }

        .slide-area {
            flex: 1; display: flex; align-items: center; justify-content: center;
            position: relative; cursor: pointer;
            container-type: size;   /* cqw/cqi mengikuti container, bukan viewport */
        }
        .slide {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 20px; padding: 60px 80px; text-align: center;
            width: 100%; max-width: 900px; align-self: stretch; overflow: hidden;
        }
        .slide-ref { font-size: clamp(18px, 2vw, 28px); color: #a78bfa; font-family: -apple-system, sans-serif; font-weight: 700; }
        .slide-text {
            /* base: ikut ukuran container (7% lebar / 5% tinggi) — OBS-safe */
            font-size: min(7cqw, 5cqi); line-height: 1.4; font-weight: 400;
            text-shadow: 0 2px 8px rgba(0,0,0,.5); max-width: 90%;
            overflow-wrap: break-word;
        }
        .slide-title {
            position: absolute; bottom: 24px; left: 50%; transform: translateX(-50%);
            font-family: -apple-system, sans-serif; font-size: 14px; color: #555;
            max-width: 80%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .idle-hint { color: #444; font-size: 20px; font-family: -apple-system, sans-serif; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .slide.active { animation: fadeIn .3s ease; }

        @media (max-width: 768px) {
            .slide { padding: 30px 20px; }
            .slide-text { font-size: 30px; }
            .toolbar { opacity: 1; }
        }
    </style>
</head>
<body>

<div class="toolbar" id="toolbar">
    <div class="toolbar-title" id="toolbarTitle">EasyLyrics — Proyektor</div>
    <span class="toolbar-counter" id="counter">- / -</span>
</div>

<div class="slide-area" id="slideArea">
    <div class="idle-hint" id="idleHint">Belum ada presentasi</div>
    <div class="slide" id="slide" style="display:none">
        <div class="slide-ref" id="slideRef" style="display:none"></div>
        <div class="slide-text" id="slideText"></div>
    </div>
    <div class="slide-title" id="slideTitle"></div>
</div>

<script>
const $ = id => document.getElementById(id);

let slides = [];
let total = 0;
let current = 0;
let fitted = {};    // cache font-size per slide index — jangan re-fit yang sama
let lastKey = '';   // last updated_at dari server, deteksi perubahan konten

async function poll() {
    try {
        const r = await fetch('api.php?action=state_get');
        const s = await r.json();

        if (s.type === 'idle' || !s.slides || s.slides.length === 0) {
            showIdle();
            return;
        }
        // konten ganti? (presenter set presentasi baru -> updated_at berubah)
        let contentChanged = false;
        if (s.updated_at !== lastKey) {
            lastKey = s.updated_at;
            slides = s.slides;
            total = s.slides.length;
            fitted = {}; // konten baru → reset cache ukuran font
            $('toolbarTitle').textContent = s.title || '';
            $('slideTitle').textContent = s.title || '';
            contentChanged = true;
        }
        // render hanya saat benar-benar berubah (konten baru ATAU index baru) —
        // kalau tidak, poll tiap 500ms bikin teks loncat (font reset ke 80px)
        if (contentChanged || s.slide !== current) {
            goToLocal(s.slide);
        }
    } catch (e) {
        // server mati: diam saja, slide terakhir tetap tampil
    }
}

function showIdle() {
    $('idleHint').style.display = '';
    $('slide').style.display = 'none';
    $('slideTitle').textContent = '';
    $('counter').textContent = '- / -';
    $('toolbarTitle').textContent = 'EasyLyrics — Proyektor';
}

function goToLocal(n) {
    if (total === 0 || n < 0 || n >= total) return;
    current = n;
    $('idleHint').style.display = 'none';
    $('slide').style.display = 'flex';
    const s = slides[n];

    $('slideRef').style.display = s.number ? '' : 'none';
    $('slideRef').textContent = s.number || '';

    if (s.type === 'pause') {
        $('slideText').textContent = '· · ·';
    } else {
        $('slideText').textContent = s.lines.join('\n');
    }
    $('counter').textContent = `${n + 1} / ${total}`;
    // selalu fit-ulang: mulai dari ukuran sekarang (cache) → tidak ada
    // lompatan ke 80px, dan tidak pernah overflow dari ukuran basi
    fitText();
}

// fit text: shrink sampai muat (sama seperti web app lama)
function fitText() {
    const slide = $('slide');
    const text = $('slideText');
    const ref = $('slideRef');
    const style = getComputedStyle(slide);
    const padV = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
    const gap = parseFloat(style.gap) || 0;
    const refH = ref.style.display === 'none' ? 0 : ref.offsetHeight;
    const availH = slide.clientHeight - padV - refH - gap - 10;
    if (availH <= 0) return;
    // mulai dari ukuran terhitung sekarang (cqw/cqi → px via getComputedStyle) —
    // hanya mengecil kalau perlu, tidak pernah loncat ke 80px (anti "bergerak-gerak")
    let size = parseFloat(getComputedStyle(text).fontSize) || 48;
    if (size > 64) size = 64;
    text.style.fontSize = size + 'px';
    // tanpa floor 12px: bisa mengecil sampai 6px supaya ayat super panjang
    // tetap muat di window/container kecil
    while (size > 6 && text.scrollHeight > availH) {
        size--;
        text.style.fontSize = size + 'px';
    }
    fitted[current] = size;
}

// navigasi langsung dari proyektor (klik/keys) — kirim balik ke state
function nav(d) {
    const n = current + d;
    if (n < 0 || n >= total) return;
    fetch('api.php?action=state_nav', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ slide: n }),
    });
    current = n;
    goToLocal(n);
}

document.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); nav(-1); }
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); nav(1); }
});

$('slideArea').addEventListener('click', e => {
    const rect = $('slideArea').getBoundingClientRect();
    const x = e.clientX - rect.left;
    if (x < rect.width / 2) nav(-1); else nav(1);
});

let touchStartX = 0;
$('slideArea').addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; });
$('slideArea').addEventListener('touchend', e => {
    const diff = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(diff) > 40) { if (diff < 0) nav(1); else nav(-1); }
});

let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(fitText, 150);
});

showIdle();
setInterval(poll, 500);   // poll: 500ms — responsif tapi tetap ringan
poll();
</script>
</body>
</html>
