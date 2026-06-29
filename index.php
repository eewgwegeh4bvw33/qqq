<?php
date_default_timezone_set('Asia/Tehran');
session_start();

$storeFile = __DIR__ . '/ghost_panel_store.json';
$receiptsFile = __DIR__ . '/ghost_panel_receipts.json';
$receiptsDir = __DIR__ . '/ghost_receipts';

$timer_defs = [
    ['id' => 1, 'name' => 'تایمر 1', 'accent' => '34, 211, 238', 'default_balance' => 450000, 'default_rate' => 15000],
    ['id' => 2, 'name' => 'تایمر 2', 'accent' => '168, 85, 247', 'default_balance' => 300000, 'default_rate' => 12000],
    ['id' => 3, 'name' => 'تایمر 3', 'accent' => '52, 211, 153', 'default_balance' => 600000, 'default_rate' => 18000],
];

$ghostSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><path d="M32 8c-10.5 0-19 8.5-19 19v21c0 2.4 2.8 3.7 4.6 2.1l4.4-3.8 4.4 3.8c1.2 1 2.9 1 4.1 0l4-3.4 4 3.4c1.2 1 2.9 1 4.1 0l4.4-3.8 4.4 3.8c1.8 1.6 4.6.3 4.6-2.1V27C51 16.5 42.5 8 32 8Z" stroke="currentColor" stroke-width="3.2" fill="rgba(255,255,255,0.08)"/><circle cx="25" cy="28" r="3.2" fill="currentColor"/><circle cx="39" cy="28" r="3.2" fill="currentColor"/><path d="M24 39c2.3 1.8 5 2.7 8 2.7s5.7-.9 8-2.7" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/></svg>';
$favicon = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($ghostSvg);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function render_ghost_icon($class = 'h-10 w-10') {
    echo '<svg class="' . h($class) . '" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M32 8c-10.5 0-19 8.5-19 19v21c0 2.4 2.8 3.7 4.6 2.1l4.4-3.8 4.4 3.8c1.2 1 2.9 1 4.1 0l4-3.4 4 3.4c1.2 1 2.9 1 4.1 0l4.4-3.8 4.4 3.8c1.8 1.6 4.6.3 4.6-2.1V27C51 16.5 42.5 8 32 8Z" stroke="currentColor" stroke-width="3.2" fill="rgba(255,255,255,0.08)"/><circle cx="25" cy="28" r="3.2" fill="currentColor"/><circle cx="39" cy="28" r="3.2" fill="currentColor"/><path d="M24 39c2.3 1.8 5 2.7 8 2.7s5.7-.9 8-2.7" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/></svg>';
}

function now_ts() {
    return microtime(true);
}

function today_key() {
    return date('Y-m-d');
}

function default_store() {
    global $timer_defs;
    $timers = [];
    foreach ($timer_defs as $def) {
        $timers[(string)$def['id']] = [
            'id' => (int)$def['id'],
            'name' => $def['name'],
            'accent' => $def['accent'],
            'mode' => 'wallet',
            'source_type' => 'none',
            'source_subscription_id' => '',
            'balance' => (float)$def['default_balance'],
            'rate' => (float)$def['default_rate'],
            'running' => false,
            'last_tick' => null,
            'elapsed_seconds' => 0,
            'updated_at' => time(),
        ];
    }

    return [
        'settings' => [
            'username' => 'admin',
            'password_hash' => password_hash('admin', PASSWORD_DEFAULT),
            'remember_token_hash' => null,
            'remember_token_expires' => null,
            'referral_reward' => 15000,
            'referral_step' => 100000,
            'gift_reward' => 75000,
            'gift_step' => 750000,
            'ref_week_reward' => 50000,
            'ref_week_step' => 1000000,
            'ref_month_reward' => 300000,
            'ref_month_step' => 5000000,
            'card_number' => '5859471028871667',
            'card_name' => 'مهدی شیرازی',
            'card_description' => 'لطفاً مبلغ را به این شماره کارت واریز کرده و عکس رسید را ارسال کنید. اشتراک شما نهایت تا ۱۰ دقیقه شارژ خواهد شد.',
        ],
        'income' => [
            'day' => today_key(),
            'today' => 0,
            'transactions' => [],
        ],
        'timers' => $timers,
        'subscriptions' => [],
    ];
}

function save_store($storeFile, $store) {
    $tmp = $storeFile . '.tmp';
    $json = json_encode($store, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
        @rename($tmp, $storeFile);
        return true;
    }
    return false;
}

function load_receipts($file) {
    if (!file_exists($file)) {
        return [];
    }
    $raw = @file_get_contents($file);
    $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
    if (!is_array($data)) {
        return [];
    }
    return $data;
}

function save_receipts($file, $data) {
    $tmp = $file . '.tmp';
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
        @rename($tmp, $file);
        return true;
    }
    return false;
}

function receipt_public($r) {
    return [
        'id' => (string)($r['id'] ?? ''),
        'subscription_id' => (string)($r['subscription_id'] ?? ''),
        'amount' => (float)($r['amount'] ?? 0),
        'status' => (string)($r['status'] ?? 'pending'),
        'created_at' => (int)($r['created_at'] ?? 0),
        'ext' => (string)($r['ext'] ?? 'jpg'),
    ];
}

function generate_invite_code($existingCodes = []) {
    do {
        $code = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    } while (isset($existingCodes[$code]));
    return $code;
}

function parse_amount($v) {
    if (is_numeric($v)) {
        return max(0, (float)$v);
    }
    $v = (string)$v;
    $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    $v = str_replace($fa, $en, $v);
    $v = str_replace($ar, $en, $v);
    $v = preg_replace('/[^0-9.\-]/', '', $v);
    return is_numeric($v) ? max(0, (float)$v) : 0.0;
}

function normalize_birth_date($v) {
    $v = trim((string)$v);
    if ($v !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        return $v;
    }
    return '';
}

/* ===== تایید دو مرحله‌ای (TOTP / Google Authenticator) ===== */
function totp_base32_encode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    for ($i = 0; $i < strlen($data); $i++) {
        $bits .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
    }
    $out = '';
    for ($i = 0; $i < strlen($bits); $i += 5) {
        $chunk = substr($bits, $i, 5);
        if (strlen($chunk) === 5) {
            $out .= $alphabet[bindec($chunk)];
        }
    }
    return $out;
}
function totp_base32_decode($b32) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', (string)$b32));
    $bits = '';
    for ($i = 0; $i < strlen($b32); $i++) {
        $pos = strpos($alphabet, $b32[$i]);
        if ($pos === false) {
            continue;
        }
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    for ($i = 0; $i < strlen($bits); $i += 8) {
        $chunk = substr($bits, $i, 8);
        if (strlen($chunk) === 8) {
            $out .= chr(bindec($chunk));
        }
    }
    return $out;
}
function totp_generate_secret($length = 20) {
    return totp_base32_encode(random_bytes($length));
}
function totp_code($secret, $time = null) {
    $time = $time === null ? time() : (int)$time;
    $binary = totp_base32_decode($secret);
    if ($binary === '') {
        return '';
    }
    $counter = (int)floor($time / 30);
    $timeBytes = pack('N', 0) . pack('N', $counter);
    $hash = hash_hmac('sha1', $timeBytes, $binary, true);
    $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
    $code = (
        ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        (ord($hash[$offset + 3]) & 0xFF)
    ) % 1000000;
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}
function totp_verify($secret, $code, $time = null, $window = 1) {
    $code = preg_replace('/[^0-9]/', '', (string)$code);
    if (strlen($code) !== 6) {
        return false;
    }
    $time = $time === null ? time() : (int)$time;
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(totp_code($secret, $time + $i * 30), $code)) {
            return true;
        }
    }
    return false;
}
function totp_otpauth_uri($secret, $label) {
    $issuer = 'Neon Timer Lab';
    $q = http_build_query([
        'secret' => $secret,
        'issuer' => $issuer,
        'algorithm' => 'SHA1',
        'digits' => 6,
        'period' => 30,
    ]);
    return 'otpauth://totp/' . rawurlencode($issuer . ':' . $label) . '?' . $q;
}

