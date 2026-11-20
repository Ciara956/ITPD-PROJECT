<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header" style="margin-bottom: 30px; text-align: center;">
        <h3>HMS</h3>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active-link' : ''; ?>" style="color: white; text-decoration: none; display: block; padding: 10px; margin: 5px 0; border-radius: 4px;"> Dashboard</a>
        <a href="add-patient.php" class="<?php echo ($current_page == 'add-patient.php') ? 'active-link' : ''; ?>" style="color: white; text-decoration: none; display: block; padding: 10px; margin: 5px 0; border-radius: 4px;">Add Patient</a>
        <a href="records.php" class="<?php echo ($current_page == 'records.php') ? 'active-link' : ''; ?>" style="color: white; text-decoration: none; display: block; padding: 10px; margin: 5px 0; border-radius: 4px;"> Patient Records</a>
        <a href="record_visit.php" class="<?php echo ($current_page == 'record_visit.php') ? 'active-link' : ''; ?>" style="color: white; text-decoration: none; display: block; padding: 10px; margin: 5px 0; border-radius: 4px;"> Record Visit</a>
        <a href="doctors.php" class="<?php echo ($current_page == 'doctors.php') ? 'active-link' : ''; ?>" style="color: white; text-decoration: none; display: block; padding: 10px; margin: 5px 0; border-radius: 4px;"> Doctors</a>
        <a href="appointments.php" class="<?php echo ($current_page == 'appointments.php') ? 'active-link' : ''; ?>" style="color: white; text-decoration: none; display: block; padding: 10px; margin: 5px 0; border-radius: 4px;"> Appointments</a>
        <a href="risk_assessment.php" class="<?php echo ($current_page == 'risk_assessment.php') ? 'active-link' : ''; ?>" style="color: white; text-decoration: none; display: block; padding: 10px; margin: 5px 0; border-radius: 4px;">Risk Analysis</a>
        
        <div class="sidebar-footer" style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 10px;">
            <a href="logout.php" class="logout-btn" style="color: white; text-decoration: none; display: block; padding: 10px;"> Logout</a>
        </div>
    </nav>
</div>

<!-- JavaScript to handle the sliding effect -->
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Toggle the 'active' class on both the sidebar and the overlay
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // Prevent body scroll when sidebar is open
    if (sidebar.classList.contains('active')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = 'auto';
    }
}

// Close sidebar when a navigation link is clicked (mobile only)
document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
    const sidebar = document.getElementById('mainSidebar');
    
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Only close on mobile (when sidebar is visible)
            if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                toggleSidebar();
            }
        });
    });
});
</script>