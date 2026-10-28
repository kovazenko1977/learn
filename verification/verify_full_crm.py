import subprocess
import time
import os
from playwright.sync_api import sync_playwright

def verify_full_crm():
    print("Starting Playwright verification for CRM system...")
    os.makedirs('verification', exist_ok=True)

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        # 1. Open App Login
        page.goto('http://127.0.0.1:5000')
        page.wait_for_load_state('networkidle')
        page.screenshot(path='verification/01_login.png')
        print("Captured 01_login.png")

        # 2. Login as Admin
        page.fill('input[placeholder="admin"]', 'admin')
        page.fill('input[placeholder="••••••••"]', 'admin123')
        page.click('button[type="submit"]')
        page.wait_for_selector('text=Дашборд', timeout=5000)
        page.screenshot(path='verification/02_dashboard.png')
        print("Captured 02_dashboard.png")

        # 3. View Requests List
        page.click('button:has-text("Заявки")')
        page.wait_for_timeout(1000)
        page.screenshot(path='verification/03_tickets_table.png')
        print("Captured 03_tickets_table.png")

        # 4. View Kanban Board
        page.click('button:has-text("Kanban")')
        page.wait_for_timeout(1000)
        page.screenshot(path='verification/04_tickets_kanban.png')
        print("Captured 04_tickets_kanban.png")

        # 5. Form Builder Page
        page.click('button:has-text("Конструктор форм")')
        page.wait_for_timeout(1000)
        page.screenshot(path='verification/05_form_builder.png')
        print("Captured 05_form_builder.png")

        # 6. Admin Settings
        page.click('button:has-text("Настройки")')
        page.wait_for_timeout(1000)
        page.screenshot(path='verification/06_settings.png')
        print("Captured 06_settings.png")

        # 7. Profile
        page.click('button:has-text("Профиль")')
        page.wait_for_timeout(1000)
        page.screenshot(path='verification/07_profile.png')
        print("Captured 07_profile.png")

        browser.close()
    print("Playwright E2E verification complete!")

if __name__ == '__main__':
    verify_full_crm()
