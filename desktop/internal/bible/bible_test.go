package bible

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"easilyrics/internal/slides"
)

// mockServer returns an httptest server that serves a beeble-like chapter.
func mockServer(t *testing.T) *httptest.Server {
	t.Helper()
	mux := http.NewServeMux()
	mux.HandleFunc("/api/v1/passage/", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]any{
			"data": map[string]any{
				"book": map[string]any{"name": "Yohanes"},
				"verses": []map[string]any{
					{"verse": 0, "type": "title", "content": "Judul"},
					{"verse": 1, "type": "content", "content": "Karena begitu besar kasih Allah"},
					{"verse": 2, "type": "content", "content": "sehingga Ia mengaruniakan Anak-Nya"},
				},
			},
		})
	})
	return httptest.NewServer(mux)
}

func TestGetChapter(t *testing.T) {
	srv := mockServer(t)
	defer srv.Close()

	c := NewClient(srv.URL)
	got, err := c.GetChapter("Yoh", 3, "tb")
	if err != nil {
		t.Fatalf("GetChapter: %v", err)
	}
	want := []slides.Verse{
		{Number: 1, Text: "Karena begitu besar kasih Allah"},
		{Number: 2, Text: "sehingga Ia mengaruniakan Anak-Nya"},
	}
	if len(got) != len(want) {
		t.Fatalf("got %d verses, want %d: %+v", len(got), len(want), got)
	}
	// title verse (type=title) must be skipped
	for i, w := range want {
		if got[i].Number != w.Number || got[i].Text != w.Text {
			t.Errorf("verse %d = %+v, want %+v", i, got[i], w)
		}
	}
}

func TestGetChapterServerError(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		http.Error(w, "boom", http.StatusInternalServerError)
	}))
	defer srv.Close()

	c := NewClient(srv.URL)
	if _, err := c.GetChapter("Yoh", 3, "tb"); err == nil {
		t.Fatal("expected error for 500 response")
	}
}

func TestGetChapterNoContentVerses(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		json.NewEncoder(w).Encode(map[string]any{
			"data": map[string]any{
				"book":   map[string]any{"name": "Obaja"},
				"verses": []map[string]any{{"verse": 0, "type": "title", "content": "x"}},
			},
		})
	}))
	defer srv.Close()

	c := NewClient(srv.URL)
	if _, err := c.GetChapter("Oba", 1, "tb"); err == nil {
		t.Fatal("expected error when no content verses")
	}
}
