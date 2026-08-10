package slides

import (
	"math"
	"strings"
)

// Slide is one projection slide.
type Slide struct {
	Type   string   `json:"type"`   // "lyric" | "pause"
	Lines  []string `json:"lines"`  // lyric lines (max 2)
	Number int      `json:"number"` // verse number for bible slides
}

// SplitLyrics splits raw lyric text into slides: max 2 lines per lyric slide,
// "pause" slides between sections, never a pause at the start or end.
// Ported from the PHP app's UnlimitedWorshipScraper::splitLyrics.
func SplitLyrics(text string) []Slide {
	text = strings.ReplaceAll(text, "\r\n", "\n")
	text = strings.ReplaceAll(text, "\r", "\n")
	lines := strings.Split(text, "\n")

	var slides []Slide
	var buffer []string
	lastWasPause := false

	for _, raw := range lines {
		line := strings.TrimSpace(raw)
		if line == "" {
			if len(buffer) > 0 {
				slides = append(slides, Slide{Type: "lyric", Lines: buffer})
				buffer = nil
			}
			// never a pause before any lyric slide (leading blank lines) and
			// consecutive blank lines merge into one pause
			if len(slides) > 0 && !lastWasPause {
				slides = append(slides, Slide{Type: "pause"})
				lastWasPause = true
			}
		} else {
			buffer = append(buffer, line)
			lastWasPause = false
			if len(buffer) == 2 {
				slides = append(slides, Slide{Type: "lyric", Lines: buffer})
				buffer = nil
			}
		}
	}
	if len(buffer) > 0 {
		slides = append(slides, Slide{Type: "lyric", Lines: buffer})
	}
	// strip trailing pause
	if n := len(slides); n > 0 && slides[n-1].Type == "pause" {
		slides = slides[:n-1]
	}
	return slides
}

// BibleSlides builds one slide per verse, numbered.
func BibleSlides(verses []Verse) []Slide {
	out := make([]Slide, 0, len(verses))
	for _, v := range verses {
		out = append(out, Slide{
			Type:   "lyric",
			Lines:  []string{v.Text},
			Number: v.Number,
		})
	}
	return out
}

// Verse is a minimal bible verse used to build slides.
type Verse struct {
	Number int    `json:"number"`
	Text   string `json:"text"`
}

// FitFontSize returns the largest font size (px) so that `lines` lines of text
// (each with the given line-height factor) fit within availHeight.
// Bounded by minSize and maxSize.
func FitFontSize(availHeight float64, lineHeight float64, lines int, minSize, maxSize float64) float64 {
	if lines <= 0 {
		return maxSize
	}
	if maxSize <= minSize {
		return maxSize
	}
	// size such that lines*size*lineHeight <= availHeight
	size := availHeight / (float64(lines) * lineHeight)
	if size > maxSize {
		size = maxSize
	}
	if size < minSize {
		size = minSize
	}
	return math.Floor(size)
}
