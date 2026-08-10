import {Events} from "@wailsio/runtime";
import {App} from "../bindings/easilyrics";

// ---------------------------------------------------------------------------
// Protocol port (web BroadcastChannel -> Wails Events)
//   presenter:goTo    operator -> projector
//   projector:synced  projector -> operator
// ---------------------------------------------------------------------------

// ---- State ----
let slides: {type: string, lines: string[], number: number}[] = [];
let current = 0;

// ---- Book list (66 books, same as beeble) ----
const BOOKS = [
    ["Kej","Kejadian",50],["Kel","Keluaran",40],["Ima","Imamat",27],["Bil","Bilangan",36],
    ["Ula","Ulangan",34],["Yos","Yosua",24],["Hak","Hakim-hakim",21],["Rut","Rut",4],
    ["1 Sam","1 Samuel",31],["2 Sam","2 Samuel",24],["1 Raj","1 Raja-Raja",22],["2 Raj","2 Raja-Raja",25],
    ["1 Taw","1 Tawarikh",29],["2 Taw","2 Tawarikh",36],["Ezr","Ezra",10],["Neh","Nehemia",13],
    ["Est","Ester",10],["Ayb","Ayub",42],["Maz","Mazmur",150],["Ams","Amsal",31],
    ["Pkh","Pengkhotbah",12],["Kid","Kidung Agung",8],["Yes","Yesaya",66],["Yer","Yeremia",52],
    ["Rat","Ratapan",5],["Yeh","Yehezkiel",48],["Dan","Daniel",12],["Hos","Hosea",14],
    ["Yoe","Yoel",3],["Amo","Amos",9],["Oba","Obaja",1],["Yun","Yunus",4],
    ["Mik","Mikha",7],["Nah","Nahum",3],["Hab","Habakuk",3],["Zef","Zefanya",3],
    ["Hag","Hagai",2],["Zak","Zakharia",14],["Mal","Maleakhi",4],["Mat","Matius",28],
    ["Mar","Markus",16],["Luk","Lukas",24],["Yoh","Yohanes",21],["Kis","Kisah Para Rasul",28],
    ["Rom","Roma",16],["1 Kor","1 Korintus",16],["2 Kor","2 Korintus",13],["Gal","Galatia",6],
    ["Efe","Efesus",6],["Flp","Filipi",4],["Kol","Kolose",4],["1 Tes","1 Tesalonika",5],
    ["2 Tes","2 Tesalonika",3],["1 Tim","1 Timotius",6],["2 Tim","2 Timotius",4],["Tit","Titus",3],
    ["Flm","Filemon",1],["Ibr","Ibrani",13],["Yak","Yakobus",5],["1 Pet","1 Petrus",5],
    ["2 Pet","2 Petrus",3],["1 Yoh","1 Yohanes",5],["2 Yoh","2 Yohanes",1],["3 Yoh","3 Yohanes",1],
    ["Yud","Yudas",1],["Wah","Wahyu",22],
];

// ---- Helpers ----
function escapeHtml(s: string): string {
    const d = document.createElement("div");
    d.textContent = s;
    return d.innerHTML;
}

// ---- Slide navigation ----
function setSlide(n: number, broadcast: boolean) {
    if (slides.length === 0) return;
    if (n < 0 || n >= slides.length) return;
    current = n;
    renderPreview();
    renderCounter();
    renderSlideList();
    if (broadcast) Events.Emit("presenter:goTo", n);
}

function next() { setSlide(current + 1, true); }
function prev() { setSlide(current - 1, true); }

