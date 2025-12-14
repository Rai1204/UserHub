$(document).ready(function() {
    // Check if user is already logged in
    const sessionToken = localStorage.getItem('sessionToken');
    if (sessionToken) {
        // Validate session token with server before redirecting
        $.ajax({
            url: '../api/get_profile.php',
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + sessionToken
            },
            success: function(response) {
                if (response.success) {
                    // Valid session, redirect to profile
                    window.location.replace('profile.html');
                } else {
                    // Invalid session, clear localStorage
                    localStorage.clear();
                }
            },
            error: function() {
                // Invalid session, clear localStorage
                localStorage.clear();
            }
        });
    }

    // Clear form fields on page load
    $('#loginForm')[0].reset();

    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        const passwordField = $('#password');
        const icon = $(this).find('svg');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.html('<path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708"/>'); // eye-slash icon
        } else {
            passwordField.attr('type', 'password');
            icon.html('<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>'); // eye icon
        }
    });

    $('#loginForm').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            email: $('#email').val(),
            password: $('#password').val()
        };

        $.ajax({
            url: '../api/login.php',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Store session token and user data in localStorage
                    localStorage.setItem('sessionToken', response.sessionToken);
                    localStorage.setItem('userId', response.userId);
                    localStorage.setItem('username', response.username);
                    localStorage.setItem('email', response.email);
                    localStorage.setItem('created_at', response.created_at || '');
                    localStorage.setItem('last_login', response.last_login || '');
                    
                    showMessage(response.message, 'success');
                    setTimeout(function() {
                        window.location.replace('profile.html');
                    }, 1000);
                } else {
                    showMessage(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                showMessage('An error occurred: ' + error, 'danger');
            }
        });
    });

    // GitHub Login
    $('#githubLoginBtn').on('click', function() {
        const clientId = 'Ov23lix7T3YzZyPrmdvX';
        const redirectUri = encodeURIComponent('http://localhost/user-management/api/github_callback.php');
        const scope = 'read:user user:email';
        
        const githubAuthUrl = `https://github.com/login/oauth/authorize?client_id=${clientId}&redirect_uri=${redirectUri}&scope=${scope}`;
        
        window.location.href = githubAuthUrl;
    });

    function showMessage(message, type) {
        $('#message').html(
            '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>'
        );
    }
});
