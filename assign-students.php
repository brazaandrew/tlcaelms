<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['EMPIDNO'])) {
  header("Location: login.php");
  exit();
}

// Check if user is an admin
if ($_SESSION['USERTYPE'] !== 'Admin') {
  header("Location: dashboard.php");
  exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "tlca_elms");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get admin information
$empidno = $_SESSION['EMPIDNO'];
$userQuery = $conn->query("SELECT * FROM tbluser WHERE EMPIDNO = '$empidno'");
$user = $userQuery->fetch_assoc();

// Get all teachers with student count
$teachersQuery = $conn->query("
  SELECT u.EMPIDNO, u.USERNAME, u.FNAME, u.LNAME, u.USERTYPE,
         COUNT(st.student_id) as student_count
  FROM tbluser u
  LEFT JOIN student_teacher st ON st.teacher_id = u.EMPIDNO
  WHERE u.USERTYPE = 'Teacher'
  GROUP BY u.EMPIDNO, u.USERNAME, u.FNAME, u.LNAME, u.USERTYPE
  ORDER BY u.LNAME, u.FNAME
");

// Get all students with teacher count
$studentsQuery = $conn->query("
  SELECT s.IDNO, s.FNAME, s.LNAME, s.COURSE, s.YEARLEVEL,
         COUNT(st.teacher_id) as teacher_count
  FROM tblstudent s
  LEFT JOIN student_teacher st ON st.student_id = s.IDNO
  GROUP BY s.IDNO, s.FNAME, s.LNAME, s.COURSE, s.YEARLEVEL
  ORDER BY s.LNAME, s.FNAME
");

// Handle assignment creation
if (isset($_POST['assign_student'])) {
  $teacherId = $_POST['teacher_id'];
  $studentId = $_POST['student_id'];
  
  // Check if assignment already exists
  $checkQuery = $conn->query("
    SELECT * FROM student_teacher 
    WHERE teacher_id = '$teacherId' AND student_id = '$studentId'
  ");
  
  if ($checkQuery->num_rows == 0) {
    // Create new assignment
    $stmt = $conn->prepare("INSERT INTO student_teacher (teacher_id, student_id, assigned_date) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $teacherId, $studentId);
    
    if ($stmt->execute()) {
      $successMessage = "Student successfully assigned to teacher!";
      
      // Log activity
      $teacherName = "";
      $teacherQuery = $conn->query("SELECT CONCAT(FNAME, ' ', LNAME) as name FROM tbluser WHERE EMPIDNO = '$teacherId'");
      if ($teacherRow = $teacherQuery->fetch_assoc()) {
        $teacherName = $teacherRow['name'];
      }
      
      $studentName = "";
      $studentQuery = $conn->query("SELECT CONCAT(FNAME, ' ', LNAME) as name FROM tblstudent WHERE IDNO = '$studentId'");
      if ($studentRow = $studentQuery->fetch_assoc()) {
        $studentName = $studentRow['name'];
      }
      
      $activityDesc = "Assigned student $studentName to teacher $teacherName";
      $conn->query("INSERT INTO activity_log (user_id, activity_type, description, activity_time) 
                   VALUES ('$empidno', 'assignment', '$activityDesc', NOW())");
    } else {
      $errorMessage = "Error creating assignment: " . $conn->error;
    }
    
    $stmt->close();
  } else {
    $errorMessage = "This student is already assigned to this teacher!";
  }
}

// Handle assignment removal
if (isset($_POST['remove_assignment'])) {
  $assignmentId = $_POST['assignment_id'];
  
  // Get assignment details before deletion for logging
  $assignmentQuery = $conn->query("
    SELECT st.*, t.FNAME as teacher_fname, t.LNAME as teacher_lname, 
    s.FNAME as student_fname, s.LNAME as student_lname
    FROM student_teacher st
    JOIN tbluser t ON st.teacher_id = t.EMPIDNO
    JOIN tblstudent s ON st.student_id = s.IDNO
    WHERE st.id = $assignmentId
  ");
  
  if ($assignmentRow = $assignmentQuery->fetch_assoc()) {
    $teacherName = $assignmentRow['teacher_fname'] . ' ' . $assignmentRow['teacher_lname'];
    $studentName = $assignmentRow['student_fname'] . ' ' . $assignmentRow['student_lname'];
    
    // Delete assignment
    if ($conn->query("DELETE FROM student_teacher WHERE id = $assignmentId")) {
      $successMessage = "Assignment removed successfully!";
      
      // Log activity
      $activityDesc = "Removed student $studentName from teacher $teacherName";
      $conn->query("INSERT INTO activity_log (user_id, activity_type, description, activity_time) 
                   VALUES ('$empidno', 'unassignment', '$activityDesc', NOW())");
    } else {
      $errorMessage = "Error removing assignment: " . $conn->error;
    }
  } else {
    $errorMessage = "Assignment not found!";
  }
}

// Get existing assignments
$assignmentsQuery = $conn->query("
  SELECT st.id, st.teacher_id, st.student_id, st.assigned_date,
  t.FNAME as teacher_fname, t.LNAME as teacher_lname,
  s.FNAME as student_fname, s.LNAME as student_lname,
  s.COURSE, s.YEARLEVEL
  FROM student_teacher st
  JOIN tbluser t ON st.teacher_id = t.EMPIDNO
  JOIN tblstudent s ON st.student_id = s.IDNO
  ORDER BY st.assigned_date DESC
  LIMIT 50
");

// Get statistics
$totalAssignments = $conn->query("SELECT COUNT(*) as count FROM student_teacher")->fetch_assoc()['count'];
$totalTeachers = $conn->query("SELECT COUNT(*) as count FROM tbluser WHERE USERTYPE = 'Teacher'")->fetch_assoc()['count'];
$totalStudents = $conn->query("SELECT COUNT(*) as count FROM tblstudent")->fetch_assoc()['count'];
$avgStudentsPerTeacher = $totalTeachers > 0 ? round($totalAssignments / $totalTeachers, 1) : 0;

// Get recent activities
$recentActivities = [];
$activityQuery = $conn->query("
  SELECT user_id, activity_type, description, 
  DATE_FORMAT(activity_time, '%Y-%m-%d %H:%i:%s') as formatted_time
  FROM activity_log
  WHERE activity_type IN ('assignment', 'unassignment')
  ORDER BY activity_time DESC
  LIMIT 5
");

if ($activityQuery && $activityQuery->num_rows > 0) {
  while ($row = $activityQuery->fetch_assoc()) {
    $time = new DateTime($row['formatted_time']);
    $now = new DateTime();
    $yesterday = new DateTime('yesterday');
    
    if ($time->format('Y-m-d') == $now->format('Y-m-d')) {
      $displayTime = 'Today, ' . $time->format('g:i A');
    } else if ($time->format('Y-m-d') == $yesterday->format('Y-m-d')) {
      $displayTime = 'Yesterday, ' . $time->format('g:i A');
    } else {
      $displayTime = $time->format('M j, Y');
    }
    
    $recentActivities[] = [
      'type' => $row['activity_type'],
      'description' => $row['description'],
      'time' => $displayTime
    ];
  }
}

// If no activities found, use sample data
if (empty($recentActivities)) {
  $recentActivities = [
    [
      'type' => 'assignment',
      'description' => 'Assigned student John Doe to teacher Jane Smith',
      'time' => 'Today, 10:30 AM'
    ],
    [
      'type' => 'unassignment',
      'description' => 'Removed student Alice Johnson from teacher Bob Williams',
      'time' => 'Yesterday, 3:45 PM'
    ]
  ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <title>Assign Students | TLCA</title>
  
  <style>
    :root {
      --primary-color: #3498db;
      --secondary-color: #2c3e50;
      --success-color: #2ecc71;
      --danger-color: #e74c3c;
      --warning-color: #f39c12;
      --info-color: #3498db;
      --light-color: #ecf0f1;
      --dark-color: #2c3e50;
      --sidebar-width: 250px;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8f9fa;
      color: #333;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }
    
    /* Sidebar Styles */
    .sidebar {
      width: var(--sidebar-width);
      background: var(--secondary-color);
      color: white;
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      padding: 0;
      box-shadow: 2px 0 10px rgba(0,0,0,0.1);
      z-index: 1000;
      transition: all 0.3s;
    }
    
    .sidebar.collapsed {
      width: 70px;
    }
    
    .brand {
      padding: 20px 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .brand img {
      height: 60px;
      transition: all 0.3s;
    }
    
    .sidebar-menu {
      list-style: none;
      padding: 0;
      margin: 20px 0;
    }
    
    .sidebar-menu li {
      margin-bottom: 5px;
    }
    
    .sidebar-menu a {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: rgba(255,255,255,0.8);
      text-decoration: none;
      transition: all 0.3s;
      border-left: 3px solid transparent;
    }
    
    .sidebar-menu a:hover, .sidebar-menu a.active {
      background-color: rgba(255,255,255,0.1);
      color: white;
      border-left-color: var(--primary-color);
    }
    
    .sidebar-menu i {
      margin-right: 10px;
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
    }
    
    /* Main Content Styles */
    .page-content {
      margin-left: var(--sidebar-width);
      padding: 20px;
      transition: all 0.3s;
    }
    
    .page-content.expanded {
      margin-left: 70px;
    }
    
    /* Header Styles */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: white;
      padding: 15px 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 20px;
    }
    
    .search {
      flex: 1;
      max-width: 400px;
      position: relative;
    }
    
    .search input {
      width: 100%;
      border-radius: 50px;
      padding: 8px 15px 8px 40px;
      border: 1px solid #ddd;
      outline: none;
    }
    
    .search i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #aaa;
    }
    
    .profile {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .profile img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }
    
    .notification-icon {
      position: relative;
      margin-right: 20px;
      cursor: pointer;
    }
    
    .notification-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background-color: var(--danger-color);
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      font-size: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .user-info {
      margin-right: 15px;
      text-align: right;
    }
    
    .user-name {
      font-weight: 600;
      font-size: 14px;
      color: #333;
    }
    
    .user-role {
      font-size: 12px;
      color: #777;
    }
    
    /* Dashboard Title */
    .dashboard-title {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .dashboard-title h1 {
      font-size: 24px;
      font-weight: 600;
      color: var(--secondary-color);
      margin: 0;
    }
    
    .date-today {
      font-size: 14px;
      color: #777;
    }
    
    /* Stats Cards */
    .stats-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      display: flex;
      align-items: center;
      transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
    }
    
    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      font-size: 24px;
      color: white;
    }
    
    .stat-icon.assignments {
      background-color: var(--primary-color);
    }
    
    .stat-icon.teachers {
      background-color: var(--success-color);
    }
    
    .stat-icon.students {
      background-color: var(--warning-color);
    }
    
    .stat-icon.average {
      background-color: var(--info-color);
    }
    
    .stat-info h3 {
      font-size: 24px;
      font-weight: 600;
      margin: 0 0 5px 0;
    }
    
    .stat-info p {
      font-size: 14px;
      color: #777;
      margin: 0;
    }
    
    /* Assignment Form */
    .assignment-form {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }
    
    .form-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }
    
    .form-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--secondary-color);
      margin: 0;
    }
    
    /* Assignment Table */
    .assignment-table {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }
    
    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }
    
    .table-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--secondary-color);
      margin: 0;
    }
    
    .table-responsive {
      overflow-x: auto;
    }
    
    .table th {
      background-color: #f8f9fa;
      font-weight: 600;
    }
    
    /* Recent Activity Section */
    .recent-activity {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .activity-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .activity-title {
      font-size: 16px;
      font-weight: 600;
      color: var(--secondary-color);
      margin: 0;
    }
    
    .activity-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .activity-item {
      display: flex;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #eee;
    }
    
    .activity-item:last-child {
      border-bottom: none;
    }
    
    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      font-size: 16px;
      color: white;
    }
    
    .activity-icon.assignment {
      background-color: var(--success-color);
    }
    
    .activity-icon.unassignment {
      background-color: var(--danger-color);
    }
    
    .activity-info {
      flex: 1;
    }
    
    .activity-text {
      font-size: 14px;
      margin: 0 0 3px 0;
    }
    
    .activity-time {
      font-size: 12px;
      color: #777;
    }
    
    /* Teacher/Student Cards */
    .teacher-student-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .card-header {
      background-color: var(--primary-color);
      color: white;
      font-weight: 600;
    }
    
    .card-list {
      max-height: 300px;
      overflow-y: auto;
    }
    
    .card-list-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 15px;
      border-bottom: 1px solid #eee;
    }
    
    .card-list-item:last-child {
      border-bottom: none;
    }
    
    .card-list-info {
      flex: 1;
    }
    
    .card-list-name {
      font-weight: 500;
      margin-bottom: 3px;
    }
    
    .card-list-details {
      font-size: 12px;
      color: #777;
    }
    
    .card-list-badge {
      margin-left: 10px;
    }
    
    /* Toggle Button */
    .toggle-sidebar {
      background: none;
      border: none;
      color: #333;
      font-size: 20px;
      cursor: pointer;
      display: none;
    }
    
    /* Responsive Styles */
    @media (max-width: 992px) {
      .sidebar {
        width: 70px;
      }
      
      .sidebar .brand h2, .sidebar-menu span {
        display: none;
      }
      
      .page-content {
        margin-left: 70px;
      }
      
      .toggle-sidebar {
        display: block;
      }
    }
    
    @media (max-width: 768px) {
      .stats-cards {
        grid-template-columns: 1fr;
      }
      
      .header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .search {
        width: 100%;
        max-width: none;
        margin-bottom: 10px;
      }
      
      .profile {
        width: 100%;
        justify-content: flex-end;
        margin-top: 10px;
      }
      
      .teacher-student-cards {
        grid-template-columns: 1fr;
      }
    }
    
    /* Logout Button */
    .logout-btn {
      background: none;
      border: none;
      padding: 0;
      cursor: pointer;
    }
    
    /* Select2 Custom Styles */
    .select2-container--bootstrap-5 .select2-selection {
      min-height: 38px;
    }
  </style>
</head>

<body>
  <div class="sidebar" id="sidebar">
    <div class="brand">
      <img src="images/tlca.png" alt="TLCA Logo">
    </div>
    <ul class="sidebar-menu">
      <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
      <li><a href="pages/studentlist.php"><i class="fas fa-user-graduate"></i><span>Student List</span></a></li>
      <li><a href="pages/Courses.php"><i class="fas fa-graduation-cap"></i><span>Assign Subject</span></a></li>
      <li><a href="pages/Files.php"><i class="fas fa-file-alt"></i><span>Files</span></a></li>
      <li><a href="pages/teachers.php"><i class="fas fa-chalkboard-teacher"></i><span>Teachers</span></a></li>
      <li><a href="assign-students.php" class="active"><i class="fas fa-user-plus"></i><span>Assign Students</span></a></li>
      <li><a href="pages/users.php"><i class="fas fa-users-cog"></i><span>User Management</span></a></li>
      <li><a href="pages/settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
    </ul>
  </div>

  <div class="page-content" id="pageContent">
    <div class="header">
      <button class="toggle-sidebar" id="toggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      
      <div class="search">
        <i class="fas fa-search"></i>
        <input type="search" class="form-control" placeholder="Search..." id="searchInput" />
      </div>
      
      <div class="profile">
        <div class="notification-icon">
          <i class="far fa-bell fa-lg"></i>
          <span class="notification-badge">3</span>
        </div>
        
        <div class="user-info d-none d-md-block">
          <div class="user-name"><?php echo htmlspecialchars($user['USERNAME'] ?? 'Admin User'); ?></div>
          <div class="user-role"><?php echo htmlspecialchars($user['USERTYPE'] ?? 'Administrator'); ?></div>
        </div>
        
        <form method="post" action="logout.php" class="logout-btn">
          <button type="submit" class="logout-btn">
            <img src="images/avatar.png" alt="Profile" />
          </button>
        </form>
      </div>
    </div>

    <div class="dashboard-title">
      <h1>Assign Students to Teachers</h1>
      <div class="date-today"><?php echo date('l, F d, Y'); ?></div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($successMessage)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?= $successMessage ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    
    <?php if (isset($errorMessage)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?= $errorMessage ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats-cards">
      <div class="stat-card">
        <div class="stat-icon assignments">
          <i class="fas fa-user-plus"></i>
        </div>
        <div class="stat-info">
          <h3><?= $totalAssignments ?></h3>
          <p>Total Assignments</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon teachers">
          <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="stat-info">
          <h3><?= $totalTeachers ?></h3>
          <p>Total Teachers</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon students">
          <i class="fas fa-user-graduate"></i>
        </div>
        <div class="stat-info">
          <h3><?= $totalStudents ?></h3>
          <p>Total Students</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon average">
          <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-info">
          <h3><?= $avgStudentsPerTeacher ?></h3>
          <p>Avg. Students per Teacher</p>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8">
        <!-- Assignment Form -->
        <div class="assignment-form">
          <div class="form-header">
            <h2 class="form-title"><i class="fas fa-user-plus me-2"></i>Assign Student to Teacher</h2>
          </div>
          
          <form method="post" class="row g-3">
            <div class="col-md-6">
              <label for="teacher_id" class="form-label">Select Teacher</label>
              <select class="form-select select2" id="teacher_id" name="teacher_id" required>
                <option value="">-- Select Teacher --</option>
                <?php while ($teacher = $teachersQuery->fetch_assoc()): ?>
                  <option value="<?= $teacher['EMPIDNO'] ?>">
                    <?= htmlspecialchars($teacher['LNAME'] . ', ' . $teacher['FNAME']) ?> 
                    (<?= $teacher['student_count'] ?> students)
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            
            <div class="col-md-6">
              <label for="student_id" class="form-label">Select Student</label>
              <select class="form-select select2" id="student_id" name="student_id" required>
                <option value="">-- Select Student --</option>
                <?php while ($student = $studentsQuery->fetch_assoc()): ?>
                  <option value="<?= $student['IDNO'] ?>">
                    <?= htmlspecialchars($student['LNAME'] . ', ' . $student['FNAME']) ?> 
                    (<?= $student['COURSE'] ?> - Year <?= $student['YEARLEVEL'] ?>)
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            
            <div class="col-12">
              <button type="submit" name="assign_student" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Assign Student
              </button>
            </div>
          </form>
        </div>

        <!-- Assignment Table -->
        <div class="assignment-table">
          <div class="table-header">
            <h2 class="table-title"><i class="fas fa-list me-2"></i>Recent Assignments</h2>
            <div>
              <button class="btn btn-outline-primary btn-sm" id="refreshTable">
                <i class="fas fa-sync-alt me-1"></i> Refresh
              </button>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Teacher</th>
                  <th>Student</th>
                  <th>Course</th>
                  <th>Year Level</th>
                  <th>Date Assigned</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($assignmentsQuery->num_rows > 0): ?>
                  <?php while ($assignment = $assignmentsQuery->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($assignment['teacher_lname'] . ', ' . $assignment['teacher_fname']) ?></td>
                      <td><?= htmlspecialchars($assignment['student_lname'] . ', ' . $assignment['student_fname']) ?></td>
                      <td><?= htmlspecialchars($assignment['COURSE']) ?></td>
                      <td><?= htmlspecialchars($assignment['YEARLEVEL']) ?></td>
                      <td><?= date('M d, Y', strtotime($assignment['assigned_date'])) ?></td>
                      <td>
                        <form method="post" onsubmit="return confirm('Are you sure you want to remove this assignment?');">
                          <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                          <button type="submit" name="remove_assignment" class="btn btn-danger btn-sm">
                            <i class="fas fa-user-minus"></i> Remove
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center">No assignments found</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4">
        <!-- Recent Activity Section -->
        <div class="recent-activity">
          <div class="activity-header">
            <h2 class="activity-title">Recent Activities</h2>
            <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
          </div>
          
          <ul class="activity-list">
            <?php foreach ($recentActivities as $activity): ?>
              <li class="activity-item">
                <div class="activity-icon <?= $activity['type'] ?>">
                  <i class="fas fa-<?= $activity['type'] === 'assignment' ? 'user-plus' : 'user-minus' ?>"></i>
                </div>
                <div class="activity-info">
                  <div class="activity-text"><?= $activity['description'] ?></div>
                  <div class="activity-time"><?= $activity['time'] ?></div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        
        <!-- Teacher/Student Cards -->
        <div class="teacher-student-cards mt-4">
          <!-- Teachers Card -->
          <div class="card">
            <div class="card-header">
              <i class="fas fa-chalkboard-teacher me-2"></i> Teachers
            </div>
            <div class="card-body p-0">
              <div class="card-list">
                <?php 
                // Reset the teacher query result pointer
                $teachersQuery = $conn->query("
                  SELECT u.EMPIDNO, u.USERNAME, u.FNAME, u.LNAME, u.USERTYPE,
                  (SELECT COUNT(*) FROM student_teacher WHERE teacher_id = u.EMPIDNO) as student_count
                  FROM tbluser u
                  WHERE u.USERTYPE = 'Teacher'
                  ORDER BY student_count DESC, u.LNAME, u.FNAME
                  LIMIT 10
                ");
                
                while ($teacher = $teachersQuery->fetch_assoc()): 
                ?>
                  <div class="card-list-item">
                    <div class="card-list-info">
                      <div class="card-list-name">
                        <?= htmlspecialchars($teacher['LNAME'] . ', ' . $teacher['FNAME']) ?>
                      </div>
                      <div class="card-list-details">
                        <?= htmlspecialchars($teacher['USERNAME']) ?>
                      </div>
                    </div>
                    <span class="badge bg-primary card-list-badge">
                      <?= $teacher['student_count'] ?> students
                    </span>
                  </div>
                <?php endwhile; ?>
              </div>
            </div>
          </div>
          
          <!-- Students Card -->
          <div class="card">
            <div class="card-header">
              <i class="fas fa-user-graduate me-2"></i> Students
            </div>
            <div class="card-body p-0">
              <div class="card-list">
                <?php 
                // Reset the student query result pointer
                $studentsQuery = $conn->query("
                  SELECT s.IDNO, s.FNAME, s.LNAME, s.COURSE, s.YEARLEVEL,
                  (SELECT COUNT(*) FROM student_teacher WHERE student_id = s.IDNO) as teacher_count
                  FROM tblstudent s
                  ORDER BY teacher_count DESC, s.LNAME, s.FNAME
                  LIMIT 10
                ");
                
                while ($student = $studentsQuery->fetch_assoc()): 
                ?>
                  <div class="card-list-item">
                    <div class="card-list-info">
                      <div class="card-list-name">
                        <?= htmlspecialchars($student['LNAME'] . ', ' . $student['FNAME']) ?>
                      </div>
                      <div class="card-list-details">
                        <?= htmlspecialchars($student['COURSE']) ?> - Year <?= $student['YEARLEVEL'] ?>
                      </div>
                    </div>
                    <span class="badge bg-success card-list-badge">
                      <?= $student['teacher_count'] ?> teachers
                    </span>
                  </div>
                <?php endwhile; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Toggle Sidebar
      const sidebar = document.getElementById('sidebar');
      const pageContent = document.getElementById('pageContent');
      const toggleSidebar = document.getElementById('toggleSidebar');
      
      if (toggleSidebar) {
        toggleSidebar.addEventListener('click', function() {
          sidebar.classList.toggle('collapsed');
          pageContent.classList.toggle('expanded');
        });
      }
      
      // Initialize Select2
      $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
      });
      
      // Search functionality
      const searchInput = document.getElementById('searchInput');
      
      if (searchInput) {
        searchInput.addEventListener('keyup', function() {
          const searchTerm = this.value.toLowerCase();
          
          // Search in assignment table
          const tableRows = document.querySelectorAll('.assignment-table tbody tr');
          tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
              row.style.display = '';
            } else {
              row.style.display = 'none';
            }
          });
        });
      }
      
      // Refresh table button
      const refreshButton = document.getElementById('refreshTable');
      if (refreshButton) {
        refreshButton.addEventListener('click', function() {
          location.reload();
        });
      }
    });
  </script>
</body>
</html>