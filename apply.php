<?php
// php/jobs/apply.php

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../helpers/csrf.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: /php/auth/login.php');
    exit;
}

$job_id = (int)($_GET['id'] ?? $_POST['job_id'] ?? 0);
if (!$job_id) { header('Location: /php/jobs/list.php'); exit; }

// fetch job
$stmt = $pdo->prepare('SELECT j.*, u.company_name FROM jobs j JOIN users u ON j.employer_id=u.id WHERE j.id=? LIMIT 1');
$stmt->execute([$job_id]);
$job = $stmt->fetch();
if (!$job) { echo "Job not found"; exit; }

// POST: handle application
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = 'Invalid form token.';
        header("Location: /php/jobs/detail.php?id={$job_id}");
        exit;
    }

    $cover = trim($_POST['cover_letter'] ?? '');

    // check if already applied
    $check = $pdo->prepare('SELECT id FROM applications WHERE job_id=? AND student_id=? LIMIT 1');
    $check->execute([$job_id, $_SESSION['user_id']]);
    if ($check->fetch()) {
        $_SESSION['flash'] = 'You have already applied to this job.';
        header("Location: /php/jobs/detail.php?id={$job_id}");
        exit;
    }

    // optional resume snapshot upload
    $resume_snapshot = null;
    if (!empty($_FILES['resume_snapshot']['name']) && $_FILES['resume_snapshot']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['resume_snapshot'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $f['tmp_name']); finfo_close($finfo);
        if ($mime === 'application/pdf' && $f['size'] <= 2*1024*1024) {
            $uploadDir = __DIR__ . '/../assets/uploads/applications/';
            if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
            $basename = bin2hex(random_bytes(12)) . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/','_',basename($f['name']));
            $dest = $uploadDir . $basename;
            if (move_uploaded_file($f['tmp_name'],$dest)) {
                $resume_snapshot = 'assets/uploads/applications/' . $basename;
            }
        }
    }

    $insert = $pdo->prepare('INSERT INTO applications (job_id, student_id, cover_letter, resume_snapshot) VALUES (?, ?, ?, ?)');
    $insert->execute([$job_id, $_SESSION['user_id'], $cover, $resume_snapshot]);
    $_SESSION['flash'] = 'Application submitted.';
    header("Location: /dashboard/student.php");
    exit;
}

// show application form
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Apply — <?php echo htmlspecialchars($job['title']); ?></title><link rel="stylesheet" href="/assets/css/style.css"></head><body>
  <main class="container" style="padding:24px;max-width:720px">
    <h1>Apply to <?php echo htmlspecialchars($job['title']); ?></h1>
    <?php if ($flash) echo '<div class="alert">'.htmlspecialchars($flash).'</div>'; ?>
    <form method="post" enctype="multipart/form-data">
      <?php echo csrf_input_field(); ?>
      <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">
      <label>Cover letter (optional) <textarea name="cover_letter" rows="6"></textarea></label>
      <label>Attach resume (optional, PDF) <input name="resume_snapshot" type="file" accept=".pdf"></label>
      <button class="btn btn-primary" type="submit">Submit application</button>
    </form>
  </main>
</body></html>
