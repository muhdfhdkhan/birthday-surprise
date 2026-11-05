# Birthday Surprise — Design Specification

**Style name:** *Pastel Purr* — modern, cute, romantic and a little premium.
**Principles:** Soft, bouncy, glowing, never cluttered. Funny cats carry the personality; clean layout and generous space carry the professionalism.

---

## 1. Design Pillars
1. **Cute first:** round shapes, big eyes, pastel gradients, tiny sparkles.
2. **Funny motion:** cats overreact. Exaggerated squash-and-stretch, comic timing.
3. **Emotional depth:** slow, warm moments (letter, photos) balance the silly ones.
4. **Premium finish:** frosted glass cards, soft shadows, gold accents on key moments, tidy typography.
5. **Mobile first:** thumb-friendly, one idea per screen.

---

## 2. Colour System

| Token | Hex | Use |
|---|---|---|
| `--blush` | `#FFD6E7` | Backgrounds, soft fills |
| `--rose` | `#FF6FA5` | Primary buttons, hearts |
| `--rose-deep` | `#E5487F` | Hover/active, links |
| `--lavender` | `#CDB8FF` | Secondary surfaces, stars |
| `--grape` | `#6B4EE6` | Headings accent, focus |
| `--mint` | `#BFF3E0` | Success, forgive state |
| `--butter` | `#FFE9A8` | Highlights, candle glow |
| `--gold` | `#F5C451` | Premium accents, birthday title |
| `--cream` | `#FFF8F3` | Page background |
| `--ink` | `#3A2B4D` | Text |
| `--ink-soft` | `#7A6A8C` | Secondary text |
| `--danger` | `#FF5C7A` | Errors, angry cat |
| `--night` | `#1E1633` | Premium dark mode background |

**Gradients**
- Hero: `linear-gradient(160deg,#FFD6E7 0%,#E8DCFF 50%,#FFF3D6 100%)`
- Button: `linear-gradient(135deg,#FF6FA5,#FF8FB8)`
- Night: `linear-gradient(160deg,#1E1633,#3A1F5C)` with gold and pink glows.

Dark/premium theme swaps cream → night, cards become glass with 12% white fill.

---

## 3. Typography

| Role | Font | Weight | Size (mobile / desktop) |
|---|---|---|---|
| Display / birthday title | **Baloo 2** or *Fredoka* | 700 | 44 / 88 px |
| Name script | **Pacifico** or *Dancing Script* | 400 | 40 / 72 px |
| Headings | Fredoka | 600 | 24 / 36 px |
| Body | **Nunito** | 400–600 | 16 / 18 px |
| Letter (handwritten) | *Caveat* | 500 | 22 / 26 px |
| Admin UI | Inter | 400–600 | 14 / 15 px |

Line height 1.5 body, 1.1 display. Letter spacing +0.02em on buttons.

---

## 4. Spacing, Shape, Elevation
- Spacing scale: 4, 8, 12, 16, 24, 32, 48, 64.
- Radius: 12 (inputs), 20 (cards), 28 (modals), 999 (pills/buttons).
- Shadows: `0 6px 20px rgba(229,72,127,.18)` (card), `0 12px 40px rgba(107,78,230,.22)` (modal), glow `0 0 24px rgba(245,196,81,.6)` (gold).
- Glass card: `background: rgba(255,255,255,.65); backdrop-filter: blur(14px); border:1px solid rgba(255,255,255,.7)`.
- Layout: max width 480 px for the story column on desktop centred, 1200 px for admin.

---

## 5. Components

**Buttons**
- Primary: pill, rose gradient, white text, 52 px high on mobile, press = scale .96, hover = lift + glow.
- Secondary: white fill, rose border.
- Ghost: text only for Skip / Replay.
- "Still angry 😾": grape outline; may wobble or dodge (optional).

**Inputs:** 52 px, 16 px text (prevents iOS zoom), rounded 16, lavender border, rose focus ring 3 px.

**Cards:** glass, 20 radius, 20 px padding, slight random tilt (±1.5°) for memory cards.

