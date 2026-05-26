document.addEventListener('DOMContentLoaded', function() {
    // Role dropdown element (used only for reading selected value)
    const dropdownTrigger = document.getElementById('dropdownTrigger');

    // Check for saved credentials on page load
    const savedCredentials = localStorage.getItem('rememberMeCredentials');
    if (savedCredentials) {
        try {
            const credentials = JSON.parse(savedCredentials);
            document.getElementById('username').value = credentials.email || '';
            document.getElementById('password').value = credentials.password || '';
        } catch (e) {
            console.error('Error parsing saved credentials:', e);
        }
    }

    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
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

    // Form submission
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');

    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const role = dropdownTrigger.getAttribute('data-selected');
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            
            if (!role) {
                showToast('Please select a role');
                return;
            }

            // Show loading state
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

                // Check if response is ok
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Invalid response content type');
                }

                const result = await response.json();

                if (result.success) {
                    // Clear the logged_out flag since user is logging in
                    sessionStorage.removeItem('logged_out');
                    sessionStorage.setItem('on_protected_page', 'true');
                    // Set just_logged_in flag so session_guard.js skips the initial logout check
                    sessionStorage.setItem('just_logged_in', 'true');

                    // Show loading modal overlay
                    const overlay = document.getElementById('loginLoadingOverlay');
                    if (overlay) {
                        overlay.style.display = 'flex';
                    }
                    setTimeout(() => {
                        window.location.replace(result.redirect);
                    }, 5000);
                } else {
                    showToast(result.message || 'Login failed. Please try again.');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.');
                console.error('Login error:', error);
            } finally {
                loginBtn.classList.remove('loading');
                loginBtn.disabled = false;
            }
        });
    }

});
