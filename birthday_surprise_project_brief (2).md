# Birthday Surprise Platform — Project Brief

**Version:** 2.0  **Stack:** HTML5, CSS3, vanilla JavaScript, PHP 8+, MySQL 8 (InnoDB, utf8mb4)
**Hosting:** Localhost first (XAMPP / Laragon), then shared hosting (cPanel)
**One-line summary:** A multi-profile, password-protected, animated birthday story with funny cats, a heartfelt apology sequence, a dramatic birthday reveal, photos, letters, two reply boxes, and a full admin panel with visitor/IP activity logs.

---

## 1. Product Overview

Birthday Surprise is a configurable web application for creating personalised birthday experiences. The admin creates a **birthday profile** for each person, fills it with messages, photos, music and settings, and shares a unique protected link. The visitor goes through a short interactive story, not a plain greeting card:

`Countdown → Welcome → Password → Sorry (cats) → Birthday reveal → Memories → Letter → Gift box + Complaint box → Finale`

The admin controls everything from a dashboard without editing code.

### Goals
1. A memorable, funny, emotional, mobile-first experience.
2. Many birthday profiles under one install, each fully isolated.
3. Protected pages: profile password plus admin-approved re-entry.
4. Proportionate visitor analytics, including IP logs, with a privacy notice.
5. Runs on cheap PHP/MySQL shared hosting.

### Non-goals (v1)
- No public social features, no payments, no user self-registration.
- No precise geolocation or device fingerprinting.

---

## 2. Roles

| Role | Can do |
|---|---|
| **Admin** | Log in, manage profiles, content, media, passwords, access requests, responses, logs, settings, exports, backups |
| **Super admin** (optional) | Everything above plus managing other admins and global settings |
| **Visitor (birthday person)** | Open link, enter password, play the journey, send gift wish and complaint, request re-entry when expired |

---

## 3. Visitor Journey (stage by stage)

### Stage 0 — Countdown (optional)
- Shows days/hours/minutes/seconds to the birthday date/time in the profile's timezone.
- Admin chooses: block access before the date, or allow early peek with a "shh, it's a surprise" cat.
- At zero, automatic transition to the celebration. After the date, a configurable post-birthday message instead of a negative timer.

### Stage 1 — Welcome
- Cheerful kitten with gift and balloons, cats wandering, floating hearts, stars and balloons.
- Mysterious welcome message from admin.
- Button: **Open Your Surprise 🎁**. Skip-animation link. Respects `prefers-reduced-motion`.

### Stage 2 — Password gate
- Profile-specific password, verified server-side with `password_verify()`.
- Friendly cat-themed error ("Meow? Wrong password 🐱"), never reveals if a profile exists.
- Rate limit: e.g. 5 attempts per 10 minutes per IP + profile, then a cool-down cat nap screen.
- Success creates a server session; protected content and media are only served after that.
- **Access window:** each successful unlock grants access for N hours (admin-configurable, default 24h).

### Stage 3 — Funny sorry sequence
- Admin-ordered apology messages shown one by one on a plate/card held by a **sorry cat**.
- Cat actions: crying, begging, hiding, rolling, funny faces, holding a sign.
- Response buttons per message: **"Okay, I forgive you 💖"** and **"Still angry 😾"** (labels editable).
- Reactions:
  - Forgive → cat dances, hearts burst, next message.
  - Still angry → angry cat reaction, escalating funnier apologies (cat offers fish, flowers, runs a tiny sad violin), the "Still angry" button can playfully shrink or dodge after several clicks (admin toggle).
- Admin chooses: required or skippable. Completing it unlocks the **secret hidden message**.

### Stage 4 — Birthday reveal
- Fireworks + confetti transition, big animated **HAPPY BIRTHDAY** heading, person's name in display typography.
- Animated cake with candles. **Blow candles:** tap/click, or microphone blow detection (optional, permission-based, with tap fallback).
- Dancing cat, balloons, party sounds.
- Music player: play/pause, mute, volume. Audio only starts after a user gesture.

### Stage 5 — Photos and memories
- Admin-uploaded photos in an animated responsive gallery / timeline with caption, date and memory text.
- Fullscreen lightbox with swipe, keyboard navigation, focus trap, close button.
- Lazy loading and thumbnails; only published images for this profile.