**Chips:** gift categories, pill, toggle with bounce.

**Toasts:** top, pill, mint for success, blush for errors, small cat icon.

**Music pill:** floating bottom-right 48 px circle with equaliser bars and volume popover.

**Progress dots:** 8 stage dots at top of the story, current dot is a tiny paw.

**Modal / lightbox:** dark blurred backdrop, rounded image, caption sheet, swipe to dismiss.

---

## 6. Cat Character System

Delivered as SVG/Lottie sprites, one consistent style: round body, big shiny eyes, blush cheeks, thick soft outline.

| Cat | Where | Animation |
|---|---|---|
| **Welcome kitten** | Welcome | Hops, holds gift, blinks, tail sway |
| **Sorry cat** | Apology | Walks in carrying a plate/sign, bows, wobbling lip |
| **Crying cat** | Apology | Fountain tears, shaking, tiny tissue |
| **Begging cat** | Apology | Hands together, puppy eyes zoom, bobbing |
| **Hiding cat** | Apology | Peeks from edge, hides behind the card |
| **Rolling cat** | Apology | Rolls across the screen, squash-stretch |
| **Angry cat** | Still angry | Red puff cheeks, steam, shake, stomps |
| **Forgiven / dancing cat** | Forgive, Reveal | Disco spin, hearts, party hat |
| **Love cat** | Wishes | Holds heart, hearts float up |
| **Sleepy cat** | Maintenance, countdown | Zzz bubbles |
| **Peek cat** | Easter egg | Pops out at screen edges |

Motion timing: enter 500 ms `cubic-bezier(.34,1.56,.64,1)` (bouncy), idle loops 2–4 s, reactions 800–1200 ms. Cats must never cover text or buttons.

---

## 7. Motion Guidelines
- Animate only `transform` and `opacity`.
- Page transitions: slide-up + fade 450 ms, with a heart/paw wipe for major stage changes.
- Ambient layer: 12–20 floating hearts, stars and balloons (reduced to 6 in lightweight mode).
- Confetti and fireworks on canvas, capped particle count.
- Typewriter: 35 ms per character, tap to skip.
- Candle flames flicker, extinguish with smoke wisp and a "pop" sound.
- **Reduced motion:** disable ambient loops, parallax, wipes; keep fades and static cat poses.

---

## 8. Screen Specifications — Visitor

1. **Countdown:** large glass card, four number tiles (flip animation), sleepy cat on top, "Almost time…" copy.
2. **Welcome:** gradient hero, kitten holding gift, floating balloons, headline, **Open Your Surprise 🎁** button, small "Skip animations" link.
3. **Password gate:** glass card, lock-paw icon, password field with show/hide, privacy line ("We log visits and IP addresses to keep this page safe."), error shake + cat face. Expired state: "Your time is up 🕰️" with **Request Access Again** and optional note field; pending state shows a waiting cat.
4. **Sorry:** progress counter "Apology 2 of 5", cat on the left/top, message on a plate card, two big buttons stacked on mobile.
5. **Birthday reveal:** dark-to-bright flash, fireworks, gold gradient **HAPPY BIRTHDAY**, name in script, cake with 3–5 candles and a "Tap to blow 🕯️" prompt, dancing cat on each side.
6. **Memories:** masonry grid (2 columns mobile), polaroid-style cards with tape, timeline mode toggle, lightbox.
7. **Letter:** envelope drops, flap opens, paper slides out with handwriting font, wax-seal heart on close, wishes as swipeable cards.
8. **Reply boxes:** two stacked glass cards: 🎁 Gift (textarea + chips + "Surprise me") and 💌 Complaint (textarea, warm placeholder). Send buttons with paw animation, confirmation cat.
9. **Finale:** love cat, final message in script font, reaction emoji row, **Replay** and **Share**, tiny privacy link.

---

## 9. Screen Specifications — Admin

Admin is cleaner and calmer than the visitor side but keeps the pastel brand.

