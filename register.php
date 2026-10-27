<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
// Keep PHP diagnostics out of the JSON response; details go to the server log.
ini_set('display_errors', '0');

function json_response($ok, $message, $status = 200)
{
    http_response_code($status);
    echo json_encode([
        'ok' => $ok,
        'message' => $message,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    json_response(false, 'Method not allowed.', 405);
}

foreach (['fullname', 'email', 'phone', 'dob', 'gender', 'password', 'confirmPassword', 'terms'] as $field) {
    if (isset($_POST[$field]) && !is_string($_POST[$field])) {
        json_response(false, 'Please enter valid information in all fields.', 400);
    }
}

$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$terms = $_POST['terms'] ?? '';

$allowedGenders = ['female', 'male', 'other', 'prefer-not-to-say'];

if (
    $fullname === '' ||
    $phone === '' ||
    $dob === '' ||
    $gender === '' ||
    $password === '' ||
    $terms !== 'on' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    json_response(false, 'Please enter valid information in all fields.', 400);
}

if (mb_strlen($fullname, 'UTF-8') > 150 || mb_strlen($email, 'UTF-8') > 150) {
    json_response(false, 'Full name and email must each be 150 characters or fewer.', 400);
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    json_response(false, 'Please enter a valid 10-digit phone number.', 400);
}

$timezone = new DateTimeZone('Asia/Colombo');
if (!preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $dob)) {
    json_response(false, 'Please enter a valid date of birth.', 400);
}
$dobDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dob, $timezone);
if (!$dobDate || $dobDate->format('Y-m-d') !== $dob) {
    json_response(false, 'Please enter a valid date of birth.', 400);
}

if ($dob < '1000-01-01' || $dobDate > new DateTimeImmutable('today', $timezone)) {
    json_response(false, 'Date of birth must be a valid date on or before today.', 400);
}

if (!in_array($gender, $allowedGenders, true)) {
    json_response(false, 'Please select a valid gender.', 400);
}

if (strlen($password) < 6) {
    json_response(false, 'Password must be at least 6 characters.', 400);
}

// PASSWORD_DEFAULT uses bcrypt, which accepts at most 72 bytes.
if (strlen($password) > 72) {
    json_response(false, 'Password must be 72 bytes or fewer. Use fewer characters.', 400);
}

if ($password !== $confirmPassword) {
    json_response(false, 'Passwords do not match. Please try again.', 400);
}

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    // Config also defines $password for MySQL; keep it separate from the user's password.
    $conn = (static function () {
        require __DIR__ . '/config.php';
        return $conn;
    })();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        'INSERT INTO users (fullname, email, phone, dob, gender, password)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('ssssss', $fullname, $email, $phone, $dob, $gender, $passwordHash);
    $stmt->execute();
    json_response(true, 'Registration successful! Your account has been created. Welcome to CareVia.', 201);
} catch (mysqli_sql_exception $exception) {
    if ((int) $exception->getCode() === 1062) {
        json_response(false, 'This email is already registered. Please use another email.', 409);
    }

    error_log('Registration database error: ' . $exception->getMessage());
    json_response(false, 'Unable to complete registration right now. Please try again.', 500);
} catch (Throwable $exception) {
    error_log('Registration failed: ' . $exception->getMessage());
    json_response(false, 'Unable to complete registration right now. Please try again.', 500);
}
