from playwright.sync_api import sync_playwright, expect
import time
import os

def verify_final():
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

            # 2. Add New Doctor with custom code
            print("Adding New Doctor...")
            page.goto(f"{base_url}/settings.php?sub=staff")
            page.fill('input[name="name"]', "Доктор Тест")
            page.select_option('select[name="role"]', "doctor")
            page.fill('input[name="access_code"]', "999999")
            page.click('button:has-text("Добавить")')
            expect(page.get_by_text("Сотрудник добавлен")).to_be_visible()

            # 3. Add Procedure with specific time
            print("Adding Procedure...")
            page.goto(f"{base_url}/settings.php?sub=procedures")
            page.fill('input[name="name"]', "Процедура X")
            page.fill('input[name="duration"]', "45")
            page.fill('input[name="prep_time"]', "15")
            page.fill('input[name="price"]', "1500")
            page.click('button:has-text("Добавить")')

            # 4. Logout and login as New Doctor
            print("Logging in as New Doctor...")
            page.goto(f"{base_url}/login.php?logout=1")
            page.fill('input[name="code"]', "999999")
            page.click('button[type="submit"]')
            expect(page).to_have_url(f"{base_url}/index.php")

            # 5. Create Patient and assign
            print("Registering Patient...")
            page.goto(f"{base_url}/patients.php")
            page.click('button:has-text("Добавить пациента")')
            page.fill('#addModal input[name="name"]', "Пациент Тест")
            page.fill('#addModal input[name="birth_date"]', "1990-01-01")
            page.click('#addModal button:has-text("Сохранить")')

            print("Assigning Procedure...")
            page.click('tr:has-text("Пациент Тест") a:has-text("Назначить")')
            page.select_option('select[name="procedure_id"]', label="Процедура X (платно)")
            page.fill('input[name="time"]', "12:00")
            page.fill('input[name="cabinet_id"]', "202")
            page.click('button:has-text("Назначить")')

            # Check table
            expect(page.locator('table').first).to_contain_text("Процедура X")
            expect(page.locator('table').first).to_contain_text("Не оплачено")

            # 6. Test Collision (12:00 + 45m + 15m = 13:00)
            print("Testing Collision at 12:45...")
            page.select_option('select[name="procedure_id"]', label="Процедура X (платно)")
            page.fill('input[name="time"]', "12:45")
            page.fill('input[name="cabinet_id"]', "202")
            page.click('button:has-text("Назначить")')
            expect(page.get_by_text("Это время занято")).to_be_visible()

            print("Collision correctly detected.")

            page.screenshot(path="/home/jules/verification/final_verified.png")
            print("Final verification successful.")

        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="/home/jules/verification/final_error.png")
            raise e
        finally:
            browser.close()

if __name__ == "__main__":
    verify_final()
