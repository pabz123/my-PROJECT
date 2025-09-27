<?php
// php/jobs/detail.php
require_once __DIR__ . '/../db_connect.php';
$slug = $_GET['slug'] ?? null;
$id = $_GET['id'] ?? null;

if ($slug) {
    $stmt = $pdo->prepare('SELECT j.*, u.company_name FROM jobs j JOIN users u ON j.employer_id = u.id WHERE j.slug = ? LIMIT 1');
    $stmt->execute([$slug]);
} elseif ($id) {
    $stmt = $pdo->prepare('SELECT j.*, u.company_name FROM jobs j JOIN users u ON j.employer_id = u.id WHERE j.id = ? LIMIT 1');
    $stmt->execute([$id]);
} else {
    header('Location: /php/jobs/list.php'); exit;
}
$job = $stmt->fetch();
if (!$job) { echo "Job not found"; exit; }
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo htmlspecialchars($job['title']); ?></title><link rel="stylesheet" href="/assets/css/style.css"></head><body>
  <main class="container" style="padding:24px">
    <h1><?php echo htmlspecialchars($job['title']); ?></h1>
    <p><strong><?php echo htmlspecialchars($job['company_name']); ?></strong> — <?php echo htmlspecialchars($job['location']); ?></p>
    <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>

    <h3>Requirements</h3>
    <p><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>

    <h3>Responsibilities</h3>
    <p><?php echo nl2br(htmlspecialchars($job['responsibilities'])); ?></p>

    <p><a class="btn btn-primary" href="/php/jobs/apply.php?id=<?php echo htmlspecialchars($job['id']); ?>">Apply now</a></p>
  </main>
</body></html>
