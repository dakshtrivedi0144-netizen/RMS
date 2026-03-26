import urllib.request
import urllib.error
import urllib.parse

BASE = "http://localhost:3030"

paths = [
    "AI_ML/ai-ml.html",
    "WEB D/web-development.html",
    "CYBER S/cyber_security.html",
    "APP DEV/app.html",
    "AR_VR/ar-vr.html",
    "DATA SCIENCE/data-science.html",
    "AI_ML/machine-learning-roadmap.html",
    "AI_ML/deep-learning-roadmap.html",
    "AI_ML/nlp-roadmap.html",
    "AI_ML/computer-vision-roadmap.html",
    "AI_ML/reinforcement-learning-roadmap.html",
    "AI_ML/auto-ml-mlops-roadmap.html",
    "AI_ML/speech-recognition-generation-roadmap.html",
    "AI_ML/ai-robotics-roadmap.html",
    "AI_ML/ai-cybersecurity-roadmap.html",
    "AI_ML/ai-finance-roadmap.html",
    "AI_ML/ai-healthcare-roadmap.html",
    "WEB D/frontend-roadmap.html",
    "WEB D/backend-roadmap.html",
    "WEB D/mern-stack.html",
    "WEB D/mean-stack.html",
    "WEB D/ui-ux.html",
    "WEB D/devops.html",
    "WEB D/database-management.html",
    "WEB D/cloud-integration.html",
    "WEB D/responsive-mobile-app.html",
    "WEB D/web-game.html",
    "CYBER S/network-security.html",
    "CYBER S/ethical-hacking.html",
    "CYBER S/cryptography.html",
    "CYBER S/digital-forensics.html",
    "CYBER S/application-security.html",
    "DATA SCIENCE/deep-learning.html",
    "DATA SCIENCE/nlp.html",
    "AR_VR/ar-vr-development-roadmap.html",
    "APP DEV/mobile_app.html",
    "APP DEV/desktop_app.html",
    "APP DEV/game_development.html",
    "login.html", "register.html", "home.html", "dashboard.html", "index.html",
]

print("=" * 55)
print("LINK CHECK REPORT")
print("=" * 55)
ok = broken = 0
for p in paths:
    encoded = p.replace(' ', '%20')
    url = f"{BASE}/{encoded}"
    try:
        r = urllib.request.urlopen(url)
        print(f"  OK  {p}")
        ok += 1
    except urllib.error.HTTPError as e:
        print(f"  BROKEN [{e.code}]  {p}")
        broken += 1
    except Exception as ex:
        print(f"  ERROR  {p} -> {ex}")
        broken += 1

print("=" * 55)
print(f"RESULT: {ok} OK  |  {broken} BROKEN")
print("=" * 55)
