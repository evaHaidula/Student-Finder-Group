<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
start_secure_session();

// Read the search choices from the URL
$q = '';
$day = '';
$format = '';
if (isset($_GET['q'])) {
    $q = trim($_GET['q']);
}
if (isset($_GET['day'])) {
    $day = $_GET['day'];
}
if (isset($_GET['format'])) {
    $format = $_GET['format'];
}

// Build the query: only approved groups are shown
$sql = "SELECT * FROM v_group_cards WHERE status = 'approved'";
$types = '';
$params = [];

if ($q !== '') {
    $sql .= ' AND (module_code LIKE ? OR module_name LIKE ? OR title LIKE ?)';
    $like = '%' . $q . '%';
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($day !== '') {
    $sql .= ' AND day_of_week = ?';
    $types .= 's';
    $params[] = $day;
}
if ($format !== '') {
    $sql .= ' AND study_format = ?';
    $types .= 's';
    $params[] = $format;
}
$sql .= ' ORDER BY created_at DESC';

$groups = fetch_all($sql, $types, $params);

$pageTitle = 'Browse groups';
$activePage = 'browse';
require __DIR__ . '/includes/header.php';
?>

<main>
    <div class="container">
        <div class="page-title">
            <span class="small-label" style="color:#1a5c3a">GROUP FINDER</span>
            <h1>Browse groups</h1>
            <p class="muted">Search approved study groups and use the filters to narrow them down.</p>
        </div>

        <div class="two-column browse-simple">
            <form class="card" method="get" action="browse.php">
                <h2>Search &amp; filters</h2>
                <div class="field">
                    <label for="fModule">Module or group name</label>
                    <input type="text" id="fModule" name="q" maxlength="60" placeholder="e.g. CPS 212S" value="<?= e($q) ?>">
                </div>
                <div class="field">
                    <label for="fDay">Day</label>
                    <select id="fDay" name="day">
                        <option value="">Any day</option>
                        <?php foreach (DAYS as $d): ?>
                            <option <?= $day === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="fFormat">Format</label>
                    <select id="fFormat" name="format">
                        <option value="">Any format</option>
                        <?php foreach (FORMATS_GROUP as $f): ?>
                            <option <?= $format === $f ? 'selected' : '' ?>><?= e($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-green btn-full">Update results</button>
                <a href="browse.php" class="btn btn-outline btn-full" style="margin-top:8px">Clear filters</a>
            </form>

            <section>
                <p class="small-label muted"><?= count($groups) ?> <?= count($groups) == 1 ? 'RESULT' : 'RESULTS' ?> · NEWEST FIRST</p>
                <div class="results">
                    <?php if (!$groups): ?>
                        <div class="card"><p>No groups match these filters. Try clearing them, or <a href="create-group.php">create the group yourself</a>.</p></div>
                    <?php endif; ?>
                    <?php foreach ($groups as $g): ?>
                        <?= render_group_card($g) ?>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>