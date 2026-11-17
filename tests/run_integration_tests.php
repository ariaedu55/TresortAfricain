<?php
/**
 * Tests intégration locaux — exécuter : php tests/run_integration_tests.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$base_url = 'http://127.0.0.1:8080';
$test_email = 'vrolingmendy0@gmail.com';

$results = [];

function test_result(array &$results, string $name, bool $ok, string $detail = ''): void {
    $results[] = [
        'name' => $name,
        'ok' => $ok,
        'detail' => $detail,
    ];
    $status = $ok ? 'OK' : 'ECHEC';
    echo "[{$status}] {$name}";
    if ($detail !== '') {
        echo " — {$detail}";
    }
    echo PHP_EOL;
}

function parse_response_headers(array $headers): array {
    $status = 0;
    if (!empty($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0], $m)) {
        $status = (int) $m[1];
    }

    $cookies = [];
    foreach ($headers as $h) {
        if (stripos($h, 'Set-Cookie:') === 0 && preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/i', $h, $m)) {
            $cookies[$m[1]] = $m[2];
        }
    }

    return ['status' => $status, 'cookies' => $cookies];
}

function http_get(string $url): array {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
            'follow_location' => 0,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $headers = $http_response_header ?? [];
    $parsed = parse_response_headers($headers);
    return [
        'status' => $parsed['status'],
        'body' => $body === false ? '' : $body,
        'headers' => $headers,
        'cookies' => $parsed['cookies'],
    ];
}

function http_post(string $url, array $fields, array $cookies = []): array {
    $cookie_header = '';
    if (!empty($cookies)) {
        $parts = [];
        foreach ($cookies as $k => $v) {
            $parts[] = $k . '=' . $v;
        }
        $cookie_header = implode('; ', $parts);
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
                . ($cookie_header ? "Cookie: {$cookie_header}\r\n" : ''),
            'content' => http_build_query($fields),
            'timeout' => 20,
            'follow_location' => 0,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $headers = $http_response_header ?? [];
    $parsed = parse_response_headers($headers);

    return [
        'status' => $parsed['status'],
        'body' => $body === false ? '' : $body,
        'headers' => $headers,
        'cookies' => $parsed['cookies'],
    ];
}

echo "=== TESTS INTEGRATION TRESORV2 ===" . PHP_EOL;
echo 'Date: ' . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

// 1. Environnement PHP
echo "--- Environnement ---" . PHP_EOL;
test_result($results, 'PHP >= 8.2', PHP_VERSION_ID >= 80200, 'Version ' . PHP_VERSION);
test_result($results, 'Extension PDO MySQL', extension_loaded('pdo_mysql'));
test_result($results, 'Composer autoload', file_exists($root . '/vendor/autoload.php'));
test_result($results, 'config/email.php', file_exists($root . '/config/email.php'));
test_result($results, 'conn/conn.php', file_exists($root . '/conn/conn.php'));

require $root . '/vendor/autoload.php';
require $root . '/includes/site_email.php';

$config = site_email_config();
test_result(
    $results,
    'Config SMTP no-replaye',
    ($config['method'] ?? '') === 'smtp'
        && ($config['smtp']['username'] ?? '') === 'no-replaye@tresorafricain.com',
    'method=' . ($config['method'] ?? 'n/a')
);

// 2. Base de données
echo PHP_EOL . "--- Base de donnees ---" . PHP_EOL;
require $root . '/conn/conn.php';
$db_ok = isset($db) && $db instanceof PDO;
test_result($results, 'Connexion PDO', $db_ok);
if ($db_ok) {
    $tables = ['users', 'admin', 'categories', 'produits', 'commandes', 'user_password_reset'];
    foreach ($tables as $table) {
        try {
            $db->query("SELECT 1 FROM `{$table}` LIMIT 1");
            test_result($results, "Table {$table}", true);
        } catch (Throwable $e) {
            test_result($results, "Table {$table}", false, $e->getMessage());
        }
    }
    $user_count = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $admin_count = (int) $db->query('SELECT COUNT(*) FROM admin')->fetchColumn();
    test_result($results, 'Comptes users', $user_count > 0, "{$user_count} utilisateur(s)");
    test_result($results, 'Comptes admin', $admin_count > 0, "{$admin_count} admin(s)");
}

// 3. Pages HTTP
echo PHP_EOL . "--- Pages HTTP ---" . PHP_EOL;
$pages = [
    '/' => [200],
    '/index.php' => [200],
    '/contact.php' => [200],
    '/user/connexion.php' => [200],
    '/user/inscription.php' => [200],
    '/user/mot-de-passe-oublie.php' => [200],
    '/admin/login.php' => [200, 302],
    '/admin/dashboard.php' => [302],
];

foreach ($pages as $path => $expected) {
    $res = http_get($base_url . $path);
    $ok = in_array($res['status'], $expected, true);
    $fatal = stripos($res['body'], 'Fatal error') !== false || stripos($res['body'], 'Composer detected') !== false;
    test_result($results, "GET {$path}", $ok && !$fatal, 'HTTP ' . $res['status']);
}

// 4. Emails SMTP
echo PHP_EOL . "--- Emails SMTP ---" . PHP_EOL;

$r1 = mail_send(
    $test_email,
    '[Test intégration] Envoi simple',
    '<p>Test 1/3 — envoi simple via no-replaye@tresorafricain.com</p>',
    true
);
test_result($results, 'Email envoi simple', !empty($r1['success']), $r1['error'] ?? 'envoye');

$r2 = mail_send_contact(
    'Test Integration',
    $test_email,
    'Test formulaire contact',
    'Message de test integration — doit arriver sur service@tresorafricain.com avec Reply-To Gmail.'
);
test_result($results, 'Email formulaire contact', !empty($r2['success']), $r2['error'] ?? 'envoye');

// Mot de passe oublie : s assurer qu un user existe avec l email de test
if ($db_ok) {
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $test_email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        $hash = password_hash('TestIntegr123!', PASSWORD_BCRYPT);
        $ins = $db->prepare(
            'INSERT INTO users (nom, prenom, email, telephone, password, date_creation, statut, accepte_conditions)
             VALUES (:nom, :prenom, :email, :telephone, :password, NOW(), :statut, 1)'
        );
        $ins->execute([
            'nom' => 'Mendy',
            'prenom' => 'Test',
            'email' => $test_email,
            'telephone' => '770000001',
            'password' => $hash,
            'statut' => 'actif',
        ]);
        test_result($results, 'Creation user test Gmail', true, $test_email);
    } else {
        test_result($results, 'User test Gmail existe', true, $test_email);
    }

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
    $_POST['email'] = $test_email;
    require_once $root . '/controllers/controller_users.php';
    $reset = process_user_forgot_password();
    test_result(
        $results,
        'Flux mot de passe oublie (controller)',
        !empty($reset['success']) && !empty($reset['reset_link']),
        $reset['message'] ?? ''
    );

    $r3 = mail_send_reset_link($test_email, $reset['reset_link'] ?? ($base_url . '/user/reinitialiser-mot-de-passe.php?token=test'), 'user');
    test_result($results, 'Email reset password', !empty($r3['success']), $r3['error'] ?? 'envoye');
}

// 5. Flux HTTP mot de passe oublie
echo PHP_EOL . "--- Flux HTTP ---" . PHP_EOL;
$forgot = http_post($base_url . '/user/mot-de-passe-oublie.php', ['email' => $test_email]);
$forgot_ok = $forgot['status'] === 200
    && stripos($forgot['body'], 'success-message') !== false
    && stripos($forgot['body'], 'Fatal error') === false;
test_result($results, 'POST mot-de-passe-oublie.php', $forgot_ok, 'HTTP ' . $forgot['status']);

// Contact avec CSRF
$contact_get = http_get($base_url . '/contact.php');
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $contact_get['body'], $csrf_match);
$csrf = $csrf_match[1] ?? '';
$session_cookie = $contact_get['cookies']['PHPSESSID'] ?? '';
if ($csrf && $session_cookie) {
    $contact_post = http_post(
        $base_url . '/contact.php',
        [
            'csrf_token' => $csrf,
            'nom' => 'Test Integration',
            'email' => $test_email,
            'sujet' => 'Test integration contact',
            'message' => 'Test automatique du formulaire de contact.',
        ],
        ['PHPSESSID' => $session_cookie]
    );
    $contact_ok = $contact_post['status'] === 200
        && stripos($contact_post['body'], 'envoyé avec succès') !== false;
    test_result($results, 'POST contact.php', $contact_ok, 'HTTP ' . $contact_post['status']);
} else {
    test_result($results, 'POST contact.php', false, 'CSRF introuvable');
}

// Admin login page
$admin_login = http_get($base_url . '/admin/login.php');
test_result(
    $results,
    'Page admin login sans fatal',
    $admin_login['status'] === 200 && stripos($admin_login['body'], 'Connexion Admin') !== false,
    'HTTP ' . $admin_login['status']
);

// Resume
echo PHP_EOL . '=== RESUME ===' . PHP_EOL;
$passed = count(array_filter($results, static fn($r) => $r['ok']));
$failed = count($results) - $passed;
echo "Reussis : {$passed}/" . count($results) . PHP_EOL;
echo "Echecs  : {$failed}" . PHP_EOL;

if ($failed > 0) {
    echo PHP_EOL . 'Details des echecs :' . PHP_EOL;
    foreach ($results as $r) {
        if (!$r['ok']) {
            echo ' - ' . $r['name'] . ($r['detail'] ? ' (' . $r['detail'] . ')' : '') . PHP_EOL;
        }
    }
    exit(1);
}

echo PHP_EOL . 'Tous les tests sont passes.' . PHP_EOL;
exit(0);
