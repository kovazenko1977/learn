import os
import time
from playwright.sync_api import sync_playwright

def verify_final():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 720})
        page = context.new_page()

        # 1. Login
        page.goto("http://localhost:8080/index.html")
        page.fill("#login-username", "admin")
        page.fill("#login-password", "admin")
        page.click("#login-btn")

        # Wait for dashboard
        page.wait_for_selector("aside")

        # 2. Check localized menu
        menu_text = page.inner_text("aside")
        print(f"Menu contains: {menu_text}")
        if "Дашборд" in menu_text and "Заявки" in menu_text:
            print("Localization verified: Dashboard/Requests found in Russian")
        else:
            print("Localization FAILED")

        # 3. Check theme colors (Slate-900 is #0f172a)
        sidebar = page.locator("aside")
        bg_color = sidebar.evaluate("e => getComputedStyle(e).backgroundColor")
        print(f"Sidebar BG color: {bg_color}") # Should be rgb(15, 23, 42)

        # 4. Check tooltips in settings
        page.click("text=Настройки")
        page.wait_for_selector(".tooltip-container")

        # Hover over help icon
        page.hover(".tooltip-container i")
        time.sleep(0.5)
        tooltip_visible = page.is_visible(".tooltip")
        print(f"Tooltip visible on hover: {tooltip_visible}")

        # 5. Screenshots
        page.screenshot(path="final_verification_dashboard.png")
        page.click("text=Заявки")
        time.sleep(1)
        page.screenshot(path="final_verification_requests.png")

        browser.close()

if __name__ == "__main__":
    verify_final()
