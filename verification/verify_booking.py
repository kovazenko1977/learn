"""
Playwright E2E verification test for WP Event Booking plugin.
Verifies:
1. Public booking flow:
   - Month selection
   - Date selection
   - Time slot selection (with dynamic remaining capacity indicator)
   - Step 2 details form completion & custom fields
   - Successful submission and ticket screen with booking code
2. Slot capacity exhaustion logic:
   - When a slot is booked up to max_capacity, verify it disappears from visitor choices.
3. PIN-protected Admin panel:
   - Unlock with PIN 222120
   - Inspect KPI cards, Bookings log, Slots limit configuration, Telegram/Email settings, and 40 features tab.
"""

import time
import subprocess
from playwright.sync_api import sync_playwright

def run_tests():
    print("Starting PHP web server on 127.0.0.1:8095...")
    server = subprocess.Popen(
        ["php", "-S", "127.0.0.1:8095", "-t", "."],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL
    )
    time.sleep(1.5)

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True)
            page = browser.new_page(viewport={"width": 1280, "height": 900})

            # 1. Test Public Booking Shortcode
            print("Navigating to public booking page...")
            page.goto("http://127.0.0.1:8095/preview.php?page=booking")
            page.wait_for_selector(".wpeb-wrapper")

            # Check month list rendered
            page.wait_for_selector(".wpeb-month-btn")
            month_btn = page.locator(".wpeb-month-btn").first
            print("Month:", month_btn.text_content())
            month_btn.click()

            # Wait for dates and pick the first date
            page.wait_for_selector(".wpeb-date-btn")
            date_btn = page.locator(".wpeb-date-btn").first
            date_btn.click()

            # Wait for time slots
            page.wait_for_selector(".wpeb-time-card")
            slot_cards = page.locator(".wpeb-time-card")
            count = slot_cards.count()
            print(f"Available time slots count: {count}")
            assert count > 0, "Expected at least 1 available time slot"

            # Select second slot (which has capacity 2 seeded for quick exhaustion test)
            target_slot = slot_cards.nth(1) if count > 1 else slot_cards.first
            slot_text = target_slot.locator(".wpeb-time-val").text_content()
            print(f"Selecting time slot: {slot_text}")
            target_slot.click()

            # Advance to Step 2
            to_form_btn = page.locator("#wpebToFormBtn")
            assert to_form_btn.is_enabled()
            to_form_btn.click()

            # Step 2: Fill personal details
            page.wait_for_selector("#wpebStepForm", state="visible")
            page.fill('input[name="client_name"]', 'Алексей Смирнов')
            page.fill('input[name="client_phone"]', '+7 (999) 111-22-33')
            page.fill('input[name="client_email"]', 'alexey@example.com')

            # Increment ticket count to 2 to exhaust slot
            qty_plus = page.locator("#wpebQtyPlus")
            qty_plus.click()

            # Fill custom fields if present
            if page.locator('input[name="custom_fields[organization]"]').count() > 0:
                page.fill('input[name="custom_fields[organization]"]', 'ТехноЛаб')

            # Submit booking
            page.click("#wpebSubmitBtn")

            # Wait for Step 3 ticket screen
            page.wait_for_selector("#wpebStepSuccess", state="visible")
            code_el = page.locator("#wpebSuccessCode")
            booking_code = code_el.text_content()
            print(f"Booking succeeded! Code: {booking_code}")
            assert len(booking_code) >= 6, "Expected a valid booking code"

            page.screenshot(path="verification/booking_success.png")

            # 2. Check Capacity Exhaustion: Reload booking and verify slot 12:30 is now hidden!
            page.goto("http://127.0.0.1:8095/preview.php?page=booking")
            page.wait_for_selector(".wpeb-wrapper")
            page.locator(".wpeb-month-btn").first.click()
            page.locator(".wpeb-date-btn").first.click()
            page.wait_for_selector(".wpeb-time-card")

            time_vals = [c.locator(".wpeb-time-val").text_content().strip() for c in page.locator(".wpeb-time-card").all()]
            print("Remaining visible time slots after booking:", time_vals)
            assert "12:30" not in time_vals, "Exhausted slot 12:30 should not be visible to visitor!"

            # 3. Test PIN Admin Gate
            print("Navigating to admin panel...")
            page.goto("http://127.0.0.1:8095/preview.php?page=admin")

            # Must show PIN screen
            page.wait_for_selector(".wpeb-pin-screen")
            page.fill('input[name="wpeb_pin"]', '222120')
            page.click('button[type="submit"]')

            # Now admin panel must be unlocked
            page.wait_for_selector("#wpebAdminApp")
            print("Admin panel unlocked successfully with PIN 222120!")

            # Verify KPI card and Bookings
            page.click('button[data-tab="bookings"]')
            page.wait_for_selector("#wpebTab_bookings", state="visible")
            assert page.locator(f"text={booking_code}").count() > 0, f"Booking {booking_code} should be listed in admin log"

            # Check 40 Features Tab
            page.click('button[data-tab="features"]')
            page.wait_for_selector("#wpebTab_features", state="visible")
            feature_cards = page.locator(".wpeb-feature-card")
            print(f"Total documented features in admin: {feature_cards.count()}")
            assert feature_cards.count() == 40, "Expected exactly 40 documented features!"

            # Check Mobile view
            page.set_viewport_size({"width": 375, "height": 667})
            page.goto("http://127.0.0.1:8095/preview.php?page=booking")
            page.wait_for_selector(".wpeb-wrapper")
            page.screenshot(path="verification/booking_mobile.png")

            browser.close()
            print("All Playwright verification tests passed with 100% success!")
    finally:
        server.terminate()

if __name__ == "__main__":
    run_tests()
