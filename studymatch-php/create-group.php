<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
start_secure_session();
$user = require_login();

// Modules for the dropdown
$modules = fetch_all('SELECT id, code, name FROM modules ORDER BY code');

// Default values for a new group
$edit_id = 0;
$module_id = '';
$title = '';
$study_goal = '';
$day_of_week = 'Tuesday';
$start_time = '16:00';
$end_time = '18:00';
$study_format = 'In person';
$location = 'NUST Library · Room 3';
$max_members = '6';
$description = '';
$errors = [];

// Edit mode: create-group.php?id=5 (only the host can edit)
if (isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $row = fetch_one('SELECT * FROM study_groups WHERE id = ? AND host_id = ?', 'ii', [$edit_id, $user['id']]);

    if (!$row) {
        set_flash('error', 'You can only edit groups that you host.');
        redirect('dashboard.php');
    }

    // Fill the form with the saved values
    $module_id = $row['module_id'];
    $title = $row['title'];
    $study_goal = $row['study_goal'];
    $day_of_week = $row['day_of_week'];
    $start_time = fmt_time($row['start_time']);
    $end_time = fmt_time($row['end_time']);
    $study_format = $row['study_format'];
    $location = $row['location'];
    $max_members = $row['max_members'];
    $description = $row['description'];
}

// Runs when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = (int)$_POST['edit_id'];
    $module_id = $_POST['module_id'];
    $title = trim($_POST['title']);
    $study_goal = trim($_POST['study_goal']);
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $study_format = $_POST['study_format'];
    $location = trim($_POST['location']);
    $max_members = $_POST['max_members'];
    $description = trim($_POST['description']);

    // Check each field
    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please try again.';
    }
    if ($module_id === '') {
        $errors['module_id'] = 'Select a module.';
    }
    if (strlen($title) < 3 || strlen($title) > 100) {
        $errors['title'] = 'Group name must be 3 to 100 characters.';
    }
    if (strlen($study_goal) < 3 || strlen($study_goal) > 150) {
        $errors['study_goal'] = 'Study goal must be 3 to 150 characters.';
    }
    if (!in_array($day_of_week, DAYS)) {
        $errors['day_of_week'] = 'Select a day.';
    }
    if ($start_time === '' || $end_time === '') {
        $errors['start_time'] = 'Enter a start and end time.';
    } elseif ($end_time <= $start_time) {
        $errors['end_time'] = 'The end time must be after the start time.';
    }
    if (!in_array($study_format, FORMATS_GROUP)) {
        $errors['study_format'] = 'Select a format.';
    }
    if (strlen($location) < 2) {
        $errors['location'] = 'Enter a location.';
    }
    if ($max_members < 2 || $max_members > 10) {
        $errors['max_members'] = 'Choose between 2 and 10 members.';
    }
    if (strlen($description) < 10 || strlen($description) > 2000) {
        $errors['description'] = 'Please write 10 to 2000 characters.';
    }

    // Editing: the max cannot be lower than the current members
    if (!$errors && $edit_id > 0) {
        $count = fetch_one("SELECT COUNT(*) AS c FROM group_members WHERE group_id = ? AND status = 'approved'", 'i', [$edit_id]);
        if ($max_members < $count['c']) {
            $errors['max_members'] = 'The group already has ' . $count['c'] . ' members.';
        }
    }

    // No errors: save
    if (!$errors) {
        $module_number = (int)$module_id;
        $max_number = (int)$max_members;

        if ($edit_id == 0) {
            // New group, status starts as pending
            run_sql("INSERT INTO study_groups (module_id, host_id, title, study_goal, description, day_of_week, start_time, end_time, study_format, location, max_members, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                    'iissssssssi',
                    [$module_number, $user['id'], $title, $study_goal, $description, $day_of_week, $start_time, $end_time, $study_format, $location, $max_number]);

            // The host is the first member
            $new_id = db()->insert_id;
            run_sql("INSERT INTO group_members (group_id, user_id, status) VALUES (?, ?, 'approved')", 'ii', [$new_id, $user['id']]);

            set_flash('ok', 'Group submitted. It will appear in Browse Groups after an admin approves it.');
        } else {
            // Edit: the group goes back to pending for the admin to check
            run_sql("UPDATE study_groups SET module_id = ?, title = ?, study_goal = ?, description = ?, day_of_week = ?, start_time = ?,
                            end_time = ?, study_format = ?, location = ?, max_members = ?, status = 'pending'
                     WHERE id = ? AND host_id = ?",
                    'issssssssiii',
                    [$module_number, $title, $study_goal, $description, $day_of_week, $start_time, $end_time, $study_format, $location, $max_number, $edit_id, $user['id']]);

            set_flash('ok', 'Group updated. An admin will review the changes.');
        }

        redirect('dashboard.php');
    }
}

