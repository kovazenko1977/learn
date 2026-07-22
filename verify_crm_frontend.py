import os
from playwright.sync_api import sync_playwright

def run_cuj(page):
    page.on("console", lambda msg: print("CONSOLE:", msg.text))
    page.on("pageerror", lambda err: print("PAGE ERROR:", err))

    # Go to the local PHP server
    page.goto("http://localhost:8000/index.html")
    page.wait_for_timeout(1000)

    # Fill username and password
    page.fill('input[type="text"]', "admin")
    page.wait_for_timeout(500)
    page.fill('input[type="password"]', "admin123")
    page.wait_for_timeout(500)

    # Click login button
    page.click('button[type="submit"]')
    page.wait_for_timeout(1500)

    # Save screenshot of the main dashboard
    page.screenshot(path="/home/jules/verification/screenshots/dashboard.png")

    # Click 'All Tasks' tab using anchor with .fa-tasks icon
    page.click("a:has(.fa-tasks)")
    page.wait_for_timeout(1000)

    # Save screenshot of All Tasks tab
    page.screenshot(path="/home/jules/verification/screenshots/tasks_list.png")

    # Click on the advanced task we created in the backend test
    page.click("text=IT Support - Router Upgrade")
    page.wait_for_timeout(1500)

    # Save screenshot of task detail modal
    page.screenshot(path="/home/jules/verification/screenshots/task_modal_after_advanced.png")

    # Input a subtask to checklist
    page.fill('input[placeholder="Новая подзадача..."]', "Verify WAN connections and speed tests")
    page.wait_for_timeout(500)
    page.click("button:has-text('Добавить')")
    page.wait_for_timeout(1000)

    # Input work hours to log
    page.fill('input[placeholder="Часы"]', "1.5")
    page.fill('input[placeholder="Описание работ..."]', "Ran speed tests and certified connectivity.")
    page.wait_for_timeout(500)
    page.click("button:has-text('Списать')")
    page.wait_for_timeout(1000)

    # Save screenshot after actions
    page.screenshot(path="/home/jules/verification/screenshots/task_modal_after_actions.png")

    # Close modal
    page.click(".fa-times")
    page.wait_for_timeout(1000)

    # Click 'Settings' tab
    page.click("a:has(.fa-cog)")
    page.wait_for_timeout(1000)

    # Take final screenshot
    page.screenshot(path="/home/jules/verification/screenshots/verification.png")
    page.wait_for_timeout(1000)

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(
            record_video_dir="/home/jules/verification/videos"
        )
        page = context.new_page()
        try:
            run_cuj(page)
        except Exception as e:
            print("RUN FAILED:", e)
        finally:
            context.close()
            browser.close()
