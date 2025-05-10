<?php
// Connect to the database
$host = 'localhost';
$dbname = 'tlca_elms';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        // Fetch student data
        $stmt = $conn->prepare("SELECT * FROM tblstudentlist WHERE IDNO = :id");
        $stmt->execute(['id' => $id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            echo "Student not found!";
            exit;
        }
    } else {
        echo "No student ID provided!";
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

