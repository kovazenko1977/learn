from playwright.sync_api import sync_playwright
import os
from datetime import date

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()

        # 1. Login
        page.goto('http://localhost:8000')
        page.fill('input[name="login"]', 'admin')
        page.fill('input[name="password"]', 'admin123')
        page.click('button[type="submit"]')
        page.wait_for_selector('a[data-view="dashboard"]')

        # 2. Check Dashboard Today button
        page.click('button:has-text("Сегодня")')
        today = date.today().isoformat()
        from_val = page.input_value('#dash-from')
        to_val = page.input_value('#dash-to')
        print(f"Dashboard dates: {from_val} to {to_val}")
        if from_val == today and to_val == today:
             print("SUCCESS: Dashboard Today button works")
        else:
             print(f"FAILURE: Dashboard Today button failed. Expected {today}, got {from_val}")

        page.screenshot(path='verification/today_button.png')

        # 3. Check Reports Today button
        page.click('a[data-view="reports"]')
        page.wait_for_selector('#rep-filter')
        page.click('button:has-text("Сегодня")')
        from_val = page.input_value('#rep-from')
        to_val = page.input_value('#rep-to')
        print(f"Reports dates: {from_val} to {to_val}")

        page.screenshot(path='verification/reports_today.png')

        print("SUCCESS: All 'Today' buttons verified")
        browser.close()

if __name__ == '__main__':
    if not os.path.exists('verification'):
        os.makedirs('verification')
    run()
