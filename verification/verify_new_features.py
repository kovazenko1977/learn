from playwright.sync_api import sync_playwright
import os

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()

        # 1. Login
        page.goto('http://localhost:8000')
        page.fill('input[name="login"]', 'admin')
        page.fill('input[name="password"]', 'admin123')
        page.click('button[type="submit"]')
        page.wait_for_selector('#app-content')

        # 2. Set Announcement
        page.click('a[data-view="admin"]')
        page.wait_for_selector('#ann-text')
        page.fill('#ann-text', 'ВНИМАНИЕ: Техработы в субботу!')

        # We need to handle the alert
        page.on("dialog", lambda dialog: dialog.accept())
        page.click('button:has-text("Ок")')

        # 3. Verify on reload
        page.reload()
        page.wait_for_selector('#announcement-banner:not(.hidden)')
        page.screenshot(path='verification/announcement_final.png')

        print("SUCCESS: Announcement banner verified")
        browser.close()

if __name__ == '__main__':
    if not os.path.exists('verification'):
        os.makedirs('verification')
    run()
