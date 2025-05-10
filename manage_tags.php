<?php
// DB connection
$conn = new mysqli("localhost", "root", "", "tlca_elms");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $folder_id = $_POST["folder_id"];
    $teacher_ids = $_POST["teacher_ids"];

    foreach ($teacher_ids as $teacher_id) {
        // Prevent duplicates
        $check = $conn->query("SELECT * FROM folder_teacher WHERE folder_id=$folder_id AND teacher_id=$teacher_id");
        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO folder_teacher (folder_id, teacher_id) VALUES ($folder_id, $teacher_id)");

            // Get teacher details
            $teacher = $conn->query("SELECT * FROM tbluser WHERE IDNO=$teacher_id")->fetch_assoc();
            $folder = $conn->query("SELECT * FROM folders WHERE id=$folder_id")->fetch_assoc();

            $to = $teacher['USERNAME'] . '@example.com'; // Replace with real email if applicable
            $subject = "New Folder Assigned: " . $folder['name'];
            $messageBody = "Hi " . $teacher['USERNAME'] . ",\n\nYou have been assigned a new folder: " . $folder['name'] . ".\nPlease check your account.\n\n- Admin";
            $headers = "From: admin@example.com";

            // mail($to, $subject, $messageBody, $headers); // Commented out
        }
    }

    $message = "✅ Folder assigned successfully! (Email sending is commented out)";
}

// Fetch folders and teachers
$folders = $conn->query("SELECT * FROM folders");
$teachers = $conn->query("SELECT * FROM tbluser WHERE USERTYPE='TEACHER'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Folder to Teacher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }
        .container {
            max-width: 600px;
            margin-top: 50px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .form-select, .form-control, .btn {
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 20px;
        }
        .folder-icon {
            margin-right: 10px;
            color: #3498db;
        }
    </style>
</head>
<body>

<div class="container">
    <h3 class="mb-4 text-center">Assign Folder to Teacher</h3>

    <?php if ($message): ?>
        <div class="alert alert-success message"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="folder_id" class="form-label">Select Folder</label>
        <select name="folder_id" class="form-select" required>
            <?php while($f = $folders->fetch_assoc()): ?>
                <option value="<?= $f['id'] ?>">
                    <i class="fas fa-folder folder-icon"></i><?= htmlspecialchars($f['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="teacher_ids" class="form-label">Select Teachers</label>
        <select name="teacher_ids[]" class="form-select" multiple size="5" required>
            <?php while($t = $teachers->fetch_assoc()): ?>
                <option value="<?= $t['IDNO'] ?>">
                    <?= htmlspecialchars($t['USERNAME']) ?> (<?= htmlspecialchars($t['EMPIDNO']) ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit" class="btn btn-primary w-100">Assign Folder</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
