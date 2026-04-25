import { test, expect } from '@playwright/test';

test('submit request form', async ({ page }) => {
  await page.goto('http://localhost:8080/index.php');

  await page.selectOption('select[name="department_id"]', '1');
  await page.fill('textarea[name="description"]', 'Test problem description');

  await page.click('button[type="submit"]');

  await expect(page.locator('#success-message')).toBeVisible();
  await expect(page.locator('#task-id')).toContainText('#');
});
