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
$promoted_ids = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM instructors ORDER BY id DESC");
        $instructors = $stmt->fetchAll();
        
        // Get all promoted instructor IDs - try admin_promotions table
        try {
            $stmt = $pdo->query("SELECT instructor_id FROM admin_promotions WHERE promoted_to = 'program_head' AND status = 'active'");
            $promotions = $stmt->fetchAll();
            $promoted_ids = array_column($promotions, 'instructor_id');
        } catch (PDOException $e) {
            // Table might not exist, try to create it
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS admin_promotions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    instructor_id INT NOT NULL,
                    promoted_to VARCHAR(50) NOT NULL,
                    promoted_by INT NOT NULL,
                    promotion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('active', 'revoked') DEFAULT 'active'
                )");
                // Retry query
                $stmt = $pdo->query("SELECT instructor_id FROM admin_promotions WHERE promoted_to = 'program_head' AND status = 'active'");
                $promotions = $stmt->fetchAll();
                $promoted_ids = array_column($promotions, 'instructor_id');
            } catch (PDOException $e2) {
                // Still failed, leave as empty array
                $promoted_ids = [];
            }
        }
        
        // Check if there's already a promoted program head
        if (!empty($promoted_ids)) {
            $promoted_instructor_id = $promoted_ids[0];
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
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
                <option value="program_head">Program Head</option>
                <option value="instructor">Instructor</option>
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
                    <th>Role</th>
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
                                <div style="font-size: 12px; color: #6b7280;"><?php echo in_array($instructor['id'], $promoted_ids) ? 'Program Head' : htmlspecialchars($instructor['position'] ?? 'Instructor'); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                    <td><?php echo htmlspecialchars($instructor['employee_id'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($instructor['department']); ?></td>
                    <td><?php echo in_array($instructor['id'], $promoted_ids) ? '<span class="status-badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">Program Head</span>' : '<span class="status-badge" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">Instructor</span>'; ?></td>
                    <td><?php echo $created_date; ?></td>
                    <td>
                        <button class="actions-btn" onclick="openActionsModal(<?php echo $instructor['id']; ?>, '<?php echo htmlspecialchars($full_name); ?>', <?php echo in_array($instructor['id'], $promoted_ids) ? 'true' : 'false'; ?>, <?php echo $promoted_instructor_id === null ? 'true' : ($promoted_instructor_id == $instructor['id'] ? 'true' : 'false'); ?>)" title="Actions">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
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
            Set a new password for <strong id="promoteInstructorName"></strong> to login as Program Head. They will use the same email but this new password.
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
.actions-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    border: none;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    font-size: 16px;
}

.actions-btn:hover {
    background: linear-gradient(135deg, #d4a843, #e8c768);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(212, 168, 67, 0.4);
}

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

<!-- Actions Modal -->
<div class="modal-overlay" id="actionsModal">
    <div class="modal" style="max-width: 380px; border-radius: 20px; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #d4a843, #e8c768); padding: 24px; text-align: center;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                <i class="fas fa-user-cog" style="font-size: 24px; color: #d4a843;"></i>
            </div>
            <h3 style="font-size: 18px; font-weight: 700; color: white; margin: 0;" id="actionsModalTitle">Actions</h3>
        </div>
        <div style="padding: 20px; display: flex; flex-direction: column; gap: 12px;">
            <button type="button" class="action-btn action-btn-edit" onclick="editInstructorFromModal()">
                <div class="action-btn-icon"><i class="fas fa-edit"></i></div>
                <div class="action-btn-text">
                    <span class="action-btn-title">Edit Instructor</span>
                    <span class="action-btn-desc">Modify instructor details</span>
                </div>
                <i class="fas fa-chevron-right" style="color: #9ca3af;"></i>
            </button>
            <button type="button" class="action-btn" id="promoteBtn">
                <div class="action-btn-icon" id="promoteIcon"><i class="fas fa-user-plus"></i></div>
                <div class="action-btn-text">
                    <span class="action-btn-title" id="promoteBtnText">Promote to Program Head</span>
                    <span class="action-btn-desc" id="promoteBtnDesc">Grant program head access</span>
                </div>
                <i class="fas fa-chevron-right" id="promoteChevron" style="color: #9ca3af;"></i>
            </button>
            <a href="#" id="deleteLink" class="action-btn action-btn-delete" onclick="return confirm('Are you sure you want to remove this instructor?')">
                <div class="action-btn-icon"><i class="fas fa-trash-alt"></i></div>
                <div class="action-btn-text">
                    <span class="action-btn-title">Remove Instructor</span>
                    <span class="action-btn-desc">Delete instructor account</span>
                </div>
                <i class="fas fa-chevron-right" style="color: #9ca3af;"></i>
            </a>
        </div>
        <div style="padding: 0 20px 20px;">
            <button onclick="closeActionsModal()" class="cancel-btn">Cancel</button>
        </div>
    </div>
</div>

<style>
.action-btn {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: none;
    border-radius: 12px;
    background: #f9fafb;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    color: inherit;
}

.action-btn:hover {
    background: #f3f4f6;
    transform: translateX(4px);
}

.action-btn-edit .action-btn-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, #d4a843, #e8c768);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.action-btn .action-btn-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, #6366f1, #818cf8);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.action-btn-delete .action-btn-icon {
    background: linear-gradient(135deg, #ef4444, #f87171) !important;
}

.action-btn-remove .action-btn-icon {
    background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
}

.action-btn-disabled {
    opacity: 0.7;
    cursor: not-allowed !important;
    pointer-events: none;
}

.action-btn-text {
    flex: 1;
    text-align: left;
}

.action-btn-title {
    display: block;
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
}

.action-btn-desc {
    display: block;
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

.cancel-btn {
    width: 100%;
    padding: 14px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: white;
    color: #6b7280;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cancel-btn:hover {
    background: #f9fafb;
    color: #1f2937;
}
</style>

<script>
let currentInstructorId = null;
let currentInstructorName = '';
let isCurrentlyPromoted = false;
let canPromote = true;

function openActionsModal(id, name, isPromoted, canPromoteStatus) {
    currentInstructorId = id;
    currentInstructorName = name;
    isCurrentlyPromoted = isPromoted;
    canPromote = canPromoteStatus;
    
    document.getElementById('actionsModalTitle').textContent = name;
    
    // Update promote button based on status
    const promoteBtn = document.getElementById('promoteBtn');
    const promoteBtnText = document.getElementById('promoteBtnText');
    const promoteBtnDesc = document.getElementById('promoteBtnDesc');
    const promoteIcon = document.getElementById('promoteIcon');
    
    // Reset button state
    promoteBtn.disabled = false;
    promoteBtn.classList.remove('action-btn-remove', 'action-btn-disabled');
    promoteBtn.style.background = '';
    document.getElementById('promoteChevron').style.visibility = 'visible';
    
    if (isCurrentlyPromoted) {
        // This instructor IS the current Program Head - show remove option (red)
        promoteBtn.classList.add('action-btn-remove');
        promoteBtnText.textContent = 'Remove as Program Head';
        promoteBtnDesc.textContent = 'Remove Program Head access to promote another';
        promoteBtn.style.background = 'rgba(220, 38, 38, 0.1)';
        promoteIcon.style.background = 'linear-gradient(135deg, #dc2626, #b91c1c)';
        promoteIcon.innerHTML = '<i class="fas fa-user-minus"></i>';
        promoteBtn.onclick = function() { closeActionsModal(); removePromotion(currentInstructorId, currentInstructorName); };
    } else if (canPromote) {
        // No Program Head exists - can promote
        promoteBtnText.textContent = 'Promote to Program Head';
        promoteBtnDesc.textContent = 'Set new password for Program Head login';
        promoteBtn.style.background = '#f9fafb';
        promoteIcon.style.background = 'linear-gradient(135deg, #6366f1, #818cf8)';
        promoteIcon.innerHTML = '<i class="fas fa-user-plus"></i>';
        promoteBtn.onclick = function() { closeActionsModal(); showPromoteModal(); };
    } else {
        // Another instructor is already Program Head - cannot promote (disabled, lock icon)
        promoteBtn.classList.add('action-btn-disabled');
        promoteBtnText.textContent = 'Cannot Promote';
        promoteBtnDesc.textContent = 'Remove current Program Head first';
        promoteBtn.style.background = '#f3f4f6';
        promoteIcon.style.background = '#9ca3af';
        promoteIcon.innerHTML = '<i class="fas fa-lock"></i>';
        promoteBtn.disabled = true;
        promoteBtn.onclick = null;
        document.getElementById('promoteChevron').style.visibility = 'hidden';
    }
    
    // Update edit and delete links
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (row) {
        // Edit is handled by function
        // Delete link
        document.getElementById('deleteLink').href = '../../data/admin_process.php?action=remove_instructor&id=' + id;
    }
    
    document.getElementById('actionsModal').classList.add('show');
}

function closeActionsModal() {
    document.getElementById('actionsModal').classList.remove('show');
}

function editInstructorFromModal() {
    closeActionsModal();
    editInstructor(currentInstructorId);
}

function promoteFromModal() {
    closeActionsModal();
    showPromoteModal();
}

function showPromoteModal() {
    document.getElementById('promoteInstructorId').value = currentInstructorId;
    document.getElementById('promoteInstructorName').textContent = currentInstructorName;
    document.getElementById('promotePassword').value = '';
    document.getElementById('promoteConfirmPassword').value = '';
    document.getElementById('promoteModal').classList.add('show');
}

// Check for success/error messages in URL and show modal
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') || urlParams.get('error')) {
        // Page was redirected back after an action, reload to clear URL params
        // But first show the message
    }
});

// Close modal when clicking outside
document.getElementById('actionsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeActionsModal();
    }
});

function filterInstructors() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const deptFilter = document.getElementById('deptFilter').value;
    const table = document.getElementById('instructorsTable');
    if (!table) return;
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        const tdName = tr[i].getElementsByTagName('td')[0];
        const tdDept = tr[i].getElementsByTagName('td')[3];
        const tdRole = tr[i].getElementsByTagName('td')[4];
        
        if (tdName && tdDept && tdRole) {
            const nameText = tdName.textContent || tdName.innerText;
            const deptText = tdDept.textContent || tdDept.innerText;
            const roleText = tdRole.textContent || tdRole.innerText;
            
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
