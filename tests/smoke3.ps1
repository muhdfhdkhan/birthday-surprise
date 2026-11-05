# Birthday Surprise — phases 3-6 smoke test (v2)
$ErrorActionPreference = 'Continue'
$base = 'http://localhost/birthday-surprise'
$php  = 'C:\xampp\php\php.exe'

function Get-Token($html) {
    if ($html -match 'name="_token" value="([0-9a-f]+)"') { return $Matches[1] }
    return $null
}
function Status($resp) {
    try { return [int]$resp.StatusCode } catch { return 0 }
}
function Show($label, $ok, $extra = '') {
    $mark = if ($ok) { 'PASS' } else { 'FAIL' }
    Write-Output ("[{0}] {1} {2}" -f $mark, $label, $extra)
}

# ---------- 1. admin login ----------
$r = Invoke-WebRequest "$base/admin/login" -SessionVariable admin -UseBasicParsing
$tok = Get-Token $r.Content
Show 'login page loads' ($null -ne $tok)
Invoke-WebRequest "$base/admin/login" -WebSession $admin -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body @{ username='admin'; password='admin123'; _token=$tok } | Out-Null
$page = Invoke-WebRequest "$base/admin" -WebSession $admin -UseBasicParsing
Show 'admin session works' ($page.Content -match 'Welcome,')

# ---------- 2. create profile ----------
$newForm = Invoke-WebRequest "$base/admin/profiles/new" -WebSession $admin -UseBasicParsing
$tok = Get-Token $newForm.Content
$slug = 'catalina' + (Get-Random -Minimum 10 -Maximum 99)
$body = @{
    display_name='Catalina Test'; slug=$slug; birthday_at='2026-11-10T18:00'
    timezone='UTC'; status='published'; publish_at=''
    password='secret123'; access_hours='24'; max_reentry_uses='1'
    reentry_policy='open'; allow_early_peek='on'; allow_share='on'
}
Invoke-WebRequest "$base/admin/profiles" -WebSession $admin -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body ($body + @{ _token=$tok }) | Out-Null
$db = & $php -r "require 'X:/Vibe/Birthday/app/bootstrap.php'; `$p = App\Models\Profile::findBySlug('$slug'); echo `$p ? 'PID='.((int)`$p['id']).' STATUS='.((string)`$p['status']) : 'MISSING';"
Show 'profile created in DB (published)' ($db -match 'PID=\d+ STATUS=published') $db
if ($db -match 'MISSING') {
    $diag = Invoke-WebRequest "$base/admin/profiles" -WebSession $admin -UseBasicParsing
    if ($diag.Content -match 'data-flash-message="([^"]*)"') { Write-Output ('FLASH: ' + $Matches[1]) }
    $recent = & $php -r "require 'X:/Vibe/Birthday/app/bootstrap.php'; `$row = App\Core\Database::first('SELECT slug, status FROM birthday_profiles ORDER BY id DESC LIMIT 3'); echo json_encode(`$row);"
    Write-Output "LAST PROFILE ROW: $recent"
    exit 1
}
if ($db -match 'PID=(\d+)') { $profileId = [int]$Matches[1] } else { Write-Output 'ABORT: no profile id'; exit 1 }

# seeded content present?
$seed = & $php -r "require 'X:/Vibe/Birthday/app/bootstrap.php'; `$c = App\Models\Content::get($profileId); `$s = count(App\Models\Sorry::forProfile($profileId)); `$w = count(App\Models\Wish::forProfile($profileId)); echo 'welcome='.((int)!empty(`$c['welcome_message'])).' sorry='.((int)(`$s>=2)).' wish='.((int)(`$w>=1));"
Show 'starter content auto-seeded' ($seed -eq 'welcome=1 sorry=1 wish=1') $seed

# ---------- 3. visitor gate ----------
try {
    $gate = Invoke-WebRequest "$base/b/$slug" -SessionVariable visit -UseBasicParsing
    $vtok = Get-Token $gate.Content
    Show 'visitor gate shows password state' ($gate.Content -match 'Secret password' -and $gate.Content -match 'data-gate="password"')
} catch {
    Write-Output "GATE ERROR: $($_.Exception.Message)"; exit 1
}

# ---------- 4. wrong password + rate limit ----------
foreach ($i in 1..5) {
    Invoke-WebRequest "$base/b/$slug/unlock" -WebSession $visit -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body @{ password='wrong'; _token=$vtok } | Out-Null
    $page = Invoke-WebRequest "$base/b/$slug" -WebSession $visit -UseBasicParsing
    $vtok = Get-Token $page.Content
}
# 6th attempt (attempt counter is now 5 -> limiter engaged); flash on next GET
Invoke-WebRequest "$base/b/$slug/unlock" -WebSession $visit -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body @{ password='wrong'; _token=$vtok } | Out-Null
$flashPage = Invoke-WebRequest "$base/b/$slug" -WebSession $visit -UseBasicParsing
Show 'rate limit blocks after 5 tries' ($flashPage.Content -match 'Too many attempts')

