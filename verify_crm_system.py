import sys
import time
from playwright.sync_api import sync_playwright

def run_cuj(page):
    # Navigate to local PHP server running on port 8000
    print("Navigating to http://127.0.0.1:8000 ...")
    page.goto("http://127.0.0.1:8000")
    page.wait_for_timeout(1000)

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

    # Click tasks view link
    print("Navigating to tasks page...")
    page.click("text=Заявки")
    page.wait_for_timeout(1000)

    # Open task creation modal
    print("Opening create task form...")
    page.click("button:has-text('Новая заявка')")
    page.wait_for_timeout(1000)

    # Fill task creation fields
    print("Filling task info...")
    # Form title input selector based on template
    page.fill("input[required]", "Автотест: Ремонт кондиционера")
    page.wait_for_timeout(500)

    # Fill in custom required fields (like Кабинет/Номер)
    # The first custom input is Кабинет/Номер
    page.fill("input[type='text']:near(label:has-text('Кабинет/Номер'))", "Кабинет 305")
    page.wait_for_timeout(500)
    page.fill("input[type='tel']", "+375291234567")
    page.wait_for_timeout(500)

    page.fill("textarea", "Необходимо произвести диагностику и ремонт кондиционера в главном холле.")
    page.wait_for_timeout(500)

    # Submit task form
    print("Submitting task...")
    page.click("button:has-text('Сохранить'), button[type='submit']")
    page.wait_for_timeout(2000)

    # Take screenshot at key moment
    print("Taking verification screenshot...")
    page.screenshot(path="/home/jules/verification/screenshots/verification.png")
    page.wait_for_timeout(1000)
    print("Cuj finished successfully!")

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
            print(f"Error occurred: {e}")
            sys.exit(1)
        finally:
            context.close()
            browser.close()
