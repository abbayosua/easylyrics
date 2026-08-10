import {defineConfig} from "@playwright/test";

/**
 * E2E — EasyLyrics (web app)
 *
 * PENTING: test SELALU dijalankan SATU PER SATU (sequential), tidak pernah
 * paralel — karena app bergantung pada 1 database + state proyeksi global
 * (tabel `state`) yang dibagi antar test.
 *
 * - workers: 1            → satu test file / worker pada satu waktu
 * - fullyParallel: false  → test case dalam 1 file juga jalan berurutan
 * - channel: 'chrome'     → pakai Google Chrome yang sudah terinstall
 *                           (TIDAK pakai chromium bundling Playwright)
 * - TIDAK ada webServer   → app di-serve oleh Apache yang sudah jalan di
 *                           port 80 (http://localhost/easylyrics)
 */
export default defineConfig({
  testDir: "./",
  workers: 1,
  fullyParallel: false,
  retries: 0,
  timeout: 60_000,
  use: {
    channel: "chrome",
    headless: true,
    baseURL: "http://localhost/easylyrics/",
    locale: "id-ID",
  },
  // Database di-bersihkan SEBELUM suite (sekali, berurutan) — lihat global-setup
  globalSetup: "./global-setup.ts",
});
