import { test, expect } from '@playwright/test';
import fs from 'fs';

test.describe('B2B Vitrina Flow', () => {
    test('Admin should add product and client', async ({ page }) => {
        await page.goto('http://localhost:8000');

        // Login as admin
        await page.fill('input[name="username"]', 'admin');
        await page.fill('input[name="password"]', 'admin123');
        await page.click('button[type="submit"]');

        await expect(page.locator('text=Administrator')).toBeVisible();

        // Add Product
        await page.click('[data-action="add-product"]');
        await page.fill('input[name="name"]', 'Test Product');
        await page.fill('textarea[name="description"]', 'Description here');
        await page.fill('input[name="price"]', '1000');
        await page.check('input[name="sufficient"]');
        await page.click('button:text("Save")');

        await expect(page.locator('text=Test Product')).toBeVisible();
        await expect(page.locator('text=Sufficient')).toBeVisible();

        // Add Client
        await page.click('[data-action="view-clients"]');
        await page.click('[data-action="add-client"]');
        await page.fill('input[name="name"]', 'Test Client LLC');
        await page.fill('input[name="username"]', 'client1');
        await page.fill('input[name="password"]', 'pass123');
        await page.fill('textarea[name="details"]', 'INN 123456789');
        await page.click('button:text("Save")');

        await expect(page.locator('text=Test Client LLC')).toBeVisible();

        // Logout
        await page.click('[data-action="logout"]');
    });

    test('Client should place order and admin should export', async ({ page }) => {
        await page.goto('http://localhost:8000');

        // Login as client
        await page.fill('input[name="username"]', 'client1');
        await page.fill('input[name="password"]', 'pass123');
        await page.click('button[type="submit"]');

        await expect(page.locator('text=Test Client LLC')).toBeVisible();

        // Add to cart
        await page.click('[data-action="add-to-cart"]');
        await page.click('[data-action="place-order"]');

        await page.on('dialog', dialog => dialog.accept());

        await expect(page.locator('text=Order #')).toBeVisible();

        // Logout
        await page.click('[data-action="logout"]');

        // Admin export check
        await page.fill('input[name="username"]', 'admin');
        await page.fill('input[name="password"]', 'admin123');
        await page.click('button[type="submit"]');

        await page.click('[data-action="view-orders"]');
        await expect(page.locator('[data-action="export-1c"]')).toBeVisible();

        const [ download ] = await Promise.all([
            page.waitForEvent('download'),
            page.click('[data-action="export-1c"]')
        ]);

        expect(download.suggestedFilename()).toContain('order_');
    });
});
