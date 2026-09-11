import asyncio
from playwright.async_api import async_playwright
import subprocess
import time

async def main():
    # Start server
    proc = subprocess.Popen(["node", "backend/index.js"])
    time.sleep(3)

    try:
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True)
            page = await browser.new_page()

            # Go to app
            await page.goto("http://localhost:5000", wait_until="networkidle")

            # Fill login
            await page.fill('input[placeholder="admin"]', "admin")
            await page.fill('input[placeholder="••••••••"]', "admin123")
            await page.click('button[type="submit"]')
            await page.wait_for_timeout(1000)

            # Check dashboard page elements
            await page.wait_for_selector('text=Дашборд')

            # Navigate to Tickets page
            await page.click('text=Заявки')
            await page.wait_for_timeout(1000)

            # Navigate to Form Builder
            await page.click('text=Конструктор форм')
            await page.wait_for_timeout(1000)

            # Navigate to Settings
            await page.click('text=Настройки')
            await page.wait_for_timeout(1000)

            # Take screenshot
            await page.screenshot(path="verification_crm.png", full_page=True)
            print("Successfully verified UI and saved screenshot to verification_crm.png")

            await browser.close()
    finally:
        proc.terminate()
        proc.wait()

if __name__ == "__main__":
    asyncio.run(main())
