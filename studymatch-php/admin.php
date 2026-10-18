<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
start_secure_session();
$user = require_admin();   // students get "Access denied"

// The five tabs on this page
$tabs = ['panelGroups', 'panelPending', 'panelReports', 'panelModules', 'panelMessages'];

// Buttons on this page (approve, reject, delete, add module...)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Which tab to come back to after the action
    $tab = $_POST['tab'];
    if (!in_array($tab, $tabs)) {
        $tab = 'panelPending';
    }
    $back = 'admin.php?tab=' . $tab;

    if (!csrf_valid()) {
        set_flash('error', 'Your session expired. Please try again.');
        redirect($back);
    }

    $action = $_POST['action'];
    $id = 0;
    if (isset($_POST['id'])) {
        $id = (int)$_POST['id'];
    }

    if ($action === 'approve_group') {
        run_sql("UPDATE study_groups SET status = 'approved' WHERE id = ? AND status = 'pending'", 'i', [$id]);
        set_flash('ok', 'Group approved.');

    } elseif ($action === 'reject_group') {
        run_sql("UPDATE study_groups SET status = 'rejected' WHERE id = ? AND status = 'pending'", 'i', [$id]);
        set_flash('ok', 'Group rejected.');

    } elseif ($action === 'delete_group') {
        run_sql('DELETE FROM study_groups WHERE id = ?', 'i', [$id]);
        set_flash('ok', 'Group deleted.');

    } elseif ($action === 'resolve_report') {
        run_sql("UPDATE reports SET status = 'resolved' WHERE id = ?", 'i', [$id]);
        set_flash('ok', 'Report resolved.');

    } elseif ($action === 'add_module') {
        $code = strtoupper(trim($_POST['code']));
        $name = trim($_POST['name']);

        // The code looks like PRG 202S
        if (!preg_match('/^[A-Z]{3} [0-9]{3}[A-Z]$/', $code)) {
            set_flash('error', 'Module code must look like PRG 202S.');
        } elseif (strlen($name) < 3) {
            set_flash('error', 'Module name must be at least 3 characters.');
        } elseif (fetch_one('SELECT id FROM modules WHERE code = ?', 's', [$code])) {
            set_flash('error', 'That module code already exists.');
        } else {
            run_sql('INSERT INTO modules (code, name) VALUES (?, ?)', 'ss', [$code, $name]);
            set_flash('ok', 'Module ' . $code . ' added.');
        }

    } elseif ($action === 'delete_module') {
        // A module that still has groups cannot be deleted
        $used = fetch_one('SELECT COUNT(*) AS c FROM study_groups WHERE module_id = ?', 'i', [$id]);
        if ($used['c'] > 0) {
            set_flash('error', 'This module still has groups. Delete them first.');
        } else {
            run_sql('DELETE FROM modules WHERE id = ?', 'i', [$id]);
            set_flash('ok', 'Module deleted.');
        }

    } elseif ($action === 'delete_message') {
        run_sql('DELETE FROM contact_messages WHERE id = ?', 'i', [$id]);
        set_flash('ok', 'Message deleted.');
    }

    // Reload the page so a refresh does not repeat the action
    redirect($back);
}

// Which tab is open?
$tab = 'panelPending';
if (isset($_GET['tab']) && in_array($_GET['tab'], $tabs)) {
    $tab = $_GET['tab'];
}

// Marks the open tab button and panel (the others stay empty)
$active = [];
$show = [];
foreach ($tabs as $t) {
    $active[$t] = '';
    $show[$t] = '';
}
$active[$tab] = ' active';
$show[$tab] = ' show';

// Data for the page
$groups = fetch_all('SELECT * FROM v_group_cards ORDER BY created_at DESC');

$pending = [];
foreach ($groups as $g) {
    if ($g['status'] === 'pending') {
        $pending[] = $g;
    }
}

