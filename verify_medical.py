from playwright.sync_api import sync_playwright, expect
import time
import os

def verify_system():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context()
        page = context.new_page()

        base_url = "http://localhost:3000"

        try:
            # 1. Login as Admin
            print("Logging in as Admin...")
            page.goto(f"{base_url}/login.php")
            page.fill('input[name="code"]', "123456")
            page.click('button[type="submit"]')
            expect(page).to_have_url(f"{base_url}/index.php")

            # 2. Add Procedure
            print("Adding procedure...")
            page.goto(f"{base_url}/settings.php?sub=procedures")
            page.fill('input[name="name"]', "ЭКГ")
            page.fill('input[name="duration"]', "15")
            page.fill('input[name="prep_time"]', "5")
            page.fill('input[name="price"]', "500")
            page.check('input[name="assigned_staff[]"]:first-child')
            page.click('button:has-text("Добавить")')
            expect(page.get_by_text("Процедура добавлена")).to_be_visible()

            # 3. Register Patient
            print("Registering Patient...")
            page.goto(f"{base_url}/patients.php")
            page.click('button:has-text("Добавить пациента")')
            page.fill('#addModal input[name="name"]', "Петров Петр Петрович")
            page.fill('#addModal input[name="birth_date"]', "1985-05-05")
            page.click('#addModal button:has-text("Сохранить")')

            # 4. Assign Procedure and test collision
            print("Assigning procedure and testing collision...")
            page.goto(f"{base_url}/patients.php")
            page.fill('input[name="q"]', "Петров Петр Петрович")
            page.click('button:has-text("Найти")')
            page.click('tr:has-text("Петров Петр Петрович") a:has-text("Назначить")')

            page.select_option('select[name="procedure_id"]', label="ЭКГ (платно)")
            page.fill('input[name="time"]', "11:00")
            page.fill('input[name="cabinet_id"]', "101")
            page.click('button:has-text("Назначить")')

            # Wait for navigation/reload
            page.wait_for_selector('td:has-text("ЭКГ")')
            print("First assignment done.")

            page.select_option('select[name="procedure_id"]', label="ЭКГ (платно)")
            page.fill('input[name="time"]', "11:10")
            page.fill('input[name="cabinet_id"]', "101")
            page.click('button:has-text("Назначить")')
            expect(page.get_by_text("Это время занято")).to_be_visible()
            print("Collision logic verified.")

            page.screenshot(path="/home/jules/verification/collision_verified.png")

            print("All verified successfully.")

        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="/home/jules/verification/error.png")
            raise e
        finally:
            browser.close()

if __name__ == "__main__":
    verify_system()
