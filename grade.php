<?php
$baseDir = '../uploads'; // Folder where all grades and subjects are stored

if (!isset($_GET['grade'])) {
    die('Grade not specified.');
}

$gradeFolder = basename($_GET['grade']);
$gradePath = $baseDir . '/' . $gradeFolder;

// Make sure the grade folder exists
if (!is_dir($gradePath)) {
    mkdir($gradePath, 0777, true);
}

// Create subject folder when submitted
if (isset($_POST['create_subject'])) {
    $subjectName = trim($_POST['subject_name']);
    if (!empty($subjectName)) {
        $subjectFolder = $gradePath . '/' . str_replace(' ', '', strtolower($subjectName)); // clean name
        if (!file_exists($subjectFolder)) {
            mkdir($subjectFolder, 0777, true);
            echo "<script>alert('Subject created successfully.'); window.location.href=window.location.href;</script>";
        } else {
            echo "<script>alert('Subject already exists.');</script>";
        }
    }
}

// List subjects
$subjects = array_filter(glob($gradePath . '/*'), 'is_dir');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars(ucfirst($gradeFolder)) ?> - Subjects</title>
  <link rel="stylesheet" href="../css/all.min.css" />
  <link rel="stylesheet" href="../css/style.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>

<div class="page-content">
  <div class="sidebar">
    <div class="brand">
      <i class="fa-solid fa-xmark xmark"></i>
      <h2>TLCA</h2>
    </div>
    <ul>
      <li><a href="../dashboard.php" class="sidebar-link"><i class="fa-regular fa-chart-bar fa-fw"></i><span>Dashboard</span></a></li>
      <li><a href="./studentlist.php" class="sidebar-link"><i class="fa-solid fa-user-graduate fa-fw"></i><span>Student List</span></a></li>
      <li><a href="./Courses.php" class="sidebar-link"><i class="fa-solid fa-graduation-cap fa-fw"></i><span>Courses</span></a></li>
      <li><a href="./Files.php" class="sidebar-link"><i class="fa-regular fa-file fa-fw"></i><span>Files</span></a></li>
    </ul>
  </div>

  <main>
    <div class="header">
      <i class="fa-solid fa-bars bar-item"></i>
      <div class="search">
        <input type="search" placeholder="Type A Keyword" />
      </div>
      <div class="profile">
        <span class="bell"><i class="fa-regular fa-bell fa-lg"></i></span>
        <form method="post" action="logout.php">
          <input type="image" src="../images/avatar.png" alt="No Image" width="30" height="30" />
        </form>   
      </div>
    </div>

    <div class="container my-5">
      <a href="./Courses.php" class="btn btn-secondary mb-4">&larr; Back to Courses</a>

      <h1 class="text-primary mb-4"><?= htmlspecialchars(ucfirst($gradeFolder)) ?> - Subjects</h1>

      <!-- Create Subject Form -->
      <div class="card mb-4">
        <div class="card-body">
          <form method="POST">
            <div class="input-group">
              <input type="text" name="subject_name" class="form-control" placeholder="Enter new subject name" required>
              <button type="submit" name="create_subject" class="btn btn-success">Create Subject</button>
            </div>
          </form>
        </div>
      </div>

      <!-- List Subjects -->
      <?php if (empty($subjects)): ?>
        <div class="alert alert-warning">No subjects created yet.</div>
      <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
          <?php foreach ($subjects as $subject): 
              $subjectName = basename($subject);
          ?>
          <div class="col">
            <div class="courses-box">
              <div class="card-image">
                <i class="fas fa-book"></i>
              </div>
              <div class="courses-card-body text-center">
                <h4><?= htmlspecialchars(ucfirst($subjectName)) ?></h4>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
