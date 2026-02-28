import os
import time
import re
from playwright.sync_api import sync_playwright, expect

def verify_system():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        # Desktop
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        try:
            # 1. Login Desktop
            page.goto("http://localhost:3000/login.php")
            page.fill("input[name='code']", "123456")
            page.click("button[type='submit']")
            expect(page).to_have_url("http://localhost:3000/index.php")
            expect(page.locator("#header-clock")).to_be_visible()
            page.screenshot(path="final_desktop_optimized.png")
            print("Desktop verified.")

            # 2. Verify Mobile
            mobile_context = browser.new_context(
                viewport={'width': 375, 'height': 667},
                user_agent="Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1"
            )
            m_page = mobile_context.new_page()
            m_page.goto("http://localhost:3000/mobile/login.php")
            m_page.fill("input[name='access_code']", "123456")
            m_page.click("button[type='submit']")
            expect(m_page).to_have_url("http://localhost:3000/mobile/index.php")
            expect(m_page.locator("#mobile-header-clock")).to_be_visible()
            m_page.screenshot(path="final_mobile_optimized.png")
            print("Mobile verified.")

        except Exception as e:
            print(f"Error: {e}")
        finally:
            browser.close()

if __name__ == "__main__":
    verify_system()
