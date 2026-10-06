import { test, expect } from '@playwright/test';

test.describe('Flower Studio Pro PWA End-to-End Verification', () => {
  test('Complete user flow and admin panel testing', async ({ page }) => {
    // 1. Visit Main Page & Auth
    await page.goto('http://127.0.0.1:8080');
    await page.waitForTimeout(1000);

    // Login as pre-seeded Admin 1111 / 1111
    await page.fill('#auth-phone', '1111');
    await page.fill('#auth-password', '1111');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(1000);

    // Verify Main Catalog is active
    const mainTitle = await page.textContent('.brand-title');
    expect(mainTitle).toContain('Flower Studio');

    // 2. Test Assistant FAB & Selection Wizard
    await page.click('.fab-assistant');
    await page.waitForSelector('#assistant-overlay', { state: 'visible' });
    await page.click('button:has-text("День рождения")');
    await page.waitForTimeout(300);
    await page.click('button:has-text("До 70 BYN")');
    await page.waitForTimeout(300);
    await page.click('button:has-text("Нежная / Пастельная")');
    await page.waitForTimeout(300);
    await page.click('button:has-text("Компактный и милый")');
    await page.waitForTimeout(1000); // Recommendation typing delay

    await page.click('button:has-text("В корзину")');
    await page.waitForTimeout(500);

    // 3. Test Roulette Widget
    await page.click('button:has-text("Испытать удачу 🎰")');
    await page.waitForTimeout(500);

    // 4. Test Cart & Checkout
    await page.click('.nav-item:has-text("Корзина")');
    await page.waitForTimeout(500);

    await page.click('button:has-text("Оформить заказ ➔")');
    await page.waitForTimeout(500);

    // Verify Leaflet Map container exists
    const mapVisible = await page.isVisible('#leaflet-map');
    expect(mapVisible).toBeTruthy();

    await page.fill('#checkout-address', 'г. Минск, пр. Независимости, 15');
    await page.fill('#checkout-promocode', 'FIRST15');
    await page.click('button:has-text("Применить")');
    await page.waitForTimeout(500);

    await page.click('button:has-text("Подтвердить и оформить")');
    await page.waitForTimeout(1000);

    // Check Order Created Modal
    const orderModalVisible = await page.isVisible('#order-success-modal');
    expect(orderModalVisible).toBeTruthy();

    await page.click('button:has-text("Посмотреть в профиле")');
    await page.waitForTimeout(500);

    // 5. Test Profile & Support Chat
    await page.click('button:has-text("Открыть админку ➔")');
    await page.waitForTimeout(500);

    // 6. Test Admin Panel Subtabs
    const subtabs = ['📊 Статистика', '🌸 Товары', '🎁 Акции', '📰 Новости', '🏷 Промокоды', '📦 Заказы', '💬 Чаты', '👥 Клиенты', '🔧 Настройки', '💾 База данных'];

    for (const subtab of subtabs) {
      await page.click(`.admin-nav-item:has-text("${subtab}")`);
      await page.waitForTimeout(300);
    }

    console.log('E2E Playwright verification passed successfully!');
  });
});
