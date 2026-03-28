import urllib.request
import urllib.error
import urllib.parse

BASE = "http://localhost:3030"

paths = [
    "AI_ML/ai-ml.html",
    "WEBD/web-development.html",
    "CYBERS/cyber_security.html",
    "APPDEV/app.html",
    "AR_VR/ar-vr.html",
    "DATASCIENCE/data-science.html",
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
    "WEBD/frontend-roadmap.html",
    "WEBD/backend-roadmap.html",
    "WEBD/mern-stack.html",
    "WEBD/mean-stack.html",
    "WEBD/ui-ux.html",
    "WEBD/devops.html",
    "WEBD/database-management.html",
    "WEBD/cloud-integration.html",
    "WEBD/responsive-mobile-app.html",
    "WEBD/web-game.html",
    "CYBERS/network-security.html",
    "CYBERS/ethical-hacking.html",
    "CYBERS/cryptography.html",
    "CYBERS/digital-forensics.html",
    "CYBERS/application-security.html",
    "DATASCIENCE/deep-learning.html",
    "DATASCIENCE/nlp.html",
    "AR_VR/ar-vr-development-roadmap.html",
    "APPDEV/mobile_app.html",
    "APPDEV/desktop_app.html",
    "APPDEV/game_development.html",
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