- **Layout:** desktop left sidebar (240 px, grape-to-night gradient, paw logo), top bar with search, profile switcher and notifications. Mobile: bottom tab bar (Dashboard, Profiles, Inbox, Logs, More).
- **Dashboard:** stat cards (visitors, unique, completions, forgive vs angry), line chart, funnel, pending requests card with approve/reject buttons, latest gift wishes.
- **Profiles:** card grid with cover photo, status pill (Draft grey, Scheduled lavender, Published mint, Paused butter, Archived rose), quick actions (Edit, Preview, Duplicate, Copy link).
- **Profile editor:** tabbed (Basics, Access, Messages, Gallery, Audio, Theme, Effects) with sticky Save and Preview bar; drag handles for ordering.
- **Gallery manager:** drag-and-drop upload zone, thumbnail grid, inline caption edit, cover star, toggle, delete.
- **Access requests:** list with status chips; detail drawer showing note, session info, Approve (duration select) / Reject (reason) buttons; copy-code field after approval.
- **Responses inbox:** two tabs (Gifts, Complaints), unread dot, mark read / archive / delete.
- **Activity logs:** filter bar (date range, profile, event, IP search), table with sticky header (collapses to cards on mobile), visitor timeline drawer, Export CSV, retention setting banner.
- **Settings:** grouped cards, danger zone for purge and restore.
- **Tables:** 48 px rows, zebra blush tint, hover highlight, badges for events.

---

## 10. Responsive Behaviour

| Breakpoint | Behaviour |
|---|---|
| 320–479 | Single column, full-width buttons, bottom-safe padding |
| 480–767 | Story card centred, 2-col gallery |
| 768–1023 | 3-col gallery, admin sidebar collapsible |
| 1024+ | Story in phone-width stage with decorative cats outside, admin full layout |

Respect safe areas (`env(safe-area-inset-*)`), use `100dvh`, tap targets ≥ 44 px.

---

## 11. Accessibility
- Contrast AA (text on pastel uses `--ink`).
- Visible focus ring (grape, 3 px), full keyboard flow, lightbox focus trap and Esc.
- Alt text for photos, `aria-live` for toasts and typewriter finished text, labelled forms.
- Reduced motion, mute-by-default until tap, captions for any spoken audio.
- Never rely on colour alone for status.

---

## 12. Sound Design
Soft meow on button taps (off by default on silent devices), "pop" for candles, party horn on reveal, sparkle on photo open, gentle piano/ukulele background loop at 35% volume. Always provide mute and volume.

---

## 13. Copy Tone
Warm, playful, slightly dramatic. Examples: "Meow… are you still angry? 🥺", "I brought fish as an apology 🐟", "Your surprise is ready 🎁", "Thanks for telling me. I read every word 💌". Errors are gentle, never blaming.

---

## 14. Asset Checklist
- Cat SVG/Lottie set (11 above), cake, candles, balloons, hearts, stars, gift box, envelope, paper texture, wax seal, polaroid frame.
- Icons: rounded line icons (Lucide style) with paw accents.
- Favicon: pink paw with heart; OG share image per profile.
- Audio: background loop, meow, pop, horn, sparkle.
- Fonts self-hosted (Baloo 2/Fredoka, Nunito, Pacifico, Caveat, Inter) with `font-display: swap`.

---

## 15. CSS Token Starter

```css
:root{
  --blush:#FFD6E7; --rose:#FF6FA5; --rose-deep:#E5487F;
  --lavender:#CDB8FF; --grape:#6B4EE6; --mint:#BFF3E0;
  --butter:#FFE9A8; --gold:#F5C451; --cream:#FFF8F3;
  --ink:#3A2B4D; --ink-soft:#7A6A8C; --night:#1E1633;
  --r-card:20px; --r-pill:999px;
  --ease-bounce:cubic-bezier(.34,1.56,.64,1);
}
@media (prefers-reduced-motion:reduce){
  *{animation-duration:.01ms!important;transition-duration:.01ms!important}
}
```

> **Design north star:** a tiny movie starring a very dramatic cat — then a quiet, honest moment when the letter opens.
