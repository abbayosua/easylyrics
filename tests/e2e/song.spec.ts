import {test, expect} from "@playwright/test";

/**
 * E2E — Flow 2: Presentasi Lagu
 * 2 baris per slide, jeda antar bagian, counter, next/prev,
 * slide list click-to-jump, keyboard.
 */

const LYRIC = "Baris satu\nBaris dua\n\nBaris tiga\nBaris empat";

function uniqueTitle(tag: string) {
  return `Lagu E2E ${tag} ${Date.now()}-${Math.random().toString(36).slice(2, 6)}`;
}

async function seedSong(page: import("@playwright/test").Page, title: string) {
  const resp = await page.request.post("api.php?action=add_song", {
    data: {title, lyric: LYRIC, chord: ""},
  });
  expect(resp.ok()).toBeTruthy();
}

async function presentSong(page: import("@playwright/test").Page, title: string) {
  await page.goto("presenter.php");
  await page.getByTestId("songSearch").fill(title);
  await page.getByTestId("btnSearch").click();
  await page.locator(".lib-item", {hasText: title}).first().click();
}

test.describe("Presentasi Lagu", () => {
  test("slide 2 baris + jeda antar bagian", async ({page}) => {
    const t = uniqueTitle("DuaBaris");
    await seedSong(page, t);
    await presentSong(page, t);

    await expect(page.getByTestId("counter")).toHaveText("1 / 3", {timeout: 10_000});
    const lines = page.getByTestId("previewLines");
    await expect(lines).toContainText("Baris satu");
    await expect(lines).toContainText("Baris dua");
  });

  test("navigasi next → pause → slide terakhir, prev kembali", async ({page}) => {
    const t = uniqueTitle("Nav");
    await seedSong(page, t);
    await presentSong(page, t);

    await page.getByTestId("btnNext").click();
    await expect(page.getByTestId("counter")).toHaveText("2 / 3");
    await expect(page.getByTestId("previewLines")).not.toContainText("Baris");

    await page.getByTestId("btnNext").click();
    await expect(page.getByTestId("counter")).toHaveText("3 / 3");
    await expect(page.getByTestId("previewLines")).toContainText("Baris tiga");
    await expect(page.getByTestId("previewLines")).toContainText("Baris empat");

    await page.getByTestId("btnPrev").click();
    await expect(page.getByTestId("counter")).toHaveText("2 / 3");
  });

  test("slide list click-to-jump", async ({page}) => {
    const t = uniqueTitle("List");
    await seedSong(page, t);
    await presentSong(page, t);

    await page.locator(".sl-item").nth(2).click();
    await expect(page.getByTestId("counter")).toHaveText("3 / 3");
    await expect(page.getByTestId("previewLines")).toContainText("Baris tiga");
  });

  test("keyboard panah kanan = next", async ({page}) => {
    const t = uniqueTitle("Keyboard");
    await seedSong(page, t);
    await presentSong(page, t);

    // pastikan fokus di halaman (bukan di input search)
    await page.locator("body").click({position: {x: 5, y: 5}});
    await page.keyboard.press("ArrowRight");
    await expect(page.getByTestId("counter")).toHaveText("2 / 3");
    await page.keyboard.press("ArrowLeft");
    await expect(page.getByTestId("counter")).toHaveText("1 / 3");
  });
});
