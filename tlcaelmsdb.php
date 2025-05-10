<?php
session_start();

$host = 'localhost';
$dbname = 'tlca_elms';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input_username = $_POST['username'];
        $input_password = $_POST['password'];

        // NOTE: Check for a typo in your column: 'PASSOWRD' ➜ 'PASSWORD'
        $stmt = $pdo->prepare("SELECT * FROM `tbluser` WHERE `EMPIDNO` = :username AND `PASSOWRD` = :password");
        $stmt->execute([
            'username' => $input_username,
            'password' => $input_password
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Store user details in session
            $_SESSION['EMPIDNO'] = $user['EMPIDNO'];
            $_SESSION['USERTYPE'] = $user['USERTYPE'];
            $_SESSION['USERNAME'] = $user['USERNAME']; // this is added

            // Redirect based on user type
            if ($user['USERTYPE'] === 'Admin') {
                header("Location: dashboard.php");
            } else {
                header("Location: tdashboard.php");
            }
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid username or password.";
            header("Location: login.php");
            exit();
        }
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}



?>
