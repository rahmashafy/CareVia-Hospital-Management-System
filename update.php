
<?php
require_once 'config.php';

$error = '';
$row = null;

// Get the user ID from the URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// If the form is submitted, get the ID from POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    exit('Invalid user ID.');
}

// Fetch the existing user details
$stmt = $conn->prepare(
    "SELECT fullname, email, phone, dob, gender FROM users WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    exit('User not found.');
}

// Update user details when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    if (
        $fullname === '' ||
        $email === '' ||
        $phone === '' ||
        $dob === '' ||
        $gender === ''
    ) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Phone number must contain exactly 10 digits.';
    } elseif (
        !DateTime::createFromFormat('!Y-m-d', $dob) ||
        DateTime::createFromFormat('!Y-m-d', $dob)->format('Y-m-d') !== $dob
    ) {
        $error = 'Please enter a valid date of birth.';
    } elseif (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $error = 'Please select a valid gender.';
    } else {
        $stmt = $conn->prepare(
            "UPDATE users
             SET fullname = ?, email = ?, phone = ?, dob = ?, gender = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "sssssi",
            $fullname,
            $email,
            $phone,
            $dob,
            $gender,
            $id
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: view.php");
            exit;
        } else {
            $error = 'Unable to update user. Please try again.';
            $stmt->close();
        }
    }

    // Keep the submitted values in the form if validation fails
    $row = [
        'fullname' => $fullname,
        'email' => $email,
        'phone' => $phone,
        'dob' => $dob,
        'gender' => $gender
    ];
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | CareVia</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #f4f8f9;
        }

        .edit-card {
            max-width: 650px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .btn-carevia {
            background: #0d5e6a;
            color: white;
        }

        .btn-carevia:hover {
            background: #094650;
            color: white;
        }
    </style>
</head>

<body>

<div class="container">
    <div class="edit-card">

        <h2 class="mb-4">Edit User Details</h2>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="update.php?id=<?php echo $id; ?>">

            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input
                    type="text"
                    name="fullname"
                    class="form-control"
                    value="<?php echo e($row['fullname']); ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="<?php echo e($row['email']); ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input
                    type="tel"
                    name="phone"
                    class="form-control"
                    pattern="[0-9]{10}"
                    maxlength="10"
                    value="<?php echo e($row['phone']); ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Date of Birth</label>
                <input
                    type="date"
                    name="dob"
                    class="form-control"
                    value="<?php echo e($row['dob']); ?>"
                    required
                >
            </div>

            <div class="mb-4">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select" required>
                    <option value="">Select Gender</option>
                    <option value="Male"
                        <?php echo $row['gender'] === 'Male' ? 'selected' : ''; ?>>
                        Male
                    </option>
                    <option value="Female"
                        <?php echo $row['gender'] === 'Female' ? 'selected' : ''; ?>>
                        Female
                    </option>
                    <option value="Other"
                        <?php echo $row['gender'] === 'Other' ? 'selected' : ''; ?>>
                        Other
                    </option>
                </select>
            </div>

            <button type="submit" class="btn btn-carevia">
                Update Details
            </button>

            <a href="view.php" class="btn btn-secondary">
                Cancel
            </a>

        </form>

    </div>
</div>

</body>
</html>

