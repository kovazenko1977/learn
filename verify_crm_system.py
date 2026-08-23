import asyncio
import os
import subprocess
import time
from playwright.async_api import async_playwright

async def main():
    # Start the server process
    server_process = subprocess.Popen(
        ["node", "backend/index.js"],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE
    )
    time.sleep(3) # Wait for Express server startup

    try:
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True)
            page = await browser.new_page(viewport={"width": 1280, "height": 800})

            # 1. Login
            await page.goto("http://localhost:5000")
            await page.wait_for_selector("input[placeholder='admin']")
            await page.fill("input[placeholder='admin']", "admin")
            await page.fill("input[placeholder='••••••••']", "admin123")
            await page.click("button[type='submit']")

            # Wait for dashboard KPI element
            await page.wait_for_selector("text=Распределение заявок по статусам", timeout=10000)
            print("Successfully logged in and reached Dashboard!")

            # 2. Navigate to Tickets page via sidebar
            await page.click("aside button:has-text('Заявки')")
            await page.wait_for_selector("text=Список (List)", timeout=5000)
            print("Successfully navigated to Tickets page!")

            # 3. Create a ticket using API for quick setup
            res_status = await page.evaluate("""
                async () => {
                    const token = localStorage.getItem('crm_token');
                    const res = await fetch('/api/tickets', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`
                        },
                        body: JSON.stringify({
                            title: 'Не работает ПК в бухгалтерии',
                            description: 'Компьютер не включается после грозы.',
                            category: 'IT',
                            priority: 'High'
                        })
                    });
                    return res.status;
                }
            """)
            print(f"Ticket creation API status: {res_status}")

            # Navigate away and back to reload state
            await page.click("aside button:has-text('Дашборд')")
            await page.wait_for_selector("text=Распределение заявок по статусам", timeout=5000)

            await page.click("aside button:has-text('Заявки')")
            await page.wait_for_selector("text=Не работает ПК в бухгалтерии", timeout=5000)
            print("Successfully created and verified ticket in UI list!")

            # 4. Take screenshot of Tickets UI with created ticket
            os.makedirs("verification", exist_ok=True)
            screenshot_path = "verification/crm_tickets.png"
            await page.screenshot(path=screenshot_path)
            print(f"Saved screenshot to {screenshot_path}")

            await browser.close()
    finally:
        server_process.terminate()
        server_process.wait()

if __name__ == "__main__":
    asyncio.run(main())
