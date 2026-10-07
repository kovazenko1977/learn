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

            os.makedirs("verification", exist_ok=True)

            # Test "Что сегодня?" Voice summary button
            voice_btn = page.locator("#voiceTodayBtn")
            assert voice_btn.is_visible(), "Voice today button not found!"
            voice_btn.click()
            print("Voice summary button clicked.")
            page.screenshot(path="verification/07_voice_today.png")

            # Go to Settings tab and test "Сбросить все интервалы"
            page.click("button[data-tab='settings']")
            page.wait_for_selector("#tabSettings.active")
            page.screenshot(path="verification/08_settings_new_reset.png")

            # Handle confirm dialog for resetting intervals
            page.on("dialog", lambda dialog: dialog.accept())
            page.click("#resetIntervalsBtn")
            page.wait_for_timeout(1000)
            print("Reset all intervals clicked.")

            # Verify unvisited count is now total points (73)
            page.click("button[data-tab='unvisited']")
            page.wait_for_selector("#tabUnvisited.active")
            unvisited_badge = page.locator("#unvisitedBadge").text_content().strip()
            print(f"Unvisited count after reset: {unvisited_badge}")
            assert unvisited_badge == "73", f"Expected 73 unvisited points, got {unvisited_badge}"
            page.screenshot(path="verification/09_after_reset_all_intervals.png")

            browser.close()
            print("Playwright test completed successfully!")

    finally:
        server_process.terminate()
        server_process.wait()
        print("Local server stopped.")

if __name__ == "__main__":
    run_test()
