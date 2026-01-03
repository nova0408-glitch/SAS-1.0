document.getElementById('loginForm').addEventListener('submit', function(e) {
    const email = this.email.value.trim();
    const password = this.password.value.trim();

    if (email === '' || password === '') {
        alert('Please enter both email and password.');
        e.preventDefault(); // prevent form submission
    }
});