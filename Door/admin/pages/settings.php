<?php
require_once __DIR__ . '/../../../data/config.php';

// Fetch current admin data (session already started in dashboard)
$admin_name = "Administrator";
$admin_email = "admin@cjcm.edu";

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' && isset($_SESSION['user_id'])) {
    $admin_name = $_SESSION['user_name'] ?? "Administrator";
    $admin_email = $_SESSION['user_email'] ?? "admin@cjcm.edu";
}

// Fetch system settings from database
$current_system_name = "Student Evaluation System";
$current_system_tagline = "Empowering excellence in education through comprehensive student performance tracking, evaluation, and assessment reporting.";

if (isset($pdo) && $pdo) {
    try {
        $stmt = $pdo->query("SELECT system_name, system_tagline FROM admins ORDER BY id LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (!empty($row['system_name'])) $current_system_name = $row['system_name'];
            if (!empty($row['system_tagline'])) $current_system_tagline = $row['system_tagline'];
        }
    } catch (Exception $e) {
        // Use defaults if table/columns don't exist yet
    }
}
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">Configure system settings</p>
    </div>
</div>

<!-- System Settings -->
<div class="card" style="background: linear-gradient(135deg, rgba(212, 168, 67, 0.03) 0%, rgba(255, 255, 255, 1) 100%); border: 1px solid rgba(212, 168, 67, 0.15); box-shadow: 0 8px 32px rgba(212, 168, 67, 0.08);">
    <div class="card-header" style="background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%); border: none; padding: 20px 24px;">
        <h3 class="card-title" style="color: white; font-size: 18px; font-weight: 700;">
            <i class="fas fa-cog" style="color: white;"></i>
            System Settings
        </h3>
    </div>
    <div class="form-grid" style="padding: 24px;">
        <div class="form-group" style="grid-column: span 2; margin-bottom: 0;">
            <label class="form-label" style="color: var(--gold-dark); font-weight: 600; letter-spacing: 1px; text-transform: uppercase; font-size: 0.7rem;">System Name</label>
            <input type="text" id="systemName" class="form-input" value="<?php echo htmlspecialchars($current_system_name); ?>" style="border: 2px solid var(--border-light); border-radius: 12px; padding: 14px 16px; font-size: 15px; font-weight: 500; color: var(--dark-text); background: white; transition: all 0.3s ease;" onfocus="this.style.borderColor='var(--gold-primary)'; this.style.boxShadow='0 0 0 4px rgba(212, 168, 67, 0.1)';" onblur="this.style.borderColor='var(--border-light)'; this.style.boxShadow='none';">
        </div>
        <div class="form-group" style="grid-column: span 2; margin-bottom: 0;">
            <label class="form-label" style="color: var(--gold-dark); font-weight: 600; letter-spacing: 1px; text-transform: uppercase; font-size: 0.7rem;">System Tagline</label>
            <textarea id="systemTagline" class="form-input" rows="3" style="border: 2px solid var(--border-light); border-radius: 12px; padding: 14px 16px; font-size: 15px; color: var(--dark-text); background: white; transition: all 0.3s ease; min-height: 80px; resize: vertical;"><?php echo htmlspecialchars($current_system_tagline); ?></textarea>
        </div>
    </div>
    <div class="form-actions" style="padding: 0 24px 24px;">
        <button type="button" id="saveSystemSettings" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Settings
        </button>
        <span id="settingsStatus" style="margin-left: 12px; font-size: 14px;"></span>
    </div>
</div>

