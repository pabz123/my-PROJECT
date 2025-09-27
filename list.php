<?php
// php/jobs/list.php
require_once __DIR__ . '/../db_connect.php';
$q = trim($_GET['q'] ?? '');
$location = trim($_GET['location'] ?? '');

$sql = 'SELECT j.*, u.company_name FROM jobs j JOIN users u ON j.employer_id = u.id WHERE j.status = "active"';
$params = [];

if ($q !== '') {
    $sql .= ' AND (j.title LIKE ? OR j.description LIKE ? OR u.company_name LIKE ?)';
    $qq = "%$q%";
    $params[] = $qq; $params[] = $qq; $params[] = $qq;
}
if ($location !== '') {
    $sql .= ' AND j.location LIKE ?';
    $params[] = "%$location%";
}
$sql .= ' ORDER BY j.created_at DESC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Jobs</title><link rel="stylesheet" href="/assets/css/style.css"></head><body>
  <main class="container" style="padding:24px">
    <h1>Job results</h1>
    <?php if (empty($jobs)): ?><p>No jobs found.</p><?php else: ?>
      <div class="cards-grid">
        <?php foreach($jobs as $j): ?>
          <article class="job-card">
            <h3><?php echo htmlspecialchars($j['title']); ?></h3>
            <p class="meta"><?php echo htmlspecialchars($j['company_name'].' — '.$j['location'].' • '.$j['type']); ?></p>
            <p><?php echo htmlspecialchars(substr($j['description'],0,160)); ?>…</p>
            <div class="card-actions">
              <a class="btn btn-outline" href="/php/jobs/detail.php?slug=<?php echo htmlspecialchars($j['slug']); ?>">View</a>
              <a class="btn btn-primary" href="/php/jobs/apply.php?id=<?php echo htmlspecialchars($j['id']); ?>">Apply</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body></html>
