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

            # Wait for preloader to complete and app container to be visible
            page.wait_for_selector("#appContainer:not(.hidden)", timeout=5000)
            print("App loaded and preloader dismissed.")

            # Take screenshot of Unvisited list
            os.makedirs("verification", exist_ok=True)
            page.screenshot(path="verification/01_unvisited_list.png")
            print("Captured screenshot: verification/01_unvisited_list.png")

            # Verify SIM card number rendering
            sim_badge = page.locator(".sim-badge").first
            assert sim_badge.is_visible(), "SIM badge not found!"
            sim_text = sim_badge.text_content().strip()
            print(f"Verified SIM badge text: {sim_text}")

            # Mark a visit on the first card
            first_card_btn = page.locator(".mark-visit-btn").first
            first_card_btn.click()

            page.wait_for_selector("#visitModal:not(.hidden)", timeout=3000)
            print("Visit modal opened.")

            # Click custom checkbox label
            page.click(".custom-checkbox")
            page.fill("#visitDefectsText", "Заменен резервный АКБ 12V 7Ah в РИП-12")
            page.fill("#visitNotesText", "Проверена работоспособность шлейфов ШС-1 и ШС-2")
            page.screenshot(path="verification/02_visit_modal.png")

            # Submit visit form
            page.click("#visitForm button[type='submit']")
            page.wait_for_selector("#visitModal", state="hidden", timeout=3000)
            print("Visit recorded and modal closed.")

            page.wait_for_timeout(500)
            page.screenshot(path="verification/03_after_visit_marked.png")

            # Switch to 'Visited' tab
            page.click("button[data-tab='visited']")
            page.wait_for_selector("#tabVisited.active")
            page.screenshot(path="verification/04_visited_tab.png")
            print("Switched to Visited tab.")

            # Switch to 'History' tab
            page.click("button[data-tab='history']")
            page.wait_for_selector("#tabHistory.active")
            page.screenshot(path="verification/05_history_tab.png")
            print("Switched to History tab.")

            # Switch to 'Settings' tab
            page.click("button[data-tab='settings']")
            page.wait_for_selector("#tabSettings.active")
            page.screenshot(path="verification/06_settings_tab.png")
            print("Switched to Settings tab.")

            browser.close()
            print("Playwright test completed successfully!")

    finally:
        server_process.terminate()
        server_process.wait()
        print("Local server stopped.")

if __name__ == "__main__":
    run_test()
