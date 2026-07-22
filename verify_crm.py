#!/usr/bin/env python3
import json
import urllib.request
import urllib.error
import urllib.parse
import sys

BASE_URL = "http://127.0.0.1:8000"

def make_request(path, method="GET", data=None, headers=None):
    url = f"{BASE_URL}/{path}"
    req_headers = {
        "Content-Type": "application/json"
    }
    if headers:
        req_headers.update(headers)

    req_data = None
    if data is not None:
        req_data = json.dumps(data).encode("utf-8")

    req = urllib.request.Request(url, data=req_data, headers=req_headers, method=method)
    try:
        with urllib.request.urlopen(req) as response:
            res_data = response.read()
            return response.status, json.loads(res_data.decode("utf-8")), response.info()
    except urllib.error.HTTPError as e:
        try:
            err_data = e.read().decode("utf-8")
            return e.code, json.loads(err_data), e.headers
        except Exception:
            return e.code, {"error": e.reason}, e.headers
    except Exception as e:
        print(f"[FAIL] Request failed: {e}")
        return 0, {"error": str(e)}, None

def run_tests():
    print("==========================================")
    print("  CRM SYSTEM BACKEND TEST SUITE           ")
    print("==========================================")

    # 1. Auth Login (Success case)
    print("\n[TEST 1] Testing Auth Login (admin / admin123)...")
    payload = {"username": "admin", "password": "admin123"}
    status, res, headers = make_request("api/auth.php?action=login", "POST", payload)

    if status == 200 and res.get("success") is True:
        token = res.get("token")
        print(f"  [PASS] Login successful! Token received: {token[:20]}...")
    else:
        print(f"  [FAIL] Login failed: status={status}, response={res}")
        sys.exit(1)

    auth_headers = {"Authorization": f"Bearer {token}"}

    # 2. Auth Login (Failure case)
    print("\n[TEST 2] Testing Auth Login with invalid credentials...")
    bad_payload = {"username": "admin", "password": "wrong_password"}
    status, res, _ = make_request("api/auth.php?action=login", "POST", bad_payload)
    if status == 401 and res.get("success") is False:
        print("  [PASS] Correctly rejected login with 401 Unauthorized.")
    else:
        print(f"  [FAIL] Expected 401, got status={status}, response={res}")
        sys.exit(1)

    # 3. Retrieve settings
    print("\n[TEST 3] Testing Settings API endpoint...")
    status, settings, _ = make_request("api/settings.php", "GET", headers=auth_headers)
    if status == 200 and "categories" in settings:
        print(f"  [PASS] Successfully retrieved settings. Categories: {settings['categories']}")
    else:
        print(f"  [FAIL] Settings retrieval failed: status={status}, response={settings}")
        sys.exit(1)

    # 4. Create Task
    print("\n[TEST 4] Creating a new Service Task...")
    task_payload = {
        "title": "Leaking Pipe in Room 104",
        "description": "Hot water pipe is leaking from under the sink.",
        "category": "Repair",
        "priority": "High",
        "attachments": [],
        "custom_fields": {
            "Location/Room": "Room 104",
            "Asset ID": "WP-9908"
        }
    }
    status, task_res, _ = make_request("api/tasks.php", "POST", task_payload, headers=auth_headers)
    if status == 200 and task_res.get("success") is True:
        task_id = task_res["task"]["id"]
        print(f"  [PASS] Task created successfully! ID: {task_id}")
        print(f"  [INFO] Calculated SLA Deadline: {task_res['task']['deadline']}")
    else:
        print(f"  [FAIL] Task creation failed: status={status}, response={task_res}")
        sys.exit(1)

    # 5. Retrieve Tasks & Filter Visibility
    print("\n[TEST 5] Retrieve task list & verify visibility...")
    status, task_list, _ = make_request("api/tasks.php", "GET", headers=auth_headers)
    if status == 200 and len(task_list) > 0:
        found_task = next((t for t in task_list if t["id"] == task_id), None)
        if found_task:
            print(f"  [PASS] Newly created task found in retrieved list.")
        else:
            print(f"  [FAIL] Created task with ID {task_id} not found in the list.")
            sys.exit(1)
    else:
        print(f"  [FAIL] Failed to retrieve task list or list is empty. Status: {status}")
        sys.exit(1)

    # 6. Retrieve Specific Task Details (with comments)
    print("\n[TEST 6] Retrieve specific task detail by ID...")
    status, task_detail, _ = make_request(f"api/tasks.php?id={task_id}", "GET", headers=auth_headers)
    if status == 200 and task_detail.get("id") == task_id:
        print(f"  [PASS] Retreived details: status='{task_detail.get('status')}', comments_count={len(task_detail.get('comments', []))}")
    else:
        print(f"  [FAIL] Task details retrieve failed: status={status}, response={task_detail}")
        sys.exit(1)

    # 7. Add Comment to the Task
    print("\n[TEST 7] Adding a comment to the task...")
    comment_payload = {
        "task_id": task_id,
        "content": "Plumber scheduled to arrive in 30 minutes.",
        "attachments": []
    }
    status, comment_res, _ = make_request("api/tasks.php?action=comment", "POST", comment_payload, headers=auth_headers)
    if status == 200 and comment_res.get("success") is True:
        print(f"  [PASS] Comment added successfully! Comment: '{comment_res['comment']['content']}'")
    else:
        print(f"  [FAIL] Failed to add comment: status={status}, response={comment_res}")
        sys.exit(1)

    # 8. Update Task status
    print("\n[TEST 8] Updating task status to 'in_work'...")
    status, update_res, _ = make_request(f"api/tasks.php?id={task_id}", "PUT", {"status": "in_work"}, headers=auth_headers)
    if status == 200 and update_res.get("success") is True:
        print("  [PASS] Task status updated to 'in_work'")
    else:
        print(f"  [FAIL] Status update failed: status={status}, response={update_res}")
        sys.exit(1)

    # 9. Verify Analytics
    print("\n[TEST 9] Testing Analytics dashboard endpoint...")
    status, analytics, _ = make_request("api/analytics.php?action=dashboard", "GET", headers=auth_headers)
    if status == 200 and "status_counts" in analytics:
        print(f"  [PASS] Analytics returned dashboard: status_counts={analytics['status_counts']}")
    else:
        print(f"  [FAIL] Analytics failed: status={status}, response={analytics}")
        sys.exit(1)

    # 10. Verify Unauthorized access on analytics
    print("\n[TEST 10] Testing access control (No token)...")
    status, analytics_no_token, _ = make_request("api/analytics.php?action=dashboard", "GET")
    if status == 403 or status == 401:
        print(f"  [PASS] Correctly blocked unauthorized access with status {status}")
    else:
        print(f"  [FAIL] Analytics without token should fail, but returned status={status}")
        sys.exit(1)

    print("\n==========================================")
    print("  ALL BACKEND INTEGRATION TESTS PASSED!   ")
    print("==========================================")

if __name__ == "__main__":
    run_tests()
