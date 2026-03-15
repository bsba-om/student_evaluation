<div class="page-header">
    <div>
        <h1 class="page-title">Manage Instructors</h1>
        <p class="page-subtitle">View and manage all instructor accounts</p>
    </div>
</div>

<?php
require_once '../../data/config.php';

$instructors = [];
$error_message = '';
$promoted_instructor_id = null;

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM instructors ORDER BY id DESC");
        $instructors = $stmt->fetchAll();
        
        // Check if there's already a promoted program head
        $stmt = $pdo->query("SELECT instructor_id FROM admin_promotions WHERE promoted_to = 'program_head' AND status = 'active' LIMIT 1");
        $promotion = $stmt->fetch();
        if ($promotion) {
            $promoted_instructor_id = $promotion['instructor_id'];
        }
    } catch (PDOException $e) {
        $error_message = "Database connection failed. Please set up the database using data.sql";
    }
} else {
    $error_message = "Database connection failed. Please set up the database using data.sql";
}
?>

<!-- Search and Filter -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 16px 24px;">
        <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 250px;">
                <input type="text" class="form-input" id="searchInput" placeholder="Search instructors..." style="width: 100%;" onkeyup="filterInstructors()">
            </div>
            <select class="form-select" style="width: auto; min-width: 180px;" id="deptFilter" onchange="filterInstructors()">
                <option value="">All Departments</option>
                <option value="Operational Management">Operational Management (OM)</option>
                <option value="Financial Management">Financial Management (FM)</option>
                <option value="Marketing Management">Marketing Management (MM)</option>
            </select>
            <select class="form-select" style="width: auto; min-width: 140px;">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>
</div>

<?php if ($error_message): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <span><?php echo htmlspecialchars($error_message); ?></span>
</div>
<?php endif; ?>

