package db

import (
	"database/sql"
	"errors"
	"fmt"
)

// Song is a single worship song stored locally.
type Song struct {
	ID       int64             `json:"id"`
	Title    string            `json:"title"`
	Slug     string            `json:"slug"`
	Lyric    string            `json:"lyric"`
	Chord    string            `json:"chord"`
	Metadata map[string]string `json:"metadata"`
}

// InsertSong adds a song. Metadata is stored as JSON.
func (d *DB) InsertSong(s Song) (int64, error) {
	meta, err := jsonMarshal(s.Metadata)
	if err != nil {
		return 0, err
	}
	res, err := d.conn.Exec(
		"INSERT INTO songs (title, slug, lyric, chord, metadata) VALUES (?, ?, ?, ?, ?)",
		s.Title, s.Slug, s.Lyric, s.Chord, meta,
	)
	if err != nil {
		return 0, fmt.Errorf("insert song: %w", err)
	}
	return res.LastInsertId()
}

// GetSong returns a song by id.
func (d *DB) GetSong(id int64) (*Song, error) {
	row := d.conn.QueryRow(
		"SELECT id, title, slug, lyric, chord, metadata FROM songs WHERE id = ?", id)
	return scanSong(row)
}

// GetSongBySlug returns a song by its unique slug.
func (d *DB) GetSongBySlug(slug string) (*Song, error) {
	row := d.conn.QueryRow(
		"SELECT id, title, slug, lyric, chord, metadata FROM songs WHERE slug = ?", slug)
	return scanSong(row)
}

// UpdateSong updates lyric/chord/metadata for an existing song.
func (d *DB) UpdateSong(s Song) error {
	meta, err := jsonMarshal(s.Metadata)
	if err != nil {
		return err
	}
	res, err := d.conn.Exec(
		"UPDATE songs SET title = ?, lyric = ?, chord = ?, metadata = ? WHERE id = ?",
		s.Title, s.Lyric, s.Chord, meta, s.ID,
	)
	if err != nil {
		return fmt.Errorf("update song: %w", err)
	}
	n, _ := res.RowsAffected()
	if n == 0 {
		return sql.ErrNoRows
	}
	return nil
}

// DeleteSong removes a song by id.
func (d *DB) DeleteSong(id int64) error {
	res, err := d.conn.Exec("DELETE FROM songs WHERE id = ?", id)
	if err != nil {
		return fmt.Errorf("delete song: %w", err)
	}
	n, _ := res.RowsAffected()
	if n == 0 {
		return sql.ErrNoRows
	}
	return nil
}

// SearchSongs finds songs whose title or lyric contains the query
// (case-insensitive, prefix-wildcard).
func (d *DB) SearchSongs(query string, limit int) ([]Song, error) {
	if limit <= 0 {
		limit = 50
	}
	like := "%" + query + "%"
	rows, err := d.conn.Query(
		"SELECT id, title, slug, lyric, chord, metadata FROM songs WHERE title LIKE ? OR lyric LIKE ? ORDER BY title LIMIT ?",
		like, like, limit,
	)
	if err != nil {
		return nil, fmt.Errorf("search songs: %w", err)
	}
	defer rows.Close()

	var out []Song
	for rows.Next() {
		var s Song
		var meta string
		if err := rows.Scan(&s.ID, &s.Title, &s.Slug, &s.Lyric, &s.Chord, &meta); err != nil {
			return nil, err
		}
		if err := jsonUnmarshal(meta, &s.Metadata); err != nil {
			return nil, err
		}
		out = append(out, s)
	}
	return out, rows.Err()
}

// CountSongs returns the total number of stored songs.
func (d *DB) CountSongs() (int, error) {
	var n int
	err := d.conn.QueryRow("SELECT COUNT(*) FROM songs").Scan(&n)
	return n, err
}

// ErrNotFound is returned when a song does not exist.
var ErrNotFound = errors.New("not found")

type rowScanner interface {
	Scan(dest ...any) error
}

func scanSong(row rowScanner) (*Song, error) {
	var s Song
	var meta string
	if err := row.Scan(&s.ID, &s.Title, &s.Slug, &s.Lyric, &s.Chord, &meta); err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrNotFound
		}
		return nil, err
	}
	if err := jsonUnmarshal(meta, &s.Metadata); err != nil {
		return nil, err
	}
	return &s, nil
}
