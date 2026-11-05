<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Visitor;

/**
 * Visitor activity logging into activity_logs.
 * Events: page_view, password_success, password_fail, stage_complete,
 * forgive_click, still_angry_click, candle_blown, photo_open,
 * gift_submit, complaint_submit, access_request, grant_redeem, replay.
 */
final class ActivityLogger
{
    public static function log(int $profileId, string $event, string $page = '', string $stage = ''): void
    {
        try {
            $uid = Visitor::uid();
            $ua  = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

            Database::query(
                'INSERT INTO activity_logs
                   (profile_id, visitor_id, visitor_uid, session_id, ip_address, event, page, stage,
                    browser, device, os, referrer)
                 VALUES (:pid, :vid, :uid, :sid, :ip, :event, :page, :stage, :browser, :device, :os, :ref)',
                [
                    'pid'     => $profileId,
                    'vid'     => Visitor::upsert($uid),
                    'uid'     => $uid,
                    'sid'     => session_id(),
                    'ip'      => client_ip(),
                    'event'   => mb_substr($event, 0, 60),
                    'page'    => mb_substr((string) ($_SERVER['REQUEST_URI'] ?? $page), 0, 150),
                    'stage'   => mb_substr($stage, 0, 40) ?: null,
                    'browser' => Visitor::browser($ua),
                    'device'  => Visitor::device($ua),
                    'os'      => Visitor::os($ua),
                    'ref'     => mb_substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 512) ?: null,
                ]
            );
        } catch (Throwable $e) {
            error_log('[activity] ' . $e->getMessage()); // never break the journey for logging
        }
    }

    /** Events logged from the visitor's story stage. */
    public static function stage(int $profileId, string $stage, string $event = 'stage_complete'): void
    {
        self::log($profileId, $event, '', $stage);
    }
}
