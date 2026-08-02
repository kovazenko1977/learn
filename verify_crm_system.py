#!/usr/bin/env python3
import asyncio
import os
from playwright.async_api import async_playwright

async def verify_crm():
    print("--- Starting Frontend E2E Playwright Tests ---")
    os.makedirs('/home/jules/verification', exist_ok=True)
    screenshot_path = '/home/jules/verification/crm_dashboard.png'

    async with async_playwright() as p:
        # Launch browser
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(viewport={"width": 1280, "height": 800})
        page = await context.new_page()

        # Listen to console and error events
        page.on("console", lambda msg: print(f"Browser Console: {msg.type}: {msg.text}"))
        page.on("pageerror", lambda err: print(f"Browser Page Error: {err}"))

        # Navigate to home
        print("Navigating to http://127.0.0.1:8000/ ...")
        await page.goto("http://127.0.0.1:8000/")

        # Give it a couple seconds for external scripts to load
        await page.wait_for_timeout(3000)

        # Capture initial page
        print("Captured page screenshot.")
        await page.screenshot(path='/home/jules/verification/crm_initial.png')

        # Fill in login form
        print("Filling in login credentials...")
        await page.fill("input[type='text']", "admin")
        await page.fill("input[type='password']", "admin123")
        await page.click("button[type='submit']")

        # Wait for dashboard view to appear
        print("Waiting for dashboard to load...")
        await page.wait_for_selector("text=Дашборд", timeout=10000)

        # Take a visual verification screenshot of the dashboard
        print(f"Taking dashboard screenshot at: {screenshot_path}")
        await page.screenshot(path=screenshot_path)

        # Click on Tasks tab
        print("Clicking on 'Заявки' (Tasks) tab...")
        await page.click("text=Заявки")
        await page.wait_for_selector("text=Новая заявка", timeout=10000)

        # Capture Kanban board
        await page.screenshot(path='/home/jules/verification/crm_tasks.png')

        # Close browser
        await browser.close()
        print("✓ E2E PLAYWRIGHT TESTS PASSED SUCCESSFULLY!")

if __name__ == "__main__":
    asyncio.run(verify_crm())
