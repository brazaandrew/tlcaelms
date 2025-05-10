<?php
include("tlcaelmsdb.php");
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
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <title>Login | TLCA</title>
  
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
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8f9fa;
      color: #333;
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .login-container {
      display: flex;
      max-width: 900px;
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .login-image {
      flex: 1;
      background-image: url('images/frontImg1.png');
      background-size: cover;
      background-position: center;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .login-image::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(44, 62, 80, 0.6);
    }
    
    .school-logo {
      position: relative;
      z-index: 2;
      max-width: 80%;
      max-height: 150px;
    }
    
    .login-form {
      flex: 1;
      padding: 40px;
    }
    
    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .login-header h2 {
      color: var(--secondary-color);
      font-weight: 600;
      margin-bottom: 10px;
    }
    
    .login-header p {
      color: #777;
      font-size: 14px;
    }
    
    .form-floating {
      margin-bottom: 20px;
    }
    
    .form-control {
      border-radius: 8px;
      padding: 12px 15px;
      height: auto;
      font-size: 14px;
      border: 1px solid #ddd;
      transition: all 0.3s;
    }
    
    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }
    
    .form-floating > label {
      padding: 12px 15px;
    }
    
    .btn-login {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      color: white;
      border-radius: 8px;
      padding: 12px 15px;
      font-weight: 500;
      width: 100%;
      transition: all 0.3s;
    }
    
    .btn-login:hover {
      background-color: #2980b9;
      border-color: #2980b9;
    }
    
    .forgot-password {
      text-align: right;
      margin-bottom: 20px;
    }
    
    .forgot-password a {
      color: var(--primary-color);
      font-size: 14px;
      text-decoration: none;
      transition: all 0.3s;
    }
    
    .forgot-password a:hover {
      color: #2980b9;
      text-decoration: underline;
    }
    
    .login-footer {
      text-align: center;
      margin-top: 30px;
      color: #777;
      font-size: 14px;
    }
    
    .error-message {
      background-color: rgba(231, 76, 60, 0.1);
      color: var(--danger-color);
      padding: 12px 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
      display: flex;
      align-items: center;
    }
    
    .error-message i {
      margin-right: 10px;
      font-size: 16px;
    }
    
    /* Responsive Styles */
    @media (max-width: 768px) {
      .login-container {
        flex-direction: column;
        max-width: 400px;
      }
      
      .login-image {
        height: 200px;
      }
      
      .login-form {
        padding: 30px 20px;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="login-container">
      <div class="login-image">
        <!-- <img src="images/tlca.png" alt="TLCA Logo" class="school-logo"> -->
      </div>
      
      <div class="login-form">
        <div class="login-header">
          <h2>Welcome Back</h2>
          <p>Please login to access your account</p>
        </div>
        
        <?php
        if (isset($_SESSION['login_error'])) {
          echo '<div class="error-message">
                  <i class="fas fa-exclamation-circle"></i>
                  ' . $_SESSION['login_error'] . '
                </div>';
          unset($_SESSION['login_error']); // clear message after showing it once
        }
        ?>
        
        <form action="./tlcaelmsdb.php" method="post">
          <div class="form-floating mb-3">
            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your employee ID" required>
            <label for="username"><i class="fas fa-id-card me-2"></i>Employee ID</label>
          </div>
          
          <div class="form-floating mb-3">
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
          </div>
          
          <div class="forgot-password">
            <a href="#">Forgot password?</a>
          </div>
          
          <button type="submit" class="btn btn-login">
            <i class="fas fa-sign-in-alt me-2"></i>Login
          </button>
        </form>
        
        <div class="login-footer">
          <p>&copy; <?php echo date('Y'); ?> TLCA Education Learning Management System</p>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>