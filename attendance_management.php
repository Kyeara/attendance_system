<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if (isset($_POST['add_attendance'])) {
    $student_id = $_POST['student_id'];
    $date = $_POST['date'];
    $time_in = $_POST['time_in'];
    $time_out = $_POST['time_out'];
    $status = $_POST['status'];
    $is_late = isset($_POST['is_late']) ? 1 : 0;
    $notes = $_POST['notes'];
    
    $stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, time_in, time_out, status, is_late, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$student_id, $date, $time_in, $time_out, $status, $is_late, $notes]);
    $success = "Attendance record added successfully!";
}

$students = $pdo->query("
    SELECT s.*, c.course_name 
    FROM students s 
    LEFT JOIN courses c ON s.course_id = c.id 
    ORDER BY s.last_name
")->fetchAll();

$attendance = $pdo->query("
    SELECT a.*, s.student_id, s.first_name, s.last_name, c.course_name 
    FROM attendance a 
    LEFT JOIN students s ON a.student_id = s.id 
    LEFT JOIN courses c ON s.course_id = c.id 
    ORDER BY a.date DESC, s.last_name
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Management</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .success { color: green; margin-bottom: 15px; }
        .logout { float: right; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 15px; color: #007bff; text-decoration: none; }
        .status-present { color: green; font-weight: bold; }
        .status-late { color: orange; font-weight: bold; }
        .status-absent { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attendance Management</h1>
        <a href="logout.php" class="logout" style="color: white;">Logout</a>
    </div>

    <div class="nav">
        <a href="admin_dashboard.php">← Back to Admin Dashboard</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="section">
        <h2>Add Attendance Record</h2>
        <form method="POST">
            <div class="form-group">
                <label>Student:</label>
                <select name="student_id" required>
                    <option value="">Select Student</option>
                    <?php foreach ($students as $student): ?>
                    <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['course_name'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Date:</label>
                <input type="date" name="date" required>
            </div>
            <div class="form-group">
                <label>Time In:</label>
                <input type="time" name="time_in">
            </div>
            <div class="form-group">
                <label>Time Out:</label>
                <input type="time" name="time_out">
            </div>
            <div class="form-group">
                <label>Status:</label>
                <select name="status" required>
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                </select>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_late" value="1"> Mark as Late
                </label>
            </div>
            <div class="form-group">
                <label>Notes:</label>
                <textarea name="notes" rows="3"></textarea>
            </div>
            <button type="submit" name="add_attendance">Add Attendance</button>
        </form>
    </div>

    <div class="section">
        <h2>All Attendance Records</h2>
        <table>
            <tr>
                <th>Student</th>
                <th>Course</th>
                <th>Date</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
                <th>Late</th>
                <th>Notes</th>
            </tr>
            <?php foreach ($attendance as $record): ?>
            <tr>
                <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name'] . ' (' . $record['student_id'] . ')'); ?></td>
                <td><?php echo htmlspecialchars($record['course_name']); ?></td>
                <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?></td>
                <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?></td>
                <td class="status-<?php echo $record['status']; ?>"><?php echo ucfirst($record['status']); ?></td>
                <td><?php echo $record['is_late'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo htmlspecialchars($record['notes']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
