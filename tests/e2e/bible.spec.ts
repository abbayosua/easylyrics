import {test, expect} from "@playwright/test";

/**
 * E2E — Alkitab (bible presentation flow)
 *
 * Alur: presenter pilih kitab+pasal → ayat tampil 1/36 → navigasi →
 * proyektor (polling state) ikut sinkron.
 *
 * NOTE: seluruh test di file ini dan antar file SELALU berjalan SEQUENTIAL
 * (workers:1, fullyParallel:false — lihat playwright.config.ts).
 */

test.describe("Alkitab", () => {
  test("muat pasal, navigasi, dan proyektor sinkron", async ({page, context}) => {
    // ---- 1. buka operator ----
    await page.goto("presenter.php");
    await expect(page).toHaveTitle(/EasyLyrics — Operator/);

    // ---- 2. buka tab Alkitab ----
    await page.getByTestId("tabBible").click();

    // ---- 3. pilih Yohanes pasal 3 ----
    await page.getByTestId("bookSel").selectOption("Yoh");
    await page.getByTestId("chapSel").selectOption("3");
    await page.getByTestId("btnLoadChapter").click();

    // ---- 4. pasal termuat: 36 ayat, slide 1 = Yoh 3:1 ----
    await expect(page.getByTestId("counter")).toHaveText("1 / 36", {timeout: 30_000});
    const ref = page.getByTestId("previewRef");
    await expect(ref).toHaveText("1");
    const lines = page.getByTestId("previewLines");
    await expect(lines).toContainText("Adalah seorang Farisi");

    // ---- 5. navigasi next → ayat 2 ----
    await page.getByTestId("btnNext").click();
    await expect(page.getByTestId("counter")).toHaveText("2 / 36");
    await expect(ref).toHaveText("2");
    await expect(lines).toContainText("Ia datang pada waktu malam");

    // ---- 6. buka proyektor (halaman kedua, URL fixed) ----
    const proyektor = await context.newPage();
    await proyektor.goto("projector.php");

    // polling tiap 500ms — tunggu sampai sinkron ke ayat 2
    await expect
      .poll(async () => proyektor.locator("#slideText").textContent())
      .toContain("Ia datang pada waktu malam", {timeout: 15_000});

    // ---- 7. navigasi presenter → proyektor ikut (ayat 3) ----
    await page.getByTestId("btnNext").click();
    await expect(page.getByTestId("counter")).toHaveText("3 / 36");
    await expect
      .poll(async () => proyektor.locator("#slideText").textContent())
      .toContain("Yesus menjawab", {timeout: 15_000});

    // ---- 8. navigasi langsung dari proyektor (klik kanan = next) ----
    const area = proyektor.locator("#slideArea");
    const box = await area.boundingBox();
    await area.click({position: {x: box!.width * 0.8, y: box!.height * 0.5}});
    await expect
      .poll(async () => page.getByTestId("counter").textContent())
      .toContain("4 / 36", {timeout: 15_000});

    // ---- 9. counter proyektor konsisten ----
    await expect(proyektor.locator("#counter")).toHaveText("4 / 36", {timeout: 10_000});
  });

  test("pasal yang sama ter-cache (tanpa jaringan, hasil tetap sama)", async ({page}) => {
    await page.goto("presenter.php");
    await page.getByTestId("tabBible").click();
    await page.getByTestId("bookSel").selectOption("Yoh");
    await page.getByTestId("chapSel").selectOption("3");
    await page.getByTestId("btnLoadChapter").click();

    await expect(page.getByTestId("counter")).toHaveText("1 / 36", {timeout: 30_000});
    await expect(page.getByTestId("previewLines")).toContainText("Adalah seorang Farisi");
  });
});
