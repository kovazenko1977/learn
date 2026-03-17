from playwright.sync_api import sync_playwright, expect
import time
import os

def verify_polished_app():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        # Set viewport for mobile to check responsiveness
        context = browser.new_context(viewport={'width': 390, 'height': 844}, user_agent="Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1")
        page = context.new_page()

        # Disable Service Worker to avoid timeouts in headless environment
        page.add_init_script("delete window.navigator.serviceWorker;")

        page.goto("http://localhost:8000")

        # Login
        page.fill("#passcode", "111111")
        page.click("#login-btn")

        # Wait for main screen
        expect(page.locator("#main-screen")).to_be_visible()
        time.sleep(1) # Wait for animations

        # Take mobile home screenshot (Chat)
        page.screenshot(path="verification/v2_mobile_chat.png")

        # Switch to Achievements
        page.click('.nav-item[data-view="achievements"]', force=True)
        time.sleep(0.5)
        page.screenshot(path="verification/v2_mobile_achievements.png")

        # Switch to Settings
        page.click('.nav-item[data-view="settings"]', force=True)
        time.sleep(0.5)
        page.screenshot(path="verification/v2_mobile_settings.png")

        # Check Desktop View
        desktop_page = browser.new_page(viewport={'width': 1280, 'height': 800})
        desktop_page.add_init_script("delete window.navigator.serviceWorker;")
        desktop_page.goto("http://localhost:8000")
        desktop_page.fill("#passcode", "111111")
        desktop_page.click("#login-btn")

        expect(desktop_page.locator("#main-screen")).to_be_visible()
        time.sleep(1)

        # Take Desktop Tasks screenshot
        desktop_page.click('.nav-item[data-view="tasks"]')
        time.sleep(0.5)
        desktop_page.screenshot(path="verification/v2_desktop_tasks.png")

        browser.close()

if __name__ == "__main__":
    if not os.path.exists("verification"):
        os.makedirs("verification")
    verify_polished_app()
