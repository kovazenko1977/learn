import asyncio
from playwright.async_api import async_playwright
import json

async def verify():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        context = await browser.new_context()
        page = await context.new_page()

        # 1. Login as Performer (code 373382 - Александр Попов, service 1)
        await page.goto('http://localhost:8000/login.php')
        await page.fill('#code-input', '373382')
        await page.click('button[type="submit"]')
        await page.wait_for_url('**/index.php')

        # 2. Verify "Новая заявка" button is present
        btn_create = page.locator('a:has-text("Новая заявка")')
        is_btn_visible = await btn_create.is_visible()
        print(f"Performer: 'New Request' button visible: {is_btn_visible}")

        # 3. Verify unassigned "New" request for service 1 is visible
        # ID 15 in data/requests.json is "New", Service 1, No Performer
        # ID 9 is "Assigned" to Performer 20 (Petr Petrov).
        # Wait, Performer 12 (Aleksandr Popov) service 1.
        # Let's check ID 15 (Service 1, New).
        request_item = page.locator('.request-card:has-text("#15")')
        is_req_visible = await request_item.is_visible()
        print(f"Performer: Unassigned request #15 (Service 1) visible: {is_req_visible}")

        # 4. Verify "Awaiting" stat card has pulse-new class
        stat_new = page.locator('.stat-new-card')
        classes = await stat_new.get_attribute('class')
        print(f"Performer: 'Awaiting' stat card classes: {classes}")
        if 'pulse-new' in classes:
            print("Pulsating effect applied to New tasks card.")

        # 5. Check visibility of Assigned tasks
        # ID 29 is "Closed" but assigned to performer 12.
        # ID 63 is "Closed" but assigned to performer 12.
        # ID 61 is "Working" and assigned to performer 12.
        request_assigned = page.locator('.request-card:has-text("#61")')
        is_assigned_visible = await request_assigned.is_visible()
        print(f"Performer: Assigned request #61 visible: {is_assigned_visible}")

        await page.screenshot(path='verification/performer_dashboard.png')
        await browser.close()

if __name__ == "__main__":
    asyncio.run(verify())
