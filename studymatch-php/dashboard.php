<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
start_secure_session();
$user = require_login();

// Buttons on this page (approve, decline, leave, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect('dashboard.php');
    }

    $action = $_POST['action'];
    $group_id = (int)$_POST['group_id'];
    $other_user_id = 0;
    if (isset($_POST['user_id'])) {
        $other_user_id = (int)$_POST['user_id'];
    }

    // Find the group
    $group = fetch_one('SELECT * FROM v_group_cards WHERE id = ?', 'i', [$group_id]);
    if (!$group) {
        set_flash('error', 'Unknown group.');
        redirect('dashboard.php');
    }

    // Am I the host of this group?
    $i_am_host = ($group['host_id'] == $user['id']);

    if ($action === 'delete_group') {
        // The SQL only deletes it if I am the host
        $deleted = run_sql('DELETE FROM study_groups WHERE id = ? AND host_id = ?', 'ii', [$group_id, $user['id']]);
        if ($deleted > 0) {
            set_flash('ok', 'Group deleted.');
        } else {
            set_flash('error', 'You can only delete groups that you host.');
        }

    } elseif ($action === 'approve_request') {
        if (!$i_am_host) {
            set_flash('error', 'You can only review requests for groups that you host.');
        } elseif (spaces_left($group) == 0) {
            set_flash('error', 'The group is full.');
        } else {
            run_sql("UPDATE group_members SET status = 'approved' WHERE group_id = ? AND user_id = ?", 'ii', [$group_id, $other_user_id]);
            set_flash('ok', 'Request approved.');
        }

    } elseif ($action === 'reject_request') {
        if (!$i_am_host) {
            set_flash('error', 'You can only review requests for groups that you host.');
        } else {
            run_sql("DELETE FROM group_members WHERE group_id = ? AND user_id = ? AND status = 'pending'", 'ii', [$group_id, $other_user_id]);
            set_flash('ok', 'Request declined.');
        }

    } elseif ($action === 'leave_group') {
        // Leave a group or cancel a request (a host must delete the group instead)
        if ($i_am_host) {
            set_flash('error', 'Hosts cannot leave their own group. Delete it instead.');
        } else {
            run_sql('DELETE FROM group_members WHERE group_id = ? AND user_id = ?', 'ii', [$group_id, $user['id']]);
            set_flash('ok', 'Done. You are no longer in that group.');
        }
    }

    // Reload the page so a refresh does not repeat the action
    redirect('dashboard.php');
}

// My profile
$profile = fetch_one('SELECT full_name, programme, year_of_study, current_modules, availability FROM users WHERE id = ?', 'i', [$user['id']]);

// All groups I am connected to (hosting, joined or waiting)
$mine = fetch_all(
    'SELECT g.*, gm.status AS my_status
       FROM v_group_cards g JOIN group_members gm ON gm.group_id = g.id
      WHERE gm.user_id = ?
      ORDER BY g.created_at DESC',
    'i', [$user['id']]
);

// Sort them into three lists
$created = [];
$joined = [];
$pending = [];
foreach ($mine as $g) {
    if ($g['host_id'] == $user['id']) {
        $created[] = $g;
    } elseif ($g['my_status'] === 'approved') {
        $joined[] = $g;
    } else {
        $pending[] = $g;
    }
}

// Upcoming sessions: approved groups I am in, from Monday to Sunday
$sessions = fetch_all(
    "SELECT g.* FROM v_group_cards g JOIN group_members gm ON gm.group_id = g.id
      WHERE gm.user_id = ? AND gm.status = 'approved' AND g.status = 'approved'
      ORDER BY FIELD(g.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), g.start_time",
    'i', [$user['id']]
);

// Join requests waiting for me to review (groups I host)
$requests = fetch_all(
    "SELECT gm.group_id, gm.user_id, u.full_name, u.programme, u.year_of_study, g.title, g.module_code
       FROM group_members gm
       JOIN v_group_cards g ON g.id = gm.group_id
       JOIN users u ON u.id = gm.user_id
      WHERE g.host_id = ? AND gm.status = 'pending'
      ORDER BY gm.requested_at",
    'i', [$user['id']]
);

// Things shown in the profile cards
$first_name = explode(' ', $profile['full_name'])[0];
$module_count = count(explode(',', $profile['current_modules']));
$availability = [];
if ($profile['availability']) {
    $availability = explode(',', $profile['availability']);
}

