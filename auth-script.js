document.addEventListener('DOMContentLoaded', function() {
    // Get form elements
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const showRegisterLink = document.getElementById('show-register');
    const showLoginLink = document.getElementById('show-login');
    
    // Switch to registration form
    showRegisterLink.addEventListener('click', function(e) {
        e.preventDefault();
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
    });
    
    // Switch to login form
    showLoginLink.addEventListener('click', function(e) {
        e.preventDefault();
        registerForm.style.display = 'none';
        loginForm.style.display = 'block';
    });
    
    // Handle login form submission
    if (loginForm) {
        const form = loginForm.querySelector('form');
        form.addEventListener('submit', function(e) {
            const email = form.querySelector('input[type="email"]').value;
            const password = form.querySelector('input[type="password"]').value;
            
            // Basic client-side validation
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields.');
                return;
            }
            
            // Allow form to submit to server
            console.log('Login form submitted with:', { email, password });
        });
    }
    
    // Handle registration form submission
    if (registerForm) {
        const form = registerForm.querySelector('form');
        const passwordInput = form.querySelector('input[type="password"]');
        
        // Basic password validation
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            if (password.length < 8) {
                this.setCustomValidity('Password must be at least 8 characters long.');
            } else {
                this.setCustomValidity('');
            }
        });
        
        form.addEventListener('submit', function(e) {
            const name = form.querySelector('input[name="customer_name"]').value;
            const email = form.querySelector('input[name="customer_email"]').value;
            const password = passwordInput.value;
            const country = form.querySelector('input[name="customer_country"]').value;
            const city = form.querySelector('input[name="customer_city"]').value;
            const terms = form.querySelector('#terms').checked;
            
            // Basic client-side validation
            if (!name || !email || !password || !country || !city || !terms) {
                e.preventDefault();
                alert('Please fill in all fields and agree to the terms.');
                return;
            }
            
            // Allow form to submit to server
            console.log('Registration form submitted with:', { name, email, password, country, city, terms });
        });
    }
});