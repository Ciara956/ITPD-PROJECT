<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'connection.php';

// Handle Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT p.id, p.name, p.gender, p.phone, p.blood_group, d.name as doctor_name 
        FROM patients p 
        LEFT JOIN doctors d ON p.assigned_doctor_id = d.id";

if ($search !== '') {
    $sql .= " WHERE p.name LIKE ? OR p.phone LIKE ?";
}
$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
if ($search !== '') {
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt->execute();
}
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Records</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .search-bar { margin-bottom: 20px; display: flex; gap: 10px; }
        .search-bar input { padding: 10px; flex: 1; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 14px; color: white; }
        .btn-view { background: #007bff; }
        .btn-record { background: #28a745; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <main class="content">
        <h2>Patient Records</h2>
        
        <!-- Search Bar -->
        <form method="GET" class="search-bar">
            <input type="text" name="search" placeholder="Search by Name or Phone Number..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-view">Search</button>
            <a href="records.php" class="btn" style="background:#6c757d;">Clear</a>
        </form>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Blood Group</th>
                    <th>Assigned Doctor</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($patients) > 0): ?>
                    <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td>PT-<?php echo $patient['id']; ?></td>
                        <td><?php echo htmlspecialchars($patient['name']); ?></td>
                        <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                        <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                        <td><?php echo htmlspecialchars($patient['blood_group'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($patient['doctor_name'] ?? 'Unassigned'); ?></td>
                        <td>
                            <a href="profile.php?id=<?php echo $patient['id']; ?>" class="btn btn-view">View</a>
                            <a href="record_visit.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-record">Visit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No patients found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
                </div>
        <br>
        <a href="add-patient.php" class="btn btn-record" style="padding: 12px 20px; font-size: 16px;">+ Add New Patient</a>
    </main>
</body>
</html>