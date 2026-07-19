const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage({ viewport: { width: 1440, height: 1400 } });
    await page.addInitScript(() => { localStorage.setItem('pb-welcomed', '1'); });
    page.on('pageerror', (err) => console.log('PAGE ERROR:', err.message));

    await page.goto('http://127.0.0.1:8123/login', { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="email"]', 'kofi@example.com');
    await page.fill('input[name="password"]', 'TempCheck!2026');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard**', { timeout: 15000 });
    await page.waitForTimeout(500);

    // Sidebar badges.
    await page.locator('nav, aside').first().screenshot({ path: 'C:\\Users\\HP\\momo-alipay\\shot_sidebar.png' }).catch(async () => {
        await page.screenshot({ path: 'C:\\Users\\HP\\momo-alipay\\shot_sidebar_full.png' });
    });

    // Learning center: chips + cards + Start here badge.
    await page.goto('http://127.0.0.1:8123/learn', { waitUntil: 'networkidle' });
    await page.waitForTimeout(700);
    await page.screenshot({ path: 'C:\\Users\\HP\\momo-alipay\\shot_learn_index.png', clip: { x: 0, y: 0, width: 1440, height: 700 } });

    // Guide show page: tip callout icon.
    await page.goto('http://127.0.0.1:8123/learn/how-to-buy-from-1688-wholesale-sourcing', { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    const tipBox = page.locator('text=Tip:').first();
    await tipBox.scrollIntoViewIfNeeded();
    await page.waitForTimeout(200);
    await page.screenshot({ path: 'C:\\Users\\HP\\momo-alipay\\shot_tip.png', clip: { x: 200, y: 0, width: 900, height: 900 } });

    console.log('done');
    await browser.close();
})();
