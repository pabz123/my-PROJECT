<?php
// php/employer/post_job.php

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../helpers/csrf.php';

// simple auth check
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'employer') {
    header('Location: /php/auth/login.php');
    exit;
}

$action = $_POST['action'] ?? '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    // verify csrf
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token.';
    }

    // collect data
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = in_array($_POST['type'] ?? 'Internship', ['Internship','Full-time','Part-time','Contract']) ? $_POST['type'] : 'Internship';
    $location = trim($_POST['location'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $responsibilities = trim($_POST['responsibilities'] ?? '');
    $deadline = $_POST['application_deadline'] ?? null;

    if (!$title) $errors[] = 'Title is required.';
    if (!$slug) {
        // generate slug
        $slug = strtolower(preg_replace('/[^a-z0-9]+/','-', $title));
    } else {
        $slug = strtolower(preg_replace('/[^a-z0-9\-]+/','', $slug));
    }

    // unique slug check
    $stmt = $pdo->prepare('SELECT id FROM jobs WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        $slug .= '-' . bin2hex(random_bytes(3)); // fallback
    }

    if (empty($errors)) {
        $insert = $pdo->prepare('INSERT INTO jobs (employer_id, title, slug, description, type, location, salary, requirements, responsibilities, application_deadline) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([ $_SESSION['user_id'], $title, $slug, $description, $type, $location, $salary, $requirements, $responsibilities, $deadline ]);
        $_SESSION['flash'] = 'Job posted successfully.';
        header('Location: /dashboard/employer.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'preview') {
    // just show preview (no DB write). Keep posted values.
    $form = array_map(function($v){ return htmlspecialchars($v ?? ''); }, $_POST);
} else {
    // GET or arriving at form empty
    $form = [
        'title'=>'',
        'slug'=>'',
        'description'=>'',
        'type'=>'Internship',
        'location'=>'',
        'salary'=>'',
        'requirements'=>'',
        'responsibilities'=>'',
        'application_deadline'=>''
    ];
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Post Job — Employer — JobIntern</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <main class="container" style="max-width:900px;padding:24px">
    <h1>Post a job / internship</h1>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
      </div>
    <?php endif; ?>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'preview'): ?>
      <section class="job-preview" style="background:#fff;padding:16px;border-radius:8px">
        <h2><?php echo $form['title']; ?></h2>
        <p><strong>Type:</strong> <?php echo $form['type']; ?> — <strong>Location:</strong> <?php echo $form['location']; ?></p>
        <p><?php echo nl2br($form['description']); ?></p>
        <h3>Requirements</h3>
        <p><?php echo nl2br($form['requirements']); ?></p>
        <h3>Responsibilities</h3>
        <p><?php echo nl2br($form['responsibilities']); ?></p>
        <p><strong>Application deadline:</strong> <?php echo $form['application_deadline']; ?></p>

        <form method="post" style="margin-top:12px">
          <?php echo csrf_input_field(); ?>
          <?php // re-send all values as hidden inputs to "save" action ?>
          <?php foreach(['title','slug','description','type','location','salary','requirements','responsibilities','application_deadline'] as $k): ?>
            <input type="hidden" name="<?php echo $k; ?>" value="<?php echo $form[$k]; ?>">
          <?php endforeach; ?>
          <input type="hidden" name="action" value="save">
          <button class="btn btn-primary" type="submit">Publish job</button>
          <a class="btn btn-outline" href="/php/employer/post_job.php">Edit</a>
        </form>
      </section>

    <?php else: ?>
      <form method="post" novalidate>
        <?php echo csrf_input_field(); ?>
        <input type="hidden" name="action" value="preview">
        <label>Job title <input name="title" type="text" value="<?php echo htmlspecialchars($form['title']); ?>" required></label>
        <label>Custom slug (optional) <input name="slug" type="text" value="<?php echo htmlspecialchars($form['slug']); ?>"></label>
        <label>Type
          <select name="type">
            <?php foreach(['Internship','Full-time','Part-time','Contract'] as $t): ?>
              <option <?php echo ($form['type']==$t)?'selected':''; ?>><?php echo $t; ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Location <input name="location" type="text" value="<?php echo htmlspecialchars($form['location']); ?>"></label>
        <label>Salary <input name="salary" type="text" value="<?php echo htmlspecialchars($form['salary']); ?>"></label>
        <label>Description <textarea name="description" rows="6"><?php echo htmlspecialchars($form['description']); ?></textarea></label>
        <label>Requirements <textarea name="requirements" rows="4"><?php echo htmlspecialchars($form['requirements']); ?></textarea></label>
        <label>Responsibilities <textarea name="responsibilities" rows="4"><?php echo htmlspecialchars($form['responsibilities']); ?></textarea></label>
        <label>Application deadline <input name="application_deadline" type="date" value="<?php echo htmlspecialchars($form['application_deadline']); ?>"></label>

        <div style="margin-top:12px">
          <button class="btn btn-outline" type="submit">Preview</button>
        </div>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>
