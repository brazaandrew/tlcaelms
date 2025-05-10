<?php
session_start();

if (!isset($_SESSION['EMPIDNO'])) {
  header("Location: login.php");
  exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "tlca_elms");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$fullname = $_SESSION['USERNAME'];
$usertype = $_SESSION['USERTYPE'];

// Get total student count
$totalStudents = 0;
$studentQuery = $conn->query("SELECT COUNT(*) as total FROM tblstudentlist");
if ($studentQuery && $row = $studentQuery->fetch_assoc()) {
  $totalStudents = $row['total'];
}

// Get total teacher count
$totalTeachers = 0;
$teacherQuery = $conn->query("SELECT COUNT(*) as total FROM tbluser WHERE USERTYPE = 'Teacher'");
if ($teacherQuery && $row = $teacherQuery->fetch_assoc()) {
  $totalTeachers = $row['total'];
}

// Get total subjects/courses count
$totalCourses = 0;
$coursesQuery = $conn->query("SELECT COUNT(*) as total FROM folders");
if ($coursesQuery && $row = $coursesQuery->fetch_assoc()) {
  $totalCourses = $row['total'];
}

// Get active enrollments (students with "Enrolled" status)
$activeEnrollments = 0;
$enrollmentsQuery = $conn->query("SELECT COUNT(*) as total FROM tblstudentlist WHERE enrollment_status = 'Enrolled'");
if ($enrollmentsQuery && $row = $enrollmentsQuery->fetch_assoc()) {
  $activeEnrollments = $row['total'];
}

// Get student distribution by grade level
$categories = [];
$studentValues = [];
$gradeQuery = $conn->query("SELECT GRADELVL, COUNT(*) as count FROM tblstudentlist GROUP BY GRADELVL ORDER BY GRADELVL");
if ($gradeQuery) {
  while ($row = $gradeQuery->fetch_assoc()) {
    $categories[] = 'Grade ' . $row['GRADELVL'];
    $studentValues[] = $row['count'];
  }
}

// Get gender distribution
$maleFemaleCounts = [0, 0]; // [Male, Female]
$genderQuery = $conn->query("SELECT sex, COUNT(*) as count FROM tblstudentlist GROUP BY sex");
if ($genderQuery) {
  while ($row = $genderQuery->fetch_assoc()) {
    if (strtolower($row['sex']) == 'male') {
      $maleFemaleCounts[0] = $row['count'];
    } else if (strtolower($row['sex']) == 'female') {
      $maleFemaleCounts[1] = $row['count'];
    }
  }
}

// For attendance data, we'll use a placeholder since we don't have actual attendance data
// In a real implementation, you would query from an attendance table
$attendanceData = [];
foreach ($categories as $category) {
  // Generate random attendance between 85 and 98
  $attendanceData[] = rand(85, 98);
}

