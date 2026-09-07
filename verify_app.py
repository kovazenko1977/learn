import time
import subprocess
import urllib.request
import json
from playwright.sync_api import sync_playwright

def main():
    print("Starting local PHP server on port 8089...")
    server_process = subprocess.Popen(
        ["php", "-S", "127.0.0.1:8089"],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE
    )
    time.sleep(2)

    try:
        # Test API response using standard urllib
        print("Testing REST API endpoint /api.php?action=get_tasks ...")
        with urllib.request.urlopen("http://127.0.0.1:8089/api.php?action=get_tasks") as resp:
            data = json.loads(resp.read().decode('utf-8'))
            assert data["success"] is True
            print("API Status 200 OK:", data)

        # Test PIN verification endpoint
        print("Testing PIN verification API ...")
        req = urllib.request.Request(
            "http://127.0.0.1:8089/api.php?action=verify_pin",
            data=json.dumps({"pin": "1111"}).encode('utf-8'),
            headers={"Content-Type": "application/json"}
        )
        with urllib.request.urlopen(req) as resp:
            pin_data = json.loads(resp.read().decode('utf-8'))
            assert pin_data["success"] is True
            print("PIN Verification 1111 OK:", pin_data)

        # Test UI with Playwright in mobile viewport
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True)
            context = browser.new_context(
                viewport={"width": 393, "height": 851},
                user_agent="Mozilla/5.0 (Linux; Android 11; Pixel 5) AppleWebKit/537.36"
            )
            page = context.new_page()

            print("Navigating to http://127.0.0.1:8089/index.php ...")
            page.goto("http://127.0.0.1:8089/index.php")

            # Check PIN screen visible
            print("Verifying PIN Lock Overlay...")
            page.wait_for_selector("#pinLockOverlay")
            assert not page.locator("#pinLockOverlay").is_hidden()

            # Click PIN keypad '1', '1', '1', '1'
            print("Entering PIN 1111 via keypad...")
            for _ in range(4):
                page.click('.key-btn[data-key="1"]')
                time.sleep(0.2)

            time.sleep(1)
            # Verify PIN lock overlay unlocked
            assert page.locator("#pinLockOverlay").is_hidden()
            print("App successfully unlocked via PIN 1111!")

            # Add a task with due date to test dynamic countdown timer
            print("Adding task with due date...")
            page.fill("#taskTitleInput", "Подготовить отчет с таймером")
            page.click("#taskTitleInput") # Focus
            page.fill("#taskDueDateInput", "2030-12-31")
            page.fill("#taskDueTimeInput", "18:00")
            page.click("#btnAddTask")
            time.sleep(1)

            # Check dynamic countdown badge rendered
            countdown_badge = page.locator(".task-countdown").first
            assert "Осталось" in countdown_badge.inner_text() or "Просрочено" in countdown_badge.inner_text()
            print("Task countdown timer badge verified:", countdown_badge.inner_text())

            # Take Mobile Verification Screenshot
            screenshot_path = "verification_mobile_app.png"
            page.screenshot(path=screenshot_path)
            print(f"Captured UI Screenshot to {screenshot_path}")

            browser.close()

    finally:
        server_process.terminate()
        server_process.wait()
        print("PHP server stopped.")

if __name__ == "__main__":
    main()
