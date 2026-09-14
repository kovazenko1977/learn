import base64

# Minimal valid 1x1 blue PNG
png_b64 = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=="
png_bytes = base64.b64decode(png_b64)

with open('frontend/public/icon-192.png', 'wb') as f:
    f.write(png_bytes)

with open('frontend/public/icon-512.png', 'wb') as f:
    f.write(png_bytes)