// Shows the red error text under a field
function field_error($errors, $key) {
    if (isset($errors[$key])) {
        return '<p class="error">' . e($errors[$key]) . '</p>';
    }
    return '';
}

$pageTitle = $edit_id == 0 ? 'Create a group' : 'Edit group';
$activePage = 'create';
require __DIR__ . '/includes/header.php';
?>

<main>
    <div class="container">
        <div class="page-title">
            <span class="small-label" style="color:#1a5c3a"><?= $edit_id == 0 ? 'NEW STUDY GROUP' : 'EDIT STUDY GROUP' ?></span>
            <h1><?= $edit_id == 0 ? 'Create a group' : 'Edit your group' ?></h1>
            <p class="muted">Set clear expectations. New and edited groups are reviewed before they appear in Browse Groups.</p>
        </div>

        <?php if ($errors): ?>
            <p class="message-error" role="alert"><?= e($errors['form'] ?? 'Please fix the highlighted fields.') ?></p>
        <?php endif; ?>

        <form class="two-column validate" method="post" action="create-group.php">
            <?= csrf_field() ?>
            <input type="hidden" name="edit_id" value="<?= (int)$edit_id ?>">

            <div class="stack">
                <section class="card">
                    <h2>1 · Academic details</h2>
                    <div class="field">
                        <label for="module">Module</label>
                        <select id="module" name="module_id" required>
                            <option value="">Select module</option>
                            <?php foreach ($modules as $m): ?>
                                <option value="<?= (int)$m['id'] ?>" <?= $module_id == $m['id'] ? 'selected' : '' ?>><?= e($m['code']) ?> - <?= e($m['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'module_id') ?>
                    </div>
                    <div class="field">
                        <label for="title">Group name</label>
                        <input type="text" id="title" name="title" maxlength="100" placeholder="Data Structures &amp; Algorithms" required value="<?= e($title) ?>">
                        <?= field_error($errors, 'title') ?>
                    </div>
                    <div class="field">
                        <label for="goal">Topic or study goal</label>
                        <input type="text" id="goal" name="study_goal" maxlength="150" placeholder="Exam prep: trees, graphs and complexity" required value="<?= e($study_goal) ?>">
                        <?= field_error($errors, 'study_goal') ?>
                    </div>
                </section>

                <section class="card">
                    <h2>2 · Schedule and format</h2>
                    <div class="field">
                        <label for="day">Day</label>
                        <select id="day" name="day_of_week">
                            <?php foreach (DAYS as $d): ?>
                                <option <?= $day_of_week === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'day_of_week') ?>
                    </div>
                    <div class="row-2">
                        <div class="field">
                            <label for="start">Start time</label>
                            <input type="time" id="start" name="start_time" required value="<?= e($start_time) ?>">
                            <?= field_error($errors, 'start_time') ?>
                        </div>
                        <div class="field">
                            <label for="end">End time</label>
                            <input type="time" id="end" name="end_time" required value="<?= e($end_time) ?>">
                            <?= field_error($errors, 'end_time') ?>
                        </div>
                    </div>
                    <div class="field">
                        <label for="format">Format</label>
                        <select id="format" name="study_format">
                            <?php foreach (FORMATS_GROUP as $f): ?>
                                <option <?= $study_format === $f ? 'selected' : '' ?>><?= e($f) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'study_format') ?>
                    </div>
                    <div class="field">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" maxlength="120" required value="<?= e($location) ?>">
                        <?= field_error($errors, 'location') ?>
                    </div>
                </section>

                <section class="card">
                    <h2>3 · Group settings</h2>
                    <div class="field">
                        <label for="max">Maximum members</label>
                        <select id="max" name="max_members">
                            <?php for ($n = 2; $n <= 10; $n++): ?>
                                <option value="<?= $n ?>" <?= $max_members == $n ? 'selected' : '' ?>><?= $n ?> students</option>
                            <?php endfor; ?>
                        </select>
                        <?= field_error($errors, 'max_members') ?>
                    </div>
                    <div class="field">
                        <label for="description">Group description</label>
                        <textarea id="description" name="description" maxlength="2000" placeholder="Describe the session format and expectations..." data-check="message" required><?= e($description) ?></textarea>
                        <?= field_error($errors, 'description') ?>
                    </div>
                </section>
            </div>

            <div class="stack">
                <section class="card">
                    <h2>Submission status</h2>
                    <span class="badge badge-spots">DRAFT</span>
                    <p class="muted" style="margin-top:10px">Submit for approval<br>→ Admin review<br>→ Published to Browse Groups</p>
                </section>
                <button type="submit" class="btn btn-amber btn-full"><?= $edit_id == 0 ? 'Submit for approval' : 'Save changes' ?></button>
                <a href="dashboard.php" class="btn btn-outline btn-full">Cancel</a>
            </div>
        </form>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>