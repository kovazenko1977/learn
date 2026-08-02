#!/usr/bin/env python3
import urllib.request
import urllib.error
import json
import time

BASE_URL = "http://127.0.0.1:8000/api"

def make_request(url, method="GET", data=None, token=None):
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"

    req_data = json.dumps(data).encode("utf-8") if data else None
    req = urllib.request.Request(f"{BASE_URL}/{url}", data=req_data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req) as response:
            return response.status, json.loads(response.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        try:
            err_body = json.loads(e.read().decode("utf-8"))
        except Exception:
            err_body = None
        return e.code, err_body

def test_api():
    print("--- Starting Backend API Tests ---")

    # 1. Login with invalid credentials
    status, res = make_request("auth.php?action=login", "POST", {"username": "admin", "password": "wrongpassword"})
    assert status == 401, f"Expected 401, got {status}"
    assert res["success"] is False, "Expected success to be False"
    print("✓ Login invalid credentials rejected successfully")

    # 2. Login with valid credentials (default admin)
    status, res = make_request("auth.php?action=login", "POST", {"username": "admin", "password": "admin123"})
    assert status == 200, f"Expected 200, got {status}"
    assert res["success"] is True, "Expected success to be True"
    admin_token = res["token"]
    print("✓ Admin authenticated successfully")

    # 3. Access settings without token
    status, res = make_request("settings.php", "GET")
    assert status == 401, f"Expected 401, got {status}"
    print("✓ Unauthenticated access rejected successfully")

    # 4. Access settings with admin token
    status, settings = make_request("settings.php", "GET", token=admin_token)
    assert status == 200, f"Expected 200, got {status}"
    assert "categories" in settings, "Expected categories in settings"
    print("✓ Settings fetched successfully by admin")

    # Ensure tech1 has a known password (admin123) by updating via admin API
    status, tech_user = make_request("users.php?id=ec5f741717d52b73a7ed760b0d06b69a", "GET", token=admin_token)
    assert status == 200
    tech_user["password"] = "admin123"
    status, save_res = make_request("users.php?id=ec5f741717d52b73a7ed760b0d06b69a", "POST", tech_user, token=admin_token)
    assert status == 200
    print("✓ Reset technician's password successfully via Admin API")

    # 5. Create a new task as admin
    task_data = {
        "title": "Test Task via Python",
        "description": "This is a programmatic test task",
        "category": settings["categories"][0],
        "priority": "Средний"
    }
    status, task_res = make_request("tasks.php", "POST", task_data, token=admin_token)
    assert status == 200, f"Expected 200, got {status}"
    assert task_res["success"] is True
    task_id = task_res["id"]
    print(f"✓ Task created successfully with ID: {task_id}")

    # 6. Fetch all tasks as admin
    status, tasks = make_request("tasks.php", "GET", token=admin_token)
    assert status == 200
    task_found = any(t["id"] == task_id for t in tasks)
    assert task_found, "Created task not found in the list"
    print("✓ Task listing retrieved successfully by admin")

    # 7. Add comment to task
    comment_data = {"content": "Programmatic comment"}
    status, comm_res = make_request(f"tasks.php?id={task_id}", "POST", comment_data, token=admin_token)
    assert status == 200
    assert comm_res["success"] is True
    print("✓ Comment posted successfully")

    # 8. Fetch task by ID with comments
    status, single_task = make_request(f"tasks.php?id={task_id}", "GET", token=admin_token)
    assert status == 200
    assert single_task["title"] == "Test Task via Python"
    assert len(single_task["comments"]) > 0, "No comments found on task"
    assert single_task["comments"][0]["content"] == "Programmatic comment"
    print("✓ Task fetched by ID with comments successfully")

    # 9. Test IDOR prevention / Permission enforcement
    # Let's log in as technician (tech1)
    status, exec_res = make_request("auth.php?action=login", "POST", {"username": "tech1", "password": "admin123"})
    assert status == 200, f"tech1 login failed: {status} / {exec_res}"
    exec_token = exec_res["token"]

    # Executor should not be able to retrieve the unassigned test task by ID (Forbidden)
    status, single_task_exec = make_request(f"tasks.php?id={task_id}", "GET", token=exec_token)
    assert status == 403, f"Expected 403 Forbidden for unassigned task, got {status}"
    print("✓ IDOR prevention: Executor cannot view unassigned task (Forbidden)")

    # Executor should not be able to change status of unassigned task
    status, update_exec = make_request(f"tasks.php?id={task_id}", "POST", {"status": "in_work"}, token=exec_token)
    assert status == 403, f"Expected 403 Forbidden for unassigned task update, got {status}"
    print("✓ IDOR prevention: Executor cannot update status of unassigned task (Forbidden)")

    # 10. Clean up / Delete task as admin
    status, del_res = make_request(f"tasks.php?id={task_id}", "DELETE", token=admin_token)
    assert status == 200
    print("✓ Task deleted successfully by admin")

    print("\n✓ ALL BACKEND API TESTS PASSED SUCCESSFULLY!")

if __name__ == "__main__":
    test_api()
