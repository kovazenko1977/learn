import asyncio
import os
import subprocess
import time
from playwright.async_api import async_playwright

async def run_tests():
    # Start PHP built-in server on port 8090
    php_proc = subprocess.Popen(
        ['php', '-S', '127.0.0.1:8090'],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE
    )
    time.sleep(1.5)

    try:
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True)
            context = await browser.new_context(viewport={'width': 1280, 'height': 800})
            page = await context.new_page()

            # Auto-accept all alerts/dialogs
            page.on("dialog", lambda dialog: asyncio.create_task(dialog.accept()))

            # 1. Open Main Page
            print("1. Testing Main Page...")
            await page.goto("http://127.0.0.1:8090/index.php")
            await page.wait_for_selector(".hero")
            await page.screenshot(path="verification_index.png")
            print("Main page loaded successfully.")

            # 2. Test Admin Login via Unified Login Form (12345 / 12345)
            print("2. Testing Unified Login as Admin (12345/12345)...")
            await page.goto("http://127.0.0.1:8090/user.php")
            await page.fill("#loginPhone", "12345")
            await page.fill("#loginPassword", "12345")
            await page.click("#loginForm button[type='submit']")
            await page.wait_for_selector("#adminDashboardContainer", state="visible")
            await page.screenshot(path="verification_admin.png")
            print("Admin login & auto-redirect successful.")

            # 3. Test Admin Backup Tab
            print("3. Testing Admin Backup & Restore Tab...")
            await page.click("button:has-text('Резервное Копирование')")
            await page.wait_for_selector("#tabBackup", state="visible")
            await page.screenshot(path="verification_backup_tab.png")
            print("Backup tab opened.")

            # Logout Admin
            await page.click("button:has-text('Выйти из системы')")
            await asyncio.sleep(1)

            # 4. Test User Cabinet Registration & Login with Phone
            print("4. Testing User Cabinet Registration...")
            phone_num = f"+7999{int(time.time()) % 10000000:07d}"
            await page.goto("http://127.0.0.1:8090/user.php")
            await page.click("#tabBtnRegister")
            await page.fill("#regPhone", phone_num)
            await page.fill("#regFullName", "Иванов Петр Сергеевич")
            await page.fill("#regPassword", "pass1234")
            await page.click("#registerForm button[type='submit']")
            await page.wait_for_selector("#cabinetContainer", state="visible")
            await page.screenshot(path="verification_user_cabinet.png")
            print("User registered and logged in.")

            # 5. Create a Memorial Page
            print("5. Creating a new Memorial Page...")
            await page.click("button:has-text('Создать страницу')")
            await page.wait_for_selector("#createPageModal.active")
            await page.fill("#createPageForm input[name='full_name']", "Смирнов Алексей Владимирович")
            await page.fill("#createPageForm input[name='birth_date']", "12.04.1965")
            await page.fill("#createPageForm input[name='death_date']", "05.11.2021")
            await page.fill("#createPageForm input[name='epitaph']", "Светлая память о любимом муже и отце")
            await page.fill("#createPageForm textarea[name='biography']", "Заслуженный врач, отдал 30 лет служению людям.")
            await page.fill("#createPageForm input[name='cemetery']", "Западное кладбище")
            await page.fill("#createPageForm input[name='section']", "Сектор 4B")
            await page.fill("#createPageForm input[name='grave_num']", "128")

            # Fill Relative
            await page.fill("#relativesContainer .rel-type", "Сын")
            await page.fill("#relativesContainer .rel-name", "Смирнов Михаил Алексеевич")
            await page.fill("#relativesContainer .rel-phone", "+79998887766")

            await page.click("#createPageForm button[type='submit']")
            await asyncio.sleep(1)

            print("Memorial page submitted.")

            # Clear session / Logout User and Login as Admin to Approve Page
            print("6. Admin approving the page...")
            await page.goto("http://127.0.0.1:8090/api/index.php?action=auth_logout")
            await page.goto("http://127.0.0.1:8090/user.php")
            await page.fill("#loginPhone", "12345")
            await page.fill("#loginPassword", "12345")
            await page.click("#loginForm button[type='submit']")
            await page.wait_for_selector("#adminDashboardContainer", state="visible")
            await page.wait_for_selector("#pendingPagesList button:has-text('Опубликовать')")
            await page.click("#pendingPagesList button:has-text('Опубликовать')")
            await asyncio.sleep(1)

            # 7. Search for published page on main page
            print("7. Searching for published page...")
            await page.goto("http://127.0.0.1:8090/index.php")
            await page.fill("#searchQuery", "Смирнов")
            await page.click("button:has-text('Найти')")
            await page.wait_for_selector(".memorial-card")
            await page.screenshot(path="verification_search_results.png")

            # 8. Open Memorial Page & QR code plaque
            print("8. Inspecting Memorial Page and QR Code plaque...")
            await page.click(".memorial-card a:has-text('Перейти к мемориалу')")
            await page.wait_for_selector(".single-page-title")
            await page.screenshot(path="verification_memorial_page.png")

            await page.click("button:has-text('Табличка с QR-кодом')")
            await page.wait_for_selector("#plaqueModal.active")
            await page.screenshot(path="verification_qr_plaque.png")

            # 9. Mobile Viewport Verification
            print("9. Verifying mobile viewport layout...")
            await page.set_viewport_size({"width": 375, "height": 667})
            await page.screenshot(path="verification_mobile_page.png")

            print("ALL VERIFICATION TESTS PASSED SUCCESSFULLY!")

            await browser.close()
    finally:
        php_proc.terminate()

if __name__ == "__main__":
    asyncio.run(run_tests())
