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

    # Navigate to Logical Tools view
    print("Navigating to Logical Tools view...")
    page.click("text=Инструменты")
    page.wait_for_timeout(1000)

    # Verify logical tools tab exists and list of 30 utilities are present
    print("Verifying Tools panel...")
    page.wait_for_selector("text=Полнофункциональный набор")
    page.wait_for_selector("text=Калькулятор SLA")
    page.wait_for_selector("text=Водный трекер")
    page.wait_for_timeout(500)

    # Let's interact with Temperature Converter (id 2)
    print("Switching to temperature converter...")
    page.click("text=2. Конвертер температур")
    page.wait_for_timeout(1000)
    page.wait_for_selector("text=Шкала Фаренгейта")

    # Let's interact with Water Tracker (id 8)
    print("Switching to water tracker...")
    page.click("text=8. Водный трекер")
    page.wait_for_timeout(1000)
    page.click("button:has-text('+250 мл')")
    page.wait_for_timeout(1000)
    page.wait_for_selector("text=250 мл")

    # Take screenshot of the logical tools panel
    print("Taking verification screenshot of tools layout...")
    page.screenshot(path="/home/jules/verification/screenshots/verification.png")
    page.wait_for_timeout(1000)
    print("E2E Playwright verification of PWA and Tools finished successfully!")

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
