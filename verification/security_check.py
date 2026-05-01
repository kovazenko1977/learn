from playwright.sync_api import sync_playwright
import os
import requests

def test_admin_permissions():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()

        # 1. Login as normal user (restricted)
        page.goto('http://localhost:8000')
        page.fill('input[name="login"]', 'restricted')
        page.fill('input[name="password"]', 'admin123')
        page.click('button[type="submit"]')
        page.wait_for_selector('#app-content')

        # Get JWT from localStorage
        token = page.evaluate("localStorage.getItem('token')")

        # 2. Try to access admin endpoint with normal user token
        headers = {'Authorization': f'Bearer {token}'}
        r = requests.get('http://localhost:8000/api/admin.php?action=users', headers=headers)
        print(f"Normal user GET /api/admin.php?action=users: {r.status_code}")
        if r.status_code != 403:
             print("FAILURE: Normal user should be forbidden from admin actions")
             exit(1)

        # 3. Disable auth globally and try again
        # We need admin access to disable auth.
        # But wait, the system says we can disable auth and THEN anyone can access.
        # Let's verify the "auth disabled" logic.

        browser.close()

def test_auth_disabled_logic():
    # Force disable auth via storage for testing if we can't do it via API easily now
    # Actually let's try to do it via API as admin first
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()

        page.goto('http://localhost:8000')
        page.fill('input[name="login"]', 'admin')
        page.fill('input[name="password"]', 'admin123')
        page.click('button[type="submit"]')
        page.wait_for_selector('a[data-view="admin"]')

        token = page.evaluate("localStorage.getItem('token')")
        headers = {'Authorization': f'Bearer {token}'}

        # Disable auth
        r = requests.post('http://localhost:8000/api/admin.php?action=update_settings',
                          headers=headers, json={'auth_enabled': False})
        print(f"Admin disable auth: {r.status_code}")

        # Now try to access users without token
        r = requests.get('http://localhost:8000/api/admin.php?action=users')
        print(f"No-auth GET /api/admin.php?action=users: {r.status_code}")
        if r.status_code != 200:
            print("FAILURE: Should be accessible when auth is disabled")
            exit(1)

        # Re-enable auth (should be allowed even without token when auth is disabled)
        r = requests.post('http://localhost:8000/api/admin.php?action=update_settings',
                          json={'auth_enabled': True})
        print(f"No-auth enable auth: {r.status_code}")

        # Now try to access users without token again - should be 401 now (or 403)
        r = requests.get('http://localhost:8000/api/admin.php?action=users')
        print(f"Back-to-auth GET /api/admin.php?action=users: {r.status_code}")
        if r.status_code not in [401, 403]:
            print(f"FAILURE: Should NOT be accessible when auth is enabled. Got {r.status_code}")
            exit(1)

        browser.close()

if __name__ == '__main__':
    try:
        test_admin_permissions()
        test_auth_disabled_logic()
        print("ALL TESTS PASSED")
    except Exception as e:
        print(f"ERROR: {e}")
        exit(1)
