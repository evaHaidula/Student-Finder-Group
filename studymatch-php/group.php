<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
start_secure_session();
$user = require_login();

// Which group? (group.php?id=3)
$id = (int)($_GET['id'] ?? 0);
$group = fetch_one('SELECT * FROM v_group_cards WHERE id = ?', 'i', [$id]);

if (!$group) {
    set_flash('error', 'That group does not exist.');
    redirect('browse.php');
}

// Is this person the host or an admin?
$isHost = ($group['host_id'] == $user['id']);
$isAdmin = ($user['role'] === 'admin');

// Only the host and admins can see groups that are not approved yet
if ($group['status'] !== 'approved' && !$isHost && !$isAdmin) {
    set_flash('error', 'That group does not exist.');
    redirect('browse.php');
}

// Buttons on this page (join and report)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $back = 'group.php?id=' . $id;

    if (!csrf_valid()) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect($back);
    }

    $action = $_POST['action'];

    if ($action === 'join') {
        // Has this student already asked or joined?
        $existing = fetch_one('SELECT status FROM group_members WHERE group_id = ? AND user_id = ?', 'ii', [$id, $user['id']]);

        if ($group['status'] !== 'approved') {
            set_flash('error', 'This group is not open yet.');
        } elseif ($isHost || $existing) {
            set_flash('error', 'You are already part of this group or have a request waiting.');
        } elseif (spaces_left($group) == 0) {
            set_flash('error', 'This group is full.');
        } else {
            run_sql("INSERT INTO group_members (group_id, user_id, status) VALUES (?, ?, 'pending')", 'ii', [$id, $user['id']]);
            set_flash('ok', 'Your request was sent. The host will review it.');
        }

    } elseif ($action === 'report') {
        $reason = trim($_POST['reason']);

        if (strlen($reason) < 5 || strlen($reason) > 255) {
            set_flash('error', 'Please describe the problem in 5 to 255 characters.');
        } else {
            run_sql('INSERT INTO reports (group_id, reporter_id, reason) VALUES (?, ?, ?)', 'iis', [$id, $user['id'], $reason]);
            set_flash('ok', 'Thank you. A moderator will review your report.');
        }
    }

    // Reload the page so a refresh does not send the form again
    redirect($back);
}

// Data for the page: approved members, this student's request, spaces left
$members = fetch_all(
    "SELECT u.id, u.full_name, u.programme, u.year_of_study
       FROM group_members gm JOIN users u ON u.id = gm.user_id
      WHERE gm.group_id = ? AND gm.status = 'approved'
      ORDER BY u.full_name",
    'i', [$id]
);
$mine = fetch_one('SELECT status FROM group_members WHERE group_id = ? AND user_id = ?', 'ii', [$id, $user['id']]);
$spaces = spaces_left($group);

$pageTitle = 'Group details';
$activePage = 'browse';
$bodyClass = 'dark';
require __DIR__ . '/includes/header.php';
?>

<main>
    <div class="container">
        <p class="breadcrumb"><a href="browse.php">Browse Groups</a> / <?= e($group['module_code']) ?> / <?= e($group['title']) ?></p>
        <?= render_flash() ?>

        <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:14px; margin-bottom:20px">
            <div>
                <p class="small-label"><?= e($group['module_code']) ?> · <?= e($group['module_name']) ?></p>
                <h1><?= e($group['title']) ?></h1>
                <p class="muted"><?= e($group['host_programme']) ?> · Year <?= (int)$group['host_year'] ?> · Hosted by <?= e($group['host_name']) ?>
                    <?= $group['status'] !== 'approved' ? ' · ' . status_badge($group['status']) : '' ?></p>
            </div>
        </div>

        <div class="two-column">
            <div class="stack">
                <section class="card">
                    <h2>About this group</h2>
                    <p><?= nl2br(e($group['description'])) ?></p>
                </section>

                <section class="card">
                    <h2>Study goal</h2>
                    <ul class="goal-list"><li>✓ <?= e($group['study_goal']) ?></li></ul>
                </section>

                <section class="card">
                    <h2>Members</h2>
                    <div class="members">
                        <?php foreach ($members as $m): ?>
                            <div class="member">
                                <strong><?= e($m['full_name']) ?><?= $m['id'] == $group['host_id'] ? ' · Host' : '' ?></strong>
                                <small><?= e($m['programme']) ?> · Y<?= (int)$m['year_of_study'] ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div class="stack">
                <section class="card">
                    <h2>Schedule &amp; location</h2>
                    <ul class="plain-list">
                        <li>Every <?= e($group['day_of_week']) ?></li>
                        <li><?= e(fmt_time($group['start_time'])) ?>-<?= e(fmt_time($group['end_time'])) ?></li>
                        <li><?= e($group['location']) ?></li>
                        <li><?= e($group['study_format']) ?></li>
                    </ul>
                </section>

                <section class="card">
                    <h2>Spaces</h2>
                    <p class="big-number"><?= $spaces ?> <?= $spaces == 1 ? 'space' : 'spaces' ?></p>
                    <p class="muted"><?= (int)$group['member_count'] ?> of <?= (int)$group['max_members'] ?> members confirmed</p>
                </section>

                <section class="card">
                    <h2>Join request</h2>
                    <?php if ($isHost): ?>
                        <p class="muted">You host this group. Review join requests on your <a href="dashboard.php">dashboard</a>.</p>
                    <?php elseif ($mine && $mine['status'] === 'approved'): ?>
                        <p class="muted">You are a member of this group.</p>
                    <?php elseif ($mine): ?>
                        <p class="muted">Your request is waiting for the host to review it.</p>
                        <span class="badge badge-pending">PENDING</span>
                    <?php elseif ($group['status'] !== 'approved'): ?>
                        <p class="muted">This group is not open for requests yet.</p>
                    <?php elseif ($spaces == 0): ?>
                        <p class="muted">This group is full.</p>
                    <?php else: ?>
                        <p class="muted">The host reviews every request before you are added.</p>
                        <form method="post" action="group.php?id=<?= $id ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="join">
                            <button type="submit" class="btn btn-amber">Request to Join</button>
                        </form>
                    <?php endif; ?>
                </section>

                <?php if (!$isHost): ?>
                <section class="card">
                    <details class="report">
                        <summary><strong>Report this group</strong></summary>
                        <form method="post" action="group.php?id=<?= $id ?>" style="margin-top:10px">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="report">
                            <div class="field">
                                <label for="reason">What is wrong?</label>
                                <textarea id="reason" name="reason" maxlength="255" required minlength="5"></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline btn-small">Send report</button>
                        </form>
                    </details>
                </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>