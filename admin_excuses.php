<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';


if (isset($_POST['action']) && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $admin_comment = trim($_POST['admin_comment'] ?? '');
    if (in_array($action, ['approve', 'reject'])) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $up = $pdo->prepare("UPDATE excuse_letters SET status = ?, admin_comment = ?, updated_at = NOW() WHERE id = ?");
        if ($up->execute([$newStatus, $admin_comment, $id])) {
            $success = "Excuse letter has been $newStatus.";
        } else {
            $error = 'Failed to update excuse letter.';
        }
    }
}


$selected_course = isset($_GET['course_id']) && $_GET['course_id'] !== '' ? (int)$_GET['course_id'] : null;
$selected_status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : '';

$courses = $pdo->query("SELECT id, course_name FROM courses ORDER BY course_name")->fetchAll();

$where = [];
$params = [];
if ($selected_course) { $where[] = 'el.course_id = ?'; $params[] = $selected_course; }
if ($selected_status) { $where[] = 'el.status = ?'; $params[] = $selected_status; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
    SELECT el.*, s.first_name, s.last_name, s.student_id AS sid_code, c.course_name
    FROM excuse_letters el
    LEFT JOIN students s ON el.student_id = s.id
    LEFT JOIN courses c ON el.course_id = c.id
    $whereSql
    ORDER BY el.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Excuse Letters - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .logout { float: right; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        select, input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top; }
        th { background: #f8f9fa; }
        .status-pill { padding: 4px 8px; border-radius: 12px; font-weight: bold; font-size: 12px; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .nav a { margin-right: 15px; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admin - Excuse Letters</h1>
        <a href="admin_dashboard.php" class="logout" style="color: white;">Back to Dashboard</a>
    </div>

    <?php if ($success): ?><div style="color: green; margin-bottom: 10px;"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div style="color: red; margin-bottom: 10px;"><?php echo $error; ?></div><?php endif; ?>

    <div class="section">
        <h2>Filters</h2>
        <form method="GET" class="form-row" style="display:flex; gap: 15px;">
            <div style="flex:1;" class="form-group">
                <label>Program</label>
                <select name="course_id">
                    <option value="">All Programs</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($selected_course == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['course_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1;" class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    <option value="pending" <?php echo $selected_status==='pending'?'selected':''; ?>>Pending</option>
                    <option value="approved" <?php echo $selected_status==='approved'?'selected':''; ?>>Approved</option>
                    <option value="rejected" <?php echo $selected_status==='rejected'?'selected':''; ?>>Rejected</option>
                </select>
            </div>
            <div style="align-self:flex-end;">
                <button type="submit">Apply</button>
            </div>
        </form>
    </div>

    <div class="section">
        <h2>Excuse Submissions</h2>
        <table>
            <tr>
                <th>Student</th>
                <th>Program</th>
                <th>Attendance Date</th>
                <th>Reason</th>
                <th>Attachment</th>
                <th>Status</th>
                <th>Admin Comment</th>
                <th>Action</th>
            </tr>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td>
                    <div><strong><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></strong></div>
                    <div style="color:#666;">ID: <?php echo htmlspecialchars($r['sid_code']); ?></div>
                </td>
                <td><?php echo htmlspecialchars($r['course_name']); ?></td>
                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($r['attendance_date']))); ?></td>
                <td><?php echo nl2br(htmlspecialchars($r['reason'])); ?></td>
                <td>
                    <?php if ($r['attachment_path']): ?>
                        <a href="<?php echo htmlspecialchars($r['attachment_path']); ?>" target="_blank">View</a>
                    <?php else: ?>-
                    <?php endif; ?>
                </td>
                <td>
                    <?php $s=$r['status']; ?>
                    <span class="status-pill status-<?php echo $s; ?>"><?php echo ucfirst($s); ?></span>
                </td>
                <td><?php echo nl2br(htmlspecialchars($r['admin_comment'])); ?></td>
                <td>
                    <?php if ($r['status'] === 'pending'): ?>
                        <form method="POST" style="display:flex; flex-direction:column; gap:6px;">
                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                            <textarea name="admin_comment" rows="2" placeholder="Optional comment..."></textarea>
                            <div style="display:flex; gap:8px;">
                                <button type="submit" name="action" value="approve" style="background:#28a745;">Approve</button>
                                <button type="submit" name="action" value="reject" style="background:#dc3545;">Reject</button>
                            </div>
                        </form>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>


