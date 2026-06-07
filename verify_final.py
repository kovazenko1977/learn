import asyncio
from playwright.async_api import async_playwright
import os

async def verify():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page(viewport={'width': 1920, 'height': 1080})

        try:
            # Go to the app
            await page.goto('http://localhost:8000', wait_until='networkidle')
            await asyncio.sleep(2) # Give it a moment to settle

            # Capture Desktop
            await page.screenshot(path='/home/jules/verification/desktop_final.png')
            print("Captured desktop_final.png")

            # Open Accommodation from Desktop icon (first icon)
            # Desktop icons have class 'desktop-icon'
            # Let's find the one that says 'Размещение'
            icons = await page.query_selector_all('.desktop-icon')
            for icon in icons:
                text = await icon.inner_text()
                if 'Размещение' in text:
                    await icon.click()
                    break

            await asyncio.sleep(2) # Wait for window to open
            await page.screenshot(path='/home/jules/verification/window_accommodation.png')
            print("Captured window_accommodation.png")

            # Open Start Menu
            await page.click('#start-btn')
            await asyncio.sleep(1)
            await page.screenshot(path='/home/jules/verification/start_menu_final.png')
            print("Captured start_menu_final.png")

        except Exception as e:
            print(f"Error during verification: {e}")
            await page.screenshot(path='/home/jules/verification/error_final.png')
        finally:
            await browser.close()

if __name__ == "__main__":
    os.makedirs('/home/jules/verification', exist_ok=True)
    asyncio.run(verify())
