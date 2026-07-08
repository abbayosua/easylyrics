<?php
require_once __DIR__ . '/scraper.php';

$id = (int) ($_GET['id'] ?? 0);
$slug = $_GET['slug'] ?? '';
if (!$id || !$slug) {
    die('Parameter id dan slug diperlukan.');
}

$scraper = new UnlimitedWorshipScraper();
try {
    $song = $scraper->detail($id, $slug);
} catch (Exception $e) {
    die('Gagal mengambil data: ' . htmlspecialchars($e->getMessage()));
}

$slides = $scraper->splitLyrics($song['lyric'] ?? '');
$channel = "easylyrics-$id";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presenter - <?= htmlspecialchars($song['title']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #111;
            color: #eee;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: #1a1a2e;
            border-bottom: 1px solid #333;
        }
        .header-title {
            font-size: 18px;
            font-weight: 600;
            color: #a78bfa;
        }
        .header-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        .status-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #555;
            transition: background 0.3s;
        }
        .status-dot.connected { background: #22c55e; }
        .status-dot.disconnected { background: #ef4444; }
        .status-label { color: #888; }

        .main {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* Preview panel */
        .preview-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #222;
            position: relative;
            min-width: 0;
        }

        .preview-current {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
            min-height: 0;
        }
        .preview-line {
            font-family: 'Georgia', serif;
            font-size: clamp(28px, 3.5vw, 48px);
            line-height: 1.5;
            max-width: 90%;
            color: #fff;
        }
        .preview-line + .preview-line { margin-top: 12px; }
        .preview-pause {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            opacity: 0.35;
        }
        .preview-pause .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #888;
        }
        .preview-pause .bar { width: 120px; height: 1px; background: #555; }

        .preview-next {
            padding: 16px 20px;
            border-top: 1px solid #222;
            background: #161616;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .preview-next-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #555;
            writing-mode: vertical-lr;
            text-orientation: mixed;
        }
        .preview-next-text {
            font-family: 'Georgia', serif;
            font-size: 18px;
            line-height: 1.4;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .preview-next-text.pause-text { color: #444; font-style: italic; }

        /* Controls bar */
        .controls {
            display: flex;
            justify-content: center;
            gap: 4px;
            padding: 10px;
            background: #161616;
            border-top: 1px solid #222;
        }
        .controls button {
            padding: 8px 18px;
            background: #222;
            color: #ccc;
            border: 1px solid #333;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.15s;
        }
        .controls button:hover { background: #333; color: #fff; }
        .controls button:active { background: #444; }
        .controls .counter {
            padding: 8px 14px;
            color: #888;
            font-size: 13px;
            display: flex;
            align-items: center;
        }

        /* Slide list */
        .list-panel {
            width: 340px;
            overflow-y: auto;
            background: #0d0d0d;
            border-left: 1px solid #222;
            flex-shrink: 0;
        }
        .list-panel::-webkit-scrollbar { width: 6px; }
        .list-panel::-webkit-scrollbar-track { background: #0d0d0d; }
        .list-panel::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }

        .list-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #1a1a1a;
            transition: background 0.15s;
        }
        .list-item:hover { background: #1a1a1a; }
        .list-item.active {
            background: #1e1b4b;
            border-left: 3px solid #a78bfa;
        }
        .list-item.pause-item {
            opacity: 0.4;
            cursor: default;
        }
        .list-item.pause-item:hover { background: transparent; cursor: default; }

        .list-number {
            font-size: 12px;
            color: #555;
            min-width: 24px;
            padding-top: 3px;
            font-variant-numeric: tabular-nums;
        }
        .list-preview {
            flex: 1;
            min-width: 0;
        }
        .list-preview-line {
            font-size: 13px;
            line-height: 1.4;
            color: #aaa;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .list-preview-line + .list-preview-line { margin-top: 2px; }
        .list-preview-pause {
            font-size: 12px;
            color: #444;
            font-style: italic;
        }

        @media (max-width: 800px) {
            .main { flex-direction: column; }
            .list-panel { width: 100%; max-height: 40vh; }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-title"><?= htmlspecialchars($song['title']) ?></div>
        <div class="header-status">
            <div class="status-dot" id="statusDot"></div>
            <span class="status-label" id="statusLabel">Mencari proyektor...</span>
        </div>
    </div>

    <div class="main">
        <div class="preview-panel">
            <div class="preview-current" id="previewCurrent"></div>
            <div class="preview-next" id="previewNext">
                <div class="preview-next-label">Next</div>
                <div class="preview-next-text" id="previewNextText"></div>
            </div>
        </div>

        <div class="list-panel" id="slideList"></div>
    </div>

    <div class="controls">
        <button onclick="goTo(0)">&#8962;</button>
        <button onclick="prev()">&#9664;</button>
        <span class="counter" id="counter">1 / <?= count($slides) ?></span>
        <button onclick="next()">&#9654;</button>
        <button onclick="goTo(<?= count($slides) - 1 ?>)">&#8963;</button>
    </div>

    <script>
        const slides = <?= json_encode($slides) ?>;
        const total = slides.length;
        let current = 0;
        let channel = null;

        // ---- BroadcastChannel ----
        function initChannel() {
            try {
                channel = new BroadcastChannel('<?= $channel ?>');
                channel.onmessage = function(e) {
                    if (e.data.action === 'synced') {
                        setSlide(e.data.index, false);
                    }
                };
                setStatus('connected', 'Proyektor terhubung');

                // Ping to see if projector is alive
                channel.postMessage({ action: 'ping' });
                setTimeout(() => {
                    if (document.querySelector('.status-dot.connected') === null) {
                        setStatus('disconnected', 'Proyektor tidak ditemukan');
                    }
                }, 2000);
            } catch (e) {
                setStatus('disconnected', 'BroadcastChannel tidak didukung');
            }
        }

        function broadcast(action, index) {
            if (channel) {
                channel.postMessage({ action, index });
            }
        }

        function setStatus(state, label) {
            const dot = document.getElementById('statusDot');
            dot.className = 'status-dot ' + state;
            document.getElementById('statusLabel').textContent = label;
        }

        // ---- Navigation ----
        function goTo(n) {
            if (n < 0 || n >= total) return;
            setSlide(n, true);
        }
        function next() { if (current < total - 1) goTo(current + 1); }
        function prev() { if (current > 0) goTo(current - 1); }

        function setSlide(n, broadcastToProjector) {
            current = n;
            renderPreview(n);
            renderSlideList(n);
            document.getElementById('counter').textContent = (n + 1) + ' / ' + total;

            if (broadcastToProjector) {
                broadcast('goTo', n);
            }
        }

        // ---- Render ----
        function renderPreview(n) {
            const el = document.getElementById('previewCurrent');
            const slide = slides[n];

            if (slide.type === 'pause') {
                el.innerHTML = `
                    <div class="preview-pause">
                        <div class="dot"></div>
                        <div class="bar"></div>
                        <div class="dot"></div>
                    </div>`;
            } else {
                el.innerHTML = slide.lines.map(l =>
                    `<div class="preview-line">${escapeHtml(l)}</div>`
                ).join('');
            }

            // Next preview
            const nextEl = document.getElementById('previewNextText');
            if (n + 1 < total) {
                const next = slides[n + 1];
                if (next.type === 'pause') {
                    nextEl.textContent = '(jeda)';
                    nextEl.className = 'preview-next-text pause-text';
                } else {
                    nextEl.textContent = next.lines[0] || '';
                    nextEl.className = 'preview-next-text';
                }
            } else {
                nextEl.textContent = '(selesai)';
                nextEl.className = 'preview-next-text pause-text';
            }
        }

        function renderSlideList(active) {
            const el = document.getElementById('slideList');
            let html = '';
            slides.forEach((slide, i) => {
                const isActive = i === active;
                const cls = 'list-item' +
                    (isActive ? ' active' : '') +
                    (slide.type === 'pause' ? ' pause-item' : '');

                html += `<div class="${cls}" data-index="${i}">`;
                html += `<div class="list-number">${i + 1}</div>`;
                html += `<div class="list-preview">`;

                if (slide.type === 'pause') {
                    html += `<div class="list-preview-pause">&mdash; jeda &mdash;</div>`;
                } else {
                    slide.lines.forEach(line => {
                        html += `<div class="list-preview-line">${escapeHtml(line)}</div>`;
                    });
                }

                html += `</div></div>`;
            });
            el.innerHTML = html;

            // Click handlers
            el.querySelectorAll('.list-item:not(.pause-item)').forEach(item => {
                item.addEventListener('click', function() {
                    goTo(parseInt(this.dataset.index));
                });
            });

            // Scroll active into view
            const activeEl = el.querySelector('.list-item.active');
            if (activeEl) {
                activeEl.scrollIntoView({ block: 'nearest' });
            }
        }

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        // ---- Keyboard ----
        document.addEventListener('keydown', function(e) {
            switch (e.key) {
                case 'ArrowUp':
                case 'ArrowLeft':
                    e.preventDefault(); prev(); break;
                case 'ArrowDown':
                case 'ArrowRight':
                    e.preventDefault(); next(); break;
            }
        });

        // ---- Init ----
        initChannel();
        setSlide(0, false);
    </script>
</body>
</html>
