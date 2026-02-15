import asyncio
from playwright.async_api import async_playwright

async def verify():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()

        # Login
        await page.goto("http://localhost:8000/sanatorium-booking/admin/login.php")
        await page.fill('input[name="username"]', "admin")
        await page.fill('input[name="password"]', "admin")
        await page.click('button[type="submit"]')
        await page.wait_for_url("**/dashboard.php")
        print("Logged in successfully")

        # Verify Calendar
        await page.goto("http://localhost:8000/sanatorium-booking/admin/calendar.php")
        # Click a free cell (they have class cal-status-free)
        # We need a selector for a cell that is free.
        free_cell = page.locator(".cal-status-free").first
        await free_cell.click()

        # Select package 1 (Оздоровительная - 7 days)
        await page.select_option("#m-package", "1")

        duration = await page.input_value("#m-duration")
        print(f"Package 1 selected. Duration is: {duration}")
        if duration == "7":
            print("SUCCESS: Duration updated to 7 days for Package 1")
        else:
            print(f"FAILURE: Duration is {duration}, expected 7")

        # Select package 2 (Лечебная - 10 days)
        await page.select_option("#m-package", "2")
        duration = await page.input_value("#m-duration")
        print(f"Package 2 selected. Duration is: {duration}")
        if duration == "10":
            print("SUCCESS: Duration updated to 10 days for Package 2")
        else:
            print(f"FAILURE: Duration is {duration}, expected 10")

        # Verify Users UI
        await page.goto("http://localhost:8000/sanatorium-booking/admin/users.php")
        await page.click('button:has-text("Добавить пользователя")')
        await page.wait_for_selector("#user-modal", state="visible")

        # Take screenshot of the modal
        await page.screenshot(path="users_modal.png")
        print("Screenshot of users modal saved to users_modal.png")

        await browser.close()

asyncio.run(verify())
