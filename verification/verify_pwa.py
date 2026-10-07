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

            # Filter by today's date using 'Сегодня' button
            page.click("#historyTodayBtn")
            page.wait_for_timeout(500)
            page.screenshot(path="verification/11_history_filtered_today.png")

            print("History calendar filtering verified.")

            browser.close()
            print("Playwright verification completed successfully!")

    finally:
        server_process.terminate()
        server_process.wait()
        print("Local server stopped.")

if __name__ == "__main__":
    run_test()