// A small button that sends a POST (Approve, Leave, Delete...)
function action_button($action, $group_id, $label, $class, $user_id = 0, $confirm = '') {
    $html = '<form method="post" action="dashboard.php" class="inline-form"';
    if ($confirm !== '') {
        $html .= ' onsubmit="return confirm(\'' . $confirm . '\')"';
    }
    $html .= '>';
    $html .= csrf_field();
    $html .= '<input type="hidden" name="action" value="' . $action . '">';
    $html .= '<input type="hidden" name="group_id" value="' . (int)$group_id . '">';
    if ($user_id > 0) {
        $html .= '<input type="hidden" name="user_id" value="' . (int)$user_id . '">';
    }
    $html .= '<button type="submit" class="btn ' . $class . ' btn-small">' . $label . '</button></form>';
    return $html;
}

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<main>
    <div class="container">
        <div class="page-title">
            <span class="small-label" style="color:#1a5c3a">STUDENT WORKSPACE</span>
            <h1>Hello, <?= e($first_name) ?></h1>
            <p class="muted">Your sessions, groups and requests in one place.</p>
        </div>
        <?= render_flash() ?>

        <div class="two-column">
            <div class="stack">

                <section class="card">
                    <h2>Upcoming sessions</h2>
                    <?php if (!$sessions): ?>
                        <p class="muted">No sessions yet. <a href="browse.php">Find a group to join.</a></p>
                    <?php else: ?>
                    <div class="sessions">
                        <?php foreach ($sessions as $s): ?>
                            <div class="session">
                                <span class="badge badge-pending">WEEKLY</span>
                                <h3 style="margin-top:8px"><?= e($s['module_code']) ?> · <?= e(short_day($s['day_of_week'])) ?> <?= e(fmt_time($s['start_time'])) ?></h3>
                                <p class="muted"><?= e($s['location']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>

                <?php if ($requests): ?>
                <section class="card">
                    <h2>Requests to review · <?= count($requests) ?></h2>
                    <?php foreach ($requests as $r): ?>
                        <div class="person" style="align-items:center; flex-wrap:wrap; gap:8px">
                            <span><strong><?= e($r['full_name']) ?></strong> · <?= e($r['programme']) ?> Y<?= (int)$r['year_of_study'] ?><br>
                                <small class="muted">wants to join <?= e($r['module_code']) ?> · <?= e($r['title']) ?></small></span>
                            <span class="button-row">
                                <?= action_button('approve_request', $r['group_id'], 'Approve', 'btn-green', $r['user_id']) ?>
                                <?= action_button('reject_request', $r['group_id'], 'Decline', 'btn-outline', $r['user_id']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </section>
                <?php endif; ?>

                <div class="grid-2">
                    <section class="card">
                        <h2>Joined groups</h2>
                        <?php if (!$joined): ?><p class="muted">You have not joined any groups yet.</p><?php endif; ?>
                        <?php foreach ($joined as $g): ?>
                            <?= render_group_card($g,
                                '<div class="button-row"><a href="group.php?id=' . (int)$g['id'] . '" class="btn btn-outline btn-small">View group</a>'
                                . action_button('leave_group', $g['id'], 'Leave', 'btn-outline', 0, 'Leave this group?') . '</div>') ?>
                        <?php endforeach; ?>
                    </section>

                    <section class="card">
                        <h2>Created groups</h2>
                        <?php if (!$created): ?><p class="muted">You have not created a group. <a href="create-group.php">Create one.</a></p><?php endif; ?>
                        <?php foreach ($created as $g): ?>
                            <p style="margin:0 0 6px"><?= status_badge($g['status']) ?></p>
                            <?= render_group_card($g,
                                '<div class="button-row"><a href="group.php?id=' . (int)$g['id'] . '" class="btn btn-outline btn-small">View</a>'
                                . '<a href="create-group.php?id=' . (int)$g['id'] . '" class="btn btn-outline btn-small">Edit group</a>'
                                . action_button('delete_group', $g['id'], 'Delete', 'btn-red', 0, 'Delete this group and all its requests?') . '</div>') ?>
                        <?php endforeach; ?>
                    </section>
                </div>

                <section class="card">
                    <h2>My pending requests</h2>
                    <?php if (!$pending): ?><p class="muted">No requests waiting.</p><?php endif; ?>
                    <?php foreach ($pending as $g): ?>
                        <div class="person" style="align-items:center; flex-wrap:wrap; gap:8px">
                            <span><?= e($g['module_code']) ?> · <?= e($g['title']) ?> <span class="badge badge-pending">AWAITING HOST</span></span>
                            <?= action_button('leave_group', $g['id'], 'Cancel request', 'btn-outline', 0, 'Cancel this request?') ?>
                        </div>
                    <?php endforeach; ?>
                </section>
            </div>

            <div class="stack">
                <section class="card">
                    <h2>Your profile</h2>
                    <span class="badge badge-spots">NUST VERIFIED</span>
                    <h3 style="margin-top:12px; font-size:22px"><?= e($profile['full_name']) ?></h3>
                    <p class="muted"><?= e($profile['programme']) ?> · Year <?= (int)$profile['year_of_study'] ?><br><?= $module_count ?> active <?= $module_count == 1 ? 'module' : 'modules' ?></p>
                </section>

                <section class="card">
                    <h2>Availability</h2>
                    <?php if (!$availability): ?>
                        <p class="muted">No availability saved.</p>
                    <?php else: ?>
                        <ul class="plain-list">
                            <?php foreach ($availability as $slot): ?><li><?= e($slot) ?></li><?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <?php if ($user['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn btn-outline">Open admin area</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>