import asyncio
from playwright.async_api import async_playwright
import subprocess
import time

async def verify():
    # Start PHP server
    php_server = subprocess.Popen(['php', '-S', 'localhost:8000'])
    time.sleep(2)

    async with async_playwright() as p:
        # iPhone 13 Pro Max emulation
        device = p.devices['iPhone 13 Pro Max']
        browser = await p.chromium.launch()
        context = await browser.new_context(**device)
        page = await context.new_page()

        try:
            # 1. Login
            await page.goto('http://localhost:8000')
            await page.fill('#passcode', '111111')
            await page.click('#login-btn')
            await page.wait_for_selector('#main-screen.active')
            print("Login successful")

            # 2. Capture Chat View (Light Theme)
            await page.click('.nav-item[data-view="chat"]')
            await page.wait_for_selector('#chat-view.active')
            await page.screenshot(path='final_light_chat_mobile.png')
            print("Captured Chat Light")

            # 3. Capture Tasks View
            await page.click('.nav-item[data-view="tasks"]')
            await page.wait_for_selector('#tasks-view.active')
            await page.screenshot(path='final_light_tasks_mobile.png')
            print("Captured Tasks Light")

            # 4. Check Desktop View
            await page.set_viewport_size({"width": 1440, "height": 900})
            await page.wait_for_timeout(500)
            await page.screenshot(path='final_light_desktop.png')
            print("Captured Desktop Light")

        except Exception as e:
            print(f"Verification failed: {e}")
        finally:
            await browser.close()
            php_server.terminate()

if __name__ == "__main__":
    asyncio.run(verify())
