<?php
require_once __DIR__ . '/scraper.php';

$scraper = new UnlimitedWorshipScraper();
$search = $_GET['search'] ?? '';
$detailId = $_GET['detail'] ?? '';
$slug = $_GET['slug'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyLyrics</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
            min-height: 100vh;
        }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 {
            text-align: center;
            color: #6C3EB8;
            margin-bottom: 30px;
            font-size: 2em;
        }
        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        .search-form input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.2s;
        }
        .search-form input:focus {
            border-color: #6C3EB8;
        }
        .search-form button {
            padding: 12px 24px;
            background: #6C3EB8;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .search-form button:hover { background: #5a32a0; }
        .back-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #eee;
            color: #333;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            transition: background 0.2s;
        }
        .back-btn:hover { background: #ddd; }
        .song-list { display: flex; flex-direction: column; gap: 10px; }
        .song-card {
            background: white;
            border-radius: 10px;
            padding: 16px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .song-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-1px);
        }
        .song-card-title {
            font-size: 18px;
            font-weight: 600;
            color: #6C3EB8;
            margin-bottom: 6px;
        }
        .song-card-lyric {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .detail-card {
            background: white;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .detail-title {
            font-size: 24px;
            font-weight: 700;
            color: #6C3EB8;
            margin-bottom: 20px;
        }
        .projector-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: #000;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .projector-btn:hover { background: #222; }
        .presenter-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: #1e1b4b;
            color: #a78bfa;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .presenter-btn:hover { background: #2e2a6b; }
        .detail-section {
            margin-bottom: 20px;
        }
        .detail-section h3 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 8px;
        }
        .detail-lyric {
            font-family: 'Georgia', serif;
            font-size: 16px;
            line-height: 1.8;
            white-space: pre-wrap;
            background: #fafafa;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #6C3EB8;
        }
        .detail-chord {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-wrap;
            background: #fafafa;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #e8a838;
            color: #888;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
        }
        .meta-item {
            padding: 12px;
            background: #fafafa;
            border-radius: 8px;
        }
        .meta-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-value {
            font-size: 16px;
            font-weight: 500;
            margin-top: 2px;
        }
        .error {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .no-result {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        @media (max-width: 600px) {
            .search-form { flex-direction: column; }
            .search-form button { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>EasyLyrics</h1>

        <form class="search-form" method="get">
            <input type="text" name="search" placeholder="Cari lagu..." value="<?= htmlspecialchars($search) ?>" autofocus>
            <button type="submit">Cari</button>
        </form>

        <?php if ($detailId): ?>
            <a class="back-btn" href="?search=<?= urlencode($search ?: '') ?>">&larr; Kembali</a>
            <?php
            try {
                $song = $scraper->detail((int)$detailId, $slug);
            } catch (Exception $e) {
                echo '<div class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                exit;
            }
            ?>
            <div class="detail-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <div class="detail-title" style="margin-bottom: 0;"><?= htmlspecialchars($song['title']) ?></div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <a class="projector-btn" href="projector.php?id=<?= $song['id'] ?>&slug=<?= urlencode($song['slug']) ?>" target="_blank">
                            &#9654; Proyektor
                        </a>
                        <a class="presenter-btn" href="presenter.php?id=<?= $song['id'] ?>&slug=<?= urlencode($song['slug']) ?>" target="_blank">
                            &#8291;&#9776; Presenter
                        </a>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Lirik</h3>
                    <div class="detail-lyric"><?= htmlspecialchars($song['lyric']) ?></div>
                </div>

                <?php if ($song['chord']): ?>
                <div class="detail-section">
                    <h3>Chord</h3>
                    <div class="detail-chord"><?= htmlspecialchars($song['chord']) ?></div>
                </div>
                <?php endif; ?>

                <?php if ($song['metadata']): ?>
                <div class="detail-section">
                    <h3>Informasi</h3>
                    <div class="meta-grid">
                        <?php foreach ($song['metadata'] as $label => $value): ?>
                            <div class="meta-item">
                                <div class="meta-label"><?= htmlspecialchars($label) ?></div>
                                <div class="meta-value"><?= htmlspecialchars($value) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($search): ?>
            <div class="song-list">
                <?php
                try {
                    $results = $scraper->search($search);
                } catch (Exception $e) {
                    echo '<div class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    $results = [];
                }

                if (empty($results)): ?>
                    <div class="no-result">Lagu tidak ditemukan</div>
                <?php else: ?>
                    <?php foreach ($results as $item): ?>
                        <a class="song-card" href="?detail=<?= $item['id'] ?>&slug=<?= urlencode($item['slug']) ?>&search=<?= urlencode($search) ?>">
                            <div class="song-card-title"><?= htmlspecialchars($item['title']) ?></div>
                            <div class="song-card-lyric"><?= htmlspecialchars($item['lyric_snippet']) ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="no-result">
                <p>Masukkan judul lagu untuk mencari lirik dan chord.</p>
                <p style="margin-top: 8px; font-size: 14px;">Sumber: <a href="https://unlimitedworship.org" target="_blank" style="color: #6C3EB8;">unlimitedworship.org</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
