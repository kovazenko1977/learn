from playwright.sync_api import sync_playwright, expect
import time
import os

def verify_final(page):
    # Go to login
    page.goto("http://localhost:8000")

    # Login
    page.fill("#passcode", "111111")
    page.click("#login-btn")

    # Wait for main screen
    expect(page.locator("#main-screen")).to_be_visible()

    # Take screenshot of Chat
    time.sleep(1)
    page.screenshot(path="verification/final_chat.png")

    # Switch to Tasks
    page.click(".nav-item[data-view='tasks']")
    time.sleep(1)
    page.screenshot(path="verification/final_tasks.png")

    # Open a task to see subtasks and comments
    # Assuming there's a task. Let's create one if not.
    if page.locator(".task-card").count() == 0:
        page.click("#new-task-btn")
        page.fill("#new-task-title", "Final Test Task")
        page.fill("#new-task-desc", "Verifying subtasks and comments")
        page.click("#confirm-new-task")
        time.sleep(1)

    page.locator(".task-card").first.click()
    time.sleep(0.5)
    page.screenshot(path="verification/final_task_details.png")
    page.click(".close-modal")

    # Switch to Shopping
    page.click(".nav-item[data-view='shopping']")
    time.sleep(0.5)
    page.screenshot(path="verification/final_shopping.png")

    # Switch to Achievements
    page.click(".nav-item[data-view='achievements']")
    time.sleep(0.5)
    page.screenshot(path="verification/final_achievements.png")

    # Switch to Settings
    page.click(".nav-item[data-view='settings']")
    time.sleep(0.5)
    page.screenshot(path="verification/final_settings.png")

if __name__ == "__main__":
    if not os.path.exists("verification"):
        os.makedirs("verification")

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        # Set viewport to something standard
        context = browser.new_page(viewport={'width': 1280, 'height': 800})
        try:
            verify_final(context)
        finally:
            browser.close()
