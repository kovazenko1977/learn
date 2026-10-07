import os
import time
import subprocess
from playwright.sync_api import sync_playwright

def run_test():
    print("Starting local PHP web server on port 8098...")
    server_process = subprocess.Popen(
        ["php", "-S", "127.0.0.1:8098", "router.php"],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE
    )
    time.sleep(1.5)

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True)
            context = browser.new_context(viewport={"width": 412, "height": 915}) # Mobile PWA view
            page = context.new_page()

            print("Navigating to http://127.0.0.1:8098 ...")
            page.goto("http://127.0.0.1:8098/")

            page.wait_for_selector("#appContainer:not(.hidden)", timeout=5000)
            print("App loaded.")

            os.makedirs("verification", exist_ok=True)

            # Mark a new visit to test exact timestamp recording
            page.locator(".mark-visit-btn").first.click()
            page.wait_for_selector("#visitModal:not(.hidden)", timeout=3000)
            page.click(".custom-checkbox")
            page.fill("#visitDefectsText", "Тестовый недостаток для проверки времени")
            page.click("#visitForm button[type='submit']")
            page.wait_for_selector("#visitModal", state="hidden", timeout=3000)
            print("New visit recorded with exact timestamp.")

            # Go to History tab
            page.click("button[data-tab='history']")
            page.wait_for_selector("#tabHistory.active")
            page.screenshot(path="verification/10_history_with_calendar.png")

            # Verify date picker is visible
            date_picker = page.locator("#historyDatePicker")
            assert date_picker.is_visible(), "History date picker not found!"

            # Test graphical calendar modal
            page.click("#openCalendarModalBtn")
            page.wait_for_selector("#calendarModal:not(.hidden)", timeout=3000)
            page.screenshot(path="verification/11_calendar_modal_open.png")

            # Click prev/next month
            page.click("#calPrevMonthBtn")
            page.wait_for_timeout(300)
            page.click("#calNextMonthBtn")
            page.wait_for_timeout(300)

            # Select a day cell
            page.locator(".cal-day-cell:not(.empty-day)").first.click()
            page.wait_for_selector("#calendarModal", state="hidden", timeout=3000)

            # Go to Visited tab and check SIM badge left placement & cancel visit button
            page.click("button[data-tab='visited']")
            page.wait_for_selector("#tabVisited.active")
            page.screenshot(path="verification/13_visited_tab_with_cancel_btn.png")

            # Click "Отменить посещение" button on the first visited card
            page.on("dialog", lambda dialog: dialog.accept())
            page.locator(".cancel-visit-btn").first.click()
            page.wait_for_timeout(800)

            # Go to Settings tab and test "Принудительно обновить данные"
            page.click("button[data-tab='settings']")
            page.wait_for_selector("#tabSettings.active")
            page.screenshot(path="verification/15_settings_with_refresh_btn.png")

            page.click("#forceRefreshBtn")
            page.wait_for_timeout(800)

            # Go to Unvisited tab and click on an organization title to open history modal
            page.click("button[data-tab='unvisited']")
            page.wait_for_selector("#tabUnvisited.active")

            # Test compact mode toggle
            page.click("#compactViewToggleBtn")
            page.wait_for_timeout(300)
            assert page.eval_on_selector("body", "el => el.classList.contains('compact-mode')"), "Compact mode not activated!"
            page.screenshot(path="verification/18_compact_view_active.png")

            # Test persistence on page reload
            page.reload()
            page.wait_for_selector("#appContainer:not(.hidden)", timeout=5000)
            assert page.eval_on_selector("body", "el => el.classList.contains('compact-mode')"), "Compact mode not persisted after reload!"

            # Test editing contract number
            page.locator(".edit-point-btn").first.click()
            page.wait_for_selector("#pointModal:not(.hidden)", timeout=3000)
            page.fill("#pointFormContract", "Д-2025/99-ТЕСТ")
            page.click("#savePointBtn")
            page.wait_for_selector("#pointModal", state="hidden", timeout=3000)

            # Re-open edit to confirm saved contract number
            page.locator(".edit-point-btn").first.click()
            page.wait_for_selector("#pointModal:not(.hidden)", timeout=3000)
            contract_val = page.input_value("#pointFormContract")
            assert contract_val == "Д-2025/99-ТЕСТ", f"Contract number mismatch: got {contract_val}"
            page.click("#cancelPointBtn")

            print("Compact view toggle, persistence, and contract number editing verified successfully!")

            browser.close()
            print("Playwright verification completed successfully!")

    finally:
        server_process.terminate()
        server_process.wait()
        print("Local server stopped.")

if __name__ == "__main__":
    run_test()
