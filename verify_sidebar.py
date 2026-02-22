import asyncio
from playwright.async_api import async_playwright
import os

async def verify_sidebar_scroll():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        context = await browser.new_context(viewport={'width': 1280, 'height': 800})
        page = await context.new_page()

        # Login
        await page.goto("http://localhost:8080/login.php")
        await page.fill('input[name="code"]', "123456")
        await page.click('button[type="submit"]')
        await page.wait_for_url("http://localhost:8080/index.php")

        # Injected dummy items to force scroll
        await page.evaluate("""() => {
            const nav = document.querySelector('.sidebar-nav');
            for(let i=0; i<30; i++) {
                const a = document.createElement('a');
                a.className = 'sidebar-item';
                a.innerHTML = '<i data-lucide="file"></i><span>Dummy Item ' + i + '</span>';
                nav.appendChild(a);
            }
        }""")

        # Check if scrollable
        is_scrollable = await page.evaluate("""() => {
            const nav = document.querySelector('.sidebar-nav');
            return nav.scrollHeight > nav.clientHeight;
        }""")

        print(f"Sidebar scrollable: {is_scrollable}")

        # Take a screenshot
        if not os.path.exists("verification"):
            os.makedirs("verification")
        await page.screenshot(path="verification/sidebar_scroll.png")

        await browser.close()
        return is_scrollable

if __name__ == "__main__":
    success = asyncio.run(verify_sidebar_scroll())
    if not success:
        exit(1)
