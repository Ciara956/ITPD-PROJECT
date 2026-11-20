<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'connection.php';

$patient_id = $_GET['id'] ?? null;
if (!$patient_id) {
    die("No patient selected.");
}

// Fetch Patient Details
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient not found.");
}

// Fetch Visit History
$stmtVisits = $pdo->prepare("
    SELECT cv.visit_date, cv.department, cv.complaint, cv.diagnosis, cv.treatment_plan, 
           cv.systolic_bp, cv.diastolic_bp, cv.weight_kg, cv.heart_rate,
           d.name as doctor_name
    FROM clinical_visits cv
    JOIN doctors d ON cv.doctor_id = d.id
    WHERE cv.patient_id = ?
    ORDER BY cv.visit_date DESC
");
$stmtVisits->execute([$patient_id]);
$visits = $stmtVisits->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile - <?php echo htmlspecialchars($patient['name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .sidebar { position: fixed; left: 0; top: 0; width: 200px; height: 100vh; background: #2196F3; color: white; padding: 20px; }
        .sidebar h3 { margin-bottom: 30px; }
        .sidebar a { display: block; color: white; text-decoration: none; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .sidebar a:hover { background: rgba(255,255,255,0.2); }
        .content { margin-left: 220px; padding: 30px; }
        .patient-info { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .patient-info h2 { color: #2196F3; margin-bottom: 15px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .info-item { padding: 10px; background: #f9f9f9; border-radius: 4px; }
        .info-item strong { color: #666; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2196F3; color: white; }
        .btn { display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
        .btn:hover { background: #1976D2; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="content">
        <div class="patient-info">
            <h2>Patient Profile: <?php echo htmlspecialchars($patient['name']); ?></h2>
            <div class="info-grid">
                <div class="info-item"><strong>ID:</strong> PT-<?php echo $patient['id']; ?></div>
                <div class="info-item"><strong>Gender:</strong> <?php echo htmlspecialchars($patient['gender']); ?></div>
                <div class="info-item"><strong>Age:</strong> <?php echo htmlspecialchars($patient['age'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Phone:</strong> <?php echo htmlspecialchars($patient['phone']); ?></div>
                <div class="info-item"><strong>Blood Group:</strong> <?php echo htmlspecialchars($patient['blood_group'] ?? 'N/A'); ?></div>
                <div class="info-item"><strong>Address:</strong> <?php echo htmlspecialchars($patient['address'] ?? 'N/A'); ?></div>
            </div>
        </div>

        <h3 style="margin: 20px 0;">Visit History</h3>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Doctor</th>
                    <th>Department</th>
                    <th>BP (Sys/Dia)</th>
                    <th>Weight</th>
                    <th>Diagnosis</th>
                    <th>Treatment</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($visits) > 0): ?>
                    <?php foreach ($visits as $visit): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($visit['visit_date']); ?></td>
                        <td><?php echo htmlspecialchars($visit['doctor_name']); ?></td>
                        <td><?php echo htmlspecialchars($visit['department'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($visit['systolic_bp'] . '/' . $visit['diastolic_bp']); ?></td>
                        <td><?php echo htmlspecialchars($visit['weight_kg']); ?> kg</td>
                        <td><?php echo htmlspecialchars($visit['diagnosis']); ?></td>
                        <td><?php echo htmlspecialchars($visit['treatment_plan']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No visit history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
                </div>
        <a href="record_visit.php?patient_id=<?php echo $patient_id; ?>" class="btn">Record New Visit</a>
        <a href="records.php" class="btn" style="background:#666; margin-left: 10px;">Back to Records</a>
    </div>
</body>
</html>