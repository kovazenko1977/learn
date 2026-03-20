import asyncio
from playwright.async_api import async_playwright

async def verify():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        context = await browser.new_context(viewport={'width': 1280, 'height': 800})
        page = await context.new_page()

        # Start PHP server
        server = await asyncio.create_subprocess_exec(
            'php', '-S', 'localhost:8000',
            stdout=asyncio.subprocess.PIPE,
            stderr=asyncio.subprocess.PIPE
        )
        await asyncio.sleep(2)

        try:
            print("1. Testing Guest Booking Flow...")
            await page.goto('http://localhost:8000/index.php')

            # Step 1: Program & Date
            await page.click('.card-item:first-child')
            await page.fill('#check-in', '2023-12-01')
            await page.click('.next-step[data-next="2"]')
            await asyncio.sleep(1)

            # Step 2: Room
            await page.click('#room-selector .card-item:first-child')
            await page.click('.next-step[data-next="3"]')
            await asyncio.sleep(1)

            # Step 3: Guest Data
            await page.fill('#guest-name', 'Безопасный Пользователь')
            await page.fill('#guest-email', 'secure@example.com')
            await page.fill('#guest-phone', '+79991112233')
            await page.click('.next-step[data-next="4"]')
            await asyncio.sleep(1)

            # Step 4: Summary & Confirm
            await page.click('#confirm-booking')
            await asyncio.sleep(2)
            await page.screenshot(path='v2_booking_success.png')

            print("2. Testing Admin Authentication...")
            await page.goto('http://localhost:8000/admin/index.php')
            # Should redirect to login
            if 'login.php' in page.url:
                print("Correctly redirected to login page.")
            else:
                print(f"FAILED: Expected login redirect, but got {page.url}")

            print("3. Logging in as Admin...")
            await page.fill('input[name="passcode"]', '123456')
            await page.click('button[type="submit"]')
            await asyncio.sleep(1)

            if 'index.php' in page.url:
                print("Login successful.")
                await page.screenshot(path='v2_admin_dashboard.png')
            else:
                print(f"FAILED: Login failed, current URL: {page.url}")

            print("4. Testing Sidebar Navigation...")
            await page.click('text=Программы')
            await asyncio.sleep(1)
            if 'programs.php' in page.url:
                print("Navigation to Programs works.")
            else:
                print(f"FAILED: Programs link broken, current URL: {page.url}")

            print("Verification complete.")
        finally:
            server.terminate()
            await server.wait()
            await browser.close()

if __name__ == "__main__":
    asyncio.run(verify())
