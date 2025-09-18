<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if (isset($_POST['add_student'])) {
    $user_id = $_POST['user_id'];
    $student_id = $_POST['student_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $course_id = $_POST['course_id'];
    $year_level = $_POST['year_level'];
    
    $stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, first_name, last_name, course_id, year_level) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $student_id, $first_name, $last_name, $course_id, $year_level]);
    $success = "Student added successfully!";
}

$users = $pdo->query("SELECT * FROM users WHERE role = 'student' AND id NOT IN (SELECT user_id FROM students WHERE user_id IS NOT NULL)")->fetchAll();
$courses = $pdo->query("SELECT * FROM courses ORDER BY course_name")->fetchAll();
$students = $pdo->query("
    SELECT s.*, u.username, c.course_name 
    FROM students s 
    LEFT JOIN users u ON s.user_id = u.id 
    LEFT JOIN courses c ON s.course_id = c.id 
    ORDER BY s.last_name
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Management</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .success { color: green; margin-bottom: 15px; }
        .logout { float: right; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 15px; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Management</h1>
        <a href="logout.php" class="logout" style="color: white;">Logout</a>
    </div>

    <div class="nav">
        <a href="admin_dashboard.php">← Back to Admin Dashboard</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="section">
        <h2>Add Student Record</h2>
        <form method="POST">
            <div class="form-group">
                <label>Select User:</label>
                <select name="user_id" required>
                    <option value="">Select a registered student user</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username'] . ' (' . $user['email'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Student ID:</label>
                <input type="text" name="student_id" required>
            </div>
            <div class="form-group">
                <label>First Name:</label>
                <input type="text" name="first_name" required>
            </div>
            <div class="form-group">
                <label>Last Name:</label>
                <input type="text" name="last_name" required>
            </div>
            <div class="form-group">
                <label>Course:</label>
                <select name="course_id" required>
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Year Level:</label>
                <input type="number" name="year_level" min="1" max="5" required>
            </div>
            <button type="submit" name="add_student">Add Student</button>
        </form>
    </div>

    <div class="section">
        <h2>All Students</h2>
        <table>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Username</th>
                <th>Course</th>
                <th>Year Level</th>
                <th>Status</th>
            </tr>
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                <td><?php echo htmlspecialchars($student['username']); ?></td>
                <td><?php echo htmlspecialchars($student['course_name']); ?></td>
                <td><?php echo $student['year_level']; ?></td>
                <td><?php echo ucfirst($student['status']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
