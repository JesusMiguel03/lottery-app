const { chromium } = require('playwright');

(async () => {
    const BASE = 'http://127.0.0.1:8000';
    const OUT = 'screenshots';

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        deviceScaleFactor: 2,
        locale: 'es-ES',
    });
    const page = await context.newPage();

    const shots = [
        { name: '01-landing',         url: '/',                    auth: false, fullPage: true },
        { name: '02-login',           url: '/admin/login',         auth: false, fullPage: false },
        { name: '03-dashboard',       url: '/admin/home-dashboard',auth: true,  fullPage: true },
        { name: '04-clientes',        url: '/admin/clients',       auth: true,  fullPage: true },
        { name: '05-monedas',         url: '/admin/currencies',    auth: true,  fullPage: true },
        { name: '06-rifas',           url: '/admin/lotteries',     auth: true,  fullPage: true },
        { name: '07-rifa-activa',     url: '/admin/lotteries/1',   auth: true,  fullPage: true },
        { name: '08-rifa-finalizada', url: '/admin/lotteries/2',   auth: true,  fullPage: true },
    ];

    let loggedIn = false;

    for (const shot of shots) {
        const url = BASE + shot.url;
        console.log(`Capturing: ${shot.name} -> ${shot.url}`);

        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

        // Login if needed and not yet logged in
        if (shot.auth && !loggedIn) {
            // We should have been redirected to login
            if (page.url().includes('/login')) {
                await page.fill('input[name="email"]', 'admin@demo.com');
                await page.fill('input[name="password"]', 'demo');
                await page.click('button[type="submit"]');
                await page.waitForURL('**/admin/**', { timeout: 15000 });
                await page.waitForTimeout(2000);
                loggedIn = true;
                // Re-navigate to the target page after login
                await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
            }
        }

        // Extra wait for Filament/Livewire to render
        await page.waitForTimeout(2500);

        const path = `${OUT}/${shot.name}.png`;
        await page.screenshot({ path, fullPage: shot.fullPage });
        console.log(`  Saved: ${path}`);
    }

    await browser.close();
    console.log('Done!');
})();
