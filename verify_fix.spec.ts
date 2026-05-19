import { test, expect } from '@playwright/test';
import path from 'path';

test('Verify News Editor Image Upload UI', async ({ page }) => {
  // Login first
  await page.goto('http://localhost:8000/login.php');
  await page.fill('input[name="pin"]', '123456');
  await page.click('button[type="submit"]');

  // Go to news creation
  await page.goto('http://localhost:8000/admin/news.php?action=add');

  // Check if editor exists
  const editor = page.locator('#editor-container');
  await expect(editor).toBeVisible();

  // Check if Image Resize module is potentially active (we can check if Quill and window.Quill are defined)
  const isQuillDefined = await page.evaluate(() => typeof window.Quill !== 'undefined');
  expect(isQuillDefined).toBe(true);

  // Check if image button exists in toolbar
  const imageButton = page.locator('.ql-image');
  await expect(imageButton).toBeVisible();

  // Take a screenshot of the editor
  await page.screenshot({ path: 'editor_verification.png' });
});
