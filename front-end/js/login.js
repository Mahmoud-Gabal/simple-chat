const params = new URLSearchParams(window.location.search);
const error = params.get('error');
const justRegistered = params.get('registered');
const errorBox = document.getElementById('errorBox');
const successBox = document.getElementById('successBox');

if (error && errorBox) {
    errorBox.textContent = error;
    errorBox.style.display = 'block';
}

if (justRegistered && successBox) {
    successBox.textContent = 'Account created. You can now log in.';
    successBox.style.display = 'block';
}

