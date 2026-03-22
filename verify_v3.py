import asyncio
from playwright.async_api import async_playwright

async def run():
    async with async_playwright() as p:
        # Emulate iPhone 13 Pro Max
        device = p.devices['iPhone 13 Pro Max']
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(**device)
        page = await context.new_page()

        # 1. Login
        print("Logging in...")
        await page.goto('http://localhost:8000')
        await page.fill('#username', 'admin')
        await page.fill('#password', '123456')
        await page.click('#auth-btn')
        await page.wait_for_selector('#main-view.active')

        # 2. Navigate to About
        print("Navigating to About...")
        await page.click('button[data-view="about"]')
        await asyncio.sleep(1)

        # 3. Check for Version String
        content = await page.content()
        if 'Версия 3.0 "Полное Обновление"' in content:
            print("SUCCESS: Version 3.0 string found in About view.")
        else:
            print("ERROR: Version 3.0 string NOT found.")

        # 4. Take screenshot
        await page.screenshot(path='v3_about_check.png')
        print("Screenshot saved to v3_about_check.png")

        await browser.close()

if __name__ == "__main__":
    asyncio.run(run())
