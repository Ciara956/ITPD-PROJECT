<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'connection.php';

$patient_id = $_GET['patient_id'] ?? null;
$message = "";
$message_type = "";

if (!$patient_id) {
    header("Location: records.php");
    exit();
}

// Fetch Patient Name
$stmt = $pdo->prepare("SELECT name FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient not found.");
}

// Fetch Doctors
$doctors = $pdo->query("SELECT id, name FROM doctors")->fetchAll(PDO::FETCH_ASSOC);

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $visit_date = $_POST['visit_date'];
    $department = $_POST['department'];
    $doctor_id = $_POST['doctor_id'];
    $complaint = $_POST['complaint'];
    $temperature = $_POST['temperature'] ?: null;
    $systolic_bp = $_POST['systolic_bp'] ?: null;
    $diastolic_bp = $_POST['diastolic_bp'] ?: null;
    $weight_kg = $_POST['weight_kg'] ?: null;
    $heart_rate = $_POST['heart_rate'] ?: null;
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $next_appointment = $_POST['next_appointment'] ?: null;

    try {
        $stmt = $pdo->prepare("INSERT INTO clinical_visits 
            (patient_id, doctor_id, visit_date, department, complaint, temperature, systolic_bp, diastolic_bp, weight_kg, heart_rate, diagnosis, treatment_plan, next_appointment) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $patient_id, $doctor_id, $visit_date, $department, $complaint, 
            $temperature, $systolic_bp, $diastolic_bp, $weight_kg, $heart_rate, 
            $diagnosis, $treatment, $next_appointment
        ]);

        $risk_alerts = [];
        if ($systolic_bp > 140 || $diastolic_bp > 90) {
            $risk_alerts[] = "High Blood Pressure ({$systolic_bp}/{$diastolic_bp})";
        }
        if ($heart_rate > 100) {
            $risk_alerts[] = "High Heart Rate ({$heart_rate} bpm)";
        }

        if (!empty($risk_alerts)) {
            $message = "Visit saved! ⚠️ RISK ALERT: " . implode(", ", $risk_alerts);
            $message_type = "warning";
        } else {
            $message = "Visit saved successfully.";
            $message_type = "success";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Visit - <?php echo htmlspecialchars($patient['name']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .sidebar { position: fixed; left: 0; top: 0; width: 200px; height: 100vh; background: #2196F3; color: white; padding: 20px; }
        .sidebar h3 { margin-bottom: 30px; }
        .sidebar a { display: block; color: white; text-decoration: none; padding: 10px; margin: 5px 0; border-radius: 4px; }
        .sidebar a:hover { background: rgba(255,255,255,0.2); }
        .content { margin-left: 220px; padding: 30px; }
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-container h2 { color: #2196F3; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;
        }
        .form-group textarea { resize: vertical; }
        .btn { padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
        .btn-save { background: #4CAF50; color: white; }
        .btn-save:hover { background: #45a049; }
        .btn-cancel { background: #666; color: white; margin-left: 10px; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="content">
        <div class="form-container">
            <h2>Record Visit: <?php echo htmlspecialchars($patient['name']); ?></h2>
            
            <?php if($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Visit Date *</label>
                        <input type="date" name="visit_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Department *</label>
                        <select name="department" required>
                            <option value="Maternity">Maternity</option>
                            <option value="General">General</option>
                            <option value="Pediatrics">Pediatrics</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Attending Doctor *</label>
                    <select name="doctor_id" required>
                        <option value="">Select Doctor...</option>
                        <?php foreach ($doctors as $doc): ?>
                            <option value="<?php echo $doc['id']; ?>"><?php echo htmlspecialchars($doc['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Complaint / Reason for Visit *</label>
                    <textarea name="complaint" rows="3" required placeholder="e.g., Headache, swelling..."></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Temperature (°C)</label>
                        <input type="number" step="0.1" name="temperature" placeholder="36.5">
                    </div>
                    <div class="form-group">
                        <label>Systolic BP (mmHg)</label>
                        <input type="number" name="systolic_bp" placeholder="120">
                    </div>
                    <div class="form-group">
                        <label>Diastolic BP (mmHg)</label>
                        <input type="number" name="diastolic_bp" placeholder="80">
                    </div>
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="number" step="0.1" name="weight_kg" placeholder="65.0">
                    </div>
                    <div class="form-group">
                        <label>Heart Rate (bpm)</label>
                        <input type="number" name="heart_rate" placeholder="72">
                    </div>
                </div>

                <div class="form-group">
                    <label>Diagnosis *</label>
                    <input type="text" name="diagnosis" required placeholder="e.g., Mild Hypertension">
                </div>

                <div class="form-group">
                    <label>Treatment / Notes</label>
                    <textarea name="treatment" rows="3" placeholder="Medication prescribed, advice..."></textarea>
                </div>

                <div class="form-group">
                    <label>Next Appointment Date</label>
                    <input type="date" name="next_appointment">
                </div>

                <button type="submit" class="btn btn-save">Save Visit</button>
                <a href="profile.php?id=<?php echo $patient_id; ?>" class="btn btn-cancel">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>