<?php

require_once 'config.php';

if (isset($_GET['id'])) {

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        exit('Invalid user ID.');
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");

    if (!$stmt) {
        exit('Unable to prepare delete query.');
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();

        header("Location: view.php");
        exit;
    } else {
        echo "Error deleting user.";
    }

    $stmt->close();

} else {
    echo "User ID is missing.";
}

?>