<?php
$host = 'localhost';
$dbname = 'tlca_elms';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['submit'])) {
  $fields = [
   'IDNO', 'STUDIDNO', 'FIRSTNAME', 'LASTNAME', 'MIDDLENAME', 'GRADELVL',
    'school_year', 'grade_level', 'enrollment_status', 'birth_cert_no',
    'lrn_number', 'extension', 'birthdate', 'sex', 'indigenous_group',
    'religion', 'has_disability', 'disability_type', 'house_street',
    'barangay', 'municipality', 'province', 'zip_code', 'father_name',
    'mother_name', 'guardian_name'
  ];

  $data = [];
  foreach ($fields as $field) {
    $data[] = isset($_POST[$field]) ? $_POST[$field] : '';
  }

  $stmt = $conn->prepare("INSERT INTO tblstudentlist (IDNO, STUDIDNO, FIRSTNAME, LASTNAME, MIDDLENAME, GRADELVL, school_year, grade_level, enrollment_status, birth_cert_no, lrn_number, extension, birthdate, sex, indigenous_group, religion, has_disability, disability_type, house_street, barangay, municipality, province, zip_code, father_name, mother_name, guardian_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

  $stmt->bind_param(str_repeat('s', count($data)), ...$data);

  if ($stmt->execute()) {
    header("Location: ./pages/studentlist.php?success=1");
  } else {
    echo "Error: " . $stmt->error;
  }

  $stmt->close();
  $conn->close();
}
?>
