# EasyLyrics — Agent Guide

Lyric presentation app — search, view, and project song lyrics from unlimitedworship.org.

## Quick Start

```bash
php -S localhost:8000        # start dev server
open http://localhost:8000    # use it
php scraper.php "kasih"      # CLI search
php scraper.php 12345 detail song-slug  # CLI detail
```

## Code Organization

| File | Purpose |
|------|---------|
| `scraper.php` | `UnlimitedWorshipScraper` class + CLI entry point |
| `index.php` | Search + detail page (main UI) |
| `projector.php` | Full-screen slide display (audience-facing) |
| `presenter.php` | Controller with slide list, prev/next (operator-facing) |
| `biblepresentation.php` | Bible verse presenter with book/chapter selector, verse slides, BroadcastChannel sync |
| `bibleprojector.php` | Bible verse full-screen projector, BroadcastChannel listener |

No framework, no database, no build step. Zero external dependencies (uses built-in DOMDocument, cURL, and hand-written CSS/JS).

## Bible Feature

Two files (`biblepresentation.php`, `bibleprojector.php`) provide Bible verse presentation using the [beeble API](https://beeble.vercel.app) with **Alkitab Terjemahan Baru (TB)**.

**API**: `https://beeble.vercel.app/api/v1/passage/{abbr}/{chapter}?ver=tb`
- Book list is embedded as a PHP array (66 books, fixed data)
- Chapter content returns verses with `type: "content"` and `verse` number

**Flow**:
1. Open `biblepresentation.php` → select book + chapter → click "Muat"
2. Verses display as slides (one verse per slide)
3. Click "Proyektor" → opens `bibleprojector.php` in new window (1280x720 popup), current page stays in presenter mode
4. Both communicate via `BroadcastChannel("bible-{book}-{chapter}")` — same protocol as lyrics (`goTo`, `synced`, `ping`)

**Navigation**: Same as lyrics — keyboard arrows, click left/right on projector, button bar on presenter, slide list with click-to-jump.

## Architecture & Data Flow

```
index.php (search/detail)
  ├── detail view ──> opens projector.php?   (popup, audience)
  │                 ──> opens presenter.php?  (popup, operator)
  └── search view ──> user clicks song card

projector.php <──> presenter.php via BroadcastChannel("easylyrics-{id}")
    "goTo"    ──────►  presenter sends index
    "synced"  ◄──────  projector confirms index
    "ping"    ──────►  presenter checks health
```

1. User searches on `index.php` → `scraper.search()` scrapes search results
2. User clicks song → `scraper.detail()` fetches full lyric + chord + metadata
3. Detail page offers two popup links: **Present** (operator) and **Proyektor** (audience)
4. Presenter and projector communicate in-browser via `BroadcastChannel` — no server-side sync

## `UnlimitedWorshipScraper` Class (scraper.php)

### Public Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `search(string $keyword): array` | `[{id, slug, title, lyric_snippet, url}]` | Scrapes unlimitedworship.org/search |
| `detail(int $id, string $slug): array` | `{id, slug, title, url, lyric, chord, metadata}` | Scrapes song detail page |
| `splitLyrics(string $text): array` | `[{type: "lyric"|"pause", lines: string[]}]` | Splits raw lyric into 2-line slides |

### Private Helpers
- `fetch(string $url): string` — cURL GET with fake UA, SSL verification disabled
- `dom(string $html): DOMXPath` — wraps HTML in DOMDocument
- `innerText(DOMElement $node): string` — converts `<br>` to `\n`, strips tags

### CLI Entry Point
```php
php scraper.php <keyword>          # search
php scraper.php <id> detail <slug> # detail (slug optional, resolves via search)
```
Detected via `PHP_SAPI === 'cli'` at line 170.

## Slide Splitting Logic (`splitLyrics`)

- Splits on `\n`, treats blank lines as section breaks
- Groups consecutive non-empty lines into "lyric" slides (max 2 lines each)
- Inserts "pause" slides between sections (but never at start or end; trailing pause is stripped)
- Final slide type is always "lyric" (trailing pause removed)

## Cross-Tab Sync (BroadcastChannel)

Present and Proyektor communicate over `BrowserChannel`.

**Channel name**: `easylyrics-{songId}`

**Protocol**:
- `{ action: "goTo", index: n }` — Presenter → Projector: navigate to slide
- `{ action: "synced", index: n }` — Projector → Presenter: confirm current slide
- `{ action: "ping" }` — Presenter → Projector: liveness check

Projector responds to `ping` with `synced` containing its current index. Presenter shows a green dot when projector is connected, red when not.

## Navigation

Both projector and presenter support:
- **Keyboard**: Arrow keys (up/down/left/right)
- **Click/tap**: Projector uses left/right half of screen; presenter has button bar
- **Presenter slide list**: click any non-pause slide to jump
- **Touch swipe** on projector (40px threshold)

## Key CSS Patterns

- All styles are **inline** `<style>` blocks per file — no external CSS
- Presenter: dark theme (`#111` bg), `#a78bfa` purple accent, `#1e1b4b` active
- Projector: black bg, white Georgia serif, `clamp()` font sizing, fade-in animation
- Shared palette: `#6C3EB8` purple (index), `#a78bfa` (projector/presenter accent)
- Font: system sans-serif for UI, Georgia serif for lyrics, Courier New for chords

## Important Gotchas

- **SSL verification is disabled** (`CURLOPT_SSL_VERIFYPEER => false`) — don't change without reason
- **No error boundaries** — detail page throws `Exception` on cURL failure, caught and shown inline
- **No rate limiting** — each search/detail call makes live HTTP requests to unlimitedworship.org
- **XPath is fragile** — scraper depends on CSS class names from unlimitedworship.org's HTML; site changes will break it
- **PHP built-in server is single-threaded** — adequate for local use, not production
- **BroadcastChannel is same-origin** — presenter and projector must be on same domain/port
- **`htmlspecialchars()` is used for XSS protection** — always use it when rendering user or scraped text
- **No autoloader** — uses `require_once __DIR__ . '/scraper.php'` manually
- **`detail()` falls back to slug as title** if `now-playing-title` not found
