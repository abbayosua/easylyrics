import {test, expect} from "@playwright/test";

/**
 * E2E — Flow 5: Proyektor (URL fixed, sinkron dua arah)
 * Presenter navigasi → proyektor ikut (polling);
 * Proyektor navigasi (klik/keyboard) → presenter ikut.
 */

const TITLE = `Lagu E2E Proyektor ${Date.now()}-${Math.random().toString(36).slice(2, 6)}`;
const LYRIC = "Proyektor baris satu\nProyektor baris dua\n\nProyektor baris tiga\nProyektor baris empat";

async function seedAndPresent(page: import("@playwright/test").Page) {
  const resp = await page.request.post("api.php?action=add_song", {
    data: {title: TITLE, lyric: LYRIC, chord: ""},
  });
  expect(resp.ok()).toBeTruthy();
  await page.goto("presenter.php");
  await page.getByTestId("songSearch").fill(TITLE);
  await page.getByTestId("btnSearch").click();
  await page.locator(".lib-item", {hasText: TITLE}).first().click();
  await expect(page.getByTestId("counter")).toHaveText("1 / 3", {timeout: 10_000});
}

test.describe("Proyektor", () => {
  test("presenter → proyektor ikut (polling state)", async ({page, context}) => {
    await seedAndPresent(page);

    const pro = await context.newPage();
    await pro.goto("projector.php");

    // slide 1 tampil di proyektor
    await expect
      .poll(async () => pro.locator("#slideText").textContent())
      .toContain("Proyektor baris satu", {timeout: 15_000});

    // presenter next → proyektor ikut ke pause (slide 2)
    await page.getByTestId("btnNext").click();
    await expect
      .poll(async () => pro.locator("#slideText").textContent())
      .toContain("· · ·", {timeout: 15_000});
    await expect(pro.locator("#counter")).toHaveCount(0);
  });

  test("proyektor navigasi → presenter ikut", async ({page, context}) => {
    await seedAndPresent(page);

    const pro = await context.newPage();
    await pro.goto("projector.php");
    await expect
      .poll(async () => pro.locator("#slideText").textContent())
      .toContain("Proyektor baris satu", {timeout: 15_000});

    // keyboard di proyektor: ArrowRight → slide 2
    await pro.locator("body").click({position: {x: 5, y: 5}});
    await pro.keyboard.press("ArrowRight");

    // presenter ikut (polling 1s)
    await expect
      .poll(async () => page.getByTestId("counter").textContent())
      .toContain("2 / 3", {timeout: 15_000});
  });

  test("klik kanan proyektor = next, klik kiri = prev", async ({page, context}) => {
    await seedAndPresent(page);

    const pro = await context.newPage();
    await pro.goto("projector.php");
    await expect
      .poll(async () => pro.locator("#slideText").textContent())
      .toContain("Proyektor baris satu", {timeout: 15_000});

    const area = pro.locator("#slideArea");
    const box = await area.boundingBox();
    // klik kanan → slide 2
    await area.click({position: {x: box!.width * 0.8, y: box!.height * 0.5}});
    await expect
      .poll(async () => pro.locator("#slideText").textContent())
      .toContain("· · ·", {timeout: 15_000});

    // klik kiri → kembali slide 1
    await area.click({position: {x: box!.width * 0.2, y: box!.height * 0.5}});
    await expect
      .poll(async () => pro.locator("#slideText").textContent())
      .toContain("Proyektor baris satu", {timeout: 15_000});
  });
});
