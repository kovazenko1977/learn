import asyncio
from playwright.async_api import async_playwright
import os
import http.server
import socketserver
import threading
import time

PORT = 8081

class MyHandler(http.server.SimpleHTTPRequestHandler):
    def log_message(self, format, *args):
        pass

def run_server():
    with socketserver.TCPServer(("", PORT), MyHandler) as httpd:
        httpd.serve_forever()

async def verify():
    # Start server in thread
    server_thread = threading.Thread(target=run_server, daemon=True)
    server_thread.start()
    time.sleep(1)

    async with async_playwright() as p:
        browser = await p.chromium.launch()
        page = await browser.new_page()
        await page.goto(f"http://localhost:{PORT}")

        # Initial screenshot
        await page.screenshot(path="russian_initial.png")
        print("Initial screenshot saved.")

        # Test Patrol
        await page.click("#patrol-btn")
        await asyncio.sleep(2.5) # Wait for first event
        await page.screenshot(path="russian_patrolling.png")
        print("Patrol screenshot saved.")

        # Stop Patrol
        await page.click("#patrol-btn")

        # Test Bounty
        await page.click("#bounty-btn")
        await asyncio.sleep(1)
        await page.screenshot(path="russian_bounty.png")
        print("Bounty screenshot saved.")

        await browser.close()

if __name__ == "__main__":
    asyncio.run(verify())
