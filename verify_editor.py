from playwright.sync_api import Page, expect, sync_playwright
import time

def verify_news_editor(page: Page):
    # Login
    page.goto("http://localhost:8000/login.php")
    page.fill('input[name="pin"]', "123456")
    page.click('button[type="submit"]')

    # Wait for dashboard
    expect(page).to_have_url("http://localhost:8000/admin/index.php")

    # Go to add news
    page.goto("http://localhost:8000/admin/news.php?action=add")

    # Check for Quill editor
    editor = page.locator("#editor-container")
    expect(editor).to_be_visible()

    # Check if image button is in toolbar
    image_btn = page.locator(".ql-image")
    expect(image_btn).to_be_visible()

    # Check for HTML toggle
    html_btn = page.locator("#toggle-html")
    expect(html_btn).to_be_visible()

    # Check if window.Quill is defined (crucial for ImageResize)
    is_quill_defined = page.evaluate("typeof window.Quill !== 'undefined'")
    print(f"Quill defined: {is_quill_defined}")

    # Take screenshot of the editor
    page.screenshot(path="editor_fixed.png")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        try:
            verify_news_editor(page)
        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="error.png")
        finally:
            browser.close()
