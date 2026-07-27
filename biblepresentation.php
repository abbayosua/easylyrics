<?php

$baseUrl = 'https://beeble.vercel.app';

$books = [
    ['abbr' => 'Kej', 'name' => 'Kejadian', 'chapters' => 50],
    ['abbr' => 'Kel', 'name' => 'Keluaran', 'chapters' => 40],
    ['abbr' => 'Ima', 'name' => 'Imamat', 'chapters' => 27],
    ['abbr' => 'Bil', 'name' => 'Bilangan', 'chapters' => 36],
    ['abbr' => 'Ula', 'name' => 'Ulangan', 'chapters' => 34],
    ['abbr' => 'Yos', 'name' => 'Yosua', 'chapters' => 24],
    ['abbr' => 'Hak', 'name' => 'Hakim-hakim', 'chapters' => 21],
    ['abbr' => 'Rut', 'name' => 'Rut', 'chapters' => 4],
    ['abbr' => '1 Sam', 'name' => '1 Samuel', 'chapters' => 31],
    ['abbr' => '2 Sam', 'name' => '2 Samuel', 'chapters' => 24],
    ['abbr' => '1 Raj', 'name' => '1 Raja-Raja', 'chapters' => 22],
    ['abbr' => '2 Raj', 'name' => '2 Raja-Raja', 'chapters' => 25],
    ['abbr' => '1 Taw', 'name' => '1 Tawarikh', 'chapters' => 29],
    ['abbr' => '2 Taw', 'name' => '2 Tawarikh', 'chapters' => 36],
    ['abbr' => 'Ezr', 'name' => 'Ezra', 'chapters' => 10],
    ['abbr' => 'Neh', 'name' => 'Nehemia', 'chapters' => 13],
    ['abbr' => 'Est', 'name' => 'Ester', 'chapters' => 10],
    ['abbr' => 'Ayb', 'name' => 'Ayub', 'chapters' => 42],
    ['abbr' => 'Maz', 'name' => 'Mazmur', 'chapters' => 150],
    ['abbr' => 'Ams', 'name' => 'Amsal', 'chapters' => 31],
    ['abbr' => 'Pkh', 'name' => 'Pengkhotbah', 'chapters' => 12],
    ['abbr' => 'Kid', 'name' => 'Kidung Agung', 'chapters' => 8],
    ['abbr' => 'Yes', 'name' => 'Yesaya', 'chapters' => 66],
    ['abbr' => 'Yer', 'name' => 'Yeremia', 'chapters' => 52],
    ['abbr' => 'Rat', 'name' => 'Ratapan', 'chapters' => 5],
    ['abbr' => 'Yeh', 'name' => 'Yehezkiel', 'chapters' => 48],
    ['abbr' => 'Dan', 'name' => 'Daniel', 'chapters' => 12],
    ['abbr' => 'Hos', 'name' => 'Hosea', 'chapters' => 14],
    ['abbr' => 'Yoe', 'name' => 'Yoel', 'chapters' => 3],
    ['abbr' => 'Amo', 'name' => 'Amos', 'chapters' => 9],
    ['abbr' => 'Oba', 'name' => 'Obaja', 'chapters' => 1],
    ['abbr' => 'Yun', 'name' => 'Yunus', 'chapters' => 4],
    ['abbr' => 'Mik', 'name' => 'Mikha', 'chapters' => 7],
    ['abbr' => 'Nah', 'name' => 'Nahum', 'chapters' => 3],
    ['abbr' => 'Hab', 'name' => 'Habakuk', 'chapters' => 3],
    ['abbr' => 'Zef', 'name' => 'Zefanya', 'chapters' => 3],
    ['abbr' => 'Hag', 'name' => 'Hagai', 'chapters' => 2],
    ['abbr' => 'Zak', 'name' => 'Zakharia', 'chapters' => 14],
    ['abbr' => 'Mal', 'name' => 'Maleakhi', 'chapters' => 4],
    ['abbr' => 'Mat', 'name' => 'Matius', 'chapters' => 28],
    ['abbr' => 'Mar', 'name' => 'Markus', 'chapters' => 16],
    ['abbr' => 'Luk', 'name' => 'Lukas', 'chapters' => 24],
    ['abbr' => 'Yoh', 'name' => 'Yohanes', 'chapters' => 21],
    ['abbr' => 'Kis', 'name' => 'Kisah Para Rasul', 'chapters' => 28],
    ['abbr' => 'Rom', 'name' => 'Roma', 'chapters' => 16],
    ['abbr' => '1 Kor', 'name' => '1 Korintus', 'chapters' => 16],
    ['abbr' => '2 Kor', 'name' => '2 Korintus', 'chapters' => 13],
    ['abbr' => 'Gal', 'name' => 'Galatia', 'chapters' => 6],
    ['abbr' => 'Efe', 'name' => 'Efesus', 'chapters' => 6],
    ['abbr' => 'Flp', 'name' => 'Filipi', 'chapters' => 4],
    ['abbr' => 'Kol', 'name' => 'Kolose', 'chapters' => 4],
    ['abbr' => '1 Tes', 'name' => '1 Tesalonika', 'chapters' => 5],
    ['abbr' => '2 Tes', 'name' => '2 Tesalonika', 'chapters' => 3],
    ['abbr' => '1 Tim', 'name' => '1 Timotius', 'chapters' => 6],
    ['abbr' => '2 Tim', 'name' => '2 Timotius', 'chapters' => 4],
    ['abbr' => 'Tit', 'name' => 'Titus', 'chapters' => 3],
    ['abbr' => 'Flm', 'name' => 'Filemon', 'chapters' => 1],
    ['abbr' => 'Ibr', 'name' => 'Ibrani', 'chapters' => 13],
    ['abbr' => 'Yak', 'name' => 'Yakobus', 'chapters' => 5],
    ['abbr' => '1 Pet', 'name' => '1 Petrus', 'chapters' => 5],
    ['abbr' => '2 Pet', 'name' => '2 Petrus', 'chapters' => 3],
    ['abbr' => '1 Yoh', 'name' => '1 Yohanes', 'chapters' => 5],
    ['abbr' => '2 Yoh', 'name' => '2 Yohanes', 'chapters' => 1],
    ['abbr' => '3 Yoh', 'name' => '3 Yohanes', 'chapters' => 1],
    ['abbr' => 'Yud', 'name' => 'Yudas', 'chapters' => 1],
    ['abbr' => 'Wah', 'name' => 'Wahyu', 'chapters' => 22],
];

