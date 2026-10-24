// Layout audit: opens every page at phone, tablet, laptop and wide-desktop widths,
// takes a screenshot of each, and lists anything that scrolls sideways or looks cut off.
//
// One-time setup (does NOT change package.json):
//   npm install --no-save playwright
//   npx playwright install chromium
//
// Run (PowerShell), with an administrator account so the admin pages are covered:
//   $env:LAYOUT_URL="https://phishcore.test"; $env:LAYOUT_EMAIL="you@example.com"; $env:LAYOUT_PASSWORD="your-password"; node layout-audit.mjs
//
// Optional: node layout-audit.mjs dashboard analytics   (only pages whose name contains one of these words)
//
// Output: storage/app/layout-audit/report.md plus one screenshot per page and width.
// It only opens pages (GET) and signs in. It does not submit any other form.

import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const BASE = (process.env.LAYOUT_URL || 'https://phishcore.test').replace(/\/$/, '');
const EMAIL = process.env.LAYOUT_EMAIL || '';
const PASSWORD = process.env.LAYOUT_PASSWORD || '';
const OUT = process.env.LAYOUT_OUT || join('storage', 'app', 'layout-audit');

const WIDTHS = [
    { name: 'phone', width: 360, height: 740 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'laptop', width: 1024, height: 768 },
    { name: 'desktop', width: 1440, height: 900 },
];

const PUBLIC_PAGES = ['/', '/scan', '/public-reports', '/terms', '/privacy', '/login'];
const SIGNED_IN_PAGES = [
    '/dashboard', '/scan-history', '/analytics', '/investigations', '/reports', '/notifications',
    '/chat', '/play', '/profile', '/scam-news', '/reported-numbers', '/safe-files', '/user-management',
];

