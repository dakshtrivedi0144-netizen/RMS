import urllib.request
import urllib.error
import json

BASE = "http://localhost:3030"

def post(url, data):
    body = json.dumps(data).encode()
    req = urllib.request.Request(url, data=body, headers={"Content-Type": "application/json"}, method="POST")
    try:
        r = urllib.request.urlopen(req)
        return r.status, json.loads(r.read().decode())
    except urllib.error.HTTPError as e:
        return e.code, json.loads(e.read().decode())

def get(url, token=None):
    headers = {"Authorization": f"Bearer {token}"} if token else {}
    req = urllib.request.Request(url, headers=headers)
    try:
        r = urllib.request.urlopen(req)
        return r.status, json.loads(r.read().decode())
    except urllib.error.HTTPError as e:
        return e.code, json.loads(e.read().decode())

print("=" * 50)
print("RMS API ENDPOINT TESTS")
print("=" * 50)

# 1. Health
s, d = get(f"{BASE}/api/health")
print(f"\n[1] GET /api/health          -> {s} | {d}")

# 2. Stats
s, d = get(f"{BASE}/api/stats")
print(f"[2] GET /api/stats           -> {s} | {d}")

# 3. Register new user
s, d = post(f"{BASE}/api/auth/register", {"username":"testcheck","email":"check@rms.com","password":"Test123","learningStyle":"visual","currentLevel":"beginner"})
print(f"[3] POST /api/auth/register  -> {s} | {'token received' if 'token' in d else d}")
token = d.get("token")

# 4. Duplicate register
s, d = post(f"{BASE}/api/auth/register", {"username":"testcheck","email":"check@rms.com","password":"Test123"})
print(f"[4] POST /register (dup)     -> {s} | {d}")

# 5. Login correct
s, d = post(f"{BASE}/api/auth/login", {"email":"check@rms.com","password":"Test123"})
print(f"[5] POST /api/auth/login     -> {s} | {'token received' if 'token' in d else d}")
token = d.get("token", token)

# 6. Login wrong password
s, d = post(f"{BASE}/api/auth/login", {"email":"check@rms.com","password":"wrongpass"})
print(f"[6] POST /login (wrong pw)   -> {s} | {d}")

# 7. Login wrong email
s, d = post(f"{BASE}/api/auth/login", {"email":"nobody@rms.com","password":"Test123"})
print(f"[7] POST /login (wrong email)-> {s} | {d}")

# 8. Get user with valid token
s, d = get(f"{BASE}/api/auth/user", token)
print(f"[8] GET /api/auth/user       -> {s} | {d}")

# 9. Get user with no token
s, d = get(f"{BASE}/api/auth/user")
print(f"[9] GET /auth/user (no token)-> {s} | {d}")

# 10. Short password
s, d = post(f"{BASE}/api/auth/register", {"username":"x","email":"x@x.com","password":"123"})
print(f"[10] POST /register (short pw)-> {s} | {d}")

print("\n" + "=" * 50)
print("ALL TESTS DONE")
print("=" * 50)
