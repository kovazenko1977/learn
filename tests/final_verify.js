const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();

  // 1. Visit Login
  await page.goto('http://localhost:8080/admin.php');
  await page.fill('input[type="text"]', '1');
  await page.fill('input[type="password"]', '1');
  await page.click('button[type="submit"]');

  await page.waitForSelector('text=Аналитика');
  console.log('Login successful');

  // 2. Open Task Modal and check Tabs
  await page.click('text=Заявки');
  await page.waitForTimeout(1000);

  const firstTask = await page.locator('.card.cursor-pointer').first();
  if (await firstTask.isVisible()) {
      await firstTask.click();
      await page.waitForSelector('text=ДЕТАЛИ');
      await page.waitForSelector('text=ЧАТ С ЗАКАЗЧИКОМ');
      console.log('Task modal tabs verified');

      await page.click('text=ЧАТ С ЗАКАЗЧИКОМ');
      await page.fill('placeholder=Введите сообщение...', 'Тестовое сообщение в чат');
      await page.click('button:has(.lucide-send)');
      console.log('Chat message sent');
  } else {
      console.log('No tasks found to test modal');
  }

  // 3. Check Constructor Templates
  await page.click('text=Конструктор');
  await page.waitForSelector('text=Готовые шаблоны');
  console.log('Form templates section verified');

  await page.screenshot({ path: 'verification_admin.png', fullPage: true });
  await browser.close();
})();