<!-- Account Settings -->
<div class="card" style="margin-top: 24px; background: linear-gradient(135deg, rgba(212, 168, 67, 0.03) 0%, rgba(255, 255, 255, 1) 100%); border: 1px solid rgba(212, 168, 67, 0.15); box-shadow: 0 8px 32px rgba(212, 168, 67, 0.08);">
    <div class="card-header" style="background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%); border: none; padding: 20px 24px;">
        <h3 class="card-title" style="color: white; font-size: 18px; font-weight: 700;">
            <i class="fas fa-user-cog" style="color: white;"></i>
            Admin Account
        </h3>
    </div>
    <div class="card-body" style="padding: 32px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 24px; align-items: center; flex-wrap: wrap; row-gap: 16px;">
            <div>
                <span style="font-size: 0.65rem; color: var(--gold-dark); font-weight: 600; letter-spacing: 1px; text-transform: uppercase;">Admin Name</span>
                <p id="adminNameDisplay" style="font-size: 1.25rem; font-weight: 700; color: var(--dark-text); margin: 4px 0 0;"><?php echo htmlspecialchars($admin_name); ?></p>
            </div>
            <div>
                <span style="font-size: 0.65rem; color: var(--gold-dark); font-weight: 600; letter-spacing: 1px; text-transform: uppercase;">Admin Email</span>
                <p id="adminEmailDisplay" style="font-size: 1.1rem; font-weight: 600; color: var(--dark-text); margin: 4px 0 0;"><?php echo htmlspecialchars($admin_email); ?></p>
            </div>
            <div>
                <button type="button" id="openUpdateModal" class="btn btn-primary" style="min-width: 160px; padding: 12px 24px; font-size: 14px; font-weight: 600; height: fit-content;">
                    <i class="fas fa-user-edit"></i> Update Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Info -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-info-circle"></i>
            System Information
        </h3>
    </div>
    <div class="card-body" style="padding: 24px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="padding: 16px; background: var(--cream); border-radius: 12px;">
                <div style="font-size: 12px; color: var(--light-text); margin-bottom: 4px;">Total Users</div>
                <div style="font-size: 24px; font-weight: 700; color: var(--dark-text);">54</div>
            </div>
            <div style="padding: 16px; background: var(--cream); border-radius: 12px;">
                <div style="font-size: 12px; color: var(--light-text); margin-bottom: 4px;">Evaluations</div>
                <div style="font-size: 24px; font-weight: 700; color: var(--dark-text);">156</div>
            </div>
            <div style="padding: 16px; background: var(--cream); border-radius: 12px;">
                <div style="font-size: 12px; color: var(--light-text); margin-bottom: 4px;">Departments</div>
                <div style="font-size: 24px; font-weight: 700; color: var(--dark-text);">3</div>
            </div>
            <div style="padding: 16px; background: var(--cream); border-radius: 12px;">
                <div style="font-size: 12px; color: var(--light-text); margin-bottom: 4px;">Courses</div>
                <div style="font-size: 24px; font-weight: 700; color: var(--dark-text);">42</div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveSystemSettings');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const systemName = document.getElementById('systemName').value.trim();
            const systemTagline = document.getElementById('systemTagline').value.trim();
            const statusSpan = document.getElementById('settingsStatus');
            if (statusSpan) statusSpan.textContent = '';
            if (!systemName) {
                if (statusSpan) {
                    statusSpan.textContent = 'System name is required';
                    statusSpan.style.color = 'var(--danger)';
                }
                return;
            }
            fetch('../../data/save_system_settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ system_name: systemName, system_tagline: systemTagline })
            })
            .then(r => r.json())
            .then(data => {
                if (statusSpan) {
                    if (data.success) {
                        statusSpan.textContent = 'Settings saved successfully!';
                        statusSpan.style.color = 'var(--success)';
                    } else {
                        statusSpan.textContent = data.message || 'Failed to save';
                        statusSpan.style.color = 'var(--danger)';
                    }
                }
            })
            .catch(err => {
                if (statusSpan) {
                    statusSpan.textContent = 'Error occurred';
                    statusSpan.style.color = 'var(--danger)';
                }
                console.error('Save error:', err);
            });
        });
    }
});
</script>

