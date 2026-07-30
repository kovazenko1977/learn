import sys
import time
from playwright.sync_api import sync_playwright

def run_cuj(page):
    # Navigate to local PHP server running on port 8000
    print("Navigating to http://127.0.0.1:8000 ...")
    page.goto("http://127.0.0.1:8000")
    page.wait_for_timeout(1000)

    # Assert PWA manifest exists in HTML
    manifest = page.locator("link[rel='manifest']")
    if manifest.count() > 0:
        print("PWA manifest link found successfully!")
    else:
        print("Error: PWA manifest link not found")
        sys.exit(1)

    # Fill in login form
    print("Logging in as admin...")
    page.fill("input[placeholder='Имя пользователя'], input[type='text']", "admin")
    page.wait_for_timeout(500)
    page.fill("input[placeholder='Пароль'], input[type='password']", "admin123")
    page.wait_for_timeout(500)

    # Click sign in button
    page.click("button:has-text('Войти'), button[type='submit']")
    page.wait_for_timeout(2000)

    # Verify dashboard view has loaded
    print("Checking dashboard elements...")
    page.wait_for_selector("text=Всего заявок")
    page.wait_for_timeout(1000)

    # Navigate to Users view
    print("Navigating to Users view...")
    page.click("text=Пользователи")
    page.wait_for_timeout(1500)
    page.wait_for_selector("text=Справочник пользователей")

    # Navigate to Chat view
    print("Navigating to Chat view...")
    page.click("text=Чат")
    page.wait_for_timeout(1500)
    page.wait_for_selector("text=Общий чат (Viber)")

    # Send a message
    print("Sending message in Viber general chat...")
    page.fill("input[placeholder='Напишите сообщение...']", "Привет всем! Новая система BELHOS CRM запущена!")
    page.wait_for_timeout(500)
    page.click("button[type='submit']")
    page.wait_for_timeout(1500)

    # Verify message appears in scroll
    page.wait_for_selector("text=Привет всем!")

    # Take screenshot of the chat panel
    print("Taking verification screenshot of Viber chat layout...")
    page.screenshot(path="/home/jules/verification/screenshots/verification.png")
    page.wait_for_timeout(1000)
    print("E2E Playwright verification of PWA, Users directory and Viber Chat finished successfully!")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(
            record_video_dir="/home/jules/verification/videos"
        )
        page = context.new_page()
        try:
            run_cuj(page)
        except Exception as e:
            print(f"Error occurred during E2E run: {e}")
            sys.exit(1)
        finally:
            context.close()
            browser.close()
