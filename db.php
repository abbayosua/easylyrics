<?php

require_once __DIR__ . '/config.php';

/**
 * DB — PDO wrapper with auto-migration. Creates the database and tables
 * on first run so the app works out of the box.
 */
class DB
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // ensure database exists
        $server = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        self::$pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        self::migrate(self::$pdo);
        return self::$pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec("
CREATE TABLE IF NOT EXISTS songs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(255) NOT NULL,
    slug       VARCHAR(255) NOT NULL UNIQUE,
    lyric      TEXT NOT NULL,
    chord      TEXT,
    metadata   JSON,
    source     ENUM('manual','unlimitedworship') NOT NULL DEFAULT 'manual',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_title (title)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bible_verses (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    book    VARCHAR(50)  NOT NULL,
    chapter INT          NOT NULL,
    verse   INT          NOT NULL,
    text    TEXT         NOT NULL,
    version VARCHAR(10)  NOT NULL DEFAULT 'tb',
    UNIQUE KEY uk_ref (book, chapter, verse, version)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS state (
    id         TINYINT PRIMARY KEY DEFAULT 1,
    type       VARCHAR(20) NOT NULL DEFAULT 'idle',
    title      VARCHAR(255),
    ref        VARCHAR(255),
    slide      INT NOT NULL DEFAULT 0,
    slides     JSON,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO state (id, type) VALUES (1, 'idle')
ON DUPLICATE KEY UPDATE id = id;
");
    }

    // ---- songs ----

    /** @return array{id:int,title:string,slug:string,lyric:string,chord:?string,metadata:array,source:string} */
    public static function getSong(int $id): ?array
    {
        $st = self::conn()->prepare('SELECT * FROM songs WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ? self::decodeSong($row) : null;
    }

    public static function getSongBySlug(string $slug): ?array
    {
        $st = self::conn()->prepare('SELECT * FROM songs WHERE slug = ?');
        $st->execute([$slug]);
        $row = $st->fetch();
        return $row ? self::decodeSong($row) : null;
    }

    /** Local search, case-insensitive title/lyric match. */
    public static function searchSongs(string $q, int $limit = 50): array
    {
        $like = '%' . $q . '%';
        $st = self::conn()->prepare(
            'SELECT * FROM songs WHERE title LIKE ? OR lyric LIKE ? ORDER BY title LIMIT ?'
        );
        $st->bindValue(1, $like);
        $st->bindValue(2, $like);
        $st->bindValue(3, $limit, PDO::PARAM_INT);
        $st->execute();
        return array_map(fn ($r) => self::decodeSong($r), $st->fetchAll());
    }

    /** Inserts a song; on slug collision appends -2, -3, ... */
    public static function insertSong(string $title, string $lyric, string $chord, string $source = 'manual', ?string $slug = null): array
    {
        $base = $slug ?? self::slugify($title);
        $candidate = $base;
        for ($i = 2; ; $i++) {
            try {
                $st = self::conn()->prepare(
                    'INSERT INTO songs (title, slug, lyric, chord, metadata, source) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $st->execute([$title, $candidate, $lyric, $chord ?: null, json_encode(['source' => $source]), $source]);
                return ['id' => (int) self::conn()->lastInsertId(), 'title' => $title, 'slug' => $candidate];
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $candidate = $base . '-' . $i;
                    continue;
                }
                throw $e;
            }
        }
    }

    private static function decodeSong(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['metadata'] = json_decode((string) $row['metadata'], true) ?: [];
        return $row;
    }

    // ---- bible ----

    public static function getChapter(string $book, int $chapter, string $version = 'tb'): array
    {
        $st = self::conn()->prepare(
            'SELECT book, chapter, verse, text FROM bible_verses WHERE book = ? AND chapter = ? AND version = ? ORDER BY verse'
        );
        $st->execute([$book, $chapter, $version]);
        return $st->fetchAll();
    }

    public static function chapterCached(string $book, int $chapter, string $version = 'tb'): bool
    {
        $st = self::conn()->prepare('SELECT COUNT(*) FROM bible_verses WHERE book = ? AND chapter = ? AND version = ?');
        $st->execute([$book, $chapter, $version]);
        return (int) $st->fetchColumn() > 0;
    }

    public static function saveChapter(string $book, int $chapter, array $verses, string $version = 'tb'): void
    {
        $pdo = self::conn();
        $st = $pdo->prepare(
            'INSERT INTO bible_verses (book, chapter, verse, text, version) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE text = VALUES(text)'
        );
        foreach ($verses as $v) {
            $st->execute([$book, $chapter, (int) $v['verse'], (string) $v['text'], $version]);
        }
    }

    // ---- state (projection state shared with OBS feed) ----

    public static function getState(): array
    {
        $st = self::conn()->query('SELECT * FROM state WHERE id = 1');
        $row = $st->fetch();
        if (!$row) {
            return ['type' => 'idle', 'title' => '', 'ref' => '', 'slide' => 0, 'slides' => []];
        }
        $row['slides'] = json_decode((string) $row['slides'], true) ?: [];
        $row['slide'] = (int) $row['slide'];
        return $row;
    }

    /** Sets a brand-new presentation (slide resets to 0). */
    public static function setState(string $type, string $title, string $ref, array $slides): void
    {
        $st = self::conn()->prepare(
            'INSERT INTO state (id, type, title, ref, slide, slides) VALUES (1, ?, ?, ?, 0, ?)
             ON DUPLICATE KEY UPDATE type = VALUES(type), title = VALUES(title), ref = VALUES(ref), slide = VALUES(slide), slides = VALUES(slides)'
        );
        $st->execute([$type, $title, $ref, json_encode($slides, JSON_UNESCAPED_UNICODE)]);
    }

    /** Navigates the current presentation (clamped to valid range). */
    public static function setSlide(int $n): void
    {
        $state = self::getState();
        $max = count($state['slides']) - 1;
        $n = max(0, min($n, max(0, $max)));
        $st = self::conn()->prepare('UPDATE state SET slide = ? WHERE id = 1');
        $st->execute([$n]);
    }

    private static function slugify(string $title): string
    {
        $s = strtolower(trim($title));
        $s = preg_replace('/[^a-z0-9\s\-_]/', '', $s);
        $s = preg_replace('/[\s\-_]+/', '-', $s);
        return trim($s, '-') ?: 'lagu';
    }
}
