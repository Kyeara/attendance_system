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

if (isset($_POST['submit_attendance'])) {
    $date = $_POST['date'];
    $time_in = $_POST['time_in'];
    $time_out = $_POST['time_out'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    

    $check_stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND date = ?");
    $check_stmt->execute([$student['id'], $date]);
    
    if ($check_stmt->fetch()) {
        $error = "Attendance already recorded for this date. Please contact administrator to modify.";
    } else {
        
        $is_late = 0;
        if ($status == 'present' && $time_in) {
            $standard_time = strtotime('08:00:00');
            $checkin_time = strtotime($time_in);
            if ($checkin_time > $standard_time) {
                $is_late = 1;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, time_in, time_out, status, is_late, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$student['id'], $date, $time_in, $time_out, $status, $is_late, $notes])) {
            $success = "Attendance submitted successfully!";
        } else {
            $error = "Failed to submit attendance. Please try again.";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT * FROM attendance 
    WHERE student_id = ? 
    ORDER BY date DESC
");
$stmt->execute([$student['id']]);
$attendance_history = $stmt->fetchAll();
$total_days = count($attendance_history);
$present_days = count(array_filter($attendance_history, function($a) { return $a['status'] == 'present'; }));
$late_days = count(array_filter($attendance_history, function($a) { return $a['status'] == 'late'; }));
$absent_days = count(array_filter($attendance_history, function($a) { return $a['status'] == 'absent'; }));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
        .header { background: #28a745; color: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-box { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; flex: 1; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007bff; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .status-present { color: green; font-weight: bold; }
        .status-late { color: orange; font-weight: bold; }
        .status-absent { color: red; font-weight: bold; }
        .logout { float: right; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #218838; }
        .success { color: green; margin-bottom: 15px; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; }
        .error { color: red; margin-bottom: 15px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Dashboard</h1>
        <a href="logout.php" class="logout" style="color: white;">Logout</a>
    </div>

    <div style="margin-bottom: 20px;">
        <a href="student_excuse.php" style="color: #007bff; text-decoration: none;">Submit/View Excuse Letters</a>
    </div>

    <div class="section">
        <h2>Student Information</h2>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
        <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course_name'] . ' (' . $student['course_code'] . ')'); ?></p>
        <p><strong>Year Level:</strong> <?php echo $student['year_level']; ?></p>
    </div>

    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="section">
        <h2>Submit Attendance</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Date:</label>
                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" required>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Time In:</label>
                    <input type="time" name="time_in" value="<?php echo date('H:i'); ?>">
                </div>
                <div class="form-group">
                    <label>Time Out:</label>
                    <input type="time" name="time_out">
                </div>
            </div>
            
            <div class="form-group">
                <label>Notes (Optional):</label>
                <textarea name="notes" rows="3" placeholder="Any additional notes about your attendance..."></textarea>
            </div>
            
            <button type="submit" name="submit_attendance">Submit Attendance</button>
        </form>
    </div>

    <div class="section">
        <h2>Attendance Statistics</h2>
        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo $total_days; ?></div>
                <div>Total Days</div>
            </div>
            <div class="stat-box">
                <div class="stat-number" style="color: green;"><?php echo $present_days; ?></div>
                <div>Present</div>
            </div>
            <div class="stat-box">
                <div class="stat-number" style="color: orange;"><?php echo $late_days; ?></div>
                <div>Late</div>
            </div>
            <div class="stat-box">
                <div class="stat-number" style="color: red;"><?php echo $absent_days; ?></div>
                <div>Absent</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Attendance History</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
                <th>Late</th>
                <th>Notes</th>
            </tr>
            <?php foreach ($attendance_history as $record): ?>
            <tr>
                <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?></td>
                <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?></td>
                <td class="status-<?php echo $record['status']; ?>">
                    <?php echo ucfirst($record['status']); ?>
                </td>
                <td><?php echo $record['is_late'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo htmlspecialchars($record['notes']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
