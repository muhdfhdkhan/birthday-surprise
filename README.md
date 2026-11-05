# Birthday Surprise 🎂🐾

A multi-profile, password-protected, animated birthday story with funny cats —
plus a full admin panel. Built on the agreed stack: HTML5, CSS3, vanilla JS,
PHP 8+, PDO, MySQL/MariaDB, GSAP, SweetAlert2, canvas-confetti.

> **Status (Phases 1–6 complete)**: admin auth + dashboard, password gate /
> birthday-window access control, full animated visitor story (welcome →
> apologies → candle reveal → memories → letter → gift/complaint replies →
> finale), admin content editor, media manager (photos + music with
> thumbnails), and access-request workflow with one-time codes.
> Remaining roadmap items: analytics, polish, deploy.

---

## Folder structure

```
Birthday/
├── admin/                  Admin panel page views (login.php, dashboard.php, …)
├── app/
│   ├── bootstrap.php       Config, autoloader, session, error handling
│   ├── helpers.php         url(), e(), flash(), csrf… helpers
│   ├── Controllers/
│   │   ├── Admin/          Profiles, Content, Media, Requests
│   │   ├── Visitor/        StoryController (gate/unlock/story/responses)
│   │   └── …               Auth + Dashboard
│   ├── Core/               Router, Database (PDO), Session, Auth, Csrf, View
│   ├── Middleware/         AuthMiddleware (auth / guest straps)
│   ├── Models/             Profile, Content, GalleryImage, MediaAsset,
│                           AccessRequest/Grant, VisitorResponse, Admin…
│   ├── Services/           Uploader (GD thumbnails), RateLimiter, ActivityLogger
│   └── Views/
│       ├── admin/          profiles / profile_form / content / media / requests
│       ├── layouts/        admin.php + visitor.php
│       ├── visitor/        gate.php + story.php (7 animated stages)
│       └── …               cat-style error pages (404 / 500)
├── config/                 app.php (live) + app.example.php
├── cron/                   cleanup.php (rate-limit pruning)
├── database/               schema.sql (17 tables) + seed.sql + migrations.sql
├── public/                 Front controller (index.php), assets, uploads
├── routes/                 web.php (full route table)
├── storage/                logs, private-uploads, backups (web-denied)
├── tests/                  smoke.php (env check) · smoke3.ps1 (28-check e2e) ·
│                           cleanup.php (removes test profiles/logs)
├── .htaccess               Denies everything except /public
└── README.md
```

## Install on XAMPP

1. **Expose the project** — this is already configured on this machine
   (`httpd-birthday.conf` uses the same `Alias` pattern as the other projects
   on the X: drive). The relevant lines in Apache:
   `conf/extra/httpd-birthday.conf`:

   ```apache
   Alias /birthday-surprise "X:/Vibe/Birthday/public"
   <Directory "X:/Vibe/Birthday/public">
       Options FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```

   Included from `httpd.conf` → restart Apache after changes
   (`C:\xampp\apache\bin\httpd.exe -k restart` for a service install,
   or stop & relaunch the console instance).

   Because only `/public` is aliased, config files, database dumps and the
   rest of the repo are never reachable over HTTP.

