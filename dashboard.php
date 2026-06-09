<?php
//#region GUARD
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

require_once 'db.php';

$user_id   = $_SESSION['user_id'];
$full_name = htmlspecialchars($_SESSION['full_name']);
$email     = htmlspecialchars($_SESSION['email']);

$stmt = $pdo->prepare('SELECT avatar FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$avatar_path = htmlspecialchars($stmt->fetchColumn() ?: '');
//#endregion

//#region HELPERS
function rp($n) { return 'Rp&nbsp;' . number_format((float)$n, 0, ',', '.'); }

function statusBadge($s) {
    $map = [
        'active'    => ['#22C55E', 'rgba(34,197,94,0.12)'],
        'inactive'  => ['#94A3B8', 'rgba(148,163,184,0.12)'],
        'completed' => ['#60A5FA', 'rgba(96,165,250,0.12)'],
    ];
    [$c, $bg] = $map[$s] ?? $map['inactive'];
    return "<span style=\"background:{$bg};color:{$c};border:1px solid {$c}55;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;white-space:nowrap;\">".ucfirst($s)."</span>";
}
//#endregion

//#region FETCH DATA
$stat_students    = $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$stat_courses     = $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
$stat_enrollments = $pdo->query('SELECT COUNT(*) FROM enrollments WHERE status = "active"')->fetchColumn();
$stat_unpaid      = $pdo->query('SELECT COALESCE(SUM(balance),0) FROM student_total_payment WHERE balance > 0')->fetchColumn();

$students = $pdo->query('
    SELECT s.*, COALESCE(stp.total_billed,0) AS total_billed,
           COALESCE(stp.total_paid,0) AS total_paid,
           COALESCE(stp.balance,0)    AS balance
    FROM students s
    LEFT JOIN student_total_payment stp ON stp.student_id = s.id
    ORDER BY s.full_name
')->fetchAll();

$courses = $pdo->query('SELECT * FROM courses ORDER BY title')->fetchAll();

$enrollments = $pdo->query('
    SELECT e.*, s.full_name AS student_name, c.title AS course_title
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    JOIN courses  c ON c.id = e.course_id
    ORDER BY e.start_date DESC
')->fetchAll();

$schedule = $pdo->query('
    SELECT sc.*, c.title AS course_title
    FROM schedule sc
    JOIN courses c ON c.id = sc.course_id
    ORDER BY FIELD(sc.day_of_week,"Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"), sc.start_time
')->fetchAll();

$payments = $pdo->query('
    SELECT p.*, s.full_name AS student_name, c.title AS course_title
    FROM payments p
    JOIN students    s ON s.id = p.student_id
    JOIN enrollments e ON e.id = p.enrollment_id
    JOIN courses     c ON c.id = e.course_id
    ORDER BY p.payment_date DESC
')->fetchAll();

$recent = $pdo->query('
    SELECT e.start_date, s.full_name AS student_name, c.title AS course_title, e.status
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    JOIN courses  c ON c.id = e.course_id
    ORDER BY e.created_at DESC LIMIT 5
')->fetchAll();
//#endregion
?>

<!-- #region HTML Start -->
<!DOCTYPE html>
<html lang="en">
<!-- #region HEAD -->
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>StackLab Academy — Dashboard</title>

  <script src="https://cdn.tailwindcss.com"></script>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="styles.css" />
</head>
<!-- #endregion -->
<body>

<div class="app-shell">

  <!-- #region SIDEBAR -->
  <aside class="sidebar" id="sidebar">

    <div class="brand animate-item">
      <div class="brand-logo-wrap">
        <img src="logo.png" alt="StackLab Academy" class="brand-logo" />
      </div>
      <div class="brand-text">
        <span class="brand-name">STACKLAB</span>
        <span class="brand-sub">ACADEMY™</span>
      </div>
    </div>

    <div class="profile-card animate-item">
      <div class="profile-avatar-wrap">
        <div class="profile-avatar">
          <?php if ($avatar_path): ?>
            <img src="<?= $avatar_path ?>" alt="Profile picture" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
          <?php else: ?>
            <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" class="avatar-svg">
              <defs>
                <linearGradient id="avatarGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" style="stop-color:#93C5FD"/>
                  <stop offset="100%" style="stop-color:#1A56DB"/>
                </linearGradient>
              </defs>
              <circle cx="40" cy="40" r="40" fill="url(#avatarGrad)"/>
              <circle cx="40" cy="30" r="14" fill="white" opacity="0.9"/>
              <ellipse cx="40" cy="65" rx="22" ry="17" fill="white" opacity="0.9"/>
            </svg>
          <?php endif; ?>
        </div>
        <span class="online-dot"></span>
      </div>
      <div class="profile-info">
        <span class="profile-name"><?= $full_name ?></span>
        <span class="profile-email"><?= $email ?></span>
      </div>
    </div>

    <nav class="nav-menu animate-item">
      <ul class="nav-list">
        <li class="nav-item active" data-tab="dashboard">
          <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></span>
          <span class="nav-label">Dashboard</span>
          <span class="nav-pip"></span>
        </li>
        <li class="nav-item" data-tab="students">
          <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
          <span class="nav-label">Students</span>
        </li>
        <li class="nav-item" data-tab="courses">
          <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span>
          <span class="nav-label">Courses</span>
        </li>
        <li class="nav-item" data-tab="enrollments">
          <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg></span>
          <span class="nav-label">Enrollments</span>
        </li>
        <li class="nav-item" data-tab="schedule">
          <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
          <span class="nav-label">Schedule</span>
        </li>
        <li class="nav-item" data-tab="payments">
          <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></span>
          <span class="nav-label">Payments</span>
        </li>
        <li class="nav-item" data-tab="profile">
          <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <span class="nav-label">Edit Profile</span>
        </li>
      </ul>
    </nav>

    <div style="margin-top:auto;">
      <a href="auth/logout.php" class="btn-logout">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:17px;height:17px;flex-shrink:0;"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Log Out
      </a>
    </div>
    <div class="sidebar-deco"></div>
  </aside>
  <!-- #endregion -->

  <!-- #region WORKSPACE -->
  <main class="workspace" id="workspace">

    <button id="hamburgerBtn" class="hamburger-btn" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>

    <header class="workspace-header animate-item">
      <div class="greeting-block">
        <h1 class="greeting-title">Hello, <?= $full_name ?></h1>
        <p class="greeting-sub" id="currentDate">Loading…</p>
      </div>
    </header>

    <!-- #region PANEL: Dashboard -->
    <div id="panel-dashboard" class="workspace-panel">
      <div class="animate-item dash-overview">

        <div class="grid grid-cols-2 gap-3">
          <div class="teacher-stat-card">
            <span class="teacher-stat-value"><?= $stat_students ?></span>
            <span class="teacher-stat-label">Total Students</span>
          </div>
          <div class="teacher-stat-card" style="background:rgba(124,58,237,0.08);border-color:rgba(124,58,237,0.15);">
            <span class="teacher-stat-value" style="color:var(--violet-light);"><?= $stat_courses ?></span>
            <span class="teacher-stat-label">Active Courses</span>
          </div>
          <div class="teacher-stat-card" style="background:rgba(13,148,136,0.08);border-color:rgba(13,148,136,0.15);">
            <span class="teacher-stat-value" style="color:var(--teal-light);"><?= $stat_enrollments ?></span>
            <span class="teacher-stat-label">Active Enrollments</span>
          </div>
          <div class="teacher-stat-card" style="background:rgba(234,179,8,0.08);border-color:rgba(234,179,8,0.15);">
            <span class="teacher-stat-value" style="color:#FCD34D;font-size:22px;"><?= rp($stat_unpaid) ?></span>
            <span class="teacher-stat-label">Unpaid Balance</span>
          </div>
        </div>

        <div class="tasks-pane">
          <h2 class="pane-title">Recent Enrollments</h2>
          <?php if (empty($recent)): ?>
            <p class="text-sm text-gray-500 m-0">No enrollments yet.</p>
          <?php else: ?>
            <table class="w-full text-sm">
              <thead>
                <tr class="text-xs uppercase text-gray-400 border-b border-white/10">
                  <th class="text-left py-2 pr-3">Student</th>
                  <th class="text-left py-2 pr-3 hidden sm:table-cell">Course</th>
                  <th class="text-left py-2 pr-3">Status</th>
                  <th class="text-left py-2 hidden sm:table-cell">Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent as $r): ?>
                <tr class="border-b border-white/5">
                  <td class="py-2 pr-3 font-semibold text-white"><?= htmlspecialchars($r['student_name']) ?></td>
                  <td class="py-2 pr-3 text-gray-400 hidden sm:table-cell"><?= htmlspecialchars($r['course_title']) ?></td>
                  <td class="py-2 pr-3"><?= statusBadge($r['status']) ?></td>
                  <td class="py-2 text-gray-500 hidden sm:table-cell"><?= $r['start_date'] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

      </div>
    </div>
    <!-- #endregion -->

    <!-- #region PANEL: Students -->
    <div id="panel-students" class="workspace-panel" style="display:none;">
      <div class="animate-item">

        <div class="flex items-center justify-between mb-5">
          <h2 class="pane-title" style="margin:0;">Students (<?= count($students) ?>)</h2>
          <button class="btn-enroll" onclick="openStudentModal('add')">+ Add Student</button>
        </div>

        <div style="overflow-x:auto;border-radius:16px;">
          <table class="w-full text-sm" style="background:var(--dash-card);border:1px solid var(--dash-border);border-radius:16px;border-collapse:separate;border-spacing:0;">
            <thead>
              <tr class="text-xs uppercase text-gray-400 border-b" style="border-color:var(--dash-border);">
                <th class="text-left px-4 py-3">Name</th>
                <th class="text-left px-4 py-3 hidden md:table-cell">Email</th>
                <th class="text-left px-4 py-3 hidden md:table-cell">Phone</th>
                <th class="text-left px-4 py-3 hidden lg:table-cell">Billed</th>
                <th class="text-left px-4 py-3 hidden lg:table-cell">Paid</th>
                <th class="text-right px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($students)): ?>
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No students yet.</td></tr>
              <?php else: ?>
                <?php foreach ($students as $s): ?>
                <tr class="border-b hover:bg-white/5" style="border-color:var(--dash-border);">
                  <td class="px-4 py-3 font-semibold text-white"><?= htmlspecialchars($s['full_name']) ?></td>
                  <td class="px-4 py-3 text-gray-400 hidden md:table-cell"><?= htmlspecialchars($s['email'] ?? '–') ?></td>
                  <td class="px-4 py-3 text-gray-400 hidden md:table-cell"><?= htmlspecialchars($s['phone'] ?? '–') ?></td>
                  <td class="px-4 py-3 text-gray-400 hidden lg:table-cell"><?= rp($s['total_billed']) ?></td>
                  <td class="px-4 py-3 text-green-400 hidden lg:table-cell"><?= rp($s['total_paid']) ?></td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex gap-2">
                        <button class="tl-edit-btn"
                        onclick="openStudentModal('edit', this)"
                        data-id="<?= $s['id'] ?>"
                        data-name="<?= htmlspecialchars($s['full_name'], ENT_QUOTES) ?>"
                        data-email="<?= htmlspecialchars($s['email'] ?? '', ENT_QUOTES) ?>"
                        data-phone="<?= htmlspecialchars($s['phone'] ?? '', ENT_QUOTES) ?>"
                        data-address="<?= htmlspecialchars($s['address'] ?? '', ENT_QUOTES) ?>"
                        title="Edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <button class="tl-del"
                        onclick="openDeleteModal('auth/student_delete.php', <?= $s['id'] ?>, <?= htmlspecialchars(json_encode('Delete student "' . $s['full_name'] . '"?')) ?>)"
                        title="Delete">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
    <!-- #endregion -->

    <!-- #region PANEL: Courses -->
    <div id="panel-courses" class="workspace-panel" style="display:none;">
      <div class="animate-item">

        <div class="flex items-center justify-between mb-5">
          <h2 class="pane-title" style="margin:0;">Courses (<?= count($courses) ?>)</h2>
          <button class="btn-enroll" onclick="openCourseModal('add')">+ Add Course</button>
        </div>

        <div style="overflow-x:auto;border-radius:16px;">
          <table class="w-full text-sm" style="background:var(--dash-card);border:1px solid var(--dash-border);border-radius:16px;border-collapse:separate;border-spacing:0;">
            <thead>
              <tr class="text-xs uppercase text-gray-400 border-b" style="border-color:var(--dash-border);">
                <th class="text-left px-4 py-3">Title</th>
                <th class="text-left px-4 py-3 hidden md:table-cell">Topic</th>
                <th class="text-left px-4 py-3">Price / Month</th>
                <th class="text-right px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($courses)): ?>
                <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500">No courses yet.</td></tr>
              <?php else: ?>
                <?php foreach ($courses as $c): ?>
                <tr class="border-b hover:bg-white/5" style="border-color:var(--dash-border);">
                  <td class="px-4 py-3 font-semibold text-white"><?= htmlspecialchars($c['title']) ?></td>
                  <td class="px-4 py-3 text-gray-400 hidden md:table-cell"><?= htmlspecialchars($c['topic'] ?? '–') ?></td>
                  <td class="px-4 py-3 text-teal-400 font-semibold"><?= rp($c['price_per_month']) ?></td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex gap-2">
                      <button class="tl-edit-btn"
                        onclick="openCourseModal('edit', this)"
                        data-id="<?= $c['id'] ?>"
                        data-title="<?= htmlspecialchars($c['title'], ENT_QUOTES) ?>"
                        data-topic="<?= htmlspecialchars($c['topic'] ?? '', ENT_QUOTES) ?>"
                        data-price="<?= $c['price_per_month'] ?>"
                        data-desc="<?= htmlspecialchars($c['description'] ?? '', ENT_QUOTES) ?>"
                        title="Edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <button class="tl-del"
                        onclick="openDeleteModal('auth/course_delete.php', <?= $c['id'] ?>, <?= htmlspecialchars(json_encode('Delete course "' . $c['title'] . '"?')) ?>)"
                        title="Delete">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
    <!-- #endregion -->

    <!-- #region PANEL: Enrollments -->
    <div id="panel-enrollments" class="workspace-panel" style="display:none;">
      <div class="animate-item">

        <div class="flex items-center justify-between mb-5">
          <h2 class="pane-title" style="margin:0;">Enrollments (<?= count($enrollments) ?>)</h2>
          <button class="btn-enroll" onclick="openEnrollmentModal('add')">+ Enroll Student</button>
        </div>

        <div style="overflow-x:auto;border-radius:16px;">
          <table class="w-full text-sm" style="background:var(--dash-card);border:1px solid var(--dash-border);border-radius:16px;border-collapse:separate;border-spacing:0;">
            <thead>
              <tr class="text-xs uppercase text-gray-400 border-b" style="border-color:var(--dash-border);">
                <th class="text-left px-4 py-3">Student</th>
                <th class="text-left px-4 py-3 hidden md:table-cell">Course</th>
                <th class="text-left px-4 py-3 hidden md:table-cell">Start Date</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-right px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($enrollments)): ?>
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No enrollments yet.</td></tr>
              <?php else: ?>
                <?php foreach ($enrollments as $e): ?>
                <tr class="border-b hover:bg-white/5" style="border-color:var(--dash-border);">
                  <td class="px-4 py-3 font-semibold text-white"><?= htmlspecialchars($e['student_name']) ?></td>
                  <td class="px-4 py-3 text-gray-400 hidden md:table-cell"><?= htmlspecialchars($e['course_title']) ?></td>
                  <td class="px-4 py-3 text-gray-400 hidden md:table-cell"><?= $e['start_date'] ?></td>
                  <td class="px-4 py-3"><?= statusBadge($e['status']) ?></td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex gap-2">
                      <button class="tl-edit-btn"
                        onclick="openEnrollmentModal('edit', this)"
                        data-id="<?= $e['id'] ?>"
                        data-student-id="<?= $e['student_id'] ?>"
                        data-course-id="<?= $e['course_id'] ?>"
                        data-start-date="<?= $e['start_date'] ?>"
                        data-status="<?= $e['status'] ?>"
                        title="Edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <button class="tl-del"
                        onclick="openDeleteModal('auth/enrollment_delete.php', <?= $e['id'] ?>, 'Remove this enrollment?', {student_id: <?= $e['student_id'] ?>, course_id: <?= $e['course_id'] ?>})"
                        title="Delete">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
    <!-- #endregion -->

    <!-- #region PANEL: Schedule -->
    <div id="panel-schedule" class="workspace-panel" style="display:none;">
      <div class="animate-item">

        <h2 class="pane-title">Schedule</h2>

        <div style="overflow-x:auto;border-radius:16px;">
          <table class="w-full text-sm" style="background:var(--dash-card);border:1px solid var(--dash-border);border-radius:16px;border-collapse:separate;border-spacing:0;">
            <thead>
              <tr class="text-xs uppercase text-gray-400 border-b" style="border-color:var(--dash-border);">
                <th class="text-left px-4 py-3">Course</th>
                <th class="text-left px-4 py-3">Day</th>
                <th class="text-left px-4 py-3">Start</th>
                <th class="text-left px-4 py-3 hidden md:table-cell">End</th>
                <th class="text-left px-4 py-3 hidden md:table-cell">Room</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($schedule)): ?>
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No schedule entries yet.</td></tr>
              <?php else: ?>
                <?php foreach ($schedule as $sc): ?>
                <tr class="border-b hover:bg-white/5" style="border-color:var(--dash-border);">
                  <td class="px-4 py-3 font-semibold text-white"><?= htmlspecialchars($sc['course_title']) ?></td>
                  <td class="px-4 py-3 text-gray-400"><?= $sc['day_of_week'] ?></td>
                  <td class="px-4 py-3 text-gray-400"><?= substr($sc['start_time'], 0, 5) ?></td>
                  <td class="px-4 py-3 text-gray-400 hidden md:table-cell"><?= $sc['end_time'] ? substr($sc['end_time'], 0, 5) : '–' ?></td>
                  <td class="px-4 py-3 text-gray-400 hidden md:table-cell"><?= htmlspecialchars($sc['room'] ?? '–') ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
    <!-- #endregion -->

    <!-- #region PANEL: Payments -->
    <div id="panel-payments" class="workspace-panel" style="display:none;">
      <div class="animate-item">

        <div class="flex items-center justify-between mb-5">
          <h2 class="pane-title" style="margin:0;">Payments (<?= count($payments) ?>)</h2>
          <button class="btn-enroll" onclick="openPaymentModal()">+ Record Payment</button>
        </div>

        <div style="overflow-x:auto;border-radius:16px;">
          <table class="w-full text-sm" style="background:var(--dash-card);border:1px solid var(--dash-border);border-radius:16px;border-collapse:separate;border-spacing:0;">
            <thead>
              <tr class="text-xs uppercase text-gray-400 border-b" style="border-color:var(--dash-border);">
                <th class="text-left px-4 py-3">Student</th>
                <th class="text-left px-4 py-3 hidden md:table-cell">Course</th>
                <th class="text-left px-4 py-3">Amount</th>
                <th class="text-left px-4 py-3">Date</th>
                <th class="text-left px-4 py-3 hidden md:table-cell">Notes</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($payments)): ?>
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No payment records yet.</td></tr>
              <?php else: ?>
                <?php foreach ($payments as $p): ?>
                <tr class="border-b hover:bg-white/5" style="border-color:var(--dash-border);">
                  <td class="px-4 py-3 font-semibold text-white"><?= htmlspecialchars($p['student_name']) ?></td>
                  <td class="px-4 py-3 text-gray-400 hidden md:table-cell"><?= htmlspecialchars($p['course_title']) ?></td>
                  <td class="px-4 py-3 text-green-400 font-semibold"><?= rp($p['amount']) ?></td>
                  <td class="px-4 py-3 text-gray-400"><?= $p['payment_date'] ?></td>
                  <td class="px-4 py-3 text-gray-500 hidden md:table-cell"><?= htmlspecialchars($p['notes'] ?? '–') ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
    <!-- #endregion -->

    <!-- #region PANEL: Edit Profile -->
    <div id="panel-profile" class="workspace-panel" style="display:none;">
      <div class="profile-panel animate-item">
        <div id="profileMessage" class="flash-msg"></div>

        <div class="profile-section">
          <h2 class="profile-section-title">Profile Picture</h2>
          <div class="flex items-center gap-5 flex-wrap">
            <div style="width:80px;height:80px;border-radius:50%;overflow:hidden;background:rgba(0,163,255,0.08);border:2px solid var(--dash-border);flex-shrink:0;display:flex;align-items:center;justify-content:center;">
              <?php if ($avatar_path): ?>
                <img src="<?= $avatar_path ?>" alt="Profile picture" style="width:100%;height:100%;object-fit:cover;" />
              <?php else: ?>
                <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" style="width:80px;height:80px;">
                  <defs><linearGradient id="pgGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#93C5FD"/><stop offset="100%" style="stop-color:#1A56DB"/></linearGradient></defs>
                  <circle cx="40" cy="40" r="40" fill="url(#pgGrad)"/>
                  <circle cx="40" cy="30" r="14" fill="white" opacity="0.9"/>
                  <ellipse cx="40" cy="65" rx="22" ry="17" fill="white" opacity="0.9"/>
                </svg>
              <?php endif; ?>
            </div>
            <form method="POST" action="auth/update_avatar.php" enctype="multipart/form-data" class="flex flex-col gap-2">
              <label class="form-label" style="margin:0;">Upload new photo</label>
              <div class="flex gap-3 items-center flex-wrap">
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required
                  style="font-size:13px;color:var(--text-secondary);background:rgba(255,255,255,0.05);border:1px solid var(--dash-border);border-radius:10px;padding:8px 12px;cursor:pointer;" />
                <button type="submit" class="btn-save" style="margin:0;white-space:nowrap;">Upload</button>
              </div>
              <span class="text-xs text-gray-500">JPG, PNG, GIF or WebP · max 2 MB</span>
            </form>
          </div>
        </div>

        <div class="profile-section">
          <h2 class="profile-section-title">Account Info</h2>
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-input" value="<?= $full_name ?>" disabled />
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Email</label>
            <input type="text" class="form-input" value="<?= $email ?>" disabled />
            <span class="text-xs text-gray-500 mt-1 block">Account details cannot be changed.</span>
          </div>
        </div>

        <div class="profile-section">
          <h2 class="profile-section-title">Change Password</h2>
          <form method="POST" action="auth/update_password.php" class="flex flex-col">
            <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-input" placeholder="Enter current password" required /></div>
            <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-input" placeholder="Min. 6 characters" required /></div>
            <div class="form-group" style="margin-bottom:20px;"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-input" placeholder="Re-enter new password" required /></div>
            <button type="submit" class="btn-save" style="align-self:flex-start;">Save Changes</button>
          </form>
        </div>

        <div class="danger-zone">
          <h2 class="danger-zone-title">Danger Zone</h2>
          <p class="danger-zone-desc">Permanently delete your account and all associated data. This action cannot be undone.</p>
          <button id="deleteAccountBtn" class="btn-danger">Delete Account</button>
        </div>
      </div>
    </div>
    <!-- #endregion -->

  </main>
  <!-- #endregion -->

</div>
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<!-- #region MODALS -->

<!-- #region MODAL: Student -->
<div id="modalStudent" class="modal-overlay">
  <div class="modal-backdrop modal-cancel-target"></div>
  <div class="modal-card" style="max-width:460px;">
    <h3 id="modalStudentTitle" style="font-family:'Rig Shaded',sans-serif;font-size:18px;color:var(--text-primary);margin:0 0 20px;letter-spacing:0.02em;">Add Student</h3>
    <form method="POST" id="formStudent" class="flex flex-col gap-3">
      <input type="hidden" name="id" id="fStudentId" />
      <div class="form-group" style="margin:0;"><label class="form-label">Full Name *</label><input type="text" name="full_name" id="fStudentName" class="form-input" required /></div>
      <div class="form-group" style="margin:0;"><label class="form-label">Email</label><input type="email" name="email" id="fStudentEmail" class="form-input" /></div>
      <div class="form-group" style="margin:0;"><label class="form-label">Phone</label><input type="text" name="phone" id="fStudentPhone" class="form-input" /></div>
      <div class="form-group" style="margin:0;"><label class="form-label">Address</label><input type="text" name="address" id="fStudentAddress" class="form-input" /></div>
      <div class="flex gap-3 mt-2">
        <button type="button" class="modal-cancel-btn" style="flex:1;background:rgba(255,255,255,0.06);border:1px solid var(--dash-border);border-radius:10px;padding:10px;font-size:13.5px;font-weight:600;color:var(--text-secondary);cursor:pointer;">Cancel</button>
        <button type="submit" class="btn-save" style="flex:1;margin:0;">Save</button>
      </div>
    </form>
  </div>
</div>
<!-- #endregion -->

<!-- #region MODAL: Course -->
<div id="modalCourse" class="modal-overlay">
  <div class="modal-backdrop modal-cancel-target"></div>
  <div class="modal-card" style="max-width:460px;">
    <h3 id="modalCourseTitle" style="font-family:'Rig Shaded',sans-serif;font-size:18px;color:var(--text-primary);margin:0 0 20px;letter-spacing:0.02em;">Add Course</h3>
    <form method="POST" id="formCourse" class="flex flex-col gap-3">
      <input type="hidden" name="id" id="fCourseId" />
      <div class="form-group" style="margin:0;"><label class="form-label">Title *</label><input type="text" name="title" id="fCourseTitle" class="form-input" required /></div>
      <div class="form-group" style="margin:0;"><label class="form-label">Topic</label><input type="text" name="topic" id="fCourseTopic" class="form-input" placeholder="e.g. Matematika, Bahasa Inggris" /></div>
      <div class="form-group" style="margin:0;"><label class="form-label">Price / Month (Rp) *</label><input type="number" name="price_per_month" id="fCoursePrice" class="form-input" min="0" step="1000" required /></div>
      <div class="form-group" style="margin:0;"><label class="form-label">Description</label><textarea name="description" id="fCourseDesc" class="form-input" rows="3" style="resize:vertical;"></textarea></div>
      <div class="flex gap-3 mt-2">
        <button type="button" class="modal-cancel-btn" style="flex:1;background:rgba(255,255,255,0.06);border:1px solid var(--dash-border);border-radius:10px;padding:10px;font-size:13.5px;font-weight:600;color:var(--text-secondary);cursor:pointer;">Cancel</button>
        <button type="submit" class="btn-save" style="flex:1;margin:0;">Save</button>
      </div>
    </form>
  </div>
</div>
<!-- #endregion -->

<!-- #region MODAL: Enrollment -->
<div id="modalEnrollment" class="modal-overlay">
  <div class="modal-backdrop modal-cancel-target"></div>
  <div class="modal-card" style="max-width:460px;">
    <h3 id="modalEnrollmentTitle" style="font-family:'Rig Shaded',sans-serif;font-size:18px;color:var(--text-primary);margin:0 0 20px;letter-spacing:0.02em;">Enroll Student</h3>
    <form method="POST" id="formEnrollment" class="flex flex-col gap-3">
      <input type="hidden" name="id" id="fEnrollId" />
      <div class="form-group" style="margin:0;">
        <label class="form-label">Student *</label>
        <select name="student_id" id="fEnrollStudent" class="form-input" required>
          <option value="">— Select student —</option>
          <?php foreach ($students as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Course *</label>
        <select name="course_id" id="fEnrollCourse" class="form-input" required>
          <option value="">— Select course —</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0;"><label class="form-label">Start Date *</label><input type="date" name="start_date" id="fEnrollDate" class="form-input" required /></div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Status</label>
        <select name="status" id="fEnrollStatus" class="form-input">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="completed">Completed</option>
        </select>
      </div>
      <div class="flex gap-3 mt-2">
        <button type="button" class="modal-cancel-btn" style="flex:1;background:rgba(255,255,255,0.06);border:1px solid var(--dash-border);border-radius:10px;padding:10px;font-size:13.5px;font-weight:600;color:var(--text-secondary);cursor:pointer;">Cancel</button>
        <button type="submit" class="btn-save" style="flex:1;margin:0;">Save</button>
      </div>
    </form>
  </div>
</div>
<!-- #endregion -->

<!-- #region MODAL: Payment -->
<div id="modalPayment" class="modal-overlay">
  <div class="modal-backdrop modal-cancel-target"></div>
  <div class="modal-card" style="max-width:460px;">
    <h3 style="font-family:'Rig Shaded',sans-serif;font-size:18px;color:var(--text-primary);margin:0 0 20px;letter-spacing:0.02em;">Record Payment</h3>
    <form method="POST" action="auth/payment_add.php" id="formPayment" class="flex flex-col gap-3">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Enrollment *</label>
        <select name="enrollment_id" class="form-input" required>
          <option value="">— Select student / course —</option>
          <?php foreach ($enrollments as $e): ?>
            <option value="<?= $e['id'] ?>">
              <?= htmlspecialchars($e['student_name']) ?> — <?= htmlspecialchars($e['course_title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0;"><label class="form-label">Amount (Rp) *</label><input type="number" name="amount" class="form-input" min="1" step="1000" required /></div>
      <div class="form-group" style="margin:0;"><label class="form-label">Payment Date *</label><input type="date" name="payment_date" class="form-input" required /></div>
      <div class="form-group" style="margin:0;"><label class="form-label">Notes</label><input type="text" name="notes" class="form-input" placeholder="e.g. Pembayaran bulan Juni" /></div>
      <div class="flex gap-3 mt-2">
        <button type="button" class="modal-cancel-btn" style="flex:1;background:rgba(255,255,255,0.06);border:1px solid var(--dash-border);border-radius:10px;padding:10px;font-size:13.5px;font-weight:600;color:var(--text-secondary);cursor:pointer;">Cancel</button>
        <button type="submit" class="btn-save" style="flex:1;margin:0;">Record</button>
      </div>
    </form>
  </div>
</div>
<!-- #endregion -->

<!-- #region MODAL: Delete -->
<div id="modalDelete" class="modal-overlay">
  <div class="modal-backdrop modal-cancel-target"></div>
  <div class="modal-card" style="max-width:420px;">
    <h3 style="font-family:'Rig Shaded',sans-serif;font-size:18px;color:var(--text-primary);margin:0 0 10px;">Confirm Delete</h3>
    <p id="deleteMessage" class="text-sm mb-5" style="color:var(--text-secondary);"></p>
    <form id="formDelete" method="POST" class="flex flex-col gap-3">
      <input type="hidden" name="id"         id="fDeleteId" />
      <input type="hidden" name="student_id" id="fDeleteStudentId" />
      <input type="hidden" name="course_id"  id="fDeleteCourseId" />
      <div class="flex gap-3">
        <button type="button" class="modal-cancel-btn" style="flex:1;background:rgba(255,255,255,0.06);border:1px solid var(--dash-border);border-radius:10px;padding:10px;font-size:13.5px;font-weight:600;color:var(--text-secondary);cursor:pointer;">Cancel</button>
        <button type="submit" style="flex:1;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.35);border-radius:10px;padding:10px;font-size:13.5px;font-weight:600;color:#FCA5A5;cursor:pointer;">Delete</button>
      </div>
    </form>
  </div>
</div>
<!-- #endregion -->

<!-- #region MODAL: Delete Account -->
<div id="deleteModal" class="modal-overlay">
  <div id="deleteModalBackdrop" class="modal-backdrop"></div>
  <div class="modal-card">
    <div style="width:48px;height:48px;background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.28);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:18px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="#FCA5A5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
    </div>
    <h3 style="font-family:'Rig Shaded',sans-serif;font-size:20px;color:var(--text-primary);margin:0 0 8px;letter-spacing:0.02em;">Delete Account</h3>
    <p class="text-sm mb-5" style="color:var(--text-secondary);">This is permanent and cannot be undone. Enter your password to confirm.</p>
    <form method="POST" action="auth/delete_account.php" class="flex flex-col gap-3">
      <input type="password" name="confirm_delete_password" class="form-input" placeholder="Enter your password" required />
      <div class="flex gap-3">
        <button type="button" id="deleteModalCancel" style="flex:1;background:rgba(255,255,255,0.06);border:1px solid var(--dash-border);border-radius:10px;padding:10px;font-size:13.5px;font-weight:600;color:var(--text-secondary);cursor:pointer;">Cancel</button>
        <button type="submit" style="flex:1;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.35);border-radius:10px;padding:10px;font-size:13.5px;font-weight:600;color:#FCA5A5;cursor:pointer;">Yes, Delete</button>
      </div>
    </form>
  </div>
</div>
<!-- #endregion -->
<!-- #endregion -->

<!-- #region SCRIPTS -->
<script src="script.js"></script>
<script>
//#region MODAL HELPERS
function modalOpen(id)  { document.getElementById(id).classList.add('open');    }
function modalClose(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-cancel-target').forEach(function(el) {
  el.addEventListener('click', function() {
    el.closest('.modal-overlay').classList.remove('open');
  });
});
document.querySelectorAll('.modal-cancel-btn').forEach(function(el) {
  el.addEventListener('click', function() {
    el.closest('.modal-overlay').classList.remove('open');
  });
});
//#endregion

//#region STUDENT MODAL
function openStudentModal(mode, btn) {
  var form = document.getElementById('formStudent');
  document.getElementById('modalStudentTitle').textContent = (mode === 'add') ? 'Add Student' : 'Edit Student';
  form.action = (mode === 'add') ? 'auth/student_add.php' : 'auth/student_edit.php';

  if (mode === 'add') {
    form.reset();
  } else {
    document.getElementById('fStudentId').value      = btn.dataset.id;
    document.getElementById('fStudentName').value    = btn.dataset.name;
    document.getElementById('fStudentEmail').value   = btn.dataset.email;
    document.getElementById('fStudentPhone').value   = btn.dataset.phone;
    document.getElementById('fStudentAddress').value = btn.dataset.address;
  }
  modalOpen('modalStudent');
}
//#endregion

//#region COURSE MODAL
function openCourseModal(mode, btn) {
  var form = document.getElementById('formCourse');
  document.getElementById('modalCourseTitle').textContent = (mode === 'add') ? 'Add Course' : 'Edit Course';
  form.action = (mode === 'add') ? 'auth/course_add.php' : 'auth/course_edit.php';

  if (mode === 'add') {
    form.reset();
  } else {
    document.getElementById('fCourseId').value    = btn.dataset.id;
    document.getElementById('fCourseTitle').value = btn.dataset.title;
    document.getElementById('fCourseTopic').value = btn.dataset.topic;
    document.getElementById('fCoursePrice').value = btn.dataset.price;
    document.getElementById('fCourseDesc').value  = btn.dataset.desc;
  }
  modalOpen('modalCourse');
}
//#endregion

//#region ENROLLMENT MODAL
function openEnrollmentModal(mode, btn) {
  var form = document.getElementById('formEnrollment');
  document.getElementById('modalEnrollmentTitle').textContent = (mode === 'add') ? 'Enroll Student' : 'Edit Enrollment';
  form.action = (mode === 'add') ? 'auth/enrollment_add.php' : 'auth/enrollment_edit.php';

  if (mode === 'add') {
    form.reset();
  } else {
    document.getElementById('fEnrollId').value      = btn.dataset.id;
    document.getElementById('fEnrollStudent').value = btn.dataset.studentId;
    document.getElementById('fEnrollCourse').value  = btn.dataset.courseId;
    document.getElementById('fEnrollDate').value    = btn.dataset.startDate;
    document.getElementById('fEnrollStatus').value  = btn.dataset.status;
  }
  modalOpen('modalEnrollment');
}
//#endregion

//#region PAYMENT MODAL
function openPaymentModal() {
  document.getElementById('formPayment').reset();
  var today = new Date().toISOString().split('T')[0];
  document.querySelector('#formPayment [name="payment_date"]').value = today;
  modalOpen('modalPayment');
}
//#endregion

//#region DELETE MODAL
function openDeleteModal(action, id, message, extra) {
  document.getElementById('formDelete').action         = action;
  document.getElementById('fDeleteId').value           = id;
  document.getElementById('deleteMessage').textContent = message;
  document.getElementById('fDeleteStudentId').value = extra ? extra.student_id : '';
  document.getElementById('fDeleteCourseId').value  = extra ? extra.course_id  : '';
  modalOpen('modalDelete');
}
//#endregion

//#region HEADER ADD BUTTON
document.getElementById('headerAddBtn')?.addEventListener('click', function() {
  var activeTab = document.querySelector('.nav-item.active')?.dataset.tab;
  if      (activeTab === 'students')    openStudentModal('add');
  else if (activeTab === 'courses')     openCourseModal('add');
  else if (activeTab === 'enrollments') openEnrollmentModal('add');
  else if (activeTab === 'payments')    openPaymentModal();
});
//#endregion
</script>
<!-- #endregion -->
<!-- #endregion -->
</body>
</html>
