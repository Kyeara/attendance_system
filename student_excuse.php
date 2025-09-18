<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: login.php');
    exit();
}

$stmt = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code 
    FROM students s 
    LEFT JOIN courses c ON s.course_id = c.id 
    WHERE s.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    echo "<div style='text-align: center; padding: 50px;'>";
    echo "<h2>Student Record Not Found</h2>";
    echo "<p>Your student profile has not been created yet. Please contact an administrator.</p>";
    echo "<a href='logout.php'>Logout</a>";
    echo "</div>";
    exit();
}

$success = '';
$error = '';


if (isset($_POST['submit_excuse'])) {
    $attendance_date = $_POST['attendance_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $attachment_path = null;

    if (!$attendance_date || !$reason) {
        $error = 'Date and reason are required.';
    } else {
  
        if (!empty($_FILES['attachment']['name'])) {
            $uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'excuses';
            if (!is_dir($uploadsDir)) {
                @mkdir($uploadsDir, 0777, true);
            }
            $originalName = basename($_FILES['attachment']['name']);
            $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $originalName);
            $targetPath = $uploadsDir . DIRECTORY_SEPARATOR . $safeName;
            if (is_uploaded_file($_FILES['attachment']['tmp_name'])) {
                if (@move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                    $attachment_path = 'uploads/excuses/' . $safeName;
                } else {
                    $error = 'Failed to upload attachment.';
                }
            }
        }

        if (!$error) {
            $insert = $pdo->prepare("INSERT INTO excuse_letters (student_id, course_id, attendance_date, reason, attachment_path) VALUES (?, ?, ?, ?, ?)");
            if ($insert->execute([$student['id'], $student['course_id'], $attendance_date, $reason, $attachment_path])) {
                $success = 'Excuse letter submitted successfully.';
            } else {
                $error = 'Failed to submit excuse letter. Please try again.';
            }
        }
    }
}


$lettersStmt = $pdo->prepare("
    SELECT el.* 
    FROM excuse_letters el 
    WHERE el.student_id = ? 
    ORDER BY el.created_at DESC
");
$lettersStmt->execute([$student['id']]);
$letters = $lettersStmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Excuse Letter</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
        .header { background: #28a745; color: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .logout { float: right; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #218838; }
        .success { color: green; margin-bottom: 15px; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; }
        .error { color: red; margin-bottom: 15px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
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
        <h1>Excuse Letters</h1>
        <a href="student_dashboard.php" class="logout" style="color: white;">Back to Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="section">
        <h2>Submit New Excuse</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Attendance Date:</label>
                <input type="date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Reason:</label>
                <textarea name="reason" rows="4" placeholder="Provide a clear reason for your absence/late..." required></textarea>
            </div>
            <div class="form-group">
                <label>Attachment (optional, PDF/JPG/PNG):</label>
                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
            </div>
            <button type="submit" name="submit_excuse">Submit Excuse</button>
        </form>
    </div>

    <div class="section">
        <h2>Your Submitted Excuses</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Admin Comment</th>
                <th>Attachment</th>
                <th>Submitted</th>
            </tr>
            <?php foreach ($letters as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['attendance_date']))); ?></td>
                <td><?php echo nl2br(htmlspecialchars($row['reason'])); ?></td>
                <td>
                    <?php $s = $row['status']; ?>
                    <span class="status-pill status-<?php echo $s; ?>"><?php echo ucfirst($s); ?></span>
                </td>
                <td><?php echo htmlspecialchars($row['admin_comment']); ?></td>
                <td>
                    <?php if ($row['attachment_path']): ?>
                        <a href="<?php echo htmlspecialchars($row['attachment_path']); ?>" target="_blank">View</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['created_at']))); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>


