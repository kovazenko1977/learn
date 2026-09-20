import asyncio
from playwright.async_api import async_playwright

async def main():
    print("Starting Playwright Python E2E Verification...")
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page(viewport={'width': 1280, 'height': 800})

        try:
            # 1. Load login page
            await page.goto('http://127.0.0.1:8088')
            await page.wait_for_selector('#loginUsername')
            print("PASS: Login page rendered successfully.")

            # 2. Perform Login as Admin
            await page.fill('#loginUsername', 'admin')
            await page.fill('#loginPassword', 'admin123')
            await page.click('button[type="submit"]')

            # Wait for dashboard to load
            await page.wait_for_selector('.kpi-grid')
            print("PASS: Authenticated and redirected to Dashboard.")

            # Capture Dashboard Screenshot
            await page.screenshot(path='verification/dashboard.png')

            # 3. Navigate to Requests page
            await page.click('.nav-item:has-text("Заявки")')
            await page.wait_for_selector('.data-table')
            print("PASS: Navigated to Requests page.")

            # 4. Create new ticket via UI modal
            await page.click('button:has-text("Создать заявку")')
            await page.wait_for_selector('#reqTitle')
            await page.fill('#reqTitle', 'Проблема с кондиционером в холле')
            await page.fill('#reqDesc', 'Кондиционер не включается, горит красная индикация')
            await page.click('button:has-text("Отправить заявку")')

            # Confirm ticket appeared in table
            await page.wait_for_selector('td:has-text("Проблема с кондиционером в холле")')
            print("PASS: Created request via UI modal.")

            # Capture Requests List Screenshot
            await page.screenshot(path='verification/requests.png')

            # 5. Navigate to Kanban Board
            await page.click('.nav-item:has-text("Канбан доска")')
            await page.wait_for_selector('.kanban-board')
            print("PASS: Navigated to Kanban board.")
            await page.screenshot(path='verification/kanban.png')

            # 6. Navigate to Form Builder
            await page.click('.nav-item:has-text("Конструктор форм")')
            await page.wait_for_selector('button:has-text("Добавить поле")')
            print("PASS: Navigated to Form Builder.")

            # 7. Toggle Dark Theme
            await page.click('button:has-text("Dark")')
            await page.screenshot(path='verification/dark_theme.png')
            print("PASS: Dark theme toggled successfully.")

            print("SUCCESS: All Playwright Python E2E verification tests passed!")
        except Exception as e:
            print(f"FAIL: E2E Verification error: {e}")
            raise e
        finally:
            await browser.close()

if __name__ == '__main__':
    asyncio.run(main())
