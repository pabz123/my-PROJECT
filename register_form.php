<?php
// php/register_form.php

require_once __DIR__ . '/helpers/csrf.php';
$role = $_GET['role'] ?? 'student';
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Sign Up — JobIntern</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <main class="container" style="max-width:720px;padding:24px">
    <h1>Create account</h1>
    <?php if($errors): ?>
      <div class="alert alert-error">
        <ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
      </div>
    <?php endif; ?>

    <form id="registerForm" action="/php/auth/register.php" method="post" enctype="multipart/form-data" novalidate>
      <?php echo csrf_input_field(); ?>

      <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">

      <label>
        Full name
        <input name="name" type="text" value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>" required>
      </label>

      <label>
        Email
        <input name="email" type="email" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" required>
      </label>

      <label>
        Password (min 8 chars)
        <input name="password" type="password" minlength="8" required>
      </label>

      <label>
        Confirm Password
        <input name="password_confirm" type="password" minlength="8" required>
      </label>

      <?php if ($role === 'student'): ?>
        <label>University
          <input name="university" type="text" value="<?php echo htmlspecialchars($old['university'] ?? ''); ?>">
        </label>
        <label>Course
          <input name="course" type="text" value="<?php echo htmlspecialchars($old['course'] ?? ''); ?>">
        </label>
        <label>Upload CV (PDF, max 2MB)
          <input name="resume" type="file" accept=".pdf,application/pdf">
        </label>
      <?php else: ?>
        <label>Company name
          <input name="company_name" type="text" value="<?php echo htmlspecialchars($old['company_name'] ?? ''); ?>">
        </label>
      <?php endif; ?>

      <button class="btn btn-primary" type="submit">Create account</button>
    </form>

    <p style="margin-top:12px">Already have an account? <a href="/php/auth/login.php">Login</a></p>
  </main>

  <script>
  // client-side minimal validation
  document.getElementById('registerForm').addEventListener('submit', function(e){
    const p = this.querySelector('input[name="password"]').value;
    const pc = this.querySelector('input[name="password_confirm"]').value;
    if (p !== pc) {
      e.preventDefault();
      alert('Passwords do not match.');
      return;
    }
    if (p.length < 8) {
      e.preventDefault();
      alert('Password must be at least 8 characters.');
      return;
    }
  });
  </script>
</body>
</html>
