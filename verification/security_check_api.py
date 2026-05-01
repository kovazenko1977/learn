import requests
import json

BASE_URL = 'http://localhost:8000/api'

def test_security():
    print("Testing Security...")

    # 1. Login as admin
    payload = {'login': 'admin', 'password': 'admin123'}
    r = requests.post(f'{BASE_URL}/auth.php?action=login', json=payload)
    if r.status_code != 200:
        print(f"FAILED: Login failed for admin user. Status: {r.status_code}")
        return False

    admin_token = r.json()['token']
    admin_headers = {'Authorization': f'Bearer {admin_token}'}

    # 2. Try to access admin actions as admin
    r = requests.get(f'{BASE_URL}/admin.php?action=users', headers=admin_headers)
    print(f"Action users as admin: {r.status_code}")
    if r.status_code != 200:
        print(f"FAILED: Admin user cannot access users list. Status: {r.status_code}")
        return False

    # 3. Test auth disabled logic
    print("Disabling auth...")
    r = requests.post(f'{BASE_URL}/admin.php?action=update_settings', headers=admin_headers, json={'auth_enabled': False})
    if r.status_code != 200:
        print(f"FAILED: Could not disable auth. Status: {r.status_code}")
        return False

    # Try admin action without token
    r = requests.get(f'{BASE_URL}/admin.php?action=users')
    print(f"Action users with auth disabled (no token): {r.status_code}")
    if r.status_code != 200:
        print(f"FAILED: Admin action should be accessible when auth is disabled. Status: {r.status_code}")
        return False

    # Re-enable auth (no token should be allowed when auth is disabled)
    print("Re-enabling auth...")
    r = requests.post(f'{BASE_URL}/admin.php?action=update_settings', json={'auth_enabled': True})
    if r.status_code != 200:
        print(f"FAILED: Could not re-enable auth. Status: {r.status_code}")
        return False

    # Try admin action without token again
    r = requests.get(f'{BASE_URL}/admin.php?action=users')
    print(f"Action users with auth re-enabled (no token): {r.status_code}")
    if r.status_code not in [401, 403]:
        print(f"FAILED: Admin action should NOT be accessible when auth is enabled. Status: {r.status_code}")
        return False

    print("SUCCESS: Security checks passed!")
    return True

if __name__ == '__main__':
    if test_security():
        print("Done.")
    else:
        print("Test FAILED.")
        exit(1)