2. **Run the web installer** — open the site once in the browser:

   ```
   http://localhost/birthday-surprise/
   ```

   Without `config/app.php` the app redirects to **`/install.php`**, a
   one-time installer that asks for:

   - **Database**: host, port, name, user, password (creates the DB if it
     doesn't exist; optional "erase old database" for a brand-new install/reset)
   - **Admin account**: display name, username, email and YOUR chosen
     password (hashed with bcrypt — nothing ad-hoc is seeded)
   - **App**: name + environment (production hides all error output)

   It then builds the schema (from `database/schema.sql`), seeds starter
   settings, writes `config/app.php` and **deletes itself**. Nothing else
   to remove afterwards. No default/printed credentials exist anywhere —
   the admin password only ever exists if you typed it during install.

   To start over: delete `config/app.php`, re-upload `install.php`
   (from the repo it's in the project root under `public/`), reload —
   the installer runs again and can wipe the old database.

3. **Check the environment** — PHP needs the **GD extension** enabled
   (image thumbnails) and **mysqli** (installer). In `C:\xampp\php\php.ini`
   make sure `extension=gd` and `extension=mysqli` are uncommented.
   Dev sanity checks (optional, after install):

   ```
   C:\xampp\php\php.exe X:\Vibe\Birthday\tests\smoke.php
   powershell -File X:\Vibe\Birthday\tests\smoke3.ps1   # full 28-check e2e
   ```

   Note: keep `database/` out of the deployed docroot — only `public/`
   is exposed.

4. **Open it** (Apache + MySQL running in the XAMPP control panel):

   | Where | URL |
   |---|---|
   | Admin login | http://localhost/birthday-surprise/admin/login |
   | Admin dashboard | http://localhost/birthday-surprise/admin |
   | Admin: profiles / requests | …/admin/profiles · …/admin/requests |
   | Admin: content & media per profile | …/admin/profiles/{id}/content · …/admin/profiles/{id}/media |
   | Entry point | http://localhost/birthday-surprise/ |
   | Visitor story | http://localhost/birthday-surprise/b/{slug} |

   ### Visitor endpoints

   | Method | Path | Purpose |
   |---|---|---|
   | GET | `/b/{slug}` | Password gate / countdown / animated story |
   | POST | `/b/{slug}/unlock` | Gate password (rate-limited: 5 tries / 15 min) |
   | POST | `/b/{slug}/access-request` | Visitor asks admin for access |
   | POST | `/b/{slug}/redeem` | Redeem one-time access code |
   | POST | `/api/b/{slug}/response` | Gift / complaint / reaction (JSON) |
   | POST | `/api/b/{slug}/event` | Client interaction beacons (JSON, allow-listed) |
   | GET | `/admin/analytics` | Visitor dashboard — `?profile=id`, `?days=7..90` filter |

## Admin login (no defaults exist)

There are **no default credentials** — nothing is printed anywhere. The
first admin account (username + email + your password) is created during
the web install step above; the password is stored as a bcrypt hash
(`password_hash` / `password_verify`) with cost 12. Forgot it? Delete rows
from `admins` in the DB (phpMyAdmin) and visit `/install.php`-style setup
again by deleting `config/app.php` + re-adding the installer, or insert a
new hash manually.

On sign-in you land on the dashboard: **"Welcome, {name} 👋"** with a small
confetti burst and a SweetAlert toast.

## Security already wired

- CSRF token on every form + AJAX endpoint, rotated on login/logout
- `session_regenerate_id(true)` on privilege change; HttpOnly + SameSite=Lax cookies
- Password / access-request / redeem rate limits: DB-backed per IP + slug (cat-nap cool-down)
- `password_verify()` timing equalised for unknown usernames
- Login failures + success logged to `admin_audit_logs`
- Root `.htaccess` denies all non-public paths; uploads execute nothing;
  `storage/` is web-denied
- Client event beacons (`/api/b/{slug}/event`) are status-token-checked,
  allow-listed per event name and flood-capped (120 / 10 min)

## Analytics (Phase 9)

Every visit, unlock attempt and interaction lands in `activity_logs`
(IP, session id, pseudonymous visitor id, browser / device / OS, referrer,
stage). The admin dashboard at **`/admin/analytics`** shows:

- page visits, unique visitors & sessions, password success rate
- a 14/30/90-day activity bar chart and per-event breakdown
- most active IPs with device & session counts, browser/device split
- the access-request trail and a recent-activity feed table
- per-profile filtering via `?profile={id}` (sidebar → **Analytics**)

 stalking note: no cookies are set for visitors; the visitor id is a random
 UUID stored in the session only, IPs are stored for abuse protection.

## Admin Inbox, Requests & Settings (Phase 10)

- **Inbox (`/admin/inbox`)** — every letter a visitor sent through the story:
  gift wishes (with the chosen chip), complaint/love letters and finale
  reactions. Filter by profile, type and status chips
  (unread/read/archived); actions: mark as read, archive, restore, delete —
  all CSRF + audited in `admin_audit_logs`. Sidebar shows a live unread
  badge.
- **Requests (`/admin/requests`)** — pending access requests with a live
  pending badge; the approve form's default window now comes from
  `access_window_hours` in Settings.
- **Settings (`/admin/settings`)** — global key/values in `app_settings`,
  whitelist-validated and applied everywhere:
  | Setting | Wired to |
  |---|---|
  | `app_name` | Admin brand, page titles, footer |
  | `maintenance_mode` | Every `/b/{slug}` page → friendly 503 "Be right back" card |
  | `visitor_privacy_note` | The privacy line on the password gate |
  | `access_window_hours` | Default code window on request approvals |
  | `uploads_max_mb` | Media upload cap in `Uploader` (1–10 MB) |
  | `uploads_allowed_types` | Allowed image extensions (bounded to jpg/jpeg/png/webp) |
  | `log_retention_days` | Reference for log cleanups |
  | `app_timezone` | Reference fallback timezone |

## Roadmap (per project brief)

1. ✅ Foundation — structure, schema, admin auth
2. ✅ Access — routing `/b/{slug}`, password gate, sessions, re-entry workflow
3. ✅ Story — apology sequence, cats, candle-blow reveal, typewriter letter
4. ✅ Personalisation — gallery (lightbox), music, letters, gift & complaint boxes, reactions
5. ✅ Analytics — visitor events, IP/session logging, `/admin/analytics` dashboard
6. Polish — themes, accessibility, sound (mostly done: GSAP, confetti, music pill, reduced-motion)
7. Deploy — QA, cPanel, HTTPS, backups
