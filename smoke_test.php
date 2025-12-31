<?php
// smoke_test.php
// Simple web-accessible health/smoke check for the demo app.
// Place these files in your webroot and visit /smoke_test.php in a browser.

require_once __DIR__ . '/config.php';

$results = [];

// PHP environment
$results[] = ['check' => 'PHP SAPI', 'ok' => true, 'detail' => php_sapi_name()];

// CSRF helpers
$hasGenerate = function_exists('generateCsrfToken');
$token = $hasGenerate ? generateCsrfToken() : null;
$results[] = ['check' => 'generateCsrfToken exists', 'ok' => $hasGenerate, 'detail' => $hasGenerate ? 'Token len=' . strlen($token) : 'missing'];

// Files contain CSRF inputs (simple text checks)
$filesToCheck = ['register.php','login.php','dashboard.php'];
foreach ($filesToCheck as $f) {
    $path = __DIR__ . '/' . $f;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $hasCsrf = (strpos($content, 'csrfInput') !== false) || (strpos($content, 'name="csrf_token"') !== false);
        $results[] = ['check' => "{$f} contains CSRF", 'ok' => $hasCsrf, 'detail' => $hasCsrf ? 'OK' : 'MISSING'];
    } else {
        $results[] = ['check' => $f, 'ok' => false, 'detail' => 'File not found'];
    }
}

// Dashboard: check confirm handlers and forms
$dbContent = file_get_contents(__DIR__ . '/dashboard.php');
$hasConfirm = strpos($dbContent, 'form.confirm') !== false || strpos($dbContent, 'data-confirm') !== false;
$results[] = ['check' => 'dashboard confirmation forms', 'ok' => $hasConfirm, 'detail' => $hasConfirm ? 'OK' : 'MISSING'];

// Attempt to call csrfInput() output
$csrfHtml = null;
if (function_exists('csrfInput')) {
    $csrfHtml = csrfInput();
    $results[] = ['check' => 'csrfInput() returns input', 'ok' => strpos($csrfHtml, 'name="csrf_token"') !== false, 'detail' => $csrfHtml];
}

// Print results
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Smoke Test</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;background:#f6f6f6}
        .wrap{max-width:900px;margin:30px auto;background:#fff;padding:20px;border-radius:6px}
        .ok{color:green}
        .bad{color:#b00020}
        pre{background:#f0f0f0;padding:10px}
    </style>
</head>
<body>
<div class="wrap">
    <h1>Smoke Test</h1>
    <p>Note: This runs checks without requiring DB to be running. To test full flows, start a webserver and ensure MySQL is available.</p>
    <table>
        <?php foreach ($results as $r): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($r['check']) ?></strong></td>
                <td><?php echo $r['ok'] ? '<span class="ok">OK</span>' : '<span class="bad">FAIL</span>' ?></td>
                <td><?php echo htmlspecialchars($r['detail']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Manual quick checks</h2>
    <ul>
        <li>Open <code>/register.php</code> and confirm a hidden input named <code>csrf_token</code> is present</li>
        <li>Submit a POST to <code>/register.php</code> with an invalid token to confirm server rejects it (it should show "Invalid CSRF token.")</li>
        <li>Start the DB and run full flows: register -> login -> create event -> buy ticket -> cancel</li>
    </ul>

    <h3>Example curl (to run locally)</h3>
    <pre>curl -i -X POST -d "name=Test&email=test@example.com&password=123456&password_confirm=123456&role=participant&csrf_token=invalid" http://127.0.0.1:8000/register.php</pre>

</div>
</body>
</html>
