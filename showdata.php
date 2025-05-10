<?php
$host = 'localhost';
$dbname = 'tlca_elms';
$username = 'root';
$password = '';

$students = []; // 👈 Avoid "undefined variable" warning

try {
    // Connect to DB
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all students
    $stmt = $conn->query("SELECT `IDNO`,`STUDIDNO`, `FIRSTNAME`,MIDDLENAME, `LASTNAME`, `GRADELVL` FROM tblstudentlist");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

?>
<?php
$search = $_GET['search'] ?? '';

if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM tblstudentlist WHERE FIRSTNAME LIKE ? OR LASTNAME LIKE ?");
    $like = "%$search%";
    $stmt->bindValue(1, $like, PDO::PARAM_STR);
    $stmt->bindValue(2, $like, PDO::PARAM_STR);
} else {
    $stmt = $conn->prepare("SELECT * FROM tblstudentlist");
}

$stmt->execute();
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>