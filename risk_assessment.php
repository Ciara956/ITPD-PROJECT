<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'connection.php';

// Risk Assessment Function (Gloria's Module)
function assessMaternalRisk($patient_id, $pdo) {
    $stmt = $pdo->prepare("SELECT systolic_bp, diastolic_bp, weight_kg, heart_rate
                           FROM clinical_visits
                           WHERE patient_id = ?
                           ORDER BY visit_date DESC LIMIT 3");
    $stmt->execute([$patient_id]);
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $risk_score = 0;
    $alerts = [];

    foreach ($visits as $visit) {
        if (!empty($visit['systolic_bp']) && $visit['systolic_bp'] > 140) {
            $risk_score += 3;
            $alerts[] = "High Blood Pressure detected.";
        }
        if (!empty($visit['heart_rate']) && $visit['heart_rate'] > 100) {
            $risk_score += 2;
            $alerts[] = "Elevated Heart Rate detected.";
        }
    }

    // Check for upward trend in BP across visits
    if (count($visits) >= 2) {
        if (!empty($visits[0]['systolic_bp']) && !empty($visits[1]['systolic_bp'])) {
            if ($visits[0]['systolic_bp'] > $visits[1]['systolic_bp'] && $visits[0]['systolic_bp'] > 130) {
                $risk_score += 2;
                $alerts[] = "Rising Blood Pressure trend detected!";
            }
        }
    }

    return ['score' => $risk_score, 'alerts' => $alerts];
}

// Fetch all patients
$patients = $pdo->query("SELECT id, name, phone, age FROM patients ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Calculate risk for each patient
$patientRisks = [];
foreach ($patients as $patient) {
    $riskData = assessMaternalRisk($patient['id'], $pdo);
    $patient['risk_score'] = $riskData['score'];
    $patient['alerts'] = $riskData['alerts'];
    $patientRisks[] = $patient;
}

// Sort by highest risk score
usort($patientRisks, function($a, $b) {
    return $b['risk_score'] <=> $a['risk_score'];
});

// Count stats
$highRiskCount = count(array_filter($patientRisks, fn($p) => $p['risk_score'] >= 5));
$mediumRiskCount = count(array_filter($patientRisks, fn($p) => $p['risk_score'] >= 2 && $p['risk_score'] < 5));
$lowRiskCount = count(array_filter($patientRisks, fn($p) => $p['risk_score'] < 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risk Assessment Dashboard - HMS</title>
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
        
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px; }
        .stat-card { padding: 20px; border-radius: 8px; text-align: center; color: white; }
        .stat-high { background: #f44336; }
        .stat-medium { background: #ff9800; }
        .stat-low { background: #4CAF50; }
        .stat-card h3 { font-size: 28px; margin-bottom: 5px; }
        .stat-card p { font-size: 14px; opacity: 0.9; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2196F3; color: white; }
        
        .risk-badge { padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; }
        .risk-high { background: #f44336; }
        .risk-medium { background: #ff9800; }
        .risk-low { background: #4CAF50; }
        
        .alert-list { margin: 0; padding-left: 20px; font-size: 13px; color: #d32f2f; }
        .btn-view { padding: 5px 10px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="content">
        <div class="container">
            <h2>Trend-Based Risk Assessment Dashboard</h2>
            
            <!-- Stats Cards -->
            <div class="stats-row">
                <div class="stat-card stat-high">
                    <h3><?php echo $highRiskCount; ?></h3>
                    <p>High Risk Patients</p>
                </div>
                <div class="stat-card stat-medium">
                    <h3><?php echo $mediumRiskCount; ?></h3>
                    <p>Medium Risk Patients</p>
                </div>
                <div class="stat-card stat-low">
                    <h3><?php echo $lowRiskCount; ?></h3>
                    <p>Low Risk Patients</p>
                </div>
            </div>

            <!-- Risk Table -->
            <h3>Patient Risk Analysis</h3>
            <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Phone</th>
                        <th>Age</th>
                        <th>Risk Score</th>
                        <th>Risk Level</th>
                        <th>Detected Alerts</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($patientRisks) > 0): ?>
                        <?php foreach ($patientRisks as $p): 
                            $level = 'Low';
                            $class = 'risk-low';
                            if ($p['risk_score'] >= 5) { $level = 'High'; $class = 'risk-high'; }
                            elseif ($p['risk_score'] >= 2) { $level = 'Medium'; $class = 'risk-medium'; }
                        ?>
                        <tr style="<?php echo $p['risk_score'] >= 5 ? 'background:#fff5f5;' : ''; ?>">
                            <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['phone']); ?></td>
                            <td><?php echo htmlspecialchars($p['age'] ?? 'N/A'); ?></td>
                            <td><strong><?php echo $p['risk_score']; ?></strong></td>
                            <td><span class="risk-badge <?php echo $class; ?>"><?php echo $level; ?></span></td>
                            <td>
                                <?php if (!empty($p['alerts'])): ?>
                                    <ul class="alert-list">
                                        <?php foreach ($p['alerts'] as $alert): ?>
                                            <li><?php echo htmlspecialchars($alert); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span style="color:#4CAF50;">No alerts</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="profile.php?id=<?php echo $p['id']; ?>" class="btn-view">View Profile</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;">No patients found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
                    </div>
        </div>
    </div>
</body>
</html>