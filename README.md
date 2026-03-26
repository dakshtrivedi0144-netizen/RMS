# RMS — Resource Management System
### AI-Powered Learning Path Platform

---

## Tech Stack

| Layer      | Technology                        |
|------------|-----------------------------------|
| Backend    | Python 3 + Flask                  |
| Auth       | JWT (PyJWT) + bcrypt              |
| Database   | JSON file (db.json)               |
| Frontend   | HTML5, CSS3, Vanilla JS           |
| Hosting    | Local / Any Python-capable server |

---

## Project Structure

```
RMS/
├── app.py                  # Flask backend (main entry point)
├── db.json                 # JSON database (auto-created)
├── .env                    # Environment variables
│
├── index.html              # Landing page
├── login.html              # Login page
├── register.html           # Register page
├── home.html               # Learning paths explorer + search
├── dashboard.html          # User dashboard
│
├── AI_ML/                  # AI & Machine Learning roadmaps (11)
├── WEB D/                  # Web Development roadmaps (10)
├── CYBER S/                # Cybersecurity roadmaps (11)
├── DATA SCIENCE/           # Data Science roadmaps (13)
├── AR_VR/                  # AR/VR roadmaps (12)
└── APP DEV/                # App Development roadmaps (6)
```

---

## Getting Started

### 1. Install dependencies
```bash
pip install flask flask-cors pyjwt bcrypt
```

### 2. Run the server
```bash
python app.py
```

### 3. Open in browser
```
http://localhost:3030
```

---

## API Endpoints

| Method | Endpoint              | Description              | Auth     |
|--------|-----------------------|--------------------------|----------|
| GET    | /api/health           | Server health check      | Public   |
| GET    | /api/stats            | Platform statistics      | Public   |
| POST   | /api/auth/register    | Register new user        | Public   |
| POST   | /api/auth/login       | Login and get JWT token  | Public   |
| GET    | /api/auth/user        | Get current user info    | Required |

---

## Features

- 50+ expert-curated learning roadmaps across 6 domains
- AI-powered learning path generator
- JWT-based authentication with bcrypt password hashing
- Real-time search across all paths
- Persistent JSON database (no MongoDB/MySQL needed)
- Responsive glassmorphism UI
