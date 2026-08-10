package db

import (
	"path/filepath"
	"testing"
)

// newTestDB creates a temp-file DB for a test and returns a cleanup func.
func newTestDB(t *testing.T) (*DB, string) {
	t.Helper()
	dir := t.TempDir()
	path := filepath.Join(dir, "test.db")
	d, err := Open(path)
	if err != nil {
		t.Fatalf("open test db: %v", err)
	}
	t.Cleanup(func() { d.Close() })
	return d, path
}

// sampleSong returns a song with the given title/slug for tests.
func sampleSong(title, slug string) Song {
	return Song{
		Title:    title,
		Slug:     slug,
		Lyric:    "Kasih yang sempurna\nMenghapus ketakutanku\n\nKasihMu padaku",
		Chord:    "[Verse]\nC G Am",
		Metadata: map[string]string{"artis": "Test Band", "tempo": "72"},
	}
}
