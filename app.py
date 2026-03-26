"""
RMS - Resource Management System
Backend API Server

Tech Stack : Python 3 + Flask
Auth       : JWT (PyJWT) + bcrypt
Database   : JSON file (db.json)
Author     : RMS Team
"""

import os
import json
import time
import datetime
from pathlib import Path
from functools import wraps

from flask import Flask, request, jsonify, send_from_directory, send_file
from flask_cors import CORS
import jwt
import bcrypt


# ─────────────────────────────────────────────
#  App Configuration
# ─────────────────────────────────────────────

BASE_DIR   = Path(__file__).parent
DB_FILE    = BASE_DIR / "db.json"
JWT_SECRET = os.environ.get("JWT_SECRET", "rms_secret_2026")
PORT       = int(os.environ.get("PORT", 3030))

app = Flask(__name__, static_folder=".", static_url_path="")
CORS(app)


# ─────────────────────────────────────────────
#  Database Helpers (JSON file with in-memory fallback for serverless)
# ─────────────────────────────────────────────

_memory_db = {"users": []}  # fallback for serverless (Vercel)

def read_db() -> dict:
    """Read the JSON database. Falls back to in-memory on serverless."""
    try:
        if DB_FILE.exists():
            return json.loads(DB_FILE.read_text())
    except Exception:
        pass
    return _memory_db


def write_db(data: dict) -> None:
    """Persist data. Falls back to in-memory on serverless."""
    global _memory_db
    _memory_db = data
    try:
        DB_FILE.write_text(json.dumps(data, indent=2, default=str))
    except Exception:
        pass  # serverless — keep in memory only


# ─────────────────────────────────────────────
#  JWT Helpers
# ─────────────────────────────────────────────

def create_token(user_id: str, email: str) -> str:
    """Generate a signed JWT valid for 7 days."""
    payload = {
        "userId": user_id,
        "email":  email,
        "exp":    datetime.datetime.utcnow() + datetime.timedelta(days=7),
    }
    return jwt.encode(payload, JWT_SECRET, algorithm="HS256")


def require_auth(f):
    """Decorator — protects routes that need a valid JWT."""
    @wraps(f)
    def wrapper(*args, **kwargs):
        token = request.headers.get("Authorization", "").replace("Bearer ", "")
        if not token:
            return jsonify({"msg": "Authentication required"}), 401
        try:
            request.user = jwt.decode(token, JWT_SECRET, algorithms=["HS256"])
        except jwt.ExpiredSignatureError:
            return jsonify({"msg": "Token has expired"}), 401
        except jwt.InvalidTokenError:
            return jsonify({"msg": "Invalid token"}), 401
        return f(*args, **kwargs)
    return wrapper


# ─────────────────────────────────────────────
#  API Routes — System
# ─────────────────────────────────────────────

@app.route("/api/health")
def health():
    db = read_db()
    return jsonify({
        "status":  "ok",
        "server":  "Python / Flask",
        "db":      "JSON file",
        "users":   len(db["users"]),
    })


@app.route("/api/stats")
def stats():
    db = read_db()
    return jsonify({
        "totalUsers":  len(db["users"]),
        "totalPaths":  50,
        "domains":     6,
    })


# ─────────────────────────────────────────────
#  API Routes — Authentication
# ─────────────────────────────────────────────

@app.route("/api/auth/register", methods=["POST"])
def register():
    body     = request.get_json() or {}
    username = body.get("username", "").strip()
    email    = body.get("email", "").strip().lower()
    password = body.get("password", "")
    style    = body.get("learningStyle", "visual")
    level    = body.get("currentLevel", "beginner")

    # Validation
    if not username or not email or not password:
        return jsonify({"msg": "All fields are required"}), 400
    if len(password) < 6:
        return jsonify({"msg": "Password must be at least 6 characters"}), 400

    db = read_db()

    if any(u["email"] == email for u in db["users"]):
        return jsonify({"msg": "Email already registered"}), 400
    if any(u["username"] == username for u in db["users"]):
        return jsonify({"msg": "Username already taken"}), 400

    # Create user
    hashed = bcrypt.hashpw(password.encode(), bcrypt.gensalt()).decode()
    user = {
        "id":            str(int(time.time() * 1000)),
        "username":      username,
        "email":         email,
        "password":      hashed,
        "learningStyle": style,
        "currentLevel":  level,
        "createdAt":     datetime.datetime.utcnow().isoformat(),
    }
    db["users"].append(user)
    write_db(db)

    token = create_token(user["id"], user["email"])
    safe  = {k: v for k, v in user.items() if k != "password"}
    return jsonify({"token": token, "user": safe}), 201


@app.route("/api/auth/login", methods=["POST"])
def login():
    body     = request.get_json() or {}
    email    = body.get("email", "").strip().lower()
    password = body.get("password", "")

    if not email or not password:
        return jsonify({"msg": "Email and password are required"}), 400

    db   = read_db()
    user = next((u for u in db["users"] if u["email"] == email), None)

    if not user or not bcrypt.checkpw(password.encode(), user["password"].encode()):
        return jsonify({"msg": "Invalid email or password"}), 400

    token = create_token(user["id"], user["email"])
    safe  = {k: v for k, v in user.items() if k != "password"}
    return jsonify({"token": token, "user": safe})


@app.route("/api/auth/user")
@require_auth
def get_user():
    db   = read_db()
    user = next((u for u in db["users"] if u["email"] == request.user["email"]), None)
    if not user:
        return jsonify({"msg": "User not found"}), 404
    safe = {k: v for k, v in user.items() if k != "password"}
    return jsonify(safe)


# ─────────────────────────────────────────────
#  Static File Serving
# ─────────────────────────────────────────────

@app.route("/")
def index():
    return send_file(BASE_DIR / "index.html")


@app.route("/<path:filename>")
def static_files(filename):
    return send_from_directory(BASE_DIR, filename)


# ─────────────────────────────────────────────
#  Entry Point
# ─────────────────────────────────────────────

if __name__ == "__main__":
    print(f"\n  RMS Platform — Python/Flask")
    print(f"  Running on http://localhost:{PORT}\n")
    app.run(host="0.0.0.0", port=PORT, debug=False)
