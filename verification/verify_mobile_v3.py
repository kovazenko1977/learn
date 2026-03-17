from playwright.sync_api import sync_playwright, expect
import time
import os

def verify_mobile(page):
    # Go to login
    page.goto("http://localhost:8000")

    # Login
    page.fill("#passcode", "111111")
    page.click("#login-btn")

    # Wait for main screen
    expect(page.locator("#main-screen")).to_be_visible()

    # Take screenshot of Mobile Chat
    time.sleep(1)
    page.screenshot(path="verification/mobile_v3_chat.png")

    # Switch to Tasks
    page.click(".nav-item[data-view='tasks']")
    time.sleep(1)
    page.screenshot(path="verification/mobile_v3_tasks.png")

    # Switch to Shopping
    page.click(".nav-item[data-view='shopping']")
    time.sleep(1)
    page.screenshot(path="verification/mobile_v3_shopping.png")

    # Switch to Settings (to see logout button)
    page.click(".nav-item[data-view='settings']")
    time.sleep(1)
    page.screenshot(path="verification/mobile_v3_settings.png")

if __name__ == "__main__":
    if not os.path.exists("verification"):
        os.makedirs("verification")

    with sync_playwright() as p:
        # Emulate iPhone 13
        iphone_13 = p.devices['iPhone 13']
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(**iphone_13)
        page = context.new_page()
        try:
            verify_mobile(page)
        finally:
            browser.close()
