<?php
$host = 'localhost';
$dbname = 'tlca_elms';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (isset($_POST['update'])) {
    $fields = [
        'IDNO','STUDIDNO', 'FIRSTNAME', 'LASTNAME', 'MIDDLENAME', 'GRADELVL',
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

    $IDNO = $_POST['IDNO'];
    $data[] = $IDNO;

    $setClause = implode(", ", array_map(fn($f) => "$f = ?", $fields));
    $sql = "UPDATE tblstudentlist SET $setClause WHERE IDNO = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) die("Prepare failed: " . $conn->error);

    $types = str_repeat('s', count($data));
    $stmt->bind_param($types, ...$data);

    if ($stmt->execute()) {
        header("Location: studentlist.php?updated=1");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>
