import { chromium } from 'playwright';

(async () => {
    console.log('Launching browser...');
    const browser = await chromium.launch({ 
        headless: true,
        executablePath: '/usr/bin/google-chrome'
    });
    const page = await browser.newPage();

    page.on('console', msg => {
        console.log(`[CONSOLE ${msg.type().toUpperCase()}]: ${msg.text()}`);
    });

    page.on('request', request => {
        if (request.url().includes('/api/')) {
            console.log(`[API REQUEST]: ${request.method()} ${request.url()}`);
            if (request.postData()) {
                console.log(`[API REQUEST PAYLOAD]:`, request.postData());
            }
        }
    });

    page.on('response', async response => {
        if (response.url().includes('/api/')) {
            console.log(`[API RESPONSE]: ${response.status()} ${response.url()}`);
            try {
                const text = await response.text();
                console.log(`[API RESPONSE BODY]:`, text.substring(0, 500));
            } catch (e) {}
        }
    });

    try {
        console.log('Navigating to categories page...');
        await page.goto('http://127.0.0.1:8000/catalog/categories');

        if (page.url().includes('/login')) {
            console.log('Redirected to login. Logging in...');
            await page.fill('input[type="email"]', 'admin@example.com');
            await page.fill('input[type="password"]', 'password');
            await page.click('button[type="submit"]');
            await page.waitForTimeout(4000);
        }

        console.log('Waiting for table initialization...');
        await page.waitForSelector('.table tbody tr');

        console.log('Clicking "Add Category" button...');
        // Wait for the button and click
        await page.click('button:has-text("Add Category")');
        await page.waitForTimeout(1000);

        console.log('Filling category form...');
        // Note: the input is inside the categoriesModal, bound to form.name
        await page.fill('#categoriesModal input[x-model="form.name"]', 'Test Category 123');

        console.log('Submitting the form...');
        await page.click('#categoriesModal button[type="submit"]');

        console.log('Waiting for the API response and table reload...');
        await page.waitForTimeout(3000);

        // Check if the newly added category is in the DOM
        const rowText = await page.innerText('.table tbody');
        if (rowText.includes('Test Category 123')) {
            console.log('SUCCESS: "Test Category 123" was successfully saved and rendered in the table!');
            
            // Now, let's test deleting it to keep the DB clean
            console.log('Locating and clicking delete button for the test category...');
            // Click three dots menu for the test category row
            // The row contains "Test Category 123", we can find the tr containing it
            const row = page.locator('.table tbody tr', { hasText: 'Test Category 123' });
            await row.locator('[data-bs-toggle="dropdown"]').click();
            await page.waitForTimeout(500);
            
            // Set up page.onDialog to automatically accept the confirm alert
            page.once('dialog', async dialog => {
                console.log(`[DIALOG]: ${dialog.message()}`);
                await dialog.accept();
            });

            await row.locator('a:has-text("Delete")').click();
            console.log('Waiting for deletion...');
            await page.waitForTimeout(3000);
            console.log('SUCCESS: Deletion request complete!');
        } else {
            console.error('FAILURE: "Test Category 123" is missing from the table!');
        }

    } catch (err) {
        console.error('Error during test:', err);
    } finally {
        await browser.close();
        console.log('Browser closed.');
    }
})();