function renderPreview() {
    const s = slides[current];
    const empty = document.getElementById("emptyHint")!;
    const ref = document.getElementById("previewRef") as HTMLDivElement;
    const lines = document.getElementById("previewLines")!;
    const nextBox = document.getElementById("previewNext")!;
    const nextText = document.getElementById("previewNextText")!;

    empty.style.display = "none";
    ref.style.display = s.number > 0 ? "" : "none";
    ref.textContent = s.number > 0 ? String(s.number) : "";

    if (s.type === "pause") {
        lines.innerHTML = `<div class="preview-pause"><span class="pause-dot"></span><span class="pause-dot wide"></span><span class="pause-dot"></span></div>`;
    } else {
        lines.innerHTML = s.lines.map(l => `<div class="preview-line">${escapeHtml(l)}</div>`).join("");
    }

    if (current + 1 < slides.length) {
        const nx = slides[current + 1];
        nextText.textContent = nx.type === "pause" ? "(jeda)" : (nx.lines[0] || "");
        nextBox.style.display = "";
    } else {
        nextText.textContent = "(selesai)";
        nextBox.style.display = "";
    }
}

function renderCounter() {
    document.getElementById("counter")!.textContent = slides.length > 0 ? `${current + 1} / ${slides.length}` : "- / -";
}

function renderSlideList() {
    const el = document.getElementById("slideList")!;
    if (slides.length === 0) {
        el.classList.remove("has-items");
        el.innerHTML = "";
        return;
    }
    el.classList.add("has-items");
    let html = "";
    slides.forEach((s, i) => {
        const active = i === current ? " active" : "";
        const label = s.type === "pause"
            ? `<div class="sl-pause">&mdash; jeda &mdash;</div>`
            : `<div class="sl-text">${escapeHtml(s.lines[0] || "")}</div>`;
        html += `<div class="sl-item${active}" data-index="${i}"><div class="sl-num">${i + 1}</div>${label}</div>`;
    });
    el.innerHTML = html;
    el.querySelectorAll(".sl-item").forEach(item => {
        item.addEventListener("click", () => {
            setSlide(parseInt((item as HTMLElement).dataset.index!), true);
        });
    });
    const act = el.querySelector(".sl-item.active");
    if (act) act.scrollIntoView({block: "nearest"});
}

// ---- Library: songs ----
async function loadSongs(query: string) {
    const list = document.getElementById("songList")!;
    list.innerHTML = `<div class="lib-empty">Memuat...</div>`;
    const songs = await App.ListSongs(query, 100);
    if (!songs || songs.length === 0) {
        list.innerHTML = `<div class="lib-empty">Tidak ada lagu. (Fase 4: auto-download dari REST)</div>`;
        return;
    }
    list.innerHTML = "";
    for (const s of songs) {
        const item = document.createElement("div");
        item.className = "lib-item";
        item.innerHTML = `<div class="lib-item-title">${escapeHtml(s.title)}</div>`;
        item.addEventListener("click", () => presentSong(s.id));
        list.appendChild(item);
    }
}

async function presentSong(id: number) {
    const s = await App.GetSongSlides(id);
    if (!s || s.length === 0) return;
    slides = s;
    current = 0;
    renderPreview();
    renderCounter();
    Events.Emit("presenter:goTo", 0);
}

// ---- Library: bible ----
function initBibleSelectors() {
    const bookSel = document.getElementById("bookSelect") as HTMLSelectElement;
    bookSel.innerHTML = '<option value="">-- Kitab --</option>';
    for (const [abbr, name] of BOOKS) {
        const o = document.createElement("option");
        o.value = abbr;
        o.textContent = name;
        bookSel.appendChild(o);
    }
    bookSel.addEventListener("change", () => {
        const ch = document.getElementById("chapterSelect") as HTMLSelectElement;
        const idx = bookSel.selectedIndex - 1;
        ch.innerHTML = "";
        if (idx < 0) return;
        const chapters = BOOKS[idx][2];
        for (let i = 1; i <= chapters; i++) {
            const o = document.createElement("option");
            o.value = String(i);
            o.textContent = "Pasal " + i;
            ch.appendChild(o);
        }
    });
}

