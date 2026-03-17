import asyncio
from playwright.async_api import async_playwright
import os
import time

async def run_verification():
    async with async_playwright() as p:
        # Launch browser
        browser = await p.chromium.launch()

        # Test Mobile View
        context_mobile = await browser.new_context(
            viewport={'width': 390, 'height': 844},
            user_agent='Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Mobile/15E148 Safari/604.1'
        )
        page_mobile = await context_mobile.new_page()

        # Login
        await page_mobile.goto('http://localhost:8000')
        await page_mobile.fill('#passcode', '123456')
        await page_mobile.click('#login-btn')

        # Wait for chat
        await page_mobile.wait_for_selector('#chat-view.active')
        await page_mobile.screenshot(path='verification/mobile_chat.png')

        # Click Tasks (Dela)
        await page_mobile.click('.nav-item[data-view="tasks"]')
        await page_mobile.wait_for_selector('#tasks-view.active')
        await page_mobile.screenshot(path='verification/mobile_tasks.png')

        # Click Achievements (Uspekhi)
        await page_mobile.click('.nav-item[data-view="achievements"]')
        await page_mobile.wait_for_selector('#achievements-view.active')
        await page_mobile.screenshot(path='verification/mobile_achievements.png')

        # Click Settings
        await page_mobile.click('.nav-item[data-view="settings"]')
        await page_mobile.wait_for_selector('#settings-view.active')
        await page_mobile.screenshot(path='verification/mobile_settings.png')

        # Test Desktop View
        context_desktop = await browser.new_context(viewport={'width': 1280, 'height': 800})
        page_desktop = await context_desktop.new_page()
        await page_desktop.goto('http://localhost:8000')
        await page_desktop.fill('#passcode', '123456')
        await page_desktop.click('#login-btn')
        await page_desktop.wait_for_selector('#chat-view.active')
        await page_desktop.screenshot(path='verification/desktop_chat.png')

        await browser.close()

if __name__ == "__main__":
    asyncio.run(run_verification())
