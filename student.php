<?php
// dashboard/student.php

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../helpers/csrf.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: /php/auth/login.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

// handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = 'Invalid form token.';
        header('Location: /dashboard/student.php');
        exit;
    }
    $name = trim($_POST['name'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if ($name === '') {
        $_SESSION['flash'] = 'Name cannot be empty.';
        header('Location: /dashboard/student.php');
        exit;
    }

    // handle resume upload optional
    if (!empty($_FILES['resume']['name']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['resume'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $f['tmp_name']); finfo_close($finfo);
        if ($mime !== 'application/pdf' || $f['size'] > 2*1024*1024) {
            $_SESSION['flash'] = 'Resume must be a PDF under 2MB.';
            header('Location: /dashboard/student.php');
            exit;
        }
        $uploadDir = __DIR__ . '/../assets/uploads/resumes/';
        if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
        $basename = bin2hex(random_bytes(12)) . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/','_',basename($f['name']));
        $dest = $uploadDir . $basename;
        if (move_uploaded_file($f['tmp_name'],$dest)) {
            $resume_path = 'assets/uploads/resumes/' . $basename;
            // update DB including resume_path
            $stmt = $pdo->prepare('UPDATE users SET name=?, university=?, course=?, bio=?, resume_path=? WHERE id=?');
            $stmt->execute([$name,$university,$course,$bio,$resume_path,$uid]);
        } else {
            $_SESSION['flash'] = 'Could not upload resume.';
            header('Location: /dashboard/student.php');
            exit;
        }
    } else {
        $stmt = $pdo->prepare('UPDATE users SET name=?, university=?, course=?, bio=? WHERE id=?');
        $stmt->execute([$name,$university,$course,$bio,$uid]);
    }

    $_SESSION['flash'] = 'Profile updated.';
    header('Location: /dashboard/student.php');
    exit;
}

// fetch profile
$stmt = $pdo->prepare('SELECT id,name,email,university,course,resume_path,bio FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$uid]);
$user = $stmt->fetch();

// fetch applications
$stmt = $pdo->prepare('SELECT a.*, j.title, j.slug, u.company_name FROM applications a JOIN jobs j ON a.job_id=j.id JOIN users u ON j.employer_id = u.id WHERE a.student_id = ? ORDER BY a.applied_at DESC');
$stmt->execute([$uid]);
$applications = $stmt->fetchAll();

// fetch saved jobs
$stmt = $pdo->prepare('SELECT s.saved_at, j.* FROM saved_jobs s JOIN jobs j ON s.job_id=j.id WHERE s.student_id = ? ORDER BY s.saved_at DESC');
$stmt->execute([$uid]);
$saved = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student Dashboard — JobIntern</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="/">JobIntern</a>
      <nav class="site-nav"><ul><li><a href="/">Home</a></li></ul></nav>
      <div>
        Hello, <?php echo htmlspecialchars($_SESSION['name']); ?> |
        <a href="/php/auth/logout.php">Logout</a>
      </div>
    </div>
  </header>

  <main class="container" style="max-width:1100px;padding:24px">
    <?php if ($flash): ?><div class="alert"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:320px 1fr;gap:16px">
      <aside style="background:#fff;padding:12px;border-radius:8px">
        <h3>Profile</h3>
        <p><strong><?php echo htmlspecialchars($user['name']); ?></strong></p>
        <p><?php echo htmlspecialchars($user['university'] ?? ''); ?></p>
        <?php if ($user['resume_path']): ?>
          <p><a href="/<?php echo htmlspecialchars($user['resume_path']); ?>" target="_blank">View resume</a></p>
        <?php endif; ?>
      </aside>

      <section>
        <h2>Edit profile</h2>
        <form method="post" enctype="multipart/form-data">
          <?php echo csrf_input_field(); ?>
          <input type="hidden" name="action" value="update_profile">
          <label>Name <input name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required></label>
          <label>University <input name="university" value="<?php echo htmlspecialchars($user['university']); ?>"></label>
          <label>Course <input name="course" value="<?php echo htmlspecialchars($user['course']); ?>"></label>
          <label>Bio <textarea name="bio" rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea></label>
          <label>Upload resume (PDF, optional) <input name="resume" type="file" accept=".pdf"></label>
          <button class="btn btn-primary" type="submit">Save profile</button>
        </form>

        <hr style="margin:18px 0">

        <h2>Saved jobs</h2>
        <?php if (empty($saved)): ?>
          <p>No saved jobs yet. Browse <a href="/index.html#jobs">jobs</a>.</p>
        <?php else: ?>
          <ul>
            <?php foreach($saved as $s): ?>
              <li>
                <strong><?php echo htmlspecialchars($s['title']); ?></strong> — <?php echo htmlspecialchars($s['location']); ?>
                <div style="margin-top:4px"><a class="btn btn-outline" href="/php/jobs/detail.php?slug=<?php echo htmlspecialchars($s['slug']); ?>">View</a></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <hr style="margin:18px 0">

        <h2>Application history</h2>
        <?php if (empty($applications)): ?>
          <p>You haven't applied to any jobs yet.</p>
        <?php else: ?>
          <table style="width:100%;background:#fff;border-radius:8px;padding:8px">
            <thead>
              <tr>
                <th>Job</th>
                <th>Company</th>
                <th>Status</th>
                <th>Applied</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($applications as $a): ?>
                <tr>
                  <td><a href="/php/jobs/detail.php?slug=<?php echo htmlspecialchars($a['slug']); ?>"><?php echo htmlspecialchars($a['title']); ?></a></td>
                  <td><?php echo htmlspecialchars($a['company_name']); ?></td>
                  <td><?php echo htmlspecialchars($a['status']); ?></td>
                  <td><?php echo htmlspecialchars($a['applied_at']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>

      </section>
    </div>
  </main>
</body>
</html>
