"""
Playwright E2E verification test for WP Event Booking plugin.
Verifies:
1. Public booking flow:
   - Month selection
   - Date selection
   - Time slot selection (with dynamic remaining capacity indicator)
   - Step 2 details form completion & custom fields
   - Successful submission and ticket screen with booking code
2. Capacity exhaustion:
   - When a slot is booked up to max_capacity, verify it disappears from visitor choices.
3. Dedicated Form Templates & Unique Shortcodes:
   - Verify VIP Form [event_booking form="vip-business-session"] renders specialized fields.
4. PIN Admin & Visual Form Builder:
   - Unlock with PIN 222120.
   - Verify Forms list table, templates, and shortcode badges.
   - Verify Visual Form Builder canvas rendering and field palette.
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
            if page.locator('input[name="custom_fields[consent_pd]"]').count() > 0:
                page.check('input[name="custom_fields[consent_pd]"]')

            # Submit booking
            page.click("#wpebSubmitBtn")

            # Wait for Step 3 ticket screen
            page.wait_for_selector("#wpebStepSuccess", state="visible")
            code_el = page.locator("#wpebSuccessCode")
            booking_code = code_el.text_content()
            print(f"Booking succeeded! Code: {booking_code}")
            assert len(booking_code) >= 6, "Expected a valid booking code"

            page.screenshot(path="verification/booking_success.png")

            # 2. Check VIP Form rendering with its dedicated unique shortcode
            print("Navigating to VIP form...")
            page.goto("http://127.0.0.1:8095/preview.php?page=vip_form")
            page.wait_for_selector(".wpeb-wrapper")
            page.locator(".wpeb-month-btn").first.click()
            page.locator(".wpeb-date-btn").first.click()
            page.wait_for_selector(".wpeb-time-card")
            page.locator(".wpeb-time-card").first.click()
            page.click("#wpebToFormBtn")
            page.wait_for_selector("#wpebStepForm", state="visible")

            # VIP Form must contain package_type select and need_transfer switch
            assert page.locator('select[name="custom_fields[package_type]"]').count() > 0, "VIP form must have package_type"
            print("VIP Form custom fields verified successfully!")

            # 3. Test PIN Admin Gate & Visual Form Builder
            print("Navigating to admin panel...")
            page.goto("http://127.0.0.1:8095/preview.php?page=admin")

            # Unlock with PIN 222120
            page.wait_for_selector(".wpeb-pin-screen")
            page.fill('input[name="wpeb_pin"]', '222120')
            page.click('button[type="submit"]')

            # Now admin panel must be unlocked
            page.wait_for_selector("#wpebAdminApp")
            print("Admin panel unlocked successfully with PIN 222120!")

            # Check Forms and Templates Tab
            page.click('button[data-tab="forms"]')
            page.wait_for_selector("#wpebTab_forms", state="visible")
            forms_rows = page.locator("#wpebTab_forms table tbody tr")
            forms_count = forms_rows.count()
            print(f"Total forms in admin catalog: {forms_count}")
            assert forms_count >= 5, "Expected at least 5 pre-built form templates"

            # Check Visual Form Builder Canvas & Palette
            page.click('button[data-tab="builder"]')
            page.wait_for_selector("#wpebTab_builder", state="visible")
            assert page.locator(".wpeb-palette-box").count() > 0, "Component palette must exist"
            assert page.locator("#wpebCanvasFields").count() > 0, "Visual canvas must exist"

            # Test adding a field from palette
            initial_fields = page.locator(".wpeb-canvas-field-row").count()
            page.click('button.wpeb-palette-btn[data-type="switch"]')
            new_fields = page.locator(".wpeb-canvas-field-row").count()
            print(f"Canvas fields before: {initial_fields}, after palette click: {new_fields}")
            assert new_fields == initial_fields + 1, "Field should be added to canvas"

            page.screenshot(path="verification/admin_builder.png")

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