<style>
/* Custom Modal Styles */
.custom-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Poppins', sans-serif;
}
.custom-modal .modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
}
.custom-modal .modal-content {
    position: relative;
    background: white;
    border-radius: 20px;
    padding: 32px;
    width: 90%;
    max-width: 420px;
    box-shadow: 0 20px 60px rgba(212, 168, 67, 0.3);
    border: 1px solid rgba(212, 168, 67, 0.2);
    animation: modalSlideIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes modalSlideIn {
    from { opacity: 0; transform: translateY(-20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.custom-modal h3 {
    margin: 0 0 20px;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--dark-text);
    text-align: center;
}
.custom-modal .form-group {
    margin-bottom: 16px;
}
.custom-modal label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--gold-dark);
    margin-bottom: 6px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.custom-modal input[type="email"],
.custom-modal input[type="password"] {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--border-light);
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s ease;
}
.custom-modal input[type="email"]:focus,
.custom-modal input[type="password"]:focus {
    outline: none;
    border-color: var(--gold-primary);
    box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.1);
}
.custom-modal .modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    justify-content: flex-end;
}
.custom-modal .btn {
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}
.custom-modal .btn-secondary {
    background: var(--cream);
    color: var(--dark-text);
    border: 1px solid var(--border-light);
}
.custom-modal .btn-secondary:hover {
    background: #f0ebe4;
}
.custom-modal .btn-primary {
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
    color: white;
}
.custom-modal .btn-primary:hover {
    box-shadow: 0 6px 20px rgba(212, 168, 67, 0.4);
    transform: translateY(-1px);
}
.custom-modal .error-msg {
    color: var(--danger);
    font-size: 0.85rem;
    margin-top: 12px;
    text-align: center;
    min-height: 1.2em;
}
.custom-modal .success-icon {
    font-size: 48px;
    color: var(--success);
    margin-bottom: 12px;
}
.custom-modal .error-icon {
    font-size: 48px;
    color: var(--danger);
    margin-bottom: 12px;
}
.custom-modal .feedback-content {
    text-align: center;
}
</style>

</style>

<!-- Verify Current Account Modal -->
<div id="verifyModal" class="custom-modal" style="display:none;">
    <div class="modal-backdrop" id="verifyBackdrop"></div>
    <div class="modal-content">
        <h3>Verify Current Account</h3>
        <div class="form-group">
            <label>Current Email</label>
            <input type="email" id="verifyEmail" value="<?php echo htmlspecialchars($admin_email); ?>">
        </div>
        <div class="form-group">
            <label>Current Password</label>
            <div style="position: relative;">
                <input type="password" id="verifyPassword" placeholder="Enter your current password" style="padding-right: 42px;">
                <button type="button" class="toggle-password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--light-text); cursor: pointer; padding: 4px;">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        <p id="verifyError" class="error-msg"></p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" id="cancelVerify">Cancel</button>
            <button type="button" class="btn btn-primary" id="verifyBtn">Verify</button>
        </div>
    </div>
</div>

