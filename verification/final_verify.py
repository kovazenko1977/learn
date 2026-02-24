from playwright.sync_api import Page, expect, sync_playwright
import os

def test_final(page: Page):
    page.goto("http://localhost:8080/login.php")
    page.screenshot(path="verification/final_login.png")

    page.fill('input[name="username"]', "admin")
    page.fill('input[name="password"]', "admin")
    page.click('button.btn-submit')

    expect(page).to_have_url("http://localhost:8080/index.php")
    page.screenshot(path="verification/final_dashboard.png", full_page=True)

    # Go to logs to see if they are empty (cleaned up)
    page.goto("http://localhost:8080/logs.php")
    page.screenshot(path="verification/final_logs.png")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        try:
            test_final(page)
        finally:
            browser.close()
