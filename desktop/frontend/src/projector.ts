import {Events} from "@wailsio/runtime";

// ---- Protocol port: presenter:goTo (operator -> projector) ----
let slides: {type: string, lines: string[], number: number}[] = [];
let current = 0;

const slideText = document.getElementById("slideText") as HTMLDivElement;
const slideRef = document.getElementById("slideRef") as HTMLDivElement;
const counterEl = document.getElementById("counter") as HTMLSpanElement;
const toolbarTitle = document.getElementById("toolbarTitle") as HTMLDivElement;

function renderSlide() {
    const s = slides[current];
    if (s.type === "pause") {
        slideText.textContent = "· · ·";
    } else {
        slideText.textContent = s.lines.join("\n");
    }
    slideRef.textContent = s.number > 0 ? String(s.number) : "";
    slideRef.style.display = s.number > 0 ? "" : "none";
    counterEl.textContent = `${current + 1} / ${slides.length}`;
    fitText();
}

// ---- Fit text: shrink font until the text fits the available height ----
function fitText() {
    const area = document.getElementById("slideArea") as HTMLDivElement;
    const slide = document.querySelector(".slide") as HTMLDivElement;
    const ref = document.getElementById("slideRef") as HTMLDivElement;
    if (!area || !slide) return;

    const style = getComputedStyle(slide);
    const padV = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
    const gap = parseFloat(style.gap) || 0;
    const refH = ref.style.display === "none" ? 0 : ref.offsetHeight;
    const availH = slide.clientHeight - padV - refH - gap - 10;
    if (availH <= 0) return;

    slideText.style.fontSize = "80px";
    let size = 80;
    while (size > 12 && slideText.scrollHeight > availH) {
        size--;
        slideText.style.fontSize = size + "px";
    }
}

function goTo(n: number, confirm: boolean) {
    if (slides.length === 0 || n < 0 || n >= slides.length) return;
    current = n;
    renderSlide();
    if (confirm) Events.Emit("projector:synced", n);
}

function next() { goTo(current + 1, true); }
function prev() { goTo(current - 1, true); }

// -- controls --
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

document.getElementById("slideArea")!.addEventListener("click", (e) => {
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
    const x = e.clientX - rect.left;
    if (x < rect.width / 2) prev();
    else next();
});

let touchStartX = 0;
document.getElementById("slideArea")!.addEventListener("touchstart", (e) => {
    touchStartX = e.touches[0].clientX;
});
document.getElementById("slideArea")!.addEventListener("touchend", (e) => {
    const diff = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(diff) > 40) {
        if (diff < 0) next();
        else prev();
    }
});

let resizeTimer: ReturnType<typeof setTimeout>;
window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(fitText, 150);
});

// ---- operator navigation ----
Events.On("presenter:goTo", (e: {data: number}) => {
    goTo(e.data, true);
});

// ---- initial state ----
renderSlide();
console.log("Projector window ready");
