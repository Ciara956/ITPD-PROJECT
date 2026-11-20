<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'connection.php';

$message = "";
$message_type = "";

// Fetch doctors for the dropdown menu
$doctors = $pdo->query("SELECT id, name FROM doctors")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $age = $_POST['age'] ?: null;
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $doctor_id = $_POST['doctor_id'] ?: null;

    try {
        $stmt = $pdo->prepare("INSERT INTO patients (name, phone, age, gender, blood_group, address, assigned_doctor_id) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $age, $gender, $blood_group, $address, $doctor_id]);
        
        $message = "Patient added successfully!";
        $message_type = "success";
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
    <title>Add Patient</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-save { background: #28a745; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <main class="content">
        <h2>Add New Patient</h2>
        
        <?php if($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="text" name="phone" required>
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Gender *</label>
                    <select name="gender" required>
                        <option value="Female">Female</option>
                        <option value="Male">Male</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Blood Group</label>
                    <select name="blood_group">
                        <option value="">Select...</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address">
            </div>

            <div class="form-group">
                <label>Assigned Doctor</label>
                <select name="doctor_id">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?php echo $doc['id']; ?>"><?php echo htmlspecialchars($doc['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
              <div class="form-group">
      <label style="display:flex; align-items:start; gap:10px; font-weight:normal; font-size:14px;">
          <input type="checkbox" name="consent" required style="width:auto; margin-top:4px;">
          <span>I confirm that the patient (or next of kin) has given informed consent for their health data to be stored digitally and to receive SMS appointment reminders.</span>
      </label>
  </div>

            <button type="submit" class="btn-save">Save Patient</button>
            <a href="records.php" style="margin-left: 15px; text-decoration: none; color: #6c757d;">Cancel</a>
        </form>
    </main>
</body>
</html>