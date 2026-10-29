
<?php

require_once __DIR__ . '/config.php';

// Search input
$search = trim($_GET['search'] ?? '');

// Retrieve users from database
if ($search !== '') {

    $stmt = $conn->prepare(
        "SELECT id, fullname, email, phone, dob, gender, created_at
         FROM users
         WHERE fullname LIKE ? OR email LIKE ?
         ORDER BY id DESC"
    );

    if (!$stmt) {
        die("Unable to retrieve users.");
    }

    $searchTerm = '%' . $search . '%';

    $stmt->bind_param('ss', $searchTerm, $searchTerm);
    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $sql = "SELECT id, fullname, email, phone, dob, gender, created_at
            FROM users
            ORDER BY id DESC";

    $result = $conn->query($sql);

    if (!$result) {
        die("Unable to retrieve users.");
    }
}

// Safely display database values
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Registered Users | CareVia</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f5f8fc;
            font-family: Arial, sans-serif;
        }

        .page-container {
            max-width: 1200px;
            margin: 50px auto;
        }

        .page-title {
            color:  #0d5e6a;;
            font-weight: 700;
        }

    
.btn-success {
    background-color: #0d5e6a;
    border-color: #0d5e6a;
    color: #ffffff;
}

.btn-success:hover {
    background-color: #094750;
    border-color: #094750;
    color: #ffffff;
}

.btn-success:focus,
.btn-success:active {
    background-color: #094750;
    border-color: #094750;
    color: #ffffff;
    box-shadow: none;
}



        .table-container {
            background: #ffffff;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
        }

        .table thead th {
            background-color: #0d5e6a;
            color: #ffffff;
            white-space: nowrap;
        }

        .table tbody tr:hover {
            background-color: #eef8f5;
        }

        .search-input {
            max-width: 400px;
        }

        .empty-message {
            padding: 25px;
            text-align: center;
            color: #777777;
        }
    </style>
</head>

<body>

<div class="container page-container">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">

        <div>
            <h2 class="page-title">CareVia Registered Users</h2>

            <p class="text-muted mb-0">
                View and search registered user details.
            </p>
        </div>

        <a href="form.php" class="btn btn-success" >
            + Register New User
        </a>

    </div>

    <div class="table-container">

        <form method="GET" action="view.php" class="mb-4">

            <label for="search" class="form-label fw-semibold">
                Search by Name or Email
            </label>

            <div class="d-flex flex-wrap gap-2">

                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control search-input"
                    placeholder="Enter name or email..."
                    value="<?= e($search) ?>"
                >

                <button type="submit" class="btn btn-success">
                    Search
                </button>

                <a href="view.php" class="btn btn-outline-secondary">
                    Clear
                </a>

            </div>

        </form>

        <div class="table-responsive">

            <table class="table table-bordered table-striped table-hover align-middle">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Date of Birth</th>
                        <th>Gender</th>
                        <th>Registered At</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>
                            <td><?= e($row['id']) ?></td>
                            <td><?= e($row['fullname']) ?></td>
                            <td><?= e($row['email']) ?></td>
                            <td><?= e($row['phone']) ?></td>
                            <td><?= e($row['dob']) ?></td>
                            <td><?= e($row['gender']) ?></td>
                            <td><?= e($row['created_at']) ?></td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty-message">
                            No registered users found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <p class="text-muted mb-0">
            Search results are displayed in descending order by user ID.
        </p>

    </div>

</div>

</body>
</html>

<?php

if (isset($stmt)) {
    $stmt->close();
}

$conn->close();

?>

