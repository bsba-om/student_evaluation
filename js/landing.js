// landing.js - Handles sliding panels, particles, and form submissions

document.addEventListener('DOMContentLoaded', function() {
    // ===========================
    // Mobile Navigation Toggle
    // ===========================
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');
    const navbar = document.getElementById('navbar');
    
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function() {
            navLinks.classList.toggle('open');
            navToggle.classList.toggle('active');
            
            // Animate hamburger to X
            const spans = navToggle.querySelectorAll('span');
            if (navToggle.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
        
        // Close menu when clicking on a link
        const navLinksItems = navLinks.querySelectorAll('a');
        navLinksItems.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.classList.remove('open');
                navToggle.classList.remove('active');
                const spans = navToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navbar.contains(e.target) && navLinks.classList.contains('open')) {
                navLinks.classList.remove('open');
                navToggle.classList.remove('active');
                const spans = navToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
        
        // Close menu on window resize to desktop
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth > 768 && navLinks.classList.contains('open')) {
                    navLinks.classList.remove('open');
                    navToggle.classList.remove('active');
                    const spans = navToggle.querySelectorAll('span');
                    spans[0].style.transform = 'none';
                    spans[1].style.opacity = '1';
                    spans[2].style.transform = 'none';
                }
            }, 250);
        });
    }
    // ===========================
    // Particle Effect
    // ===========================
    const particlesContainer = document.getElementById('particles');
    if (particlesContainer) {
        const particleCount = 30;
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            const size = Math.random() * 4 + 2;
            particle.style.width = size + 'px';
            particle.style.height = size + 'px';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.background = `rgba(212, 168, 67, ${Math.random() * 0.5 + 0.2})`;
            particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
            particle.style.animationDelay = Math.random() * 5 + 's';
            particlesContainer.appendChild(particle);
        }
    }

    // ===========================
    // DOM Elements
    // ===========================
    const openLoginBtn = document.getElementById('openLoginPanel');
    const loginPanel = document.getElementById('loginPanel');
    const heroContent = document.getElementById('heroContent');
    const closeLoginBtn = document.getElementById('closeLoginPanel');
    const closeRegisterBtn = document.getElementById('closeRegisterPanel');
    const switchToRegisterBtn = document.getElementById('switchToRegister');
    const switchToLoginBtn = document.getElementById('switchToLogin');
    const panelBackdrop = document.getElementById('panelBackdrop');

    // Role dropdown elements
    const dropdownTrigger = document.getElementById('dropdownTrigger');
    const dropdownMenu = document.getElementById('dropdownMenu');
    const dropdownArrow = document.getElementById('dropdownArrow');
    const selectedRole = document.getElementById('selectedRole');
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    const loginFooter = document.getElementById('loginFooter');

    // Toggle Panel
    function openPanel() {
        loginPanel.classList.add('active');
        heroContent.classList.add('shifted');
        if (panelBackdrop) panelBackdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closePanel() {
        // Ensure dropdown is closed and margin removed
        if (dropdownTrigger && dropdownMenu && dropdownArrow) {
            dropdownTrigger.classList.remove('active');
            dropdownMenu.classList.remove('open');
            dropdownArrow.classList.remove('rotated');
            const roleSelector = dropdownTrigger.closest('.role-selector');
            if (roleSelector) roleSelector.classList.remove('dropdown-open');
        }
        loginPanel.classList.remove('active');
        heroContent.classList.remove('shifted');
        if (panelBackdrop) panelBackdrop.classList.remove('active');
        document.body.style.overflow = '';
        // Reset to login view after a short delay if in register mode
        setTimeout(() => {
            if (registerView && registerView.style.display === 'block') {
                switchToLogin();
            }
        }, 300);
    }

    if (openLoginBtn) openLoginBtn.addEventListener('click', function(e) {
        e.preventDefault();
        openPanel();
    });
    if (closeLoginBtn) closeLoginBtn.addEventListener('click', closePanel);
    if (closeRegisterBtn) closeRegisterBtn.addEventListener('click', closePanel);
    if (panelBackdrop) panelBackdrop.addEventListener('click', closePanel);

    // ===========================
    // Dropdown Role Selection
    // ===========================
    if (dropdownTrigger && dropdownMenu && dropdownArrow && selectedRole) {
        // Helper to close dropdown and remove margin
        function closeDropdown() {
            dropdownTrigger.classList.remove('active');
            dropdownMenu.classList.remove('open');
            dropdownArrow.classList.remove('rotated');
            const roleSelector = dropdownTrigger.closest('.role-selector');
            if (roleSelector) roleSelector.classList.remove('dropdown-open');
        }

        dropdownTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = this.classList.toggle('active');
            dropdownMenu.classList.toggle('open');
            dropdownArrow.classList.toggle('rotated');
            const roleSelector = this.closest('.role-selector');
            if (roleSelector) {
                if (isOpen) {
                    roleSelector.classList.add('dropdown-open');
                } else {
                    roleSelector.classList.remove('dropdown-open');
                }
            }
        });

        dropdownItems.forEach(item => {
            item.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                const title = this.querySelector('.dropdown-item-title').textContent;
                
                // Remove selected class from all items
                dropdownItems.forEach(i => i.classList.remove('selected'));
                
                // Add selected class to clicked item
                this.classList.add('selected');
                
                // Update displayed text
                selectedRole.textContent = title;
                selectedRole.classList.remove('placeholder');
                
                // Store selected value
                dropdownTrigger.setAttribute('data-selected', value);
                
                // Close dropdown
                closeDropdown();
                
                // Show sign-up button only if instructor selected
                if (value === 'instructor' && loginFooter) {
                    loginFooter.style.display = 'block';
                } else if (loginFooter) {
                    loginFooter.style.display = 'none';
                }
    });

    // ===========================
    // Toast Notification
    // ===========================
    window.showToast = function(message, type = 'info') {
        // Find the active login panel to append toast to
        const loginPanel = document.getElementById('loginPanel');
        if (!loginPanel) return;
        
        let toast = loginPanel.querySelector('.toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = `
                <button class="toast-close" aria-label="Close">&times;</button>
                <i class="fas fa-info-circle toast-icon"></i>
                <span class="toast-message"></span>
            `;
            loginPanel.appendChild(toast);
            toast.querySelector('.toast-close').addEventListener('click', () => {
                toast.classList.remove('show');
            });
        }
        const toastMessage = toast.querySelector('.toast-message');
        const icon = toast.querySelector('.toast-icon');
        
        toastMessage.textContent = message;
        toast.className = 'toast';
        
        // Set border color and icon based on type
        const colors = {
            'error': '#dc2626',
            'warning': '#f59e0b',
            'success': '#10b981',
            'info': '#3b82f6'
        };
        const icons = {
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'success': 'fa-check-circle',
            'info': 'fa-info-circle'
        };
        
        const color = colors[type] || colors.info;
        const iconClass = icons[type] || icons.info;
        
        toast.style.borderLeftColor = color;
        icon.className = 'fas ' + iconClass + ' toast-icon';
        icon.style.color = color;
        
        toast.classList.add('show');
        
        // Auto hide after 4 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    };
});
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownTrigger.contains(e.target) && !dropdownMenu.contains(e.target)) {
                closeDropdown();
            }
        });
    }

    // ===========================
    // Toggle Password Visibility
    // ===========================
    const toggleLoginPassword = document.getElementById('toggleLoginPassword');
    const loginPassword = document.getElementById('loginPassword');
    if (toggleLoginPassword && loginPassword) {
        toggleLoginPassword.addEventListener('click', function() {
            const type = loginPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            loginPassword.setAttribute('type', type);
            const icon = this.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    const toggleRegPassword = document.getElementById('toggleRegPassword');
    const regPassword = document.getElementById('regPassword');
    if (toggleRegPassword && regPassword) {
        toggleRegPassword.addEventListener('click', function() {
            const type = regPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            regPassword.setAttribute('type', type);
            const icon = this.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    // ===========================
    // View Switching
    // ===========================
    const loginView = document.getElementById('loginView');
    const registerView = document.getElementById('registerView');

    if (switchToRegisterBtn) {
        switchToRegisterBtn.addEventListener('click', function() {
            loginView.style.display = 'none';
            registerView.style.display = 'block';
            loginPanel.classList.add('register-mode');
        });
    }

    function switchToLogin() {
        registerView.style.display = 'none';
        loginView.style.display = 'block';
        loginPanel.classList.remove('register-mode');
    }

    if (switchToLoginBtn) {
        switchToLoginBtn.addEventListener('click', switchToLogin);
    }

    // ===========================
    // Login Form Submission
    // ===========================
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');

    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const role = dropdownTrigger ? dropdownTrigger.getAttribute('data-selected') : '';
            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;

            if (!role) {
                showToast('Please select a role', 'warning');
                return;
            }

            if (!email || !password) {
                showToast('Please enter email and password', 'warning');
                return;
            }

            loginBtn.classList.add('loading');
            loginBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('role', role);
                formData.append('email', email);
                formData.append('password', password);

                const response = await fetch('./data/login_process.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Invalid response content type');
                }

                const result = await response.json();

                if (result.success) {
                    sessionStorage.removeItem('logged_out');
                    sessionStorage.setItem('on_protected_page', 'true');
                    sessionStorage.setItem('just_logged_in', 'true');
                    setTimeout(() => {
                        window.location.replace(result.redirect);
                    }, 500);
                } else {
                    // Show error message from server
                    const errorMsg = result.message || 'Login failed. Please try again.';
                    showToast(errorMsg, 'error');
                }
            } catch (error) {
                console.error('Login error:', error);
            } finally {
                loginBtn.classList.remove('loading');
                loginBtn.disabled = false;
            }
        });
    }

    // ===========================
    // Register Form Submission
    // ===========================
    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');

    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const firstName = document.getElementById('regFirstName').value.trim();
            const lastName = document.getElementById('regLastName').value.trim();
            const middleName = document.getElementById('regMiddleName').value.trim();
            const suffix = document.getElementById('regSuffix').value;
            const email = document.getElementById('regEmail').value.trim();
            const employeeId = document.getElementById('regEmployeeId').value.trim();
            const department = document.getElementById('regDepartment').value;
            const password = document.getElementById('regPassword').value;
            const confirmPassword = document.getElementById('regConfirmPassword').value;

            if (password !== confirmPassword) {
                showToast('Passwords do not match', 'error');
                return;
            }

            if (password.length < 6) {
                showToast('Password must be at least 6 characters', 'warning');
                return;
            }

            if (!department) {
                showToast('Please select a department', 'warning');
                return;
            }

            if (!firstName || !lastName || !email || !employeeId) {
                showToast('Please fill in all required fields', 'warning');
                return;
            }

            registerBtn.classList.add('loading');
            registerBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'register_instructor');
                formData.append('first_name', firstName);
                formData.append('last_name', lastName);
                formData.append('middle_name', middleName);
                formData.append('suffix', suffix);
                formData.append('email', email);
                formData.append('employee_id', employeeId);
                formData.append('department', department);
                formData.append('password', password);

                const response = await fetch('./data/admin_process.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message || 'Registration successful!', 'success');
                    registerForm.reset();
                    setTimeout(() => {
                        switchToLogin();
                    }, 1500);
                } else {
                    // Show error message from server
                    const errorMsg = result.message || 'Registration failed. Please try again.';
                    showToast(errorMsg, 'error');
                }
            } catch (error) {
                console.error('Registration error:', error);
            } finally {
                registerBtn.classList.remove('loading');
                registerBtn.disabled = false;
            }
        });
    }

});