<?php
// Small helper functions shared by the group pages
// (auth.php must be included first, because we use e() here)

// Lists used by the forms
const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const FORMATS_GROUP = ['In person', 'Online'];

// "16:00:00" becomes "16:00"
function fmt_time($t) {
    return substr($t, 0, 5);
}

// "Tuesday" becomes "Tue"
function short_day($d) {
    return substr($d, 0, 3);
}

// How many places are left in a group (never below 0)
function spaces_left($g) {
    return max(0, (int)$g['max_members'] - (int)$g['member_count']);
}

// Green badge with the spots left, or a red "Full" badge
function spots_badge($g) {
    $n = spaces_left($g);
    if ($n === 0) {
        return '<span class="badge badge-red">Full</span>';
    }
    return '<span class="badge badge-spots">' . $n . ($n === 1 ? ' spot' : ' spots') . '</span>';
}

// Badge showing if a group is pending, approved or rejected
function status_badge($status) {
    $classes = ['pending' => 'badge-pending', 'approved' => 'badge-spots', 'rejected' => 'badge-red'];
    $class = $classes[$status] ?? 'badge-pending';
    return '<span class="badge ' . $class . '">' . e(strtoupper($status)) . '</span>';
}

// One group card (used on Browse and Dashboard)
// $actionHtml is an optional button/link made by the page that calls this
function render_group_card($g, $actionHtml = '') {
    $when = short_day($g['day_of_week']) . ' · ' . fmt_time($g['start_time']) . '-' . fmt_time($g['end_time']);

    $html  = '<article class="card group-card">';
    $html .= '<div class="card-top"><span class="small-label">' . e($g['module_code']) . '</span>' . spots_badge($g) . '</div>';
    $html .= '<h3>' . e($g['title']) . '</h3>';
    $html .= '<p class="muted">' . e($g['host_programme']) . ' · Year ' . (int)$g['host_year'] . '</p>';
    $html .= '<div class="tags"><span class="tag">' . e($when) . '</span>'
           . '<span class="tag">' . e($g['location']) . '</span>'
           . '<span class="tag tag-amber">' . e($g['study_goal']) . '</span></div>';

    if ($actionHtml !== '') {
        $html .= $actionHtml;
    } else {
        $html .= '<a href="group.php?id=' . (int)$g['id'] . '" class="btn btn-outline btn-small">View group</a>';
    }

    $html .= '</article>';
    return $html;
}

// Runs a SELECT and returns all the rows
// $types is like "si" (s = text, i = number), $params are the values
function fetch_all($sql, $types = '', $params = []) {
    $stmt = db()->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

// Same as fetch_all but gives back only the first row (or null)
function fetch_one($sql, $types = '', $params = []) {
    $rows = fetch_all($sql, $types, $params);
    return $rows[0] ?? null;
}

// Runs INSERT / UPDATE / DELETE and returns how many rows changed
function run_sql($sql, $types, $params) {
    $stmt = db()->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $changed = $stmt->affected_rows;
    $stmt->close();
    return (int)$changed;
}