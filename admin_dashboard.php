<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if (isset($_POST['add_course'])) {
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];
    $description = $_POST['description'];
    
    $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name, description) VALUES (?, ?, ?)");
    $stmt->execute([$course_code, $course_name, $description]);
    $success = "Course added successfully!";
}

$courses = $pdo->query("SELECT * FROM courses ORDER BY course_name")->fetchAll();
$attendance_by_course = $pdo->query("
    SELECT c.course_name, s.year_level, 
           COUNT(a.id) as total_attendance,
           SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
           SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
           SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
    FROM courses c
    LEFT JOIN students s ON c.id = s.course_id
    LEFT JOIN attendance a ON s.id = a.student_id
    GROUP BY c.id, s.year_level
    ORDER BY c.course_name, s.year_level
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .success { color: green; margin-bottom: 15px; }
        .logout { float: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Admin Dashboard</h1>
        <a href="logout.php" class="logout" style="color: white;">Logout</a>
    </div>

    <div style="margin-bottom: 20px;">
        <a href="student_management.php" style="color: #007bff; text-decoration: none; margin-right: 15px;">Manage Students</a>
        <a href="attendance_management.php" style="color: #007bff; text-decoration: none; margin-right: 15px;">Manage Attendance</a>
        <a href="admin_excuses.php" style="color: #007bff; text-decoration: none;">Review Excuse Letters</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="section">
        <h2>Add New Course</h2>
        <form method="POST">
            <div class="form-group">
                <label>Course Code:</label>
                <input type="text" name="course_code" required>
            </div>
            <div class="form-group">
                <label>Course Name:</label>
                <input type="text" name="course_name" required>
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <button type="submit" name="add_course">Add Course</button>
        </form>
    </div>

    <div class="section">
        <h2>All Courses</h2>
        <table>
            <tr>
                <th>Course Code</th>
                <th>Course Name</th>
                <th>Description</th>
            </tr>
            <?php foreach ($courses as $course): ?>
            <tr>
                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                <td><?php echo htmlspecialchars($course['description']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section">
        <h2>Attendance Summary by Course and Year Level</h2>
        <table>
            <tr>
                <th>Course</th>
                <th>Year Level</th>
                <th>Total Records</th>
                <th>Present</th>
                <th>Late</th>
                <th>Absent</th>
            </tr>
            <?php foreach ($attendance_by_course as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                <td><?php echo $row['year_level']; ?></td>
                <td><?php echo $row['total_attendance']; ?></td>
                <td><?php echo $row['present_count']; ?></td>
                <td><?php echo $row['late_count']; ?></td>
                <td><?php echo $row['absent_count']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
