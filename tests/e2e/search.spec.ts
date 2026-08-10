import {test, expect} from "@playwright/test";

/**
 * E2E — Flow 1: Cari Lagu
 * Local MySQL first; manual song always searchable; gibberish -> empty state.
 */

const TITLE = `Lagu E2E Search ${Date.now()}`;

test.describe("Cari Lagu", () => {
  test("lagu manual bisa dicari dan muncul di daftar", async ({page}) => {
    // seed via API (bukan lewat UI — itu flow 3)
    const resp = await page.request.post("api.php?action=add_song", {
      data: {title: TITLE, lyric: "Baris pertama e2e\nBaris kedua e2e", chord: "C G"},
    });
    expect(resp.ok()).toBeTruthy();

    await page.goto("presenter.php");
    await page.getByTestId("songSearch").fill(TITLE);
    await page.getByTestId("btnSearch").click();

    const item = page.locator('.src-group[data-src="local"] .lib-item', {hasText: TITLE});
    await expect(item).toBeVisible({timeout: 15_000});
  });

  test("kata yang tidak ada → grup local menampilkan 'kosong'", async ({page}) => {
    await page.goto("presenter.php");
    await page.getByTestId("songSearch").fill("zygote-e2e-abcdef");
    await page.getByTestId("btnSearch").click();

    // local selalu paling cepat: tampil "· kosong"
    await expect(page.locator('.src-group[data-src="local"] .src-count')).toHaveText("· kosong", {timeout: 10_000});
  });

  test("search menampilkan grup per situs (spinner → hasil)", async ({page}) => {
    await page.goto("presenter.php");
    await page.getByTestId("songSearch").fill("kasih");
    await page.getByTestId("btnSearch").click();

    // grup sumber tampil semua (4 grup)
    await expect(page.locator(".src-group")).toHaveCount(4);
    // local berisi hasil
    await expect(page.locator('.src-group[data-src="local"] .src-count')).not.toBeEmpty({timeout: 10_000});
    // jrchord mengisi hasil (atau kosong/gagal) — spinner hilang
    await expect(page.locator('.src-group[data-src="jrchord"] .src-spinner')).toHaveCount(0, {timeout: 30_000});
  });

  test("klik hasil search → lagu tampil (counter & slide 1)", async ({page}) => {
    const own = `Lagu E2E Klik ${Date.now()}`;
    const resp = await page.request.post("api.php?action=add_song", {
      data: {title: own, lyric: "Baris pertama e2e\\nBaris kedua e2e", chord: "C G"},
    });
    expect(resp.ok()).toBeTruthy();

    await page.goto("presenter.php");
    await page.getByTestId("songSearch").fill(own);
    await page.getByTestId("btnSearch").click();

    const item = page.locator(".lib-item", {hasText: own});
    await expect(item).toBeVisible({timeout: 10_000});
    await item.click();

    await expect(page.getByTestId("counter")).toHaveText(/1 \//, {timeout: 10_000});
    await expect(page.getByTestId("previewLines")).toContainText("Baris pertama e2e");
    await expect(page.getByTestId("previewLines")).toContainText("Baris kedua e2e");
  });
});