$reports = fetch_all(
    "SELECT r.id, r.reason, g.id AS group_id, g.title, m.code AS module_code, u.full_name AS reporter
       FROM reports r
       JOIN study_groups g ON g.id = r.group_id
       JOIN modules m ON m.id = g.module_id
       JOIN users u ON u.id = r.reporter_id
      WHERE r.status = 'open'
      ORDER BY r.created_at"
);
$modules = fetch_all('SELECT m.id, m.code, m.name, (SELECT COUNT(*) FROM study_groups g WHERE g.module_id = m.id) AS group_count FROM modules m ORDER BY m.code');
$messages = fetch_all('SELECT id, category, name, email, subject, message, created_at FROM contact_messages ORDER BY created_at DESC');

// A small button that sends a POST (Approve, Delete...)
// $tab_name is the tab to come back to
function admin_button($action, $id, $label, $class, $tab_name, $confirm = '') {
    $html = '<form method="post" action="admin.php" class="inline-form"';
    if ($confirm !== '') {
        $html .= ' onsubmit="return confirm(\'' . $confirm . '\')"';
    }
    $html .= '>';
    $html .= csrf_field();
    $html .= '<input type="hidden" name="action" value="' . $action . '">';
    $html .= '<input type="hidden" name="id" value="' . (int)$id . '">';
    $html .= '<input type="hidden" name="tab" value="' . $tab_name . '">';
    $html .= '<button type="submit" class="btn ' . $class . ' btn-small">' . $label . '</button></form>';
    return $html;
}

// The group name cell used in two tables
function group_cell($g) {
    return '<strong>' . e($g['module_code']) . ' · ' . e($g['title']) . '</strong>'
        . '<small>' . e($g['host_name']) . ' · ' . e($g['study_format']) . ' · ' . (int)$g['max_members'] . ' max · '
        . e(short_day($g['day_of_week'])) . ' ' . e(fmt_time($g['start_time'])) . '</small>';
}

$pageTitle = 'Administration';
$activePage = 'admin';
require __DIR__ . '/includes/header.php';
?>

