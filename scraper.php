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
            $lyric = trim($lyricNodes->item(0)->textContent);
        }

        $chordNodes = $xpath->query("//pre[contains(@class, 'lyric-chord')]");
        if ($chordNodes->length > 0) {
            $chord = trim($chordNodes->item(0)->textContent);
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
            if (!$lastWasPause) {
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
