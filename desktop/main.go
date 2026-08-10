package main

import (
	"context"
	"embed"
	"log"
	"os"
	"path/filepath"
	"strconv"
	"strings"

	"github.com/wailsapp/wails/v3/pkg/application"

	"easilyrics/internal/bible"
	"easilyrics/internal/db"
	"easilyrics/internal/slides"
)

//go:embed all:frontend/dist
var assets embed.FS

// Registered events (ported from the web app's BroadcastChannel protocol):
//   presenter:goTo    operator -> projector  (navigate to slide index)
//   projector:synced  projector -> operator  (confirm current slide index)
func init() {
	application.RegisterEvent[int]("presenter:goTo")
	application.RegisterEvent[int]("projector:synced")
}

// App is the main application struct. Its exported methods are exposed to the
// frontend via Wails bindings.
type App struct {
	app       *application.App
	projector *application.WebviewWindow
	db        *db.DB
}

func main() {
	a := &App{}

	app := application.New(application.Options{
		Name:        "EasyLyrics",
		Description: "Lirik & Alkitab presentation",
		Services: []application.Service{
			application.NewService(a),
		},
		Assets: application.AssetOptions{
			Handler: application.AssetFileServerFS(assets),
		},
		Mac: application.MacOptions{
			ApplicationShouldTerminateAfterLastWindowClosed: true,
		},
	})
	a.app = app

	// Operator window (main display, presenter-facing)
	app.Window.NewWithOptions(application.WebviewWindowOptions{
		Title:            "EasyLyrics — Operator",
		Width:            1100,
		Height:           700,
		BackgroundColour: application.NewRGB(17, 17, 17),
		URL:              "/",
	})

	// Projector window (audience-facing, can be moved to a second display)
	a.projector = app.Window.NewWithOptions(application.WebviewWindowOptions{
		Title:            "EasyLyrics — Proyektor",
		Width:            1280,
		Height:           720,
		BackgroundColour: application.NewRGB(0, 0, 0),
		URL:              "/projector.html",
	})

	if err := app.Run(); err != nil {
		log.Fatal(err)
	}
}

// dbPath returns the SQLite file location in the user's app data directory.
func dbPath() string {
	dir, err := os.UserConfigDir()
	if err != nil {
		dir = "."
	}
	return filepath.Join(dir, "EasyLyrics", "easilyrics.db")
}

// ServiceStartup is called when the application boots; it opens the local database.
func (a *App) ServiceStartup(ctx context.Context, options application.ServiceOptions) error {
	d, err := db.Open(dbPath())
	if err != nil {
		log.Printf("WARN: cannot open local db: %v", err)
		return nil
	}
	a.db = d
	log.Printf("DB ready at %s", dbPath())
	return nil
}

// AddSong inserts a song into the local database (used by hybrid search in Fase 4).
func (a *App) AddSong(s db.Song) (int64, error) {
	if a.db == nil {
		return 0, db.ErrNotFound
	}
	return a.db.InsertSong(s)
}

// AddSongManual adds a song typed/pasted by the user. A slug is generated
// from the title (uniqueness ensured by appending a counter if needed).
func (a *App) AddSongManual(title string, lyric string, chord string) (int64, error) {
	if a.db == nil {
		return 0, db.ErrNotFound
	}
	slug := slugify(title)
	s := db.Song{Title: title, Slug: slug, Lyric: lyric, Chord: chord, Metadata: map[string]string{}}
	id, err := a.db.InsertSong(s)
	if err != nil {
		// slug collision (same title twice): append -2, -3, ...
		for i := 2; ; i++ {
			s.Slug = slugify(title) + "-" + itoa(i)
			id, err = a.db.InsertSong(s)
			if err == nil {
				break
			}
		}
	}
	return id, nil
}

func slugify(title string) string {
	var b strings.Builder
	lower := strings.ToLower(strings.TrimSpace(title))
	for _, r := range lower {
		switch {
		case r >= 'a' && r <= 'z', r >= '0' && r <= '9':
			b.WriteRune(r)
		case r == ' ', r == '-', r == '_':
			b.WriteByte('-')
		}
	}
	if b.Len() == 0 {
		return "lagu"
	}
	return b.String()
}

func itoa(n int) string {
	return strconv.Itoa(n)
}

// SearchSongs queries the local library (title/lyric).
func (a *App) SearchSongs(query string, limit int) ([]db.Song, error) {
	if a.db == nil {
		return nil, nil
	}
	return a.db.SearchSongs(query, limit)
}

// ListSongs returns the song library (title/lyric match when query != "").
func (a *App) ListSongs(query string, limit int) []db.Song {
	if a.db == nil {
		return nil
	}
	songs, err := a.db.SearchSongs(query, limit)
	if err != nil {
		return nil
	}
	return songs
}

// GetSongSlides loads a song from the local DB and splits it into lyric slides.
func (a *App) GetSongSlides(id int64) []slides.Slide {
	if a.db == nil {
		return nil
	}
	song, err := a.db.GetSong(id)
	if err != nil {
		return nil
	}
	return slides.SplitLyrics(song.Lyric)
}

// GetBibleSlides fetches a chapter from the beeble API (live, TB) and builds
// one slide per verse. Caching into the local DB lands in Fase 4.
func (a *App) GetBibleSlides(book string, chapter int, version string) []slides.Slide {
	if version == "" {
		version = "tb"
	}
	c := bible.NewClient("")
	verses, err := c.GetChapter(book, chapter, version)
	if err != nil {
		return nil
	}
	return slides.BibleSlides(verses)
}

// Counts returns the number of stored songs and bible verses.
func (a *App) Counts() map[string]int {
	if a.db == nil {
		return map[string]int{"songs": 0, "verses": 0}
	}
	songs, _ := a.db.CountSongs()
	verses, _ := a.db.CountVerses()
	return map[string]int{"songs": songs, "verses": verses}
}

// FocusProjector brings the projector window to the front.
func (a *App) FocusProjector() {
	if a.projector != nil {
		a.projector.Show()
		a.projector.Focus()
	}
}

// ToggleProjectorFullscreen toggles the projector window fullscreen.
func (a *App) ToggleProjectorFullscreen() bool {
	if a.projector == nil {
		return false
	}
	if a.projector.IsFullscreen() {
		a.projector.UnFullscreen()
		return false
	}
	a.projector.Fullscreen()
	return true
}

// IsProjectorFullscreen reports whether the projector window is fullscreen.
func (a *App) IsProjectorFullscreen() bool {
	return a.projector != nil && a.projector.IsFullscreen()
}
