

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'connection.php';

// Get patient ID from URL (e.g., record_visit.php?patient_id=1)
$patient_id = $_GET['patient_id'] ?? null;
$message = "";
$message_type = ""; // 'success' or 'error'
if (!$patient_id) {
    header("Location:records.php?error=No patient selected. Please go back to records.");
    exit();
}

// Fetch Patient Name for the header
$stmt = $pdo->prepare("SELECT name FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient_id) {
    die("Error: No patient selected. Please go back to records.");
}

// Fetch Doctors for the dropdown
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
        // 1. Save the Clinical Visit
        $stmt = $pdo->prepare("INSERT INTO clinical_visits
            (patient_id, doctor_id, visit_date, department, complaint, temperature, systolic_bp, diastolic_bp, weight_kg, heart_rate, diagnosis, treatment, next_appointment)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
       
        $stmt->execute([
            $patient_id, $doctor_id, $visit_date, $department, $complaint,
            $temperature, $systolic_bp, $diastolic_bp, $weight_kg, $heart_rate,
            $diagnosis, $treatment, $next_appointment
        ]);

        // 2. Trigger Risk Assessment (Gloria's Module)
        // We will create a simple inline check here for now
        $risk_alerts = [];
        if ($systolic_bp > 140 || $diastolic_bp > 90) {
            $risk_alerts[] = "High Blood Pressure Detected ({$systolic_bp}/{$diastolic_bp})";
        }
        if ($heart_rate > 100) {
            $risk_alerts[] = "Tachycardia Detected (HR: {$heart_rate})";
        }

        if (!empty($risk_alerts)) {
            $message = "Visit saved! RISK ALERT: " . implode(", ", $risk_alerts);
            $message_type = "warning";
           
            // TODO: Hellen - Add SMS trigger here later
        } else {
            $message = "Visit saved successfully. No immediate risks detected.";
            $message_type = "success";
        }

    } catch (PDOException $e) {
        $message = "Error saving visit: " . $e->getMessage();
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
    <link rel="stylesheet" href="style.css">
    <style>
        /* Inline styles for quick layout fix */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-save { background: #28a745; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-cancel { background: #6c757d; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; display: inline-block; margin-left: 10px; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <main class="content">
        <h2>Record Visit: <?php echo htmlspecialchars($patient['name']); ?></h2>
       
        <?php if($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>Visit Date</label>
                    <input type="date" name="visit_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department">
                        <option value="Maternity">Maternity</option>
                        <option value="General">General</option>
                        <option value="Pediatrics">Pediatrics</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Attending Doctor</label>
                    <select name="doctor_id" required>
                        <?php foreach ($doctors as $doc): ?>
                            <option value="<?php echo $doc['id']; ?>"><?php echo htmlspecialchars($doc['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label>Complaint / Reason for Visit</label>
                <textarea name="complaint" rows="3" required placeholder="e.g. Headache, swelling in legs"></textarea>
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

            <div class="form-group" style="margin-bottom: 15px;">
                <label>Diagnosis</label>
                <input type="text" name="diagnosis" required placeholder="e.g., Mild Hypertension">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label>Treatment / Notes</label>
                <textarea name="treatment" rows="3" placeholder="Medication prescribed, advice given..."></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label>Next Appointment Date</label>
                <input type="date" name="next_appointment">
            </div>

            <button type="submit" class="btn-save">Save Visit & Assess Risk</button>
            <a href="profile.php?id=<?php echo $patient_id; ?>" class="btn-cancel">Cancel</a>
        </form>
    </main>
</body>
</html>