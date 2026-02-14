import asyncio
from playwright.async_api import async_playwright
import os

async def run():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        context = await browser.new_context(viewport={'width': 1280, 'height': 1000})
        page = await context.new_page()

        # 1. Login
        await page.goto('http://localhost:8000/admin/login.php')
        await page.fill('input[name="username"]', 'admin')
        await page.fill('input[name="password"]', 'admin')
        await page.click('button[type="submit"]')
        await page.wait_for_url('**/dashboard.php')

        # 2. Check Help page
        await page.goto('http://localhost:8000/admin/help.php')
        await page.screenshot(path='/home/jules/verification/help_expanded.png', full_page=True)
        print("Captured help_expanded.png")

        # 3. Check Settings page and Import Modal
        await page.goto('http://localhost:8000/admin/settings.php')
        await page.screenshot(path='/home/jules/verification/settings_new.png')

        await page.click('button:has-text("Начать импорт с сайта")')
        await page.wait_for_selector('#import-modal', state='visible')
        await page.screenshot(path='/home/jules/verification/import_modal.png')
        print("Captured import_modal.png")

        # 4. Attempt a mock import (self-parsing for demo)
        await page.fill('input[name="import_url"]', 'http://localhost:8000/admin/help.php')
        await page.click('button:has-text("Запустить парсинг")')
        await page.wait_for_selector('.alert-success, .alert-danger')
        await page.screenshot(path='/home/jules/verification/import_result.png')
        print("Captured import_result.png")

        await browser.close()

if __name__ == '__main__':
    if not os.path.exists('/home/jules/verification'):
        os.makedirs('/home/jules/verification')
    asyncio.run(run())
