package bible

import (
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"time"

	"easilyrics/internal/slides"
)

// Client fetches chapter content from the beeble API (Terjemahan Baru).
type Client struct {
	baseURL string
	http    *http.Client
}

// NewClient creates a beeble client. baseURL defaults to the public API.
func NewClient(baseURL string) *Client {
	if baseURL == "" {
		baseURL = "https://beeble.vercel.app"
	}
	return &Client{
		baseURL: baseURL,
		http:    &http.Client{Timeout: 15 * time.Second},
	}
}

// ChapterResponse mirrors the beeble JSON structure.
type ChapterResponse struct {
	Data struct {
		Book struct {
			Name string `json:"name"`
		} `json:"book"`
		Verses []struct {
			Verse   int    `json:"verse"`
			Type    string `json:"type"`
			Content string `json:"content"`
		} `json:"verses"`
	} `json:"data"`
}

// GetChapter fetches one chapter (default version "tb" = Terjemahan Baru)
// and returns one slide-verse per content verse.
func (c *Client) GetChapter(book string, chapter int, version string) ([]slides.Verse, error) {
	if version == "" {
		version = "tb"
	}
	u := fmt.Sprintf("%s/api/v1/passage/%s/%d?ver=%s",
		c.baseURL, url.PathEscape(book), chapter, url.QueryEscape(version))
	req, err := http.NewRequest(http.MethodGet, u, nil)
	if err != nil {
		return nil, err
	}
	req.Header.Set("User-Agent", "EasyLyrics/1.0")

	resp, err := c.http.Do(req)
	if err != nil {
		return nil, fmt.Errorf("fetch %s %d: %w", book, chapter, err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("beeble returned %s", resp.Status)
	}
	body, err := io.ReadAll(io.LimitReader(resp.Body, 10<<20))
	if err != nil {
		return nil, err
	}
	var out ChapterResponse
	if err := json.Unmarshal(body, &out); err != nil {
		return nil, fmt.Errorf("decode beeble response: %w", err)
	}
	verses := make([]slides.Verse, 0, len(out.Data.Verses))
	for _, v := range out.Data.Verses {
		if v.Type == "content" {
			verses = append(verses, slides.Verse{Number: v.Verse, Text: v.Content})
		}
	}
	if len(verses) == 0 {
		return nil, fmt.Errorf("no content verses for %s %d", book, chapter)
	}
	return verses, nil
}
