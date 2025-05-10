<?php
$host = 'localhost';
$dbname = 'tlca_elms';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_GET['IDNO'])) {
    header("Location: studentlist.php");
    exit();
}

$id = intval($_GET['IDNO']);
$query = $conn->prepare("SELECT * FROM tblstudentlist WHERE IDNO = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    header("Location: studentlist.php");
    exit();
}

$student = $result->fetch_assoc();
$middleInitial = isset($student['MIDDLENAME'][0]) ? $student['MIDDLENAME'][0] . '.' : '';
$fullName = ucwords("{$student['LASTNAME']}, {$student['FIRSTNAME']} {$middleInitial}");

$grade = htmlspecialchars($student['GRADELVL']);
$schoolYear = htmlspecialchars($student['school_year']);
$today = date("jS \of F Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Certificate of Good Moral</title>
  <style>
    body {
      font-family: 'Times New Roman', serif;
      margin: 50px 80px;
    }
    .center {
      text-align: center;
    }
    .title {
      font-weight: bold;
      letter-spacing: 2px;
      margin: 20px 0;
    }
    .content {
      font-size: 18px;
      text-align: justify;
      line-height: 1.8;
    }
    .bold {
      font-weight: bold;
      text-decoration: underline;
    }
    .footer {
      margin-top: 50px;
      text-align: right;
    }
  </style>
</head>
<body onload="window.print()">
  <div class="center">
    <p>Republic of the Philippines</p>
    <p><strong>Department of Education</strong></p>
    <p>REGION VI WESTERN VISAYAS</p>
    <p>SCHOOLS DIVISION OF BINALBAGAN</p>
    <p><strong>THE LIGHT CHRISTIAN ACADEMY OF BINALBAGAN</strong></p>
    <hr>
    <h3 class="title">C E R T I F I C A T I O N</h3>
  </div>

  <div class="content">
    <p><strong>TO WHOM IT MAY CONCERN:</strong></p>
    
    <p>
      THIS IS TO CERTIFY that <span class="bold"><?= $fullName ?></span> is officially enrolled as
      <span class="bold">Grade <?= $grade ?></span> learner of The Light Christian Academy of Binalbagan, Prk. Aguihis, Brgy. Canmoros, Binalbagan for the school year <span class="bold"><?= $schoolYear ?></span>.
    </p>

    <p>
      This is to certify further that he/she is a student of good moral character and has no property or financial responsibility in this school.
    </p>

    <p>
      This certification is issued upon request of the student concerned for his/her desire to transfer to another school.
    </p>

    <p>
      Done this <?= date("jS") ?> day of <?= date("F Y") ?> at The Light Christian Academy of Binalbagan, Prk. Aguihis, Brgy. Canmoros, Binalbagan.
    </p>
  </div>

  <div class="footer">
  <p><strong>FLORENCE A. LIMASAC Ph.D</strong><br>Principal</p>
 
  </div>
</body>
</html>
