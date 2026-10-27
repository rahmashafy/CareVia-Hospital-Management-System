<?php

// Run with Apache and MySQL running: C:\xampp\php\php.exe tests\registration_test.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require dirname(__DIR__) . '/config.php';

function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}

function request_registration($data = null)
{
    $curl = curl_init('http://localhost/CareVia/register.php');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    if ($data !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($body === false) {
        throw new RuntimeException('HTTP request failed: ' . $error);
    }
    if (strpos($contentType ?? '', 'application/json') !== 0) {
        throw new RuntimeException('Expected a JSON content type.');
    }
    return [$status, json_decode($body, true, 512, JSON_THROW_ON_ERROR)];
}

$email = 'carevia-registration-test-' . bin2hex(random_bytes(8)) . '@example.test';
$data = [
    'fullname' => "  CareVia O'Connor பதிவு  ",
    'email' => $email,
    'phone' => '0700000000',
    'dob' => '2000-02-29',
    'gender' => 'prefer-not-to-say',
    'password' => 'Test-only-password-2026!',
    'confirmPassword' => 'Test-only-password-2026!',
    'terms' => 'on',
];

$failure = null;
try {
    [$status, $json] = request_registration();
    check($status === 405 && $json['ok'] === false, 'GET returns a JSON method error');

    [$status, $json] = request_registration($data);
    check($status === 201 && $json['ok'] === true, 'Registration succeeds through the live HTTP endpoint');
    check(strpos($json['message'], 'Registration successful!') !== false, 'Success message is returned after saving');

    $select = $conn->prepare('SELECT fullname, email, phone, dob, gender, password FROM users WHERE email = ?');
    $select->bind_param('s', $email);
    $select->execute();
    $result = $select->get_result();
    check($result->num_rows === 1, 'Exactly one registration is stored in carevia_db.users');
    $user = $result->fetch_assoc();
    foreach (['fullname', 'email', 'phone', 'dob', 'gender'] as $field) {
        check($user[$field] === trim($data[$field]), "Saved $field matches the submitted value");
    }
    check($user['password'] !== $data['password'] && password_verify($data['password'], $user['password']), 'Password is securely hashed and verifies correctly');

    [$status, $json] = request_registration($data);
    check($status === 409 && $json['ok'] === false, 'Duplicate email returns a readable JSON error');

    $invalidCases = [
        'empty name' => ['fullname' => '  '],
        'invalid email' => ['email' => 'invalid-email'],
        'invalid phone' => ['phone' => '1234'],
        'invalid date' => ['dob' => '2001-02-29'],
        'malformed date' => ['dob' => "2000-\0" . '01-01'],
        'future date' => ['dob' => '2999-01-01'],
        'unsupported date range' => ['dob' => '0001-01-01'],
        'invalid gender' => ['gender' => 'invalid'],
        'short password' => ['password' => '12345', 'confirmPassword' => '12345'],
        'password mismatch' => ['confirmPassword' => 'different-password'],
        'long password' => ['password' => str_repeat('a', 73), 'confirmPassword' => str_repeat('a', 73)],
        'long Unicode password' => ['password' => str_repeat('அ', 25), 'confirmPassword' => str_repeat('அ', 25)],
        'missing terms' => ['terms' => ''],
        'invalid terms' => ['terms' => 'false'],
        'long name' => ['fullname' => str_repeat('a', 151)],
        'long email' => ['email' => str_repeat('a', 64) . '@' . str_repeat('b', 63) . '.' . str_repeat('c', 20) . '.test'],
        'array input' => ['fullname' => ['invalid']],
        'array password' => ['password' => ['invalid']],
    ];
    foreach ($invalidCases as $label => $changes) {
        [$status, $json] = request_registration(array_replace($data, $changes));
        check($status === 400 && $json['ok'] === false, "$label is rejected before saving");
    }

    $select->execute();
    check($select->get_result()->num_rows === 1, 'Duplicate and invalid submissions create no extra account');
    $select->close();
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    // Remove only this run's synthetic account; existing users stay untouched.
    $delete = $conn->prepare('DELETE FROM users WHERE email = ?');
    $delete->bind_param('s', $email);
    $delete->execute();
    $delete->close();
    $conn->close();
}

if ($failure !== null) {
    fwrite(STDERR, 'FAIL: ' . $failure->getMessage() . "\n");
    exit(1);
}
echo "Registration checks passed. The test account was removed.\n";
