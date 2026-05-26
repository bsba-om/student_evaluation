<div class="page-header">
    <div>
        <h1 class="page-title">Remove Instructor</h1>
        <p class="page-subtitle">Remove an instructor from the system</p>
    </div>
    <a href="dashboard.php?page=manage_program_heads" class="btn" style="background: var(--cream); color: var(--dark-text);">
        <i class="fas fa-arrow-left"></i>
        Back to List
    </a>
</div>

<!-- Error Message -->
<?php if (isset($_GET['error'])): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <span><?php echo htmlspecialchars($_GET['error']); ?></span>
</div>
<?php endif; ?>

<!-- Program Heads to Remove -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-user-minus"></i>
            Select Instructor to Remove
        </h3>
    </div>
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Instructor</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #d4a843, #e8c768); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">JH</div>
                            <div>
                                <div style="font-weight: 600;">John Harris</div>
                                <div style="font-size: 12px; color: #6b7280;">john.harris@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td>john.harris@example.com</td>
                    <td>Computer Science</td>
                    <td><span class="status-badge active">Active</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmRemove(1, 'John Harris')">
                            <i class="fas fa-trash"></i>
                            Remove
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #60a5fa); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">SM</div>
                            <div>
                                <div style="font-weight: 600;">Sarah Miller</div>
                                <div style="font-size: 12px; color: #6b7280;">sarah.miller@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td>sarah.miller@example.com</td>
                    <td>Information Technology</td>
                    <td><span class="status-badge active">Active</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmRemove(2, 'Sarah Miller')">
                            <i class="fas fa-trash"></i>
                            Remove
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #16a34a, #4ade80); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">RW</div>
                            <div>
                                <div style="font-weight: 600;">Robert Wilson</div>
                                <div style="font-size: 12px; color: #6b7280;">robert.wilson@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td>robert.wilson@example.com</td>
                    <td>Business Administration</td>
                    <td><span class="status-badge active">Active</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmRemove(3, 'Robert Wilson')">
                            <i class="fas fa-trash"></i>
                            Remove
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #8b5cf6, #a78bfa); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">LJ</div>
                            <div>
                                <div style="font-weight: 600;">Lisa Johnson</div>
                                <div style="font-size: 12px; color: #6b7280;">lisa.johnson@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td>lisa.johnson@example.com</td>
                    <td>Engineering</td>
                    <td><span class="status-badge inactive">Inactive</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmRemove(4, 'Lisa Johnson')">
                            <i class="fas fa-trash"></i>
                            Remove
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #f59e0b, #fbbf24); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">MB</div>
                            <div>
                                <div style="font-weight: 600;">Michael Brown</div>
                                <div style="font-size: 12px; color: #6b7280;">michael.brown@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td>michael.brown@example.com</td>
                    <td>Mathematics</td>
                    <td><span class="status-badge active">Active</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmRemove(5, 'Michael Brown')">
                            <i class="fas fa-trash"></i>
                            Remove
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #ec4899, #f472b6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">AW</div>
                            <div>
                                <div style="font-weight: 600;">Amanda White</div>
                                <div style="font-size: 12px; color: #6b7280;">amanda.white@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td>amanda.white@example.com</td>
                    <td>Computer Science</td>
                    <td><span class="status-badge active">Active</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmRemove(6, 'Amanda White')">
                            <i class="fas fa-trash"></i>
                            Remove
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #14b8a6, #2dd4bf); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">DK</div>
                            <div>
                                <div style="font-weight: 600;">David Kim</div>
                                <div style="font-size: 12px; color: #6b7280;">david.kim@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td>david.kim@example.com</td>
                    <td>Information Technology</td>
                    <td><span class="status-badge active">Active</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmRemove(7, 'David Kim')">
                            <i class="fas fa-trash"></i>
                            Remove
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #818cf8); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">EG</div>
                            <div>
                                <div style="font-weight: 600;">Emily Garcia</div>
                                <div style="font-size: 12px; color: #6b7280;">emily.garcia@example.com</div>
                            </div>
                        </div>
                    </td>
                    <td>emily.garcia@example.com</td>
                    <td>Business Administration</td>
                    <td><span class="status-badge inactive">Inactive</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmRemove(8, 'Emily Garcia')">
                            <i class="fas fa-trash"></i>
                            Remove
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="removeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; padding: 32px; max-width: 400px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(220, 38, 38, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 28px; color: #dc2626;"></i>
            </div>
            <h3 style="font-size: 20px; font-weight: 700; color: var(--dark-text); margin-bottom: 8px;">Confirm Removal</h3>
            <p style="color: var(--light-text);">Are you sure you want to remove <strong id="removeName"></strong> from the system? This action cannot be undone.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="#" id="confirmRemoveBtn" class="btn btn-danger" style="flex: 1; justify-content: center;">
                <i class="fas fa-trash"></i>
                Yes, Remove
            </a>
            <button type="button" onclick="closeModal()" class="btn" style="flex: 1; justify-content: center; background: var(--cream); color: var(--dark-text);">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
    function confirmRemove(id, name) {
        document.getElementById('removeName').textContent = name;
        document.getElementById('confirmRemoveBtn').href = '../../data/admin_process.php?action=remove_program_head&id=' + id;
        document.getElementById('removeModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('removeModal').style.display = 'none';
    }

    // Close modal when clicking outside
    document.getElementById('removeModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>
