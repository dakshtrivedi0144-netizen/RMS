import urllib.request, urllib.error, json, time

BASE = "https://rms-chi-brown.vercel.app"

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
print("LIVE VERCEL TESTS — " + BASE)
print("=" * 50)

s, d = get(f"{BASE}/api/health")
print(f"[1] Health       -> {s} | {d}")

s, d = get(f"{BASE}/api/stats")
print(f"[2] Stats        -> {s} | {d}")

uid = str(int(time.time()))
s, d = post(f"{BASE}/api/auth/register", {"username": f"user{uid}", "email": f"u{uid}@test.com", "password": "Test123"})
print(f"[3] Register     -> {s} | {'token OK' if 'token' in d else d}")
token = d.get("token")

s, d = post(f"{BASE}/api/auth/login", {"email": f"u{uid}@test.com", "password": "Test123"})
print(f"[4] Login        -> {s} | {'token OK' if 'token' in d else d}")
token = d.get("token", token)

s, d = get(f"{BASE}/api/auth/user", token)
print(f"[5] Get user     -> {s} | {d.get('username','?')} / {d.get('email','?')}")

s, d = post(f"{BASE}/api/auth/login", {"email": f"u{uid}@test.com", "password": "wrong"})
print(f"[6] Wrong pw     -> {s} | {d}")

print("=" * 50)
print("ALL LIVE TESTS DONE")
print("=" * 50)
