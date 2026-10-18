<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
start_secure_session();

// Choices for the dropdowns and checkboxes
$programmes = ['BSc Computer Science', 'BSc Information Technology', 'BSc Software Engineering'];
$formats = ['In person and online', 'In person', 'Online'];
$slots = ['Mon', 'Tue 16:00-20:00', 'Wed', 'Thu 17:00-20:00', 'Fri', 'Sat 09:00-13:00'];

// Empty values for the first visit (also used to refill the form)
$full_name = '';
$email = '';
$student_number = '';
$programme = '';
$year = '2';
$modules = '';
$format = 'In person and online';
$chosen = [];
$errors = [];

// Runs when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = strtolower(trim($_POST['email']));
    $student_number = trim($_POST['student_number']);
    $password = $_POST['password'];
    $programme = $_POST['programme'];
    $year = $_POST['year'];
    $modules = trim($_POST['modules']);
    $format = $_POST['format'];
    if (isset($_POST['availability'])) {
        $chosen = $_POST['availability'];
    }

    // Check each field
    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please try again.';
    }
    if (strlen($full_name) < 2 || strlen($full_name) > 100) {
        $errors['full_name'] = 'Enter your full name.';
    }
    if (!is_nust_email($email)) {
        $errors['email'] = 'Use your NUST email (name@nust.na).';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if (strlen($student_number) != 9 || !ctype_digit($student_number)) {
        $errors['student_number'] = 'Student number must be 9 digits.';
    }
    if (!in_array($programme, $programmes)) {
        $errors['programme'] = 'Select your programme.';
    }
    if ($modules === '') {
        $errors['modules'] = 'List your current modules.';
    }

    // Is the email or student number already used?
    if (!$errors) {
        if (fetch_one('SELECT id FROM users WHERE email = ?', 's', [$email])) {
            $errors['email'] = 'An account with this email already exists.';
        }
        if (fetch_one('SELECT id FROM users WHERE student_number = ?', 's', [$student_number])) {
            $errors['student_number'] = 'An account with this student number already exists.';
        }
    }

    // No errors: save the student
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $availability = implode(', ', $chosen);   // for example "Mon, Wed"
        $year_number = (int)$year;

        run_sql("INSERT INTO users (full_name, email, student_number, password_hash, programme,
                                    year_of_study, current_modules, availability, preferred_format, role)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')",
                'sssssisss',
                [$full_name, $email, $student_number, $hash, $programme, $year_number, $modules, $availability, $format]);

        set_flash('ok', 'Account created. You can now log in.');
        redirect('login.php');
    }
}

// Shows the red error text under a field
function field_error($errors, $key) {
    if (isset($errors[$key])) {
        return '<p class="error">' . e($errors[$key]) . '</p>';
    }
    return '';
}

$pageTitle = 'Register';
$activePage = 'register';
require __DIR__ . '/includes/header.php';
?>

<main>
    <div class="container">
        <div class="page-title">
            <span class="small-label">STUDENT ACCOUNT</span>
            <h1>Create your StudyMatch profile</h1>
            <p class="muted">Use your NUST details so we can make relevant, trusted matches.</p>
        </div>

        <?php if ($errors): ?>
            <p class="message-error" role="alert">Please fix the highlighted fields.</p>
        <?php endif; ?>

        <form class="two-column validate" method="post" action="register.php">
            <?= csrf_field() ?>

            <div class="stack">
                <!-- 1. Account -->
                <section class="card">
                    <h2>1 · Account</h2>
                    <div class="field">
                        <label for="fullName">Full name</label>
                        <input type="text" id="fullName" name="full_name" placeholder="Your first and last name"
                               maxlength="100" required value="<?= e($full_name) ?>">
                        <?= field_error($errors, 'full_name') ?>
                    </div>
                    <div class="field">
                        <label for="email">NUST student email</label>
                        <input type="email" id="email" name="email" placeholder="student@nust.na"
                               data-check="nust-email" maxlength="150" required value="<?= e($email) ?>">
                        <?= field_error($errors, 'email') ?>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create a secure password"
                               data-check="password" minlength="8" maxlength="200" required>
                        <?= field_error($errors, 'password') ?>
                    </div>
                    <div class="field">
                        <label for="studentNumber">Student number</label>
                        <input type="text" id="studentNumber" name="student_number" placeholder="202612345"
                               data-check="student-number" maxlength="9" required value="<?= e($student_number) ?>">
                        <?= field_error($errors, 'student_number') ?>
                    </div>
                </section>

                <!-- 2. Academic profile -->
                <section class="card">
                    <h2>2 · Academic profile</h2>
                    <div class="row-2">
                        <div class="field">
                            <label for="programme">Programme</label>
                            <select id="programme" name="programme" required>
                                <option value="">Select programme</option>
                                <?php foreach ($programmes as $p): ?>
                                    <option <?= $programme === $p ? 'selected' : '' ?>><?= e($p) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= field_error($errors, 'programme') ?>
                        </div>
                        <div class="field">
                            <label for="year">Year</label>
                            <select id="year" name="year">
                                <?php for ($y = 1; $y <= 4; $y++): ?>
                                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>>Year <?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label for="modules">Current modules</label>
                        <input type="text" id="modules" name="modules" placeholder="CPS 212S, MAT 201S, NET 311S"
                               maxlength="255" required value="<?= e($modules) ?>">
                        <?= field_error($errors, 'modules') ?>
                    </div>
                </section>

                <!-- 3. Availability -->
                <section class="card">
                    <h2>3 · Availability</h2>
                    <p class="availability">
                        <?php foreach ($slots as $slot): ?>
                            <label><input type="checkbox" name="availability[]" value="<?= e($slot) ?>" style="width:auto" <?= in_array($slot, $chosen) ? 'checked' : '' ?>> <?= e($slot) ?></label>
                        <?php endforeach; ?>
                    </p>
                    <div class="field">
                        <label for="format">Preferred format</label>
                        <select id="format" name="format">
                            <?php foreach ($formats as $f): ?>
                                <option <?= $format === $f ? 'selected' : '' ?>><?= e($f) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </section>
            </div>

            <div class="stack">
                <section class="card">
                    <h2>Why we ask</h2>
                    <p class="muted">Programme, year, modules and availability help rank compatible groups. Your student number is never shown publicly.</p>
                </section>
                <button type="submit" class="btn btn-amber btn-full">Create account</button>
                <p class="muted">By registering, you agree to StudyMatch community and safety guidelines.</p>
            </div>
        </form>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>