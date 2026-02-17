const { chromium } = require('playwright');
const fs = require('fs');

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();

  const baseUrl = 'http://localhost:3000';

  try {
    // 1. Login as Admin
    console.log('Logging in as Admin...');
    await page.goto(`${baseUrl}/login.php`);
    await page.fill('input[name="access_code"]', '123456');
    await page.click('button[type="submit"]');
    await page.waitForURL(`${baseUrl}/index.php`);

    // 2. Add a Specialist
    console.log('Adding a specialist...');
    await page.goto(`${baseUrl}/settings.php?sub=staff`);
    await page.fill('input[name="name"]', 'Тестовый Доктор');
    await page.selectOption('select[name="role"]', 'doctor');
    await page.fill('input[name="specialization"]', 'Терапевт');
    await page.click('button[type="submit"]');
    await page.waitForSelector('text=Сотрудник добавлен');

    // 3. Add a Procedure
    console.log('Adding a procedure...');
    await page.goto(`${baseUrl}/settings.php?sub=procedures`);
    await page.fill('input[name="name"]', 'Тестовая процедура');
    await page.fill('input[name="duration"]', '30');
    await page.fill('input[name="prep_time"]', '10');
    await page.fill('input[name="price"]', '1000');
    await page.click('button[type="submit"]');
    await page.waitForSelector('text=Процедура добавлена');

    // 4. Logout and Login as Doctor
    console.log('Logging in as Doctor...');
    await page.goto(`${baseUrl}/login.php?logout=1`);
    await page.fill('input[name="access_code"]', '101010');
    await page.click('button[type="submit"]');

    // 5. Register a Patient
    console.log('Registering a patient...');
    await page.goto(`${baseUrl}/patients.php`);
    await page.click('button:has-text("Добавить пациента")');
    await page.fill('#addModal input[name="name"]', 'Тестовый Пациент');
    await page.fill('#addModal input[name="birth_date"]', '1990-01-01');
    await page.fill('#addModal input[name="phone"]', '1234567890');
    await page.click('#addModal button:has-text("Сохранить")');
    await page.waitForSelector('text=Тестовый Пациент');

    // 6. Assign Procedure
    console.log('Assigning procedure...');
    await page.click('tr:has-text("Тестовый Пациент") a:has-text("Назначить")');
    await page.selectOption('select[name="procedure_id"]', { label: 'Тестовая процедура' });
    await page.fill('input[name="time"]', '10:00');
    await page.click('button:has-text("Назначить")');
    await page.waitForSelector('text=Назначено');

    // 7. Check Collision
    console.log('Checking collision...');
    // Existing is 10:00 + 30m + 10m = 10:40. So 10:30 should collide.
    await page.fill('input[name="time"]', '10:30');
    await page.click('button:has-text("Назначить")');
    await page.waitForSelector('text=Это время занято');
    console.log('Collision detected correctly.');

    // 8. Login as Cashier and pay
    console.log('Logging in as Cashier...');
    await page.goto(`${baseUrl}/login.php?logout=1`);
    await page.fill('input[name="access_code"]', '202020');
    await page.click('button[type="submit"]');
    await page.goto(`${baseUrl}/procedures_cashier.php`);
    await page.click('tr:has-text("Тестовый Пациент") a:has-text("Карточка")');
    await page.click('button:has-text("Оплатить")');
    await page.waitForSelector('text=Оплачено');

    // 9. Login as Nurse and mark attended
    console.log('Logging in as Nurse...');
    await page.goto(`${baseUrl}/login.php?logout=1`);
    await page.fill('input[name="access_code"]', '303030');
    await page.click('button[type="submit"]');
    await page.goto(`${baseUrl}/procedures_nurse.php`);
    await page.click('tr:has-text("Тестовый Пациент") button:has-text("Отметить прием")');
    await page.waitForSelector('text=Принят в');

    console.log('System-wide test passed!');
    await page.screenshot({ path: '/home/jules/verification/system_test_success.png', fullPage: true });

  } catch (error) {
    console.error('Test failed:', error);
    await page.screenshot({ path: '/home/jules/verification/system_test_error.png', fullPage: true });
    process.exit(1);
  } finally {
    await browser.close();
  }
})();
