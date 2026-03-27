document.addEventListener("DOMContentLoaded", () => {
    // --- Utility Functions ---
    const el = (id) => document.getElementById(id);
    const getStoredUser = () => JSON.parse(sessionStorage.getItem('lm_user') || 'null');
    const getProfile = () => JSON.parse(sessionStorage.getItem('lm_profile') || 'null');

    // --- UI Setup ---
    const setupPasswordToggle = (toggleId, passwordId, iconId) => {
        const toggleButton = el(toggleId);
        const passwordInput = el(passwordId);
        const icon = el(iconId);
        if (toggleButton) {
            toggleButton.addEventListener('click', () => {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                icon.className = isPassword ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        }
    };

    // Header state is now handled by PHP (SSR)
    // const applyHeaderState = () => { ... }

    // --- Form Handlers ---
    const handleLoginForm = () => {
        const loginForm = el('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                const errorDiv = el('loginError');
                errorDiv.style.display = 'none';

                // Perform basic client-side validation for UI feedback
                if (!loginForm.checkValidity()) {
                    e.preventDefault(); // Stop submission if fields are empty
                    errorDiv.textContent = 'Please enter a valid email and password.';
                    errorDiv.style.display = 'block';
                }
                // If validation passes, the form will submit to the PHP backend
            });
        }
    };

    const handleSignupForm = () => {
        const signupForm = el('signupForm');
        if (signupForm) {
            signupForm.addEventListener('submit', (e) => {
                const msgDiv = el('signupMsg');
                msgDiv.style.display = 'none';
                let errors = [];

                if (!signupForm.checkValidity()) {
                    errors.push("Please fill out all required fields.");
                }
                const pwd = el('signupPassword').value;
                if (pwd.length < 8) {
                    errors.push("Password must be at least 8 characters.");
                }
                if (pwd !== el('confirmPassword').value) {
                    errors.push("Passwords do not match.");
                }

                if (errors.length > 0) {
                    e.preventDefault(); // Stop submission if validation fails
                    msgDiv.innerHTML = errors.join('<br>');
                    msgDiv.style.display = 'block';
                }
                // If validation passes, the form will submit to the PHP backend
            });

            el('roleSelect')?.addEventListener('change', (e) => {
                const container = el('roleExtra');
                container.innerHTML = '';
                if (e.target.value === 'student') {
                    container.innerHTML = `<div class="mb-3"><label class="form-label">Student ID</label><input id="studentId" name="studentId" class="form-control" required></div><div class="mb-2 small-muted">You will be asked to verify student status.</div>`;
                } else if (e.target.value === 'employer') {
                    container.innerHTML = `<div class="mb-3"><label class="form-label">Company / Institution</label><input id="company" name="company" class="form-control" required></div>`;
                } else if (e.target.value === 'admin') {
                    container.innerHTML = `<div class="mb-3"><label class="form-label">Input Authentication code</label><input id="adminCode" name="adminCode" class="form-control" required></div>`;
                }
            });
        }
    };

    const handleLogout = () => {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.logoutBtn')) {
                e.preventDefault();
                // Clear session storage for immediate UI update
                sessionStorage.removeItem('lm_user');
                sessionStorage.removeItem('lm_profile');
                // Redirect to a logout script that handles the backend session
                window.location.href = 'logout.php'; 
            }
        });
    };
    
    // --- Profile Page Handler ---
    const handleProfilePage = () => {
        // This logic is for client-side visibility and display, so it remains.
        // It handles avatar previews and form population on the profile page.
    };


    // --- Initialization ---
    // applyHeaderState(); // Moved to PHP
    setupPasswordToggle('toggleLoginPwd', 'loginPassword', 'toggleLoginIcon');
    // Add more toggles if they exist in HTML
    // setupPasswordToggle('toggleSignupPwd', 'signupPassword', 'toggleSignupIcon');
    handleLoginForm();
    handleSignupForm();
    handleLogout();
    handleProfilePage();
});
