<?php
require_once '../../data/config.php';

$instructor_count = 0;
$department_count = 3;
$recent_instructors = [];
$error_message = '';
$promoted_ids = [];

if ($pdo) {
    try {
        // Get instructor count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM instructors");
        $result = $stmt->fetch();
        $instructor_count = $result['count'] ?? 0;
        
        // Get all instructors for Instructor List table
        $stmt = $pdo->query("SELECT * FROM instructors ORDER BY first_name ASC");
        $recent_instructors = $stmt->fetchAll();
        
        // Get promoted instructor IDs (Role = Program Head for these)
        try {
            $stmt = $pdo->query("SELECT instructor_id FROM admin_promotions WHERE promoted_to = 'program_head' AND status = 'active'");
            $promotions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $promoted_ids = array_map('intval', is_array($promotions) ? $promotions : []);
        } catch (PDOException $e) {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS admin_promotions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    instructor_id INT NOT NULL,
                    promoted_to VARCHAR(50) NOT NULL,
                    promoted_by INT NOT NULL,
                    promotion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('active', 'revoked') DEFAULT 'active'
                )");
                $stmt = $pdo->query("SELECT instructor_id FROM admin_promotions WHERE promoted_to = 'program_head' AND status = 'active'");
                $promotions = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $promoted_ids = array_map('intval', is_array($promotions) ? $promotions : []);
            } catch (PDOException $e2) {
                $promoted_ids = [];
            }
        }
        
    } catch (PDOException $e) {
        $error_message = "Database connection failed. Please set up the database using data.sql";
    }
} else {
    $error_message = "Database connection failed. Please set up the database using data.sql";
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard Overview</h1>
        <p class="page-subtitle">Welcome back, Admin!</p>
    </div>
</div>

<?php if ($error_message): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <span><?php echo htmlspecialchars($error_message); ?></span>
</div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon gold">
            <i class="fas fa-user-tie"></i>
        </div>
        <div class="stat-value"><?php echo $instructor_count; ?></div>
        <div class="stat-label">Total Instructors</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-value"><?php echo $instructor_count * 5; ?></div>
        <div class="stat-label">Evaluations Completed</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-book"></i>
        </div>
        <div class="stat-value"><?php echo $department_count; ?></div>
        <div class="stat-label">Courses</div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-users"></i>
            Instructor List (<?php echo $instructor_count; ?>)
        </h3>
    </div>
    <div class="card-body">
        <?php if (empty($recent_instructors)): ?>
        <div class="empty-state">
            <i class="fas fa-user-plus"></i>
            <h3>No Instructors Yet</h3>
            <p>Click "Add Instructor" to get started.</p>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_instructors as $instructor): ?>
                <?php 
                    $initials = strtoupper(substr($instructor['first_name'], 0, 1) . substr($instructor['last_name'], 0, 1));
                    $joined_date = isset($instructor['created_at']) ? date('M j, Y', strtotime($instructor['created_at'])) : 'N/A';
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 36px; height: 36px; border-radius: 8px; background: linear-gradient(135deg, #d4a843, #e8c768); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 12px;"><?php echo $initials; ?></div>
                            <div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?></div>
                                <div style="font-size: 12px; color: #6b7280;"><?php echo htmlspecialchars($instructor['email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($instructor['department']); ?></td>
                    <td><?php echo in_array((int)$instructor['id'], $promoted_ids, true) ? '<span class="status-badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">Program Head</span>' : '<span class="status-badge" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">Instructor</span>'; ?></td>
                    <td><?php echo $joined_date; ?></td>
                    <td>
                        <a href="dashboard.php?page=manage_program_heads" class="btn btn-sm" style="background: none; border: none; color: var(--gold); cursor: pointer;">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