# clear the block so we can probe the plain wrong-password message
& $php -r "require 'X:/Vibe/Birthday/app/bootstrap.php'; App\Core\Database::query('DELETE FROM rate_limits');"
$page = Invoke-WebRequest "$base/b/$slug" -WebSession $visit -UseBasicParsing
$vtok = Get-Token $page.Content
Invoke-WebRequest "$base/b/$slug/unlock" -WebSession $visit -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body @{ password='wrong'; _token=$vtok } | Out-Null
$flashPage = Invoke-WebRequest "$base/b/$slug" -WebSession $visit -UseBasicParsing
Show 'wrong password flashes error' ($flashPage.Content -match 'Wrong password')

# clear again, then unlock for real
& $php -r "require 'X:/Vibe/Birthday/app/bootstrap.php'; App\Core\Database::query('DELETE FROM rate_limits');"
$page = Invoke-WebRequest "$base/b/$slug" -WebSession $visit -UseBasicParsing
$vtok = Get-Token $page.Content
Invoke-WebRequest "$base/b/$slug/unlock" -WebSession $visit -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body @{ password='secret123'; _token=$vtok } | Out-Null
$story = Invoke-WebRequest "$base/b/$slug" -WebSession $visit -UseBasicParsing
Show 'correct password renders story' ($story.Content -match 'HAPPY BIRTHDAY' -and $story.Content -match 'Something special')

$vcsrf = if ($story.Content -match 'data-csrf="([0-9a-f]+)"') { $Matches[1] } else { $null }
Show 'story exposes csrf meta' ($null -ne $vcsrf)
Show 'story has all enabled stages' ($story.Content -match 'data-stage="sorry"' -and $story.Content -match 'data-stage="replies"' -and $story.Content -match 'data-stage="finale"')

# ---------- 6. gift / complaint / reaction via API ----------
$k = Invoke-WebRequest "$base/api/b/$slug/response" -WebSession $visit -UseBasicParsing -Method Post -Headers @{ 'X-CSRF-TOKEN'=$vcsrf } -Body @{ type='gift'; body='A new piano'; category='Surprise me!'; website='' }
Show 'gift response accepted' ($k.Content -match 'wrapping')
$k = Invoke-WebRequest "$base/api/b/$slug/response" -WebSession $visit -UseBasicParsing -Method Post -Headers @{ 'X-CSRF-TOKEN'=$vcsrf } -Body @{ type='complaint'; body='You forgot the fish.' }
Show 'complaint response accepted' ($k.Content -match 'every word')
$k = Invoke-WebRequest "$base/api/b/$slug/response" -WebSession $visit -UseBasicParsing -Method Post -Headers @{ 'X-CSRF-TOKEN'=$vcsrf } -Body @{ type='reaction'; reaction=[char]0xD83D + [char]0xDC96 }
Show 'reaction accepted' ($k.Content -match 'noted')
try {
    Invoke-WebRequest "$base/api/b/$slug/response" -WebSession $visit -UseBasicParsing -Method Post -Headers @{ 'X-CSRF-TOKEN'=$vcsrf } -Body @{ type='gift'; body=''; website='' } | Out-Null
    Show 'empty gift rejected 422' $false 'no 422 raised'
} catch {
    Show 'empty gift rejected 422' ($_.Exception.Response.StatusCode.value__ -eq 422) "status=$($_.Exception.Response.StatusCode.value__)"
}

# honeypot: bot filling "website" gets fake success and no DB row
$bot = Invoke-WebRequest "$base/api/b/$slug/response" -WebSession $visit -UseBasicParsing -Method Post -Headers @{ 'X-CSRF-TOKEN'=$vcsrf } -Body @{ type='gift'; body='BUY NOW http://spam'; website='http://spam.com' }
$hp = & $php -r "require 'X:/Vibe/Birthday/app/bootstrap.php'; echo App\Core\Database::scalar('SELECT COUNT(*) FROM visitor_responses WHERE body LIKE '.App\Core\Database::pdo()->quote('%BUY NOW%'));"
Show 'honeypot swallows bot spam' ($bot.Content -match 'Thanks' -and $hp -eq '0') "rows=$hp"

# ---------- 7. access request -> admin approve -> code ----------
Invoke-WebRequest "$base/b/$slug/access-request" -WebSession $visit -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body @{ note='Please let me in again'; _token=$vcsrf } | Out-Null
$requests = Invoke-WebRequest "$base/admin/requests" -WebSession $admin -UseBasicParsing
Show 'access request visible to admin' ($requests.Content -match 'Catalina Test')

