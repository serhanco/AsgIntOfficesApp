import re
import json

with open("/Users/serhandemirel/.gemini/antigravity/brain/a2ddad09-f972-4cc1-989a-50de248b3de2/.system_generated/steps/529/content.md", "r") as f:
    html = f.read()

pattern = re.compile(r'<div class="ci-card"[^>]*>.*?<span class="ci-name">([^<]+)</span>', re.IGNORECASE | re.DOTALL)
matches = pattern.findall(html)

results = [m.strip() for m in matches]

with open("institutions.json", "w") as f:
    json.dump(results, f, indent=2)

print(f"Extracted {len(results)} institutions")
