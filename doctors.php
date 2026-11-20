<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'connection.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM doctors WHERE id = ?")->execute([$id]);
    header("Location: doctors.php");
}

// Handle Add
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_doctor'])) {
    $name = $_POST['name'];
    $specialty = $_POST['specialty'];
    $phone = $_POST['phone'];
    $pdo->prepare("INSERT INTO doctors (name, specialty, phone) VALUES (?, ?, ?)")->execute([$name, $specialty, $phone]);
    header("Location: doctors.php");
    exit();
}

$doctors = $pdo->query("SELECT * FROM doctors")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors - HMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .sidebar { position: fixed; left: 0; top: 0; width: 200px; height: 100vh; background: #2196F3; color: white; padding: 20px; }
        .sidebar h3 { margin-bottom: 30px; }
        .sidebar a { display: block; color: white; text-decoration: none; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .sidebar a:hover { background: rgba(255,255,255,0.2); }
        .content { margin-left: 220px; padding: 30px; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #2196F3; margin-bottom: 20px; }
        .add-form { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .form-row { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end; margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-add { background: #4CAF50; color: white; }
        .btn-edit { background: #2196F3; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; margin-right: 5px; }
        .btn-delete { background: #f44336; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2196F3; color: white; }
        tr:hover { background: #f5f5f5; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="content">
        <div class="container">
            <h2>Doctors Management</h2>
            
            <!-- Add Doctor Form -->
            <div class="add-form">
                <h3 style="margin-bottom: 15px;">Add New Doctor</h3>
                <form method="POST" action="">
                    <input type="hidden" name="add_doctor" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" required placeholder="Dr. Name">
                        </div>
                        <div class="form-group">
                            <label>Specialty</label>
                            <select name="specialty" required>
                                <option value="General">General</option>
                                <option value="Maternity">Maternity</option>
                                <option value="Pediatrics">Pediatrics</option>
                                <option value="Surgery">Surgery</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" required placeholder="0770111222">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-add">Add Doctor</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Doctors Table -->
            <h3>Doctors List</h3>
            <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Specialty</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($doctors) > 0): ?>
                        <?php foreach ($doctors as $doc): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($doc['name']); ?></td>
                            <td><?php echo htmlspecialchars($doc['specialty']); ?></td>
                            <td><?php echo htmlspecialchars($doc['phone']); ?></td>
                            <td>
                                <a href="#" class="btn-edit">Edit</a>
                                <a href="?delete=<?php echo $doc['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this doctor?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;">No doctors found. Add one above.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
                    </div>
        </div>
    </div>
</body>
</html>