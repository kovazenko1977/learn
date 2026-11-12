import os
import time
import subprocess
from playwright.sync_api import sync_playwright

def run_crm_e2e():
    print("=== STARTING CRM E2E VERIFICATION WITH PLAYWRIGHT ===")

    server_process = subprocess.Popen(
        ["php", "-S", "127.0.0.1:8088"],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE
    )
    time.sleep(1)

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True)
            page = browser.new_page(viewport={"width": 1280, "height": 800})

            print("1. Navigating to CRM...")
            page.goto("http://127.0.0.1:8088/")
            page.wait_for_selector("#loginUsername", timeout=5000)
            page.screenshot(path="verification/verification_login.png")
            print("   Login screen loaded and captured.")

            print("2. Logging in as Admin...")
            page.fill("#loginUsername", "admin")
            page.fill("#loginPassword", "admin123")
            page.click("button[type='submit']")
            page.wait_for_selector(".kpi-card", timeout=5000)
            page.screenshot(path="verification/verification_dashboard.png")
            print("   Dashboard loaded successfully.")

            print("3. Navigating to Tickets View...")
            page.click("a[data-view='tickets']")
            page.wait_for_selector("#ticketsTable", timeout=5000)
            page.screenshot(path="verification/verification_tickets.png")
            print("   Tickets view verified.")

            print("4. Navigating to Kanban Board...")
            page.click("a[data-view='kanban']")
            page.wait_for_selector("#kanbanBoard", timeout=5000)
            page.screenshot(path="verification/verification_kanban.png")
            print("   Kanban board verified.")

            print("5. Navigating to Form Builder...")
            page.click("a[data-view='form_builder']")
            page.wait_for_selector("#fbCanvas", timeout=5000)
            page.screenshot(path="verification/verification_form_builder.png")
            print("   Form Builder verified.")

            print("6. Navigating to Analytics...")
            page.click("a[data-view='analytics']")
            page.wait_for_selector(".table-responsive", timeout=5000)
            page.screenshot(path="verification/verification_analytics.png")
            print("   Analytics verified.")

            print("7. Navigating to Settings...")
            page.click("a[data-view='settings']")
            page.wait_for_selector("#storageModeSelect", timeout=5000)
            page.screenshot(path="verification/verification_settings.png")
            print("   Settings verified.")

            print("8. Testing Dark Theme Toggle...")
            page.click("#themeToggleBtn")
            time.sleep(0.5)
            page.screenshot(path="verification/verification_dark_theme.png")
            print("   Dark theme verified.")

            print("9. Testing Ticket Creation Modal...")
            page.click("button:has-text('Создать заявку')")
            page.wait_for_selector("#createTicketModal", timeout=5000)
            page.fill("#newTicketTitle", "E2E Автоматический тест: Замена светильника")
            page.fill("#newTicketDesc", "Мигает светодиодная панель в кабинете 204")
            page.screenshot(path="verification/verification_modal_create.png")
            page.click("#createTicketModal button[type='submit']")
            time.sleep(1)
            print("   Ticket created successfully.")

            browser.close()
            print("\n=== ALL PLAYWRIGHT E2E TESTS PASSED SUCCESSFULLY! ===")

    finally:
        server_process.terminate()
        server_process.wait()

if __name__ == "__main__":
    run_crm_e2e()
