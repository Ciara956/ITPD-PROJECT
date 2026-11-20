<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'connection.php';

// Function to send SMS via Africa's Talking
function sendSMSAlert($phoneNumber, $message) {
    $api_url = "https://api.africastalking.com/version1/messaging";
    $api_key = "atsk_37da93a8c87a56ab16143603d2b93e42a4b19a48bb29d5b7912bad11d72471f66c6c55ce";
    // In production, use cURL to actually send the SMS
    // For now, we'll log it to the notification_logs table
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notification_logs (recipient_phone, message_content, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$phoneNumber, $message]);
    return true;
}

// Handle: Check for missed appointments
if (isset($_POST['check_missed'])) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT a.id, p.name, p.phone, a.appointment_date
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.id
                           WHERE a.appointment_date < ? AND a.status = 'scheduled'");
    $stmt->execute([$today]);
    $missed = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $alertCount = 0;
    foreach ($missed as $appointment) {
        $doctorMessage = "ALERT: Patient {$appointment['name']} missed her antenatal visit on {$appointment['appointment_date']}.";
        sendSMSAlert("0770111222", $doctorMessage); // Doctor's phone
        
        $patientMessage = "Dear {$appointment['name']}, we missed you at your antenatal visit. Please contact us to reschedule.";
        sendSMSAlert($appointment['phone'], $patientMessage);
        
        $updateStmt = $pdo->prepare("UPDATE appointments SET status = 'missed' WHERE id = ?");
        $updateStmt->execute([$appointment['id']]);
        $alertCount++;
    }
    $message = "Checked! {$alertCount} missed appointment(s) detected and alerts sent.";
    $message_type = "warning";
}

// Handle: Reschedule appointment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reschedule'])) {
    $apt_id = $_POST['apt_id'];
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];
    
    $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, status = 'rescheduled' WHERE id = ?")
        ->execute([$new_date, $new_time, $apt_id]);
    
    // Send confirmation SMS
    $stmt = $pdo->prepare("SELECT p.name, p.phone FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.id = ?");
    $stmt->execute([$apt_id]);
    $apt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($apt) {
        sendSMSAlert($apt['phone'], "Dear {$apt['name']}, your appointment has been rescheduled to {$new_date} at {$new_time}.");
    }
    
    $message = "Appointment rescheduled successfully!";
    $message_type = "success";
}

// Fetch all appointments
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.reason_for_visit,
               p.name as patient_name, p.phone, d.name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        LEFT JOIN doctors d ON a.doctor_id = d.id";

if ($filter !== 'all') {
    $sql .= " WHERE a.status = ?";
    $stmt = $pdo->prepare($sql . " ORDER BY a.appointment_date DESC");
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query($sql . " ORDER BY a.appointment_date DESC");
}
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count by status
$totalScheduled = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'scheduled'")->fetchColumn();
$totalMissed = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'missed'")->fetchColumn();
$totalCompleted = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - HMS</title>
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
        .stat-scheduled { background: #2196F3; }
        .stat-missed { background: #f44336; }
        .stat-completed { background: #4CAF50; }
        .stat-card h3 { font-size: 28px; margin-bottom: 5px; }
        .stat-card p { font-size: 14px; opacity: 0.9; }
        .actions-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .filter-links a { padding: 8px 15px; background: #e0e0e0; color: #333; text-decoration: none; border-radius: 4px; margin-right: 5px; font-size: 14px; }
        .filter-links a.active { background: #2196F3; color: white; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-check { background: #ff9800; color: white; }
        .btn-reschedule { background: #2196F3; color: white; padding: 5px 10px; font-size: 12px; }
        .btn-complete { background: #4CAF50; color: white; padding: 5px 10px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2196F3; color: white; }
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .status-scheduled { background: #e3f2fd; color: #1976D2; }
        .status-missed { background: #ffebee; color: #c62828; }
        .status-completed { background: #e8f5e9; color: #2e7d32; }
        .status-rescheduled { background: #fff3e0; color: #e65100; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 8px; max-width: 400px; margin: 100px auto; }
        .modal-content h3 { margin-bottom: 15px; color: #2196F3; }
        .modal-content label { display: block; margin: 10px 0 5px; font-weight: bold; }
        .modal-content input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .modal-actions { margin-top: 20px; text-align: right; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="content">
        <div class="container">
            <h2>Appointments Management</h2>
            
            <?php if(isset($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-row">
                <div class="stat-card stat-scheduled">
                    <h3><?php echo $totalScheduled; ?></h3>
                    <p>Scheduled</p>
                </div>
                <div class="stat-card stat-missed">
                    <h3><?php echo $totalMissed; ?></h3>
                    <p>Missed</p>
                </div>
                <div class="stat-card stat-completed">
                    <h3><?php echo $totalCompleted; ?></h3>
                    <p>Completed</p>
                </div>
            </div>

            <!-- Actions Bar -->
            <div class="actions-bar">
                <div class="filter-links">
                    <a href="?filter=all" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?filter=scheduled" class="<?php echo $filter === 'scheduled' ? 'active' : ''; ?>">Scheduled</a>
                    <a href="?filter=missed" class="<?php echo $filter === 'missed' ? 'active' : ''; ?>">Missed</a>
                    <a href="?filter=completed" class="<?php echo $filter === 'completed' ? 'active' : ''; ?>">Completed</a>
                </div>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="check_missed" class="btn btn-check"> Check Missed Appointments</button>
                </form>
            </div>

            <!-- Appointments Table -->
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Phone</th>
                        <th>Doctor</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($appointments) > 0): ?>
                        <?php foreach ($appointments as $apt): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($apt['appointment_date']); ?></td>
                            <td><?php echo htmlspecialchars($apt['appointment_time'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($apt['phone']); ?></td>
                            <td><?php echo htmlspecialchars($apt['doctor_name'] ?? 'Unassigned'); ?></td>
                            <td><?php echo htmlspecialchars($apt['reason_for_visit'] ?? 'Routine'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $apt['status']; ?>">
                                    <?php echo ucfirst($apt['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($apt['status'] === 'missed' || $apt['status'] === 'scheduled'): ?>
                                    <button class="btn btn-reschedule" onclick="openReschedule(<?php echo $apt['id']; ?>)">Reschedule</button>
                                <?php endif; ?>
                                <?php if ($apt['status'] === 'scheduled'): ?>
                                    <a href="?complete=<?php echo $apt['id']; ?>" class="btn btn-complete">Complete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center;">No appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="modal">
        <div class="modal-content">
            <h3>Reschedule Appointment</h3>
            <form method="POST" action="">
                <input type="hidden" name="apt_id" id="apt_id">
                <label>New Date</label>
                <input type="date" name="new_date" required>
                <label>New Time</label>
                <input type="time" name="new_time" required>
                <div class="modal-actions">
                    <button type="button" class="btn" style="background:#666; color:white;" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="reschedule" class="btn btn-reschedule">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReschedule(id) {
            document.getElementById('apt_id').value = id;
            document.getElementById('rescheduleModal').style.display = 'block';
        }
        function closeModal() {
            document.getElementById('rescheduleModal').style.display = 'none';
        }
    </script>
</body>
</html>