<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Folder Contents</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
<?php
if (isset($_GET['folder'])) {
  $folder = basename($_GET['folder']); // sanitize input
  $folderPath = 'uploads/' . $folder;

  if (is_dir($folderPath)) {
    echo "<h2>Contents of Folder: $folder</h2>";
    echo "<a href='Files.php' class='btn btn-secondary mb-4'>&larr; Back</a>";
    echo "<ul class='list-group'>";
    $files = scandir($folderPath);
    foreach ($files as $file) {
      if ($file !== '.' && $file !== '..') {
        echo "<li class='list-group-item'>$file</li>";
      }
    }
    echo "</ul>";
  } else {
    echo "<div class='alert alert-danger'>Folder does not exist.</div>";
  }
} else {
  echo "<div class='alert alert-warning'>No folder selected.</div>";
}
?>
</body>
</html>
