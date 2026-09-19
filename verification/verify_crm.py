import time
import sys
from playwright.sync_api import sync_playwright

def verify_crm():
    print("Starting Playwright E2E Verification for CRM Service...")

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={"width": 1280, "height": 800})
        page = context.new_page()

        # 1. Open App
        page.goto("http://127.0.0.1:8088/")
        page.wait_for_selector("#loginForm")
        print("✓ Login screen loaded.")

        # 2. Login as Admin
        page.fill("#loginUsername", "admin")
        page.fill("#loginPassword", "admin123")
        page.click("#loginForm button[type='submit']")

        page.wait_for_selector(".page-title")
        title_text = page.inner_text(".page-title")
        print(f"✓ Admin Logged In. Page Title: {title_text}")

        # Capture Dashboard Screenshot
        page.screenshot(path="verification/01_dashboard_admin.png")

        # 3. Navigate to Form Builder & Add Field
        page.click("a[data-page='form-builder']")
        page.wait_for_selector("button:has-text('Добавить поле')")
        page.click("button:has-text('Добавить поле')")
        page.wait_for_selector("#addFieldForm")

        page.fill("#fieldLabel", "Срочность согласования")
        page.fill("#fieldPlaceholder", "Например: До 15:00")
        page.click("#addFieldForm button[type='submit']")
        page.wait_for_selector("#modalOverlay", state="hidden")
        time.sleep(0.5)
        print("✓ Custom form field added via Form Builder.")

        # 4. Open New Ticket Modal
        page.click("#quickNewTicketBtn")
        page.wait_for_selector("#newTicketForm")
        page.fill("#ticketTitle", "Аварийная протечка трубы в цеху 3")
        page.fill("#ticketDescription", "Труба холодного водоснабжения дала течь около станка №4.")

        # Fill all custom fields inputs appropriately
        for custom_input in page.query_selector_all(".custom-field-input"):
            input_type = custom_input.get_attribute("type")
            if input_type == "date":
                custom_input.fill("2026-03-25")
            elif input_type == "number":
                custom_input.fill("123")
            else:
                custom_input.fill("101")

        page.click("#newTicketForm button[type='submit']")
        page.wait_for_selector("#modalOverlay", state="hidden")
        time.sleep(0.5)
        print("✓ Created new repair request.")

        # Capture Tickets Page Screenshot
        page.click("a[data-page='tickets']")
        page.wait_for_selector("#ticketsTableBody")
        time.sleep(0.5)
        page.screenshot(path="verification/02_tickets_list.png")
        print("✓ Captured Tickets List screenshot.")

        # 5. Open Kanban View
        page.click("a[data-page='kanban']")
        page.wait_for_selector(".kanban-board")
        time.sleep(0.5)
        page.screenshot(path="verification/03_kanban_board.png")
        print("✓ Captured Kanban Board screenshot.")

        # 6. Open Analytics & Theme Toggle
        page.click("a[data-page='analytics']")
        page.wait_for_selector(".page-title")

        page.click("#themeToggleBtn")
        time.sleep(0.5)
        page.screenshot(path="verification/04_analytics_dark_theme.png")
        print("✓ Dark Theme toggled and Analytics view captured.")

        browser.close()
        print("ALL E2E PLAYWRIGHT TESTS PASSED SUCCESSFUL!")

if __name__ == "__main__":
    verify_crm()
