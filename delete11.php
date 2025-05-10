<?php
// Show errors for debugging


// Database connection
$host = 'localhost';
$dbname = 'tlca_elms';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if ID is passed
    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        // Prepare and execute delete
        $stmt = $conn->prepare("DELETE FROM `tblstudentlist` WHERE `IDNO` = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Redirect
    header("Location: pages/studentlist.php");
    exit();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
