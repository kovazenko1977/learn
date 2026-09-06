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
            page.wait_for_selector("#headerTitle")

            title_text = page.inner_text("#headerTitle")
            print("App Title:", title_text)
            assert "Задачи" in title_text

            # Test adding a task via UI
            print("Adding task via UI...")
            page.fill("#taskTitleInput", "Купить продукты по голосу")
            page.click("#btnAddTask")
            time.sleep(1)

            # Verify task rendered
            task_card = page.locator(".task-card").first
            assert "Купить продукты по голосу" in task_card.inner_text()
            print("Task created successfully on UI!")

            # Take Task View Mobile Verification Screenshot (Default Dark Mode)
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