async function presentBibleChapter() {
    const book = (document.getElementById("bookSelect") as HTMLSelectElement).value;
    const chapter = (document.getElementById("chapterSelect") as HTMLSelectElement).value;
    if (!book || !chapter) return;
    const s = await App.GetBibleSlides(book, parseInt(chapter), "tb");
    if (!s || s.length === 0) {
        alert("Gagal memuat pasal (periksa koneksi internet).");
        return;
    }
    slides = s;
    current = 0;
    renderPreview();
    renderCounter();
    Events.Emit("presenter:goTo", 0);
}

// ---- Wire up ----
document.getElementById("btnFirst")!.addEventListener("click", () => setSlide(0, true));
document.getElementById("btnLast")!.addEventListener("click", () => setSlide(slides.length - 1, true));
document.getElementById("btnPrev")!.addEventListener("click", prev);
document.getElementById("btnNext")!.addEventListener("click", next);

document.addEventListener("keydown", (e) => {
    switch (e.key) {
        case "ArrowUp":
        case "ArrowLeft":
            e.preventDefault(); prev(); break;
        case "ArrowDown":
        case "ArrowRight":
            e.preventDefault(); next(); break;
    }
});

document.getElementById("btnProjector")!.addEventListener("click", () => App.FocusProjector());
document.getElementById("btnFullscreen")!.addEventListener("click", async () => App.ToggleProjectorFullscreen());

// tabs
document.getElementById("tabSongs")!.addEventListener("click", () => {
    document.getElementById("tabSongs")!.classList.add("active");
    document.getElementById("tabBible")!.classList.remove("active");
    document.getElementById("panelSongs")!.style.display = "";
    document.getElementById("panelBible")!.style.display = "none";
});
document.getElementById("tabBible")!.addEventListener("click", () => {
    document.getElementById("tabBible")!.classList.add("active");
    document.getElementById("tabSongs")!.classList.remove("active");
    document.getElementById("panelBible")!.style.display = "";
    document.getElementById("panelSongs")!.style.display = "none";
});

document.getElementById("btnSearch")!.addEventListener("click", () => {
    loadSongs((document.getElementById("songSearch") as HTMLInputElement).value);
});
document.getElementById("songSearch")!.addEventListener("keydown", (e) => {
    if (e.key === "Enter") loadSongs((e.target as HTMLInputElement).value);
});
document.getElementById("btnLoadChapter")!.addEventListener("click", presentBibleChapter);

// ---- Add song modal ----
const addModal = document.getElementById("addModal")!;
function openModal() {
    (document.getElementById("inpTitle") as HTMLInputElement).value = "";
    (document.getElementById("inpLyric") as HTMLTextAreaElement).value = "";
    (document.getElementById("inpChord") as HTMLTextAreaElement).value = "";
    addModal.style.display = "flex";
    (document.getElementById("inpTitle") as HTMLInputElement).focus();
}
function closeModal() { addModal.style.display = "none"; }

document.getElementById("btnAddSong")!.addEventListener("click", openModal);
document.getElementById("btnCloseModal")!.addEventListener("click", closeModal);
addModal.addEventListener("click", (e) => { if (e.target === addModal) closeModal(); });
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && addModal.style.display !== "none") closeModal();
});

document.getElementById("btnSaveSong")!.addEventListener("click", async () => {
    const title = (document.getElementById("inpTitle") as HTMLInputElement).value.trim();
    const lyric = (document.getElementById("inpLyric") as HTMLTextAreaElement).value;
    const chord = (document.getElementById("inpChord") as HTMLTextAreaElement).value;
    if (!title) {
        alert("Judul wajib diisi.");
        return;
    }
    await App.AddSongManual(title, lyric, chord);
    closeModal();
    loadSongs("");
});

// Projector confirms its index (mirrors BroadcastChannel "synced")
Events.On("projector:synced", (e: {data: number}) => {
    const n = e.data;
    if (n >= 0 && n < slides.length) setSlide(n, false);
});

initBibleSelectors();
loadSongs("");
renderCounter();
renderSlideList();
console.log("Operator window ready");
