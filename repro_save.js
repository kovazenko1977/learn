const { chromium } = require('playwright');
const fs = require('fs');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  page.on('console', msg => console.log('PAGE LOG:', msg.text()));
  page.on('pageerror', err => console.log('PAGE ERROR:', err.message));
  page.on('request', request => console.log('REQ >>', request.method(), request.url()));
  page.on('response', response => console.log('RES <<', response.status(), response.url()));

  try {
    await page.goto('http://localhost:8000/index.php');

    // Login
    await page.fill('.code-input input', '123456');
    await page.click('button:has-text("Войти")');
    await page.waitForSelector('h1:has-text("Управление новостями")');

    // Click "Добавить новость"
    await page.click('button:has-text("Добавить новость")');
    await page.waitForSelector('.modal', { state: 'visible' });

    // Fill title
    await page.fill('.form-group input[placeholder="Введите заголовок"]', 'Test News');

    // Fill content via Quill
    await page.evaluate(() => {
        window.newsApp.quill.root.innerHTML = '<p>Test content</p>';
    });

    console.log('Clicking Save...');
    // Click "Сохранить"
    await page.click('.modal-footer button.btn-primary');

    // Wait for toast or modal to close
    try {
        await page.waitForSelector('.toast', { timeout: 5000 });
        console.log('Toast appeared!');
    } catch (e) {
        console.log('Toast did not appear.');
    }

    await page.screenshot({ path: '/home/jules/verification/save_attempt.png' });

  } catch (err) {
    console.error('Test failed:', err);
  } finally {
    await browser.close();
  }
})();
