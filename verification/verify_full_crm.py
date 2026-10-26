import time
from playwright.sync_api import sync_playwright

def run_cuj(page):
    page.goto("http://127.0.0.1:8088/")
    page.wait_for_selector("#loginForm")
    page.wait_for_timeout(500)

    # Login
    page.fill("#loginUsername", "admin")
    page.fill("#loginPassword", "admin123")
    page.click("#loginForm button[type='submit']")

    page.wait_for_selector(".page-title")
    page.wait_for_timeout(500)

    # 1. Profile Page
    page.click("#profileMenuBtn")
    page.wait_for_timeout(300)
    page.click("a[data-page='profile']")
    page.wait_for_selector("#profileUpdateForm")
    page.wait_for_timeout(500)

    # 2. Notifications Page
    page.click("#profileMenuBtn")
    page.wait_for_timeout(300)
    page.click("a[data-page='notifications']")
    page.wait_for_selector("#notifSettingsForm")
    page.wait_for_timeout(500)

    # 3. Settings Page (Categories, Priorities, Storage)
    page.click("a[data-page='settings']")
    page.wait_for_selector("#storageSwitchForm")
    page.wait_for_timeout(500)

    # Take screenshot of Admin Settings
    page.screenshot(path="/home/jules/verification/screenshots/verification.png")
    page.wait_for_timeout(1000)

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(
            record_video_dir="/home/jules/verification/videos"
        )
        page = context.new_page()
        try:
            run_cuj(page)
        finally:
            context.close()
            browser.close()
