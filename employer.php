<?php
// dashboard/employer.php

require_once __DIR__ . '/../db_connect.php';
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'employer') {
    header('Location: /php/auth/login.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

// fetch jobs posted by this employer
$stmt = $pdo->prepare('SELECT * FROM jobs WHERE employer_id = ? ORDER BY created_at DESC');
$stmt->execute([$uid]);
$jobs = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Employer Dashboard</title><link rel="stylesheet" href="/assets/css/style.css"></head>
<body>
  <header class="site-header"><div class="container header-inner"><a class="brand" href="/">JobIntern</a><div>Hello, <?php echo htmlspecialchars($_SESSION['name']); ?> | <a href="/php/auth/logout.php">Logout</a></div></div></header>
  <main class="container" style="padding:24px">
    <?php if ($flash): ?><div class="alert"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>
    <h1>Employer Dashboard</h1>
    <p><a class="btn btn-primary" href="/php/employer/post_job.php">Post a new job</a></p>

    <h2>Your listings</h2>
    <?php if (empty($jobs)): ?>
      <p>You have no active jobs. Post one to get applicants.</p>
    <?php else: ?>
      <ul>
        <?php foreach($jobs as $j): ?>
          <li>
            <strong><?php echo htmlspecialchars($j['title']); ?></strong> — <?php echo htmlspecialchars($j['type']); ?> — <?php echo htmlspecialchars($j['status']); ?>
            <div><a class="btn btn-outline" href="/php/jobs/detail.php?slug=<?php echo htmlspecialchars($j['slug']); ?>">View</a></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </main>
</body>
</html>