<!-- Instructors Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-users"></i>
            All Instructors (<?php echo count($instructors); ?>)
        </h3>
    </div>
    <div class="card-body">
        <?php if (empty($instructors)): ?>
        <div class="empty-state">
            <i class="fas fa-user-plus"></i>
            <h3>No Instructors Found</h3>
            <p>No instructors found in the system.</p>
        </div>
        <?php else: ?>
        <table class="data-table" id="instructorsTable">
            <thead>
                <tr>
                    <th>Instructor</th>
                    <th>Email</th>
                    <th>Employee ID</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="instructorsTableBody">
                <?php foreach ($instructors as $instructor): ?>
                <?php 
                    $initials = strtoupper(substr($instructor['first_name'], 0, 1) . (isset($instructor['middle_name']) && $instructor['middle_name'] ? substr($instructor['middle_name'], 0, 1) : '') . substr($instructor['last_name'], 0, 1));
                    $full_name = $instructor['first_name'] . ' ' . ($instructor['middle_name'] ?? '') . ' ' . $instructor['last_name'] . ($instructor['suffix'] ? ', ' . $instructor['suffix'] : '');
                    $full_name = preg_replace('/\s+/', ' ', trim($full_name));
                    $created_date = isset($instructor['created_at']) ? date('M j, Y', strtotime($instructor['created_at'])) : date('M j, Y');
                ?>
                <tr data-id="<?php echo $instructor['id']; ?>" 
                    data-first-name="<?php echo htmlspecialchars($instructor['first_name']); ?>"
                    data-middle-name="<?php echo htmlspecialchars($instructor['middle_name'] ?? ''); ?>"
                    data-last-name="<?php echo htmlspecialchars($instructor['last_name']); ?>"
                    data-suffix="<?php echo htmlspecialchars($instructor['suffix'] ?? ''); ?>"
                    data-email="<?php echo htmlspecialchars($instructor['email']); ?>"
                    data-employee-id="<?php echo htmlspecialchars($instructor['employee_id'] ?? ''); ?>"
                    data-department="<?php echo htmlspecialchars($instructor['department']); ?>"
                    data-position="<?php echo htmlspecialchars($instructor['position'] ?? 'Instructor'); ?>">
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #d4a843, #e8c768); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;"><?php echo $initials; ?></div>
                            <div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($full_name); ?></div>
                                <div style="font-size: 12px; color: #6b7280;"><?php echo htmlspecialchars($instructor['position'] ?? 'Instructor'); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                    <td><?php echo htmlspecialchars($instructor['employee_id'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($instructor['department']); ?></td>
                    <td><span class="status-badge active">Active</span></td>
                    <td><?php echo $created_date; ?></td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <button class="btn btn-sm" style="background: none; border: none; color: var(--gold); cursor: pointer;" onclick="editInstructor(<?php echo $instructor['id']; ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($promoted_instructor_id === null): ?>
                                <button class="btn btn-sm" style="background: none; border: none; color: #10b981; cursor: pointer;" onclick="promoteInstructor(<?php echo $instructor['id']; ?>, '<?php echo htmlspecialchars($full_name); ?>')" title="Promote to Program Head">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                            <?php elseif ($promoted_instructor_id == $instructor['id']): ?>
                                <button class="btn btn-sm" style="background: none; border: none; color: #dc2626; cursor: pointer;" onclick="removePromotion(<?php echo $instructor['id']; ?>, '<?php echo htmlspecialchars($full_name); ?>')" title="Remove Promotion">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            <?php endif; ?>
                            <a href="../../data/admin_process.php?action=remove_instructor&id=<?php echo $instructor['id']; ?>" class="btn btn-sm btn-danger" title="Remove" onclick="return confirm('Are you sure you want to remove this instructor?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 20px; font-weight: 700; color: var(--dark-text);">Edit Instructor</h3>
            <button onclick="closeEditModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--light-text);">&times;</button>
        </div>
        <form method="POST" action="../../data/admin_process.php?action=edit_instructor">
            <input type="hidden" name="id" id="editId">
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">First Name</label>
                <input type="text" class="form-input" name="first_name" id="editFirstName" required>
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Middle Name</label>
                <input type="text" class="form-input" name="middle_name" id="editMiddleName">
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-input" name="last_name" id="editLastName" required>
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Suffix</label>
                <select class="form-select" name="suffix" id="editSuffix">
                    <option value="">None</option>
                    <option value="Jr.">Jr.</option>
                    <option value="Sr.">Sr.</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                    <option value="V">V</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Email</label>
                <input type="email" class="form-input" name="email" id="editEmail" required>
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Department</label>
                <select class="form-select" name="department" id="editDepartment" required>
                    <option value="">Select Department</option>
                    <option value="Operational Management">Operational Management (OM)</option>
                    <option value="Financial Management">Financial Management (FM)</option>
                    <option value="Marketing Management">Marketing Management (MM)</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Position</label>
                <input type="text" class="form-input" name="position" id="editPosition" value="Instructor" required>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn" style="background: var(--cream); color: var(--dark-text);" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Promote Modal -->
<div class="modal-overlay" id="promoteModal">
    <div class="modal" style="max-width: 450px;">
        <div class="modal-header" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 20px; font-weight: 700; color: var(--dark-text);">Promote to Program Head</h3>
            <button onclick="closePromoteModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--light-text);">&times;</button>
        </div>
        <p style="margin-bottom: 20px; color: var(--light-text);">
            You are about to promote <strong id="promoteInstructorName"></strong> to Program Head. 
            They will be able to login with the credentials below.
        </p>
        <form method="POST" action="../../data/admin_process.php?action=promote_instructor" id="promoteForm">
            <input type="hidden" name="instructor_id" id="promoteInstructorId">
            <input type="hidden" name="promote_to" value="program_head">
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Set Password for Program Head Login</label>
                <input type="password" class="form-input" name="password" id="promotePassword" placeholder="Enter password" required minlength="6">
                <small style="color: var(--light-text); font-size: 12px;">This password will be used to login as Program Head</small>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Confirm Password</label>
                <input type="password" class="form-input" name="confirm_password" id="promoteConfirmPassword" placeholder="Confirm password" required>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn" style="background: var(--cream); color: var(--dark-text);" onclick="closePromoteModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-user-plus"></i>
                    Promote to Program Head
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.modal-overlay.show {
    opacity: 1;
    visibility: visible;
}
</style>

<script>
function filterInstructors() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const deptFilter = document.getElementById('deptFilter').value;
    const table = document.getElementById('instructorsTable');
    if (!table) return;
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        const tdName = tr[i].getElementsByTagName('td')[0];
        const tdDept = tr[i].getElementsByTagName('td')[3];
        
        if (tdName && tdDept) {
            const nameText = tdName.textContent || tdName.innerText;
            const deptText = tdDept.textContent || tdDept.innerText;
            
            const matchesSearch = nameText.toLowerCase().indexOf(searchInput) > -1;
            const matchesDept = deptFilter === '' || deptText.indexOf(deptFilter) > -1;
            
            tr[i].style.display = matchesSearch && matchesDept ? '' : 'none';
        }
    }
}

function editInstructor(id) {
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (row) {
        document.getElementById('editId').value = row.dataset.id;
        document.getElementById('editFirstName').value = row.dataset.firstName;
        document.getElementById('editMiddleName').value = row.dataset.middleName || '';
        document.getElementById('editLastName').value = row.dataset.lastName;
        document.getElementById('editSuffix').value = row.dataset.suffix || '';
        document.getElementById('editEmail').value = row.dataset.email;
        document.getElementById('editDepartment').value = row.dataset.department;
        document.getElementById('editPosition').value = row.dataset.position;
        
        document.getElementById('editModal').classList.add('show');
    }
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

function promoteInstructor(id, name) {
    document.getElementById('promoteInstructorId').value = id;
    document.getElementById('promoteInstructorName').textContent = name;
    document.getElementById('promotePassword').value = '';
    document.getElementById('promoteConfirmPassword').value = '';
    document.getElementById('promoteModal').classList.add('show');
}

function closePromoteModal() {
    document.getElementById('promoteModal').classList.remove('show');
}

// Handle promote form submission
document.getElementById('promoteForm').addEventListener('submit', function(e) {
    const password = document.getElementById('promotePassword').value;
    const confirmPassword = document.getElementById('promoteConfirmPassword').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters!');
        return false;
    }
});

function removePromotion(id, name) {
    if (confirm('Are you sure you want to remove the Program Head promotion from ' + name + '?')) {
        window.location.href = '../../data/admin_process.php?action=remove_promotion&id=' + id;
    }
}

// Close modal when clicking outside
document.getElementById('promoteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePromoteModal();
    }
});

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>
