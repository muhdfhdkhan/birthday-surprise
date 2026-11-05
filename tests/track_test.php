<?php
/* Quick API-guard test: locked vs unlocked / bad vs good CSRF / unknown event. */
$jar = sys_get_temp_dir() . '/bs_track_cookies.txt';
@unlink($jar);

function call(string $url, string $post = '', string $jar = ''): array {
    $ch = curl_init();
    $opt = [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => false,
    ];
    if ($jar !== '') {
        $opt[CURLOPT_COOKIEJAR] = $jar;
        $opt[CURLOPT_COOKIEFILE] = $jar;
    }
    if ($post !== '') {
        $opt[CURLOPT_POST] = true;
        $opt[CURLOPT_POSTFIELDS] = $post;
    }
    curl_setopt_array($ch, $opt);
    $res  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $body = substr((string) $res, (int) (curl_getinfo($ch, CURLINFO_HEADER_SIZE)));
    curl_close($ch);
    return [$code, $body];
}

$base = 'http://localhost/birthday-surprise/b/catalina-x';
// Never unlocked in this fresh jar → expect the gate, not the story.
[$gateCode] = call($base, '', $jar);
$gateBody = call($base, '', $jar)[1];
preg_match('/data-csrf="([^"]+)"/', $gateBody, $m);
$tok = $m[1] ?? '(none)';
$gate  = $gateCode === 200 && strpos($gateBody, 'gate') !== false ? 'gate html' : substr($gateBody, 0, 80);

$api = 'http://localhost/birthday-surprise/api/b/catalina-x/event';
[$c1, $b1] = call($api, http_build_query(['event' => 'music_play', '_token' => $tok]), $jar);
[$c2, $b2] = call($api, http_build_query(['event' => 'hack', '_token' => $tok]), $jar);
[$c3, $b3] = call($api, http_build_query(['event' => 'music_play', '_token' => 'badtoken']), $jar);

echo "gate=$gateCode\n";
echo "unlocked-valid=$c1 " . substr($b1, 0, 60) . "\n";
printf("unknown-event=%d %s\n", $c2, substr($b2, 0, 60));
printf("bad-csrf=%d %s\n", $c3, substr($b3, 0, 60));
