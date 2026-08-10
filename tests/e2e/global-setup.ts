import mysql from "mysql2/promise";

/**
 * Global setup — dijalankan SATU KALI sebelum semua test (berurutan).
 *
 * 1. State proyeksi di-reset ke 'idle'
 * 2. Cache pasal Yoh 3 di-warm-up via API (fetch beeble SEKALI di setup)
 *    → test suite tidak bergantung pada ketersediaan jaringan beeble.
 */
export default async function globalSetup() {
  const conn = await mysql.createConnection({
    host: "127.0.0.1",
    port: 3306,
    user: "root",
    password: "",
    database: "easilyrics",
  });
  await conn.query("UPDATE state SET type='idle', title=NULL, ref=NULL, slide=0, slides=NULL WHERE id=1");
  await conn.end();

  // warm-up cache Yoh 3 (hapus dulu biar fetch benar-benar terjadi, lalu simpan)
  const warmup = await fetch(
    "http://localhost/easylyrics/api.php?action=get_chapter&book=Yoh&chapter=3",
  );
  const body = await warmup.json().catch(() => null);
  if (!body || !body.slides || body.slides.length === 0) {
    console.warn("E2E: warm-up Yoh 3 gagal — beeble mungkin sedang down. Test Alkitab bisa gagal.");
  } else {
    console.log(`E2E: cache Yoh 3 siap (${body.slides.length} ayat).`);
  }
}
