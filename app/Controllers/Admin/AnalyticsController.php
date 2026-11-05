<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Profile;
use Throwable;

/**
 * Phase 9 — visitor analytics & activity logs.
 * Reads activity_logs (events) + access_requests; every query is scope-able
 * to one profile via ?profile={id} so per-birthday reports are one click away.
 */
final class AnalyticsController
{
    private const DAYS = 14;

    public function index(): void
    {
        // Optional profile filter (validated) + optional day window.
        $pid = max(0, (int) ($_GET['profile'] ?? 0));
        $days = max(1, min(90, (int) ($_GET['days'] ?? self::DAYS)));

        $profiles = Profile::all(); // all non-deleted profiles
        $chosen = null;
        if ($pid > 0) {
            foreach ($profiles as $p) {
                if ((int) $p['id'] === $pid) {
                    $chosen = $p;
                    break;
                }
            }
            if ($chosen === null) {
                $pid = 0;
            }
        }

        try {
            $where = $pid > 0 ? 'WHERE profile_id = :pid' : '';
            $bind = ($pid > 0 ? ['pid' => $pid] : []) + ['days' => $days];

            // ---------- headline numbers (current window) ----------
            $totals = [
                'page_views'   => (int) Database::scalar($this->sql($where, "AND event = 'page_view'", 'COUNT(*)'), $bind),
                'sessions'     => (int) Database::scalar($this->sql($where, 'AND session_id IS NOT NULL', 'COUNT(DISTINCT session_id)'), $bind),
                'visitors'     => (int) Database::scalar($this->sql($where, 'AND visitor_uid IS NOT NULL', 'COUNT(DISTINCT visitor_uid)'), $bind),
                'pw_success'   => (int) Database::scalar($this->sql($where, "AND event = 'password_success'", 'COUNT(*)'), $bind),
                'pw_fail'      => (int) Database::scalar($this->sql($where, "AND event = 'password_fail'", 'COUNT(*)'), $bind),
                'gifts'        => (int) Database::scalar($this->sql($where, "AND event = 'gift_submit'", 'COUNT(*)'), $bind),
                'complaints'   => (int) Database::scalar($this->sql($where, "AND event = 'complaint_submit'", 'COUNT(*)'), $bind),
                'reactions'    => (int) Database::scalar($this->sql($where, "AND event = 'reaction_click'", 'COUNT(*)'), $bind),
                'candles'      => (int) Database::scalar($this->sql($where, "AND event = 'candle_blown'", 'COUNT(*)'), $bind),
                'forgives'     => (int) Database::scalar($this->sql($where, "AND event = 'forgive_click'", 'COUNT(*)'), $bind),
                'still_angry'  => (int) Database::scalar($this->sql($where, "AND event = 'angry_click'", 'COUNT(*)'), $bind),
                'photos'       => (int) Database::scalar($this->sql($where, "AND event = 'photo_open'", 'COUNT(*)'), $bind),
                'music'        => (int) Database::scalar($this->sql($where, "AND event = 'music_play'", 'COUNT(*)'), $bind),
                'replays'      => (int) Database::scalar($this->sql($where, "AND event = 'replay_click'", 'COUNT(*)'), $bind),
            ];
            $totals['unlock_rate'] = ($totals['pw_success'] + $totals['pw_fail']) > 0
                ? (int) round(100 * $totals['pw_success'] / ($totals['pw_success'] + $totals['pw_fail']))
                : null;

            // ---------- event breakdown (all-time table) ----------
            $bindAllTime = $pid > 0 ? ['pid' => $pid] : [];

            $events = Database::all(
                $this->sql($where, '', 'event AS event, COUNT(*) AS c, MAX(created_at) AS last_seen',
                    ' ORDER BY c DESC', true, ' GROUP BY event')
                , $bindAllTime
            );

            // ---------- daily bars (chart) ----------
            $daily = $this->daily($pid, $days);

            // ---------- top visitors by IP ----------
            $topIps = Database::all(
                $this->sql($where, '', 'ip_address AS ip, COUNT(*) AS c, COUNT(DISTINCT session_id) AS sessions,
                                        MAX(browser) AS browser, MAX(os) AS os, MAX(device) AS device, MAX(created_at) AS last_seen',
                    ' ORDER BY c DESC LIMIT 10', true, ' GROUP BY ip_address'),
                $bindAllTime
            );

            // ---------- browsers / devices / OS (all-time) ----------
            $browsers = Database::all($this->sql($where, "AND event = 'page_view'",
                'COALESCE(browser, "Unknown") AS label, COUNT(*) AS c', ' ORDER BY c DESC LIMIT 6', true, ' GROUP BY browser'), $bindAllTime);
            $devices = Database::all($this->sql($where, "AND event = 'page_view'",
                'COALESCE(device, "Unknown") AS label, COUNT(*) AS c', ' ORDER BY c DESC LIMIT 6', true, ' GROUP BY device'), $bindAllTime);

            // ---------- recent activity ----------
            $recent = $this->recent($pid, 50);

            // ---------- access requests ----------
            $reqSql = 'SELECT r.*, p.display_name AS profile_name
                       FROM access_requests r
                       LEFT JOIN birthday_profiles p ON p.id = r.profile_id ' .
                ($pid > 0 ? 'WHERE r.profile_id = :pid ' : '') .
                'ORDER BY r.created_at DESC LIMIT 8';
            $reqRows = Database::all($reqSql, $bindAllTime);
        } catch (Throwable $e) {
            error_log('[analytics] ' . $e->getMessage());
            flash('error', 'Analytics database hiccup — showing empty stats 🐱');
            redirect(url('/admin'));
        }

        view('admin/analytics', [
            'title'      => 'Analytics · Birthday Surprise',
            'active'     => 'analytics',
            'chosen'     => $chosen,
            'pid'        => $pid,
            'days'       => $days,
            'profiles'   => $profiles,
            'totals'     => $totals,
            'events'     => $events,
            'daily'      => $daily,
            'topIps'     => $topIps,
            'browsers'   => $browsers,
            'devices'    => $devices,
            'recent'     => $recent,
            'reqRows'    => $reqRows,
            'flash'      => pull_flash(),
        ], 'layouts/admin');
    }