const only = process.argv.slice(2).map((w) => w.toLowerCase());
const wanted = (path) => only.length === 0 || only.some((w) => path.toLowerCase().includes(w));
const slug = (path) => (path === '/' ? 'home' : path.replace(/^\//, '').replace(/[^a-z0-9]+/gi, '-')).slice(0, 60);

/** Runs inside the page. Returns what scrolls sideways and what looks cut off. */
function inspectPage() {
    const vw = document.documentElement.clientWidth;

    const describe = (el) => {
        const cls = (el.getAttribute('class') || '').split(/\s+/).filter(Boolean).slice(0, 4).join('.');
        const text = (el.innerText || el.value || '').replace(/\s+/g, ' ').trim().slice(0, 40);
        return `${el.tagName.toLowerCase()}${el.id ? '#' + el.id : ''}${cls ? '.' + cls : ''}${text ? ` "${text}"` : ''}`;
    };

    // Slide-out menus sit off-screen on purpose, so anything inside a fixed panel is skipped.
    const insideFixed = (el) => {
        for (let p = el; p && p !== document.body; p = p.parentElement) {
            if (getComputedStyle(p).position === 'fixed') { return true; }
        }
        return false;
    };

    // Items still waiting for their "reveal when you scroll" animation are not in their final place yet.
    // Cards inside a swipe row only animate in when swiped into view, and this script cannot swipe, so they are ignored.
    const inSwipeRow = (node) => {
        for (let p = node.parentElement; p && p !== document.body; p = p.parentElement) {
            const ox = getComputedStyle(p).overflowX;
            if (ox === 'auto' || ox === 'scroll') { return true; }
        }
        return false;
    };
    const stillAnimating = (el) => {
        const waitingParent = el.closest('.reveal, [data-fx]');
        return waitingParent !== null && ! inSwipeRow(waitingParent);
    };
    const waiting = new Set();

    const visible = (el, rect) => {
        const s = getComputedStyle(el);
        return rect.width > 0 && rect.height > 0 && s.display !== 'none' && s.visibility !== 'hidden'
            && s.opacity !== '0' && s.position !== 'fixed' && !el.closest('[aria-hidden="true"], .sr-only, [hidden]');
    };

    const sideways = [];
    const cutOff = [];
    const pageScrolls = document.documentElement.scrollWidth > vw + 1;

    for (const el of document.body.querySelectorAll('*')) {
        const rect = el.getBoundingClientRect();
        if (! visible(el, rect) || insideFixed(el)) { continue; }
        if (stillAnimating(el)) { waiting.add(el.closest('.reveal, [data-fx]')); continue; }

        // Walk up: is something between this element and the page scrolling or clipping it?
        let scrollsInside = false;
        let clipBox = null;
        for (let p = el.parentElement; p && p !== document.body && p !== document.documentElement; p = p.parentElement) {
            const ox = getComputedStyle(p).overflowX;
            if (ox === 'auto' || ox === 'scroll') { scrollsInside = true; break; }
            if ((ox === 'hidden' || ox === 'clip') && ! clipBox) { clipBox = p.getBoundingClientRect(); }
        }
        if (scrollsInside) { continue; }

        const isContent = el.matches('a, button, input, select, textarea, h1, h2, h3, h4, h5, h6, p, label, td, th, li, img, svg');
        if (! isContent && ! pageScrolls) { continue; }

        if (! clipBox && (rect.right > vw + 1 || rect.left < -1)) {
            sideways.push({ what: describe(el), by: Math.round(Math.max(rect.right - vw, -rect.left)) });
        } else if (clipBox && getComputedStyle(el).textOverflow !== 'ellipsis' && (rect.right > clipBox.right + 2 || rect.left < clipBox.left - 2)) {
            cutOff.push({ what: describe(el) + ` [element ${Math.round(rect.left)}..${Math.round(rect.right)}px, parent box ${Math.round(clipBox.left)}..${Math.round(clipBox.right)}px]`, by: Math.round(Math.max(rect.right - clipBox.right, clipBox.left - rect.left)) });
        }
    }

    const root = document.documentElement;
    const small = [...document.querySelectorAll('a, button, input:not([type=hidden]), select, textarea, [role=button]')]
        .filter((el) => { const r = el.getBoundingClientRect(); return visible(el, r) && (r.width < 28 || r.height < 28) && ! el.closest('label'); })
        .map(describe);

    return {
        pageScrollsSideways: root.scrollWidth > vw + 1,
        pageWidth: root.scrollWidth,
        viewport: vw,
        sideways: sideways.sort((a, b) => b.by - a.by).slice(0, 6),
        cutOff: cutOff.sort((a, b) => b.by - a.by).slice(0, 6),
        smallTargets: small.slice(0, 6),
        smallTargetCount: small.length,
        neverRevealed: [...waiting].slice(0, 4).map(describe),
    };
}

async function signIn(context) {
    const page = await context.newPage();
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    await page.fill('#login-email', EMAIL);
    await page.fill('#login-password', PASSWORD);
    await Promise.all([
        page.waitForURL((url) => ! url.pathname.startsWith('/login'), { timeout: 20000 }).catch(() => {}),
        page.press('#login-password', 'Enter'),
    ]);
    const ok = ! new URL(page.url()).pathname.startsWith('/login');
    await page.close();
    return ok;
}

async function firstScanResultPath(context) {
    const page = await context.newPage();
    try {
        await page.goto(`${BASE}/scan-history`, { waitUntil: 'domcontentloaded' });
        const hrefs = await page.$$eval('a[href]', (as) => as.map((a) => a.getAttribute('href')));
        for (const href of hrefs) {
            const path = new URL(href, BASE).pathname;
            if (/^\/scan\/[^/]+$/.test(path)) { return path; }
        }
    } catch { /* no scans yet */ } finally { await page.close(); }
    return null;
}

mkdirSync(OUT, { recursive: true });
const browser = await chromium.launch();
const results = [];

try {
    const probe = await browser.newContext({ ignoreHTTPSErrors: true });
    let signedIn = false;
    let pages = PUBLIC_PAGES.filter(wanted);
    let signedInContextState = null;

    if (EMAIL && PASSWORD) {
        signedIn = await signIn(probe);
        if (signedIn) {
            signedInContextState = await probe.storageState();
            const scanPath = await firstScanResultPath(probe);
            pages = [...pages, ...SIGNED_IN_PAGES.filter(wanted), ...(scanPath && wanted('scan-result') ? [scanPath] : [])];
        } else {
            console.error('Could not sign in. Check LAYOUT_EMAIL and LAYOUT_PASSWORD. Auditing public pages only.');
        }
    } else {
        console.error('LAYOUT_EMAIL / LAYOUT_PASSWORD not set. Auditing public pages only.');
    }
    await probe.close();

    for (const path of pages) {
        const needsLogin = ! PUBLIC_PAGES.includes(path);
        for (const size of WIDTHS) {
            const context = await browser.newContext({
                ignoreHTTPSErrors: true,
                viewport: { width: size.width, height: size.height },
                ...(needsLogin && signedInContextState ? { storageState: signedInContextState } : {}),
            });
            const page = await context.newPage();
            let status = 'ok';
            try {
                const response = await page.goto(BASE + path, { waitUntil: 'networkidle', timeout: 30000 }).catch(() => page.goto(BASE + path, { waitUntil: 'domcontentloaded', timeout: 30000 }));
                if (response && response.status() >= 400) { status = `HTTP ${response.status()}`; }
                await page.waitForTimeout(900); // let the entrance animations finish
                // Scroll to the bottom and back so "reveal when you scroll" animations have finished too.
                await page.evaluate(async () => {
                    const pause = (ms) => new Promise((r) => setTimeout(r, ms));
                    const step = Math.max(200, window.innerHeight * 0.4); // small steps so every item is seen by the page
                    const jump = (y) => window.scrollTo({ top: y, behavior: 'instant' }); // the site uses smooth scrolling, which is too slow here
                    const bottom = () => document.documentElement.scrollHeight;
                    for (let y = 0; y < bottom(); y += step) { jump(y); await pause(250); }
                    jump(bottom());
                    await pause(400);
                    for (let y = bottom(); y > 0; y -= step) { jump(y); await pause(120); }
                    jump(0);
                    await pause(900);
                });
                // Wait (up to 4 seconds) until no element is still waiting to animate in.
                await page.waitForFunction(() => document.querySelectorAll('.reveal').length === 0, null, { timeout: 4000 }).catch(() => {});
                const data = await page.evaluate(inspectPage);
                await page.screenshot({ path: join(OUT, `${slug(path)}-${size.name}-${size.width}.png`), fullPage: true });
                results.push({ path, size, status, ...data });
            } catch (error) {
                results.push({ path, size, status: `failed: ${String(error.message).split('\n')[0]}`, sideways: [], cutOff: [], smallTargets: [], smallTargetCount: 0 });
            }
            await context.close();
        }
        process.stdout.write('.');
    }
} finally {
    await browser.close();
}

const problems = results.filter((r) => r.pageScrollsSideways || r.sideways.length || r.cutOff.length || (r.neverRevealed || []).length || r.status !== 'ok');
const lines = [
    '# Layout audit',
    '',
    `Site: ${BASE}. Pages checked: ${new Set(results.map((r) => r.path)).size}. Checks run: ${results.length}. Checks with a problem: ${problems.length}.`,
    '',
    'Widths: ' + WIDTHS.map((w) => `${w.name} ${w.width}px`).join(', ') + '.',
    '',
];

if (problems.length === 0) {
    lines.push('No sideways scrolling or cut-off content found.');
}
for (const r of problems) {
    lines.push(`## ${r.path} at ${r.size.name} (${r.size.width}px)`);
    if (r.status !== 'ok') { lines.push(`- Status: ${r.status}`); }
    if (r.pageScrollsSideways) { lines.push(`- The whole page scrolls sideways: content is ${r.pageWidth}px wide on a ${r.viewport}px screen.`); }
    for (const s of r.sideways) { lines.push(`- Sticks out by ${s.by}px: ${s.what}`); }
    for (const w of r.neverRevealed || []) { lines.push(`- Still waiting to animate in after scrolling (check the screenshot, it may look blank or shifted): ${w}`); }
    for (const c of r.cutOff) { lines.push(`- May be cut off by ${c.by}px (a parent hides the overflow): ${c.what}`); }
    lines.push('');
}

const tight = results.filter((r) => r.size.width === 360 && r.smallTargetCount > 0);
if (tight.length) {
    lines.push('## Small tap targets on the phone (under 28px), for information only');
    for (const r of tight) { lines.push(`- ${r.path}: ${r.smallTargetCount}, for example ${r.smallTargets.slice(0, 3).join(' | ')}`); }
    lines.push('');
}

writeFileSync(join(OUT, 'report.md'), lines.join('\n'));
console.log(`\n${lines.slice(0, 5).join('\n')}\n\nFull report: ${join(OUT, 'report.md')}`);
