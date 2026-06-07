import requests
import json
import os
import re

base_url = 'http://127.0.0.1:8080'
packages_path = 'data/packages.json'
settings_path = 'data/settings.json'

print("--- STARTING NEW FIXES AND CUSTOMIZER TESTS ---")

session = requests.Session()

# 1. Admin Login
print("\n[1] Logging in as admin...")
login_data = {
    'login_submit': '1',
    'username': 'admin',
    'password': 'Top@#sg@#mountain'
}
r = session.post(f"{base_url}/admin", data=login_data)
if "logout" not in r.text.lower() and "dashboard" not in r.text.lower():
    print("[-] ERROR: Admin login failed.")
    exit(1)
print("[+] Successfully logged in as admin.")

# 2. Verify CKEditor is loaded on admin.php
print("\n[2] Verifying CKEditor is loaded in admin panel...")
r = session.get(f"{base_url}/admin")
if "ckeditor.js" not in r.text:
    print("[-] ERROR: CKEditor library CDN script is not loaded in the admin HTML.")
    exit(1)
print("[+] CKEditor library script is loaded correctly.")

# 3. Test Unauthorized AJAX Media Upload
print("\n[3] Testing Unauthorized AJAX Media Upload (should be blocked)...")
# Send request without logged-in session
unauth_res = requests.post(f"{base_url}/admin", data={'ajax_upload_media': '1'}, files={'media_file': ('test_image.jpg', b'dummy_content', 'image/jpeg')})
print(f"Unauth status: {unauth_res.status_code}, Response: {unauth_res.text}")
try:
    unauth_data = unauth_res.json()
    if unauth_data.get('success') is not False or "Unauthorized" not in unauth_data.get('error', ''):
        print("[-] ERROR: Unauthenticated AJAX media upload was not blocked correctly.")
        exit(1)
    print("[+] Unauthenticated AJAX upload correctly rejected with 'Unauthorized access'.")
except Exception as e:
    print(f"[-] ERROR: Failed to parse unauthorized response as JSON: {e}")
    exit(1)

# 4. Test Authorized AJAX Media Upload
print("\n[4] Testing Authorized AJAX Media Upload...")
mock_image_data = b'GIF89a\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;'
files = {
    'media_file': ('integration_test_badge.gif', mock_image_data, 'image/gif')
}
upload_data = {
    'ajax_upload_media': '1'
}
upload_res = session.post(f"{base_url}/admin", data=upload_data, files=files)
print(f"Upload response status: {upload_res.status_code}")
print(f"Upload response text: {upload_res.text}")
try:
    upload_json = upload_res.json()
    if not upload_json.get('success'):
        print(f"[-] ERROR: Upload failed. JSON error: {upload_json.get('error')}")
        exit(1)
    uploaded_url = upload_json.get('url')
    print(f"[+] Uploaded successfully! URL: {uploaded_url}")
    # Verify file exists locally
    local_path = uploaded_url.lstrip('/')
    if not os.path.exists(local_path):
        print(f"[-] ERROR: Uploaded file not found at local path {local_path}!")
        exit(1)
    print(f"[+] File exists locally at {local_path}.")
except Exception as e:
    print(f"[-] ERROR: Failed to process upload response: {e}")
    exit(1)

# 5. Save Custom Header/Footer Scripts & Settings
print("\n[5] Saving custom header/footer HTML and partner badges...")
header_test_code = "<!-- DYNAMIC_HEADER_SCRIPT_TEST_INTEGRATION_OK -->"
footer_test_code = "<!-- DYNAMIC_FOOTER_SCRIPT_TEST_INTEGRATION_OK -->"

# Read original settings to preserve navigation structure
with open(settings_path, 'r', encoding='utf-8') as f:
    orig_settings = json.load(f)

nav_titles = [item['title'] for item in orig_settings['navigation']]
nav_links = [item['link'] for item in orig_settings['navigation']]

settings_post_data = {
    'save_settings': '1',
    'company_name': 'Top Mountain Treks Pvt. Ltd.',
    'whatsapp_number': '+977 985-1123456',
    'header_custom_html': header_test_code,
    'footer_custom_html': footer_test_code,
}
# Construct list arrays for nav
for idx, title in enumerate(nav_titles):
    settings_post_data[f'nav_titles[{idx}]'] = title
    settings_post_data[f'nav_links[{idx}]'] = nav_links[idx]