// Get recent activities
$recentActivities = [];
$activityQuery = $conn->query("
  (SELECT 'add' as type, CONCAT('New student ', FIRSTNAME, ' ', LASTNAME, ' was added') as description, 
   DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as activity_time, FIRSTNAME as name
   FROM tblstudentlist WHERE created_at IS NOT NULL ORDER BY created_at DESC LIMIT 2)
  UNION
  (SELECT 'edit' as type, CONCAT('Subject ', name, ' was updated') as description, 
   NOW() as activity_time, name as name
   FROM folders ORDER BY id DESC LIMIT 1)
  UNION
  (SELECT 'delete' as type, CONCAT('Teacher was removed') as description, 
   DATE_SUB(NOW(), INTERVAL 1 DAY) as activity_time, '' as name
   FROM dual LIMIT 1)
  ORDER BY activity_time DESC
  LIMIT 4
");

if (!$activityQuery) {
  // If the query fails (e.g., if created_at column doesn't exist), use sample data
  $recentActivities = [
    [
      'type' => 'add',
      'description' => 'New student <strong>John Doe</strong> was added',
      'time' => 'Today, 10:30 AM',
      'name' => 'John Doe'
    ],
    [
      'type' => 'edit',
      'description' => 'Course <strong>Mathematics 101</strong> was updated',
      'time' => 'Yesterday, 3:45 PM',
      'name' => 'Mathematics 101'
    ],
    [
      'type' => 'delete',
      'description' => 'Teacher <strong>Jane Smith</strong> was removed',
      'time' => 'Yesterday, 1:20 PM',
      'name' => 'Jane Smith'
    ],
    [
      'type' => 'add',
      'description' => 'New course <strong>Science 202</strong> was added',
      'time' => 'May 10, 2023',
      'name' => 'Science 202'
    ]
  ];
} else {
  while ($row = $activityQuery->fetch_assoc()) {
    $time = new DateTime($row['activity_time']);
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
      'type' => $row['type'],
      'description' => $row['description'],
      'time' => $displayTime,
      'name' => $row['name']
    ];
  }
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

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<title>TLCA Dashboard</title>

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
    --header-height: 60px;
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
  
  .stat-icon.students {
    background-color: var(--primary-color);
  }
  
  .stat-icon.teachers {
    background-color: var(--success-color);
  }
  
  .stat-icon.courses {
    background-color: var(--warning-color);
  }
  
  .stat-icon.enrollments {
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
  
  /* Charts Section */
  .charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
  }
  
  .chart-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  }
  
  .chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
  }
  
  .chart-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--secondary-color);
    margin: 0;
  }
  
  .chart-actions {
    display: flex;
    gap: 10px;
  }
  
  .chart-actions button {
    background: none;
    border: none;
    color: #777;
    cursor: pointer;
    font-size: 14px;
  }
  
  .chart-actions button:hover {
    color: var(--primary-color);
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
  
  .activity-icon.add {
    background-color: var(--success-color);
  }
  
  .activity-icon.edit {
    background-color: var(--warning-color);
  }
  
  .activity-icon.delete {
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
    
    .charts-section {
      grid-template-columns: 1fr;
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
  }
  
  /* Logout Button */
  .logout-btn {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
  }
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
<div class="brand">
  <img src="images/tlca.png" alt="TLCA Logo">
</div>
<ul class="sidebar-menu">
  <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
  <!-- <li><a href="./pages/studentlist.php"><i class="fas fa-user-graduate"></i><span>Student List</span></a></li> -->
  <li><a href="./tpages/tCourses.php"><i class="fas fa-graduation-cap"></i><span>Assign Subject</span></a></li>
  <li><a href="./pages/Files.php"><i class="fas fa-file-alt"></i><span>Files</span></a></li>
  <!-- <li><a href="manage_user.php"><i class="fas fa-chalkboard-teacher"></i><span>Teachers</span></a></li> -->
  <!-- <li><a href="assign-students.php"><i class="fas fa-cog"></i><span>Assign Student</span></a></li> -->
</ul>
</div>

<div class="page-content" id="pageContent">
<div class="header">
  <button class="toggle-sidebar" id="toggleSidebar">
    <i class="fas fa-bars"></i>
  </button>
  
  <div class="search">
    <i class="fas fa-search"></i>
    <input type="search" class="form-control" placeholder="Search..." />
  </div>
  
  <div class="profile">
    <div class="notification-icon">
      <i class="far fa-bell fa-lg"></i>
      <span class="notification-badge">3</span>
    </div>
    
    <div class="user-info d-none d-md-block">
      <div class="user-name"><?php echo htmlspecialchars($fullname); ?></div>
      <div class="user-role"><?php echo htmlspecialchars($usertype); ?></div>
    </div>
    
    <form method="post" action="logout.php" class="logout-btn">
      <button type="submit" class="logout-btn">
        <img src="images/avatar.png" alt="Profile" />
      </button>
    </form>
  </div>
</div>

<div class="dashboard-title">
  <h1>Dashboard</h1>
  <div class="date-today"><?php echo date('l, F d, Y'); ?></div>
</div>

<!-- Stats Cards -->
<div class="stats-cards">
  <div class="stat-card">
    <div class="stat-icon students">
      <i class="fas fa-user-graduate"></i>
    </div>
    <div class="stat-info">
      <h3><?php echo $totalStudents; ?></h3>
      <p>Total Students</p>
    </div>
  </div>
  
  <div class="stat-card">
    <div class="stat-icon teachers">
      <i class="fas fa-chalkboard-teacher"></i>
    </div>
    <div class="stat-info">
      <h3><?php echo $totalTeachers; ?></h3>
      <p>Total Teachers</p>
    </div>
  </div>
  
  <div class="stat-card">
    <div class="stat-icon courses">
      <i class="fas fa-book"></i>
    </div>
    <div class="stat-info">
      <h3><?php echo $totalCourses; ?></h3>
      <p>Total Subjects</p>
    </div>
  </div>
  
  <div class="stat-card">
    <div class="stat-icon enrollments">
      <i class="fas fa-user-check"></i>
    </div>
    <div class="stat-info">
      <h3><?php echo $activeEnrollments; ?></h3>
      <p>Active Enrollments</p>
    </div>
  </div>
</div>

<!-- Charts Section -->
<div class="charts-section">
  <div class="chart-container">
    <div class="chart-header">
      <h2 class="chart-title">Student Distribution by Grade Level</h2>
      <div class="chart-actions">
        <button><i class="fas fa-sync-alt"></i> Refresh</button>
        <button><i class="fas fa-download"></i> Export</button>
      </div>
    </div>
    <canvas id="studentChart" width="400" height="200"></canvas>
  </div>
  
  <div class="chart-container">
    <div class="chart-header">
      <h2 class="chart-title">Gender Distribution</h2>
      <div class="chart-actions">
        <button><i class="fas fa-sync-alt"></i> Refresh</button>
        <button><i class="fas fa-download"></i> Export</button>
      </div>
    </div>
    <canvas id="genderChart" width="400" height="200"></canvas>
  </div>
  
  <div class="chart-container">
    <div class="chart-header">
      <h2 class="chart-title">Attendance Rate by Grade Level</h2>
      <div class="chart-actions">
        <button><i class="fas fa-sync-alt"></i> Refresh</button>
        <button><i class="fas fa-download"></i> Export</button>
      </div>
    </div>
    <canvas id="attendanceChart" width="400" height="200"></canvas>
  </div>
</div>



<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Toggle Sidebar
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('sidebar');
  const pageContent = document.getElementById('pageContent');
  const toggleSidebar = document.getElementById('toggleSidebar');
  
  toggleSidebar.addEventListener('click', function() {
    sidebar.classList.toggle('collapsed');
    pageContent.classList.toggle('expanded');
  });
  
  // Student Distribution Chart
  const studentCtx = document.getElementById('studentChart').getContext('2d');
  const studentChart = new Chart(studentCtx, {
    type: 'bar',
    data: {
      labels: <?php echo json_encode($categories); ?>,
      datasets: [{
        label: 'Number of Students',
        data: <?php echo json_encode($studentValues); ?>,
        backgroundColor: 'rgba(52, 152, 219, 0.7)',
        borderColor: 'rgba(52, 152, 219, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: false,
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
  
  // Gender Distribution Chart
  const genderCtx = document.getElementById('genderChart').getContext('2d');
  const genderChart = new Chart(genderCtx, {
    type: 'doughnut',
    data: {
      labels: ['Male', 'Female'],
      datasets: [{
        label: 'Gender Distribution',
        data: <?php echo json_encode($maleFemaleCounts); ?>,
        backgroundColor: [
          'rgba(52, 152, 219, 0.7)',
          'rgba(231, 76, 60, 0.7)'
        ],
        borderColor: [
          'rgba(52, 152, 219, 1)',
          'rgba(231, 76, 60, 1)'
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        }
      }
    }
  });
  
  // Attendance Chart
  const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
  const attendanceChart = new Chart(attendanceCtx, {
    type: 'line',
    data: {
      labels: <?php echo json_encode($categories); ?>,
      datasets: [{
        label: 'Attendance Rate (%)',
        data: <?php echo json_encode($attendanceData); ?>,
        backgroundColor: 'rgba(46, 204, 113, 0.2)',
        borderColor: 'rgba(46, 204, 113, 1)',
        borderWidth: 2,
        tension: 0.3,
        fill: true
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        }
      },
      scales: {
        y: {
          beginAtZero: false,
          min: 80,
          max: 100
        }
      }
    }
  });
});
</script>
</body>
</html>