/* ===== تقویم جلالی (شمسی) — پیاده‌سازی مستقل و دقیق (port از jalaali-js) ===== */
function jal_cal($jy) {
    $breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
    $bl = count($breaks);
    $gy = (int)$jy + 621;
    $leapJ = -14;
    $jp = $breaks[0];
    $jm = 0;
    $jump = 0;
    for ($i = 1; $i < $bl; $i++) {
        $jm = $breaks[$i];
        $jump = $jm - $jp;
        if ((int)$jy < $jm) { break; }
        $leapJ = $leapJ + intdiv($jump, 33) * 8 + intdiv($jump % 33, 4);
        $jp = $jm;
    }
    $n = (int)$jy - $jp;
    $leapJ = $leapJ + intdiv($n, 33) * 8 + intdiv($n % 33 + 3, 4);
    if ($jump % 33 === 4 && $jump - $n === 4) { $leapJ += 1; }
    $leapG = intdiv($gy, 4) - intdiv((intdiv($gy, 100) + 1) * 3, 4) - 150;
    $march = 20 + $leapJ - $leapG;
    if ($jump - $n < 6) { $n = $n - $jump + intdiv($jump + 4, 33) * 33; }
    $leap = (($n + 1) % 33 - 1) % 4;
    if ($leap === -1) { $leap = 4; }
    return ['leap' => $leap, 'gy' => $gy, 'march' => $march];
}
function jal_g2d($gy, $gm, $gd) {
    $d = intdiv(((int)$gy + intdiv((int)$gm - 8, 6) + 100100) * 1461, 4)
        + intdiv(153 * (((int)$gm + 9) % 12) + 2, 5)
        + (int)$gd - 34840408;
    $d = $d - intdiv(intdiv((int)$gy + 100100 + intdiv((int)$gm - 8, 6), 100) * 3, 4) + 752;
    return $d;
}
function jal_d2g($jdn) {
    $j = 4 * (int)$jdn + 139361631;
    $j = $j + intdiv(intdiv(4 * (int)$jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
    $i = intdiv($j % 1461, 4) * 5 + 308;
    $gd = intdiv($i % 153, 5) + 1;
    $gm = intdiv($i, 153) % 12 + 1;
    $gy = intdiv($j, 1461) - 100100 + intdiv(8 - $gm, 6);
    return ['gy' => $gy, 'gm' => $gm, 'gd' => $gd];
}
function jal_j2d($jy, $jm, $jd) {
    $r = jal_cal($jy);
    return jal_g2d($r['gy'], 3, $r['march']) + ((int)$jm - 1) * 31 - intdiv((int)$jm, 7) * ((int)$jm - 7) + (int)$jd - 1;
}
function jal_d2j($jdn) {
    $gy = jal_d2g($jdn)['gy'];
    $jy = $gy - 621;
    $r = jal_cal($jy);
    $jdn1f = jal_g2d($gy, 3, $r['march']);
    $k = (int)$jdn - $jdn1f;
    if ($k >= 0) {
        if ($k <= 185) {
            $jm = 1 + intdiv($k, 31);
            $jd = $k % 31 + 1;
            return ['jy' => $jy, 'jm' => $jm, 'jd' => $jd];
        } else {
            $k -= 186;
        }
    } else {
        $jy -= 1;
        $k += 179;
        if ($r['leap'] === 1) { $k += 1; }
    }
    $jm = 7 + intdiv($k, 30);
    $jd = $k % 30 + 1;
    return ['jy' => $jy, 'jm' => $jm, 'jd' => $jd];
}
function gregorian_to_jalali($gy, $gm, $gd) {
    return jal_d2j(jal_g2d((int)$gy, (int)$gm, (int)$gd));
}
function jalali_is_leap($jy) {
    return jal_cal($jy)['leap'] === 0;
}
function jalali_month_length($jy, $jm) {
    if ($jm <= 6) { return 31; }
    if ($jm <= 11) { return 30; }
    return jalali_is_leap($jy) ? 30 : 29;
}
function jalali_now() {
    static $cache = null;
    if ($cache === null) {
        $cache = gregorian_to_jalali((int)date('Y'), (int)date('n'), (int)date('j'));
    }
    return $cache;
}
function current_week_key() {
    $j = jalali_now();
    $doy = (int)$j['jd'];
    for ($m = 1; $m < (int)$j['jm']; $m++) {
        $doy += jalali_month_length((int)$j['jy'], $m);
    }
    $week = intdiv($doy - 1, 7) + 1;
    return (int)$j['jy'] . '-W' . $week;
}
function current_month_key() {
    $j = jalali_now();
    return (int)$j['jy'] . '-' . str_pad((int)$j['jm'], 2, '0', STR_PAD_LEFT);
}

function client_json() {
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function is_list_array($arr) {
    if (!is_array($arr)) {
        return false;
    }
    $i = 0;
    foreach ($arr as $k => $_) {
        if ($k !== $i) {
            return false;
        }
        $i++;
    }
    return true;
}

function normalize_store(&$store) {
    global $timer_defs;
    $changed = false;

    if (!is_array($store)) {
        $store = default_store();
        return true;
    }

    if (!isset($store['settings']) || !is_array($store['settings'])) {
        $store['settings'] = default_store()['settings'];
        $changed = true;
    }
    if (!isset($store['settings']['username']) || trim((string)$store['settings']['username']) === '') {
        $store['settings']['username'] = 'admin';
        $changed = true;
    }
    if (!isset($store['settings']['password_hash']) || !is_string($store['settings']['password_hash']) || $store['settings']['password_hash'] === '') {
        $store['settings']['password_hash'] = password_hash('admin', PASSWORD_DEFAULT);
        $changed = true;
    }
    if (!isset($store['settings']['remember_token_hash'])) {
        $store['settings']['remember_token_hash'] = null;
        $changed = true;
    }
    if (!isset($store['settings']['remember_token_expires'])) {
        $store['settings']['remember_token_expires'] = null;
        $changed = true;
    }
    if (!isset($store['settings']['referral_reward'])) {
        $store['settings']['referral_reward'] = 15000;
        $changed = true;
    }
    if (!isset($store['settings']['referral_step'])) {
        $store['settings']['referral_step'] = 100000;
        $changed = true;
    }
    if (!isset($store['settings']['gift_reward'])) {
        $store['settings']['gift_reward'] = 75000;
        $changed = true;
    }
    if (!isset($store['settings']['gift_step'])) {
        $store['settings']['gift_step'] = 750000;
        $changed = true;
    }
    if (!isset($store['settings']['ref_week_reward'])) {
        $store['settings']['ref_week_reward'] = 50000;
        $changed = true;
    }
    if (!isset($store['settings']['ref_week_step'])) {
        $store['settings']['ref_week_step'] = 1000000;
        $changed = true;
    }
    if (!isset($store['settings']['ref_month_reward'])) {
        $store['settings']['ref_month_reward'] = 300000;
        $changed = true;
    }
    if (!isset($store['settings']['ref_month_step'])) {
        $store['settings']['ref_month_step'] = 5000000;
        $changed = true;
    }
    if (!isset($store['settings']['card_number'])) {
        $store['settings']['card_number'] = '5859471028871667';
        $changed = true;
    } else {
        $cn = (string)$store['settings']['card_number'];
        // مهاجرت: اگه مقدار پیش‌فرض قبلی یا خالی بود، با مقدار جدید جایگزین کن
        if ($cn === '' || $cn === '6037-9911-2233-4455' || $cn === '6037991122334455') {
            $store['settings']['card_number'] = '5859471028871667';
            $changed = true;
        } else {
            $store['settings']['card_number'] = $cn;
        }
    }
    if (!isset($store['settings']['card_name'])) {
        $store['settings']['card_name'] = 'مهدی شیرازی';
        $changed = true;
    } else {
        $cna = (string)$store['settings']['card_name'];
        // مهاجرت: اگه نام پیش‌فرض قبلی یا خالی بود، با نام جدید جایگزین کن
        if ($cna === '' || $cna === 'نام صاحب کارت') {
            $store['settings']['card_name'] = 'مهدی شیرازی';
            $changed = true;
        } else {
            $store['settings']['card_name'] = $cna;
        }
    }
    if (!isset($store['settings']['card_description'])) {
        $store['settings']['card_description'] = '';
        $changed = true;
    } else {
        $store['settings']['card_description'] = (string)$store['settings']['card_description'];
    }

    if (!isset($store['income']) || !is_array($store['income'])) {
        $store['income'] = default_store()['income'];
        $changed = true;
    }
    if (!isset($store['income']['day'])) {
        $store['income']['day'] = today_key();
        $changed = true;
    }
    if ($store['income']['day'] !== today_key()) {
        $store['income']['day'] = today_key();
        $store['income']['today'] = 0;
        $store['income']['transactions'] = [];
        $changed = true;
    }
    if (!isset($store['income']['today'])) {
        $store['income']['today'] = 0;
        $changed = true;
    }
    if (!isset($store['income']['transactions']) || !is_array($store['income']['transactions'])) {
        $store['income']['transactions'] = [];
        $changed = true;
    }

    if (!isset($store['timers']) || !is_array($store['timers'])) {
        $store['timers'] = [];
        $changed = true;
    }
    if (is_list_array($store['timers'])) {
        $converted = [];
        foreach ($store['timers'] as $item) {
            if (is_array($item) && isset($item['id'])) {
                $converted[(string)$item['id']] = $item;
            }
        }
        $store['timers'] = $converted;
        $changed = true;
    }

    foreach ($timer_defs as $def) {
        $key = (string)$def['id'];
        if (!isset($store['timers'][$key]) || !is_array($store['timers'][$key])) {
            $store['timers'][$key] = [
                'id' => (int)$def['id'],
                'name' => $def['name'],
                'accent' => $def['accent'],
                'mode' => 'wallet',
                'source_type' => 'none',
                'source_subscription_id' => '',
                'balance' => (float)$def['default_balance'],
                'rate' => (float)$def['default_rate'],
                'running' => false,
                'last_tick' => null,
                'elapsed_seconds' => 0,
                'updated_at' => time(),
            ];
            $changed = true;
        } else {
            $t =& $store['timers'][$key];
            $defaults = [
                'id' => (int)$def['id'],
                'name' => $def['name'],
                'accent' => $def['accent'],
                'mode' => 'wallet',
                'source_type' => 'none',
                'source_subscription_id' => '',
                'balance' => (float)$def['default_balance'],
                'rate' => (float)$def['default_rate'],
                'running' => false,
                'last_tick' => null,
                'elapsed_seconds' => 0,
                'updated_at' => time(),
            ];
            foreach ($defaults as $k => $v) {
                if (!array_key_exists($k, $t)) {
                    $t[$k] = $v;
                    $changed = true;
                }
            }
            if (!in_array($t['mode'], ['wallet', 'counter'], true)) {
                $t['mode'] = 'wallet';
                $changed = true;
            }
            if (!in_array($t['source_type'], ['none', 'subscription'], true)) {
                $t['source_type'] = 'none';
                $changed = true;
            }
            $t['balance'] = max(0, (float)$t['balance']);
            $t['rate'] = max(0, (float)$t['rate']);
            $t['elapsed_seconds'] = max(0, (float)$t['elapsed_seconds']);
        }
    }

    if (!isset($store['subscriptions']) || !is_array($store['subscriptions'])) {
        $store['subscriptions'] = [];
        $changed = true;
    }
    if (is_list_array($store['subscriptions'])) {
        $converted = [];
        foreach ($store['subscriptions'] as $item) {
            if (is_array($item) && isset($item['id'])) {
                $converted[(string)$item['id']] = $item;
            }
        }
        $store['subscriptions'] = $converted;
        $changed = true;
    }

    $wk = current_week_key();
    $mk = current_month_key();
    $inviteIndex = [];
    foreach ($store['subscriptions'] as $id => &$sub) {
        if (!is_array($sub)) {
            $sub = [];
            $changed = true;
        }
        $id = (string)$id;
        if (!isset($sub['id']) || (string)$sub['id'] !== $id) {
            $sub['id'] = $id;
            $changed = true;
        }
        $sub['balance'] = isset($sub['balance']) ? max(0, (float)$sub['balance']) : 0;
        $sub['recharged_total'] = isset($sub['recharged_total']) ? max(0, (float)$sub['recharged_total']) : 0;
        $sub['created_at'] = isset($sub['created_at']) ? (int)$sub['created_at'] : time();
        $sub['updated_at'] = isset($sub['updated_at']) ? (int)$sub['updated_at'] : time();
        $sub['note'] = isset($sub['note']) ? (string)$sub['note'] : '';
        $sub['phone'] = isset($sub['phone']) ? trim((string)$sub['phone']) : '';
        $sub['birth_date'] = normalize_birth_date($sub['birth_date'] ?? '');
        $sub['twofa_secret'] = isset($sub['twofa_secret']) ? preg_replace('/[^A-Za-z2-7]/', '', (string)$sub['twofa_secret']) : '';
        $sub['twofa_pending_secret'] = isset($sub['twofa_pending_secret']) ? preg_replace('/[^A-Za-z2-7]/', '', (string)$sub['twofa_pending_secret']) : '';
        $sub['twofa_enabled'] = !empty($sub['twofa_enabled']) ? true : false;
        $sub['referred_by_id'] = isset($sub['referred_by_id']) ? (string)$sub['referred_by_id'] : '';
        $sub['invite_code'] = isset($sub['invite_code']) ? trim((string)$sub['invite_code']) : '';
        $sub['invite_code'] = preg_replace('/[^0-9]/', '', $sub['invite_code']);
        if ($sub['invite_code'] === '' || strlen($sub['invite_code']) !== 4 || (int)$sub['invite_code'] > 9999 || isset($inviteIndex[$sub['invite_code']])) {
            $sub['invite_code'] = generate_invite_code($inviteIndex);
            $changed = true;
        } else {
            $sub['invite_code'] = str_pad($sub['invite_code'], 4, '0', STR_PAD_LEFT);
        }
        $inviteIndex[$sub['invite_code']] = $id;
        if ($sub['referred_by_id'] !== '' && !isset($store['subscriptions'][$sub['referred_by_id']])) {
            $sub['referred_by_id'] = '';
            $changed = true;
        }

        if (!isset($sub['ref_week']) || !is_array($sub['ref_week'])) {
            $sub['ref_week'] = ['key' => $wk, 'total' => 0, 'paid' => 0];
            $changed = true;
        } elseif (($sub['ref_week']['key'] ?? '') !== $wk) {
            $sub['ref_week'] = ['key' => $wk, 'total' => 0, 'paid' => 0];
            $changed = true;
        } else {
            $sub['ref_week']['total'] = max(0, (float)($sub['ref_week']['total'] ?? 0));
            $sub['ref_week']['paid'] = max(0, (int)($sub['ref_week']['paid'] ?? 0));
        }

        if (!isset($sub['ref_month']) || !is_array($sub['ref_month'])) {
            $sub['ref_month'] = ['key' => $mk, 'total' => 0, 'paid' => 0];
            $changed = true;
        } elseif (($sub['ref_month']['key'] ?? '') !== $mk) {
            $sub['ref_month'] = ['key' => $mk, 'total' => 0, 'paid' => 0];
            $changed = true;
        } else {
            $sub['ref_month']['total'] = max(0, (float)($sub['ref_month']['total'] ?? 0));
            $sub['ref_month']['paid'] = max(0, (int)($sub['ref_month']['paid'] ?? 0));
        }
    }
    unset($sub);

    foreach ($store['timers'] as $id => &$timer) {
        if ($timer['source_type'] === 'subscription' && $timer['source_subscription_id'] !== '') {
            if (isset($store['subscriptions'][$timer['source_subscription_id']])) {
                $timer['balance'] = max(0, (float)$store['subscriptions'][$timer['source_subscription_id']]['balance']);
            } else {
                $timer['source_type'] = 'none';
                $timer['source_subscription_id'] = '';
                $timer['running'] = false;
                $timer['last_tick'] = null;
                $changed = true;
            }
        }
    }
    unset($timer);

    return $changed;
}

function load_store() {
    global $storeFile;
    if (!file_exists($storeFile)) {
        $store = default_store();
        save_store($storeFile, $store);
        return $store;
    }
    $raw = @file_get_contents($storeFile);
    $store = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
    if (!is_array($store)) {
        $store = default_store();
        save_store($storeFile, $store);
        return $store;
    }
    $changed = normalize_store($store);
    if ($changed) {
        save_store($storeFile, $store);
    }
    return $store;
}

function ensure_login_from_cookie(&$store) {
    if (!empty($_SESSION['admin_logged_in'])) {
        return true;
    }
    if (empty($_COOKIE['ghost_panel_remember'])) {
        return false;
    }
    $token = (string)$_COOKIE['ghost_panel_remember'];
    $hash = hash('sha256', $token);
    $expires = (int)($store['settings']['remember_token_expires'] ?? 0);
    $storedHash = (string)($store['settings']['remember_token_hash'] ?? '');
    if ($expires > time() && $storedHash !== '' && hash_equals($storedHash, $hash)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = (string)$store['settings']['username'];
        return true;
    }
    return false;
}

function clear_remember_token(&$store) {
    $store['settings']['remember_token_hash'] = null;
    $store['settings']['remember_token_expires'] = null;
}

function set_remember_cookie($token, $remember) {
    if ($remember) {
        setcookie('ghost_panel_remember', $token, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        setcookie('ghost_panel_remember', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function require_auth_api() {
    if (empty($_SESSION['admin_logged_in'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'unauthorized'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

function birthdays_today($store) {
    $j = jalali_now();
    $todayMD = str_pad((int)$j['jm'], 2, '0', STR_PAD_LEFT) . '-' . str_pad((int)$j['jd'], 2, '0', STR_PAD_LEFT);
    $list = [];
    foreach ($store['subscriptions'] as $id => $sub) {
        $bd = trim((string)($sub['birth_date'] ?? ''));
        if (preg_match('/^\d{4}-(\d{2}-\d{2})$/', $bd, $m) && $m[1] === $todayMD) {
            $list[] = [
                'id' => (string)$id,
                'phone' => (string)($sub['phone'] ?? ''),
                'note' => (string)($sub['note'] ?? ''),
                'invite_code' => (string)($sub['invite_code'] ?? ''),
            ];
        }
    }
    return $list;
}

function public_store_state($store) {
    return [
        'ok' => true,
        'server_time' => now_ts(),
        'settings' => [
            'username' => (string)$store['settings']['username'],
            'referral_reward' => (float)$store['settings']['referral_reward'],
            'referral_step' => (float)$store['settings']['referral_step'],
            'gift_reward' => (float)$store['settings']['gift_reward'],
            'gift_step' => (float)$store['settings']['gift_step'],
            'ref_week_reward' => (float)$store['settings']['ref_week_reward'],
            'ref_week_step' => (float)$store['settings']['ref_week_step'],
            'ref_month_reward' => (float)$store['settings']['ref_month_reward'],
            'ref_month_step' => (float)$store['settings']['ref_month_step'],
            'card_number' => (string)$store['settings']['card_number'],
            'card_name' => (string)$store['settings']['card_name'],
            'card_description' => (string)$store['settings']['card_description'],
        ],
        'timers' => array_values($store['timers']),
        'subscriptions' => array_values(array_map(function($s) use ($store) { return subscription_public($s, $store); }, $store['subscriptions'])),
        'income' => $store['income'],
        'birthdays_today' => birthdays_today($store),
        'current_user' => (string)($_SESSION['admin_username'] ?? $store['settings']['username']),
    ];
}

function add_income(&$store, $amount, $type, $subscriptionId = '', $meta = []) {
    $amount = max(0, (float)$amount);
    if ($amount <= 0) {
        return;
    }
    $store['income']['today'] = max(0, (float)($store['income']['today'] ?? 0)) + $amount;
    $store['income']['transactions'][] = array_merge([
        'ts' => time(),
        'type' => $type,
        'amount' => $amount,
        'subscription_id' => (string)$subscriptionId,
    ], $meta);
    if (count($store['income']['transactions']) > 250) {
        $store['income']['transactions'] = array_slice($store['income']['transactions'], -250);
    }
}

function perform_recharge(&$store, $subscriptionId, $amount, $label = 'شارژ اشتراک') {
    $out = ['referral_bonus' => 0, 'gift_bonus' => 0, 'week_bonus' => 0, 'month_bonus' => 0];
    $subscriptionId = trim((string)$subscriptionId);
    $amount = max(0, (float)$amount);
    if ($amount <= 0 || !isset($store['subscriptions'][$subscriptionId])) {
        return $out;
    }
    $store['subscriptions'][$subscriptionId]['balance'] = max(0, (float)$store['subscriptions'][$subscriptionId]['balance']) + $amount;
    $store['subscriptions'][$subscriptionId]['updated_at'] = time();
    add_income($store, $amount, 'recharge', $subscriptionId, ['label' => $label]);
    $bonuses = apply_recharge_bonuses($store, $subscriptionId, $amount);
    $refBonuses = apply_referral_recharge_rewards($store, $subscriptionId, $amount);
    $out['referral_bonus'] = $bonuses['referral_bonus'];
    $out['gift_bonus'] = $bonuses['gift_bonus'];
    $out['week_bonus'] = $refBonuses['week_bonus'];
    $out['month_bonus'] = $refBonuses['month_bonus'];
    return $out;
}

function apply_recharge_bonuses(&$store, $subscriberId, $creditedAmount) {
    $creditedAmount = max(0, (float)$creditedAmount);
    $result = ['referral_bonus' => 0, 'gift_bonus' => 0];
    if ($creditedAmount <= 0 || empty($store['subscriptions'][$subscriberId])) {
        return $result;
    }
    $refStep = max(1, (float)($store['settings']['referral_step'] ?? 100000));
    $refReward = max(0, (float)($store['settings']['referral_reward'] ?? 0));
    $giftStep = max(1, (float)($store['settings']['gift_step'] ?? 750000));
    $giftReward = max(0, (float)($store['settings']['gift_reward'] ?? 0));

    $sub =& $store['subscriptions'][$subscriberId];
    $beforeRef = floor(((float)$sub['recharged_total']) / $refStep);
    $beforeGift = floor(((float)$sub['recharged_total']) / $giftStep);
    $sub['recharged_total'] = (float)$sub['recharged_total'] + $creditedAmount;
    $afterRef = floor(((float)$sub['recharged_total']) / $refStep);
    $afterGift = floor(((float)$sub['recharged_total']) / $giftStep);

    $refChunks = max(0, (int)($afterRef - $beforeRef));
    if ($refChunks > 0 && !empty($sub['referred_by_id']) && isset($store['subscriptions'][$sub['referred_by_id']])) {
        $bonus = $refChunks * $refReward;
        $store['subscriptions'][$sub['referred_by_id']]['balance'] = max(0, (float)$store['subscriptions'][$sub['referred_by_id']]['balance']) + $bonus;
        $store['subscriptions'][$sub['referred_by_id']]['updated_at'] = time();
        $result['referral_bonus'] = $bonus;
    }

    $giftChunks = max(0, (int)($afterGift - $beforeGift));
    if ($giftChunks > 0 && $giftReward > 0) {
        $bonus = $giftChunks * $giftReward;
        $sub['balance'] = max(0, (float)$sub['balance']) + $bonus;
        $result['gift_bonus'] = $bonus;
    }

    return $result;
}

function apply_referral_recharge_rewards(&$store, $referredSubId, $amount) {
    $result = ['week_bonus' => 0, 'month_bonus' => 0];
    $amount = max(0, (float)$amount);
    if ($amount <= 0 || empty($store['subscriptions'][$referredSubId])) {
        return $result;
    }
    $refId = (string)($store['subscriptions'][$referredSubId]['referred_by_id'] ?? '');
    if ($refId === '' || !isset($store['subscriptions'][$refId])) {
        return $result;
    }
    $weekStep = max(1, (float)($store['settings']['ref_week_step'] ?? 1000000));
    $weekReward = max(0, (float)($store['settings']['ref_week_reward'] ?? 50000));
    $monthStep = max(1, (float)($store['settings']['ref_month_step'] ?? 5000000));
    $monthReward = max(0, (float)($store['settings']['ref_month_reward'] ?? 300000));

    $ref =& $store['subscriptions'][$refId];

    $w =& $ref['ref_week'];
    $w['total'] = (float)$w['total'] + $amount;
    $wChunks = (int)floor($w['total'] / $weekStep);
    if ($weekReward > 0 && $wChunks > (int)$w['paid']) {
        $delta = $wChunks - (int)$w['paid'];
        $bonus = $delta * $weekReward;
        $ref['balance'] = max(0, (float)$ref['balance']) + $bonus;
        $result['week_bonus'] = $bonus;
        $w['paid'] = $wChunks;
    }
    unset($w);

    $m =& $ref['ref_month'];
    $m['total'] = (float)$m['total'] + $amount;
    $mChunks = (int)floor($m['total'] / $monthStep);
    if ($monthReward > 0 && $mChunks > (int)$m['paid']) {
        $delta = $mChunks - (int)$m['paid'];
        $bonus = $delta * $monthReward;
        $ref['balance'] = max(0, (float)$ref['balance']) + $bonus;
        $result['month_bonus'] = $bonus;
        $m['paid'] = $mChunks;
    }
    unset($m);

    $ref['updated_at'] = time();
    return $result;
}

function sync_timer_from_subscription(&$store, &$timer) {
    if ($timer['source_type'] === 'subscription') {
        $sid = (string)$timer['source_subscription_id'];
        if ($sid !== '' && isset($store['subscriptions'][$sid])) {
            $timer['balance'] = max(0, (float)$store['subscriptions'][$sid]['balance']);
        }
    }
}

function advance_timer(&$store, &$timer, $now) {
    $changed = false;
    if ($timer['source_type'] === 'subscription') {
        sync_timer_from_subscription($store, $timer);
    }
    if (!empty($timer['running']) && !empty($timer['last_tick'])) {
        $elapsed = max(0, $now - (float)$timer['last_tick']);
        if ($elapsed > 0.03) {
            if ($timer['mode'] === 'counter') {
                $timer['elapsed_seconds'] = max(0, (float)$timer['elapsed_seconds']) + $elapsed;
                $timer['last_tick'] = $now;
                $changed = true;
            } else {
                $rate = max(0, (float)$timer['rate']);
                if ($rate > 0) {
                    $timer['balance'] = max(0, (float)$timer['balance'] - ($elapsed * $rate / 60.0));
                    $timer['last_tick'] = $now;
                    $changed = true;
                    if ($timer['source_type'] === 'subscription' && $timer['source_subscription_id'] !== '' && isset($store['subscriptions'][$timer['source_subscription_id']])) {
                        $store['subscriptions'][$timer['source_subscription_id']]['balance'] = max(0, (float)$timer['balance']);
                        $store['subscriptions'][$timer['source_subscription_id']]['updated_at'] = time();
                    }
                    if ($timer['balance'] <= 0.0001) {
                        $timer['balance'] = 0;
                        $timer['running'] = false;
                        $timer['last_tick'] = null;
                    }
                }
            }
        }
    }
    return $changed;
}

function advance_all_timers(&$store) {
    $now = now_ts();
    $changed = false;
    foreach ($store['timers'] as $id => &$timer) {
        $changed = advance_timer($store, $timer, $now) || $changed;
    }
    unset($timer);
    return $changed;
}

function find_subscription_by_invite($store, $code) {
    $code = preg_replace('/[^0-9]/', '', trim((string)$code));
    if ($code === '' || strlen($code) !== 4) {
        return '';
    }
    $code = str_pad($code, 4, '0', STR_PAD_LEFT);
    foreach ($store['subscriptions'] as $id => $sub) {
        if (!empty($sub['invite_code']) && (string)$sub['invite_code'] === $code) {
            return (string)$id;
        }
    }
    return '';
}

function timer_response_message($timer, $op) {
    if ($op === 'start') {
        if ($timer['mode'] === 'counter') {
            return 'تایمر شمارشی فعال شد.';
        }
        return 'تایمر فعال شد و از این لحظه شارژ کم می‌شود.';
    }
    if ($op === 'update') {
        if ($timer['mode'] === 'counter') {
            return 'تنظیمات تایمر شمارشی ذخیره شد.';
        }
        return 'تنظیمات تایمر ذخیره شد.';
    }
    if ($op === 'stop') {
        return 'تایمر متوقف شد.';
    }
    return 'ریست شد. حالا می‌توانی مقادیر جدید وارد کنی.';
}

function subscription_public($sub, $store = null) {
    $out = [
        'id' => (string)$sub['id'],
        'invite_code' => (string)$sub['invite_code'],
        'referred_by_id' => (string)($sub['referred_by_id'] ?? ''),
        'balance' => (float)$sub['balance'],
        'recharged_total' => (float)$sub['recharged_total'],
        'created_at' => (int)$sub['created_at'],
        'updated_at' => (int)$sub['updated_at'],
        'note' => (string)($sub['note'] ?? ''),
        'phone' => (string)($sub['phone'] ?? ''),
        'birth_date' => (string)($sub['birth_date'] ?? ''),
    ];
    if (is_array($store)) {
        $count = 0;
        $sid = (string)$sub['id'];
        foreach ($store['subscriptions'] as $other) {
            if ((string)($other['referred_by_id'] ?? '') === $sid) { $count++; }
        }
        $out['referral_count'] = $count;
        $out['ref_week_total'] = (float)($sub['ref_week']['total'] ?? 0);
        $out['ref_month_total'] = (float)($sub['ref_month']['total'] ?? 0);
    }
    return $out;
}

function logout_and_redirect(&$store) {
    global $storeFile;
    clear_remember_token($store);
    save_store($storeFile, $store);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    set_remember_cookie('', false);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$store = load_store();
ensure_login_from_cookie($store);

if (isset($_GET['logout'])) {
    logout_and_redirect($store);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);
    $storedUsername = (string)($store['settings']['username'] ?? 'admin');
    $storedHash = (string)($store['settings']['password_hash'] ?? '');

    if ($username === $storedUsername && $storedHash !== '' && password_verify($password, $storedHash)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $storedUsername;
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $store['settings']['remember_token_hash'] = hash('sha256', $token);
            $store['settings']['remember_token_expires'] = time() + 60 * 60 * 24 * 30;
            save_store($storeFile, $store);
            set_remember_cookie($token, true);
        } else {
            clear_remember_token($store);
            save_store($storeFile, $store);
            set_remember_cookie('', false);
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    $loginError = 'نام کاربری یا رمز عبور اشتباه است.';
}

if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $action = (string)$_GET['api'];
    $publicActions = ['user_lookup', 'user_card_info', 'user_recharge_request', 'user_console_status', 'user_2fa_setup', 'user_2fa_verify', 'user_2fa_login', 'user_2fa_disable'];
    if (in_array($action, $publicActions, true)) {
        // public, no auth required
    } elseif ($action !== 'state') {
        require_auth_api();
    } elseif (empty($_SESSION['admin_logged_in'])) {
        require_auth_api();
    }

    $body = array_merge($_GET, $_POST, client_json());

    if ($action === 'receipt_image') {
        $rid = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)($body['id'] ?? ''));
        if ($rid === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid_id']);
            exit;
        }
        $path = $receiptsDir . '/' . $rid . '.jpg';
        if (!file_exists($path)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'not_found']);
            exit;
        }
        header('Content-Type: image/jpeg');
        header('Cache-Control: no-store');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    $store = load_store();
    $changed = advance_all_timers($store);

    if ($action === 'user_lookup') {
        $sid = trim((string)($body['id'] ?? ''));
        if ($sid === '' || !isset($store['subscriptions'][$sid])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'subscription_not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $sub = $store['subscriptions'][$sid];
        // اگه تایید دو مرحله‌ای فعال است، ابتدا کد گوگل لازم است (اطلاعات نمایش داده نمی‌شود)
        if (!empty($sub['twofa_enabled']) && !empty($sub['twofa_secret'])) {
            echo json_encode([
                'ok' => true,
                'twofa_required' => true,
                'twofa_enabled' => true,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $count = 0;
        foreach ($store['subscriptions'] as $other) {
            if ((string)($other['referred_by_id'] ?? '') === $sid) {
                $count++;
            }
        }
        // وضعیت فعال: اشتراک روی یکی از تایمرها سوار و در حال اجراست
        $inTimerRunning = false;
        foreach ($store['timers'] as $timer) {
            if ($timer['source_type'] === 'subscription'
                && (string)$timer['source_subscription_id'] === $sid
                && !empty($timer['running'])) {
                $inTimerRunning = true;
                break;
            }
        }
        $balance = (float)$sub['balance'];
        echo json_encode([
            'ok' => true,
            'active' => $inTimerRunning,
            'twofa_enabled' => false,
            'subscription' => [
                'id' => (string)$sub['id'],
                'balance' => $balance,
                'recharged_total' => (float)$sub['recharged_total'],
                'created_at' => (int)$sub['created_at'],
                'invite_code' => (string)$sub['invite_code'],
                'referred_by_id' => (string)($sub['referred_by_id'] ?? ''),
                'note' => (string)($sub['note'] ?? ''),
                'referral_count' => $count,
                'ref_week_total' => (float)($sub['ref_week']['total'] ?? 0),
                'ref_month_total' => (float)($sub['ref_month']['total'] ?? 0),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'user_console_status') {
        $consoles = [];
        foreach ($store['timers'] as $timer) {
            $consoles[] = [
                'name' => (string)$timer['name'],
                'running' => !empty($timer['running']),
            ];
        }
        echo json_encode(['ok' => true, 'consoles' => $consoles], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'user_2fa_setup') {
        $sid = trim((string)($body['id'] ?? ''));
        if ($sid === '' || !isset($store['subscriptions'][$sid])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'subscription_not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $secret = totp_generate_secret(20);
        $store['subscriptions'][$sid]['twofa_pending_secret'] = $secret;
        $store['subscriptions'][$sid]['updated_at'] = time();
        save_store($storeFile, $store);
        $uri = totp_otpauth_uri($secret, $sid);
        $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=0&data=' . rawurlencode($uri);
        echo json_encode([
            'ok' => true,
            'secret' => $secret,
            'otpauth_uri' => $uri,
            'qr_url' => $qr,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'user_2fa_verify') {
        $sid = trim((string)($body['id'] ?? ''));
        $code = (string)($body['code'] ?? '');
        if ($sid === '' || !isset($store['subscriptions'][$sid])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'subscription_not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $sub = $store['subscriptions'][$sid];
        $secret = (string)($sub['twofa_pending_secret'] ?? '');
        if ($secret === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'setup_not_started'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if (!totp_verify($secret, $code)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid_code'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $store['subscriptions'][$sid]['twofa_secret'] = $secret;
        $store['subscriptions'][$sid]['twofa_enabled'] = true;
        $store['subscriptions'][$sid]['twofa_pending_secret'] = '';
        $store['subscriptions'][$sid]['updated_at'] = time();
        save_store($storeFile, $store);
        echo json_encode(['ok' => true, 'message' => 'تایید دو مرحله‌ای با موفقیت فعال شد.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'user_2fa_login') {
        $sid = trim((string)($body['id'] ?? ''));
        $code = (string)($body['code'] ?? '');
        if ($sid === '' || !isset($store['subscriptions'][$sid])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'subscription_not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $sub = $store['subscriptions'][$sid];
        $secret = (string)($sub['twofa_secret'] ?? '');
        if (empty($sub['twofa_enabled']) || $secret === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'twofa_not_enabled'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if (!totp_verify($secret, $code)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid_code'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $count = 0;
        foreach ($store['subscriptions'] as $other) {
            if ((string)($other['referred_by_id'] ?? '') === $sid) {
                $count++;
            }
        }
        $inTimerRunning = false;
        foreach ($store['timers'] as $timer) {
            if ($timer['source_type'] === 'subscription'
                && (string)$timer['source_subscription_id'] === $sid
                && !empty($timer['running'])) {
                $inTimerRunning = true;
                break;
            }
        }
        $balance = (float)$sub['balance'];
        echo json_encode([
            'ok' => true,
            'active' => $inTimerRunning,
            'twofa_enabled' => true,
            'subscription' => [
                'id' => (string)$sub['id'],
                'balance' => $balance,
                'recharged_total' => (float)$sub['recharged_total'],
                'created_at' => (int)$sub['created_at'],
                'invite_code' => (string)$sub['invite_code'],
                'referred_by_id' => (string)($sub['referred_by_id'] ?? ''),
                'note' => (string)($sub['note'] ?? ''),
                'referral_count' => $count,
                'ref_week_total' => (float)($sub['ref_week']['total'] ?? 0),
                'ref_month_total' => (float)($sub['ref_month']['total'] ?? 0),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'user_2fa_disable') {
        $sid = trim((string)($body['id'] ?? ''));
        $code = (string)($body['code'] ?? '');
        if ($sid === '' || !isset($store['subscriptions'][$sid])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'subscription_not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $sub = $store['subscriptions'][$sid];
        $secret = (string)($sub['twofa_secret'] ?? '');
        if (empty($sub['twofa_enabled']) || $secret === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'twofa_not_enabled'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if (!totp_verify($secret, $code)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid_code'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $store['subscriptions'][$sid]['twofa_secret'] = '';
        $store['subscriptions'][$sid]['twofa_pending_secret'] = '';
        $store['subscriptions'][$sid]['twofa_enabled'] = false;
        $store['subscriptions'][$sid]['updated_at'] = time();
        save_store($storeFile, $store);
        echo json_encode(['ok' => true, 'message' => 'تایید دو مرحله‌ای غیرفعال شد.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'user_card_info') {
        echo json_encode([
            'ok' => true,
            'card_number' => (string)$store['settings']['card_number'],
            'card_name' => (string)$store['settings']['card_name'],
            'card_description' => (string)$store['settings']['card_description'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'user_recharge_request') {
        $sid = trim((string)($body['id'] ?? ''));
        $amount = parse_amount($body['amount'] ?? 0);
        $img = trim((string)($body['receipt'] ?? ''));

        if ($sid === '' || !isset($store['subscriptions'][$sid])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'subscription_not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if ($amount <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'amount_invalid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        // strip data URI prefix
        $img = preg_replace('/^data:[^;]+;base64,/', '', $img);
        $bin = base64_decode($img, true);
        if ($bin === false || strlen($bin) < 200) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'receipt_invalid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        // basic image sanity check
        $info = @getimagesizefromstring($bin);
        if ($info === false) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'receipt_invalid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if (strlen($bin) > 4 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'receipt_too_large'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if (!is_dir($receiptsDir)) {
            @mkdir($receiptsDir, 0775, true);
        }
        $rid = 'R' . dechex(time()) . bin2hex(random_bytes(3));
        $saved = @file_put_contents($receiptsDir . '/' . $rid . '.jpg', $bin, LOCK_EX);
        if ($saved === false) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'save_failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $receipts = load_receipts($receiptsFile);
        $receipts[$rid] = [
            'id' => $rid,
            'subscription_id' => $sid,
            'amount' => $amount,
            'status' => 'pending',
            'created_at' => time(),
            'ext' => 'jpg',
        ];
        // keep last 300 receipts
        if (count($receipts) > 300) {
            $receipts = array_slice($receipts, -300, null, true);
        }
        save_receipts($receiptsFile, $receipts);

        echo json_encode([
            'ok' => true,
            'message' => 'سفارش شما ثبت شد. اشتراک شما نهایت تا ۱۰ دقیقه شارژ می‌شود.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'receipt_list') {
        $receipts = load_receipts($receiptsFile);
        $list = array_values($receipts);
        usort($list, function ($a, $b) {
            return ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0);
        });
        $out = array_map(function ($r) use ($store) {
            $pub = receipt_public($r);
            $sid = (string)$r['subscription_id'];
            $pub['subscription_exists'] = isset($store['subscriptions'][$sid]);
            $pub['balance'] = isset($store['subscriptions'][$sid]) ? (float)$store['subscriptions'][$sid]['balance'] : null;
            return $pub;
        }, $list);
        echo json_encode(['ok' => true, 'receipts' => $out], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'receipt_action') {
        $rid = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)($body['id'] ?? ''));
        $op = strtolower(trim((string)($body['op'] ?? '')));
        $receipts = load_receipts($receiptsFile);
        if ($rid === '' || !isset($receipts[$rid])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'receipt_not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if (!in_array($op, ['confirm', 'cancel'], true)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'unknown_op'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if ($receipts[$rid]['status'] !== 'pending') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'already_processed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $r = $receipts[$rid];
        $bonusSummary = ['referral_bonus' => 0, 'gift_bonus' => 0, 'week_bonus' => 0, 'month_bonus' => 0];
        if ($op === 'confirm') {
            $receipts[$rid]['status'] = 'confirmed';
            $bonusSummary = perform_recharge($store, $r['subscription_id'], $r['amount'], 'تأیید رسید');
            save_store($storeFile, $store);
        } else {
            $receipts[$rid]['status'] = 'cancelled';
        }
        $receipts[$rid]['processed_at'] = time();
        save_receipts($receiptsFile, $receipts);

        echo json_encode([
            'ok' => true,
            'message' => $op === 'confirm' ? 'رسید تأیید شد و اشتراک شارژ شد.' : 'رسید لغو شد.',
            'receipt' => receipt_public($receipts[$rid]),
            'bonuses' => $bonusSummary,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'state') {
        if ($changed) {
            save_store($storeFile, $store);
        }
        echo json_encode(public_store_state($store), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'timer_action') {
        $id = (string)intval($body['id'] ?? 0);
        $op = strtolower(trim((string)($body['op'] ?? '')));
        if ($id === '' || !isset($store['timers'][$id])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'timer_not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $timer =& $store['timers'][$id];
        $now = now_ts();

        if (in_array($op, ['start', 'update'], true)) {
            $mode = (string)($body['mode'] ?? 'wallet');
            $sourceType = (string)($body['source_type'] ?? 'none');
            $rate = parse_amount($body['rate'] ?? 0);
            $balance = parse_amount($body['balance'] ?? 0);
            $subscriptionId = trim((string)($body['source_subscription_id'] ?? ''));

            if ($mode !== 'counter') {
                $mode = 'wallet';
            }
            if ($mode === 'counter') {
                $sourceType = 'none';
                $subscriptionId = '';
            } elseif ($sourceType !== 'subscription') {
                $sourceType = 'none';
                $subscriptionId = '';
            }

            $timer['mode'] = $mode;
            $timer['source_type'] = $sourceType;
            $timer['source_subscription_id'] = $subscriptionId;
            $timer['rate'] = $rate;

            if ($timer['source_type'] === 'subscription') {
                if ($subscriptionId === '' || !isset($store['subscriptions'][$subscriptionId])) {
                    http_response_code(400);
                    echo json_encode(['ok' => false, 'error' => 'subscription_not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit;
                }
                $timer['balance'] = max(0, (float)$store['subscriptions'][$subscriptionId]['balance']);
            } else {
                $timer['balance'] = $mode === 'counter' ? 0 : $balance;
            }

            if ($op === 'start') {
                if ($mode === 'counter') {
                    if ($rate <= 0) {
                        $timer['running'] = false;
                        $timer['last_tick'] = null;
                        $msg = 'برای شروع تایمر شمارشی، قیمت هر دقیقه باید بیشتر از صفر باشد.';
                    } else {
                        $timer['running'] = true;
                        $timer['last_tick'] = $now;
                        $msg = timer_response_message($timer, $op);
                    }
                } else {
                    if ($rate > 0 && $timer['balance'] > 0) {
                        $timer['running'] = true;
                        $timer['last_tick'] = $now;
                        $msg = timer_response_message($timer, $op);
                    } else {
                        $timer['running'] = false;
                        $timer['last_tick'] = null;
                        $msg = 'برای شروع، موجودی و قیمت هر دقیقه باید بیشتر از صفر باشند.';
                    }
                }
            } else {
                if ($mode === 'counter') {
                    if ($rate <= 0) {
                        $timer['running'] = false;
                        $timer['last_tick'] = null;
                        $msg = 'برای تایمر شمارشی، قیمت هر دقیقه باید بیشتر از صفر باشد.';
                    } elseif ($timer['running']) {
                        $timer['last_tick'] = $now;
                        $msg = 'مقادیر جدید ذخیره شد و تایمر شمارشی ادامه دارد.';
                    } else {
                        $msg = timer_response_message($timer, $op);
                    }
                } else {
                    if ($timer['balance'] <= 0 || $rate <= 0) {
                        $timer['running'] = false;
                        $timer['last_tick'] = null;
                        $msg = 'برای شروع، موجودی و قیمت هر دقیقه باید بیشتر از صفر باشند.';
                    } else {
                        if ($timer['running']) {
                            $timer['last_tick'] = $now;
                            $msg = 'مقادیر جدید ذخیره شد و تایمر ادامه دارد.';
                        } else {
                            $msg = 'مقادیر ذخیره شد. حالا می‌توانی شروع کنی.';
                        }
                    }
                }
            }

            $timer['updated_at'] = time();
            unset($timer);
            save_store($storeFile, $store);
            echo json_encode(['ok' => true, 'timer' => $store['timers'][$id], 'message' => $msg], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if ($op === 'stop') {
            $timer['running'] = false;
            $timer['last_tick'] = null;
            $timer['updated_at'] = time();
            save_store($storeFile, $store);
            echo json_encode(['ok' => true, 'timer' => $timer, 'message' => timer_response_message($timer, $op)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if ($op === 'reset') {
            $timer['mode'] = 'wallet';
            $timer['source_type'] = 'none';
            $timer['source_subscription_id'] = '';
            $timer['balance'] = 0;
            $timer['rate'] = 0;
            $timer['running'] = false;
            $timer['last_tick'] = null;
            $timer['elapsed_seconds'] = 0;
            $timer['updated_at'] = time();
            save_store($storeFile, $store);
            echo json_encode(['ok' => true, 'timer' => $timer, 'message' => timer_response_message($timer, $op)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'unknown_timer_action'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'subscription_create') {
        $subscriptionId = trim((string)($body['id'] ?? ''));
        $inviteRef = strtoupper(trim((string)($body['invite_ref'] ?? '')));
        $initialBalance = parse_amount($body['initial_balance'] ?? 0);
        $note = trim((string)($body['note'] ?? ''));
        $phone = trim((string)($body['phone'] ?? ''));
        $birthDate = normalize_birth_date($body['birth_date'] ?? '');

        if ($subscriptionId === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'subscription_id_required'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if (isset($store['subscriptions'][$subscriptionId])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'subscription_exists'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $referredById = '';
        $inviteRef = preg_replace('/[^0-9]/', '', $inviteRef);
        if ($inviteRef !== '' && strlen($inviteRef) === 4) {
            $referredById = find_subscription_by_invite($store, $inviteRef);
            if ($referredById === '') {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'invite_code_invalid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        } elseif ($inviteRef !== '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invite_code_invalid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $existingCodes = [];
        foreach ($store['subscriptions'] as $sub) {
            $existingCodes[(string)$sub['invite_code']] = true;
        }
        $inviteCode = generate_invite_code($existingCodes);

        $store['subscriptions'][$subscriptionId] = [
            'id' => $subscriptionId,
            'invite_code' => $inviteCode,
            'referred_by_id' => $referredById,
            'balance' => $initialBalance,
            'recharged_total' => 0,
            'created_at' => time(),
            'updated_at' => time(),
            'note' => $note,
            'phone' => $phone,
            'birth_date' => $birthDate,
            'twofa_secret' => '',
            'twofa_pending_secret' => '',
            'twofa_enabled' => false,
        ];

        add_income($store, $initialBalance, 'create', $subscriptionId, ['label' => 'ساخت اشتراک']);
        $bonuses = apply_recharge_bonuses($store, $subscriptionId, $initialBalance);
        $refBonuses = apply_referral_recharge_rewards($store, $subscriptionId, $initialBalance);
        $store['subscriptions'][$subscriptionId]['updated_at'] = time();
        save_store($storeFile, $store);

        echo json_encode([
            'ok' => true,
            'message' => 'اشتراک ساخته شد.',
            'subscription' => subscription_public($store['subscriptions'][$subscriptionId], $store),
            'bonus_added' => $bonuses['referral_bonus'],
            'gift_added' => $bonuses['gift_bonus'],
            'week_bonus' => $refBonuses['week_bonus'],
            'month_bonus' => $refBonuses['month_bonus'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'subscription_recharge') {
        $subscriptionId = trim((string)($body['id'] ?? ''));
        $amount = parse_amount($body['amount'] ?? 0);
        if ($subscriptionId === '' || !isset($store['subscriptions'][$subscriptionId])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'subscription_not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if ($amount <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'amount_invalid'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $bonuses = perform_recharge($store, $subscriptionId, $amount, 'شارژ اشتراک');
        save_store($storeFile, $store);

        echo json_encode([
            'ok' => true,
            'message' => 'اشتراک شارژ شد.',
            'subscription' => subscription_public($store['subscriptions'][$subscriptionId], $store),
            'bonus_added' => $bonuses['referral_bonus'],
            'gift_added' => $bonuses['gift_bonus'],
            'week_bonus' => $bonuses['week_bonus'],
            'month_bonus' => $bonuses['month_bonus'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'subscription_update') {
        $subscriptionId = trim((string)($body['id'] ?? ''));
        if ($subscriptionId === '' || !isset($store['subscriptions'][$subscriptionId])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'subscription_not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $newBalance = isset($body['balance']) ? parse_amount($body['balance']) : null;
        $newInvite = isset($body['invite_code']) ? strtoupper(trim((string)$body['invite_code'])) : null;
        $newNote = isset($body['note']) ? trim((string)$body['note']) : null;
        $newPhone = isset($body['phone']) ? trim((string)$body['phone']) : null;
        $newBirth = isset($body['birth_date']) ? normalize_birth_date($body['birth_date']) : null;

        if ($newInvite !== null) {
            $newInvite = preg_replace('/[^0-9]/', '', $newInvite);
            if ($newInvite === '' || strlen($newInvite) !== 4 || (int)$newInvite > 9999) {
                $existingCodes = [];
                foreach ($store['subscriptions'] as $sid => $sub) {
                    if ((string)$sid !== $subscriptionId) {
                        $existingCodes[(string)$sub['invite_code']] = true;
                    }
                }
                $newInvite = generate_invite_code($existingCodes);
            } else {
                $newInvite = str_pad($newInvite, 4, '0', STR_PAD_LEFT);
                foreach ($store['subscriptions'] as $sid => $sub) {
                    if ((string)$sid !== $subscriptionId && strtoupper((string)$sub['invite_code']) === $newInvite) {
                        http_response_code(400);
                        echo json_encode(['ok' => false, 'error' => 'invite_code_duplicate'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        exit;
                    }
                }
            }
            $store['subscriptions'][$subscriptionId]['invite_code'] = $newInvite;
        }
        if ($newBalance !== null) {
            $store['subscriptions'][$subscriptionId]['balance'] = $newBalance;
        }
        if ($newNote !== null) {
            $store['subscriptions'][$subscriptionId]['note'] = $newNote;
        }
        if ($newPhone !== null) {
            $store['subscriptions'][$subscriptionId]['phone'] = $newPhone;
        }
        if ($newBirth !== null) {
            $store['subscriptions'][$subscriptionId]['birth_date'] = $newBirth;
        }
        $store['subscriptions'][$subscriptionId]['updated_at'] = time();
        save_store($storeFile, $store);

        echo json_encode([
            'ok' => true,
            'message' => 'اشتراک ذخیره شد.',
            'subscription' => subscription_public($store['subscriptions'][$subscriptionId], $store),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'subscription_delete') {
        $subscriptionId = trim((string)($body['id'] ?? ''));
        if ($subscriptionId === '' || !isset($store['subscriptions'][$subscriptionId])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'subscription_not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        foreach ($store['timers'] as &$timer) {
            if ($timer['source_type'] === 'subscription' && (string)$timer['source_subscription_id'] === $subscriptionId) {
                $timer['source_type'] = 'none';
                $timer['source_subscription_id'] = '';
                $timer['running'] = false;
                $timer['last_tick'] = null;
            }
        }
        unset($timer);

        unset($store['subscriptions'][$subscriptionId]);
        save_store($storeFile, $store);

        echo json_encode(['ok' => true, 'message' => 'اشتراک حذف شد.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'settings_save') {
        $newUsername = trim((string)($body['username'] ?? ''));
        $newPassword = (string)($body['password'] ?? '');
        $reward = parse_amount($body['referral_reward'] ?? 15000);
        $step = max(1, parse_amount($body['referral_step'] ?? 100000));
        $giftReward = parse_amount($body['gift_reward'] ?? 75000);
        $giftStep = max(1, parse_amount($body['gift_step'] ?? 750000));
        $weekReward = parse_amount($body['ref_week_reward'] ?? 50000);
        $weekStep = max(1, parse_amount($body['ref_week_step'] ?? 1000000));
        $monthReward = parse_amount($body['ref_month_reward'] ?? 300000);
        $monthStep = max(1, parse_amount($body['ref_month_step'] ?? 5000000));
        $cardNumber = isset($body['card_number']) ? trim((string)$body['card_number']) : null;
        $cardName = isset($body['card_name']) ? trim((string)$body['card_name']) : null;
        $cardDesc = isset($body['card_description']) ? trim((string)$body['card_description']) : null;
        $tokenClear = false;

        if ($newUsername === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'username_required'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if ($newUsername !== (string)$store['settings']['username']) {
            $tokenClear = true;
        }
        $store['settings']['username'] = $newUsername;

        if ($newPassword !== '') {
            $store['settings']['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $tokenClear = true;
        }
        $store['settings']['referral_reward'] = $reward;
        $store['settings']['referral_step'] = $step;
        $store['settings']['gift_reward'] = $giftReward;
        $store['settings']['gift_step'] = $giftStep;
        $store['settings']['ref_week_reward'] = $weekReward;
        $store['settings']['ref_week_step'] = $weekStep;
        $store['settings']['ref_month_reward'] = $monthReward;
        $store['settings']['ref_month_step'] = $monthStep;
        if ($cardNumber !== null) {
            $store['settings']['card_number'] = $cardNumber;
        }
        if ($cardName !== null) {
            $store['settings']['card_name'] = $cardName;
        }
        if ($cardDesc !== null) {
            $store['settings']['card_description'] = $cardDesc;
        }

        if ($tokenClear) {
            clear_remember_token($store);
            set_remember_cookie('', false);
        }

        $_SESSION['admin_username'] = $store['settings']['username'];
        save_store($storeFile, $store);

        echo json_encode([
            'ok' => true,
            'message' => 'تنظیمات ذخیره شد.',
            'settings' => [
                'username' => (string)$store['settings']['username'],
                'referral_reward' => (float)$store['settings']['referral_reward'],
                'referral_step' => (float)$store['settings']['referral_step'],
                'gift_reward' => (float)$store['settings']['gift_reward'],
                'gift_step' => (float)$store['settings']['gift_step'],
                'ref_week_reward' => (float)$store['settings']['ref_week_reward'],
                'ref_week_step' => (float)$store['settings']['ref_week_step'],
                'ref_month_reward' => (float)$store['settings']['ref_month_reward'],
                'ref_month_step' => (float)$store['settings']['ref_month_step'],
                'card_number' => (string)$store['settings']['card_number'],
                'card_name' => (string)$store['settings']['card_name'],
                'card_description' => (string)$store['settings']['card_description'],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'logout') {
        logout_and_redirect($store);
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'unknown_action'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$loggedIn = !empty($_SESSION['admin_logged_in']);
if ($loggedIn) {
    $changed = advance_all_timers($store);
    if ($changed) {
        save_store($storeFile, $store);
    }
}

if (!$loggedIn):
    $loginError = $loginError ?? '';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود ادمین - Neon Timer Lab</title>
    <link rel="icon" href="<?php echo h($favicon); ?>">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: dark; --cyan:#22d3ee; --violet:#a855f7; --gold:#fbbf24; }
        * { box-sizing: border-box; }
        body { font-family: "Vazirmatn", system-ui, sans-serif; color:#e6ebff;
            background:
                radial-gradient(circle at 80% -10%, rgba(34,211,238,.20), transparent 30%),
                radial-gradient(circle at 10% 25%, rgba(168,85,247,.20), transparent 28%),
                radial-gradient(circle at 50% 115%, rgba(52,211,153,.14), transparent 30%),
                linear-gradient(180deg,#04060f 0%,#070b1c 55%,#03050d 100%);
            background-attachment: fixed; }
        #bg-canvas { position: fixed; inset: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
        .bg-grid { position: fixed; inset: 0; z-index: 1; pointer-events: none;
            background-image: linear-gradient(rgba(120,180,255,.06) 1px, transparent 1px), linear-gradient(90deg, rgba(120,180,255,.06) 1px, transparent 1px);
            background-size: 64px 64px; opacity:.5;
            mask-image: radial-gradient(circle at 50% 30%, #000 0%, transparent 80%);
            -webkit-mask-image: radial-gradient(circle at 50% 30%, #000 0%, transparent 80%);
            animation: gridMove 22s linear infinite; }
        .scanlines { position: fixed; inset: 0; z-index: 2; pointer-events: none;
            background: repeating-linear-gradient(0deg, rgba(255,255,255,.025) 0 1px, transparent 1px 4px);
            opacity:.5; mix-blend-mode: overlay; }
        .orb { position: fixed; border-radius: 9999px; filter: blur(50px); opacity:.5; mix-blend-mode: screen; z-index: 1; pointer-events: none; }
        .orb-1 { top: -9rem; right: -7rem; width: 26rem; height: 26rem; background: radial-gradient(circle, rgba(34,211,238,.4), transparent 70%); animation: driftA 16s ease-in-out infinite; }
        .orb-2 { left: -9rem; top: 16%; width: 24rem; height: 24rem; background: radial-gradient(circle, rgba(168,85,247,.36), transparent 70%); animation: driftB 18s ease-in-out infinite; }
        .orb-3 { right: 16%; bottom: -11rem; width: 30rem; height: 30rem; background: radial-gradient(circle, rgba(52,211,153,.24), transparent 72%); animation: driftC 20s ease-in-out infinite; }
        .panel { position: relative; overflow: hidden; background: rgba(10,14,31,.74); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.08); border-radius: 1.6rem;
            box-shadow: 0 0 0 1px rgba(255,255,255,.02), 0 24px 70px rgba(0,0,0,.55), inset 0 1px 0 rgba(255,255,255,.05); }
        .panel::before { content:''; position:absolute; inset:0; border-radius: inherit; padding:1px;
            background: linear-gradient(135deg, rgba(34,211,238,.55), transparent 40%, transparent 60%, rgba(168,85,247,.45));
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none; }
        .panel > * { position: relative; }
        .field { width: 100%; border-radius: .9rem; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.09); padding: .9rem 1rem; color: #fff; outline: none; transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; }
        .field:focus { border-color: var(--cyan); background: rgba(255,255,255,.07); box-shadow: 0 0 0 3px rgba(34,211,238,.18), 0 0 22px rgba(34,211,238,.16); }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; border-radius: .9rem; padding: .9rem 1.2rem; font-weight: 800; transition: transform .18s ease, filter .18s ease, opacity .18s ease; user-select: none; }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.08); }
        .btn:active { transform: translateY(0); }
        .btn:disabled { opacity: .45; cursor: not-allowed; transform: none; filter: none; }
        .btn-primary { background: linear-gradient(135deg, #22d3ee, #34d399); color: #04121a; box-shadow: 0 12px 32px rgba(34,211,238,.32); }
        .ghost-label { letter-spacing: .4em; font-weight: 800; color: var(--cyan); text-shadow: 0 0 16px rgba(34,211,238,.5); }
        .glow-title { text-shadow: 0 0 24px rgba(34,211,238,.4); }
        @keyframes gridMove { from { transform: translateY(0); } to { transform: translateY(64px); } }
        @keyframes driftA { 0%,100%{transform:translate3d(0,0,0) scale(1)} 50%{transform:translate3d(-24px,18px,0) scale(1.08)} }
        @keyframes driftB { 0%,100%{transform:translate3d(0,0,0) scale(1)} 50%{transform:translate3d(18px,-20px,0) scale(.94)} }
        @keyframes driftC { 0%,100%{transform:translate3d(0,0,0) scale(1)} 50%{transform:translate3d(-18px,-14px,0) scale(1.06)} }
    </style>
</head>
<body class="text-white">
    <canvas id="bg-canvas"></canvas>
    <div class="bg-grid"></div>
    <div class="scanlines"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <main class="relative z-10 flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
        <div class="panel w-full max-w-md rounded-[1.75rem] p-6 sm:p-8">
            <div class="relative">
                <div class="flex items-center gap-4">
                    <div class="ghost-logo-wrap">
                        <?php render_ghost_icon('h-14 w-14 text-cyan-200'); ?>
                    </div>
                    <div>
                        <div class="ghost-label text-xs">NEON TIMER LAB</div>
                        <h1 class="mt-1 text-2xl font-black glow-title">ورود ادمین</h1>
                    </div>
                </div>

                <p class="mt-5 text-sm leading-7 text-slate-300">با یوزرنیم و پسورد وارد پنل شو. اگر گزینه ذخیره ورود را فعال کنی، دفعه بعد نیاز به ورود مجدد نداری.</p>

                <?php if (!empty($loginError)): ?>
                    <div class="mt-4 rounded-2xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200"><?php echo h($loginError); ?></div>
                <?php endif; ?>

                <form class="mt-6 grid gap-4" method="post" action="">
                    <input type="hidden" name="action" value="login">
                    <label class="grid gap-2">
                        <span class="text-sm text-slate-300">یوزرنیم</span>
                        <input class="field" type="text" name="username" value="admin" autocomplete="username" dir="ltr" placeholder="admin">
                    </label>
                    <label class="grid gap-2">
                        <span class="text-sm text-slate-300">پسورد</span>
                        <input class="field" type="password" name="password" autocomplete="current-password" dir="ltr" placeholder="admin">
                    </label>
                    <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm text-slate-200">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-white/20 bg-transparent text-cyan-400 focus:ring-cyan-400">
                        ذخیره ورود برای دفعات بعد
                    </label>
                    <button type="submit" class="btn btn-primary">ورود به پنل</button>
                </form>
            </div>
        </div>
    </main>

    <script>
    (function(){
        const canvas = document.getElementById('bg-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];
        function resize(){
            const dpr = Math.max(1, window.devicePixelRatio || 1);
            canvas.width = window.innerWidth * dpr;
            canvas.height = window.innerHeight * dpr;
            canvas.style.width = window.innerWidth + 'px';
            canvas.style.height = window.innerHeight + 'px';
            ctx.setTransform(dpr,0,0,dpr,0,0);
            particles = [];
            const total = Math.min(80, Math.max(40, Math.floor(window.innerWidth / 21)));
            for(let i=0;i<total;i++){
                particles.push({x: Math.random()*window.innerWidth, y: Math.random()*window.innerHeight, vx: (Math.random()-0.5)*0.28, vy: (Math.random()-0.5)*0.28, r: Math.random()*1.8 + 0.6, hue: Math.random()>0.5 ? 190 : 280, alpha: Math.random()*0.5+0.16});
            }
        }
        function draw(){
            ctx.clearRect(0,0,window.innerWidth,window.innerHeight);
            particles.forEach(p=>{
                p.x += p.vx; p.y += p.vy;
                if(p.x < -20) p.x = window.innerWidth + 20;
                if(p.x > window.innerWidth + 20) p.x = -20;
                if(p.y < -20) p.y = window.innerHeight + 20;
                if(p.y > window.innerHeight + 20) p.y = -20;
                const g = ctx.createRadialGradient(p.x,p.y,0,p.x,p.y,p.r*14);
                g.addColorStop(0, `hsla(${p.hue},100%,70%,${p.alpha})`);
                g.addColorStop(1, 'rgba(0,0,0,0)');
                ctx.fillStyle = g;
                ctx.beginPath();
                ctx.arc(p.x,p.y,p.r*14,0,Math.PI*2);
                ctx.fill();
            });
            requestAnimationFrame(draw);
        }
        window.addEventListener('resize', resize, {passive:true});
        resize(); draw();
    })();
    </script>
</body>
</html>
<?php
exit;
endif;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neon Timer Lab - پنل مدیریت</title>
    <link rel="icon" href="<?php echo h($favicon); ?>">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/jalaali-js@1.2.7/dist/jalaali.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: dark; --cyan:#22d3ee; --violet:#a855f7; --magenta:#d946ef; --green:#34d399; --gold:#fbbf24; --danger:#fb7185; --text:#e6ebff; --muted:#93a0c4; --line:rgba(255,255,255,.09); }
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body { font-family: "Vazirmatn", system-ui, sans-serif; color: var(--text);
            background:
                radial-gradient(circle at 82% -8%, rgba(34,211,238,.16), transparent 28%),
                radial-gradient(circle at 8% 18%, rgba(168,85,247,.16), transparent 26%),
                radial-gradient(circle at 50% 112%, rgba(52,211,153,.12), transparent 30%),
                linear-gradient(180deg, #04060f 0%, #070b1c 55%, #03050d 100%);
            background-attachment: fixed; overflow-x: hidden; }
        #bg-canvas { position: fixed; inset: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
        .bg-grid { position: fixed; inset: 0; z-index: 1; pointer-events: none;
            background-image: linear-gradient(rgba(120,180,255,.06) 1px, transparent 1px), linear-gradient(90deg, rgba(120,180,255,.06) 1px, transparent 1px);
            background-size: 64px 64px; opacity:.5;
            mask-image: radial-gradient(circle at 50% 26%, #000 0%, transparent 80%);
            -webkit-mask-image: radial-gradient(circle at 50% 26%, #000 0%, transparent 80%);
            animation: gridMove 22s linear infinite; }
        .scanlines { position: fixed; inset: 0; z-index: 2; pointer-events: none;
            background: repeating-linear-gradient(0deg, rgba(255,255,255,.022) 0 1px, transparent 1px 4px);
            opacity:.45; mix-blend-mode: overlay; }
        .orb { position: fixed; border-radius: 9999px; filter: blur(50px); opacity:.5; mix-blend-mode: screen; z-index: 1; pointer-events: none; }
        .orb-1 { top: -9rem; right: -7rem; width: 26rem; height: 26rem; background: radial-gradient(circle, rgba(34,211,238,.4), transparent 70%); animation: driftA 16s ease-in-out infinite; }
        .orb-2 { left: -9rem; top: 14%; width: 24rem; height: 24rem; background: radial-gradient(circle, rgba(168,85,247,.36), transparent 70%); animation: driftB 18s ease-in-out infinite; }
        .orb-3 { right: 14%; bottom: -12rem; width: 30rem; height: 30rem; background: radial-gradient(circle, rgba(52,211,153,.22), transparent 72%); animation: driftC 20s ease-in-out infinite; }

        .ghost-label { letter-spacing: .4em; font-weight: 800; color: var(--cyan); text-shadow: 0 0 16px rgba(34,211,238,.5); }
        .glow-title { text-shadow: 0 0 24px rgba(34,211,238,.4); }

        .panel { position: relative; overflow: hidden; background: rgba(10,14,31,.74); backdrop-filter: blur(20px);
            border: 1px solid var(--line); border-radius: 1.5rem;
            box-shadow: 0 0 0 1px rgba(255,255,255,.02), 0 24px 70px rgba(0,0,0,.55), inset 0 1px 0 rgba(255,255,255,.05); }
        .panel::before { content:''; position:absolute; inset:0; border-radius: inherit; padding:1px;
            background: linear-gradient(135deg, rgba(34,211,238,.5), transparent 40%, transparent 60%, rgba(168,85,247,.42));
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none; }
        .panel > * { position: relative; }

        .hud-bar { position: sticky; top: .75rem; z-index: 40; }
        .brand-sub { font-size: .8rem; color: var(--muted); margin-top: .15rem; }
        .sync-pill { display:inline-flex; align-items:center; gap:.5rem; padding:.45rem .85rem; border-radius:9999px; background: rgba(52,211,153,.1); border:1px solid rgba(52,211,153,.25); font-size:.78rem; color:#bff3df; }
        #sync-dot { width:.6rem; height:.6rem; border-radius:9999px; background:var(--green); box-shadow:0 0 12px var(--green); }
        .hud-user { font-size:.85rem; color: var(--muted); display:inline-flex; align-items:center; gap:.35rem; }

        .timer-shell { position: relative; overflow: hidden; border-radius: 1.6rem; background: rgba(10,14,31,.74); backdrop-filter: blur(20px); border: 1px solid rgba(var(--accent-rgba), 0.3); box-shadow: 0 0 0 1px rgba(255,255,255,.03), 0 18px 60px rgba(0,0,0,.5), 0 0 44px rgba(var(--accent-rgba), .12); transition: transform 220ms ease, box-shadow 220ms ease; }
        .timer-shell::before { content:''; position:absolute; inset:0; border-radius: inherit; padding:1px; background: linear-gradient(135deg, rgba(var(--accent-rgba), .6), transparent 40%, transparent 60%, rgba(var(--accent-rgba), .4)); -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0); -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none; }
        .timer-shell::after { content:''; position:absolute; inset:0; background: radial-gradient(circle at top right, rgba(var(--accent-rgba), .14), transparent 34%); pointer-events:none; }
        .timer-shell.is-running { transform: translateY(-4px); box-shadow: 0 0 0 1px rgba(255,255,255,.05), 0 24px 72px rgba(0,0,0,.55), 0 0 60px rgba(var(--accent-rgba), .26); }
        .timer-shell > * { position: relative; }

        .status-chip { display:inline-flex; align-items:center; gap:.45rem; border-radius:9999px; padding:.5rem .8rem; font-size:.74rem; font-weight:700; background: rgba(var(--accent-rgba), .12); color: rgb(var(--accent-rgba)); border:1px solid rgba(var(--accent-rgba), .25); white-space: nowrap; }
        .status-chip::before { content:''; width:.5rem; height:.5rem; border-radius:9999px; background: currentColor; box-shadow: 0 0 10px currentColor; }
        .status-chip.running { animation: pulseChip 1.5s ease-in-out infinite; }

        .field { width: 100%; border-radius: .9rem; background: rgba(255,255,255,.05); border: 1px solid var(--line); padding: .85rem 1rem; color: #fff; outline: none; transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; }
        .field:focus { border-color: var(--cyan); background: rgba(255,255,255,.07); box-shadow: 0 0 0 3px rgba(34,211,238,.16), 0 0 22px rgba(34,211,238,.14); }
        input[type="date"].field { color-scheme: dark; }

        .btn { display:inline-flex; align-items:center; justify-content:center; gap:.45rem; border-radius:.9rem; padding:.85rem 1.15rem; font-weight:800; transition: transform .18s ease, filter .18s ease, opacity .18s ease; user-select:none; }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.08); }
        .btn:active { transform: translateY(0); }
        .btn:disabled { opacity:.45; cursor:not-allowed; transform:none; filter:none; }
        .btn-sm { padding:.6rem .9rem; font-size:.85rem; border-radius:.75rem; }
        .btn-primary { background: linear-gradient(135deg, #22d3ee, #34d399); color: #04121a; box-shadow: 0 12px 32px rgba(34,211,238,.3); }
        .btn-secondary { background: linear-gradient(135deg, #a855f7, #6366f1); color:#fff; box-shadow: 0 12px 32px rgba(168,85,247,.3); }
        .btn-danger { background: linear-gradient(135deg, #fb7185, #f43f5e); color:#2a0410; box-shadow: 0 12px 32px rgba(244,63,94,.28); }
        .btn-gold { background: linear-gradient(135deg, #fbbf24, #f59e0b); color:#2a1a02; box-shadow: 0 12px 32px rgba(251,191,36,.34); }

        .nav-tab { display:inline-flex; align-items:center; justify-content:center; border-radius:.85rem; padding:.7rem 1.05rem; font-size:.88rem; font-weight:800; color: rgba(226,232,240,.85); border:1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.04); transition: transform .18s ease, background .18s ease, border-color .18s ease, color .18s ease, box-shadow .18s ease; }
        .nav-tab:hover { transform: translateY(-2px); border-color: rgba(34,211,238,.3); }
        .nav-tab.active { color: #06131a; border-color: rgba(34,211,238,.6); background: linear-gradient(135deg, rgba(34,211,238,.95), rgba(52,211,153,.92)); box-shadow: 0 10px 26px rgba(34,211,238,.3); }

        .tab-panel { animation: fadeSlide .26s ease both; }
        body, main, header, section, article, div, p, span, h1, h2, h3, td, th, input, select, textarea { user-select: text; -webkit-user-select: text; }
        .countdown { text-shadow: 0 0 24px rgba(var(--accent-rgba), .55); letter-spacing:.12em; direction:ltr; unicode-bidi:plaintext; }
        .countdown.running { animation: glowPulse 1.8s ease-in-out infinite; }

        /* Birthday banner */
        .birthday-bar { position: relative; z-index: 35; border-radius: 1.25rem; overflow: hidden; border: 1px solid rgba(251,191,36,.4); background: linear-gradient(135deg, rgba(251,191,36,.16), rgba(217,70,239,.14)); box-shadow: 0 12px 40px rgba(251,191,36,.18); animation: fadeSlide .35s ease both; }
        .bday-inner { display:flex; align-items:center; gap: 1rem; padding: .9rem 1.1rem; flex-wrap: wrap; }
        .bday-emoji { font-size: 1.7rem; filter: drop-shadow(0 0 10px rgba(251,191,36,.6)); animation: bob 2.2s ease-in-out infinite; }
        .bday-items { display:flex; flex-direction: column; gap:.25rem; font-size:.92rem; color:#fff; }
        .bday-item b { color: var(--gold); }
        @keyframes bob { 0%,100%{transform:translateY(0) rotate(-4deg)} 50%{transform:translateY(-5px) rotate(4deg)} }

        /* Subscription cards */
        .sub-card { position: relative; border-radius: 1.4rem; padding: 1.15rem; background: rgba(10,14,31,.7); border: 1px solid var(--line); box-shadow: 0 16px 50px rgba(0,0,0,.4), inset 0 1px 0 rgba(255,255,255,.04); transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; overflow: hidden; }
        .sub-card::before { content:''; position:absolute; inset:0; border-radius: inherit; padding:1px; background: linear-gradient(135deg, rgba(168,85,247,.45), transparent 45%, transparent 60%, rgba(34,211,238,.35)); -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0); -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none; }
        .sub-card:hover { transform: translateY(-3px); box-shadow: 0 22px 60px rgba(0,0,0,.5), 0 0 36px rgba(168,85,247,.18); }
        .sub-card > * { position: relative; }
        .sub-card-top { display:flex; align-items:center; justify-content: space-between; gap: 1rem; }
        .sub-id-wrap { display:flex; flex-direction: column; }
        .sub-id-label { font-size:.68rem; letter-spacing:.3em; color: var(--muted); text-transform: uppercase; }
        .sub-id-num { font-size: 1.7rem; font-weight: 900; line-height: 1.1; background: linear-gradient(135deg,#22d3ee,#a855f7); -webkit-background-clip: text; background-clip: text; color: transparent; text-shadow: 0 0 24px rgba(168,85,247,.25); }
        .sub-badge { font-size:.7rem; font-weight:800; padding:.35rem .7rem; border-radius:9999px; background: rgba(52,211,153,.14); color:#86f0c4; border:1px solid rgba(52,211,153,.3); }
        .sub-info { margin-top: 1rem; display:grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
        .info-cell { background: rgba(255,255,255,.04); border:1px solid var(--line); border-radius:.85rem; padding:.6rem .75rem; }
        .info-cell span { display:block; font-size:.68rem; color: var(--muted); margin-bottom:.2rem; }
        .info-cell b { font-size:.9rem; font-weight:700; word-break: break-word; }
        .sub-balance-row { margin-top: .8rem; display:grid; grid-template-columns: 1fr 1.4fr; gap:.6rem; }
        .sub-balance-box { border-radius:.9rem; padding:.65rem .8rem; border:1px solid var(--line); }
        .sub-balance-box span { display:block; font-size:.68rem; color: var(--muted); margin-bottom:.25rem; }
        .sub-balance-box b { font-size:1.05rem; font-weight:800; color:#fff; }
        .gift-box { background: linear-gradient(135deg, rgba(251,191,36,.12), rgba(168,85,247,.08)); }
        .gp-bar { height:8px; border-radius:9999px; background: rgba(255,255,255,.1); overflow:hidden; }
        .gp-fill { height:100%; border-radius:9999px; background: linear-gradient(90deg,#fbbf24,#fb7185); box-shadow:0 0 12px rgba(251,191,36,.5); transition: width .5s ease; }
        .sub-fields { margin-top: .9rem; display:grid; grid-template-columns: 1fr 1fr; gap:.6rem; }
        .sf { display:flex; flex-direction:column; gap:.3rem; }
        .sf span { font-size:.7rem; color: var(--muted); }
        .sf-full { grid-column: 1 / -1; }
        .sub-actions { margin-top: 1rem; display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
        .sub-created { font-size:.7rem; color: var(--muted); margin-right:auto; }
        .empty-state { grid-column: 1 / -1; text-align:center; padding:2.5rem 1rem; color: var(--muted); border:1px dashed var(--line); border-radius:1.2rem; }

        /* Lottery */
        .lottery-stage { border-radius:1.5rem; border:1px solid var(--line); background: radial-gradient(circle at 50% 0%, rgba(251,191,36,.12), transparent 60%), rgba(10,14,31,.6); padding:1.5rem; min-height: 220px; display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden; }
        .lottery-reel { width:100%; display:flex; align-items:center; justify-content:center; }
        .lottery-placeholder { color: var(--muted); font-size:1rem; }
        .lottery-cell { width:100%; max-width:420px; text-align:center; border-radius:1.2rem; border:1px solid rgba(34,211,238,.3); background: rgba(255,255,255,.04); padding:1.4rem; }
        .lottery-cell.spinning { animation: reelPulse .25s ease; }
        .lc-id { font-size:2.4rem; font-weight:900; background: linear-gradient(135deg,#22d3ee,#a855f7); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .lc-phone, .lc-balance { margin-top:.4rem; color:#cbd5f5; }
        .winner-card { width:100%; max-width:460px; text-align:center; border-radius:1.4rem; border:1px solid rgba(251,191,36,.5); background: linear-gradient(135deg, rgba(251,191,36,.16), rgba(217,70,239,.12)); padding:1.6rem; box-shadow:0 0 50px rgba(251,191,36,.25); animation: winnerPop .5s cubic-bezier(.2,1.3,.4,1) both; }
        .winner-tag { font-size:.7rem; letter-spacing:.3em; color:var(--gold); font-weight:800; }
        .winner-banner { margin-top:1rem; text-align:center; font-size:1.05rem; color:#fff; border-radius:1rem; border:1px solid rgba(251,191,36,.35); background: rgba(251,191,36,.1); padding:.8rem; }
        .lottery-info { font-size:.85rem; color: var(--muted); padding:.5rem .9rem; border-radius:9999px; border:1px solid var(--line); background: rgba(255,255,255,.04); }

        @keyframes pulseChip { 0%,100%{transform:scale(1)} 50%{transform:scale(1.04)} }
        @keyframes glowPulse { 0%,100%{opacity:.92} 50%{opacity:1; filter: drop-shadow(0 0 16px rgba(var(--accent-rgba), .26))} }
        @keyframes gridMove { from{transform:translateY(0)} to{transform:translateY(64px)} }
        @keyframes driftA { 0%,100%{transform:translate3d(0,0,0) scale(1)} 50%{transform:translate3d(-24px,18px,0) scale(1.08)} }
        @keyframes driftB { 0%,100%{transform:translate3d(0,0,0) scale(1)} 50%{transform:translate3d(18px,-20px,0) scale(.94)} }
        @keyframes driftC { 0%,100%{transform:translate3d(0,0,0) scale(1)} 50%{transform:translate3d(-18px,-14px,0) scale(1.06)} }
        @keyframes fadeSlide { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes reelPulse { 0%{transform:scale(.96)} 100%{transform:scale(1)} }
        @keyframes winnerPop { 0%{transform:scale(.8); opacity:0} 100%{transform:scale(1); opacity:1} }

        /* confetti */
        .confetti-layer { position: fixed; inset:0; z-index:95; pointer-events:none; overflow:hidden; }
        .confetti-piece { position:absolute; top:-14px; width:9px; height:14px; border-radius:2px; animation: confettiFall linear forwards; }
        @keyframes confettiFall { to { transform: translateY(108vh) rotate(760deg); opacity:.95; } }
    </style>
</head>
<body class="text-white">
    <canvas id="bg-canvas"></canvas>
    <div class="bg-grid"></div>
    <div class="scanlines"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <main class="relative z-10 mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8 lg:py-6">
        <div id="birthday-bar" class="birthday-bar hidden"></div>

        <header class="hud-bar panel rounded-[1.5rem] px-4 py-4 sm:px-5 mt-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <?php render_ghost_icon('h-12 w-12 text-cyan-200'); ?>
                    <div>
                        <div class="ghost-label text-xs">NEON TIMER LAB</div>
                        <div class="brand-sub mt-1">پنل مدیریت تایمرها و اشتراک‌ها</div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <span class="sync-pill"><span id="sync-dot"></span><span id="sync-text">در حال همگام‌سازی با سرور…</span></span>
                    <span class="hud-user">👤 ورود: <?php echo h($_SESSION['admin_username'] ?? $store['settings']['username']); ?></span>
                    <a href="?logout=1" class="btn btn-danger btn-sm">خروج</a>
                </div>
            </div>

            <nav class="mt-4 flex flex-wrap gap-2">
                <button class="nav-tab active" data-tab="timers" type="button">⏱ تایمرها</button>
                <button class="nav-tab" data-tab="create" type="button">➕ ساخت اشتراک</button>
                <button class="nav-tab" data-tab="recharge" type="button">⚡ شارژ اشتراک</button>
                <button class="nav-tab" data-tab="manage" type="button">👥 مدیریت اشتراک‌ها</button>
                <button class="nav-tab" data-tab="receipts" type="button">🧾 تایید رسید <span id="receipt-badge" class="hidden" style="background:#f43f5e;color:#fff;border-radius:9999px;padding:0 .4rem;font-size:.7rem;margin-inline-start:.2rem">0</span></button>
                <button class="nav-tab" data-tab="lottery" type="button">🎡 قرعه کشی</button>
                <button class="nav-tab" data-tab="settings" type="button">⚙️ ستینگ</button>
                <button class="nav-tab" data-tab="income" type="button">💰 درآمد</button>
            </nav>
        </header>

        <div class="mt-6 space-y-6">
            <section id="panel-timers" class="tab-panel">
                <div class="grid gap-6 xl:grid-cols-3">
                    <?php foreach ($timer_defs as $timer): ?>
                        <article class="timer-shell p-5 sm:p-6" id="timer-card-<?php echo $timer['id']; ?>" style="--accent-rgba: <?php echo $timer['accent']; ?>;">
                            <div class="relative">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-white/40">Timer <?php echo (int)$timer['id']; ?></p>
                                        <div class="mt-2 flex items-center gap-3">
                                            <?php render_ghost_icon('h-9 w-9 text-cyan-200'); ?>
                                            <h2 class="text-2xl font-black text-white"><?php echo h($timer['name']); ?></h2>
                                        </div>
                                    </div>
                                    <span class="status-chip" id="timer-status-<?php echo $timer['id']; ?>">در حال بارگذاری…</span>
                                </div>

                                <div class="mt-5 rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)]">
                                    <p class="text-sm text-slate-400" id="timer-label-<?php echo $timer['id']; ?>">زمان باقی‌مانده</p>
                                    <div class="countdown mt-2 text-4xl font-black sm:text-5xl" id="timer-time-<?php echo $timer['id']; ?>">00:00:00</div>
                                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div class="rounded-2xl border border-white/8 bg-white/[0.035] px-4 py-3">
                                            <p class="text-xs text-slate-400" id="timer-amount-label-<?php echo $timer['id']; ?>">موجودی فعلی</p>
                                            <p class="mt-1 text-lg font-bold" id="timer-amount-value-<?php echo $timer['id']; ?>">— تومان</p>
                                        </div>
                                        <div class="rounded-2xl border border-white/8 bg-white/[0.035] px-4 py-3">
                                            <p class="text-xs text-slate-400">قیمت هر دقیقه</p>
                                            <p class="mt-1 text-lg font-bold" id="timer-rate-value-<?php echo $timer['id']; ?>">— تومان</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-4">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <label class="grid gap-2">
                                            <span class="text-sm font-medium text-slate-300">نوع تایمر</span>
                                            <select class="field" id="timer-mode-<?php echo $timer['id']; ?>">
                                                <option value="wallet">بدون اشتراک - موجودی + قیمت هر دقیقه</option>
                                                <option value="counter">بدون اشتراک - فقط قیمت هر دقیقه</option>
                                            </select>
                                        </label>
                                        <label class="grid gap-2" id="timer-source-wrap-<?php echo $timer['id']; ?>">
                                            <span class="text-sm font-medium text-slate-300">حالت اشتراک</span>
                                            <select class="field" id="timer-source-<?php echo $timer['id']; ?>">
                                                <option value="none">بدون اشتراک</option>
                                                <option value="subscription">اشتراک</option>
                                            </select>
                                        </label>
                                    </div>

                                    <label class="grid gap-2" id="timer-balance-wrap-<?php echo $timer['id']; ?>">
                                        <span class="text-sm font-medium text-slate-300">موجودی تایمر به تومان</span>
                                        <input class="field" id="timer-balance-<?php echo $timer['id']; ?>" inputmode="numeric" dir="ltr" placeholder="مثلا 450000" value="<?php echo (int)$timer['default_balance']; ?>">
                                    </label>

                                    <label class="grid gap-2 hidden" id="timer-subscription-wrap-<?php echo $timer['id']; ?>">
                                        <span class="text-sm font-medium text-slate-300">شماره اشتراک</span>
                                        <input class="field" id="timer-subscription-<?php echo $timer['id']; ?>" inputmode="text" dir="ltr" placeholder="مثلا 1001">
                                    </label>

                                    <label class="grid gap-2">
                                        <span class="text-sm font-medium text-slate-300">قیمت هر دقیقه</span>
                                        <input class="field" id="timer-rate-<?php echo $timer['id']; ?>" inputmode="numeric" dir="ltr" placeholder="مثلا 15000" value="<?php echo (int)$timer['default_rate']; ?>">
                                    </label>
                                </div>

                                <p class="mt-4 text-sm leading-7 text-slate-300/80" id="timer-message-<?php echo $timer['id']; ?>">در حال دریافت وضعیت از سرور…</p>

                                <div class="mt-5 flex flex-wrap gap-3">
                                    <button class="btn btn-primary" id="timer-start-<?php echo $timer['id']; ?>" type="button">شروع</button>
                                    <button class="btn btn-danger" id="timer-stop-<?php echo $timer['id']; ?>" type="button">توقف</button>
                                    <button class="btn btn-secondary" id="timer-update-<?php echo $timer['id']; ?>" type="button">اپدیت</button>
                                    <button class="btn btn-danger" id="timer-reset-<?php echo $timer['id']; ?>" type="button">ریست</button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section id="panel-create" class="tab-panel hidden">
                <div class="panel rounded-[1.75rem] p-5 sm:p-6">
                    <div class="relative">
                        <h2 class="text-2xl font-black glow-title">➕ ساخت اشتراک</h2>
                        <p class="mt-1 text-sm text-slate-400">برای اشتراک جدید شماره دلخواه، شماره تماس، تاریخ تولد، کد دعوت معرف و موجودی اولیه را وارد کن.</p>

                        <form id="create-subscription-form" class="mt-6 grid gap-4 md:grid-cols-2">
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">شماره اشتراک دلخواه</span>
                                <input class="field" id="create-subscription-id" inputmode="text" dir="ltr" placeholder="مثلا 1001">
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">شماره تماس</span>
                                <input class="field" id="create-phone" inputmode="tel" dir="ltr" placeholder="مثلا 09121234567">
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">تاریخ تولد (شمسی)</span>
                                <div id="create-birth-date" class="jalali-host"></div>
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">کد دعوت معرف (4 رقمی)</span>
                                <input class="field" id="create-invite-code" inputmode="numeric" dir="ltr" placeholder="مثلا 1234" maxlength="4">
                            </label>
                            <label class="grid gap-2 md:col-span-2">
                                <span class="text-sm font-medium text-slate-300">موجودی اولیه اشتراک به تومان</span>
                                <input class="field" id="create-initial-balance" inputmode="numeric" dir="ltr" placeholder="مثلا 250000" value="0">
                            </label>
                            <label class="grid gap-2 md:col-span-2">
                                <span class="text-sm font-medium text-slate-300">یادداشت اختیاری</span>
                                <input class="field" id="create-note" inputmode="text" dir="rtl" placeholder="مثلا پلن ویژه">
                            </label>
                            <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                                <button type="submit" class="btn btn-primary">ساخت اشتراک</button>
                                <span class="text-sm text-slate-400">کد دعوت اختصاصی و هدیه شارژ به‌صورت خودکار محاسبه می‌شود.</span>
                            </div>
                        </form>

                        <div id="create-result" class="mt-5 rounded-2xl border border-white/8 bg-white/[0.04] px-4 py-4 text-sm text-slate-200">هنوز اشتراکی ساخته نشده است.</div>
                    </div>
                </div>
            </section>

            <section id="panel-recharge" class="tab-panel hidden">
                <div class="panel rounded-[1.75rem] p-5 sm:p-6">
                    <div class="relative">
                        <h2 class="text-2xl font-black glow-title">⚡ شارژ اشتراک</h2>
                        <p class="mt-1 text-sm text-slate-400">شماره اشتراک و مبلغ شارژ را وارد کن تا موجودی افزایش پیدا کند. هر <b id="recharge-gift-step">750000</b> تومان، <b id="recharge-gift-reward">75000</b> تومان هدیه خودکار اضافه می‌شود.</p>

                        <form id="recharge-subscription-form" class="mt-6 grid gap-4 md:grid-cols-2">
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">شماره اشتراک</span>
                                <input class="field" id="recharge-subscription-id" inputmode="text" dir="ltr" placeholder="مثلا 1001">
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">مبلغ شارژ به تومان</span>
                                <input class="field" id="recharge-amount" inputmode="numeric" dir="ltr" placeholder="مثلا 70000" value="0">
                            </label>
                            <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                                <button type="submit" class="btn btn-secondary">شارژ اشتراک</button>
                                <span class="text-sm text-slate-400">پاداش دعوت و هدیه شارژ خودکار محاسبه می‌شود.</span>
                            </div>
                        </form>

                        <div id="recharge-result" class="mt-5 rounded-2xl border border-white/8 bg-white/[0.04] px-4 py-4 text-sm text-slate-200">هنوز شارژی ثبت نشده است.</div>
                    </div>
                </div>
            </section>

            <section id="panel-manage" class="tab-panel hidden">
                <div class="panel rounded-[1.75rem] p-5 sm:p-6">
                    <div class="relative">
                        <h2 class="text-2xl font-black glow-title">👥 مدیریت اشتراک‌ها</h2>
                        <p class="mt-1 text-sm text-slate-400">همه اشتراک‌ها را به‌صورت حرفه‌ای ویرایش یا حذف کن. نوار پیشرفت میزان نزدیکی به هدیه بعدی را نشان می‌دهد.</p>

                        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3" id="subscription-list"></div>
                    </div>
                </div>
            </section>

            <section id="panel-receipts" class="tab-panel hidden">
                <div class="panel rounded-[1.75rem] p-5 sm:p-6">
                    <div class="relative">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div>
                                <h2 class="text-2xl font-black glow-title">🧾 تایید رسید</h2>
                                <p class="mt-1 text-sm text-slate-400">رسیدهای پرداختی کاربران اینجا نمایش داده می‌شود. با تأیید، اشتراک شارژ می‌شود.</p>
                            </div>
                            <button class="btn btn-secondary btn-sm" id="receipt-refresh" type="button">🔄 بروزرسانی</button>
                        </div>
                        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3" id="receipt-list"></div>
                    </div>
                </div>
            </section>

            <section id="panel-lottery" class="tab-panel hidden">
                <div class="panel rounded-[1.75rem] p-5 sm:p-7">
                    <div class="relative">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div>
                                <h2 class="text-2xl font-black glow-title">🎡 قرعه کشی</h2>
                                <p class="mt-1 text-sm text-slate-400">با زدن دکمه شروع، یکی از اشتراک‌ها به‌صورت کاملاً تصادفی و با شانس برابر انتخاب می‌شود.</p>
                            </div>
                            <div class="lottery-info" id="lottery-info">— اشتراک ثبت شده</div>
                        </div>

                        <div class="lottery-stage mt-6">
                            <div class="lottery-reel" id="lottery-reel">
                                <div class="lottery-placeholder">برای شروع قرعه کشی دکمه زیر را بزن 🎲</div>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <button class="btn btn-gold" id="lottery-start" type="button">🎲 شروع قرعه کشی</button>
                            <span class="text-sm text-slate-400">نتیجه کاملاً رندوم و با شانس برابر برای همه اشتراک‌هاست.</span>
                        </div>

                        <div id="lottery-result" class="mt-5"></div>
                    </div>
                </div>
            </section>

            <section id="panel-settings" class="tab-panel hidden">
                <div class="panel rounded-[1.75rem] p-5 sm:p-6">
                    <div class="relative">
                        <h2 class="text-2xl font-black glow-title">⚙️ ستینگ</h2>
                        <p class="mt-1 text-sm text-slate-400">یوزرنیم، پسورد، پاداش دعوت و تنظیمات هدیه شارژ را از اینجا تغییر بده.</p>

                        <form id="settings-form" class="mt-6 grid gap-4 md:grid-cols-2">
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">یوزرنیم جدید</span>
                                <input class="field" id="settings-username" inputmode="text" dir="ltr" autocomplete="username">
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">پسورد جدید</span>
                                <input class="field" id="settings-password" type="password" inputmode="text" dir="ltr" autocomplete="new-password" placeholder="اگر خالی بماند تغییر نمی‌کند">
                            </label>

                            <div class="md:col-span-2 my-1 rounded-2xl border border-amber-300/20 bg-amber-400/[0.05] px-4 py-3 text-sm text-amber-200/90">🎁 تنظیمات هدیه شارژ</div>

                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">مقدار هدیه (به ازای هر آستانه)</span>
                                <input class="field" id="settings-gift-reward" inputmode="numeric" dir="ltr" value="75000">
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">آستانه هدیه (مبلغ شارژ لازم)</span>
                                <input class="field" id="settings-gift-step" inputmode="numeric" dir="ltr" value="750000">
                            </label>

                            <div class="md:col-span-2 my-1 rounded-2xl border border-violet-300/20 bg-violet-400/[0.05] px-4 py-3 text-sm text-violet-200/90">📅 پاداش دعوت هفتگی</div>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">مقدار پاداش هفتگی</span>
                                <input class="field" id="settings-week-reward" inputmode="numeric" dir="ltr" value="50000">
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">آستانه هفتگی (مجموع شارژ دعوت‌شده‌ها)</span>
                                <input class="field" id="settings-week-step" inputmode="numeric" dir="ltr" value="1000000">
                            </label>

                            <div class="md:col-span-2 my-1 rounded-2xl border border-fuchsia-300/20 bg-fuchsia-400/[0.05] px-4 py-3 text-sm text-fuchsia-200/90">🗓 پاداش دعوت ماهانه</div>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">مقدار پاداش ماهانه</span>
                                <input class="field" id="settings-month-reward" inputmode="numeric" dir="ltr" value="300000">
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">آستانه ماهانه (مجموع شارژ دعوت‌شده‌ها)</span>
                                <input class="field" id="settings-month-step" inputmode="numeric" dir="ltr" value="5000000">
                            </label>

                            <div class="md:col-span-2 my-1 rounded-2xl border border-cyan-300/20 bg-cyan-400/[0.05] px-4 py-3 text-sm text-cyan-200/90">🤝 تنظیمات پاداش دعوت</div>

                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">پاداش دعوت به ازای هر آستانه</span>
                                <input class="field" id="settings-referral-reward" inputmode="numeric" dir="ltr" value="15000">
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">آستانه شارژ برای پاداش دعوت</span>
                                <input class="field" id="settings-referral-step" inputmode="numeric" dir="ltr" value="100000">
                            </label>

                            <div class="md:col-span-2 my-1 rounded-2xl border border-amber-300/20 bg-amber-400/[0.05] px-4 py-3 text-sm text-amber-200/90">💳 اطلاعات کارت (نمایش داده شده به کاربران در صفحه شارژ)</div>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">شماره کارت</span>
                                <input class="field" id="settings-card-number" inputmode="text" dir="ltr" placeholder="5859471028871667">
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-300">اسم صاحب کارت</span>
                                <input class="field" id="settings-card-name" inputmode="text" dir="rtl" placeholder="نام صاحب کارت">
                            </label>
                            <label class="grid gap-2 md:col-span-2">
                                <span class="text-sm font-medium text-slate-300">توضیحات پرداخت</span>
                                <input class="field" id="settings-card-desc" inputmode="text" dir="rtl" placeholder="مبلغ را واریز کنید و رسید را ارسال کنید.">
                            </label>

                            <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                                <button type="submit" class="btn btn-primary">ذخیره تنظیمات</button>
                                <a href="?logout=1" class="btn btn-danger">خروج از همه دستگاه‌ها</a>
                                <span class="text-sm text-slate-400">رمز عبور خالی بماند، همان رمز فعلی حفظ می‌شود.</span>
                            </div>
                        </form>

                        <div id="settings-result" class="mt-5 rounded-2xl border border-white/8 bg-white/[0.04] px-4 py-4 text-sm text-slate-200">تنظیمات فعلی در حال بارگذاری است.</div>
                    </div>
                </div>
            </section>

            <section id="panel-income" class="tab-panel hidden">
                <div class="panel rounded-[1.75rem] p-5 sm:p-6">
                    <div class="relative">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h2 class="text-2xl font-black glow-title">💰 درآمد امروز</h2>
                                <p class="mt-1 text-sm text-slate-400">این مقدار هر شب بعد از نیمه‌شب ریست می‌شود.</p>
                            </div>
                            <div class="rounded-[1.5rem] border border-white/10 bg-white/[0.04] px-4 py-3">
                                <div class="text-xs text-slate-400">درآمد امروز</div>
                                <div id="income-today" class="mt-1 text-3xl font-black">0 تومان</div>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3 lg:grid-cols-2" id="income-transactions"></div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
    const TIMER_DEFS = <?php echo json_encode($timer_defs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const app = { state: null, timers: {}, subscriptions: [], settings: null, income: null, serverTimeOffset: 0, activeTab: 'timers' };
    const refs = { timers: {} };
    const numberFormatter = new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 0 });

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function moneyText(value) {
        return `${numberFormatter.format(Math.max(0, Math.round(Number(value) || 0)))} تومان`;
    }

    function formatDate(d) {
        if (!d) return '—';
        return String(d).replace(/-/g, '/').replace(/[0-9]/g, m => '۰۱۲۳۴۵۶۷۸۹'[m]);
    }

    function pad2(n) {
        return String(Math.max(0, Math.floor(Number(n) || 0))).padStart(2, '0');
    }

    function formatDuration(seconds) {
        seconds = Math.max(0, Math.floor(Number(seconds) || 0));
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${pad2(h)}:${pad2(m)}:${pad2(s)}`;
    }

    function parseMoney(v) {
        v = String(v ?? '');
        v = v.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
             .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
        v = v.replace(/[^0-9.\-]/g, '');
        const n = Number(v);
        return Number.isFinite(n) && n > 0 ? n : 0;
    }

    const JALALI_MONTHS = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
    function toFa(n) { return numberFormatter.format(Number(n) || 0); }
    function jalaliMonthLengthClient(jy, jm) {
        if (typeof jalaali !== 'undefined' && typeof jalaali.jalaaliMonthLength === 'function') {
            try { return jalaali.jalaaliMonthLength(jy, jm); } catch (e) {}
        }
        if (jm <= 6) return 31;
        if (jm <= 11) return 30;
        return 29;
    }
    function buildJalaliSelects(host, currentValue) {
        let jy = '', jm = '', jd = '';
        if (currentValue && /^\d{4}-\d{2}-\d{2}$/.test(currentValue)) {
            const p = currentValue.split('-');
            jy = p[0]; jm = String(parseInt(p[1], 10)); jd = String(parseInt(p[2], 10));
        }
        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.className = 'jalali-value';
        function sync() {
            const yy = yearSel.value, mm = monthSel.value, dd = daySel.value;
            hidden.value = (yy && mm && dd) ? `${yy}-${String(mm).padStart(2,'0')}-${String(dd).padStart(2,'0')}` : '';
        }
        const yearSel = document.createElement('select');
        yearSel.className = 'field text-center';
        const yDef = document.createElement('option'); yDef.value=''; yDef.textContent='سال'; yearSel.appendChild(yDef);
        for (let y = 1410; y >= 1300; y--) {
            const o = document.createElement('option'); o.value = String(y); o.textContent = toFa(y); if (String(y) === jy) o.selected = true; yearSel.appendChild(o);
        }
        const monthSel = document.createElement('select');
        monthSel.className = 'field text-center';
        const mDef = document.createElement('option'); mDef.value=''; mDef.textContent='ماه'; monthSel.appendChild(mDef);
        JALALI_MONTHS.forEach((name, idx) => {
            const o = document.createElement('option'); o.value = String(idx + 1); o.textContent = name; if (String(idx + 1) === jm) o.selected = true; monthSel.appendChild(o);
        });
        const daySel = document.createElement('select');
        daySel.className = 'field text-center';
        function rebuildDays() {
            const yy = parseInt(yearSel.value, 10) || 0;
            const mm = parseInt(monthSel.value, 10) || 0;
            const max = (yy && mm) ? jalaliMonthLengthClient(yy, mm) : 31;
            const prev = daySel.value;
            daySel.innerHTML = '';
            const o = document.createElement('option'); o.value=''; o.textContent='روز'; daySel.appendChild(o);
            for (let d = 1; d <= max; d++) {
                const op = document.createElement('option'); op.value = String(d); op.textContent = toFa(d); if (String(d) === jd) op.selected = true; daySel.appendChild(op);
            }
            if (prev && parseInt(prev, 10) <= max) daySel.value = prev;
            sync();
        }
        yearSel.addEventListener('change', rebuildDays);
        monthSel.addEventListener('change', rebuildDays);
        daySel.addEventListener('change', sync);
        rebuildDays();
        host.innerHTML = '';
        host.appendChild(hidden);
        const wrap = document.createElement('div');
        wrap.style.cssText = 'display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem';
        wrap.appendChild(yearSel); wrap.appendChild(monthSel); wrap.appendChild(daySel);
        host.appendChild(wrap);
        host.getValue = () => hidden.value;
        return host;
    }

    async function api(action, body = null) {
        const opts = body ? {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        } : {};
        const res = await fetch(`?api=${encodeURIComponent(action)}`, opts);
        if (res.status === 401) {
            window.location.reload();
            throw new Error('unauthorized');
        }
        const data = await res.json();
        if (!res.ok) {
            const err = new Error(data.error || 'request_failed');
            err.data = data;
            throw err;
        }
        return data;
    }

    function fireConfettiBurst() {
        const colors = ['#22d3ee', '#a855f7', '#fbbf24', '#34d399', '#fb7185', '#d946ef'];
        const layer = document.createElement('div');
        layer.className = 'confetti-layer';
        for (let i = 0; i < 70; i++) {
            const c = document.createElement('span');
            c.className = 'confetti-piece';
            c.style.left = Math.random() * 100 + '%';
            c.style.background = colors[Math.floor(Math.random() * colors.length)];
            c.style.animationDelay = (Math.random() * 0.5) + 's';
            c.style.animationDuration = (1.6 + Math.random() * 1.5) + 's';
            c.style.transform = `rotate(${Math.random() * 360}deg)`;
            layer.appendChild(c);
        }
        document.body.appendChild(layer);
        setTimeout(() => layer.remove(), 3600);
    }

    function bindRefs() {
        TIMER_DEFS.forEach(t => {
            refs.timers[t.id] = {
                card: document.getElementById(`timer-card-${t.id}`),
                status: document.getElementById(`timer-status-${t.id}`),
                label: document.getElementById(`timer-label-${t.id}`),
                time: document.getElementById(`timer-time-${t.id}`),
                amountLabel: document.getElementById(`timer-amount-label-${t.id}`),
                amountValue: document.getElementById(`timer-amount-value-${t.id}`),
                rateValue: document.getElementById(`timer-rate-value-${t.id}`),
                mode: document.getElementById(`timer-mode-${t.id}`),
                source: document.getElementById(`timer-source-${t.id}`),
                sourceWrap: document.getElementById(`timer-source-wrap-${t.id}`),
                balanceWrap: document.getElementById(`timer-balance-wrap-${t.id}`),
                subscriptionWrap: document.getElementById(`timer-subscription-wrap-${t.id}`),
                balance: document.getElementById(`timer-balance-${t.id}`),
                subscription: document.getElementById(`timer-subscription-${t.id}`),
                rate: document.getElementById(`timer-rate-${t.id}`),
                message: document.getElementById(`timer-message-${t.id}`),
                start: document.getElementById(`timer-start-${t.id}`),
                stop: document.getElementById(`timer-stop-${t.id}`),
                update: document.getElementById(`timer-update-${t.id}`),
                reset: document.getElementById(`timer-reset-${t.id}`),
                _userEdited: { balance: false, rate: false, subscription: false, mode: false, source: false },
            };
        });

        refs.tabs = Array.from(document.querySelectorAll('[data-tab]'));
        refs.panels = {
            timers: document.getElementById('panel-timers'),
            create: document.getElementById('panel-create'),
            recharge: document.getElementById('panel-recharge'),
            manage: document.getElementById('panel-manage'),
            receipts: document.getElementById('panel-receipts'),
            lottery: document.getElementById('panel-lottery'),
            settings: document.getElementById('panel-settings'),
            income: document.getElementById('panel-income'),
        };
        refs.receiptBadge = document.getElementById('receipt-badge');
        refs.receiptList = document.getElementById('receipt-list');
        refs.receiptRefresh = document.getElementById('receipt-refresh');
        refs.syncDot = document.getElementById('sync-dot');
        refs.syncText = document.getElementById('sync-text');
        refs.birthdayBar = document.getElementById('birthday-bar');

        refs.createForm = document.getElementById('create-subscription-form');
        refs.createId = document.getElementById('create-subscription-id');
        refs.createPhone = document.getElementById('create-phone');
        refs.createBirthDate = document.getElementById('create-birth-date');
        refs.createInvite = document.getElementById('create-invite-code');
        refs.createBalance = document.getElementById('create-initial-balance');
        refs.createNote = document.getElementById('create-note');
        refs.createResult = document.getElementById('create-result');

        refs.rechargeForm = document.getElementById('recharge-subscription-form');
        refs.rechargeId = document.getElementById('recharge-subscription-id');
        refs.rechargeAmount = document.getElementById('recharge-amount');
        refs.rechargeResult = document.getElementById('recharge-result');
        refs.rechargeGiftStep = document.getElementById('recharge-gift-step');
        refs.rechargeGiftReward = document.getElementById('recharge-gift-reward');

        refs.manageBody = document.getElementById('subscription-list');

        refs.lotteryReel = document.getElementById('lottery-reel');
        refs.lotteryStart = document.getElementById('lottery-start');
        refs.lotteryResult = document.getElementById('lottery-result');
        refs.lotteryInfo = document.getElementById('lottery-info');

        refs.settingsForm = document.getElementById('settings-form');
        refs.settingsUsername = document.getElementById('settings-username');
        refs.settingsPassword = document.getElementById('settings-password');
        refs.settingsReward = document.getElementById('settings-referral-reward');
        refs.settingsStep = document.getElementById('settings-referral-step');
        refs.settingsGiftReward = document.getElementById('settings-gift-reward');
        refs.settingsGiftStep = document.getElementById('settings-gift-step');
        refs.settingsWeekReward = document.getElementById('settings-week-reward');
        refs.settingsWeekStep = document.getElementById('settings-week-step');
        refs.settingsMonthReward = document.getElementById('settings-month-reward');
        refs.settingsMonthStep = document.getElementById('settings-month-step');
        refs.settingsCardNumber = document.getElementById('settings-card-number');
        refs.settingsCardName = document.getElementById('settings-card-name');
        refs.settingsCardDesc = document.getElementById('settings-card-desc');
        refs.settingsResult = document.getElementById('settings-result');
        refs.incomeToday = document.getElementById('income-today');
        refs.incomeTransactions = document.getElementById('income-transactions');
    }

    function setTab(tab) {
        app.activeTab = tab;
        Object.entries(refs.panels).forEach(([key, el]) => {
            if (!el) return;
            el.classList.toggle('hidden', key !== tab);
        });
        refs.tabs.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });
    }

    function normalizeTimer(timer, forceSync = false) {
        app.timers[timer.id] = Object.assign(app.timers[timer.id] || {}, timer);
        app.timers[timer.id]._server_seen_at = Date.now();
        if (forceSync && refs.timers[timer.id]) {
            refs.timers[timer.id]._userEdited = { balance: false, rate: false, subscription: false };
        }
        renderTimer(timer.id, forceSync);
    }

    function syncTimerFields(id) {
        const t = app.timers[id];
        const el = refs.timers[id];
        if (!t || !el) return;

        const mode = el.mode.value || t.mode || 'wallet';
        const source = el.source.value || t.source_type || 'none';

        el.sourceWrap.classList.toggle('hidden', mode === 'counter');
        el.balanceWrap.classList.toggle('hidden', mode === 'counter' || source === 'subscription');
        el.subscriptionWrap.classList.toggle('hidden', mode === 'counter' || source !== 'subscription');
    }

    function renderTimer(id, forceSync = false) {
        const t = app.timers[id];
        const el = refs.timers[id];
        if (!t || !el) return;

        const edit = el._userEdited || {};

        if (forceSync || (!edit.mode && document.activeElement !== el.mode)) {
            el.mode.value = t.mode || 'wallet';
        }
        if (forceSync || (!edit.source && document.activeElement !== el.source)) {
            el.source.value = t.source_type || 'none';
        }

        if (forceSync || (!edit.balance && document.activeElement !== el.balance && t.source_type !== 'subscription')) {
            el.balance.value = t.balance > 0 ? Math.round(t.balance) : '';
        }
        if (forceSync || (!edit.subscription && document.activeElement !== el.subscription)) {
            el.subscription.value = t.source_subscription_id || '';
        }
        if (forceSync || (!edit.rate && document.activeElement !== el.rate)) {
            el.rate.value = t.rate > 0 ? Math.round(t.rate) : '';
        }

        syncTimerFields(id);

        const rate = Number(t.rate) || 0;
        let balance = Number(t.balance) || 0;
        let elapsed = Number(t.elapsed_seconds) || 0;

        if (t.running && t._server_seen_at) {
            const delta = (Date.now() - t._server_seen_at) / 1000;
            if (t.mode === 'counter') {
                elapsed += delta;
            } else {
                balance = Math.max(0, balance - (delta * rate / 60));
            }
        }

        if (t.mode === 'counter') {
            const spent = elapsed * rate / 60;
            if (el.label.textContent !== 'زمان سپری‌شده') el.label.textContent = 'زمان سپری‌شده';
            el.time.textContent = formatDuration(elapsed);
            if (el.amountLabel.textContent !== 'هزینه فعلی') el.amountLabel.textContent = 'هزینه فعلی';
            el.amountValue.textContent = moneyText(spent);
            el.rateValue.textContent = moneyText(rate);
        } else {
            const remaining = rate > 0 ? (balance / rate) * 60 : 0;
            if (el.label.textContent !== 'زمان باقی‌مانده') el.label.textContent = 'زمان باقی‌مانده';
            el.time.textContent = formatDuration(remaining);
            const newAmtLabel = t.source_type === 'subscription' ? 'موجودی اشتراک' : 'موجودی فعلی';
            if (el.amountLabel.textContent !== newAmtLabel) el.amountLabel.textContent = newAmtLabel;
            el.amountValue.textContent = moneyText(balance);
            el.rateValue.textContent = moneyText(rate);
        }

        const newMsg = t._message || (t.running ? 'برداشت از موجودی به صورت زنده انجام می‌شود.' : 'مقادیر را تنظیم کن و سپس شروع یا اپدیت را بزن.');
        if (el.message.textContent !== newMsg) el.message.textContent = newMsg;

        const selectedSource = el.source.value || t.source_type || 'none';
        const subscriptionId = String(el.subscription.value || '').trim();

        if (t.running) {
            el.card.classList.add('is-running');
            el.status.classList.add('running');
            if (el.status.textContent !== 'در حال اجرا') el.status.textContent = 'در حال اجرا';
            el.time.classList.add('running');
            el.start.disabled = true;
            el.stop.disabled = false;
        } else {
            el.card.classList.remove('is-running');
            el.status.classList.remove('running');
            el.time.classList.remove('running');
            const valid = t.mode === 'counter'
                ? rate > 0
                : (selectedSource === 'subscription' ? (rate > 0 && subscriptionId !== '') : (balance > 0 && rate > 0));
            const newStatus = valid ? (t.mode === 'counter' ? 'آماده شمارش' : (selectedSource === 'subscription' ? 'آماده اشتراک' : 'آماده')) : 'نیاز به تنظیم';
            if (el.status.textContent !== newStatus) el.status.textContent = newStatus;
            el.start.disabled = !valid;
            el.stop.disabled = true;
        }
    }

    function renderTimers() {
        Object.keys(app.timers).forEach(id => renderTimer(id));
    }

    function giftProgressInfo(total) {
        const step = Math.max(1, Number(app.settings?.gift_step) || 750000);
        const within = (Number(total) || 0) % step;
        return { pct: Math.min(100, (within / step) * 100), remaining: Math.max(0, step - within), step };
    }

    function manageCardHtml(sub) {
        const created = new Date((sub.created_at || 0) * 1000).toLocaleString('fa-IR');
        const pi = giftProgressInfo(sub.recharged_total);
        return `
        <article class="sub-card" data-sub-id="${escapeHtml(sub.id)}">
            <div class="sub-card-top">
                <div class="sub-id-wrap">
                    <span class="sub-id-label">اشتراک</span>
                    <span class="sub-id-num">${escapeHtml(sub.id)}</span>
                </div>
                <span class="sub-badge">فعال</span>
            </div>
            <div class="sub-info">
                <div class="info-cell"><span>شماره تماس</span><b class="sub-phone-display">${escapeHtml(sub.phone || '—')}</b></div>
                <div class="info-cell"><span>تاریخ تولد</span><b class="sub-birth-display">${formatDate(sub.birth_date)}</b></div>
                <div class="info-cell"><span>تعداد دعوت‌شده‌ها</span><b class="sub-ref-count">${escapeHtml(String(sub.referral_count ?? 0))} نفر</b></div>
                <div class="info-cell"><span>معرف این اشتراک</span><b class="sub-referred">${escapeHtml(sub.referred_by_id || '—')}</b></div>
                <div class="info-cell"><span>شارژ دعوت‌شده‌ها (هفته)</span><b class="sub-week-total">${moneyText(sub.ref_week_total || 0)}</b></div>
                <div class="info-cell"><span>شارژ دعوت‌شده‌ها (ماه)</span><b class="sub-month-total">${moneyText(sub.ref_month_total || 0)}</b></div>
                <div class="info-cell"><span>موجودی فعلی</span><b class="stat-balance">${moneyText(sub.balance || 0)}</b></div>
                <div class="info-cell"><span>کل شارژ خودش</span><b class="sub-recharged-total">${moneyText(sub.recharged_total || 0)}</b></div>
            </div>
            <div class="sub-balance-box gift-box" style="margin-top:.8rem;">
                <span class="gp-remaining">${moneyText(pi.remaining)} تا هدیه بعدی (${pi.pct.toFixed(0)}%)</span>
                <div class="gp-bar"><div class="gp-fill" style="width:${pi.pct.toFixed(1)}%"></div></div>
            </div>
            <div class="sub-fields">
                <label class="sf"><span>شماره تماس</span><input class="field sub-phone" dir="ltr" inputmode="tel" value="${escapeHtml(sub.phone || '')}" placeholder="0912..."></label>
                <label class="sf"><span>کد دعوت</span><input class="field sub-invite" dir="ltr" maxlength="4" inputmode="numeric" value="${escapeHtml(sub.invite_code || '')}"></label>
                <label class="sf"><span>موجودی</span><input class="field sub-balance" dir="ltr" inputmode="numeric" value="${Math.round(Number(sub.balance) || 0)}"></label>
                <label class="sf"><span>یادداشت</span><input class="field sub-note" dir="rtl" value="${escapeHtml(sub.note || '')}"></label>
                <label class="sf sf-full"><span>تاریخ تولد (شمسی)</span><div class="jalali-host sub-birth-host" data-birth="${escapeHtml(sub.birth_date || '')}"></div></label>
            </div>
            <div class="sub-actions">
                <button type="button" class="btn btn-secondary btn-sm" data-action="save-subscription">ذخیره تغییرات</button>
                <button type="button" class="btn btn-danger btn-sm" data-action="delete-subscription">حذف اشتراک</button>
                <span class="sub-created">ساخته شده: ${created}</span>
            </div>
        </article>`;
    }

    function findCard(subId) {
        let found = null;
        refs.manageBody.querySelectorAll('[data-sub-id]').forEach(c => {
            if (c.dataset.subId === subId) found = c;
        });
        return found;
    }

    function updateManageCard(card, sub, isFocused) {
        const isEdited = app._editingSubscriptions[sub.id];
        const balEl = card.querySelector('.stat-balance');
        if (balEl) balEl.textContent = moneyText(sub.balance || 0);
        const totEl = card.querySelector('.sub-recharged-total');
        if (totEl) totEl.textContent = moneyText(sub.recharged_total || 0);
        const refEl = card.querySelector('.sub-referred');
        if (refEl) refEl.textContent = sub.referred_by_id || '—';
        const bdEl = card.querySelector('.sub-birth-display');
        if (bdEl) bdEl.textContent = formatDate(sub.birth_date);
        const phEl = card.querySelector('.sub-phone-display');
        if (phEl) phEl.textContent = sub.phone || '—';
        const rcEl = card.querySelector('.sub-ref-count');
        if (rcEl) rcEl.textContent = String(sub.referral_count ?? 0) + ' نفر';
        const wkEl = card.querySelector('.sub-week-total');
        if (wkEl) wkEl.textContent = moneyText(sub.ref_week_total || 0);
        const moEl = card.querySelector('.sub-month-total');
        if (moEl) moEl.textContent = moneyText(sub.ref_month_total || 0);
        const pi = giftProgressInfo(sub.recharged_total);
        const fill = card.querySelector('.gp-fill');
        if (fill) fill.style.width = pi.pct.toFixed(1) + '%';
        const rem = card.querySelector('.gp-remaining');
        if (rem) rem.textContent = moneyText(pi.remaining) + ' تا هدیه بعدی (' + pi.pct.toFixed(0) + '%)';
        if (!isFocused && !isEdited) {
            const invite = card.querySelector('.sub-invite');
            const bal = card.querySelector('.sub-balance');
            const note = card.querySelector('.sub-note');
            const phone = card.querySelector('.sub-phone');
            if (invite) invite.value = sub.invite_code || '';
            if (bal) bal.value = Math.round(Number(sub.balance) || 0);
            if (note) note.value = sub.note || '';
            if (phone) phone.value = sub.phone || '';
        }
    }

    function renderSubscriptions(forceFullRender = false) {
        const list = [...(app.subscriptions || [])].sort((a, b) => (b.created_at || 0) - (a.created_at || 0));
        if (!list.length) {
            refs.manageBody.innerHTML = '<div class="empty-state">هنوز اشتراکی ساخته نشده است.</div>';
            return;
        }
        const focusedEl = document.activeElement;
        const focusedCard = focusedEl ? focusedEl.closest('[data-sub-id]') : null;
        const focusedSubId = focusedCard ? focusedCard.dataset.subId : null;

        const existingCards = refs.manageBody.querySelectorAll('[data-sub-id]');
        const needsRebuild = forceFullRender || existingCards.length !== list.length;

        if (!app._editingSubscriptions) app._editingSubscriptions = {};
        if (!app._subscriptionTimestamps) app._subscriptionTimestamps = {};

        if (needsRebuild) {
            refs.manageBody.innerHTML = list.map(sub => manageCardHtml(sub)).join('');
            refs.manageBody.querySelectorAll('[data-sub-id]').forEach(card => {
                const subId = card.dataset.subId;
                card.querySelectorAll('input').forEach(input => {
                    input.addEventListener('input', () => { app._editingSubscriptions[subId] = true; });
                    input.addEventListener('focus', () => { app._editingSubscriptions[subId] = true; });
                });
                const host = card.querySelector('.sub-birth-host');
                if (host) {
                    buildJalaliSelects(host, host.dataset.birth || '');
                    host.addEventListener('change', () => { app._editingSubscriptions[subId] = true; });
                }
            });
            list.forEach(sub => { app._subscriptionTimestamps[sub.id] = sub.updated_at || 0; });
        } else {
            list.forEach(sub => {
                const lastTs = app._subscriptionTimestamps[sub.id] || 0;
                const curTs = sub.updated_at || 0;
                if (curTs > lastTs && app._editingSubscriptions[sub.id]) {
                    delete app._editingSubscriptions[sub.id];
                }
                app._subscriptionTimestamps[sub.id] = curTs;
            });
            list.forEach(sub => {
                const card = findCard(sub.id);
                if (!card) return;
                updateManageCard(card, sub, focusedSubId === sub.id);
            });
        }
    }

    function renderBirthdays(list) {
        list = list || [];
        if (!list.length) {
            refs.birthdayBar.classList.add('hidden');
            refs.birthdayBar.innerHTML = '';
            app._lastBirthdayCount = 0;
            return;
        }
        const prev = app._lastBirthdayCount || 0;
        refs.birthdayBar.classList.remove('hidden');
        refs.birthdayBar.innerHTML = `
            <div class="bday-inner">
                <span class="bday-emoji">🎂</span>
                <div class="bday-items">
                    ${list.map(b => `<div class="bday-item">🎉 امروز تولد اشتراک <b>#${escapeHtml(b.id)}</b> است!${b.phone ? ` &nbsp;شماره تماس: <b>${escapeHtml(b.phone)}</b>` : ''}</div>`).join('')}
                </div>
                <span class="bday-emoji">🎁</span>
            </div>`;
        if (prev === 0) {
            fireConfettiBurst();
        }
        app._lastBirthdayCount = list.length;
    }

    function lotteryCellMini(sub) {
        return `<div class="lottery-cell spinning">
            <div class="lc-id">#${escapeHtml(sub.id)}</div>
            <div class="lc-phone">${escapeHtml(sub.phone || 'بدون شماره')}</div>
            <div class="lc-balance">${moneyText(sub.balance || 0)}</div>
        </div>`;
    }

    function winnerCard(sub) {
        return `<div class="winner-card">
            <div class="winner-tag">🎉 برنده قرعه کشی</div>
            <div class="lc-id" style="margin-top:.4rem">#${escapeHtml(sub.id)}</div>
            <div class="lc-phone" style="margin-top:.5rem">شماره تماس: ${escapeHtml(sub.phone || '—')}</div>
            <div class="lc-balance">موجودی: ${moneyText(sub.balance || 0)}</div>
        </div>`;
    }

    function statusLabel(s) {
        if (s === 'confirmed') return { t: 'تأیید شده', c: '#34d399', b: 'rgba(52,211,153,.3)' };
        if (s === 'cancelled') return { t: 'لغو شده', c: '#fb7185', b: 'rgba(251,113,133,.3)' };
        return { t: 'در انتظار', c: '#fbbf24', b: 'rgba(251,191,36,.3)' };
    }
    function receiptCardHtml(r) {
        const sl = statusLabel(r.status);
        const time = new Date((r.created_at || 0) * 1000).toLocaleString('fa-IR');
        const balLine = (r.balance !== null && r.balance !== undefined) ? `<span>موجودی فعلی اشتراک</span><b>${moneyText(r.balance)}</b>` : '';
        const exists = r.subscription_exists ? '' : '<div class="text-xs text-rose-300 mt-1">⚠️ اشتراک حذف شده است</div>';
        const actions = r.status === 'pending'
            ? `<div class="flex gap-2 mt-3">
                  <button type="button" class="btn btn-primary btn-sm flex-1" data-receipt-action="confirm" data-id="${escapeHtml(r.id)}">✅ تایید و شارژ</button>
                  <button type="button" class="btn btn-danger btn-sm flex-1" data-receipt-action="cancel" data-id="${escapeHtml(r.id)}">❌ لغو</button>
               </div>`
            : '';
        return `
        <article class="sub-card" data-receipt-id="${escapeHtml(r.id)}">
            <div class="sub-card-top">
                <div class="sub-id-wrap">
                    <span class="sub-id-label">اشتراک</span>
                    <span class="sub-id-num">${escapeHtml(r.subscription_id)}</span>
                </div>
                <span class="sub-badge" style="background:rgba(${sl.c === '#34d399' ? '52,211,153' : sl.c === '#fb7185' ? '251,113,133' : '251,191,36'},.14);color:${sl.c};border-color:${sl.b}">${escapeHtml(sl.t)}</span>
            </div>
            <div class="sub-info mt-3">
                <div class="info-cell"><span>مبلغ درخواستی</span><b style="color:#fbbf24">${moneyText(r.amount)}</b></div>
                <div class="info-cell">${balLine}</div>
            </div>
            ${exists}
            <div class="preview-wrap mt-3" style="border-radius:.9rem"><img src="index.php?api=receipt_image&id=${encodeURIComponent(r.id)}" alt="رسید" style="width:100%;display:block"></div>
            <div class="sub-created mt-2">زمان: ${time}</div>
            ${actions}
        </article>`;
    }
    async function fetchReceipts() {
        try {
            const res = await api('receipt_list');
            const list = res.receipts || [];
            refs.receiptList.innerHTML = list.length
                ? list.map(receiptCardHtml).join('')
                : '<div class="empty-state">هیچ رسیدی ثبت نشده است.</div>';
            const pending = list.filter(r => r.status === 'pending').length;
            if (pending > 0) {
                refs.receiptBadge.classList.remove('hidden');
                refs.receiptBadge.textContent = String(pending);
            } else {
                refs.receiptBadge.classList.add('hidden');
            }
        } catch (e) {
            refs.receiptList.innerHTML = '<div class="empty-state">خطا در بارگذاری رسیدها.</div>';
        }
    }
    async function handleReceiptAction(id, op) {
        const card = refs.receiptList.querySelector(`[data-receipt-id="${CSS.escape(id)}"]`);
        if (card) card.querySelectorAll('button').forEach(b => b.disabled = true);
        try {
            const res = await api('receipt_action', { id, op });
            if (op === 'confirm') {
                const b = res.bonuses || {};
                const lines = [];
                if (b.gift_bonus > 0) lines.push(`🎁 هدیه: ${moneyText(b.gift_bonus)}`);
                if (b.referral_bonus > 0) lines.push(`🤝 پاداش دعوت: ${moneyText(b.referral_bonus)}`);
                if (b.week_bonus > 0) lines.push(`📅 هفتگی: ${moneyText(b.week_bonus)}`);
                if (b.month_bonus > 0) lines.push(`🗓 ماهانه: ${moneyText(b.month_bonus)}`);
                alert(res.message + (lines.length ? '\n' + lines.join(' | ') : ''));
            }
            fetchReceipts();
            refreshState(false);
        } catch (e) {
            alert(e.data?.error || 'خطا در پردازش رسید');
            fetchReceipts();
        }
    }
    function handleReceiptClick(ev) {
        const btn = ev.target.closest('button[data-receipt-action]');
        if (!btn) return;
        handleReceiptAction(btn.dataset.id, btn.dataset.receiptAction);
    }

    let lotteryRunning = false;
    function startLottery() {
        if (lotteryRunning) return;
        const subs = (app.subscriptions || []);
        if (!subs.length) {
            refs.lotteryResult.innerHTML = `<div class="winner-banner" style="border-color:rgba(244,63,94,.4);background:rgba(244,63,94,.1)">ابتدا یک اشتراک بساز تا قرعه کشی انجام شود.</div>`;
            return;
        }
        lotteryRunning = true;
        refs.lotteryStart.disabled = true;
        refs.lotteryResult.innerHTML = '';
        const winner = subs[Math.floor(Math.random() * subs.length)];
        const totalSpins = 26;
        let i = 0;
        function spin() {
            const pick = subs[Math.floor(Math.random() * subs.length)];
            refs.lotteryReel.innerHTML = lotteryCellMini(pick);
            i++;
            if (i < totalSpins) {
                setTimeout(spin, 70 + i * 10);
            } else {
                refs.lotteryReel.innerHTML = winnerCard(winner);
                refs.lotteryResult.innerHTML = `<div class="winner-banner">✨ اشتراک <b>#${escapeHtml(winner.id)}</b> به‌عنوان برنده قرعه کشی انتخاب شد!</div>`;
                fireConfettiBurst();
                lotteryRunning = false;
                refs.lotteryStart.disabled = false;
            }
        }
        spin();
    }

    function renderIncome() {
        refs.incomeToday.textContent = moneyText(app.income?.today || 0);
        const list = (app.income?.transactions || []).slice().reverse();
        if (!list.length) {
            refs.incomeTransactions.innerHTML = '<div class="rounded-2xl border border-white/8 bg-white/[0.04] px-4 py-4 text-sm text-slate-400">هنوز تراکنشی برای امروز ثبت نشده است.</div>';
            return;
        }
        refs.incomeTransactions.innerHTML = list.map(tx => {
            const ts = new Date((tx.ts || 0) * 1000).toLocaleTimeString('fa-IR');
            const label = tx.label || (tx.type === 'recharge' ? 'شارژ اشتراک' : 'ساخت اشتراک');
            return `
                <div class="rounded-2xl border border-white/8 bg-white/[0.04] px-4 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-bold text-white">${escapeHtml(label)}</div>
                            <div class="mt-1 text-xs text-slate-400">${escapeHtml(tx.subscription_id || '—')} • ${escapeHtml(ts)}</div>
                        </div>
                        <div class="font-black text-cyan-200">${moneyText(tx.amount || 0)}</div>
                    </div>
                </div>`;
        }).join('');
    }

    function renderSettings() {
        if (!app._settingsEdited) app._settingsEdited = {};
        if (!app._settingsEdited.username && document.activeElement !== refs.settingsUsername) {
            refs.settingsUsername.value = app.settings?.username || '';
        }
        if (!app._settingsEdited.reward && document.activeElement !== refs.settingsReward) {
            refs.settingsReward.value = Math.round(Number(app.settings?.referral_reward) || 0);
        }
        if (!app._settingsEdited.step && document.activeElement !== refs.settingsStep) {
            refs.settingsStep.value = Math.round(Number(app.settings?.referral_step) || 100000);
        }
        if (!app._settingsEdited.giftReward && document.activeElement !== refs.settingsGiftReward) {
            refs.settingsGiftReward.value = Math.round(Number(app.settings?.gift_reward) || 75000);
        }
        if (!app._settingsEdited.giftStep && document.activeElement !== refs.settingsGiftStep) {
            refs.settingsGiftStep.value = Math.round(Number(app.settings?.gift_step) || 750000);
        }
        if (!app._settingsEdited.weekReward && document.activeElement !== refs.settingsWeekReward) {
            refs.settingsWeekReward.value = Math.round(Number(app.settings?.ref_week_reward) || 50000);
        }
        if (!app._settingsEdited.weekStep && document.activeElement !== refs.settingsWeekStep) {
            refs.settingsWeekStep.value = Math.round(Number(app.settings?.ref_week_step) || 1000000);
        }
        if (!app._settingsEdited.monthReward && document.activeElement !== refs.settingsMonthReward) {
            refs.settingsMonthReward.value = Math.round(Number(app.settings?.ref_month_reward) || 300000);
        }
        if (!app._settingsEdited.monthStep && document.activeElement !== refs.settingsMonthStep) {
            refs.settingsMonthStep.value = Math.round(Number(app.settings?.ref_month_step) || 5000000);
        }
        if (refs.rechargeGiftStep) refs.rechargeGiftStep.textContent = numberFormatter.format(Math.round(Number(app.settings?.gift_step) || 750000));
        if (refs.rechargeGiftReward) refs.rechargeGiftReward.textContent = numberFormatter.format(Math.round(Number(app.settings?.gift_reward) || 75000));
    }

    function applyState(data) {
        app.state = data;
        app.settings = data.settings || app.settings;
        app.income = data.income || app.income;
        app.subscriptions = data.subscriptions || [];
        app.serverTimeOffset = (data.server_time * 1000) - Date.now();
        if (data.current_user) {
            if (refs.syncText) refs.syncText.textContent = 'همگام با سرور • ' + new Date(Date.now() + app.serverTimeOffset).toLocaleTimeString('fa-IR');
        }
        if (refs.lotteryInfo) refs.lotteryInfo.textContent = numberFormatter.format(app.subscriptions.length) + ' اشتراک ثبت شده';
        renderBirthdays(data.birthdays_today);
        const isInitial = !app._state;
        (data.timers || []).forEach(timer => normalizeTimer(timer, isInitial));
        renderSubscriptions(isInitial);
        renderIncome();
        renderSettings();
        app._state = data;
    }

    async function refreshState(showSync = true) {
        try {
            const data = await api('state');
            if (showSync) {
                refs.syncText.textContent = 'همگام با سرور • ' + new Date(Date.now() + app.serverTimeOffset).toLocaleTimeString('fa-IR');
                refs.syncDot.style.background = '#34d399';
            }
            applyState(data);
            return true;
        } catch (e) {
            refs.syncText.textContent = 'قطع ارتباط با سرور';
            refs.syncDot.style.background = '#f87171';
            return false;
        }
    }

    function timerPayload(id, op) {
        const el = refs.timers[id];
        const payload = { id, op, mode: el.mode.value, source_type: el.source.value };
        if (payload.mode === 'counter') {
            payload.rate = parseMoney(el.rate.value);
            payload.balance = 0;
            payload.source_subscription_id = '';
        } else {
            payload.rate = parseMoney(el.rate.value);
            payload.source_subscription_id = el.source.value === 'subscription' ? String(el.subscription.value || '').trim() : '';
            payload.balance = el.source.value === 'subscription' ? 0 : parseMoney(el.balance.value);
        }
        return payload;
    }

    async function doTimerAction(op, id) {
        const el = refs.timers[id];
        el.start.disabled = el.stop.disabled = el.update.disabled = el.reset.disabled = true;
        try {
            const res = await api('timer_action', timerPayload(id, op));
            if (res.ok && res.timer) {
                res.timer._message = res.message || '';
                el._userEdited = { balance: false, rate: false, subscription: false, mode: false, source: false };
                normalizeTimer(res.timer, true);
            }
        } catch (e) {
            const t = app.timers[id];
            if (t) {
                t._message = e.data?.error || 'خطا در ارتباط با سرور';
                renderTimer(id);
            }
        } finally {
            renderTimer(id);
            el.start.disabled = el.stop.disabled = el.update.disabled = el.reset.disabled = false;
        }
    }

    async function submitCreateSubscription(ev) {
        ev.preventDefault();
        try {
            const res = await api('subscription_create', {
                id: String(refs.createId.value || '').trim(),
                phone: String(refs.createPhone.value || '').trim(),
                birth_date: String((refs.createBirthDate && refs.createBirthDate.getValue) ? refs.createBirthDate.getValue() : '').trim(),
                invite_ref: String(refs.createInvite.value || '').trim(),
                initial_balance: parseMoney(refs.createBalance.value),
                note: String(refs.createNote.value || '').trim(),
            });
            const giftLine = (res.gift_added && res.gift_added > 0) ? `<div class="mt-1 text-xs text-amber-300">🎁 هدیه شارژ: ${moneyText(res.gift_added)}</div>` : '';
            const refLine = (res.bonus_added && res.bonus_added > 0) ? `<div class="mt-1 text-xs text-emerald-300">🤝 پاداش دعوت سریع به معرف: ${moneyText(res.bonus_added)}</div>` : '';
            const weekLine = (res.week_bonus && res.week_bonus > 0) ? `<div class="mt-1 text-xs text-violet-300">📅 پاداش هفتگی دعوت به معرف: ${moneyText(res.week_bonus)}</div>` : '';
            const monthLine = (res.month_bonus && res.month_bonus > 0) ? `<div class="mt-1 text-xs text-fuchsia-300">🗓 پاداش ماهانه دعوت به معرف: ${moneyText(res.month_bonus)}</div>` : '';
            refs.createResult.innerHTML = `
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="font-bold text-emerald-300">${escapeHtml(res.message || 'اشتراک ساخته شد.')}</div>
                        <div class="mt-1 text-xs text-slate-400">کد دعوت اختصاصی: ${escapeHtml(res.subscription.invite_code)}</div>
                        ${giftLine}${refLine}${weekLine}${monthLine}
                    </div>
                    <div class="text-sm text-slate-300">موجودی: ${moneyText(res.subscription.balance || 0)}</div>
                </div>`;
            refs.createForm.reset();
            refs.createBalance.value = '0';
            await refreshState(false);
        } catch (e) {
            refs.createResult.innerHTML = `<div class="font-bold text-rose-300">${escapeHtml(e.data?.error || e.message || 'خطا در ساخت اشتراک')}</div>`;
        }
    }

    async function submitRechargeSubscription(ev) {
        ev.preventDefault();
        try {
            const res = await api('subscription_recharge', {
                id: String(refs.rechargeId.value || '').trim(),
                amount: parseMoney(refs.rechargeAmount.value),
            });
            const giftLine = (res.gift_added && res.gift_added > 0) ? `<div class="mt-1 text-xs text-amber-300">🎁 هدیه شارژ به همین اشتراک: ${moneyText(res.gift_added)}</div>` : '';
            const refLine = (res.bonus_added && res.bonus_added > 0) ? `<div class="mt-1 text-xs text-emerald-300">🤝 پاداش دعوت سریع به معرف: ${moneyText(res.bonus_added)}</div>` : '';
            const weekLine = (res.week_bonus && res.week_bonus > 0) ? `<div class="mt-1 text-xs text-violet-300">📅 پاداش هفتگی دعوت به معرف: ${moneyText(res.week_bonus)}</div>` : '';
            const monthLine = (res.month_bonus && res.month_bonus > 0) ? `<div class="mt-1 text-xs text-fuchsia-300">🗓 پاداش ماهانه دعوت به معرف: ${moneyText(res.month_bonus)}</div>` : '';
            refs.rechargeResult.innerHTML = `
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="font-bold text-emerald-300">${escapeHtml(res.message || 'اشتراک شارژ شد.')}</div>
                        <div class="mt-1 text-xs text-slate-400">شماره اشتراک: ${escapeHtml(res.subscription.id)}</div>
                        ${giftLine}${refLine}${weekLine}${monthLine}
                    </div>
                    <div class="text-sm text-slate-300">موجودی جدید: ${moneyText(res.subscription.balance || 0)}</div>
                </div>`;
            refs.rechargeForm.reset();
            refs.rechargeAmount.value = '0';
            await refreshState(false);
        } catch (e) {
            refs.rechargeResult.innerHTML = `<div class="font-bold text-rose-300">${escapeHtml(e.data?.error || e.message || 'خطا در شارژ اشتراک')}</div>`;
        }
    }

    async function submitSettings(ev) {
        ev.preventDefault();
        try {
            const res = await api('settings_save', {
                username: String(refs.settingsUsername.value || '').trim(),
                password: String(refs.settingsPassword.value || ''),
                referral_reward: parseMoney(refs.settingsReward.value),
                referral_step: Math.max(1, parseMoney(refs.settingsStep.value)),
                gift_reward: parseMoney(refs.settingsGiftReward.value),
                gift_step: Math.max(1, parseMoney(refs.settingsGiftStep.value)),
                ref_week_reward: parseMoney(refs.settingsWeekReward.value),
                ref_week_step: Math.max(1, parseMoney(refs.settingsWeekStep.value)),
                ref_month_reward: parseMoney(refs.settingsMonthReward.value),
                ref_month_step: Math.max(1, parseMoney(refs.settingsMonthStep.value)),
            });
            refs.settingsResult.innerHTML = `<div class="font-bold text-emerald-300">${escapeHtml(res.message || 'تنظیمات ذخیره شد.')}</div>`;
            refs.settingsPassword.value = '';
            app._settingsEdited = {};
            await refreshState(true);
        } catch (e) {
            refs.settingsResult.innerHTML = `<div class="font-bold text-rose-300">${escapeHtml(e.data?.error || e.message || 'خطا در ذخیره تنظیمات')}</div>`;
        }
    }

    function handleManageClick(ev) {
        const button = ev.target.closest('button[data-action]');
        if (!button) return;
        const card = ev.target.closest('[data-sub-id]');
        if (!card) return;
        const id = card.dataset.subId;
        if (button.dataset.action === 'save-subscription') {
            const inviteVal = String(card.querySelector('.sub-invite')?.value || '').trim();
            const balanceVal = card.querySelector('.sub-balance')?.value;
            const noteVal = String(card.querySelector('.sub-note')?.value || '').trim();
            const phoneVal = String(card.querySelector('.sub-phone')?.value || '').trim();
            const birthHost = card.querySelector('.sub-birth-host');
            const birthVal = String((birthHost && birthHost.getValue) ? birthHost.getValue() : '').trim();

            api('subscription_update', {
                id,
                invite_code: inviteVal,
                balance: parseMoney(balanceVal),
                note: noteVal,
                phone: phoneVal,
                birth_date: birthVal,
            }).then(() => {
                if (app._editingSubscriptions) delete app._editingSubscriptions[id];
                refreshState(true);
            }).catch(err => {
                alert(err.data?.error || err.message || 'خطا در ذخیره اشتراک');
            });
        }
        if (button.dataset.action === 'delete-subscription') {
            if (!confirm(`اشتراک ${id} حذف شود؟`)) return;
            api('subscription_delete', { id }).then(() => {
                if (app._editingSubscriptions) delete app._editingSubscriptions[id];
                refreshState(true);
            }).catch(err => {
                alert(err.data?.error || err.message || 'خطا در حذف اشتراک');
            });
        }
    }

    function setupEvents() {
        refs.tabs.forEach(btn => btn.addEventListener('click', () => setTab(btn.dataset.tab)));

        Object.keys(refs.timers).forEach(id => {
            const el = refs.timers[id];
            el.mode.addEventListener('change', () => {
                el._userEdited.mode = true;
                syncTimerFields(id);
                renderTimer(id);
            });
            el.source.addEventListener('change', () => {
                el._userEdited.source = true;
                syncTimerFields(id);
                renderTimer(id);
            });

            ['balance', 'rate', 'subscription'].forEach(field => {
                el[field].addEventListener('input', () => { el._userEdited[field] = true; });
                el[field].addEventListener('focus', () => { el._userEdited[field] = true; });
            });

            el.start.addEventListener('click', () => doTimerAction('start', id));
            el.stop.addEventListener('click', () => doTimerAction('stop', id));
            el.update.addEventListener('click', () => doTimerAction('update', id));
            el.reset.addEventListener('click', () => doTimerAction('reset', id));
        });

        refs.createForm.addEventListener('submit', submitCreateSubscription);
        refs.rechargeForm.addEventListener('submit', submitRechargeSubscription);
        refs.settingsForm.addEventListener('submit', submitSettings);
        refs.manageBody.addEventListener('click', handleManageClick);
        refs.lotteryStart.addEventListener('click', startLottery);
        refs.receiptRefresh.addEventListener('click', fetchReceipts);
        refs.receiptList.addEventListener('click', handleReceiptClick);
        document.querySelectorAll('[data-tab]').forEach(b => {
            if (b.dataset.tab === 'receipts') {
                b.addEventListener('click', () => fetchReceipts());
            }
        });

        refs.settingsUsername.addEventListener('input', () => { if (!app._settingsEdited) app._settingsEdited = {}; app._settingsEdited.username = true; });
        refs.settingsReward.addEventListener('input', () => { if (!app._settingsEdited) app._settingsEdited = {}; app._settingsEdited.reward = true; });
        refs.settingsStep.addEventListener('input', () => { if (!app._settingsEdited) app._settingsEdited = {}; app._settingsEdited.step = true; });
        refs.settingsGiftReward.addEventListener('input', () => { if (!app._settingsEdited) app._settingsEdited = {}; app._settingsEdited.giftReward = true; });
        refs.settingsGiftStep.addEventListener('input', () => { if (!app._settingsEdited) app._settingsEdited = {}; app._settingsEdited.giftStep = true; });
        refs.settingsWeekReward.addEventListener('input', () => { if (!app._settingsEdited) app._settingsEdited = {}; app._settingsEdited.weekReward = true; });
        refs.settingsWeekStep.addEventListener('input', () => { if (!app._settingsEdited) app._settingsEdited = {}; app._settingsEdited.weekStep = true; });
        refs.settingsMonthReward.addEventListener('input', () => { if (!app._settingsEdited) app._settingsEdited = {}; app._settingsEdited.monthReward = true; });
        refs.settingsMonthStep.addEventListener('input', () => { if (!app._settingsEdited) app._settingsEdited = {}; app._settingsEdited.monthStep = true; });
        if (refs.createBirthDate) buildJalaliSelects(refs.createBirthDate, '');
    }

    function setupCanvas() {
        const canvas = document.getElementById('bg-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];
        function resize() {
            const dpr = Math.max(1, window.devicePixelRatio || 1);
            canvas.width = window.innerWidth * dpr;
            canvas.height = window.innerHeight * dpr;
            canvas.style.width = window.innerWidth + 'px';
            canvas.style.height = window.innerHeight + 'px';
            ctx.setTransform(dpr,0,0,dpr,0,0);
            particles = [];
            const total = Math.min(90, Math.max(44, Math.floor(window.innerWidth / 19)));
            for (let i = 0; i < total; i++) {
                particles.push({
                    x: Math.random() * window.innerWidth,
                    y: Math.random() * window.innerHeight,
                    vx: (Math.random() - 0.5) * 0.26,
                    vy: (Math.random() - 0.5) * 0.26,
                    r: Math.random() * 1.8 + 0.6,
                    hue: Math.random() > 0.5 ? 190 : 280,
                    alpha: Math.random() * 0.5 + 0.16,
                });
            }
        }
        function draw() {
            ctx.clearRect(0,0,window.innerWidth,window.innerHeight);
            particles.forEach(p => {
                p.x += p.vx; p.y += p.vy;
                if (p.x < -20) p.x = window.innerWidth + 20;
                if (p.x > window.innerWidth + 20) p.x = -20;
                if (p.y < -20) p.y = window.innerHeight + 20;
                if (p.y > window.innerHeight + 20) p.y = -20;
                const g = ctx.createRadialGradient(p.x,p.y,0,p.x,p.y,p.r*14);
                g.addColorStop(0, `hsla(${p.hue},100%,70%,${p.alpha})`);
                g.addColorStop(1, 'rgba(0,0,0,0)');
                ctx.fillStyle = g;
                ctx.beginPath();
                ctx.arc(p.x,p.y,p.r*14,0,Math.PI*2);
                ctx.fill();
            });
            requestAnimationFrame(draw);
        }
        window.addEventListener('resize', resize, { passive: true });
        resize();
        draw();
    }

    bindRefs();
    setupEvents();
    setupCanvas();
    setTab('timers');
    refreshState();
    fetchReceipts();
    setInterval(() => refreshState(), 2200);
    setInterval(() => renderTimers(), 200);
    setInterval(() => fetchReceipts(), 6000);
    </script>
</body>
</html>
