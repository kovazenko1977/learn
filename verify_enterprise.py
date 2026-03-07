from playwright.sync_api import sync_playwright

def verify_enterprise_features(page):
    print("Verifying Enterprise Node Features...")

    # 1. Login
    page.goto("http://localhost:8080")
    page.fill("input[name='username']", "admin")
    page.fill("input[name='password']", "admin123")
    page.click("#login-btn")

    # 2. Sidebar Presence
    page.wait_for_selector("aside")
    page.wait_for_selector("text=ALCO.PRO")
    print("Enterprise Sidebar confirmed.")

    # 3. Analytics Command Center
    page.click("#nav-analytics")
    page.wait_for_selector("text=Центр бизнес-аналитики")
    page.wait_for_selector("canvas") # Check for Charts
    page.wait_for_selector("text=Реестр эффективности SKU")
    print("Analytics Command Center confirmed.")

    # 4. Role Cabinet Check (Warehouse)
    page.click("#nav-warehouse")
    page.wait_for_selector("text=Складской Учет")
    print("Operational Cabinets (Warehouse) confirmed.")

    # 5. Logout verification
    page.click("text=Выход")
    page.wait_for_selector("text=Идентификация")
    print("Security Protocol (Logout) confirmed.")

    # Final Screenshot
    page.screenshot(path="/home/jules/verification/enterprise_final_localized.png", full_page=True)

with sync_playwright() as p:
    browser = p.chromium.launch()
    context = browser.new_context(viewport={'width': 1920, 'height': 1080})
    page = context.new_page()
    try:
        verify_enterprise_features(page)
        print("Enterprise verification successful.")
    except Exception as e:
        print(f"Enterprise verification FAILED: {e}")
        page.screenshot(path="/home/jules/verification/enterprise_failure_localized.png")
    finally:
        browser.close()
