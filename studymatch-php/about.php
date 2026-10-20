<?php
require __DIR__ . '/includes/auth.php';
$pageTitle  = 'About';
$activePage = 'about';
$bodyClass  = 'dark';
require __DIR__ . '/includes/header.php';
?>
<main>
    <section class="section" style="text-align:center">
        <div class="container">
            <span class="small-label">ABOUT STUDYMATCH</span>
            <h1>Better study outcomes start with the right peers.</h1>
            <p class="muted">StudyMatch helps NUST students turn shared modules and compatible schedules into dependable study groups.</p>
        </div>
    </section>

    <section class="section section-deep">
        <div class="container">
            <div class="grid-2">
                <article class="card">
                    <h2>Our mission</h2>
                    <p class="muted">Make peer learning easier to discover, safer to join and practical enough to fit student life.</p>
                </article>
                <article class="card">
                    <h2>Built for NUST</h2>
                    <p class="muted">Real module codes, programme context, campus locations and verified student identities keep matches relevant.</p>
                </article>
            </div>

            <p class="small-label" style="margin-top:30px">MATCHING METHOD</p>
            <h2>Relevant before popular</h2>
            <p class="muted">Groups are ranked by the signals that determine whether students can actually work well together.</p>

            <div class="grid-4">
                <article class="card"><h3>1 · Module fit</h3><p class="muted">A clear match signal explains why each recommendation appears.</p></article>
                <article class="card"><h3>2 · Programme + year</h3><p class="muted">A clear match signal explains why each recommendation appears.</p></article>
                <article class="card"><h3>3 · Shared availability</h3><p class="muted">A clear match signal explains why each recommendation appears.</p></article>
                <article class="card"><h3>4 · Format + location</h3><p class="muted">A clear match signal explains why each recommendation appears.</p></article>
            </div>

            <div class="grid-4" style="margin-top:16px">
                <article class="card"><h3>NUST email verification</h3><span class="badge badge-amber">TRUST SIGNAL</span></article>
                <article class="card"><h3>Approval before publishing</h3><span class="badge badge-amber">TRUST SIGNAL</span></article>
                <article class="card"><h3>Visible capacity and hosts</h3><span class="badge badge-amber">TRUST SIGNAL</span></article>
                <article class="card"><h3>Reporting and moderation</h3><span class="badge badge-amber">TRUST SIGNAL</span></article>
            </div>

            <div class="cta-band" style="margin-top:30px">
                <h2>Ready to find your study group?</h2>
                <a href="browse.php" class="btn btn-outline">Find a Group</a>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
