import asyncio
from playwright.async_api import async_playwright
import sys

async def verify():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        context = await browser.new_context(
            viewport={'width': 1280, 'height': 800}
        )
        page = await context.new_page()

        # Start PHP server
        server = await asyncio.create_subprocess_exec(
            'php', '-S', 'localhost:8000',
            stdout=asyncio.subprocess.PIPE,
            stderr=asyncio.subprocess.PIPE
        )
        await asyncio.sleep(2)

        try:
            # 1. Check Guest Interface
            print("Checking Guest Interface...")
            await page.goto('http://localhost:8000/index.php')
            await page.screenshot(path='guest_index.png')

            # Select program
            await page.click('.card-item:first-child')
            # Set date
            await page.fill('#check-in', '2023-12-01')
            # Next to Step 2
            await page.click('.next-step[data-next="2"]')
            await asyncio.sleep(1)
            await page.screenshot(path='guest_step2.png')

            # Select room
            await page.click('#room-selector .card-item:first-child')
            # Next to Step 3
            await page.click('.next-step[data-next="3"]')
            await asyncio.sleep(1)

            # Fill guest data
            await page.fill('#guest-name', 'Тестовый Пользователь')
            await page.fill('#guest-email', 'test@example.com')
            await page.fill('#guest-phone', '+79991112233')
            # Next to Step 4
            await page.click('.next-step[data-next="4"]')
            await asyncio.sleep(1)
            await page.screenshot(path='guest_step4.png')

            # Confirm booking
            await page.click('#confirm-booking')
            await asyncio.sleep(2)
            await page.screenshot(path='booking_success.png')

            # 2. Check Admin Panel
            print("Checking Admin Panel...")
            await page.goto('http://localhost:8000/admin/index.php')
            await page.screenshot(path='admin_dashboard.png')

            # Check for the booking
            booking_exists = await page.query_selector('text=Тестовый Пользователь')
            if booking_exists:
                print("Booking found in Admin Panel!")
            else:
                print("Booking NOT found in Admin Panel!")

            print("Verification complete.")
        finally:
            server.terminate()
            await server.wait()
            await browser.close()

if __name__ == "__main__":
    asyncio.run(verify())
