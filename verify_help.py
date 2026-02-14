import asyncio
from playwright.async_api import async_playwright

async def run():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()

        # Login
        await page.goto("http://localhost:8080/sanatorium-booking/admin/login.php")
        await page.fill("input[name='username']", "admin")
        await page.fill("input[name='password']", "admin")
        await page.click("button[type='submit']")

        # Go to Help
        await page.goto("http://localhost:8080/sanatorium-booking/admin/help.php")
        await page.wait_for_selector(".help-content")
        await page.screenshot(path="/home/jules/verification/help_page.png", full_page=True)
        print("Help page screenshot saved.")

        await browser.close()

asyncio.run(run())
