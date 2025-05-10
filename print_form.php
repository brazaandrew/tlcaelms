<?php
// Database connection
$host = 'localhost';
$dbname = 'tlca_elms';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) throw new Exception("Connection failed: " . $conn->connect_error);
    if (!isset($_GET['IDNO'])) throw new Exception('Student ID not provided');

    $id = intval($_GET['IDNO']);
    $query = $conn->prepare("SELECT * FROM tblstudentlist WHERE IDNO = ?");
    if (!$query) throw new Exception("Query preparation failed: " . $conn->error);

    $query->bind_param("i", $id);
    if (!$query->execute()) throw new Exception("Query execution failed: " . $query->error);

    $result = $query->get_result();
    if ($result->num_rows === 0) throw new Exception('Student not found');

    $student = $result->fetch_assoc();
    $birthdate = !empty($student['birthdate']) ? date('F d, Y', strtotime($student['birthdate'])) : 'N/A';
    $currentYear = date('Y');
    $schoolYear = "$currentYear-" . ($currentYear + 1);
    $currentDate = date('F d, Y');

} catch (Exception $e) {
    $errorMessage = urlencode($e->getMessage());
    header("Location: ./pages/studentlist.php?error=$errorMessage");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Form - <?= htmlspecialchars($student['FIRSTNAME']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.4;
            font-size: 11pt;
            color: #000;
            background: #fff;
            padding: 10px;
        }

        @page {
            size: 8.5in 13in;
            margin: 0.5in;
        }

        .container {
            width: 100%;
            max-width: 7.5in;
            margin: auto;
        }

        .form-box {
            border: 1px solid #000;
            padding: 20px;
            position: relative;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }

        .header img {
            width: 70px;
            height: 70px;
        }

        .header h1 {
            font-size: 18pt;
            margin: 10px 0;
        }

        .header h3, .header h4 {
            margin: 3px 0;
            font-size: 11pt;
        }

        .school-year {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin: 10px 0;
        }

        .section-title {
            font-weight: bold;
            background: #eee;
            padding: 5px 10px;
            margin: 20px 0 10px;
            border-left: 4px solid #000;
            font-size: 12pt;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 20px;
        }

        .field {
            display: flex;
            flex-direction: column;
        }

        .label {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 2px;
        }

        .value {
            border-bottom: 1px solid #000;
            min-height: 20px;
            padding: 3px 5px;
        }

        .signature-section {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            text-align: center;
            gap: 10px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 3px;
        }

        .signature-title {
            font-size: 10pt;
            font-weight: bold;
        }

        .footer {
            text-align: right;
            font-size: 9pt;
            margin-top: 20px;
        }

        .print-controls {
            text-align: center;
            margin: 10px 0;
        }

        .print-btn {
            padding: 8px 16px;
            margin-right: 5px;
            font-size: 13px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80pt;
            color: rgba(0, 0, 0, 0.05);
            pointer-events: none;
            z-index: 0;
        }

        @media print {
            .print-controls {
                display: none;
            }
            .form-box {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<div class="print-controls">
    <button class="print-btn" onclick="window.print()">Print Form</button>
    <button class="print-btn" style="background:#6c757d;" onclick="window.history.back()">Go Back</button>
</div>

<div class="container">
    <div class="form-box">
        <div class="watermark">TLCA</div>
        <div class="header">
            <img src="./images/tlca.png" alt="Logo">
            <h3>Republic of the Philippines</h3>
            <h4>Department of Education</h4>
            <h1>ENROLLMENT FORM</h1>
        </div>

        <div class="school-year">School Year: <?= htmlspecialchars($schoolYear) ?></div>

        <h3 class="section-title">Learner Information</h3>
        <div class="grid">
            <div class="field"><span class="label">LRN Number:</span><div class="value"><?= htmlspecialchars($student['lrn_number'] ?? 'N/A') ?></div></div>
            <div class="field"><span class="label">Birth Certificate No.:</span><div class="value"><?= htmlspecialchars($student['birth_cert_no'] ?? 'N/A') ?></div></div>
            <div class="field"><span class="label">First Name:</span><div class="value"><?= htmlspecialchars($student['FIRSTNAME']) ?></div></div>
            <div class="field"><span class="label">Middle Name:</span><div class="value"><?= htmlspecialchars($student['MIDDLENAME']) ?></div></div>
            <div class="field"><span class="label">Last Name:</span><div class="value"><?= htmlspecialchars($student['LASTNAME']) ?></div></div>
            <div class="field"><span class="label">Extension Name:</span><div class="value"><?= htmlspecialchars($student['extension']) ?></div></div>
            <div class="field"><span class="label">Birthdate:</span><div class="value"><?= $birthdate ?></div></div>
            <div class="field"><span class="label">Sex:</span><div class="value"><?= htmlspecialchars($student['sex']) ?></div></div>
        </div>

        <h3 class="section-title">Current Address</h3>
        <div class="grid">
            <div class="field"><span class="label">House/Street:</span><div class="value"><?= htmlspecialchars($student['house_street']) ?></div></div>
            <div class="field"><span class="label">Barangay:</span><div class="value"><?= htmlspecialchars($student['barangay']) ?></div></div>
            <div class="field"><span class="label">Municipality/City:</span><div class="value"><?= htmlspecialchars($student['municipality']) ?></div></div>
            <div class="field"><span class="label">Province:</span><div class="value"><?= htmlspecialchars($student['province']) ?></div></div>
            <div class="field"><span class="label">Zip Code:</span><div class="value"><?= htmlspecialchars($student['zip_code']) ?></div></div>
        </div>

        <h3 class="section-title">Parent/Guardian Information</h3>
        <div class="grid">
            <div class="field"><span class="label">Father's Name:</span><div class="value"><?= htmlspecialchars($student['father_name']) ?></div></div>
            <div class="field"><span class="label">Mother's Name:</span><div class="value"><?= htmlspecialchars($student['mother_name']) ?></div></div>
            <div class="field"><span class="label">Guardian's Name:</span><div class="value"><?= htmlspecialchars($student['guardian_name']) ?></div></div>
        </div>

        <h3 class="section-title">Academic Information</h3>
        <div class="grid">
            <div class="field"><span class="label">Grade Level:</span><div class="value"><?= htmlspecialchars($student['GRADELVL']) ?></div></div>
            <div class="field"><span class="label">Enrollment Status:</span><div class="value"><?= htmlspecialchars($student['enrollment_status']) ?></div></div>
            <div class="field"><span class="label">Indigenous Group:</span><div class="value"><?= htmlspecialchars($student['indigenous_group']) ?></div></div>
            <div class="field"><span class="label">Has Disability:</span><div class="value"><?= htmlspecialchars($student['has_disability'] ?? 'No') ?></div></div>
            <div class="field"><span class="label">Disability Type:</span><div class="value"><?= htmlspecialchars($student['disability_type']) ?></div></div>
        </div>

        <div class="signature-section">
            <div>
                <div class="signature-line"></div>
                <div class="signature-title">Parent/Guardian</div>
            </div>
            <div>
                <div class="signature-line"></div>
                <div class="signature-title">Registrar/Adviser</div>
            </div>
            <div>
                <div class="signature-line"></div>
                <div class="signature-title">School Head/Principal</div>
            </div>
        </div>

        <div class="footer">
            <p>Date Printed: <?= $currentDate ?></p>
        </div>
    </div>
</div>

<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
</script>

</body>
</html>