    /** WHERE(created_at >= window) OR created_at for the all-time variant. */
    private function sql(string $where, string $extra = '', string $select = 'COUNT(*)', string $tail = '', bool $allTime = false, string $group = ''): string
    {
        $window = $allTime ? ('1=1') : 'created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)';
        return 'SELECT ' . $select . ' FROM activity_logs WHERE ' . $window . ' ' . $extra . ' ' .
            ($where !== '' ? (str_replace('WHERE ', 'AND ', $where)) : '') . $group . $tail;
    }

    /** Daily event counts for the bar chart (fills gaps with 0). */
    private function daily(int $pid, int $days): array
    {
        $bind = $pid > 0 ? ['pid' => $pid, 'days' => $days] : ['days' => $days];
        $rows = Database::all(
            'SELECT DATE(created_at) AS d, COUNT(*) AS c FROM activity_logs
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY) ' .
            ($pid > 0 ? 'AND profile_id = :pid ' : '') .
            'GROUP BY DATE(created_at)',
            $bind
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(string) $r['d']] = (int) $r['c'];
        }
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day"));
            $out[] = [
                'date'  => $d,
                'label' => date('m-d', strtotime($d)),
                'c'     => $map[$d] ?? 0,
            ];
        }
        return $out;
    }

    /** Latest events with profile names for the activity feed table. */
    private function recent(int $pid, int $limit): array
    {
        $sql = 'SELECT l.*, p.display_name AS profile_name
                FROM activity_logs l
                LEFT JOIN birthday_profiles p ON p.id = l.profile_id ' .
            ($pid > 0 ? 'WHERE l.profile_id = :pid ' : '') .
            'ORDER BY l.id DESC LIMIT ' . (int) $limit;
        return Database::all($sql, $pid > 0 ? ['pid' => $pid] : []);
    }
}