$book = $_GET['book'] ?? '';
$chapter = (int) ($_GET['chapter'] ?? 0);
$ver = $_GET['ver'] ?? 'tb';
$verses = [];
$bookName = '';
$loaded = false;

if ($book && $chapter > 0) {
    $apiUrl = "$baseUrl/api/v1/passage/" . urlencode($book) . "/$chapter?ver=" . urlencode($ver);
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'EasyLyrics/1.0']]);
    $json = @file_get_contents($apiUrl, false, $ctx);
    if ($json) {
        $data = json_decode($json, true);
        if ($data && isset($data['data']['verses'])) {
            foreach ($data['data']['verses'] as $v) {
                if ($v['type'] === 'content') {
                    $verses[] = ['number' => $v['verse'], 'text' => $v['content']];
                }
            }
            $bookName = $data['data']['book']['name'] ?? $book;
            $loaded = true;
        }
    }
}

$channel = $book ? "bible-" . urlencode($book) . "-$chapter" : 'bible';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $loaded ? htmlspecialchars($bookName) . ' ' . $chapter . ' - Alkitab' : 'Alkitab - Presenter' ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #111;
            color: #eee;
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
            flex-wrap: wrap;
            gap: 8px;
        }
        .header-title {
            font-size: 18px;
            font-weight: 600;
            color: #a78bfa;
        }
        .header-selectors {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        .header-selectors select {
            padding: 6px 12px;
            background: #222;
            color: #eee;
            border: 1px solid #444;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }
        .header-selectors select:focus { outline: none; border-color: #a78bfa; }
        .btn {
            padding: 6px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s;
        }
        .btn-primary { background: #a78bfa; color: #1a1a2e; }
        .btn-primary:hover { background: #c4b5fd; }
        .btn-proyektor { background: #000; color: #fff; }
        .btn-proyektor:hover { background: #222; }
        .btn-proyektor.disabled { opacity: 0.4; pointer-events: none; }
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
            align-items: flex-start;
            flex: 1;
            overflow: hidden;
        }

        .preview-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #222;
            position: relative;
            min-width: 0;
        }
        .preview-current {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
        }
        .preview-ref {
            font-size: 14px;
            color: #a78bfa;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .preview-line {
            font-family: 'Georgia', serif;
            font-size: clamp(28px, 3.5vw, 48px);
            line-height: 1.5;
            max-width: 90%;
            color: #fff;
        }
        .preview-line + .preview-line { margin-top: 12px; }

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

        .list-panel {
            width: 340px;
            overflow-y: auto;
            background: #0d0d0d;
            border-left: 1px solid #222;
            flex-shrink: 0;
            align-self: stretch;
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
            line-height: 1.5;
            color: #aaa;
        }

        .empty-state {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
            color: #666;
        }
        .empty-state p { font-size: 16px; max-width: 400px; line-height: 1.6; }

        @media (max-width: 800px) {
            .main { flex-direction: column; }
            .list-panel { width: 100%; max-height: 40vh; }
            .header { flex-direction: column; align-items: stretch; }
            .header-selectors { justify-content: center; }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-title">Alkitab</div>
        <div class="header-selectors">
            <select id="bookSelect" onchange="onBookChange()">
                <option value="">-- Pilih Kitab --</option>
                <?php foreach ($books as $b): ?>
                    <option value="<?= htmlspecialchars($b['abbr']) ?>" data-chapters="<?= $b['chapters'] ?>" <?= $book === $b['abbr'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="chapterSelect">
                <option value="">-- Pasal --</option>
            </select>
            <button class="btn btn-primary" onclick="loadChapter()">Muat</button>
            <?php if ($loaded): ?>
                <a class="btn btn-proyektor" id="proyektorBtn" href="bibleprojector.php?book=<?= urlencode($book) ?>&chapter=<?= $chapter ?>&ver=<?= urlencode($ver) ?>" onclick="openProjector(event, this.href); return false;">&#9654; Proyektor</a>
            <?php else: ?>
                <a class="btn btn-proyektor disabled" href="#">&#9654; Proyektor</a>
            <?php endif; ?>
            <div class="header-status">
                <div class="status-dot" id="statusDot"></div>
                <span class="status-label" id="statusLabel">Proyektor</span>
            </div>
        </div>
    </div>

    <div class="main">
        <?php if ($loaded): ?>
        <div class="preview-panel">
            <div class="preview-current" id="previewCurrent"></div>
            <div class="preview-next" id="previewNext">
                <div class="preview-next-label">Next</div>
                <div class="preview-next-text" id="previewNextText"></div>
            </div>
        </div>
        <div class="list-panel" id="slideList"></div>
        <?php else: ?>
        <div class="empty-state">
            <p>Pilih kitab dan pasal, lalu klik <strong>Muat</strong> untuk memulai presentasi.</p>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($loaded): ?>
    <div class="controls">
        <button onclick="goTo(0)">&#8962;</button>
        <button onclick="prev()">&#9664;</button>
        <span class="counter" id="counter">1 / <?= count($verses) ?></span>
        <button onclick="next()">&#9654;</button>
        <button onclick="goTo(<?= count($verses) - 1 ?>)">&#8963;</button>
    </div>
    <?php endif; ?>

    <script>
        const verses = <?= json_encode($verses) ?>;
        const total = verses.length;
        let current = 0;
        let channel = null;

        <?php if ($loaded): ?>
        function initChannel() {
            try {
                channel = new BroadcastChannel('<?= $channel ?>');
                channel.onmessage = function(e) {
                    if (e.data.action === 'synced') {
                        setSlide(e.data.index, false);
                    }
                };
                setStatus('connected', 'Proyektor terhubung');
                channel.postMessage({ action: 'ping' });
                setTimeout(() => {
                    if (document.querySelector('.status-dot.connected') === null) {
                        setStatus('disconnected', 'Proyektor tidak ditemukan');
                    }
                }, 2000);
            } catch (e) {
                setStatus('disconnected', 'BroadcastChannel N/A');
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

        function renderPreview(n) {
            const el = document.getElementById('previewCurrent');
            const v = verses[n];
            el.innerHTML = '<div class="preview-ref">' + escapeHtml(v.number) + '</div>' +
                '<div class="preview-line">' + escapeHtml(v.text) + '</div>';

            const nextEl = document.getElementById('previewNextText');
            if (n + 1 < total) {
                nextEl.textContent = verses[n + 1].text.substring(0, 80) + (verses[n + 1].text.length > 80 ? '...' : '');
            } else {
                nextEl.textContent = '(selesai)';
            }
        }

        function renderSlideList(active) {
            const el = document.getElementById('slideList');
            let html = '';
            verses.forEach((v, i) => {
                const isActive = i === active;
                const cls = 'list-item' + (isActive ? ' active' : '');
                html += '<div class="' + cls + '" data-index="' + i + '">';
                html += '<div class="list-number">' + v.number + '</div>';
                html += '<div class="list-preview"><div class="list-preview-line">' + escapeHtml(v.text) + '</div></div>';
                html += '</div>';
            });
            el.innerHTML = html;
            el.querySelectorAll('.list-item').forEach(item => {
                item.addEventListener('click', function() {
                    goTo(parseInt(this.dataset.index));
                });
            });
            const activeEl = el.querySelector('.list-item.active');
            if (activeEl) {
                activeEl.scrollIntoView({ block: 'nearest' });
            }
        }

        function openProjector(e, href) {
            window.open(href, 'bibleproyektor', 'width=1280,height=720,menubar=no,toolbar=no,location=no,status=no');
        }

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

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

        initChannel();
        setSlide(0, false);
        <?php endif; ?>

        function onBookChange() {
            const sel = document.getElementById('bookSelect');
            const ch = document.getElementById('chapterSelect');
            const opt = sel.options[sel.selectedIndex];
            if (!opt || !opt.value) {
                ch.innerHTML = '<option value="">-- Pasal --</option>';
                return;
            }
            const chapters = parseInt(opt.dataset.chapters);
            ch.innerHTML = '';
            for (let i = 1; i <= chapters; i++) {
                const o = document.createElement('option');
                o.value = i;
                o.textContent = 'Pasal ' + i;
                ch.appendChild(o);
            }
        }

        function loadChapter() {
            const book = document.getElementById('bookSelect').value;
            const chapter = document.getElementById('chapterSelect').value;
            if (!book || !chapter) return;
            window.location.href = 'biblepresentation.php?book=' + encodeURIComponent(book) + '&chapter=' + chapter + '&ver=tb';
        }

        // Initialize chapter select if book is pre-selected
        <?php if ($book): ?>
        onBookChange();
        document.getElementById('chapterSelect').value = '<?= $chapter ?>';
        <?php endif; ?>
    </script>
</body>
</html>
