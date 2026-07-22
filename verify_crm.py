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
    print("  CRM ADVANCED FEATURE INTEGRATION TESTS  ")
    print("==========================================")

    # 1. Auth Login
    print("\n[TEST 1] Logging in as admin...")
    payload = {"username": "admin", "password": "admin123"}
    status, res, _ = make_request("api/auth.php?action=login", "POST", payload)

    if status == 200 and res.get("success") is True:
        token = res.get("token")
        print(f"  [PASS] Logged in successfully!")
    else:
        print(f"  [FAIL] Login failed: status={status}, response={res}")
        sys.exit(1)

    auth_headers = {"Authorization": f"Bearer {token}"}

    # 2. Create Task with tags
    print("\n[TEST 2] Creating a Task with tags...")
    task_payload = {
        "title": "IT Support - Router Upgrade",
        "description": "Configure and install high-performance dual-band routers on the second floor.",
        "category": "IT Support",
        "priority": "Medium",
        "tags": ["IT", "network", "hardware"],
        "attachments": [],
        "custom_fields": {
            "Location/Room": "Server Room",
            "Asset ID": "RT-404"
        }
    }
    status, task_res, _ = make_request("api/tasks.php", "POST", task_payload, headers=auth_headers)
    if status == 200 and task_res.get("success") is True:
        task_id = task_res["task"]["id"]
        print(f"  [PASS] Task created successfully with tags {task_res['task']['tags']}! ID: {task_id}")
    else:
        print(f"  [FAIL] Task creation failed: status={status}, response={task_res}")
        sys.exit(1)

    # 3. Add Subtask (Checklist Builder)
    print("\n[TEST 3] Adding subtasks to task checklist...")
    subtask_payload = {
        "task_id": task_id,
        "title": "Configure SSID and passphrases"
    }
    status, sub_res, _ = make_request("api/tasks.php?action=add_subtask", "POST", subtask_payload, headers=auth_headers)
    if status == 200 and sub_res.get("success") is True:
        sub_id = sub_res["subtask"]["id"]
        print(f"  [PASS] Subtask added! ID: {sub_id}, title: '{sub_res['subtask']['title']}'")
    else:
        print(f"  [FAIL] Failed to add subtask: response={sub_res}")
        sys.exit(1)

    # 4. Toggle Subtask completion
    print("\n[TEST 4] Toggling subtask completion state...")
    toggle_payload = {
        "task_id": task_id,
        "subtask_id": sub_id,
        "completed": True
    }
    status, toggle_res, _ = make_request("api/tasks.php?action=toggle_subtask", "POST", toggle_payload, headers=auth_headers)
    if status == 200 and toggle_res.get("success") is True:
        print("  [PASS] Subtask completed state set to True.")
    else:
        print(f"  [FAIL] Failed to toggle subtask: response={toggle_res}")
        sys.exit(1)

    # 5. Log Work Hours
    print("\n[TEST 5] Logging work hours on the task...")
    wl_payload = {
        "task_id": task_id,
        "hours": 2.5,
        "notes": "Upgraded router firmware and configured WAN settings."
    }
    status, wl_res, _ = make_request("api/tasks.php?action=add_work_log", "POST", wl_payload, headers=auth_headers)
    if status == 200 and wl_res.get("success") is True:
        print(f"  [PASS] Successfully logged {wl_res['work_log']['hours']} hours.")
    else:
        print(f"  [FAIL] Work log entry failed: response={wl_res}")
        sys.exit(1)

    # 6. Add Customer Satisfaction Feedback Rating
    print("\n[TEST 6] Adding customer satisfaction feedback review...")
    fb_payload = {
        "task_id": task_id,
        "rating": 5,
        "comment": "Excellent upgrade! WiFi speed is blazing fast now."
    }
    status, fb_res, _ = make_request("api/tasks.php?action=add_feedback", "POST", fb_payload, headers=auth_headers)
    if status == 200 and fb_res.get("success") is True:
        print(f"  [PASS] Logged feedback review with {fb_res['feedback']['rating']} stars!")
    else:
        print(f"  [FAIL] Failed to log feedback review: response={fb_res}")
        sys.exit(1)

    # 7. Check specific task details integration (Verify subtasks, logs, and audits exist)
    print("\n[TEST 7] Verifying combined task details integration fields...")
    status, details, _ = make_request(f"api/tasks.php?id={task_id}", "GET", headers=auth_headers)
    if status == 200 and details.get("id") == task_id:
        subtasks = details.get("subtasks", [])
        work_logs = details.get("work_logs", [])
        audit_logs = details.get("audit_logs", [])
        feedback = details.get("feedback", {})

        # Check subtasks match
        assert len(subtasks) == 1 and subtasks[0]["completed"] is True, "Subtasks mismatch"
        # Check work log match
        assert len(work_logs) == 1 and float(work_logs[0]["hours"]) == 2.5, "Work logs mismatch"
        # Check feedback match
        assert feedback and feedback["rating"] == 5, "Feedback rating mismatch"
        # Check audit logs are recorded
        assert len(audit_logs) >= 5, f"Expected audit logs timeline to have multiple steps, got {len(audit_logs)}"

        print(f"  [PASS] All details integrated perfectly: subtasks={len(subtasks)}, work_logs={len(work_logs)}, audit_logs={len(audit_logs)}")
    else:
        print(f"  [FAIL] Failed to load integrated task details: status={status}")
        sys.exit(1)

    print("\n==========================================")
    print("  ALL ADVANCED ENDPOINTS PASSED SECURELY! ")
    print("==========================================")

if __name__ == "__main__":
    run_tests()
