package db

import (
	"database/sql"
	"errors"
	"fmt"
)

// BibleVerse is a single verse stored locally.
type BibleVerse struct {
	ID      int64  `json:"id"`
	Book    string `json:"book"`
	Chapter int    `json:"chapter"`
	Verse   int    `json:"verse"`
	Text    string `json:"text"`
	Version string `json:"version"`
}

// UpsertBibleVerse inserts a verse, or updates the text if the same
// (book, chapter, verse, version) already exists.
func (d *DB) UpsertBibleVerse(v BibleVerse) error {
	_, err := d.conn.Exec(`
INSERT INTO bible_verses (book, chapter, verse, text, version) VALUES (?, ?, ?, ?, ?)
ON CONFLICT(book, chapter, verse, version) DO UPDATE SET text = excluded.text`,
		v.Book, v.Chapter, v.Verse, v.Text, v.Version)
	if err != nil {
		return fmt.Errorf("upsert verse: %w", err)
	}
	return nil
}

// GetChapter returns all verses of a chapter in verse order.
func (d *DB) GetChapter(book string, chapter int, version string) ([]BibleVerse, error) {
	rows, err := d.conn.Query(
		"SELECT id, book, chapter, verse, text, version FROM bible_verses WHERE book = ? AND chapter = ? AND version = ? ORDER BY verse",
		book, chapter, version)
	if err != nil {
		return nil, fmt.Errorf("get chapter: %w", err)
	}
	defer rows.Close()

	var out []BibleVerse
	for rows.Next() {
		var v BibleVerse
		if err := rows.Scan(&v.ID, &v.Book, &v.Chapter, &v.Verse, &v.Text, &v.Version); err != nil {
			return nil, err
		}
		out = append(out, v)
	}
	return out, rows.Err()
}

// ChapterExists reports whether a chapter is already cached locally.
func (d *DB) ChapterExists(book string, chapter int, version string) (bool, error) {
	var n int
	err := d.conn.QueryRow(
		"SELECT COUNT(*) FROM bible_verses WHERE book = ? AND chapter = ? AND version = ?",
		book, chapter, version).Scan(&n)
	if err != nil {
		return false, err
	}
	return n > 0, nil
}

// CountVerses returns the total number of stored verses.
func (d *DB) CountVerses() (int, error) {
	var n int
	err := d.conn.QueryRow("SELECT COUNT(*) FROM bible_verses").Scan(&n)
	return n, err
}

// ErrNoVerses is returned when a chapter is not cached.
var ErrNoVerses = errors.New("no verses")

// GetChapterSafe returns ErrNoVerses when the chapter is not cached.
func (d *DB) GetChapterSafe(book string, chapter int, version string) ([]BibleVerse, error) {
	out, err := d.GetChapter(book, chapter, version)
	if err != nil {
		return nil, err
	}
	if len(out) == 0 {
		return nil, ErrNoVerses
	}
	return out, nil
}

var _ = sql.ErrNoRows // keep database/sql import if helpers change
