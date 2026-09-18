<?php

class UnlimitedWorshipScraper
{
    private string $baseUrl = 'https://unlimitedworship.org';

    private function fetch(string $url): string
    {
        // resolve relative path (hasil search berupa /slug/)
        if (str_starts_with($url, '/')) {
            $url = $this->baseUrl . $url;
        }
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            CURLOPT_TIMEOUT => 30,
        ]);
        $html = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            throw new RuntimeException("cURL error ($errno): $error");
        }

        return $html;
    }

    private function dom(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        return new DOMXPath($dom);
    }

    private function innerText(DOMElement $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $child->ownerDocument->saveHTML($child);
        }
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = strip_tags($html);
        return trim($html);
    }

    public function search(string $keyword): array
    {
        $url = $this->baseUrl . '/search?type=1&key=' . urlencode($keyword);
        $xpath = $this->dom($this->fetch($url));

        $results = [];
        $items = $xpath->query("//a[contains(@class, 'song-item')]");

        foreach ($items as $item) {
            $href = $item->getAttribute('href');
            if (!preg_match('#/songs/detail/(\d+)/([\w-]+)#', $href, $m)) {
                continue;
            }

            $titleNode = $xpath->query(".//div[contains(@class, 'song-title-text')]", $item);
            $title = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : '';

            $lyricNode = $xpath->query(".//div[contains(@class, 'song-lyric-text')]", $item);
            $lyricSnippet = $lyricNode->length > 0 ? trim($lyricNode->item(0)->textContent) : '';

            $results[] = [
                'id' => (int) $m[1],
                'slug' => $m[2],
                'title' => $title,
                'lyric_snippet' => $lyricSnippet,
                'url' => $href,
            ];
        }

        return $results;
    }

    public function detail(int $id, string $slug): array
    {
        $url = $this->baseUrl . "/songs/detail/$id/$slug";
        $xpath = $this->dom($this->fetch($url));

        $title = '';
        $lyric = '';
        $chord = '';
        $metadata = [];

        $titleNodes = $xpath->query("//div[contains(@class, 'now-playing-title')]");
        if ($titleNodes->length > 0) {
            $title = trim($titleNodes->item(0)->textContent);
        }

        $lyricNodes = $xpath->query("//pre[contains(@class, 'lyric-content') and contains(@class, 'active')]");
        if ($lyricNodes->length > 0) {
            $lyric = $this->innerText($lyricNodes->item(0));
        }

        $chordNodes = $xpath->query("//pre[contains(@class, 'lyric-chord')]");
        if ($chordNodes->length > 0) {
            $chord = $this->innerText($chordNodes->item(0));
        }

        $infoNodes = $xpath->query("//div[contains(@class, 'song-info')]//div[contains(@class, 'info')]");
        foreach ($infoNodes as $info) {
            $labelNode = $xpath->query(".//span[contains(@class, 'info-label')]", $info);
            $valueNode = $xpath->query(".//span[contains(@class, 'info-value')]", $info);
            if ($labelNode->length > 0 && $valueNode->length > 0) {
                $label = trim($labelNode->item(0)->textContent);
                $value = trim($valueNode->item(0)->textContent);
                $metadata[$label] = $value;
            }
        }

        return [
            'id' => $id,
            'slug' => $slug,
            'title' => $title ?: $slug,
            'url' => $url,
            'lyric' => $lyric,
            'chord' => $chord,
            'metadata' => $metadata,
        ];
    }

    public function splitLyrics(string $text, int $perSlide = 2): array
{
    $text = str_replace("\r\n", "\n", $text);
    $text = str_replace("\r", "\n", $text);
    if ($perSlide < 1) {
        $perSlide = 1;
    }
    $lines = explode("\n", $text);

    $slides = [];
    $buffer = [];
    $lastWasPause = false;

    foreach ($lines as $line) {
        $line = trim($line);
        // section labels ("Reff:", "Chorus", "Verse 2", ...) are not lyric
        if ($line !== '' && $this->isSectionLabel($line)) {
            $line = '';
        }
        if ($line === '') {
            if (!empty($buffer)) {
                $slides[] = ['type' => 'lyric', 'lines' => $buffer];
                $buffer = [];
            }
            // never a pause before any lyric slide (leading blank lines) and
            // consecutive blank lines merge into one pause
            if (!empty($slides) && !$lastWasPause) {
                $slides[] = ['type' => 'pause'];
                $lastWasPause = true;
            }
        } else {
            $buffer[] = $line;
            $lastWasPause = false;
            if (count($buffer) >= $perSlide) {
                $slides[] = ['type' => 'lyric', 'lines' => $buffer];
                $buffer = [];
            }
        }
    }

    if (!empty($buffer)) {
        $slides[] = ['type' => 'lyric', 'lines' => $buffer];
    }

    if (($slides[count($slides)-1]['type'] ?? '') === 'pause') {
        array_pop($slides);
    }

    return $slides;
}

