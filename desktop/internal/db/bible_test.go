package db

import (
	"errors"
	"testing"
)

func TestBibleVersesCRUD(t *testing.T) {
	d, _ := newTestDB(t)

	// upsert inserts
	v := BibleVerse{Book: "Yoh", Chapter: 3, Verse: 16, Text: "Karena begitu besar kasih Allah", Version: "tb"}
	if err := d.UpsertBibleVerse(v); err != nil {
		t.Fatalf("upsert insert: %v", err)
	}

	// upsert updates same key
	v.Text = "Karena begitu besar kasih Allah akan dunia ini"
	if err := d.UpsertBibleVerse(v); err != nil {
		t.Fatalf("upsert update: %v", err)
	}

	// fetch chapter
	ch, err := d.GetChapter("Yoh", 3, "tb")
	if err != nil {
		t.Fatalf("get chapter: %v", err)
	}
	if len(ch) != 1 {
		t.Fatalf("got %d verses, want 1", len(ch))
	}
	if ch[0].Text != v.Text {
		t.Errorf("text = %q, want %q", ch[0].Text, v.Text)
	}

	// duplicate key did not create a second row
	n, _ := d.CountVerses()
	if n != 1 {
		t.Errorf("count = %d, want 1 (upsert must not duplicate)", n)
	}
}

func TestBibleVersesMultipleBooks(t *testing.T) {
	d, _ := newTestDB(t)
	verses := []BibleVerse{
		{Book: "Kej", Chapter: 1, Verse: 1, Text: "Pada mulanya", Version: "tb"},
		{Book: "Kej", Chapter: 1, Verse: 2, Text: "Bumi belum berbentuk", Version: "tb"},
		{Book: "Kej", Chapter: 2, Verse: 1, Text: "Demikianlah langit", Version: "tb"},
		{Book: "Maz", Chapter: 23, Verse: 1, Text: "Tuhan adalah gembalaku", Version: "tb"},
	}
	for _, v := range verses {
		if err := d.UpsertBibleVerse(v); err != nil {
			t.Fatal(err)
		}
	}

	ch, _ := d.GetChapter("Kej", 1, "tb")
	if len(ch) != 2 {
		t.Errorf("Kej 1 has %d verses, want 2", len(ch))
	}
	if ch[0].Verse != 1 || ch[1].Verse != 2 {
		t.Errorf("verse order wrong: %+v", ch)
	}

	exists, _ := d.ChapterExists("Maz", 23, "tb")
	if !exists {
		t.Error("Maz 23 should exist")
	}
	exists, _ = d.ChapterExists("Maz", 24, "tb")
	if exists {
		t.Error("Maz 24 should not exist")
	}
}

func TestGetChapterSafeMissing(t *testing.T) {
	d, _ := newTestDB(t)
	if _, err := d.GetChapterSafe("Rom", 8, "tb"); !errors.Is(err, ErrNoVerses) {
		t.Errorf("expected ErrNoVerses, got %v", err)
	}
}

func TestBibleDifferentVersions(t *testing.T) {
	d, _ := newTestDB(t)
	tb := BibleVerse{Book: "Yoh", Chapter: 1, Verse: 1, Text: "Pada mulanya adalah Firman", Version: "tb"}
	bis := BibleVerse{Book: "Yoh", Chapter: 1, Verse: 1, Text: "Sebelum dunia diciptakan, Firman sudah ada", Version: "bis"}
	if err := d.UpsertBibleVerse(tb); err != nil {
		t.Fatal(err)
	}
	if err := d.UpsertBibleVerse(bis); err != nil {
		t.Fatal(err)
	}
	// same ref, different version => 2 rows
	n, _ := d.CountVerses()
	if n != 2 {
		t.Errorf("count = %d, want 2 (different versions)", n)
	}
	ch, _ := d.GetChapter("Yoh", 1, "bis")
	if len(ch) != 1 || ch[0].Text != bis.Text {
		t.Errorf("bis text wrong: %+v", ch)
	}
}
