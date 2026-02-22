import asyncio
from playwright.async_api import async_playwright

async def verify():
    async with async_playwright() as p:
        browser = await p.chromium.launch()

        # Check login page
        page = await browser.new_page()
        await page.goto('http://localhost:8000/login.php')
        content = await page.content()
        if "Разработчик wes.by" in content:
            print("Login page: Developer credit found.")
        else:
            print("Login page: Developer credit NOT found.")
        await page.screenshot(path='verification/login_footer.png')

        # Check main page (need to login)
        # Assuming admin code is 123456 as per previous steps or standard
        # Let's check users.json to find a valid code
        import json
        with open('data/users.json', 'r') as f:
            users = json.load(f)
            code = users[0]['code']

        await page.fill('#code-input', code)
        await page.click('button[type="submit"]')
        await page.wait_for_url('**/index.php')

        content = await page.content()
        if "Разработчик wes.by" in content:
            print("Index page: Developer credit found.")
        else:
            print("Index page: Developer credit NOT found.")

        # Scroll to bottom
        await page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
        await page.screenshot(path='verification/index_footer.png')

        # Mobile view check
        mobile_page = await browser.new_page(viewport={'width': 375, 'height': 667})
        await mobile_page.goto('http://localhost:8000/index.php')
        await mobile_page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
        await mobile_page.screenshot(path='verification/mobile_footer.png')
        content = await mobile_page.content()
        if "Разработчик wes.by" in content:
            print("Mobile Index page: Developer credit found.")
        else:
            print("Mobile Index page: Developer credit NOT found.")

        await browser.close()

if __name__ == "__main__":
    asyncio.run(verify())