private function isSectionLabel(string $line): bool
{
    return (bool) preg_match(
        '/^(intro|bait|reff|refrain|chorus|verse|musik|interlude|ending|overtone|pre[- ]?chorus|jembatan|bridge|coda|instrumental|outro|tag)\s*\d*\s*:?\s*$/i',
        trim($line)
    );
}
}

/**
 * JrChordScraper — second source: https://www.jrchord.com/
 * (unlimitedworship.org & suaranafiri are behind bot protection; this one is plain HTML.)
 */
class JrChordScraper
{
    private string $baseUrl = 'https://www.jrchord.com';

    private function fetch(string $url): string
    {
        // resolve relative path (hasil search berupa /slug/)
        if (str_starts_with($url, '/')) {
            $url = $this->baseUrl . $url;
        }
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            CURLOPT_TIMEOUT => 20,
        ]);
        $html = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        if ($errno) {
            throw new RuntimeException("cURL error ($errno)");
        }
        return $html;
    }

    private function dom(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        return new DOMXPath($dom);
    }

    /** @return array{id:string, slug:string, title:string, url:string} */
    public function search(string $keyword): array
    {
        $xpath = $this->dom($this->fetch($this->baseUrl . '/?s=' . urlencode($keyword)));
        $out = [];
        $seen = [];
        // non-song pages to skip
        $blocked = ['daftar-lagu', 'request', 'privacy-policy', 'tentang', 'kontak', 'kategori'];
        foreach ($xpath->query("//article//a[contains(@href, 'jrchord.com/')]") as $a) {
            $href = trim($a->getAttribute('href'));
            if (!preg_match('#jrchord\.com/([a-z0-9-]+)/?$#', $href, $m)) {
                continue;
            }
            $slug = $m[1];
            if (in_array($slug, $blocked, true) || isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;
            $title = trim($a->textContent) ?: $slug;
            // title may include the artist on later lines ("Title" + newline + "Artist")
            $firstLine = preg_split('/\R/', $title)[0];
            $out[] = ['slug' => $slug, 'title' => trim($firstLine), 'url' => $href];
            if (count($out) >= 10) {
                break;
            }
        }
        return $out;
    }

    /** @return array{title:string, lyric:string, chord:string} */
    public function detail(string $url): array
    {
        $xpath = $this->dom($this->fetch($url));
        $title = '';
        $titles = $xpath->query("//h1");
        if ($titles->length > 0) {
            $title = trim($titles->item(0)->textContent);
        }
        $pre = '';
        $pres = $xpath->query("//pre");
        if ($pres->length > 0) {
            $pre = trim($pres->item(0)->textContent);
        }
        // user wants lyrics only — chords are stripped out
        return ['title' => $title ?: basename(parse_url($url, PHP_URL_PATH)), 'lyric' => $this->stripChords($pre), 'chord' => ''];
    }

    /**
     * Removes chord-only lines and section labels from a JRChord <pre> block,
     * keeping only the lyric lines.
     */
    private function stripChords(string $text): string
    {
        $lines = preg_split('/\R/', $text);
        $out = [];
        foreach ($lines as $line) {
            $line = rtrim($line);
            $t = trim($line);
            if ($t === '') {
                $out[] = ''; // keep blank line as section break
                continue;
            }
            // heading "Chord <title>"
            if (preg_match('/^Chord\s+/i', $t)) {
                continue;
            }
            // section labels: Bait :, Reff :, Intro :, ...
            if ($this->isSectionLabel($t)) {
                continue;
            }
            // chord-only line: every whitespace-separated token is a chord symbol
            if ($this->isChordLine($t)) {
                continue;
            }
            $out[] = $line;
        }
        // collapse multiple blank lines to one
        $clean = [];
        $prevBlank = false;
        foreach ($out as $line) {
            if ($line === '') {
                if ($prevBlank) {
                    continue;
                }
                $prevBlank = true;
            } else {
                $prevBlank = false;
            }
            $clean[] = $line;
        }
        return implode("
", $clean);
    }

    private function isChordLine(string $line): bool
    {
        $tokens = preg_split('/\s+/', trim($line));
        if (count($tokens) === 0 || count($tokens) > 12) {
            return false;
        }
        // every token must be a chord symbol (A-G with optional #/b, suffix, slash)
        foreach ($tokens as $tok) {
            if (!preg_match('/^[A-G](#|b)?(m|M|maj|min|dim|sus|aug|add)?(\d+)?(\/[A-G](#|b)?(m|M)?(\d+)?)?$/', $tok)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Fetches detail pages IN PARALLEL (curl_multi) and returns the first
     * lyric line of each song — dipakai sebagai excerpt hasil search.
     *
     * @param array<int, array{slug:string,title:string,url:string}> $items
     * @return array<string, string> slug => excerpt
     */
    public function excerpts(array $items): array
    {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($items as $i => $it) {
            $url = str_starts_with($it['url'], '/') ? $this->baseUrl . $it['url'] : $it['url'];
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                CURLOPT_TIMEOUT => 8,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[] = ['ch' => $ch, 'slug' => $it['slug']];
        }
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.1);
        } while ($running > 0);

        $out = [];
        foreach ($handles as $h) {
            $html = curl_multi_getcontent($h['ch']);
            curl_multi_remove_handle($mh, $h['ch']);
            $slug = $h['slug'];
            if (!$html) {
                continue;
            }
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xp = new DOMXPath($dom);
            $pres = $xp->query("//pre");
            if ($pres->length === 0) {
                continue;
            }
            $lyric = $this->stripChords(trim($pres->item(0)->textContent));
            // ambil baris lirik pertama yang tidak kosong
            $first = '';
            foreach (preg_split('/\R/', $lyric) as $ln) {
                $ln = trim($ln);
                if ($ln !== '') {
                    $first = $ln;
                    break;
                }
            }
            if ($first !== '') {
                $out[$slug] = mb_substr($first, 0, 120);
            }
        }
        curl_multi_close($mh);
        return $out;
    }
}

/**
 * LirikLaguKristenScraper — third source: https://liriklagukristen.id/
 * Situs menyediakan /search-index.json (slug + judul + lirik penuh, ±4800 lagu)
 * — pencarian dilakukan lokal di index ini, tanpa scraping halaman search.
 */
class LirikLaguKristenScraper
{
    private string $baseUrl = 'https://liriklagukristen.id';
    private static ?array $index = null;

    private function fetch(string $url): string
    {
        // resolve relative path
        if (str_starts_with($url, '/')) {
            $url = $this->baseUrl . $url;
        }
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            CURLOPT_TIMEOUT => 30,
        ]);
        $html = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        if ($errno) {
            throw new RuntimeException("cURL error ($errno)");
        }
        return $html;
    }

    /** Index lagu (di-cache per proses PHP — fetch sekali saja). */
    public function getIndex(): array
    {
        if (self::$index === null) {
            $data = json_decode($this->fetch('/search-index.json'), true);
            self::$index = is_array($data) ? $data : [];
        }
        return self::$index;
    }

    /**
     * Cari di index lokal (judul ATAU lirik mengandung kata kunci).
     *
     * @return array<int, array{slug:string,title:string,url:string,excerpt:string,lyric_full:string}>
     */
    public function search(string $keyword): array
    {
        $q = mb_strtolower(trim($keyword));
        if ($q === '') {
            return [];
        }
        $out = [];
        foreach ($this->getIndex() as $e) {
            $title = trim((string) ($e['t'] ?? ''));
            $lyric = (string) ($e['l'] ?? '');
            $slug  = (string) ($e['s'] ?? '');
            if ($title === '' || $slug === '') {
                continue;
            }
            if (!str_contains(mb_strtolower($title), $q)
                && !str_contains(mb_strtolower($lyric), $q)) {
                continue;
            }
            // excerpt: baris lirik pertama yang tidak kosong
            $excerpt = '';
            foreach (preg_split("/\R/", $lyric) as $ln) {
                $ln = trim($ln, " 	`“”‘’");
                if ($ln !== '') {
                    $excerpt = $ln;
                    break;
                }
            }
            $out[] = [
                'slug'       => $slug,
                'title'      => $title,
                'url'        => $this->baseUrl . '/' . $slug . '/',
                'excerpt'    => mb_substr($excerpt, 0, 120),
                'lyric_full' => $lyric,
            ];
            if (count($out) >= 10) {
                break;
            }
        }
        return $out;
    }

    /** Detail lengkap dari halaman HTML (untuk entry yang terpotong di index). */
    public function detail(string $url): array
    {
        $xpath = $this->dom($this->fetch($url));

        $title = '';
        $titles = $xpath->query("//h1");
        if ($titles->length > 0) {
            $title = trim($titles->item(0)->textContent);
        }

        $lyric = '';
        $lyrics = $xpath->query("//div[contains(@class, 'lyrics')]");
        if ($lyrics->length > 0) {
            // convert <br> to newlines, strip tags, normalize blank lines
            $html = '';
            foreach ($lyrics->item(0)->childNodes as $child) {
                $html .= $child->ownerDocument->saveHTML($child);
            }
            $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
            $text = trim(strip_tags($html));
            $text = preg_replace('/[\r\n]{3,}/', "\n\n", $text);
            $lyric = $text;
        }
        return [
            'title' => $title ?: basename(parse_url($url, PHP_URL_PATH)),
            'lyric' => $lyric,
            'chord' => '',
        ];
    }

    private function dom(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        return new DOMXPath($dom);
    }
}




if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $scraper = new UnlimitedWorshipScraper();

    if (!isset($argv[1])) {
        echo "Usage:\n";
        echo "  Search: php scraper.php <keyword>\n";
        echo "  Detail: php scraper.php <id> detail <slug>\n";
        exit;
    }

    if (isset($argv[2]) && $argv[2] === 'detail') {
        $id = (int) $argv[1];
        $slug = $argv[3] ?? '';
        if (!$slug) {
            $results = $scraper->search((string) $id);
            if ($results) {
                $slug = $results[0]['slug'];
            } else {
                echo "Song not found.\n";
                exit(1);
            }
        }
        $song = $scraper->detail($id, $slug);
        echo json_encode($song, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        $results = $scraper->search($argv[1]);
        echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