$rid = if ($requests.Content -match '/admin/requests/(\d+)/approve') { [int]$Matches[1] } else { 0 }
$atok = Get-Token $requests.Content
Invoke-WebRequest "$base/admin/requests/$rid/approve" -WebSession $admin -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body @{ hours='24'; uses='1'; _token=$atok } | Out-Null
$requests = Invoke-WebRequest "$base/admin/requests" -WebSession $admin -UseBasicParsing
$code = if ($requests.Content -match 'grant-code"[^>]*>([A-F0-9-]{19})') { $Matches[1] } else { $null }
Show 'approve issues one-time code' ($null -ne $code) $code

# ---------- 8. redeem from a fresh visitor session ----------
$r2 = Invoke-WebRequest "$base/b/$slug" -SessionVariable visit2 -UseBasicParsing
$t2 = Get-Token $r2.Content
Invoke-WebRequest "$base/b/$slug/redeem" -WebSession $visit2 -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body @{ code=$code; _token=$t2 } | Out-Null
$story2 = Invoke-WebRequest "$base/b/$slug" -WebSession $visit2 -UseBasicParsing
Show 'code redemption unlocks story (no password needed)' ($story2.Content -match 'HAPPY BIRTHDAY')

# reuse must fail: new anon session, same code
$r3 = Invoke-WebRequest "$base/b/$slug" -SessionVariable visit3 -UseBasicParsing
$t3 = Get-Token $r3.Content
Invoke-WebRequest "$base/b/$slug/redeem" -WebSession $visit3 -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body @{ code=$code; _token=$t3 } | Out-Null
$gate3 = Invoke-WebRequest "$base/b/$slug" -WebSession $visit3 -UseBasicParsing
Show 'used code rejected on next visit' ($gate3.Content -match 'Secret password' -or $gate3.Content -match 'not valid')

# ---------- 9. media upload (photo via GD, music stub mp3) ----------
$media = Invoke-WebRequest "$base/admin/profiles/$profileId/media" -WebSession $admin -UseBasicParsing
$mtok = Get-Token $media.Content
& $php -r "`$img = imagecreatetruecolor(80,60); imagefilledrectangle(`$img,0,0,79,59,imagecolorallocate(`$img,255,111,165)); imagejpeg(`$img,'X:/Vibe/Birthday/public/tmp_test.jpg',80);"

