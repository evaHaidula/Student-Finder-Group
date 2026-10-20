<?php
require __DIR__ . '/includes/auth.php';
$pageTitle  = 'Home';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>
<main>
    <!-- Hero with search box -->
    <section class="hero">
        <div class="container">
            <span class="pill">NUST STUDENTS · VERIFIED COMMUNITY</span>
            <h1>Find the right people to study with.</h1>
            <p class="lead">Match by module, programme and real availability, then review the group before you request to join.</p>

            <form class="search-box" method="get" action="browse.php">
                <div class="field">
                    <label for="module">Module</label>
                    <input type="text" id="module" name="q" maxlength="60" placeholder="Search module code or name">
                </div>
                <div class="row-2">
                    <div class="field">
                        <label for="day">Day</label>
                        <select id="day" name="day">
                            <option value="">Any day</option>
                            <option>Monday</option>
                            <option>Tuesday</option>
                            <option>Wednesday</option>
                            <option>Thursday</option>
                            <option>Friday</option>
                            <option>Saturday</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="format">Format</label>
                        <select id="format" name="format">
                            <option value="">Any format</option>
                            <option>In person</option>
                            <option>Online</option>
                        </select>
                    </div>
                </div>
                <div class="button-row">
                    <button type="submit" class="btn btn-amber">Find a Group</button>
                    <a href="create-group.php" class="btn btn-outline">Create a Group</a>
                </div>
            </form>
        </div>
    </section>

    <!-- Featured groups -->
    <section class="section section-light">
        <div class="container">
            <h2>Featured study groups</h2>
            <div class="grid-3">
                <article class="card">
                    <div class="card-top"><span class="small-label">CPS 212S</span><span class="badge badge-spots">2 spots</span></div>
                    <h3>Data Structures &amp; Algorithms</h3>
                    <p class="muted">BSc Computer Science · Year 2</p>
                    <div class="tags"><span class="tag">Tue · 16:00-18:00</span><span class="tag">Library, Room 3</span><span class="tag tag-amber">Exam prep</span></div>
                    <p class="muted">Strong match: same programme, module and Tuesday availability.</p>
                </article>
                <article class="card">
                    <div class="card-top"><span class="small-label">MAT 201S</span><span class="badge badge-spots">2 spots</span></div>
                    <h3>Discrete Mathematics II</h3>
                    <p class="muted">BSc Computer Science · Year 2</p>
                    <div class="tags"><span class="tag">Tue · 16:00-18:00</span><span class="tag">Library, Room 3</span><span class="tag tag-amber">Exam prep</span></div>
                    <p class="muted">Strong match: same programme, module and Tuesday availability.</p>
                </article>
                <article class="card">
                    <div class="card-top"><span class="small-label">NET 311S</span><span class="badge badge-spots">2 spots</span></div>
                    <h3>Computer Networks</h3>
                    <p class="muted">BSc Computer Science · Year 2</p>
                    <div class="tags"><span class="tag">Tue · 16:00-18:00</span><span class="tag">Library, Room 3</span><span class="tag tag-amber">Exam prep</span></div>
                    <p class="muted">Strong match: same programme, module and Tuesday availability.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Four steps -->
    <section class="section section-green">
        <div class="container">
            <h2>From module to match in four steps</h2>
            <div class="grid-4">
                <div class="step"><h3>1 · Select a module</h3><p>Clear details, verified student profiles and transparent group capacity.</p></div>
                <div class="step"><h3>2 · Match programme + availability</h3><p>Clear details, verified student profiles and transparent group capacity.</p></div>
                <div class="step"><h3>3 · Review the group</h3><p>Clear details, verified student profiles and transparent group capacity.</p></div>
                <div class="step"><h3>4 · Request to join</h3><p>Clear details, verified student profiles and transparent group capacity.</p></div>
            </div>
        </div>
    </section>

    <!-- Trust signals -->
    <section class="trust-bar">
        <div class="container">
            <ul>
                <li>NUST email verification</li>
                <li>Student-led moderation</li>
                <li>Report and safety tools</li>
                <li>Approval before publishing</li>
            </ul>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
