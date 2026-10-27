<?php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

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
    json_response(false, 'Method not allowed.', 405);
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (
    $name === '' ||
    $subject === '' ||
    $message === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    json_response(false, 'Please enter valid information in all fields.', 400);
}

$stmt = $conn->prepare(
    'INSERT INTO contact_messages (name, email, subject, message)
     VALUES (?, ?, ?, ?)'
);

if (!$stmt) {
    error_log('Contact form prepare failed: ' . $conn->error);
    json_response(false, 'Unable to submit your message right now. Please try again.', 500);
}

$stmt->bind_param('ssss', $name, $email, $subject, $message);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    json_response(true, 'Message submitted successfully! Thank you for contacting CareVia.');
}

error_log('Contact form insert failed: ' . $stmt->error);
$stmt->close();
$conn->close();
json_response(false, 'Unable to submit your message right now. Please try again.', 500);
