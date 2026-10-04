import subprocess
import time
import os
from playwright.sync_api import sync_playwright

def test_medservice_crm():
    # Start PHP server
    server_process = subprocess.Popen(['php', '-S', '127.0.0.1:8080'])
    time.sleep(2)

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True)
            context = browser.new_context(viewport={'width': 1280, 'height': 800})
            page = context.new_page()

            # Navigate to local server
            page.goto('http://127.0.0.1:8080')
            page.wait_for_load_state('networkidle')

            # Bypass onboarding
            page.evaluate("localStorage.setItem('medservice_onboarding_done', 'true')")
            page.reload()
            page.wait_for_load_state('networkidle')

            # Screenshot login screen
            page.screenshot(path='verification_crm_login.png')
            print("Login screen loaded successfully")

            # Check if login modal is present
            if page.is_visible('#loginModal'):
                page.fill('#loginPhone', '1111')
                page.fill('#loginPassword', '123456')
                page.click('#loginModal button[type="submit"]')
                page.wait_for_timeout(1500)

            # Wait for main workspace
            page.wait_for_selector('#greetingText', timeout=5000)
            page.screenshot(path='verification_crm_dashboard.png')
            print("Dashboard screen loaded successfully")

            # Check requests view
            page.click('a[data-view="requests"]')
            page.wait_for_timeout(1000)
            page.screenshot(path='verification_crm_requests.png')
            print("Requests screen loaded successfully")

            # Check settings view
            page.click('a[data-view="settings"]')
            page.wait_for_timeout(1000)
            page.screenshot(path='verification_crm_settings.png')
            print("Settings screen loaded successfully")

            browser.close()
    finally:
        server_process.terminate()
        server_process.wait()

if __name__ == '__main__':
    test_medservice_crm()