### Stage 6 — Letter and wishes
- Envelope opens, long personal letter appears with typewriter effect (skip button available).
- Multiple wishes shown as cards in configured order, love cat appears with special wishes.
- Final surprise message unlocked after the configured condition.

### Stage 7 — Two reply boxes (required feature)
1. **🎁 "Which gift do you want?"**
   - Free text, optional category chips set by admin (e.g. Flowers, Cake, Trip, Book), optional "Surprise me!" choice.
2. **💌 "Any complaint, or something you want to tell me?"**
   - Multiline text, tone is warm and private. Never shown publicly.
- Both: server-side validation, length limits (e.g. 1,000 characters), honeypot + rate limit, escaped output, confirmation cat animation.
- Stored per profile and session, visible only in admin. Admin can mark read, archive, delete.
- Optional: allow one edit within 10 minutes of sending.

### Stage 8 — Finale
- Final affectionate message, share button (copies the link only if admin allows), **Replay** button.
- Replay works inside the valid access session and never bypasses the password gate.
- Privacy notice link.

---

## 4. Access Control: Password + Re-entry

**Model:** (1) initial profile password, (2) admin-approved, time-limited re-entry credential. No hard-coded universal password.

| Step | Detail |
|---|---|
| Initial access | Visitor enters profile password → access window starts (default 24h) |
| Expiry | After the window or max-session count, visitor sees **Request Access Again** |
| Request | Visitor sends optional short note → `access_requests` row (pending) linked to profile + session |
| Admin decision | Admin sees it in a pending queue (badge on dashboard), approves with duration (default 24h) or rejects with a reason |
| Grant | On approval, a random one-time code/link is generated, stored **hashed**, expires automatically, single-use (configurable up to N uses) |
| Delivery | Email if SMTP is configured; otherwise admin copies the code/link and sends it manually |
| Redemption | Visitor opens the link or enters the code → new access window |
| Logging | Request, decision, grant, redemption, expiry and revocation all logged |
| Safety | Re-entry never changes the original password; admin can revoke active grants |

---

## 5. Admin Panel

### 5.1 Login and security
- Email/username + password (`password_hash`), CSRF tokens, session regeneration, idle timeout, login rate limit.
- Optional TOTP two-factor (phase 2).
- Logout everywhere, password change, forced change of default password on first login.

### 5.2 Dashboard
- Cards: profiles (draft / scheduled / published / archived), total visitors, unique sessions, page views, completed journeys, forgiveness vs still-angry clicks, gallery views.
- Pending access requests, new gift wishes, new complaints.
- Charts: visits over time, stage completion funnel, device split.
- Recent activity feed and security events. Date range filter.

### 5.3 Birthday profiles
- Create, edit, duplicate as template, archive (soft delete), restore.
- Fields: display name, slug (unique URL `/b/{slug}`), profile picture, birthday date/time/timezone, welcome text, status, password, access duration, re-entry policy.
- Status: draft, scheduled (auto-publishes at a set time), published, paused, archived.
- **Preview as visitor** (bypasses password for admin only, no logging).

### 5.4 Content editor
- Welcome message, sorry messages (add unlimited, drag reorder, per-message cat and reaction), wishes, personal letter (sanitised rich text), secret message and its unlock rule, final message, post-birthday message.
- Enable/disable each section. Draft vs published versions.

### 5.5 Gallery manager
- Multi-upload with drag and drop, caption, alt text, date, memory note, ordering, cover photo, enable/disable, replace, delete.
- Validation: JPEG/PNG/WebP, max size (e.g. 5 MB), max dimensions; server-side re-encoding and thumbnail generation; random filenames.

### 5.6 Audio and effects
- Upload/select background music and sound effects (MP3/OGG), start point, default volume.
- Toggles: confetti, fireworks, hearts, balloons, each cat action, screen transitions, animation speed/intensity.
- Fallbacks if assets fail to load.

### 5.7 Theme and appearance
- Theme presets (Pastel Cats, Romantic Pink, Premium Night), accent colours, fonts, button style, cat character set.
- Mobile / desktop preview toggle.

### 5.8 Access requests
- Tabs: pending, approved, rejected, expired. Approve/reject/revoke, set duration, copy code or link, audit trail.

