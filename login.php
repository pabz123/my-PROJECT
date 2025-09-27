<?php
// php/auth/login.php

require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // handle login
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $_SESSION['errors'] = ['Invalid form token.'];
        header('Location: /php/auth/login.php');
        exit;
    }

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $_SESSION['errors'] = ['Please provide email and password.'];
        header('Location: /php/auth/login.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, password, role, name FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['errors'] = ['Invalid credentials.'];
        header('Location: /php/auth/login.php');
        exit;
    }

    // success
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];

    // redirect by role
    if ($user['role'] === 'student') {
        header('Location: /dashboard/student.php');
    } elseif ($user['role'] === 'employer') {
        header('Location: /dashboard/employer.php');
    } else {
        header('Location: /admin/index.php');
    }
    exit;
}

// GET: show login form
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — JobIntern</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <main class="container" style="max-width:480px;padding:24px">
    <h1>Login</h1>
    <?php if ($errors): ?><div class="alert"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div><?php endif; ?>

    <form action="/php/auth/login.php" method="post" novalidate>
      <?php echo csrf_input_field(); ?>
      <label>Email <input name="email" type="email" required></label>
      <label>Password <input name="password" type="password" minlength="8" required></label>
      <button class="btn btn-primary" type="submit">Login</button>
    </form>

    <p><a href="/php/register_form.php">Create an account</a></p>
  </main>
</body>
</html>
