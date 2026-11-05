<?php

declare(strict_types=1);

namespace App\Controllers\Visitor;

use App\Core\Csrf;
use App\Models\AccessGrant;
use App\Models\AccessRequest;
use App\Models\Content;
use App\Models\GalleryImage;
use App\Models\MediaAsset;
use App\Models\Profile;
use App\Models\Sorry;
use App\Models\VisitorResponse;
use App\Models\Visitor;
use App\Models\Wish;
use App\Services\ActivityLogger;
use App\Services\RateLimiter;
use Throwable;

final class StoryController
{
    /** GET /b/{slug} — gate or story. */
    public function page(string $slug): void
    {
        // Global maintenance: every birthday page politely closes.
        if (\App\Models\Settings::get('maintenance_mode', '0') === '1') {
            http_response_code(503);
            view('visitor/maintenance', ['title' => 'Be right back'], 'layouts/visitor');
            return;
        }

        $profile = Profile::findBySlug($slug);
        if ($profile === null) {
            $this->notFound();
            return;
        }
        $pid = (int) $profile['id'];
        Content::ensureRow($pid);

        // Lazy auto-publish: a scheduled profile reveals itself in place.
        if ($profile['status'] === 'scheduled'
            && !empty($profile['publish_at'])
            && strtotime((string) $profile['publish_at'] . ' UTC') <= time()) {
            Profile::setStatus($pid, 'published');
            $profile['status'] = 'published';
        }

        $nowLocal       = new \DateTime('now', new \DateTimeZone((string) $profile['timezone']));
        $birthdayLocal  = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            (string) $profile['birthday_at'],
            new \DateTimeZone((string) $profile['timezone'])
        );

        // Draft / paused / archived / not-yet-scheduled → invisible
        // (unless it is a pre-birthday blocker, which shows a countdown).
        $preBirthday = $birthdayLocal !== false && $nowLocal < $birthdayLocal
            && (int) $profile['block_before_birthday'] === 1;

        if ($profile['status'] !== 'published') {
            if ($preBirthday) {
                ActivityLogger::log($pid, 'page_view', '', 'countdown');
                $this->renderGate($profile, $slug, 'countdown');
            } else {
                $this->notFound();
            }
            return;
        }

        // Unlocked visitors see the story; everyone else gets the gate.
        if (self::unlockedState($slug) !== null) {
            ActivityLogger::log($pid, 'page_view', '', 'story');
            $this->renderStory($profile, $slug);
            return;
        }

        $expired = !empty($_SESSION['visitor'][$slug]['was_expired']);
        if ($expired) {
            unset($_SESSION['visitor'][$slug]['was_expired']);
        }