### 5.9 Visitor responses
- Inbox for gift wishes and complaints with filters (profile, date, read/unread, archived), mark read, archive, delete, CSV export.

### 5.10 Activity logs and analytics
Fields recorded:

| Field | Notes |
|---|---|
| Visitor ID | Random pseudonymous cookie ID (identifies returning visitors) |
| Session ID | Server session reference |
| IP address | Respect proxy headers only from trusted proxies; optional truncation/hash mode |
| Time | UTC timestamp, displayed in admin timezone |
| Profile / page / stage | What was viewed |
| Browser, device, OS | Parsed from user agent |
| Referrer | If available |
| Event | page_view, password_success, password_fail, stage_complete, forgive_click, still_angry_click, candle_blown, photo_open, gift_submit, complaint_submit, access_request, grant_redeem, replay, music_toggle |
| Approx. location | Optional, country/city level only via offline GeoIP database |

Admin tools: search by IP, filter by date/profile/event/device, per-visitor timeline, returning-visitor flag, CSV export (authorised, audited), delete old logs, **auto-retention** (e.g. 90 days via cron), block IP (optional).

**Privacy:** short visitor notice on the password page explaining analytics and IP logging; logs visible to admins only; HTTPS in production; no fingerprinting; configurable retention.

### 5.11 Settings and maintenance
- App name, logo, default theme, timezone, date format, upload limits, allowed types, SMTP, privacy text, retention period.
- **Maintenance mode** with a funny sleeping-cat page.
- **Backup/restore:** SQL export button plus zip of uploads, with restore guide.
- Audit log of admin actions.

---

## 6. Extra Features Added (things easy to miss)
1. Scheduled publishing and auto-reveal at the birthday time.
2. Admin preview with desktop/mobile switch.
3. Visitor reaction emoji at the end (❤️ 😂 😭 🎂) saved to analytics.
4. Interactive gift box and candle blowing (mic optional).
5. Sound effects on/off, music remembers mute state.
6. Easter eggs: tap the cat 5 times for a secret meow.
7. Custom 404, expired-link and offline pages in cat style.
8. Admin notification badge or email when a response or access request arrives.
9. Profile duplication, export/import of a profile as JSON.
10. Auto cleanup cron for logs, expired grants and orphaned uploads.
11. Accessibility: keyboard, focus rings, alt text, reduced-motion mode, contrast checks.
12. Lightweight mode for slow phones (fewer particles).

---

## 7. Functional Requirements
- Every profile has a unique slug and isolated content/media.
- Only published profiles are reachable; protected endpoints require a valid session.
- Prepared statements (PDO) for every query; output escaping; allowlist HTML sanitiser for the letter.
- Upload validation by extension, MIME, content and size; random names; uploads folder denies script execution.
- CSRF on every state-changing request; SameSite, HttpOnly and Secure cookies.
- Rate limits: admin login, password gate, form submissions, access requests.
- Pagination on logs, responses and requests.
- Empty, loading and error states everywhere.

## 8. Non-functional Requirements
- Mobile-first from 320 px up; first meaningful paint under 2.5 s on 4G.
- Images compressed and lazy loaded; animation 60 fps target using transform/opacity.
- WCAG AA contrast, `prefers-reduced-motion` support.
- Works on current Chrome, Safari, Firefox, Edge, plus Android/iOS browsers.
- `display_errors` off in production, private error logs.

---

## 9. Database (MySQL, InnoDB, utf8mb4)

| Table | Purpose |
|---|---|
| `admins` | Admin accounts, password hash, role, last login |
| `birthday_profiles` | Name, slug, birthday datetime, timezone, status, publish_at, cover/profile image |
| `profile_access` | Password hash, access hours, max re-entry uses, policy |
| `access_requests` | Visitor re-entry requests, note, status, decided_by, reason |
| `access_grants` | Hashed code, expires_at, uses_left, redeemed_at, revoked |
| `birthday_content` | Welcome, letter, secret, final, post-birthday text |
| `sorry_messages` | Ordered apologies, cat action, reactions |
| `birthday_messages` | Ordered wishes |
| `gallery_images` | File, thumb, caption, alt, date, order, is_cover, is_active |
| `media_assets` | Audio and animation files |
| `profile_theme_settings` | Theme, colours, fonts, animation toggles and speed |
| `visitors` | Pseudonymous visitor ID, first/last seen, visit count |
| `activity_logs` | Event, IP, UA fields, referrer, stage, timestamp |
| `visitor_responses` | type (gift / complaint / reaction), text, category, status |
| `rate_limits` | Key, attempts, window |
| `admin_audit_logs` | Admin actions with timestamps |
| `app_settings` | Global key/value settings |

