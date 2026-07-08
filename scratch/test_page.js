import { chromium } from 'playwright';

(async () => {
    console.log('Launching browser...');
    const browser = await chromium.launch({ 
        headless: true,
        executablePath: '/usr/bin/google-chrome'
    });
    const page = await browser.newPage();

    // Listen to console logs
    page.on('console', msg => {
        console.log(`[CONSOLE ${msg.type().toUpperCase()}]: ${msg.text()}`);
    });

    // Listen to network requests/responses
    page.on('request', request => {
        if (request.url().includes('/api/')) {
            console.log(`[API REQUEST]: ${request.method()} ${request.url()}`);
        }
    });

    page.on('response', async response => {
        if (response.url().includes('/api/')) {
            console.log(`[API RESPONSE]: ${response.status()} ${response.url()}`);
            try {
                const text = await response.text();
                console.log(`[API RESPONSE BODY]:`, text.substring(0, 500));
            } catch (e) {
                console.log('[API RESPONSE BODY ERROR]: Could not read body');
            }
        }
    });

    try {
        console.log('Navigating to categories page...');
        await page.goto('http://127.0.0.1:8000/catalog/categories');

        // Check if we are on the login page
        if (page.url().includes('/login')) {
            console.log('Redirected to login. Logging in...');
            await page.fill('input[type="email"]', 'admin@example.com');
            await page.fill('input[type="password"]', 'password');
            await page.click('button[type="submit"]');
            await page.waitForTimeout(5000);
            console.log('Post-login URL:', page.url());
        }

        // Wait extra time for the page to initialize and API requests to finish
        await page.waitForTimeout(5000);

        console.log('Page Title:', await page.title());
        const managementText = await page.innerText('.categories-management');
        console.log('Management container text length:', managementText.length);
        console.log('Management container text:', managementText.substring(0, 500));

        // Dump the Alpine components data using Alpine v3 APIs
        const categoriesTableData = await page.evaluate(() => {
            const el = document.querySelector('.categories-management');
            if (el && window.Alpine) {
                const data = window.Alpine.$data(el);
                // Serialize all keys of data
                const keys = Object.keys(data);
                const serialized = {};
                for (const key of keys) {
                    if (typeof data[key] !== 'function') {
                        serialized[key] = data[key];
                    } else {
                        serialized[key] = '[Function]';
                    }
                }
                return {
                    hasAlpine: true,
                    keys: keys,
                    serialized: serialized
                };
            }
            return { hasAlpine: false, elExists: !!el, hasGlobalAlpine: !!window.Alpine };
        });
        console.log('Alpine Categories Table Data:', JSON.stringify(categoriesTableData, null, 2));

    } catch (err) {
        console.error('Error during test:', err);
    } finally {
        await browser.close();
        console.log('Browser closed.');
    }
})();
