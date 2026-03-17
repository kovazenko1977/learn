import asyncio
from playwright.async_api import async_playwright
import os
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

            # 2. Check Chat View and Send Button visibility
            await page.click('.nav-item[data-view="chat"]')
            await page.wait_for_selector('#chat-view.active')

            # Check if send button is visible
            send_btn = await page.wait_for_selector('#send-chat-btn')
            is_visible = await send_btn.is_visible()
            print(f"Chat Send Button visible: {is_visible}")

            # Take screenshot of mobile chat
            await page.screenshot(path='screenshot_chat_mobile.png')

            # 3. Check Settings View
            await page.click('.nav-item[data-view="settings"]')
            await page.wait_for_selector('#settings-view.active')
            print("Settings view accessible")

            # Toggle Light Theme
            await page.select_option('#setting-theme', 'light')
            await page.click('#save-settings-btn')

            # Verify body class
            has_light_theme = await page.evaluate("document.body.classList.contains('theme-light')")
            print(f"Theme toggled to light: {has_light_theme}")

            await page.screenshot(path='screenshot_settings_mobile.png')

        except Exception as e:
            print(f"Verification failed: {e}")
            await page.screenshot(path='error_screenshot.png')
        finally:
            await browser.close()
            php_server.terminate()

if __name__ == "__main__":
    asyncio.run(verify())
