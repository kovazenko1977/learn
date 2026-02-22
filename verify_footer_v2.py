import asyncio
from playwright.async_api import async_playwright
import json

async def verify():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        context = await browser.new_context()

        # Check login page
        page = await context.new_page()
        await page.goto('http://localhost:8000/login.php')
        content = await page.content()
        if "Разработчик wes.by" in content:
            print("Login page: Developer credit found.")

        # Login
        with open('data/users.json', 'r') as f:
            users = json.load(f)
            code = users[0]['code']

        await page.fill('#code-input', code)
        await page.click('button[type="submit"]')
        await page.wait_for_url('**/index.php')

        # Check Index page
        content = await page.content()
        if "Разработчик wes.by" in content:
            print("Index page: Developer credit found.")

        # Mobile view check (using same context to keep session)
        await page.set_viewport_size({'width': 375, 'height': 667})
        await page.reload()
        await page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
        await page.screenshot(path='verification/mobile_index_footer.png')
        content = await page.content()
        if "Разработчик wes.by" in content:
            print("Mobile Index page: Developer credit found.")

        await browser.close()

if __name__ == "__main__":
    asyncio.run(verify())
