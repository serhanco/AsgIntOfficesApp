# Project Backlog & Future Features

This document contains ideas, planned features, and improvements for future iterations of the Acıbadem International Offices application.

## 1. Smart Geolocation & Global Assistance Fallback

**Context:** 
Currently, the "Find Nearest Office" feature on the homepage relies purely on Haversine distance (bird's-eye view). This can lead to awkward suggestions, such as suggesting an office in a neighboring country (e.g., suggesting Burgas, Bulgaria for a user in Istanbul) which is technically closer but impractical due to international borders and travel requirements.

**Proposed Solution:**
*   **Reverse Geocoding:** When retrieving user coordinates via `navigator.geolocation`, use a Reverse Geocoding API (e.g., OpenStreetMap Nominatim) to determine the user's current country.
*   **Same-Country Priority:** If Acıbadem has physical offices in the user's current country, only calculate and show the nearest office *within* that country.
*   **Global Assistance Fallback:** If there are NO offices in the user's country, do not show a cross-border office as the primary result. Instead, display a premium "Global Assistance" UI card.
    *   *Suggested Copy:* "World-class care is a single click away." / "We don't have a physical office in your country yet, but our international team is ready to assist you."
    *   *Actions:* Direct buttons to the Global Call Center, International WhatsApp line, and Global Email.
    *   *Secondary Info:* Optionally display the physically closest international office (e.g., "Nearest physical office: Burgas, 300km") as a small, secondary footnote.
