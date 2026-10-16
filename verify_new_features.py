import asyncio
from playwright.async_api import async_playwright

async def run():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page(viewport={"width": 390, "height": 844})

        print("Visiting Index page...")
        await page.goto("http://localhost:8090/index.php")
        await page.wait_for_timeout(1000)

        print("Searching...")
        await page.fill("#searchQuery", "Иван")
        await page.click("button[type='submit']")
        await page.wait_for_timeout(1000)

        print("Logging in...")
        await page.goto("http://localhost:8090/user.php")
        await page.fill("#loginPhone", "12345")
        await page.fill("#loginPassword", "12345")
        await page.click("#loginForm button[type='submit']")
        await page.wait_for_timeout(1500)

        print("Current URL:", page.url)
        await page.screenshot(path="verification_mobile.png")
        print("Screenshot saved to verification_mobile.png")
        await browser.close()

asyncio.run(run())
