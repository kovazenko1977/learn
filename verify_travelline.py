import subprocess
import time
import requests
import sys
import os
from playwright.sync_api import sync_playwright

def start_php_server():
    print("Starting local PHP built-in server on port 8000...")
    proc = subprocess.Popen(
        ["php", "-S", "0.0.0.0:8000"],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE
    )
    time.sleep(2) # Allow server to start up
    return proc

def clean_data_dir():
    # Remove any test files to ensure a clean state
    test_files = ["data/config.json", "data/rooms.json", "data/bookings.json", "data/database.sqlite"]
    for f in test_files:
        if os.path.exists(f):
            try:
                os.remove(f)
            except Exception:
                pass

def test_integration():
    clean_data_dir()

    # Verify landing pages load properly
    try:
        r = requests.get("http://127.0.0.1:8000/index.php")
        assert r.status_code == 200, "Landing page did not return 200 OK"
        print("✓ Landing page loads perfectly!")

        r = requests.get("http://127.0.0.1:8000/booking.php")
        assert r.status_code == 200, "Booking Engine page did not return 200 OK"
        print("✓ Booking engine page loads perfectly!")

        r = requests.get("http://127.0.0.1:8000/admin.php")
        assert r.status_code == 200, "WebPMS Admin page did not return 200 OK"
        print("✓ Admin page loads perfectly!")

    except Exception as e:
        print(f"HTTP GET verification failed: {e}")
        sys.exit(1)

    # Perform E2E checkout using Playwright
    with sync_playwright() as p:
        print("Launching Playwright...")
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # --- TEST JSON STORAGE MODE ---
        print("\n--- Testing JSON Storage Mode ---")
        page.goto("http://127.0.0.1:8000/booking.php")
        time.sleep(1)

        print("Selecting Standard Single Room and booking...")
        page.click("text=Выбрать номер")
        time.sleep(1)

        print("Filling out checkout guest details...")
        page.fill("input[name='guest_name']", "Иванов Иван (JSON)")
        page.fill("input[name='guest_email']", "json@travelline-clone.ru")
        page.fill("input[name='guest_phone']", "+7 (999) 777-1111")

        print("Submitting booking form...")
        page.click("button[type='submit']:has-text('Забронировать')")
        time.sleep(2)

        body_text = page.inner_text("body")
        assert "Бронирование успешно оформлено" in body_text
        print("✓ JSON booking successful!")

        # Verify on Admin panel
        page.goto("http://127.0.0.1:8000/admin.php")
        time.sleep(1)
        admin_text = page.inner_text("body")
        assert "Иванов Иван (JSON)" in admin_text
        print("✓ JSON WebPMS sync verified!")

        # --- TEST SQL STORAGE MODE (SQLite) ---
        print("\n--- Testing SQL Storage Mode (SQLite) ---")

        # Switch to SQL mode via settings tab
        print("Switching storage engine to SQL...")
        page.click("text=Настройки БД и Отеля")
        time.sleep(1)

        # Click SQL engine card label
        page.click("text=SQL Движок (PDO)")
        time.sleep(1)

        # Submit the configuration
        page.click("button:has-text('Сохранить конфигурацию хранения')")
        time.sleep(2)
        print("✓ Storage engine successfully changed to SQL (SQLite)!")

        # Check booking with SQL storage mode
        page.goto("http://127.0.0.1:8000/booking.php")
        time.sleep(1)

        print("Selecting Standard Single Room in SQL mode and booking...")
        page.click("text=Выбрать номер")
        time.sleep(1)

        print("Filling out checkout guest details for SQL...")
        page.fill("input[name='guest_name']", "Петров Петр (SQL)")
        page.fill("input[name='guest_email']", "sql@travelline-clone.ru")
        page.fill("input[name='guest_phone']", "+7 (999) 777-2222")

        print("Submitting booking form...")
        page.click("button[type='submit']:has-text('Забронировать')")
        time.sleep(2)

        body_text = page.inner_text("body")
        assert "Бронирование успешно оформлено" in body_text
        print("✓ SQL booking successful!")

        # Verify on Admin panel
        page.goto("http://127.0.0.1:8000/admin.php")
        time.sleep(1)
        admin_text = page.inner_text("body")
        assert "Петров Петр (SQL)" in admin_text
        print("✓ SQL WebPMS sync verified!")

        browser.close()

if __name__ == "__main__":
    proc = start_php_server()
    try:
        test_integration()
        print("\n🎉 ALL E2E INTEGRATION AND DUAL-STORAGE TESTS PASSED SUCCESSFULLY! 🎉")
    finally:
        print("Stopping local PHP server...")
        proc.terminate()
        proc.wait()
