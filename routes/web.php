<?php
/**
 * Web routes.
 * Row format: ['METHOD', '/path', [Controller::class, 'action'], [straps...]]
 * Straps: 'auth'  = signed-in admins only
 *         'guest' = redirect signed-in admins away (login pages)
 */

declare(strict_types=1);

use App\Controllers\Admin\ContentController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\AnalyticsController;
use App\Controllers\Admin\InboxController;
use App\Controllers\Admin\ProfilesController;
use App\Controllers\Admin\RequestsController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\Visitor\StoryController;

return [
    ['GET',  '/',                    [AuthController::class, 'home'],      []],
    ['GET',  '/admin/login',         [AuthController::class, 'showLogin'], ['guest']],
    ['POST', '/admin/login',         [AuthController::class, 'login'],     []],
    ['POST', '/admin/logout',        [AuthController::class, 'logout'],    []],
    ['GET',  '/admin',               [DashboardController::class, 'index'], ['auth']],

    // ---------- Phase 3: birthday profiles ----------
    ['GET',  '/admin/profiles',                          [ProfilesController::class, 'index'],    ['auth']],
    ['GET',  '/admin/profiles/new',                      [ProfilesController::class, 'create'],   ['auth']],
    ['POST', '/admin/profiles',                          [ProfilesController::class, 'store'],    ['auth']],
    ['GET',  '/admin/profiles/{id}/edit',                [ProfilesController::class, 'edit'],     ['auth']],
    ['POST', '/admin/profiles/{id}/update',              [ProfilesController::class, 'update'],   ['auth']],
    ['POST', '/admin/profiles/{id}/publish',             [ProfilesController::class, 'publish'],  ['auth']],
    ['POST', '/admin/profiles/{id}/unpublish',           [ProfilesController::class, 'unpublish'], ['auth']],
    ['POST', '/admin/profiles/{id}/archive',             [ProfilesController::class, 'archive'],  ['auth']],
    ['POST', '/admin/profiles/{id}/restore',             [ProfilesController::class, 'restore'],  ['auth']],
    ['POST', '/admin/profiles/{id}/delete',              [ProfilesController::class, 'destroy'],  ['auth']],

    // ---------- Phase 5: content management ----------
    ['GET',  '/admin/profiles/{id}/content',             [ContentController::class, 'show'],       ['auth']],
    ['POST', '/admin/profiles/{id}/content',             [ContentController::class, 'save'],       ['auth']],
    ['POST', '/admin/profiles/{id}/sorry',               [ContentController::class, 'sorryAdd'],   ['auth']],
    ['POST', '/admin/profiles/{id}/sorry/{mid}/update',  [ContentController::class, 'sorryUpdate'], ['auth']],
    ['POST', '/admin/profiles/{id}/sorry/{mid}/delete',  [ContentController::class, 'sorryDelete'], ['auth']],
    ['POST', '/admin/profiles/{id}/sorry/{mid}/move',    [ContentController::class, 'sorryMove'],  ['auth']],
    ['POST', '/admin/profiles/{id}/wishes',              [ContentController::class, 'wishAdd'],    ['auth']],
    ['POST', '/admin/profiles/{id}/wishes/{wid}/update', [ContentController::class, 'wishUpdate'], ['auth']],
    ['POST', '/admin/profiles/{id}/wishes/{wid}/delete', [ContentController::class, 'wishDelete'], ['auth']],
    ['POST', '/admin/profiles/{id}/wishes/{wid}/move',   [ContentController::class, 'wishMove'],   ['auth']],

    // ---------- Phase 6: media management ----------
    ['GET',  '/admin/profiles/{id}/media',               [MediaController::class, 'show'],         ['auth']],
    ['POST', '/admin/profiles/{id}/gallery',             [MediaController::class, 'upload'],       ['auth']],
    ['POST', '/admin/profiles/{id}/gallery/{gid}/update',[MediaController::class, 'imageUpdate'],  ['auth']],
    ['POST', '/admin/profiles/{id}/gallery/{gid}/delete',[MediaController::class, 'imageDelete'],  ['auth']],
    ['POST', '/admin/profiles/{id}/gallery/{gid}/move',  [MediaController::class, 'imageMove'],    ['auth']],
    ['POST', '/admin/profiles/{id}/gallery/{gid}/cover', [MediaController::class, 'imageCover'],   ['auth']],
    ['POST', '/admin/profiles/{id}/audio',               [MediaController::class, 'audioUpload'],  ['auth']],
    ['POST', '/admin/profiles/{id}/audio/{aid}/delete',  [MediaController::class, 'audioDelete'],  ['auth']],

    // ---------- Phase 9: analytics ----------
    ['GET',  '/admin/analytics',                        [AnalyticsController::class, 'index'],   ['auth']],

    // ---------- Phase 10: inbox (visitor letters) ----------
    ['GET',  '/admin/inbox',                            [InboxController::class, 'index'],   ['auth']],
    ['POST', '/admin/inbox/{id}/status',                [InboxController::class, 'status'],  ['auth']],
    ['POST', '/admin/inbox/{id}/delete',                [InboxController::class, 'destroy'], ['auth']],

    // ---------- Phase 10: settings ----------
    ['GET',  '/admin/settings',                         [SettingsController::class, 'index'], ['auth']],
    ['POST', '/admin/settings',                         [SettingsController::class, 'save'],  ['auth']],

    // ---------- Phase 4: access requests ----------
    ['GET',  '/admin/requests',                          [RequestsController::class, 'index'],   ['auth']],
    ['POST', '/admin/requests/{id}/approve',             [RequestsController::class, 'approve'], ['auth']],
    ['POST', '/admin/requests/{id}/reject',              [RequestsController::class, 'reject'],  ['auth']],
    ['POST', '/admin/grants/{id}/revoke',                [RequestsController::class, 'revoke'],  ['auth']],

    // ---------- Visitor (protected birthday pages) ----------
    ['GET',  '/b/{slug}',                [StoryController::class, 'page'],           []],
    ['POST', '/b/{slug}/unlock',         [StoryController::class, 'unlock'],         []],
    ['POST', '/b/{slug}/access-request', [StoryController::class, 'accessRequest'],  []],
    ['POST', '/b/{slug}/redeem',         [StoryController::class, 'redeem'],         []],
    ['POST', '/api/b/{slug}/response',   [StoryController::class, 'response'],       []],
    ['POST', '/api/b/{slug}/event',      [StoryController::class, 'trackEvent'],     []],
];