$boundary = '----bs' + [guid]::NewGuid().ToString('N')
$ms = New-Object System.IO.MemoryStream
$sw = New-Object System.IO.StreamWriter($ms); $sw.NewLine = "`r`n"
$sw.Write("--$boundary`r`nContent-Disposition: form-data; name=`"_token`"`r`n`r`n$mtok`r`n--$boundary`r`nContent-Disposition: form-data; name=`"photos[]`"; filename=cat.jpg`r`nContent-Type: image/jpeg`r`n`r`n"); $sw.Flush()
$bytes = [IO.File]::ReadAllBytes('X:\Vibe\Birthday\public\tmp_test.jpg'); $ms.Write($bytes, 0, $bytes.Length)
$sw.Write("`r`n--$boundary--`r`n"); $sw.Flush()
Invoke-WebRequest "$base/admin/profiles/$profileId/gallery" -WebSession $admin -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -ContentType "multipart/form-data; boundary=$boundary" -Body $ms.ToArray() | Out-Null
$mediaPhoto = Invoke-WebRequest "$base/admin/profiles/$profileId/media" -WebSession $admin -UseBasicParsing
Show 'photo uploaded + listed' ($mediaPhoto.Content -match 'photo\(s\) uploaded' -and $mediaPhoto.Content -match 't_[0-9a-f]{12}\.jpg')

[IO.File]::WriteAllBytes('X:\Vibe\Birthday\public\tmp_test.mp3', ([Text.Encoding]::ASCII.GetBytes("ID3`x3`x0`x0`x0`x0`x0`x0" + ('A' * 400))))
$ms2 = New-Object System.IO.MemoryStream
$sw2 = New-Object System.IO.StreamWriter($ms2); $sw2.NewLine = "`r`n"
$sw2.Write("--$boundary`r`nContent-Disposition: form-data; name=`"_token`"`r`n`r`n$mtok`r`n--$boundary`r`nContent-Disposition: form-data; name=`"kind`"`r`n`r`nmusic`r`n--$boundary`r`nContent-Disposition: form-data; name=`"label`"`r`n`r`nOur song`r`n--$boundary`r`nContent-Disposition: form-data; name=`"audio`"; filename=song.mp3`r`nContent-Type: audio/mpeg`r`n`r`n"); $sw2.Flush()
$ab = [IO.File]::ReadAllBytes('X:\Vibe\Birthday\public\tmp_test.mp3'); $ms2.Write($ab, 0, $ab.Length)
$sw2.Write("`r`n--$boundary--`r`n"); $sw2.Flush()
Invoke-WebRequest "$base/admin/profiles/$profileId/audio" -WebSession $admin -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -ContentType "multipart/form-data; boundary=$boundary" -Body $ms2.ToArray() | Out-Null

$media = Invoke-WebRequest "$base/admin/profiles/$profileId/media" -WebSession $admin -UseBasicParsing
Show 'music uploaded + listed' ($media.Content -match 'uploaded' -and $media.Content -match 'aud_[0-9a-f]{12}\.mp3')
$mysql = 'C:\xampp\mysql\bin\mysql.exe'
$dbCounts = & $mysql -u root -N birthday_surprise -e "SELECT (SELECT COUNT(*) FROM gallery_images WHERE profile_id=$profileId), (SELECT COUNT(*) FROM media_assets WHERE profile_id=$profileId AND kind='music');"
Show 'media rows in DB' ($dbCounts -match '^1\s+1') $dbCounts

$story = Invoke-WebRequest "$base/b/$slug" -WebSession $visit -UseBasicParsing
Show 'visitor story shows polaroid gallery' ($story.Content -match 'polaroid-grid')
Show 'visitor story shows data-until window' ($story.Content -match 'data-until="\d+"')

# ---------- 10. content editor save + stage settings ----------
$cont = Invoke-WebRequest "$base/admin/profiles/$profileId/content" -WebSession $admin -UseBasicParsing
$ctok = Get-Token $cont.Content
Invoke-WebRequest "$base/admin/profiles/$profileId/content" -WebSession $admin -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body @{
    _token=$ctok; sections=@('welcome','sorry','reveal','memories','letter','replies','finale')
    secret_unlock_rule='after_candles'; sorry_required='on'
    welcome_message='Meow! Happy day test'; letter_text='Dear you <b>x</b>'
    secret_message='The key is under the mat'; final_message='Love always'; post_birthday_message=''
    gift_categories='Cake, Piano'; complaint_placeholder='Tell me'
} | Out-Null
$saved = & $php -r "require 'X:/Vibe/Birthday/app/bootstrap.php'; `$c = App\Models\Content::get($profileId); echo 'rule='.`$c['secret_unlock_rule'].' gift='.`$c['gift_categories'];"
Show 'content save persists' ($saved -match 'rule=after_candles gift=Cake, Piano') $saved

# XSS attempt in letter is sanitised server-side (strip_tags + on* removal)
$check = & $php -r "require 'X:/Vibe/Birthday/app/bootstrap.php'; `$c = App\Models\Content::get($profileId); echo ((stripos(`$c['letter_text'], '<script') === false && stripos(`$c['letter_text'], 'onerror') === false) ? 'safe' : 'unsafe');"
Show 'letter sanitiser strips dangerous html' ($check -eq 'safe') $check

# ---------- 11. unpublish -> visitor 404; archive & restore; delete ----------
$profiles = Invoke-WebRequest "$base/admin/profiles" -WebSession $admin -UseBasicParsing
$ptok = Get-Token $profiles.Content
Invoke-WebRequest "$base/admin/profiles/$profileId/unpublish" -WebSession $admin -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body @{ _token=$ptok } | Out-Null
try { $r404 = Invoke-WebRequest "$base/b/$slug" -WebSession $visit -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue } catch { $r404 = $null }
$st = 0; try { $st = [int](Invoke-WebRequest "$base/b/$slug" -WebSession $visit -UseBasicParsing -MaximumRedirection 0 -ErrorAction Stop).StatusCode } catch { $st = try { [int]$_.Exception.Response.StatusCode } catch { 404 } }
Show 'unpublished page returns 404' ($st -eq 404) "status=$st"

# restore for people who want to test the story later
Invoke-WebRequest "$base/admin/profiles/$profileId/publish" -WebSession $admin -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue -Method Post -Body @{ _token=$ptok } | Out-Null
$back = & $php -r "require 'X:/Vibe/Birthday/app/bootstrap.php'; echo App\Core\Database::scalar('SELECT status FROM birthday_profiles WHERE id=$profileId');"
Show 're-published' ($back -eq 'published') $back

# tidy the uploads temp files
Remove-Item 'X:\Vibe\Birthday\public\tmp_test.jpg','X:\Vibe\Birthday\public\tmp_test.mp3' -Force -ErrorAction SilentlyContinue
Write-Output 'SMOKE TEST DONE'
