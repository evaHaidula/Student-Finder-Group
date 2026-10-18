<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
start_secure_session();

// If already logged in, go to the right page
$current = current_user();
if ($current) {
    redirect(home_for_role($current['role']));
}

$error = '';
$email = '';

// Runs only when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } elseif (!is_nust_email($email)) {
        $error = 'Use your NUST email (name@nust.na).';
    } else {
        // Find the user with this email
        $stmt = db()->prepare('SELECT id, full_name, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Check the password against the saved hash
        if ($row && password_verify($password, $row['password_hash'])) {
            // New session id after login (safer)
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$row['id'];
            $_SESSION['user_name'] = $row['full_name'];
            $_SESSION['user_role'] = $row['role'];
            unset($_SESSION['csrf']);

            redirect(home_for_role($row['role']));
        }

        // Same message for wrong email and wrong password
        $error = 'Incorrect email or password.';
    }
}

$pageTitle  = 'Log in';
$activePage = 'login';
require __DIR__ . '/includes/header.php';
?>

<main class="login-page">
    <section class="login-left">
        <span class="small-label">WELCOME BACK</span>
        <h1>Your next study session starts here.</h1>
        <p>Sign in with your NUST student email to manage your groups and requests.</p>
    </section>

    <section class="login-right">
        <form class="card login-card validate" method="post" action="login.php">
            <h2>Log in to StudyMatch</h2>

            <?= render_flash() ?>
            <?php if ($error !== ''): ?>
                <p class="message-error" role="alert"><?= e($error) ?></p>
            <?php endif; ?>

            <?= csrf_field() ?>

            <div class="field">
                <label for="email">Student email</label>
                <input type="email" id="email" name="email" placeholder="student@nust.na"
                       data-check="nust-email" required autocomplete="username" value="<?= e($email) ?>">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       autocomplete="current-password" maxlength="200">
            </div>
            <p><a href="contact.php">Forgot password?</a></p>
            <button type="submit" class="btn btn-green btn-full">Log in</button>
            <p class="muted" style="margin-top:14px">New to StudyMatch? <a href="register.php">Register with your student details.</a></p>
        </form>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>