<?php
session_start(); 
// Database connection
include('tlcaelmsdb.php');


// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $studidno = $_POST['studidno'];
    $lrn_number = $_POST['lrn_number'];
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'] ?? '';
    $lastname = $_POST['lastname'];
    $extension = $_POST['extension'] ?? '';
    $birthdate = $_POST['birthdate'];
    $sex = $_POST['sex'];
    
    // Address information
    $house_street = $_POST['house_street'];
    $barangay = $_POST['barangay'];
    $municipality = $_POST['municipality'];
    $province = $_POST['province'];
    $zip_code = $_POST['zip_code'];
    
    // Academic information
    $gradelevel = $_POST['gradelevel'];
    $grade_level = $_POST['grade_level'];
    $school_year = $_POST['school_year'];
    $enrollment_status = $_POST['enrollment_status'];
    $birth_cert_no = $_POST['birth_cert_no'];
    
    // Other information
    $indigenous_group = $_POST['indigenous_group'] ?? '';
    $religion = $_POST['religion'] ?? '';
    $has_disability = $_POST['has_disability'] ?? 'No';
    $disability_type = $_POST['disability_type'] ?? '';
    
    // Guardian information
    $father_name = $_POST['father_name'] ?? '';
    $mother_name = $_POST['mother_name'] ?? '';
    $guardian_name = $_POST['guardian_name'] ?? '';
    
    // Prepare SQL statement for student table
    $sql = "INSERT INTO students (STUDIDNO, LRN_NUMBER, FIRSTNAME, MIDDLENAME, LASTNAME, EXTENSION, BIRTHDATE, SEX, GRADELVL, GRADE_LEVEL, SCHOOL_YEAR, ENROLLMENT_STATUS, BIRTH_CERT_NO) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Create prepared statement
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bind_param("sssssssssssss", 
        $studidno, 
        $lrn_number, 
        $firstname, 
        $middlename, 
        $lastname, 
        $extension, 
        $birthdate, 
        $sex, 
        $gradelevel, 
        $grade_level, 
        $school_year, 
        $enrollment_status, 
        $birth_cert_no
    );
    
    // Execute the statement
    if ($stmt->execute()) {
        // Get the last inserted ID
        $student_id = $conn->insert_id;
        
        // Insert address information
        $address_sql = "INSERT INTO student_address (STUDENT_ID, HOUSE_STREET, BARANGAY, MUNICIPALITY, PROVINCE, ZIP_CODE) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        $addr_stmt = $conn->prepare($address_sql);
        $addr_stmt->bind_param("isssss", $student_id, $house_street, $barangay, $municipality, $province, $zip_code);
        $addr_stmt->execute();
        
        // Insert other information
        $other_sql = "INSERT INTO student_other_info (STUDENT_ID, INDIGENOUS_GROUP, RELIGION, HAS_DISABILITY, DISABILITY_TYPE) 
                      VALUES (?, ?, ?, ?, ?)";
        $other_stmt = $conn->prepare($other_sql);
        $other_stmt->bind_param("issss", $student_id, $indigenous_group, $religion, $has_disability, $disability_type);
        $other_stmt->execute();
        
        // Insert guardian information
        $guardian_sql = "INSERT INTO student_guardians (STUDENT_ID, FATHER_NAME, MOTHER_NAME, GUARDIAN_NAME) 
                         VALUES (?, ?, ?, ?)";
        $guardian_stmt = $conn->prepare($guardian_sql);
        $guardian_stmt->bind_param("isss", $student_id, $father_name, $mother_name, $guardian_name);
        $guardian_stmt->execute();
       
        
        // Redirect back to student list with success message
        header("Location: pages/studentlist.php?status=success&message=Student added successfully");
        exit();
    } else {
        // Redirect back with error message
        header("Location: pages/studentlist.php?status=error&message=Failed to add student: " . $stmt->error);
        exit();
    }
}
?>