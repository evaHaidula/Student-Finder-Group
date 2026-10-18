<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
start_secure_session();
$me = current_user();   // null if nobody is logged in

$categories = ['Group or join request', 'Account access', 'Safety concern', 'General question'];

// Empty values for the first visit
$category = 'Group or join request';
$name = '';
$email = '';
$subject = '';
$message = '';
$errors = [];

// If logged in, fill in the name and email
if ($me) {
    $name = $me['name'];
    $row = fetch_one('SELECT email FROM users WHERE id = ?', 'i', [$me['id']]);
    $email = $row['email'];
}

// Runs when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $name = trim($_POST['name']);
    $email = strtolower(trim($_POST['email']));
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Check each field
    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please try again.';
    }
    if (!in_array($category, $categories)) {
        $errors['category'] = 'Select a category.';
    }
    if (strlen($name) < 2 || strlen($name) > 100) {
        $errors['name'] = 'Enter your name.';
    }
    if (!is_nust_email($email)) {
        $errors['email'] = 'Use your NUST email (name@nust.na).';
    }
    if (strlen($subject) < 3 || strlen($subject) > 150) {
        $errors['subject'] = 'Subject must be 3 to 150 characters.';
    }
    if (strlen($message) < 10 || strlen($message) > 2000) {
        $errors['message'] = 'Please write 10 to 2000 characters.';
    }

    // No errors: save the message
    if (!$errors) {
        // user_id stays empty (NULL) if the visitor is not logged in
        $user_id = null;
        if ($me) {
            $user_id = $me['id'];
        }

        run_sql('INSERT INTO contact_messages (user_id, category, name, email, subject, message) VALUES (?, ?, ?, ?, ?, ?)',
                'isssss', [$user_id, $category, $name, $email, $subject, $message]);

        set_flash('ok', 'Thanks! Your request was sent. We will reply by email.');
        redirect('contact.php');
    }
}

// Shows the red error text under a field
function field_error($errors, $key) {
    if (isset($errors[$key])) {
        return '<p class="error">' . e($errors[$key]) . '</p>';
    }
    return '';
}

$pageTitle = 'Contact';
$activePage = 'contact';
$bodyClass = 'dark';
require __DIR__ . '/includes/header.php';
?>
<main>
    <div class="container">
        <div class="page-title">
            <span class="small-label">HELP AND SUPPORT</span>
            <h1>How can we help?</h1>
            <p class="muted">Choose a contact route or send a categorized support request.</p>
        </div>

        <div class="contact-cards">
            <article class="card"><h2>General support</h2><p class="muted">support@studymatch.na<br>Replies within 1 business day</p></article>
            <article class="card"><h2>Safety &amp; reports</h2><p class="muted">safety@studymatch.na<br>Urgent group or member concerns</p></article>
            <article class="card"><h2>NUST Student Services</h2><p class="muted">Student Support Centre<br>Main Campus · Office hours</p></article>
        </div>

        <div class="two-column">
            <!-- Support request form (data entry) -->
            <form class="card validate" method="post" action="contact.php">
                <?= render_flash() ?>
                <?php if ($errors): ?><p class="message-error" role="alert"><?= e($errors['form'] ?? 'Please fix the highlighted fields.') ?></p><?php endif; ?>
                <?= csrf_field() ?>
                <h2>Send a support request</h2>

                <div class="field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <?php foreach ($categories as $c): ?>
                            <option <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'category') ?>
                </div>
                <div class="row-2">
                    <div class="field">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" maxlength="100" placeholder="Your full name" required value="<?= e($name) ?>">
                        <?= field_error($errors, 'name') ?>
                    </div>
                    <div class="field">
                        <label for="email">Student email</label>
                        <input type="email" id="email" name="email" maxlength="150" placeholder="student@nust.na" data-check="nust-email" required value="<?= e($email) ?>">
                        <?= field_error($errors, 'email') ?>
                    </div>
                </div>
                <div class="field">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" maxlength="150" placeholder="Brief summary" required value="<?= e($subject) ?>">
                    <?= field_error($errors, 'subject') ?>
                </div>
                <div class="field">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" maxlength="2000" placeholder="Tell us what happened and what you need..." data-check="message" required><?= e($message) ?></textarea>
                    <?= field_error($errors, 'message') ?>
                </div>
                <button type="submit" class="btn btn-amber">Send request</button>
            </form>

            <div class="stack">
                <section class="card">
                    <h2>Before you send</h2>
                    <p class="muted">For group specific issues, include the module code and group name. Never share your password or private login details.</p>
                </section>
                <section class="card">
                    <h2>Response times</h2>
                    <ul class="plain-list muted">
                        <li>Safety reports · Priority</li>
                        <li>Account access · 1 day</li>
                        <li>General questions · 2 days</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>