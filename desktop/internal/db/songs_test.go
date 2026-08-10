package db

import (
	"database/sql"
	"errors"
	"io"
	"os"
	"path/filepath"
	"strings"
	"testing"
)

func TestInsertSong(t *testing.T) {
	d, _ := newTestDB(t)
	id, err := d.InsertSong(sampleSong("Kasih Mu", "kasih-mu"))
	if err != nil {
		t.Fatalf("insert: %v", err)
	}
	got, err := d.GetSong(id)
	if err != nil {
		t.Fatalf("get: %v", err)
	}
	if got.Title != "Kasih Mu" {
		t.Errorf("title = %q, want %q", got.Title, "Kasih Mu")
	}
	if got.Metadata["artis"] != "Test Band" {
		t.Errorf("metadata.artis = %q, want %q", got.Metadata["artis"], "Test Band")
	}
}

func TestInsertDuplicateSlug(t *testing.T) {
	d, _ := newTestDB(t)
	if _, err := d.InsertSong(sampleSong("Satu", "dua")); err != nil {
		t.Fatalf("first insert: %v", err)
	}
	if _, err := d.InsertSong(sampleSong("Satu Lagi", "dua")); err == nil {
		t.Fatal("expected error for duplicate slug")
	}
}

func TestUpdateSong(t *testing.T) {
	d, _ := newTestDB(t)
	id, _ := d.InsertSong(sampleSong("Lama", "lagu-lama"))
	s := sampleSong("Baru", "lagu-baru")
	s.ID = id
	s.Lyric = "Lirik baru"
	if err := d.UpdateSong(s); err != nil {
		t.Fatalf("update: %v", err)
	}
	got, _ := d.GetSong(id)
	if got.Title != "Baru" {
		t.Errorf("title = %q, want %q", got.Title, "Baru")
	}
	if got.Lyric != "Lirik baru" {
		t.Errorf("lyric = %q, want %q", got.Lyric, "Lirik baru")
	}
}

func TestUpdateSongMissing(t *testing.T) {
	d, _ := newTestDB(t)
	s := sampleSong("X", "x")
	s.ID = 9999
	if err := d.UpdateSong(s); !errors.Is(err, sql.ErrNoRows) {
		t.Errorf("expected ErrNoRows, got %v", err)
	}
}

func TestDeleteSong(t *testing.T) {
	d, _ := newTestDB(t)
	id, _ := d.InsertSong(sampleSong("Hapus", "hapus"))
	if err := d.DeleteSong(id); err != nil {
		t.Fatalf("delete: %v", err)
	}
	if _, err := d.GetSong(id); !errors.Is(err, ErrNotFound) {
		t.Errorf("expected ErrNotFound after delete, got %v", err)
	}
	if err := d.DeleteSong(id); !errors.Is(err, sql.ErrNoRows) {
		t.Errorf("expected ErrNoRows deleting twice, got %v", err)
	}
}

func TestSearchLike(t *testing.T) {
	d, _ := newTestDB(t)
	// lowercase query "kasih" must match title "KasihMu" (case-insensitive)
	if _, err := d.InsertSong(sampleSong("KasihMu", "kasihmu")); err != nil {
		t.Fatal(err)
	}
	// and a lyric match: title "Lagu Lain" contains "kasihMu" in lyric
	if _, err := d.InsertSong(sampleSong("Lagu Lain", "lagu-lain")); err != nil {
		t.Fatal(err)
	}
	// unrelated song must NOT match
	gunung := sampleSong("Gunung Batu", "gunung-batu")
	gunung.Lyric = "Batu karang yang teguh\nDi tengah lautan"
	if _, err := d.InsertSong(gunung); err != nil {
		t.Fatal(err)
	}

	res, err := d.SearchSongs("kasih", 50)
	if err != nil {
		t.Fatalf("search: %v", err)
	}
	if len(res) != 2 {
		t.Fatalf("got %d results, want 2: %+v", len(res), res)
	}
	for _, s := range res {
		if !strings.Contains(strings.ToLower(s.Title), "kasih") &&
			!strings.Contains(strings.ToLower(s.Lyric), "kasih") {
			t.Errorf("result %q does not contain query", s.Title)
		}
	}
}

func TestSearchNoResult(t *testing.T) {
	d, _ := newTestDB(t)
	if _, err := d.InsertSong(sampleSong("KasihMu", "kasihmu")); err != nil {
		t.Fatal(err)
	}
	res, err := d.SearchSongs("zygote", 50)
	if err != nil {
		t.Fatalf("search: %v", err)
	}
	if len(res) != 0 {
		t.Fatalf("got %d results, want 0", len(res))
	}
}

func TestSearchLimit(t *testing.T) {
	d, _ := newTestDB(t)
	for i := 0; i < 5; i++ {
		slug := strings.Repeat("s", i+1) + "-lagu"
		if _, err := d.InsertSong(sampleSong("Lagu Sama", slug)); err != nil {
			t.Fatal(err)
		}
	}
	res, _ := d.SearchSongs("Lagu Sama", 3)
	if len(res) != 3 {
		t.Errorf("limit: got %d results, want 3", len(res))
	}
}

// TestCopyDB verifies the DB file can be copied to a new location and still
// serve data (backup / moving between computers).
func TestCopyDB(t *testing.T) {
	d, path := newTestDB(t)
	if _, err := d.InsertSong(sampleSong("Lagu Backup", "lagu-backup")); err != nil {
		t.Fatal(err)
	}
	d.Close()

	// simulate backup: copy the .db file (+ its WAL/SHM if present)
	dst := filepath.Join(t.TempDir(), "backup.db")
	// after a clean Close the WAL is checkpointed into the main file;
	// remove any leftover -wal/-shm so the copy is standalone
	for _, suffix := range []string{"-wal", "-shm"} {
		os.Remove(path + suffix)
	}
	if err := copyFile(path, dst); err != nil {
		t.Fatalf("copy db: %v", err)
	}

	d2, err := Open(dst)
	if err != nil {
		t.Fatalf("open copied db: %v", err)
	}
	defer d2.Close()
	got, err := d2.GetSongBySlug("lagu-backup")
	if err != nil {
		t.Fatalf("read from copied db: %v", err)
	}
	if got.Title != "Lagu Backup" {
		t.Errorf("copied db title = %q", got.Title)
	}
}

func copyFile(src, dst string) error {
	in, err := os.Open(src)
	if err != nil {
		return err
	}
	defer in.Close()
	out, err := os.Create(dst)
	if err != nil {
		return err
	}
	defer out.Close()
	_, err = io.Copy(out, in)
	return err
}
