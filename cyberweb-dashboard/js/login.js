
'use strict';

const loginForm = document.getElementById('loginForm');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');
const statusMessage = document.getElementById('formStatus');
const submitBtn = document.getElementById('submitBtn');

// Update footer year
for (const id of ['showcaseYear', 'formYear']) {
    const el = document.getElementById(id);

    if (el) {
        el.textContent = new Date().getFullYear();
    }
}

// Show field validation errors
function fieldError(field, message) {
    field.setAttribute(
        'aria-invalid',
        String(Boolean(message))
    );

    const errorElement = document.getElementById(
        field.id + 'Error'
    );

    if (errorElement) {
        errorElement.textContent = message;
    }
}

// Validate login form
function validate() {
    let valid = true;

    const username = emailInput.value.trim();

    const usernameRegex = /^[a-zA-Z0-9_.-]{3,60}$/;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!username) {
        fieldError(
            emailInput,
            'Please enter your username or email.'
        );
        valid = false;
    } else if (
        !usernameRegex.test(username) &&
        !emailRegex.test(username)
    ) {
        fieldError(
            emailInput,
            'Please enter a valid username or email.'
        );
        valid = false;
    } else {
        fieldError(emailInput, '');
    }

    if (!passwordInput.value) {
        fieldError(
            passwordInput,
            'Please enter your password.'
        );
        valid = false;
    } else {
        fieldError(passwordInput, '');
    }

    return valid;
}

// Show / hide password
if (togglePassword) {
    togglePassword.addEventListener('click', () => {

        const visible = passwordInput.type === 'password';

        passwordInput.type = visible ? 'text' : 'password';

        togglePassword.setAttribute(
            'aria-label',
            visible ? 'Hide password' : 'Show password'
        );

        togglePassword.setAttribute(
            'aria-pressed',
            String(visible)
        );
    });
}

// Clear input errors
for (const el of [emailInput, passwordInput]) {
    el.addEventListener('input', () => {
        fieldError(el, '');
        statusMessage.textContent = '';
    });
}

// Submit login form to PHP
loginForm.addEventListener('submit', (event) => {

    if (!validate()) {
        event.preventDefault();

        statusMessage.textContent =
            'Please correct the highlighted fields.';

        return;
    }

    submitBtn.disabled = true;

    statusMessage.textContent = 'Signing in…';

    // Form submits normally to auth/login_process.php
});

// Forgot password panel
const helpPanel = document.getElementById('helpPanel');
const forgotLink = document.getElementById('forgotLink');
const closeHelp = document.getElementById('closeHelp');

if (helpPanel && forgotLink && closeHelp) {

    forgotLink.addEventListener('click', (event) => {
        event.preventDefault();

        helpPanel.hidden = false;

        helpPanel.querySelector('strong').textContent =
            'Forgot your password?';

        helpPanel.querySelector('p').textContent =
            'Please contact your administrator to reset your password.';
    });

    closeHelp.addEventListener('click', () => {
        helpPanel.hidden = true;
    });
}