        ActivityLogger::log($pid, 'page_view', '', 'gate');
        $this->renderGate($profile, $slug, $expired ? 'expired' : 'password');
    }

    /** POST /b/{slug}/unlock — profile password. */
    public function unlock(string $slug): void
    {
        $profile = Profile::findBySlug($slug);
        if ($profile === null || $profile['status'] !== 'published') {
            redirect(url('/'));
        }
        $pid = (int) $profile['id'];

        if (!Csrf::check($_POST['_token'] ?? null)) {
            flash_visit($slug, 'error', 'Your session expired — please try again 🐾');
            redirect(url('/b/' . $slug));
        }

        $key = 'gate:' . client_ip() . ':' . $slug;
        $limit = RateLimiter::status($key, 5, 10);
        if ($limit['blocked']) {
            $mins = (int) ceil($limit['retry_in'] / 60);
            flash_visit($slug, 'error', "Too many attempts — the cat is napping 😴 Try again in {$mins} min.");
            redirect(url('/b/' . $slug));
        }

        $access   = Profile::accessSetting($pid);
        $password = (string) ($_POST['password'] ?? '');

        if ($access !== null && password_verify($password, (string) $access['password_hash'])) {
            RateLimiter::reset($key);
            self::grantUnlock($pid, $slug, (int) $access['access_hours'], 'password');
            ActivityLogger::log($pid, 'password_success', '', 'gate');
            redirect(url('/b/' . $slug));
        }

        RateLimiter::hit($key, 10);
        ActivityLogger::log($pid, 'password_fail', '', 'gate');
        flash_visit($slug, 'error', 'Meow? Wrong password 🐱 Try again.');
        redirect(url('/b/' . $slug));
    }

    /** POST /b/{slug}/access-request — after expiry. */
    public function accessRequest(string $slug): void
    {
        $profile = Profile::findBySlug($slug);
        if ($profile === null) {
            redirect(url('/'));
        }
        $pid = (int) $profile['id'];

        if (!Csrf::check($_POST['_token'] ?? null)) {
            flash_visit($slug, 'error', 'Session expired — please try again 🐾');
            redirect(url('/b/' . $slug));
        }

        $key = 'access_request:' . client_ip() . ':' . $slug;
        $limit = RateLimiter::status($key, 2, 10);
        if ($limit['blocked']) {
            flash_visit($slug, 'error', 'Your note is already on its way 🐾');
            redirect(url('/b/' . $slug));
        }
        RateLimiter::hit($key, 10);

        AccessRequest::create($pid, Visitor::uid(), trim((string) ($_POST['note'] ?? '')), client_ip());
        ActivityLogger::log($pid, 'access_request', '', 'gate');

        flash_visit($slug, 'success', 'Request sent! The birthday wizard will decide soon 🎩🐾');
        redirect(url('/b/' . $slug));
    }

    /** POST /b/{slug}/redeem — one-time re-entry code. */
    public function redeem(string $slug): void
    {
        $profile = Profile::findBySlug($slug);
        if ($profile === null) {
            redirect(url('/'));
        }
        $pid = (int) $profile['id'];

        if (!Csrf::check($_POST['_token'] ?? null)) {
            flash_visit($slug, 'error', 'Session expired — please try again 🐾');
            redirect(url('/b/' . $slug));
        }

        $key = 'redeem:' . client_ip() . ':' . $slug;
        if (RateLimiter::status($key, 8, 10)['blocked']) {
            flash_visit($slug, 'error', 'Too many tries — take a little cat nap first 😴');
            redirect(url('/b/' . $slug));
        }

        $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
        if ($code === '') {
            RateLimiter::hit($key, 10);
            flash_visit($slug, 'error', 'Paste the code your human gave you 🎫');
            redirect(url('/b/' . $slug));
        }
        RateLimiter::hit($key, 10);

        $grant = AccessGrant::redeem($pid, $code);
        if ($grant === []) {
            ActivityLogger::log($pid, 'password_fail', '', 'gate_redeem');
            flash_visit($slug, 'error', 'That code is not valid (or already used) 🐱');
            redirect(url('/b/' . $slug));
        }

        $endsUtc  = strtotime((string) $grant['expires_at'] . ' UTC');
        $fallback = time() + max(1, (int) $grant['access_hours']) * 3600;
        $_SESSION['visitor'][$slug] = [
            'profile_id' => $pid,
            'expires_at' => min($endsUtc !== false ? $endsUtc : $fallback, $fallback),
            'method'     => 'grant',
        ];

        ActivityLogger::log($pid, 'grant_redeem', '', 'gate');
        redirect(url('/b/' . $slug));
    }

    /** POST /api/b/{slug}/response — gift / complaint / reaction. */
    public function response(string $slug): void
    {
        $profile = Profile::findBySlug($slug);
        if ($profile === null || $profile['status'] !== 'published') {
            json_out(['message' => 'Unknown page.'], 404);
        }
        $pid = (int) $profile['id'];

        if (!Csrf::check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_token'] ?? null))) {
            // 403 (not 419): some PHP/Apache builds 500 on non-standard codes.
            json_out(['message' => 'Session expired — refresh the page 🐾'], 403);
        }

        if (self::unlockedState($slug) === null) {
            json_out(['message' => 'Please unlock the page first 🐱'], 401);
        }

        // Honeypot: bots filling invisible fields get a fake success.
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            json_out(['ok' => true, 'message' => 'Thanks! 🐾']);
        }

        try {
            $type = (string) ($_POST['type'] ?? '');
            $body = trim(mb_substr((string) ($_POST['body'] ?? ''), 0, 1000));
            $cat  = trim(mb_substr((string) ($_POST['category'] ?? ''), 0, 80));

            if ($type === 'gift') {
                if ($cat === '' && $body === '') {
                    json_out(['message' => 'Pick a gift first — tap a little box 🎁'], 422);
                }
                VisitorResponse::add($pid, 'gift', $body, $cat !== '' ? $cat : null, null, Visitor::uid());
                ActivityLogger::log($pid, 'gift_submit', '', 'replies');
                json_out(['ok' => true, 'message' => 'Wish received! The cat is wrapping it already 🎁🐾']);
            }

            if ($type === 'complaint') {
                if ($body === '') {
                    json_out(['message' => 'Write a few words first 💌'], 422);
                }
                VisitorResponse::add($pid, 'complaint', $body, null, null, Visitor::uid());
                ActivityLogger::log($pid, 'complaint_submit', '', 'replies');
                json_out(['ok' => true, 'message' => 'Thanks for telling me. I read every word 💌']);
            }

            if ($type === 'reaction') {
                $reaction = trim((string) ($_POST['reaction'] ?? ''));
                if ($reaction === '') {
                    json_out(['message' => 'Pick an emoji 🥰'], 422);
                }
                VisitorResponse::add($pid, 'reaction', '', null, mb_substr($reaction, 0, 16), Visitor::uid());
                ActivityLogger::log($pid, 'reaction_click', '', 'finale');
                json_out(['ok' => true, 'message' => 'Reaction noted 💖']);
            }

            json_out(['message' => 'Unknown reaction type 🐱'], 422);
        } catch (Throwable $e) {
            error_log('[response] ' . $e->getMessage());
            json_out(['message' => 'Something went wrong — try again 🐾'], 500);
        }
    }

    /** POST /api/b/{slug}/event — client-side interaction beacons (Phase 9). */
    public function trackEvent(string $slug): void
    {
        $profile = Profile::findBySlug($slug);
        if ($profile === null || $profile['status'] !== 'published') {
            json_out(['message' => 'Unknown page.'], 404);
        }
        $pid = (int) $profile['id'];

        if (!Csrf::check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_token'] ?? null))) {
            json_out(['message' => 'Session expired'], 403);
        }

        if (self::unlockedState($slug) === null) {
            json_out(['message' => 'Locked'], 401);
        }

        // Flood guard: 120 beacons / 10 min per session is plenty for a visit.
        $key = 'event:' . (session_id() !== '' ? session_id() : client_ip()) . ':' . $slug;
        if (RateLimiter::status($key, 120, 10)['blocked']) {
            json_out(['ok' => true, 'message' => 'Ignored (flood guard)']);
        }
        RateLimiter::hit($key, 10);

        // Only recognised interaction events are stored.
        $allowed = [
            'stage_view', 'forgive_click', 'angry_click', 'candle_blown',
            'photo_open', 'music_play', 'music_pause', 'letter_skip',
            'secret_reveal', 'replay_click', 'share_click',
        ];
        $event = (string) ($_POST['event'] ?? '');
        if (!in_array($event, $allowed, true)) {
            json_out(['message' => 'Unknown event 🐱'], 422);
        }

        $stage = '';
        if (in_array($event, ['forgive_click', 'angry_click'], true)) {
            $stage = 'sorry';
        } elseif (in_array($event, ['candle_blown', 'secret_reveal'], true)) {
            $stage = 'reveal';
        } elseif ($event === 'photo_open') {
            $stage = 'memories';
        } elseif ($event === 'letter_skip') {
            $stage = 'letter';
        } elseif (in_array($event, ['music_play', 'music_pause'], true)) {
            $stage = 'story-music';
        } elseif (in_array($event, ['replay_click', 'share_click'], true)) {
            $stage = 'finale';
        }

        try {
            ActivityLogger::log($pid, $event, '', $stage);
            json_out(['ok' => true]);
        } catch (Throwable $e) {
            error_log('[trackEvent] ' . $e->getMessage());
            json_out(['message' => 'Noted (offline)'], 500);
        }
    }

    // =========================================================
    //  internals
    // =========================================================

    private function notFound(): void
    {
        http_response_code(404);
        view('errors/404', ['title' => 'Not found']);
    }

    private function renderGate(array $profile, string $slug, string $state): void
    {
        view('visitor/gate', [
            'title'      => 'A surprise for ' . (string) $profile['display_name'] . ' 🎁',
            'profile'    => $profile,
            'slug'       => $slug,
            'state'      => $state,
            'visitFlash' => pull_visit($slug),
            'targetIso'  => $this->targetIso((string) $profile['timezone'], (string) $profile['birthday_at']),
            'privacy'    => (string) (\App\Models\Settings::get('visitor_privacy_note', 'We log visits and IP addresses to keep this page safe.') ?? ''),
        ], 'layouts/visitor');
    }

    private function renderStory(array $profile, string $slug): void
    {
        $pid      = (int) $profile['id'];
        $content  = Content::get($pid) ?? [];
        $sections = array_flip(explode(',', (string) ($content['sections_enabled'] ?? '')));

        view('visitor/story', [
            'title'         => (string) $profile['display_name'],
            'profile'       => $profile,
            'slug'          => $slug,
            'content'       => $content,
            'sections'      => $sections,
            'sorry'         => Sorry::forProfile($pid),
            'wishes'        => Wish::forProfile($pid),
            'images'        => GalleryImage::forProfile($pid, true),
            'music'         => MediaAsset::music($pid),
            'visitFlash'    => pull_visit($slug),
            'unlockedUntil' => (int) ($_SESSION['visitor'][$slug]['expires_at'] ?? time()),
            'targetIso'     => $this->targetIso((string) $profile['timezone'], (string) $profile['birthday_at']),
        ], 'layouts/visitor');
    }

    /** Visitor access lives in $_SESSION['visitor'][slug] (server-side). */
    public static function unlockedState(string $slug): ?array
    {
        $state = $_SESSION['visitor'][$slug] ?? null;
        if (!is_array($state)) {
            return null;
        }
        if ((int) ($state['expires_at'] ?? 0) > time()) {
            return ['expires_at' => (int) $state['expires_at'], 'method' => (string) ($state['method'] ?? 'password')];
        }
        // Expired: keep a one-shot flag so the gate can show "time is up".
        $_SESSION['visitor'][$slug] = ['was_expired' => true];
        return null;
    }

    public static function grantUnlock(int $pid, string $slug, int $hours, string $method): void
    {
        $_SESSION['visitor'][$slug] = [
            'profile_id' => $pid,
            'expires_at' => time() + max(1, $hours) * 3600,
            'method'     => $method,
        ];
    }

    /** ISO-8601 target with the profile's timezone offset, for the JS countdown. */
    private function targetIso(string $tz, string $birthdayAt): ?string
    {
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $birthdayAt, new \DateTimeZone($tz !== '' ? $tz : 'UTC'));
        return $dt === false ? null : $dt->format('Y-m-d\TH:i:sP');
    }
}
