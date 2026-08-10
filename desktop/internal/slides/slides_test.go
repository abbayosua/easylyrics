package slides

import (
	"strings"
	"testing"
)

func TestSplitLyricsBasic(t *testing.T) {
	text := "Line one\nLine two\n\nLine three\n\nLine four\nLine five\nLine six"
	got := SplitLyrics(text)

	// expect: lyric[1,2], pause, lyric[3], pause, lyric[4,5], lyric[6]
	wantTypes := []string{"lyric", "pause", "lyric", "pause", "lyric", "lyric"}
	if len(got) != len(wantTypes) {
		t.Fatalf("got %d slides, want %d: %+v", len(got), len(wantTypes), got)
	}
	for i, w := range wantTypes {
		if got[i].Type != w {
			t.Errorf("slide %d type = %q, want %q", i, got[i].Type, w)
		}
	}
}

func TestSplitLyricsMaxTwoLines(t *testing.T) {
	got := SplitLyrics("a\nb\nc\nd\ne")
	for _, s := range got {
		if s.Type == "lyric" && len(s.Lines) > 2 {
			t.Errorf("slide has %d lines, max is 2: %+v", len(s.Lines), s.Lines)
		}
	}
}

func TestSplitLyricsNoPauseAtStartOrEnd(t *testing.T) {
	got := SplitLyrics("\n\na\nb\n\n")
	if got[0].Type == "pause" {
		t.Error("first slide must not be pause")
	}
	last := got[len(got)-1]
	if last.Type == "pause" {
		t.Error("last slide must not be pause")
	}
}

func TestSplitLyricsEmpty(t *testing.T) {
	got := SplitLyrics("")
	if len(got) != 0 {
		t.Errorf("empty text -> %d slides, want 0", len(got))
	}
	got = SplitLyrics("\n\n\n")
	if len(got) != 0 {
		t.Errorf("whitespace-only -> %d slides, want 0", len(got))
	}
}

func TestSplitLyricsPauseMerged(t *testing.T) {
	// consecutive blank lines produce exactly ONE pause
	got := SplitLyrics("a\n\n\n\n\nb")
	pauses := 0
	for _, s := range got {
		if s.Type == "pause" {
			pauses++
		}
	}
	if pauses != 1 {
		t.Errorf("got %d pauses, want 1", pauses)
	}
}

func TestSplitLyricsLastSlideLyric(t *testing.T) {
	got := SplitLyrics("a\nb\n\nc\nd\n\n")
	if got[len(got)-1].Type != "lyric" {
		t.Errorf("final slide must be lyric, got %q", got[len(got)-1].Type)
	}
}

func TestSplitLyricsCRLF(t *testing.T) {
	got := SplitLyrics("a\r\nb\r\n\r\nc")
	if len(got) != 3 {
		t.Fatalf("CRLF text -> %d slides, want 3", len(got))
	}
	if got[0].Type != "lyric" || got[1].Type != "pause" || got[2].Type != "lyric" {
		t.Errorf("unexpected types: %+v", got)
	}
}

func TestBibleSlides(t *testing.T) {
	vs := []Verse{{Number: 1, Text: "A"}, {Number: 2, Text: "B"}}
	got := BibleSlides(vs)
	if len(got) != 2 {
		t.Fatalf("got %d slides, want 2", len(got))
	}
	if got[0].Number != 1 || got[0].Lines[0] != "A" {
		t.Errorf("slide 0 wrong: %+v", got[0])
	}
	if got[0].Type != "lyric" {
		t.Errorf("bible slide type = %q, want lyric", got[0].Type)
	}
}

func TestFitFontSize(t *testing.T) {
	// avail 800px, lineHeight 1.4, 5 lines -> each line <= 160px -> size ~114
	got := FitFontSize(800, 1.4, 5, 12, 64)
	if got != 64 {
		t.Errorf("short text should stay at maxSize, got %v", got)
	}
	// very long text must shrink below max
	got = FitFontSize(800, 1.4, 30, 12, 64)
	if got >= 64 {
		t.Errorf("long text must shrink below 64, got %v", got)
	}
	if got < 12 {
		t.Errorf("must not go below minSize, got %v", got)
	}
	// exact bound: 64px * 1.4 * lines == avail (800 / 89.6 = 8.93 -> 8 lines)
	avail := 800.0
	lines := int(avail / (64 * 1.4))
	got = FitFontSize(avail, 1.4, lines, 12, 64)
	if got != 64 {
		t.Errorf("exact fit should be 64, got %v", got)
	}
}

func TestFitFontSizeBounds(t *testing.T) {
	// tiny space forces min
	got := FitFontSize(20, 1.4, 50, 12, 64)
	if got != 12 {
		t.Errorf("tiny space -> minSize 12, got %v", got)
	}
	// zero lines -> max
	if got := FitFontSize(100, 1.4, 0, 12, 64); got != 64 {
		t.Errorf("zero lines -> maxSize, got %v", got)
	}
	// inverted bounds -> max
	if got := FitFontSize(100, 1.4, 5, 64, 12); got != 12 {
		t.Errorf("inverted bounds -> 12, got %v", got)
	}
}

func TestSplitLyricsStringContent(t *testing.T) {
	got := SplitLyrics("Satu\nDua")
	if !strings.Contains(got[0].Lines[0], "Satu") {
		t.Errorf("line trimmed content wrong: %+v", got[0])
	}
}
