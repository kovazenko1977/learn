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

    # Navigate to Help & Training Tab
    print("Navigating to Help and Training view...")
    page.click("text=Справка и обучение")
    page.wait_for_timeout(1000)

    # Verify reference manual is displayed
    print("Verifying Справка manual contents...")
    page.wait_for_selector("text=Справка по системе BELHOS")
    page.wait_for_selector("text=Жизненный цикл заявки")
    page.wait_for_timeout(1000)

    # Switch to Training tab
    print("Switching to interactive training simulator...")
    page.click("text=Интерактивное обучение")
    page.wait_for_timeout(1000)

    # Verify simulator step 1 loaded
    page.wait_for_selector("text=Шаг 1: Выберите вашу роль")
    page.wait_for_timeout(500)

    # Choose Responsible Employee role
    print("Selecting Employee role simulator...")
    page.click("text=Сотрудник")
    page.wait_for_timeout(1000)

    # Fill title in simulator
    print("Filling simulator fields...")
    page.fill("input[placeholder='Введите название (например: Поломка лифта)']", "Обучение: Проверка крана")
    page.wait_for_timeout(500)
    page.fill("textarea[placeholder='Введите описание проблемы']", "Кран подтекает в кухонном блоке.")
    page.wait_for_timeout(500)

    # Click Submit in simulator to proceed to quiz (Step 3)
    page.click("button:has-text('Отправить заявку')")
    page.wait_for_timeout(1000)

    # Verify quiz step
    print("Answering the educational quiz...")
    page.select_option("select:near(label:has-text('Какая роль имеет доступ к полным настройкам'))", "Administrator")
    page.wait_for_timeout(500)
    page.select_option("select:near(label:has-text('Генерирует ли СУБД MySQL схемы'))", "yes")
    page.wait_for_timeout(500)

    # Click check answers
    print("Submitting quiz answers...")
    page.click("button:has-text('Проверить ответы')")
    page.wait_for_timeout(1000)

    # Take screenshot of successful simulator completion
    print("Taking verification screenshot of training complete...")
    page.screenshot(path="/home/jules/verification/screenshots/verification.png")
    page.wait_for_timeout(1000)
    print("E2E Playwright Help/Training verification finished successfully!")

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
