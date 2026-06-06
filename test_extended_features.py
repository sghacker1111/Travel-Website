import requests
import json
import os

base_url = 'http://127.0.0.1:8080'
packages_path = 'data/packages.json'

print("--- STARTING EXTENDED FEATURE TESTS ---")

# 1. Clean URLs verification
print("\n[1] Verifying Clean URL routing via router.php...")
urls_to_test = [
    '/trekking/everest-base-camp-trek',
    '/tour/annapurna-base-camp-helicopter-tour',
    '/blog',
]

for url in urls_to_test:
    r = requests.get(base_url + url)
    print(f"Request to {url}: Status {r.status_code}")
    if r.status_code != 200:
        print(f"[-] ERROR: Clean URL {url} failed with status {r.status_code}")
        exit(1)
    if "top mountain treks" not in r.text.lower():
        print(f"[-] ERROR: Clean URL {url} output missing company name branding.")
        exit(1)
print("[+] Clean URL routing passes successfully!")

# 2. Review submission verification
print("\n[2] Submitting traveler review...")
pkg_slug = 'everest-base-camp-trek'
submit_url = f"{base_url}/trekking/{pkg_slug}"

review_data = {
    'submit_review': '1',
    'rev_name': 'Integration Test Traveler',
    'rev_rating': '5',
    'rev_comment': 'An unforgettable journey organized by Top Mountain!'
}

r = requests.post(submit_url, data=review_data)
print(f"Review submit request status: {r.status_code}")

# Check packages.json
if not os.path.exists(packages_path):
    print("[-] ERROR: packages.json does not exist!")
    exit(1)

with open(packages_path, 'r', encoding='utf-8') as f:
    packages = json.load(f)

ebc_pkg = None
for p in packages:
    if p['slug'] == pkg_slug:
        ebc_pkg = p
        break

if not ebc_pkg:
    print(f"[-] ERROR: Could not find package {pkg_slug} in JSON.")
    exit(1)

reviews = ebc_pkg.get('reviews', [])
test_rev = None
test_rev_idx = -1
for idx, rev in enumerate(reviews):
    if rev['name'] == 'Integration Test Traveler':
        test_rev = rev
        test_rev_idx = idx
        break

if not test_rev:
    print("[-] ERROR: Review was not written to packages.json!")
    exit(1)

print(f"[+] Found review in JSON: {test_rev}")
if test_rev['approved'] is not False:
    print("[-] ERROR: Submitted review is not marked as approved=false (pending moderation)!")
    exit(1)
print("[+] Review saved and correctly marked as approved=false.")

# 3. Admin Login & Moderation verification
print("\n[3] Logging in as admin and approving review...")
session = requests.Session()

login_data = {
    'login_submit': '1',
    'username': 'admin',
    'password': 'Top@#sg@#mountain'
}

r = session.post(f"{base_url}/admin", data=login_data)
print(f"Login request status: {r.status_code}")
if "logout" not in r.text.lower() and "dashboard" not in r.text.lower():
    print("[-] ERROR: Admin login failed or response doesn't indicate dashboard access.")
    exit(1)
print("[+] Successfully logged in as admin.")

# Send approval request
approve_data = {
    'approve_review': '1',
    'rev_pkg_id': ebc_pkg['id'],
    'rev_idx': str(test_rev_idx)
}

r = session.post(f"{base_url}/admin", data=approve_data)
print(f"Approve review request status: {r.status_code}")

# Verify packages.json again
with open(packages_path, 'r', encoding='utf-8') as f:
    packages = json.load(f)

ebc_pkg = next(p for p in packages if p['slug'] == pkg_slug)
updated_rev = ebc_pkg['reviews'][test_rev_idx]

print(f"[+] Updated review in JSON: {updated_rev}")
if updated_rev['approved'] is not True:
    print("[-] ERROR: Review was not marked as approved=true after admin request!")
    exit(1)
print("[+] Review successfully marked as approved=true in database.")

# 4. Public visibility verification
print("\n[4] Verifying review is visible on the package details page...")
r = requests.get(submit_url)
if "Integration Test Traveler" not in r.text or "An unforgettable journey" not in r.text:
    print("[-] ERROR: Approved review is not visible in the HTML of the package page!")
    exit(1)
print("[+] Review is live and visible to visitors!")

# Clean up: delete test review
print("\n[5] Cleaning up test review...")
delete_data = {
    'delete_review': '1',
    'rev_pkg_id': ebc_pkg['id'],
    'rev_idx': str(test_rev_idx),
    'delete_confirm_password': login_data['password']
}
r = session.post(f"{base_url}/admin", data=delete_data)
print(f"Delete request status: {r.status_code}")

with open(packages_path, 'r', encoding='utf-8') as f:
    packages = json.load(f)

ebc_pkg = next(p for p in packages if p['slug'] == pkg_slug)
if any(rev['name'] == 'Integration Test Traveler' for rev in ebc_pkg.get('reviews', [])):
    print("[-] WARNING: Could not clean up the test review.")
else:
    print("[+] Test review cleaned up successfully.")

print("\n--- ALL EXTENDED FEATURE TESTS PASSED PERFECTLY! ---")
