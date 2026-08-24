<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/bibleclient.php';
require_once __DIR__ . '/scraper.php';

/**
 * api.php — JSON API for the operator/presenter UI.
 *
 * Endpoints:
 *   GET  api.php?action=search&q=...        local search; fallback: scrape unlimitedworship + cache
 *   GET  api.php?action=get_song&id=...     song + lyric slides
 *   GET  api.php?action=get_chapter&book=..&chapter=..   verses (cache or beeble) + slides
 *   POST api.php?action=add_song            {title, lyric, chord}
 *   GET  api.php?action=state_get           current projection state
 *   POST api.php?action=state_set           {type, title, ref, slides}
 *   POST api.php?action=state_nav           {slide}
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

function respond(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $message, int $code = 400): never
{
    respond(['error' => $message], $code);
}

function postJson(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$scraper = new UnlimitedWorshipScraper();

switch ($action) {

    // ------------------------------------------------------------ search
    // Search per sumber — frontend memanggil 4x (local, unlimitedworship,
    // jrchord, liriklagukristen) SECARA PARALEL dan menampilkan tiap grup
    // dengan spinner-nya sendiri.
    case 'search':
        $q = trim($_GET['q'] ?? '');
        $source = $_GET['source'] ?? 'local';
        if ($q === '') {
            respond(['source' => $source, 'items' => []]);
        }
        switch ($source) {
            case 'local':
                $items = array_map(fn ($s) => [
                    'id' => $s['id'],
                    'title' => $s['title'],
                    'slug' => $s['slug'],
                    'lyric' => mb_substr((string) $s['lyric'], 0, 120),
                    'source' => $s['source'],
                ], DB::searchSongs($q, 50));
                respond(['source' => 'local', 'items' => $items]);

            case 'unlimitedworship':
                respond(['source' => 'unlimitedworship', 'items' => searchUnlimitedWorship($scraper, $q)]);

            case 'jrchord':
                respond(['source' => 'jrchord', 'items' => searchJrChord($q)]);

            case 'liriklagukristen':
                respond(['source' => 'liriklagukristen', 'items' => searchLirikLaguKristen($q)]);

            default:
                fail('Sumber tidak dikenal.', 404);
        }

    // ----------------------------------------------------------- get_song
    case 'get_song':
        $id = (int) ($_GET['id'] ?? 0);
        $song = DB::getSong($id);
        if (!$song) {
            fail('Lagu tidak ditemukan.', 404);
        }
        // lazy full-detail fetch for remote-sourced songs (search caches only
        // the snippet; the full lyric+chord is fetched once on demand)
        if ($song['source'] !== 'manual' && !empty($song['source_ref'])) {
            try {
                if ($song['source'] === 'jrchord') {
                    $d = (new JrChordScraper())->detail($song['source_ref']);
                } elseif ($song['source'] === 'liriklagukristen') {
                    $d = (new LirikLaguKristenScraper())->detail($song['source_ref']);
                } else {
                    $d = $scraper->detail((int) $song['id'], $song['slug']);
                }
                $fullLyric = $d['lyric'] ?? '';
                if ($fullLyric !== '' && strlen($fullLyric) > strlen($song['lyric'])) {
                    DB::updateSongContent($song['id'], $fullLyric, $d['chord'] ?? '');
                    $song['lyric'] = $fullLyric;
                    $song['chord'] = $d['chord'] ?? '';
                }
            } catch (Throwable $e) {
                // offline: serve cached snippet as-is
            }
        }
        respond([
            'id' => $song['id'],
            'title' => $song['title'],
            'slug' => $song['slug'],
            'lyric' => $song['lyric'],
            'chord' => $song['chord'],
            'slides' => $scraper->splitLyrics($song['lyric']),
        ]);

    // --------------------------------------------------------- get_chapter
    case 'get_chapter':
        $book = $_GET['book'] ?? '';
        $chapter = (int) ($_GET['chapter'] ?? 0);
        $version = $_GET['version'] ?? 'tb';
        if ($book === '' || $chapter < 1) {
            fail('Parameter book dan chapter diperlukan.');
        }

        // cache-first; fetch beeble only when the chapter is not cached yet
        if (!DB::chapterCached($book, $chapter, $version)) {
            try {
                $client = new BibleClient();
                $verses = $client->fetchChapter($book, $chapter, $version);
                DB::saveChapter($book, $chapter, $verses, $version);
            } catch (Throwable $e) {
                // offline: serve whatever is cached (possibly nothing)
            }
        }
        $rows = DB::getChapter($book, $chapter, $version);
        if (count($rows) === 0) {
            fail('Pasal tidak tersedia (periksa koneksi internet).', 404);
        }
        $slides = array_map(fn ($v) => [
            'type' => 'lyric',
            'lines' => [$v['text']],
            'number' => (int) $v['verse'],
        ], $rows);
        $ref = $book . ' ' . $chapter;
        respond(['ref' => $ref, 'slides' => $slides]);

    // ------------------------------------------------------------ add_song
    case 'add_song':
        $body = postJson();
        $title = trim($body['title'] ?? '');
        $lyric = trim($body['lyric'] ?? '');
        $chord = trim($body['chord'] ?? '');
        if ($title === '' || $lyric === '') {
            fail('Judul dan lirik wajib diisi.');
        }
        $saved = DB::insertSong($title, $lyric, $chord, 'manual');
        respond(['id' => $saved['id'], 'title' => $saved['title'], 'slug' => $saved['slug']], 201);

    // ------------------------------------------------------------ state_get
    case 'state_get':
        respond(DB::getState());

    // ------------------------------------------------------------ state_set
    case 'state_set':
        $body = postJson();
        DB::setState(
            (string) ($body['type'] ?? 'idle'),
            (string) ($body['title'] ?? ''),
            (string) ($body['ref'] ?? ''),
            is_array($body['slides'] ?? null) ? $body['slides'] : [],
            (int) ($body['slide'] ?? 0)
        );
        respond(['ok' => true]);

    // ------------------------------------------------------------ state_nav
    case 'state_nav':
        $body = postJson();
        DB::setSlide((int) ($body['slide'] ?? 0));
        respond(['ok' => true]);

    default:
        fail('Aksi tidak dikenal.', 404);
}

// ---- remote search helpers ----

/** unlimitedworship.org fallback (may be Cloudflare-blocked → returns []). */
function searchUnlimitedWorship(UnlimitedWorshipScraper $scraper, string $q): array
{
    try {
        $remote = $scraper->search($q);
        $out = [];
        foreach ($remote as $r) {
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $r['slug']));
            $cached = DB::insertSong(
                $r['title'],
                (string) ($r['lyric_snippet'] ?? ''),
                '',
                'unlimitedworship',
                $slug ?: 'lagu-' . $r['id'],
                $r['url']
            );
            $out[] = [
                'id' => $cached['id'],
                'title' => $r['title'],
                'slug' => $cached['slug'],
                'lyric' => $r['lyric_snippet'] ?? '',
                'source' => 'unlimitedworship',
            ];
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

/** liriklagukristen.id fallback (WordPress, plain HTML). */
function searchLirikLaguKristen(string $q): array
{
    try {
        $lr = new LirikLaguKristenScraper();
        $remote = $lr->search($q);
        $out = [];
        foreach ($remote as $r) {
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $r['slug']));
            $excerpt = mb_substr((string) ($r['excerpt'] ?? ''), 0, 120);
            // index menyimpan lirik penuh — langsung simpan, lazy fetch tidak perlu
            $full = (string) ($r['lyric_full'] ?? '');
            $existing = DB::getSongBySlug($slug);
            if ($existing) {
                if (!empty($full) && strlen($full) > strlen($existing['lyric'])) {
                    DB::updateSongContent($existing['id'], $full, '');
                }
            }
            $cached = $existing ? ['id' => $existing['id'], 'slug' => $existing['slug'], 'title' => $existing['title']]
                : DB::insertSong($r['title'], $full ?: $excerpt, '', 'liriklagukristen', $slug ?: 'lagu-' . substr(md5($r['url']), 0, 6), $r['url']);
            $out[] = [
                'id' => $cached['id'],
                'title' => $r['title'],
                'slug' => $cached['slug'],
                'lyric' => $excerpt,
                'source' => 'liriklagukristen',
            ];
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

/** jrchord.com fallback (plain HTML, always accessible). */
function searchJrChord(string $q): array
{
    try {
        $jr = new JrChordScraper();
        $remote = $jr->search($q);
        // excerpt: fetch detail pages PARALLEL (curl_multi), ambil baris
        // lirik pertama — supaya hasil search bisa dibedakan
        $excerpts = $jr->excerpts($remote);
        $out = [];
        foreach ($remote as $r) {
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $r['slug']));
            $excerpt = $excerpts[$r['slug']] ?? '';
            // update kalau sudah pernah di-cache (hindari duplikat slug)
            $existing = DB::getSongBySlug($slug);
            if ($existing && !empty($excerpt) && $existing['lyric'] === '') {
                DB::updateSongContent($existing['id'], $excerpt, '');
            }
            $cached = $existing ? ['id' => $existing['id'], 'slug' => $existing['slug'], 'title' => $existing['title']]
                : DB::insertSong($r['title'], $excerpt, '', 'jrchord', $slug ?: 'lagu-' . substr(md5($r['url']), 0, 6), $r['url']);
            $out[] = [
                'id' => $cached['id'],
                'title' => $r['title'],
                'slug' => $cached['slug'],
                'lyric' => $excerpts[$r['slug']] ?? '',
                'source' => 'jrchord',
            ];
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}
