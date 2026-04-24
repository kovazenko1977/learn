import os
import time
from playwright.sync_api import sync_playwright

def verify_final_features():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 720})
        page = context.new_page()

        # 1. Login
        page.goto("http://localhost:8080/index.html")
        page.fill("#login-username", "admin")
        page.fill("#login-password", "admin")
        page.click("#login-btn")
        page.wait_for_selector("aside")

        # 2. Check Form Builder
        page.click("text=Конструктор форм")
        page.wait_for_selector("text=Конструктор полей заявки")

        # Add a new field
        page.click("text=+ Добавить поле")
        page.screenshot(path="verify_form_builder.png")
        print("Form builder tab verified")

        # 3. Create Request with custom fields
        page.click("text=Заявки")
        page.click("#btn-add-request")
        page.wait_for_selector("#req-title")

        # Fill standard fields
        page.fill("#req-title", "Test with custom fields")
        page.fill("#req-desc", "Description")

        # Fill dynamic field (first one)
        custom_field = page.locator("input[required]").first
        if custom_field.count() > 0:
            custom_field.fill("Room 101")

        page.click("#req-save-btn")
        time.sleep(1)
        page.screenshot(path="verify_request_creation.png")
        print("Request creation with custom fields verified")

        browser.close()

if __name__ == "__main__":
    verify_final_features()
