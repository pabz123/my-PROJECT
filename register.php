<?php
// php/auth/register.php

require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../helpers/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /php/register_form.php');
    exit;
}

// CSRF
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    $_SESSION['errors'] = ['Form token invalid or expired. Please reload the page and try again.'];
    header('Location: /php/register_form.php');
    exit;
}

// sanitize + validate
$name = trim($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$role = in_array($_POST['role'] ?? 'student', ['student','employer']) ? $_POST['role'] : 'student';

$errors = [];
if (!$name) $errors[] = 'Name is required.';
if (!$email) $errors[] = 'A valid email is required.';
if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
if ($password !== $password_confirm) $errors[] = 'Passwords do not match.';

$old = [
  'name'=>$name,
  'email'=>$email,
  'university'=>$_POST['university'] ?? '',
  'course'=>$_POST['course'] ?? '',
  'company_name'=>$_POST['company_name'] ?? ''
];

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $old;
    header('Location: /php/register_form.php?role='.$role);
    exit;
}

// check duplicate
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $_SESSION['errors'] = ['Email already registered.'];
    $_SESSION['old'] = $old;
    header('Location: /php/register_form.php?role='.$role);
    exit;
}

// handle resume upload for students
$resume_path = null;
if ($role === 'student' && !empty($_FILES['resume']['name'])) {
    $f = $_FILES['resume'];
    if ($f['error'] === UPLOAD_ERR_OK) {
        // validate type & size
        $allowed = ['application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $f['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed)) {
            $_SESSION['errors'] = ['Resume must be a PDF file.'];
            $_SESSION['old'] = $old;
            header('Location: /php/register_form.php?role=student');
            exit;
        }
        if ($f['size'] > 2 * 1024 * 1024) {
            $_SESSION['errors'] = ['Resume must be less than 2MB.'];
            $_SESSION['old'] = $old;
            header('Location: /php/register_form.php?role=student');
            exit;
        }
        // save (ensure assets/uploads exists and is writable)
        $uploadDir = __DIR__ . '/../assets/uploads/resumes/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $basename = bin2hex(random_bytes(12)) . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/','_',basename($f['name']));
        $dest = $uploadDir . $basename;
        if (move_uploaded_file($f['tmp_name'], $dest)) {
            $resume_path = 'assets/uploads/resumes/' . $basename; // path to store in DB
        }
    }
}

// create user
$hash = password_hash($password, PASSWORD_DEFAULT);

$insert = $pdo->prepare('INSERT INTO users (role, name, email, password, university, course, resume_path, company_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$insert->execute([
  $role,
  $name,
  $email,
  $hash,
  $role === 'student' ? ($_POST['university'] ?? null) : null,
  $role === 'student' ? ($_POST['course'] ?? null) : null,
  $resume_path,
  $role === 'employer' ? ($_POST['company_name'] ?? null) : null
]);

$userId = $pdo->lastInsertId();
session_regenerate_id(true);
$_SESSION['user_id'] = $userId;
$_SESSION['role'] = $role;
$_SESSION['name'] = $name;

if ($role === 'student') {
    header('Location: /dashboard/student.php');
} else {
    header('Location: /dashboard/employer.php');
}
exit;
