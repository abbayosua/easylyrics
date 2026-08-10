<?php

class UnlimitedWorshipScraper
{
    private string $baseUrl = 'https://unlimitedworship.org';

    private function fetch(string $url): string
    {
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

    public function splitLyrics(string $text): array
{
    $text = str_replace("\r\n", "\n", $text);
    $text = str_replace("\r", "\n", $text);
    $lines = explode("\n", $text);

    $slides = [];
    $buffer = [];
    $lastWasPause = false;

    foreach ($lines as $line) {
        $line = trim($line);
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
            if (count($buffer) === 2) {
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
            if (preg_match('/^(intro|bait|reff|refrain|chorus|musik|interlude|ending|overtone|pre[- ]?chorus|jembatan|bridge|coda|instrumental)\s*:?$/i', $t)) {
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
