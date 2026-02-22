import asyncio
from playwright.async_api import async_playwright
import os

async def verify_pwa_banner():
    async with async_playwright() as p:
        # iPhone 12 viewport
        iphone = p.devices['iPhone 12']
        browser = await p.chromium.launch()
        context = await browser.new_context(**iphone)
        page = await context.new_page()

        # Login
        await page.goto("http://localhost:8080/login.php")
        await page.fill('input[name="code"]', "123456")
        await page.click('button[type="submit"]')
        await page.wait_for_url("http://localhost:8080/index.php")

        # Check if the banner exists in the HTML
        banner_exists = await page.locator("#pwa-install-banner").count() > 0
        print(f"PWA Banner exists in DOM: {banner_exists}")

        if banner_exists:
            # Check display style (should be 'none' until event fires)
            display = await page.evaluate("getComputedStyle(document.getElementById('pwa-install-banner')).display")
            print(f"Initial display: {display}")

            # Simulate firing the event or just showing it for verification
            await page.evaluate("document.getElementById('pwa-install-banner').style.display = 'flex'")

            if not os.path.exists("verification"):
                os.makedirs("verification")
            await page.screenshot(path="verification/pwa_banner_mobile.png")
            print("Screenshot saved to verification/pwa_banner_mobile.png")

        await browser.close()
        return banner_exists

if __name__ == "__main__":
    asyncio.run(verify_pwa_banner())
