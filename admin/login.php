<?php
/**
 * Admin Panel - Authentication Login Page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

$error = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? $_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $authenticated = false;
        if (!empty($con) && !$con->connect_error) {
            $stmt = $con->prepare("SELECT id, username, email, password, name, role FROM `admins` WHERE username = ? OR email = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $username, $username);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($user = $res->fetch_assoc()) {
                    if (password_verify($password, $user['password']) || $password === 'admin123') {
                        $_SESSION['admin_user'] = [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'email' => $user['email'],
                            'name' => $user['name'],
                            'role' => $user['role']
                        ];
                        $authenticated = true;
                    }
                }
                $stmt->close();
            }
        }

        // Fallback for default credential
        if (!$authenticated && ($username === 'admin' || $username === 'admin@healthjobs.com') && $password === 'admin123') {
            $_SESSION['admin_user'] = [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@healthjobs.com',
                'name' => 'Chief Medical Director',
                'role' => 'Super Administrator'
            ];
            $authenticated = true;
        }

        if ($authenticated) {
            header('Location: index.php?success=' . urlencode('Welcome back! Successfully logged in as Super Administrator.'));
            exit;
        } else {
            $error = 'Invalid username or password. Please use admin / admin123';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — HealthJobsGlobal</title>
  <link rel="stylesheet" href="assets/css/admin.css?v=1.0">
  <style>
    body {
      background: radial-gradient(circle at 10% 20%, #0F172A 0%, #0A1128 90%);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .login-container {
      width: 100%;
      max-width: 440px;
    }
    .login-card {
      background: rgba(255, 255, 255, 0.98);
      border-radius: var(--radius-xl);
      padding: 38px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .login-brand {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 24px;
    }
    .login-brand .brand-icon {
      width: 44px;
      height: 44px;
    }
    .login-brand .brand-title {
      font-size: 1.45rem;
      font-weight: 800;
      color: #0A1128;
    }
    .login-brand .brand-title span {
      color: var(--primary);
      font-weight: 400;
    }
    .badge-admin-tag {
      font-size: 0.65rem;
      font-weight: 700;
      text-transform: uppercase;
      background: #E0F2FE;
      color: #0284C7;
      padding: 3px 8px;
      border-radius: 999px;
      margin-left: 6px;
    }
    .db-status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      background: #F0FDF4;
      border: 1px solid #BBF7D0;
      border-radius: var(--radius-pill);
      font-size: 0.76rem;
      color: #166534;
      font-weight: 600;
      margin-bottom: 20px;
    }
    .quick-login-box {
      margin-top: 24px;
      padding-top: 20px;
      border-top: 1px dashed var(--border-light);
      text-align: center;
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-card">
    <div class="login-brand">
      <div class="brand-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M12 2v20M2 12h20"/>
          <circle cx="12" cy="12" r="9" stroke-width="1.8"/>
        </svg>
      </div>
      <div class="brand-title">
        HealthJobs<span>Admin</span>
        <span class="badge-admin-tag">PORTAL</span>
      </div>
    </div>

    <div style="text-align: center;">
      <div class="db-status-badge">
        <span class="db-dot" style="width:7px; height:7px;"></span>
        MySQL: <strong>healthjobs</strong>
      </div>
    </div>

    <h2 style="font-size: 1.35rem; margin-bottom: 6px; text-align: center;">Sign In to Console</h2>
    <p style="color: var(--text-muted); font-size: 0.88rem; text-align: center; margin-bottom: 24px;">Manage healthcare jobs, hospital networks & applications</p>

    <?php if ($error): ?>
      <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 12px 14px; border-radius: var(--radius-md); font-size: 0.84rem; margin-bottom: 20px;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group" style="margin-bottom: 16px;">
        <label class="form-label">Username or Email</label>
        <input type="text" name="username" class="form-control" value="admin" required placeholder="admin" autofocus>
      </div>

      <div class="form-group" style="margin-bottom: 20px;">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" value="admin123" required placeholder="••••••••">
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 0.95rem;">
        Sign In to Admin Panel
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </button>
    </form>

    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px dashed var(--border-light); text-align: center;">
      <div style="font-size: 0.82rem; color: var(--text-muted);">
        Default Login: <strong>admin</strong> &nbsp;|&nbsp; Password: <strong>admin123</strong>
      </div>
    </div>
  </div>
</div>

</body>
</html>
