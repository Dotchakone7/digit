/**
 * Generates the demo product illustrations used by DemoSeeder.
 * Run once (output is committed): node scripts/generate-demo-images.mjs
 * Requires Playwright's Chromium. Real product photos replace these in production.
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const OUT = process.env.OUT_DIR ?? new URL('../database/seeders/demo-images/', import.meta.url).pathname;
mkdirSync(OUT, { recursive: true });

const shade = (hex, amt) => {
    const n = parseInt(hex.slice(1), 16);
    const c = (v) => Math.max(0, Math.min(255, v + amt));
    return '#' + [(n >> 16) + 0, ((n >> 8) & 255) + 0, (n & 255) + 0].map((v) => c(v).toString(16).padStart(2, '0')).join('');
};

const shapes = {
    headphones: (c) => `
        <path d="M245 430 A155 155 0 0 1 555 430" fill="none" stroke="${shade(c, -30)}" stroke-width="36" stroke-linecap="round"/>
        <rect x="200" y="395" width="96" height="170" rx="44" fill="${c}"/><rect x="232" y="420" width="44" height="120" rx="22" fill="${shade(c, -45)}"/>
        <rect x="504" y="395" width="96" height="170" rx="44" fill="${c}"/><rect x="524" y="420" width="44" height="120" rx="22" fill="${shade(c, -45)}"/>`,
    earbuds: (c) => `
        <rect x="250" y="390" width="300" height="200" rx="100" fill="#ffffff"/><path d="M262 470 H538" stroke="#e4e4e7" stroke-width="6"/>
        <circle cx="400" cy="560" r="10" fill="${c}"/>
        <ellipse cx="340" cy="300" rx="52" ry="58" fill="#ffffff"/><rect x="318" y="320" width="34" height="110" rx="17" fill="#ffffff"/><circle cx="340" cy="296" r="22" fill="${c}"/>
        <ellipse cx="460" cy="300" rx="52" ry="58" fill="#ffffff"/><rect x="448" y="320" width="34" height="110" rx="17" fill="#ffffff"/><circle cx="460" cy="296" r="22" fill="${c}"/>`,
    speaker: (c) => {
        let dots = '';
        for (let y = 330; y <= 570; y += 30) for (let x = 330; x <= 470; x += 30) dots += `<circle cx="${x}" cy="${y}" r="7" fill="${shade(c, -40)}"/>`;
        return `<rect x="290" y="250" width="220" height="390" rx="110" fill="${c}"/><ellipse cx="400" cy="262" rx="96" ry="22" fill="${shade(c, 30)}"/>${dots}
            <rect x="370" y="600" width="60" height="12" rx="6" fill="${shade(c, 40)}"/>`;
    },
    smartphone: (c) => `
        <rect x="290" y="170" width="220" height="460" rx="40" fill="#18181b"/>
        <rect x="304" y="186" width="192" height="428" rx="30" fill="url(#screen)"/>
        <defs><linearGradient id="screen" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${shade(c, 40)}"/><stop offset="1" stop-color="${shade(c, -50)}"/></linearGradient></defs>
        <rect x="370" y="198" width="60" height="16" rx="8" fill="#18181b"/>
        <circle cx="400" cy="420" r="58" fill="#ffffff" opacity="0.18"/><rect x="335" y="540" width="130" height="10" rx="5" fill="#ffffff" opacity="0.5"/>`,
    powerbank: (c) => `
        <rect x="255" y="290" width="290" height="320" rx="44" fill="${c}"/>
        <rect x="275" y="310" width="250" height="280" rx="32" fill="${shade(c, 18)}"/>
        <circle cx="360" cy="560" r="8" fill="#a3e635"/><circle cx="390" cy="560" r="8" fill="#a3e635"/><circle cx="420" cy="560" r="8" fill="#a3e635"/><circle cx="450" cy="560" r="8" fill="#ffffff" opacity="0.5"/>
        <path d="M400 290 V230 Q400 200 430 200 H520" fill="none" stroke="#27272a" stroke-width="14" stroke-linecap="round"/>`,
    watch: (c) => `
        <rect x="352" y="160" width="96" height="480" rx="34" fill="${c}"/>
        <circle cx="400" cy="400" r="122" fill="#d4d4d8"/><circle cx="400" cy="400" r="104" fill="#18181b"/>
        <circle cx="400" cy="400" r="92" fill="${shade(c, 70)}"/>
        <path d="M400 400 V335" stroke="#18181b" stroke-width="10" stroke-linecap="round"/><path d="M400 400 L448 428" stroke="#18181b" stroke-width="8" stroke-linecap="round"/>
        <circle cx="400" cy="400" r="9" fill="#18181b"/><rect x="520" y="385" width="22" height="30" rx="6" fill="#a1a1aa"/>`,
    sneaker: (c) => `
        <path d="M170 520 Q170 470 220 450 L310 400 Q350 380 380 350 L420 330 Q450 320 470 350 L520 430 Q600 450 640 490 Q660 515 650 540 Z" fill="${c}"/>
        <path d="M165 520 H655 Q660 580 610 590 H200 Q160 585 165 520 Z" fill="#ffffff"/><path d="M165 548 H655" stroke="#e4e4e7" stroke-width="6"/>
        <path d="M300 470 Q420 430 560 470" fill="none" stroke="#ffffff" stroke-width="16" stroke-linecap="round" opacity="0.85"/>
        <path d="M380 380 l40 18 M395 360 l40 18 M410 340 l38 18" stroke="#ffffff" stroke-width="8" stroke-linecap="round"/>`,
    tshirt: (c) => `
        <path d="M300 215 L360 195 Q400 240 440 195 L500 215 L605 290 L560 370 L510 340 L510 615 L290 615 L290 340 L240 370 L195 290 Z" fill="${c}"/>
        <path d="M360 195 Q400 250 440 195" fill="none" stroke="${shade(c, -35)}" stroke-width="10"/>
        <rect x="335" y="380" width="130" height="16" rx="8" fill="#ffffff" opacity="0.6"/>`,
    dress: (c) => `
        <path d="M352 190 L370 260 M448 190 L430 260" stroke="${shade(c, -30)}" stroke-width="10" stroke-linecap="round"/>
        <path d="M345 255 Q400 285 455 255 L470 345 Q560 470 600 620 L200 620 Q240 470 330 345 Z" fill="${c}"/>
        <path d="M330 345 Q400 375 470 345" fill="none" stroke="${shade(c, -35)}" stroke-width="14"/>`,
    handbag: (c) => `
        <path d="M310 370 Q310 230 400 230 Q490 230 490 370" fill="none" stroke="${shade(c, -35)}" stroke-width="24" stroke-linecap="round"/>
        <path d="M225 360 H575 L605 615 H195 Z" fill="${c}"/><path d="M215 430 H585" stroke="${shade(c, -25)}" stroke-width="8"/>
        <rect x="372" y="412" width="56" height="40" rx="10" fill="#e5c07b"/>`,
    backpack: (c) => `
        <path d="M360 240 Q360 200 400 200 Q440 200 440 240" fill="none" stroke="${shade(c, -35)}" stroke-width="18"/>
        <rect x="265" y="235" width="270" height="390" rx="80" fill="${c}"/>
        <rect x="305" y="430" width="190" height="150" rx="34" fill="${shade(c, -22)}"/><path d="M325 470 H475" stroke="#f4f4f5" stroke-width="6" stroke-dasharray="14 10"/>
        <rect x="385" y="300" width="30" height="70" rx="15" fill="${shade(c, 25)}"/>`,
    sunglasses: (c) => `
        <path d="M200 360 L150 330 M600 360 L650 330" stroke="#27272a" stroke-width="14" stroke-linecap="round"/>
        <rect x="200" y="350" width="175" height="130" rx="58" fill="#27272a"/><rect x="425" y="350" width="175" height="130" rx="58" fill="#27272a"/>
        <rect x="214" y="364" width="147" height="102" rx="48" fill="${c}" opacity="0.92"/><rect x="439" y="364" width="147" height="102" rx="48" fill="${c}" opacity="0.92"/>
        <path d="M375 395 Q400 370 425 395" fill="none" stroke="#27272a" stroke-width="14"/>
        <path d="M240 390 Q270 375 300 385" stroke="#ffffff" stroke-width="10" stroke-linecap="round" opacity="0.5"/>`,
    lamp: (c) => `
        <path d="M300 220 H500 L565 400 H235 Z" fill="${c}"/><path d="M245 380 H555" stroke="${shade(c, -30)}" stroke-width="10"/>
        <rect x="388" y="400" width="24" height="190" fill="#a1a1aa"/><ellipse cx="400" cy="600" rx="110" ry="26" fill="#3f3f46"/>
        <ellipse cx="400" cy="410" rx="120" ry="20" fill="#fde68a" opacity="0.5"/>`,
    mug: (c) => `
        <path d="M500 400 Q590 400 590 470 Q590 540 500 540" fill="none" stroke="${c}" stroke-width="30"/>
        <rect x="270" y="330" width="240" height="270" rx="34" fill="${c}"/><ellipse cx="390" cy="335" rx="120" ry="20" fill="${shade(c, -45)}"/>
        <path d="M350 290 Q330 250 355 215 M400 290 Q380 245 405 205 M450 290 Q430 250 455 215" fill="none" stroke="#a1a1aa" stroke-width="8" stroke-linecap="round" opacity="0.7"/>`,
    plant: (c) => `
        <ellipse cx="400" cy="330" rx="40" ry="120" fill="#4d7c0f" transform="rotate(-25 400 430)"/><ellipse cx="400" cy="330" rx="40" ry="120" fill="#65a30d" transform="rotate(20 400 430)"/>
        <ellipse cx="400" cy="320" rx="36" ry="125" fill="#84cc16"/><ellipse cx="400" cy="360" rx="34" ry="100" fill="#3f6212" transform="rotate(55 400 450)"/>
        <path d="M290 460 H510 L485 625 H315 Z" fill="${c}"/><rect x="280" y="450" width="240" height="34" rx="10" fill="${shade(c, -25)}"/>`,
    candle: (c) => `
        <path d="M400 230 Q440 280 400 320 Q360 280 400 230 Z" fill="#f59e0b"/><path d="M400 262 Q418 290 400 310 Q382 290 400 262 Z" fill="#fde68a"/>
        <rect x="396" y="315" width="8" height="30" fill="#27272a"/>
        <rect x="290" y="340" width="220" height="270" rx="40" fill="${c}" opacity="0.35"/><rect x="304" y="380" width="192" height="216" rx="30" fill="#fafaf9"/>
        <rect x="320" y="470" width="160" height="70" rx="10" fill="${c}"/>`,
    perfume: (c) => `
        <rect x="355" y="210" width="90" height="70" rx="14" fill="#18181b"/><rect x="375" y="275" width="50" height="60" fill="#d4d4d8"/>
        <rect x="280" y="330" width="240" height="290" rx="48" fill="${c}" opacity="0.85"/><rect x="300" y="350" width="70" height="250" rx="30" fill="#ffffff" opacity="0.25"/>
        <rect x="335" y="450" width="130" height="80" rx="10" fill="#ffffff" opacity="0.9"/><rect x="355" y="478" width="90" height="10" rx="5" fill="#a1a1aa"/>`,
    cream: (c) => `
        <rect x="245" y="365" width="310" height="80" rx="30" fill="${shade(c, -30)}"/>
        <rect x="260" y="430" width="280" height="180" rx="40" fill="${c}"/><rect x="310" y="485" width="180" height="60" rx="12" fill="#ffffff" opacity="0.85"/>`,
    yogamat: (c) => `
        <rect x="200" y="390" width="400" height="170" rx="85" fill="${c}"/>
        <circle cx="285" cy="475" r="85" fill="${shade(c, -25)}"/><circle cx="285" cy="475" r="58" fill="${c}"/><circle cx="285" cy="475" r="30" fill="${shade(c, -25)}"/>
        <path d="M320 560 H600" stroke="${shade(c, -25)}" stroke-width="8"/>`,
    dumbbell: (c) => `
        <rect x="250" y="388" width="300" height="24" rx="12" fill="#a1a1aa"/>
        <rect x="200" y="300" width="60" height="200" rx="18" fill="${c}"/><rect x="250" y="330" width="40" height="140" rx="14" fill="${shade(c, -30)}"/>
        <rect x="540" y="300" width="60" height="200" rx="18" fill="${c}"/><rect x="510" y="330" width="40" height="140" rx="14" fill="${shade(c, -30)}"/>`,
    bottle: (c) => `
        <rect x="360" y="190" width="80" height="60" rx="16" fill="#27272a"/><rect x="345" y="240" width="110" height="30" rx="10" fill="#3f3f46"/>
        <rect x="320" y="260" width="160" height="370" rx="60" fill="${c}"/><rect x="340" y="300" width="36" height="280" rx="18" fill="#ffffff" opacity="0.25"/>`,
    cap: (c) => `
        <path d="M240 470 Q240 300 400 300 Q560 300 560 470 Z" fill="${c}"/><path d="M400 300 V470" stroke="${shade(c, -30)}" stroke-width="6"/>
        <path d="M230 465 H600 Q660 470 640 510 H300 Q240 505 230 465 Z" fill="${shade(c, -35)}"/><circle cx="400" cy="300" r="14" fill="${shade(c, -35)}"/>`,
    cushion: (c) => `
        <path d="M230 260 Q400 300 570 260 Q540 430 570 600 Q400 560 230 600 Q260 430 230 260 Z" fill="${c}"/>
        <path d="M275 305 Q400 335 525 305 Q500 430 525 555 Q400 525 275 555 Q300 430 275 305 Z" fill="none" stroke="#ffffff" stroke-width="6" stroke-dasharray="14 12" opacity="0.6"/>
        <circle cx="400" cy="430" r="14" fill="${shade(c, -30)}"/>`,
};

const backgrounds = ['#f4f1ec', '#eef2f7', '#f3eef6', '#eef5f0', '#f7efe9', '#f1f1ef'];

export const items = [
    ['casque-audio-sans-fil', 'headphones', '#0a1d37'], ['casque-audio-sans-fil-2', 'headphones', '#c54016'],
    ['ecouteurs-bluetooth', 'earbuds', '#4b689f'], ['enceinte-portable', 'speaker', '#2c406a'], ['enceinte-portable-2', 'speaker', '#c54016'],
    ['smartphone', 'smartphone', '#ff6b35'], ['smartphone-2', 'smartphone', '#4b689f'], ['batterie-externe', 'powerbank', '#27272a'],
    ['montre-connectee', 'watch', '#0a1d37'], ['montre-classique', 'watch', '#9c3418'],
    ['baskets-urbaines', 'sneaker', '#0a1d37'], ['baskets-urbaines-2', 'sneaker', '#ff6b35'], ['baskets-running', 'sneaker', '#4b689f'],
    ['tshirt-coton-bio', 'tshirt', '#0a1d37'], ['tshirt-coton-bio-2', 'tshirt', '#e7e5e4'], ['polo-wax', 'tshirt', '#c54016'],
    ['robe-wax', 'dress', '#c54016'], ['robe-ete', 'dress', '#4b689f'],
    ['sac-a-main-cuir', 'handbag', '#9c3418'], ['sac-a-main-cuir-2', 'handbag', '#27272a'], ['sac-a-dos', 'backpack', '#2c406a'], ['sac-a-dos-2', 'backpack', '#57534e'],
    ['lunettes-de-soleil', 'sunglasses', '#c54016'], ['casquette', 'cap', '#0a1d37'],
    ['lampe-design', 'lamp', '#ff8a5c'], ['mug-ceramique', 'mug', '#2c406a'], ['plante-interieur', 'plant', '#e7e5e4'],
    ['bougie-parfumee', 'candle', '#c54016'], ['coussin-decoratif', 'cushion', '#ff8a5c'], ['coussin-decoratif-2', 'cushion', '#4b689f'],
    ['parfum', 'perfume', '#ffa985'], ['parfum-2', 'perfume', '#6d88b8'], ['creme-karite', 'cream', '#fde68a'],
    ['tapis-yoga', 'yogamat', '#6d88b8'], ['halteres', 'dumbbell', '#0a1d37'], ['gourde-isotherme', 'bottle', '#ff6b35'], ['gourde-isotherme-2', 'bottle', '#2c406a'],
];

const svg = (shape, color, bg) => `<!doctype html><html><body style="margin:0">
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">
  <defs><radialGradient id="glow" cx="0.5" cy="0.42" r="0.6"><stop offset="0" stop-color="#ffffff" stop-opacity="0.9"/><stop offset="1" stop-color="#ffffff" stop-opacity="0"/></radialGradient>
  <filter id="soft" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="18" stdDeviation="18" flood-color="#0a1d37" flood-opacity="0.18"/></filter></defs>
  <rect width="800" height="800" fill="${bg}"/><rect width="800" height="800" fill="url(#glow)"/>
  <ellipse cx="400" cy="690" rx="270" ry="30" fill="#0a1d37" opacity="0.08"/>
  <g transform="translate(400 430) scale(1.22) translate(-400 -430)"><g filter="url(#soft)">${shapes[shape](color)}</g></g>
</svg></body></html>`;

const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_PATH });
const page = await browser.newPage({ viewport: { width: 800, height: 800 } });
let i = 0;
for (const [name, shape, color] of items) {
    await page.setContent(svg(shape, color, backgrounds[i++ % backgrounds.length]));
    await page.screenshot({ path: `${OUT}${name}.jpg`, type: 'jpeg', quality: 86 });
}
await browser.close();
console.log(`${items.length} images generated in ${OUT}`);