<!-- Update Account Modal -->
<div id="updateModal" class="custom-modal" style="display:none;">
    <div class="modal-backdrop" id="updateBackdrop"></div>
    <div class="modal-content">
        <h3>Update Account</h3>
        <div class="form-group">
            <label>New Email</label>
            <input type="email" id="newEmail" placeholder="Enter new email">
        </div>
        <div class="form-group">
            <label>New Password</label>
            <div style="position: relative;">
                <input type="password" id="newPassword" placeholder="At least 6 characters" style="padding-right: 42px;">
                <button type="button" class="toggle-password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--light-text); cursor: pointer; padding: 4px;">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <div style="position: relative;">
                <input type="password" id="confirmPassword" placeholder="Re-enter new password" style="padding-right: 42px;">
                <button type="button" class="toggle-password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--light-text); cursor: pointer; padding: 4px;">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        <p id="updateError" class="error-msg"></p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" id="cancelUpdate">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveUpdate">Save Changes</button>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="custom-modal" style="display:none;">
    <div class="modal-backdrop" id="feedbackBackdrop"></div>
    <div class="modal-content" style="text-align: center;">
        <div id="feedbackIcon"></div>
        <h3 id="feedbackTitle"></h3>
        <p id="feedbackMessage" style="color: var(--light-text); margin-top: 8px;"></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('openUpdateModal');
    const verifyModal = document.getElementById('verifyModal');
    const updateModal = document.getElementById('updateModal');
    const feedbackModal = document.getElementById('feedbackModal');
    const verifyBackdrop = document.getElementById('verifyBackdrop');
    const updateBackdrop = document.getElementById('updateBackdrop');
    const feedbackBackdrop = document.getElementById('feedbackBackdrop');

    let currentPassword = '';

    // Toggle password visibility
    function setupPasswordToggle(button) {
        if (button) {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }
    }

    // Initialize toggles
    document.querySelectorAll('.toggle-password').forEach(btn => setupPasswordToggle(btn));

    // Open verification modal
    if (openBtn) {
        openBtn.addEventListener('click', function() {
            verifyModal.style.display = 'flex';
            document.getElementById('verifyPassword').value = '';
            document.getElementById('verifyError').textContent = '';
            currentPassword = '';
        });
    }

    // Close any modal on backdrop click
    [verifyBackdrop, updateBackdrop, feedbackBackdrop].forEach(backdrop => {
        if (backdrop) backdrop.addEventListener('click', closeAll);
    });

    document.getElementById('cancelVerify').addEventListener('click', closeAll);
    document.getElementById('cancelUpdate').addEventListener('click', closeAll);

    // Verify current credentials
    document.getElementById('verifyBtn').addEventListener('click', function() {
        const email = document.getElementById('verifyEmail').value.trim();
        const pwd = document.getElementById('verifyPassword').value;
        const errEl = document.getElementById('verifyError');
        errEl.textContent = '';
        if (!email) {
            errEl.textContent = 'Enter your email';
            return;
        }
        if (!pwd) {
            errEl.textContent = 'Enter your current password';
            return;
        }
        fetch('../../data/admin_update_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                current_email: email,
                current_password: pwd
            })
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            if (data.success && data.verified) {
                currentPassword = pwd;
                verifyModal.style.display = 'none';
                // Show update modal
                document.getElementById('newEmail').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
                document.getElementById('updateError').textContent = '';
                updateModal.style.display = 'flex';
            } else {
                errEl.textContent = data.message || 'Verification failed';
            }
        })
        .catch(err => {
            errEl.textContent = 'Network error. Please try again.';
            console.error(err);
        });
    });

    // Save updates
    document.getElementById('saveUpdate').addEventListener('click', function() {
        const newEmail = document.getElementById('newEmail').value.trim();
        const newPwd = document.getElementById('newPassword').value;
        const confirmPwd = document.getElementById('confirmPassword').value;
        const errEl = document.getElementById('updateError');
        errEl.textContent = '';

        if (!newEmail && !newPwd) {
            errEl.textContent = 'Provide new email or new password';
            return;
        }
        if (!currentPassword) {
            errEl.textContent = 'Please verify your account first';
            return;
        }
        if (newPwd && newPwd !== confirmPwd) {
            errEl.textContent = 'New passwords do not match';
            return;
        }
        if (newPwd && newPwd.length < 6) {
            errEl.textContent = 'New password must be at least 6 characters';
            return;
        }

        fetch('../../data/admin_update_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                current_password: currentPassword,
                new_email: newEmail,
                new_password: newPwd
            })
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            closeAll();
            if (data.success && newEmail) {
                document.getElementById('adminEmailDisplay').textContent = newEmail;
            }
            showFeedback(data.success, data.message || (data.success ? 'Account updated' : 'Update failed'));
        })
        .catch(err => {
            errEl.textContent = 'Network error. Please try again.';
            console.error(err);
        });
    });

    function closeAll() {
        verifyModal.style.display = 'none';
        updateModal.style.display = 'none';
        feedbackModal.style.display = 'none';
    }

    function showFeedback(success, message) {
        const icon = document.getElementById('feedbackIcon');
        const title = document.getElementById('feedbackTitle');
        const msg = document.getElementById('feedbackMessage');
        if (success) {
            icon.innerHTML = '<i class="fas fa-check-circle success-icon"></i>';
            title.textContent = 'Success!';
            title.style.color = 'var(--success)';
        } else {
            icon.innerHTML = '<i class="fas fa-exclamation-circle error-icon"></i>';
            title.textContent = 'Error';
            title.style.color = 'var(--danger)';
        }
        msg.textContent = message;
        feedbackModal.style.display = 'flex';
        // auto close after 2 seconds
        setTimeout(closeAll, 2000);
    }
});
</script>

