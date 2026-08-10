package db

import (
	"database/sql"
	"fmt"
	"os"
	"path/filepath"

	_ "modernc.org/sqlite"
)

// DB wraps the SQLite connection.
type DB struct {
	conn *sql.DB
}

// Open opens (and creates if needed) the SQLite database at path, then runs
// the schema migration.
func Open(path string) (*DB, error) {
	if dir := filepath.Dir(path); dir != "" && dir != "." {
		if err := os.MkdirAll(dir, 0o755); err != nil {
			return nil, fmt.Errorf("create db dir: %w", err)
		}
	}
	conn, err := sql.Open("sqlite", path)
	if err != nil {
		return nil, fmt.Errorf("open sqlite: %w", err)
	}
	// WAL makes copy-as-backup safe and reads faster.
	if _, err := conn.Exec("PRAGMA journal_mode=WAL"); err != nil {
		conn.Close()
		return nil, fmt.Errorf("set journal mode: %w", err)
	}
	d := &DB{conn: conn}
	if err := d.migrate(); err != nil {
		conn.Close()
		return nil, err
	}
	return d, nil
}

// Close closes the underlying connection.
func (d *DB) Close() error {
	return d.conn.Close()
}

// Conn exposes the raw connection (for advanced queries/tests).
func (d *DB) Conn() *sql.DB {
	return d.conn
}

func (d *DB) migrate() error {
	const schema = `
CREATE TABLE IF NOT EXISTS songs (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    title      TEXT NOT NULL,
    slug       TEXT NOT NULL UNIQUE,
    lyric      TEXT NOT NULL DEFAULT '',
    chord      TEXT NOT NULL DEFAULT '',
    metadata   TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS bible_verses (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    book       TEXT NOT NULL,
    chapter    INTEGER NOT NULL,
    verse      INTEGER NOT NULL,
    text       TEXT NOT NULL,
    version    TEXT NOT NULL DEFAULT 'tb',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (book, chapter, verse, version)
);

CREATE INDEX IF NOT EXISTS idx_songs_title ON songs (title);
CREATE INDEX IF NOT EXISTS idx_bible_ref ON bible_verses (book, chapter);
`
	if _, err := d.conn.Exec(schema); err != nil {
		return fmt.Errorf("migrate: %w", err)
	}
	return nil
}
