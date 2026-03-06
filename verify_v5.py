import time
from playwright.sync_api import sync_playwright

def verify_industrial_v5(page):
    print("Verifying Industrial ERP v5.0 features...")

    # 1. Login
    page.goto("http://localhost:8080")
    page.fill("input[name='username']", "admin")
    page.fill("input[name='password']", "admin123")
    page.click("#login-btn")

    # 2. Check Dashboard V5
    page.wait_for_selector("text=Панель Управления")
    page.wait_for_selector("text=Выручка Brutto")
    page.wait_for_selector("text=Industrial ERP System v5.0")
    print("Dashboard V5 confirmed.")

    # 3. Check Expanded Analytics
    page.click("text=Подробный отчет ->")
    page.wait_for_selector("text=Глобальная Аналитика")
    page.wait_for_selector("text=Оборачиваемость Сырья")
    page.wait_for_selector("text=Финансовые показатели по SKU")
    print("Expanded Analytics confirmed.")

    # 4. Check Audit Log
    page.click("text=Админ")
    page.click("text=Журнал Аудита")
    page.wait_for_selector("th:has-text('Timestamp')")
    print("Audit Log V5 confirmed.")

    # Take screenshot
    page.screenshot(path="/home/jules/verification/industrial_v5.png", full_page=True)
    print("Screenshot saved to /home/jules/verification/industrial_v5.png")

with sync_playwright() as p:
    browser = p.chromium.launch()
    context = browser.new_context(viewport={'width': 1600, 'height': 1200})
    page = context.new_page()
    try:
        verify_industrial_v5(page)
        print("Industrial v5 verification successful.")
    except Exception as e:
        print(f"Verification failed: {e}")
        page.screenshot(path="/home/jules/verification/failure_v5.png")
    finally:
        browser.close()
