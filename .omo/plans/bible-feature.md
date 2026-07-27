# Plan: bible-feature

> Created: 2026-07-28 06:10:11
> **Status**: Draft

## Objective

Create Bible verse presenter and projector using beeble API with Terjemahan Baru (TB)

## Scope

**In Scope:**
-

**Out of Scope:**
-

## Context

PHP app, no framework. Files: index.php, presenter.php, projector.php, scraper.php. Beeble API at https://beeble.vercel.app. Passage endpoint: /api/v1/passage/{abbr}/{chapter}?ver=tb. Books list endpoint: /api/v1/passage/list. Need to create biblepresentation.php and bibleprojector.php following same patterns as existing presenter.php/projector.php (BroadcastChannel sync, dark theme, keyboard nav, click/swipe support).

## Acceptance Criteria

1. biblepresentation.php: book selector, chapter selector, verse slides, prev/next, "Proyektor" button that opens bibleprojector.php in new window + navigates current page to presentation mode, BroadcastChannel sync. 2. bibleprojector.php: fullscreen verse display, BroadcastChannel listener, keyboard/click navigation, fade-in animation.

## Approach

-

## Tasks

| # | Task | Files | Status |
|---|------|-------|--------|
| 1 | - | - | pending |

## Risks & Mitigations

-

## Verification

- [ ] All tasks completed
- [ ] Tests pass
- [ ] Edge cases handled