Indexes: slug (unique), profile_id + created_at, ip, session_id, status, expires_at. Foreign keys with `ON DELETE CASCADE` for profile-owned data.

---

## 10. Routes and Endpoints

**Public**
- `GET /b/{slug}` — landing/gate
- `POST /b/{slug}/unlock` — password check
- `GET /api/b/{slug}/story` — protected story data (session required)
- `POST /api/b/{slug}/event` — log interaction
- `POST /api/b/{slug}/response` — gift / complaint / reaction
- `POST /api/b/{slug}/access-request` and `POST /api/b/{slug}/redeem`
- `GET /media/{id}` — protected media streaming

**Admin** (`/admin/...`): login, dashboard, profiles, content, gallery, audio, theme, requests, responses, logs, export, settings, backup.

---

## 11. Architecture and Folder Structure

```
birthday-surprise/
├── app/ (Controllers, Services, Middleware, Models, Views)
├── config/app.example.php
├── database/schema.sql, seed.sql
├── public/ (index.php, assets/{css,js,cats,images,audio}, uploads/)
├── admin/
├── routes/ (web.php, api.php)
├── storage/ (logs, private-uploads, backups)
├── cron/ (cleanup.php)
├── tests/
└── README.md
```
Libraries: GSAP (timelines), canvas-confetti, Lottie (cats, optional), custom CSS keyframes.

---

## 12. Deployment

**Localhost:** install XAMPP/Laragon → create DB → import `schema.sql` → copy `app.example.php` to `app.php` → set credentials → open `http://localhost/birthday-surprise/public`.

**Shared hosting (cPanel):** upload files, point domain to `public/`, create DB + restricted user, import schema, set config, enable HTTPS, set folder permissions, add cron for cleanup, turn off error display, test uploads, sessions, email and backups.

---

## 13. Testing Checklist
- Wrong/right password, rate limit, expiry, re-entry request → approve → redeem → expiry.
- Cannot fetch protected media or story JSON without session.
- Both reply boxes save and appear only in admin; XSS payloads are escaped.
- Upload tests: fake extension, oversized, script files.
- Logs record IP and events; CSV export and retention purge work.
- Two profiles stay fully isolated.
- Mobile (320–430 px), tablet, desktop; reduced motion; keyboard only.
- Audio autoplay policy on iOS/Android.

## 14. Acceptance Criteria
1. Admin creates and publishes at least two independent profiles.
2. Each has its own URL and password.
3. Protected content is unreachable without a valid session.
4. Visitor completes the full journey on mobile.
5. Gift wish and complaint are saved and visible only to admin.
6. Expired access shows the re-entry request flow; admin approval issues an expiring one-time credential.
7. IP and activity logs show in admin with filters, CSV export and retention.
8. Countdown, music, gallery, messages, theme and animations are configurable.
9. Works on localhost and shared hosting.

## 15. Implementation Phases
1. **Foundation:** structure, schema, admin auth, profile CRUD.
2. **Access:** routing, password gate, sessions, re-entry workflow.
3. **Story:** apology sequence, cats, birthday reveal.
4. **Personalisation:** gallery, letters, gift and complaint boxes.
5. **Analytics:** events, logs, dashboard, CSV, retention.
6. **Polish:** themes, accessibility, performance, sound.
7. **Deploy:** QA, cPanel setup, HTTPS, backups.

## 16. Risks
| Risk | Mitigation |
|---|---|
| Audio blocked by browsers | Start after tap; visible music button |
| Heavy animations on old phones | Lightweight mode, CSS-first animation |
| IP privacy concerns | Notice, retention, optional IP truncation |
| Shared hosting limits | Small dependencies, file-based fallbacks, cron optional |
| Lost access code | Admin can reissue from dashboard |

> **Product principle:** playful and heartfelt in front, practical and secure behind.
