import requests
import json

BASE_URL = 'http://localhost:8000/api'

def test_lookup_access():
    print("Testing Lookup Access for Non-Admins...")

    # Login as restricted user
    payload = {'login': 'restricted', 'password': 'admin123'}
    r = requests.post(f'{BASE_URL}/auth.php?action=login', json=payload)
    if r.status_code != 200:
        print(f"FAILED: Login failed for restricted user. Status: {r.status_code}")
        return False

    token = r.json()['token']
    headers = {'Authorization': f'Bearer {token}'}

    # Try to access lookup actions
    lookup_actions = ['users', 'worktypes', 'departments']
    for action in lookup_actions:
        r = requests.get(f'{BASE_URL}/admin.php?action={action}', headers=headers)
        print(f"Action {action} as restricted user: {r.status_code}")
        if r.status_code != 200:
            print(f"FAILED: Restricted user cannot access lookup action {action}")
            return False

    print("SUCCESS: Lookup access checks passed!")
    return True

if __name__ == '__main__':
    if test_lookup_access():
        print("Done.")
    else:
        print("Test FAILED.")
        exit(1)
