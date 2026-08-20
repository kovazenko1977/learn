import asyncio
import socket
import subprocess
import time
from playwright.async_api import async_playwright

async def main():
    # Run backend from within backend directory so path.resolve('public') resolves correctly
    proc = subprocess.Popen(["node", "index.js"], cwd="backend")
    time.sleep(2)

    try:
        async with async_playwright() as p:
            browser = await p.chromium.launch(
                executable_path="/usr/bin/google-chrome",
                args=["--no-sandbox", "--disable-setuid-sandbox"]
            )
            page = await browser.new_page()
            await page.set_viewport_size({"width": 1280, "height": 800})

            # Go to app
            await page.goto("http://localhost:5000", wait_until="networkidle")

            # Fill credentials
            await page.fill('input[placeholder="admin"]', 'admin')
            await page.fill('input[placeholder="••••••••"]', 'admin123')
            await page.click('button[type="submit"]')

            # Wait for dashboard UI elements
            await page.wait_for_timeout(2000)

            # Take screenshot of Dashboard
            screenshot_path = "dashboard_verification.png"
            await page.screenshot(path=screenshot_path)
            print(f"Dashboard screenshot saved: {screenshot_path}")

            await browser.close()
    finally:
        proc.terminate()

if __name__ == "__main__":
    asyncio.run(main())