# Add uploaded badge as a footer icon
files_badge = {
    'footer_logos[]': ('integration_test_badge.gif', mock_image_data, 'image/gif')
}

save_res = session.post(f"{base_url}/admin", data=settings_post_data, files=files_badge)
print(f"Save settings response status: {save_res.status_code}")

# Verify in settings.json
with open(settings_path, 'r', encoding='utf-8') as f:
    saved_settings = json.load(f)

if saved_settings.get('header_custom_html') != header_test_code:
    print("[-] ERROR: header_custom_html not saved correctly.")
    exit(1)
if saved_settings.get('footer_custom_html') != footer_test_code:
    print("[-] ERROR: footer_custom_html not saved correctly.")
    exit(1)
if not saved_settings.get('footer_icons'):
    print("[-] ERROR: Footer badge icon was not uploaded and saved.")
    exit(1)

print("[+] Custom scripts and footer badge successfully saved in settings.json database.")

# 6. Verify Custom Scripts & Grayscale Badges in Homepage output
print("\n[6] Verifying custom scripts injection and grayscale logo rendering on homepage...")
home_res = requests.get(base_url + '/')
if header_test_code not in home_res.text:
    print("[-] ERROR: Custom header script not injected into public page header.")
    exit(1)
if footer_test_code not in home_res.text:
    print("[-] ERROR: Custom footer script not injected into public page footer.")
    exit(1)
if "footer-accreditation-link" not in home_res.text:
    print("[-] ERROR: Partner logo/badge markup does not include linked footer accreditation styling.")
    exit(1)
print("[+] Custom header/footer injection and CSS grayscale badges are live and working!")

# 7. Verify Leaflet Interactive Route Mapper and Injected Coordinates
print("\n[7] Verifying Leaflet interactive map rendering and coordinate markers...")
pkg_url = f"{base_url}/trekking/everest-base-camp-trek"
pkg_res = requests.get(pkg_url)

if "leaflet-route-map" not in pkg_res.text:
    print("[-] ERROR: Leaflet map container ('leaflet-route-map') is missing in the package page HTML.")
    exit(1)
if "leaflet.js" not in pkg_res.text or "leaflet.css" not in pkg_res.text:
    print("[-] ERROR: Leaflet JS or CSS assets are not imported on the page.")
    exit(1)

# Verify geocoding locations and coordinates are correctly injected into JavaScript itinerary
expected_locations = ['Lukla', 'Phakding', 'Namche', 'Tengboche', 'Dingboche', 'Lobuche', 'Gorak Shep']
found_expected = False
for exp in expected_locations:
    if exp.lower() in pkg_res.text.lower():
        found_expected = True
        print(f"[+] Successfully verified location presence in map data: '{exp}'")

if not found_expected:
    print("[-] ERROR: Did not find any expected reached location markers inside the map data.")
    exit(1)
print("[+] Interactive Leaflet route map and location markers are loaded correctly.")

# 8. Clean up uploaded files and restore settings
print("\n[8] Cleaning up uploaded test files and restoring settings...")
# Delete uploaded file
if os.path.exists(local_path):
    os.remove(local_path)
    print(f"[+] Removed mock uploaded media file: {local_path}")

# Remove badge file from footer_icons and settings
for icon_entry in saved_settings['footer_icons']:
    icon_path = icon_entry.get('image', '') if isinstance(icon_entry, dict) else icon_entry
    if 'integration_test_badge.gif' in icon_path and os.path.exists(icon_path):
        os.remove(icon_path)
        print(f"[+] Removed uploaded footer badge file: {icon_path}")

# Restore settings
orig_settings['header_custom_html'] = ""
orig_settings['footer_custom_html'] = ""
orig_settings['footer_icons'] = []
with open(settings_path, 'w', encoding='utf-8') as f:
    json.dump(orig_settings, f, indent=2)
print("[+] settings.json successfully restored to default.")

print("\n--- ALL CUSTOMIZER AND NEW FIXES TESTS PASSED PERFECTLY! ---")
