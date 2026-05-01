from playwright.sync_api import sync_playwright
import os

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()

        # 1. Login as admin
        page.goto('http://localhost:8000')
        page.fill('input[name="login"]', 'admin')
        page.fill('input[name="password"]', 'admin123')
        page.click('button[type="submit"]')
        page.wait_for_selector('a[data-view="admin"]')

        # 2. Go to Admin view
        page.click('a[data-view="admin"]')
        page.wait_for_selector('#storage-mode')

        # Take screenshot of the new database section
        page.screenshot(path='verification/database_settings.png')

        print("SUCCESS: Database settings section visible")
        browser.close()

if __name__ == '__main__':
    if not os.path.exists('verification'):
        os.makedirs('verification')
    run()
