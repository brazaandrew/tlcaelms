
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
$school_year = isset($student['school_year']) ? $student['school_year'] : '________'; // fallback
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Certificate of Enrollment</title>
  <style>
    body {
      font-family: 'Times New Roman', serif;
      margin: 60px;
      text-align: center;
    }
    .header img {
      width: 80px;
      vertical-align: middle;
    }
    .header h4, .header h3, .header h2 {
      margin: 2px;
    }
    .title {
      margin: 30px 0;
      font-size: 20px;
      letter-spacing: 2px;
      font-weight: bold;
    }
    .content {
      text-align: justify;
      line-height: 1.8;
      font-size: 18px;
    }
    .bold {
      font-weight: bold;
      text-decoration: underline;
    }
    .footer {
      margin-top: 60px;
      text-align: right;
      font-size: 18px;
    }
  </style>
</head>
<body onload="window.print()">
  <div class="header">
    <img src="./images/deped.png" alt="DepEd Logo" style="float:left;">
    <img src="./images/tlca.png" alt="School Logo" style="float:right;">
    <div style="margin: 0 100px;">
      <h4>Republic of the Philippines</h4>
      <h3>Department of Education</h3>
      <h4>Region VI, Western Visayas</h4>
      <h4>THE LIGHT CHRISTIAN ACADEMY OF BINALBAGAN</h4>
      <h4>Prk Aguihis Brgy. Canmoros, Binalbagan</h4>
    </div>
  </div>

  <div class="title">C E R T I F I C A T I O N</div>

  <div class="content">
    This is to certify that <span class="bold"><?= strtoupper($student['FIRSTNAME'] . ' ' . $student['MIDDLENAME'][0] . '. ' . $student['LASTNAME']) ?></span>
    is a bonafide <span class="bold"><?= htmlspecialchars($student['GRADELVL']) ?></span>
    pupil of The Light Christian Academy of Binalbagan <span class="bold"><?= htmlspecialchars($school_year) ?></span>.

    <br><br>
    This certification is issued upon the request of the above-mentioned for whatever legal purpose it may serve them best.

    <br><br>
    Given this <?= date('jS') ?> day of <?= date('F Y') ?> at Prk Aguihis Brgy. Canmoros, Binalbagan.

  </div>

  <div class="footer">
  <p><strong>FLORENCE A. LIMASAC Ph.D</strong><br>Principal-I</p>
  </div>
</body>
</html>
