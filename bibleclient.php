<?php

/**
 * BibleClient — fetches chapters from the beeble API (Terjemahan Baru).
 */
class BibleClient
{
    private string $baseUrl = 'https://beeble.vercel.app';

    /**
     * @return array<int, array{verse:int, text:string}>
     */
    public function fetchChapter(string $book, int $chapter, string $version = 'tb'): array
    {
        $url = $this->baseUrl . '/api/v1/passage/' . rawurlencode($book) . '/' . $chapter . '?ver=' . urlencode($version);
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'EasyLyrics/1.0',
                'ignore_errors' => true,
            ],
        ]);
        $json = @file_get_contents($url, false, $ctx);
        if ($json === false) {
            throw new RuntimeException("Gagal mengambil pasal dari beeble: $book $chapter");
        }
        $data = json_decode($json, true);
        if (!isset($data['data']['verses']) || !is_array($data['data']['verses'])) {
            throw new RuntimeException('Respon beeble tidak valid.');
        }
        $out = [];
        foreach ($data['data']['verses'] as $v) {
            if (($v['type'] ?? '') === 'content') {
                $out[] = ['verse' => (int) $v['verse'], 'text' => (string) $v['content']];
            }
        }
        if (count($out) === 0) {
            throw new RuntimeException("Tidak ada ayat untuk $book $chapter.");
        }
        return $out;
    }
}
