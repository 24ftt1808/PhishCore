# PhishCore

**Centralised Phishing Website Detection Platform** — Final Year Project, Level 5 Diploma in Applications Development, Politeknik Brunei.

Live site: **https://phishcore.tech**

PhishCore helps anyone check suspicious links, emails, phone numbers, screenshots and files, and gives a clear verdict with the reasons behind it. It is built with Brunei in mind, with detection for local brands, Malay scam wording and local scam patterns.

## Features

### Four scan types
- **Link scan** — domain age (WHOIS), SSL validity, Google Safe Browsing, VirusTotal, IP reputation and location, redirect chain, previous reports of the same domain, page content analysis, free-hosting platform detection, form action security and site availability.
- **Email scan** — sender domain age, brand mimicry, sender-domain mismatch and scam content patterns.
- **Phone scan** — number validation and line type (default region Brunei), fabricated number patterns and prior report counts.
- **Screenshot scan** — reads the text in an image with OCR, finds links and email addresses in it, and runs them through the link scan.

### Verdicts
Each scan gives a risk score from 0 to 100: **0–24 clean**, **25–59 suspicious**, **60–100 phishing**. A check that fails or times out is shown as **UNKNOWN**, never as safe.

### Optional AI help
- **AI text check and AI page review** (Google Gemini, off by default) add an extra signal to scans.
- **Cora**, the in-app AI safety adviser chatbot (Groq), for logged-in users.

### Platform
- User accounts, dashboard, scan history and analytics, with CSV export of history.
- **Investigations** — team members can open, assign and track takedown cases, with a status timeline and in-app notifications.
- **Public Reports** — a public page of confirmed suspicious and phishing URLs, with stats and trends and a downloadable feed (CSV/JSON).
- **Shareable scan links** — share a result without revealing who scanned it, and turn the link off at any time.
- **Page preview** — an optional, display-only screenshot of a scanned page, never used in scoring.
- **Phish Lab** — quiz and minigames to learn how to spot phishing.
- PDF report download for link scans.

## Tech stack

Laravel (PHP), MySQL, Blade, Tailwind CSS, Alpine.js and Vite. Deployed on a DigitalOcean droplet with Nginx and HTTPS.

## Limitations

PhishCore gives guidance, not a guarantee. A clean result does not prove a link, message or number is safe, and some checks depend on third-party services that can be unavailable.

## License

Copyright © 2026 PhishCore. All rights reserved.

PhishCore is an academic project (Final Year Project, Politeknik Brunei). The code may not be copied, reused, modified or republished without the author's written permission.

PhishCore is built on the Laravel framework, which is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).