<?php
// EasyLyrics — Projector (URL FIXED: aman untuk OBS browser source)
// Hanya menampilkan isi lirik/ayat — tanpa judul, nomor, atau counter.
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

        .slide-area {
            flex: 1; display: flex; align-items: center; justify-content: center;
            position: relative; cursor: pointer;
            container-type: size;   /* cqw/cqi mengikuti container, bukan viewport */
        }
        .slide {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 60px 80px; text-align: center;
            width: 100%; max-width: 1000px; align-self: stretch; overflow: hidden;
        }
        .slide-text {
            /* base: ikut ukuran container (7% lebar / 5% tinggi) — OBS-safe */
            font-size: min(7cqw, 5cqi); line-height: 1.4; font-weight: 400;
            text-shadow: 0 2px 8px rgba(0,0,0,.5); max-width: 92%;
            overflow-wrap: break-word; white-space: pre-line;
        }
        .idle-hint { color: #444; font-size: 20px; font-family: -apple-system, sans-serif; }

        @media (max-width: 768px) {
            .slide { padding: 30px 20px; }
            .slide-text { font-size: 30px; }
        }
    </style>
</head>
<body>

<div class="slide-area" id="slideArea">
    <div class="idle-hint" id="idleHint">Belum ada presentasi</div>
    <div class="slide" id="slide" style="display:none">
        <div class="slide-text" id="slideText"></div>
    </div>
</div>

<script>
const $ = id => document.getElementById(id);

let slides = [];
let total = 0;
let current = 0;
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
            contentChanged = true;
        }
        // render hanya saat benar-benar berubah (konten baru ATAU index baru) —
        // kalau tidak, poll tiap 500ms bikin teks loncat (font reset)
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
}

function goToLocal(n) {
    if (total === 0 || n < 0 || n >= total) return;
    current = n;
    $('idleHint').style.display = 'none';
    $('slide').style.display = 'flex';
    const s = slides[n];

    if (s.type === 'pause') {
        $('slideText').textContent = '· · ·';
    } else {
        $('slideText').textContent = s.lines.join('\n');
    }
    // selalu fit-ulang: mulai dari ukuran sekarang → tidak ada lompatan
    fitText();
}

// fit text: shrink sampai muat (sama seperti web app lama)
function fitText() {
    const slide = $('slide');
    const text = $('slideText');
    const style = getComputedStyle(slide);
    const padV = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
    const availH = slide.clientHeight - padV - 10;
    if (availH <= 0) return;
    // mulai dari ukuran terhitung sekarang (cqw/cqi → px via getComputedStyle) —
    // hanya mengecil kalau perlu, tidak pernah loncat (anti "bergerak-gerak")
    let size = parseFloat(getComputedStyle(text).fontSize) || 48;
    if (size > 64) size = 64;
    text.style.fontSize = size + 'px';
    // tanpa floor 12px: bisa mengecil sampai 6px supaya ayat super panjang tetap muat
    while (size > 6 && text.scrollHeight > availH) {
        size--;
        text.style.fontSize = size + 'px';
    }
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
