<?php
// Top of every page. Each page sets $pageTitle and $activePage before including this file
require_once __DIR__ . '/auth.php';
start_secure_session();

$user = current_user();

if (!isset($pageTitle)) {
    $pageTitle = 'StudyMatch';
}
if (!isset($activePage)) {
    $activePage = '';
}
if (!isset($bodyClass)) {
    $bodyClass = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyMatch - <?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?= e($bodyClass) ?>">

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= $user ? 'dashboard.php' : 'index.php' ?>"><span class="logo"></span> StudyMatch <small>NUST STUDY GROUP FINDER</small></a>
        <button class="menu-button" id="menuButton" type="button" aria-label="Open menu">☰</button>
        <nav id="mainNav" aria-label="Main navigation">
            <ul>
<?php if ($user === null): ?>
                <li><a href="index.php" <?= $activePage == 'home' ? 'class="active"' : '' ?>>Home</a></li>
                <li><a href="browse.php" <?= $activePage == 'browse' ? 'class="active"' : '' ?>>Browse Groups</a></li>
                <li><a href="about.php" <?= $activePage == 'about' ? 'class="active"' : '' ?>>About</a></li>
                <li><a href="contact.php" <?= $activePage == 'contact' ? 'class="active"' : '' ?>>Contact</a></li>
                <li><a href="login.php" <?= $activePage == 'login' ? 'class="active"' : '' ?>><strong>Log in</strong></a></li>
                <li><a href="register.php" class="btn btn-amber btn-small">Register</a></li>
<?php else: ?>
                <li><a href="browse.php" <?= $activePage == 'browse' ? 'class="active"' : '' ?>>Browse Groups</a></li>
                <li><a href="dashboard.php" <?= $activePage == 'dashboard' ? 'class="active"' : '' ?>>Dashboard</a></li>
                <li><a href="create-group.php" <?= $activePage == 'create' ? 'class="active"' : '' ?>>Create Group</a></li>
<?php if ($user['role'] === 'admin'): ?>
                <li><a href="admin.php" <?= $activePage == 'admin' ? 'class="active"' : '' ?>>Admin</a></li>
<?php endif; ?>
                <li class="user-name"><span class="avatar"></span> <?= e($user['name']) ?></li>
                <li>
                    <form method="post" action="logout.php" class="inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline btn-small">Log out</button>
                    </form>
                </li>
<?php endif; ?>
            </ul>
        </nav>
    </div>
</header>