<main>
    <div class="container">
        <div class="page-title">
            <span class="small-label">ADMINISTRATION</span>
            <h1>StudyMatch operations</h1>
            <p class="muted">Review group quality, respond to reports and maintain the NUST module directory.</p>
        </div>
        <?= render_flash() ?>

        <div class="stats">
            <div class="card"><h2>All groups</h2><p class="stat-number"><?= count($groups) ?></p></div>
            <div class="card"><h2>Pending approvals</h2><p class="stat-number" style="color:#e8a020"><?= count($pending) ?></p></div>
            <div class="card"><h2>Open reports</h2><p class="stat-number" style="color:#c0392b"><?= count($reports) ?></p></div>
            <div class="card"><h2>Active modules</h2><p class="stat-number"><?= count($modules) ?></p></div>
        </div>

        <div class="tabs">
            <button type="button" class="tab<?= $active['panelGroups'] ?>" data-panel="panelGroups">All groups</button>
            <button type="button" class="tab<?= $active['panelPending'] ?>" data-panel="panelPending">Pending approvals</button>
            <button type="button" class="tab<?= $active['panelReports'] ?>" data-panel="panelReports">Reports</button>
            <button type="button" class="tab<?= $active['panelModules'] ?>" data-panel="panelModules">Module management</button>
            <button type="button" class="tab<?= $active['panelMessages'] ?>" data-panel="panelMessages">Contact messages</button>
        </div>

        <div class="two-column">
            <div>
                <!-- All groups -->
                <section class="card tab-panel<?= $show['panelGroups'] ?>" id="panelGroups">
                    <h2>All groups</h2>
                    <table>
                        <thead><tr><th>Group</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($groups as $g): ?>
                            <tr>
                                <td><?= group_cell($g) ?></td>
                                <td><?= status_badge($g['status']) ?></td>
                                <td>
                                    <a class="btn btn-outline btn-small" href="group.php?id=<?= (int)$g['id'] ?>">View</a>
                                    <?= admin_button('delete_group', $g['id'], 'Delete', 'btn-red', 'panelGroups', 'Delete this group permanently?') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

                <!-- Pending approvals -->
                <section class="card tab-panel<?= $show['panelPending'] ?>" id="panelPending">
                    <h2>Pending approvals · <?= count($pending) ?></h2>
                    <?php if (!$pending): ?><p class="muted">Nothing waiting for review.</p><?php else: ?>
                    <table>
                        <thead><tr><th>Group</th><th>Submitted</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($pending as $g): ?>
                            <tr>
                                <td><?= group_cell($g) ?></td>
                                <td><?= e(substr($g['created_at'], 0, 16)) ?></td>
                                <td>
                                    <a class="btn btn-outline btn-small" href="group.php?id=<?= (int)$g['id'] ?>">Review</a>
                                    <?= admin_button('approve_group', $g['id'], 'Approve', 'btn-green', 'panelPending') ?>
                                    <?= admin_button('reject_group', $g['id'], 'Reject', 'btn-red', 'panelPending') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </section>

                <!-- Reports -->
                <section class="card tab-panel<?= $show['panelReports'] ?>" id="panelReports">
                    <h2>Open reports · <?= count($reports) ?></h2>
                    <?php if (!$reports): ?><p class="muted">No open reports.</p><?php else: ?>
                    <table>
                        <thead><tr><th>Group</th><th>Reason</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <td><strong><?= e($r['module_code']) ?> · <?= e($r['title']) ?></strong><small>Reported by <?= e($r['reporter']) ?></small></td>
                                <td><?= e($r['reason']) ?></td>
                                <td>
                                    <a class="btn btn-outline btn-small" href="group.php?id=<?= (int)$r['group_id'] ?>">View group</a>
                                    <?= admin_button('resolve_report', $r['id'], 'Resolve', 'btn-green', 'panelReports') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </section>

                <!-- Modules -->
                <section class="card tab-panel<?= $show['panelModules'] ?>" id="panelModules">
                    <h2>Module management</h2>
                    <table>
                        <thead><tr><th>Code</th><th>Name</th><th>Groups</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($modules as $m): ?>
                            <tr>
                                <td><strong><?= e($m['code']) ?></strong></td>
                                <td><?= e($m['name']) ?></td>
                                <td><?= (int)$m['group_count'] ?></td>
                                <td><?= admin_button('delete_module', $m['id'], 'Delete', 'btn-red', 'panelModules', 'Delete this module?') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

                <!-- Contact messages -->
                <section class="card tab-panel<?= $show['panelMessages'] ?>" id="panelMessages">
                    <h2>Contact messages · <?= count($messages) ?></h2>
                    <?php if (!$messages): ?><p class="muted">No messages yet.</p><?php else: ?>
                    <table>
                        <thead><tr><th>From</th><th>Message</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($messages as $c): ?>
                            <tr>
                                <td><strong><?= e($c['name']) ?></strong><small><?= e($c['email']) ?> · <?= e($c['category']) ?><br><?= e(substr($c['created_at'], 0, 16)) ?></small></td>
                                <td><strong><?= e($c['subject']) ?></strong><br><?= nl2br(e($c['message'])) ?></td>
                                <td><?= admin_button('delete_message', $c['id'], 'Delete', 'btn-red', 'panelMessages', 'Delete this message?') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </section>
            </div>

            <div class="stack">
                <section class="card">
                    <h2>Review checklist</h2>
                    <ul class="plain-list">
                        <li><label><input type="checkbox" style="width:auto"> Valid NUST module</label></li>
                        <li><label><input type="checkbox" style="width:auto"> Clear study goal</label></li>
                        <li><label><input type="checkbox" style="width:auto"> Appropriate schedule</label></li>
                        <li><label><input type="checkbox" style="width:auto"> Safe public location</label></li>
                        <li><label><input type="checkbox" style="width:auto"> Reasonable capacity</label></li>
                    </ul>
                </section>

                <section class="card">
                    <h2>Add a module</h2>
                    <form method="post" action="admin.php" class="validate">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add_module">
                        <input type="hidden" name="tab" value="panelModules">
                        <div class="field">
                            <label for="moduleCode">Module code</label>
                            <input type="text" id="moduleCode" name="code" placeholder="e.g. PRG 202S" maxlength="8" required>
                        </div>
                        <div class="field">
                            <label for="moduleName">Module name</label>
                            <input type="text" id="moduleName" name="name" placeholder="Programming 2" maxlength="120" required>
                        </div>
                        <button type="submit" class="btn btn-green btn-small">Add module</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>