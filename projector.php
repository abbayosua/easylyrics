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
    <title>Proyektor - <?= htmlspecialchars($song['title']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; overflow: hidden; }
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #000;
            color: #fff;
            display: flex;
            flex-direction: column;
            user-select: none;
        }

        .toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: rgba(0,0,0,0.7);
            z-index: 10;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .toolbar:hover { opacity: 1; }
        .toolbar-title {
            font-size: 16px;
            font-weight: 600;
            color: #aaa;
        }
        .toolbar-actions { display: flex; gap: 10px; align-items: center; }
        .toolbar-counter {
            font-size: 14px;
            color: #888;
        }
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: #555;
        }
        .status-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #444;
            transition: background 0.3s;
        }
        .status-dot.on { background: #22c55e; }
        .status-dot.off { background: #ef4444; }
        .btn-exit {
            padding: 6px 16px;
            background: #c0392b;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-exit:hover { background: #e74c3c; }

        .slide-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
        }

        .slide {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 20px;
            padding: 60px 80px;
            text-align: center;
            width: 100%;
        }
        .slide.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .slide-line {
            font-size: clamp(32px, 5vw, 64px);
            line-height: 1.4;
            font-weight: 400;
            text-shadow: 0 2px 8px rgba(0,0,0,0.5);
            max-width: 90%;
        }
        .slide-line:empty { display: none; }

        .slide-pause {
            display: none;
            align-items: center;
            justify-content: center;
            padding: 60px 80px;
            width: 100%;
        }
        .slide-pause.active { display: flex; }
        .pause-indicator {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            opacity: 0.5;
        }
        .pause-dot {
            width: 12px; height: 12px;
            border-radius: 50%;
            background: #555;
        }
        .pause-dot.spacer { width: 200px; height: 2px; background: #333; border-radius: 0; }

        .nav-hint {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: #444;
            font-size: 13px;
            font-family: -apple-system, sans-serif;
            opacity: 0;
            transition: opacity 0.5s;
        }
        .nav-hint.show { opacity: 1; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .slide { padding: 30px 20px; }
            .slide-line { font-size: clamp(24px, 6vw, 40px); }
            .toolbar { opacity: 1; }
        }
    </style>
</head>
<body>

    <div class="toolbar" id="toolbar">
        <div class="toolbar-title"><?= htmlspecialchars($song['title']) ?></div>
        <div class="toolbar-actions">
            <div class="status-indicator">
                <div class="status-dot" id="statusDot"></div>
                <span id="statusLabel">Presenter</span>
            </div>
            <span class="toolbar-counter" id="counter">1 / <?= count($slides) ?></span>
            <button class="btn-exit" onclick="exitProjector()">Tutup</button>
        </div>
    </div>

    <div class="slide-area" id="slideArea">
        <?php $idx = 0; ?>
        <?php foreach ($slides as $slide): $idx++; ?>
            <?php if ($slide['type'] === 'lyric'): ?>
                <div class="slide<?= $idx === 1 ? ' active' : '' ?>" data-index="<?= $idx ?>">
                    <?php foreach ($slide['lines'] as $line): ?>
                        <div class="slide-line"><?= htmlspecialchars($line) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="slide-pause<?= $idx === 1 ? ' active' : '' ?>" data-index="<?= $idx ?>">
                    <div class="pause-indicator">
                        <div class="pause-dot"></div>
                        <div class="pause-dot spacer"></div>
                        <div class="pause-dot"></div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="nav-hint" id="navHint">&#8593;&#8595; &#8592;&#8594; navigasi &middot; Esc tutup</div>

    <script>
        const slideEls = document.querySelectorAll('.slide, .slide-pause');
        const total = slideEls.length;
        let current = 0;
        let hintTimeout;
        let channel = null;

        // ---- BroadcastChannel ----
        function initChannel() {
            try {
                channel = new BroadcastChannel('<?= $channel ?>');
                channel.onmessage = function(e) {
                    const data = e.data;
                    switch (data.action) {
                        case 'goTo':
                            goTo(data.index, false);
                            break;
                        case 'ping':
                            channel.postMessage({ action: 'synced', index: current });
                            updateStatus('on', 'Presenter terhubung');
                            break;
                    }
                };
                updateStatus('off', 'Menunggu presenter...');
            } catch (e) {
                updateStatus('off', 'BroadcastChannel N/A');
            }
        }

        function broadcast(action, index) {
            if (channel) {
                channel.postMessage({ action, index });
            }
        }

        function updateStatus(state, label) {
            const dot = document.getElementById('statusDot');
            dot.className = 'status-dot ' + state;
            document.getElementById('statusLabel').textContent = label;
        }

        // ---- Navigation ----
        function goTo(n, broadcastToPresenter) {
            if (n < 0 || n >= total) return;
            slideEls.forEach(el => el.classList.remove('active'));
            slideEls[n].classList.add('active');
            current = n;
            document.getElementById('counter').textContent = (n + 1) + ' / ' + total;

            if (broadcastToPresenter !== false) {
                broadcast('synced', n);
            }
        }

        function next() { if (current < total - 1) goTo(current + 1); }
        function prev() { if (current > 0) goTo(current - 1); }

        function exitProjector() {
            if (document.referrer) {
                window.location.href = document.referrer;
            } else {
                window.close();
            }
        }

        // Keyboard
        document.addEventListener('keydown', function(e) {
            switch (e.key) {
                case 'ArrowUp':
                case 'ArrowLeft':
                    e.preventDefault();
                    prev();
                    break;
                case 'ArrowDown':
                case 'ArrowRight':
                    e.preventDefault();
                    next();
                    break;
                case 'Escape':
                    exitProjector();
                    break;
            }
        });

        // Click / tap on slide area
        document.getElementById('slideArea').addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            if (x < rect.width / 2) {
                prev();
            } else {
                next();
            }
        });

        // Touch support
        let touchStartX = 0;
        document.getElementById('slideArea').addEventListener('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
        });
        document.getElementById('slideArea').addEventListener('touchend', function(e) {
            const diff = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(diff) > 40) {
                if (diff < 0) next();
                else prev();
            }
        });

        // Nav hint auto-hide
        function showHint() {
            const hint = document.getElementById('navHint');
            hint.classList.add('show');
            clearTimeout(hintTimeout);
            hintTimeout = setTimeout(() => hint.classList.remove('show'), 4000);
        }
        showHint();
        document.addEventListener('keydown', showHint);
        document.addEventListener('click', showHint);

        // ---- Init ----
        initChannel();
    </script>
</body>
</html>
