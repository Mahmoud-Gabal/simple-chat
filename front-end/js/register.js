const regParams = new URLSearchParams(window.location.search);
const regError = regParams.get('error');
const regErrorBox = document.getElementById('errorBox');

if (regError && regErrorBox) {
    regErrorBox.textContent = regError;
    regErrorBox.style.display = 'block';
}

