$(document).ready(function() {
    // Initialize Quill rich text editor
    const quill = new Quill('#bioEditor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'color': [] }, { 'background': [] }],
                ['link'],
                ['clean']
            ]
        },
        placeholder: 'Write something about yourself...'
    });

    // Check if user is logged in
    const sessionToken = localStorage.getItem('sessionToken');
    if (!sessionToken) {
        window.location.replace('login.html');
        return;
    }

    // Prevent back button after logout
    window.history.pushState(null, '', window.location.href);
    window.onpopstate = function() {
        const token = localStorage.getItem('sessionToken');
        if (!token) {
            window.location.replace('login.html');
        } else {
            window.history.pushState(null, '', window.location.href);
        }
    };

    // Check session when page becomes visible
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            const token = localStorage.getItem('sessionToken');
            if (!token) {
                window.location.replace('login.html');
            }
        }
    });

    // Display user info from localStorage
    $('#displayUsername').text(localStorage.getItem('username'));
    $('#displayEmail').text(localStorage.getItem('email'));

    // Load profile data
    loadProfile();
    
    // Load account statistics
    loadAccountStats();

    // Handle profile picture upload
    $('#profilePictureInput').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showUploadMessage('Please select a valid image file (JPG, PNG, GIF, or WebP)', 'danger');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showUploadMessage('File size must be less than 5MB', 'danger');
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#profilePicturePreview').attr('src', e.target.result);
        };
        reader.readAsDataURL(file);

        // Upload file
        const formData = new FormData();
        formData.append('profilePicture', file);
        formData.append('sessionToken', sessionToken);

        $.ajax({
            url: '../api/upload_profile_picture.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showUploadMessage(response.message, 'success');
                    $('#removeProfilePicture').show();
                    setTimeout(function() {
                        $('#uploadMessage').html('');
                    }, 3000);
                } else {
                    showUploadMessage(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                showUploadMessage('Upload failed: ' + error, 'danger');
            }
        });
    });

    // Handle profile form submission
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();

        // Get bio content from Quill editor
        const bioContent = quill.root.innerHTML;

        const formData = {
            sessionToken: sessionToken,
            fullname: $('#fullname').val(),
            age: $('#age').val(),
            dob: $('#dob').val(),
            contact: $('#contact').val(),
            address: $('#address').val(),
            bio: bioContent,
            linkedin: $('#linkedin').val(),
            twitter: $('#twitter').val(),
            github: $('#github').val(),
            website: $('#website').val()
        };

        $.ajax({
            url: '../api/update_profile.php',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessage(response.message, 'success');
                } else {
                    if (response.message === 'Invalid session') {
                        logout();
                    } else {
                        showMessage(response.message, 'danger');
                    }
                }
            },
            error: function(xhr, status, error) {
                showMessage('An error occurred: ' + error, 'danger');
            }
        });
    });

    // Toggle password visibility for change password form
    $('#toggleCurrentPassword').on('click', function() {
        togglePasswordVisibility($('#currentPassword'), $(this).find('svg'));
    });

    $('#toggleNewPassword').on('click', function() {
        togglePasswordVisibility($('#newPassword'), $(this).find('svg'));
    });

    $('#toggleConfirmNewPassword').on('click', function() {
        togglePasswordVisibility($('#confirmNewPassword'), $(this).find('svg'));
    });

    // Handle change password form submission
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();

        const newPassword = $('#newPassword').val();
        const confirmNewPassword = $('#confirmNewPassword').val();

        // Validate password match
        if (newPassword !== confirmNewPassword) {
            showPasswordMessage('New passwords do not match!', 'danger');
            return;
        }

        // Validate password length
        if (newPassword.length < 6) {
            showPasswordMessage('Password must be at least 6 characters long!', 'danger');
            return;
        }

        // Validate uppercase letter
        if (!/[A-Z]/.test(newPassword)) {
            showPasswordMessage('Password must contain at least 1 uppercase letter!', 'danger');
            return;
        }

        // Validate number
        if (!/[0-9]/.test(newPassword)) {
            showPasswordMessage('Password must contain at least 1 number!', 'danger');
            return;
        }

        // Validate special character
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(newPassword)) {
            showPasswordMessage('Password must contain at least 1 special character (!@#$%^&*(),.?":{}|<>)!', 'danger');
            return;
        }

        const formData = {
            sessionToken: sessionToken,
            currentPassword: $('#currentPassword').val(),
            newPassword: newPassword
        };

        $.ajax({
            url: '../api/change_password.php',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showPasswordMessage(response.message, 'success');
                    $('#changePasswordForm')[0].reset();
                    setTimeout(function() {
                        $('#passwordMessage').html('');
                    }, 3000);
                } else {
                    if (response.message === 'Invalid session') {
                        logout();
                    } else {
                        showPasswordMessage(response.message, 'danger');
                    }
                }
            },
            error: function(xhr, status, error) {
                showPasswordMessage('An error occurred: ' + error, 'danger');
            }
        });
    });

    // Handle remove profile picture
    $('#removeProfilePicture').on('click', function() {
        if (!confirm('Are you sure you want to remove your profile picture?')) {
            return;
        }

        $.ajax({
            url: '../api/delete_profile_picture.php',
            type: 'POST',
            data: JSON.stringify({ sessionToken: sessionToken }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#profilePicturePreview').attr('src', '../assets/images/default-avatar.png');
                    $('#removeProfilePicture').hide();
                    showUploadMessage(response.message, 'success');
                    setTimeout(function() {
                        $('#uploadMessage').html('');
                    }, 3000);
                } else {
                    showUploadMessage(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                showUploadMessage('Failed to remove picture: ' + error, 'danger');
            }
        });
    });

    // Handle QR Code generation
    let qrcodeInstance = null;
    let currentProfileData = {};

    $(document).on('click', '#generateQRBtn', function(e) {
        e.preventDefault();
        console.log('QR Code button clicked');
        
        // Get current profile data
        $.ajax({
            url: '../api/get_profile.php',
            type: 'POST',
            data: JSON.stringify({ sessionToken: sessionToken }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                console.log('Profile data loaded:', response);
                
                if (response.success) {
                    const profile = response.profile || {};
                    
                    // Store profile data
                    currentProfileData = {
                        username: localStorage.getItem('username'),
                        email: localStorage.getItem('email'),
                        fullname: profile.fullname || 'Not provided',
                        contact: profile.contact || 'Not provided',
                        linkedin: profile.linkedin || '',
                        twitter: profile.twitter || '',
                        github: profile.github || '',
                        website: profile.website || ''
                    };

                    // Create vCard format data
                    let vCardData = `BEGIN:VCARD\nVERSION:3.0\n`;
                    vCardData += `FN:${currentProfileData.fullname}\n`;
                    vCardData += `EMAIL:${currentProfileData.email}\n`;
                    if (currentProfileData.contact !== 'Not provided') {
                        vCardData += `TEL:${currentProfileData.contact}\n`;
                    }
                    if (currentProfileData.linkedin) {
                        vCardData += `URL;type=LinkedIn:${currentProfileData.linkedin}\n`;
                    }
                    if (currentProfileData.website) {
                        vCardData += `URL:${currentProfileData.website}\n`;
                    }
                    vCardData += `END:VCARD`;

                    console.log('Clearing previous QR code...');
                    // Clear previous QR code
                    $('#qrcode').empty();
                    
                    console.log('Generating new QR code...');
                    // Generate new QR code
                    qrcodeInstance = new QRCode(document.getElementById('qrcode'), {
                        text: vCardData,
                        width: 256,
                        height: 256,
                        colorDark: '#000000',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });

                    // Display profile info
                    let profileHTML = `
                        <p class="mb-1"><strong>Name:</strong> ${currentProfileData.fullname}</p>
                        <p class="mb-1"><strong>Email:</strong> ${currentProfileData.email}</p>
                        <p class="mb-1"><strong>Contact:</strong> ${currentProfileData.contact}</p>
                    `;
                    
                    if (currentProfileData.linkedin) {
                        profileHTML += `<p class="mb-1"><strong>LinkedIn:</strong> <a href="${currentProfileData.linkedin}" target="_blank">View Profile</a></p>`;
                    }
                    if (currentProfileData.github) {
                        profileHTML += `<p class="mb-1"><strong>GitHub:</strong> <a href="${currentProfileData.github}" target="_blank">View Profile</a></p>`;
                    }
                    if (currentProfileData.website) {
                        profileHTML += `<p class="mb-1"><strong>Website:</strong> <a href="${currentProfileData.website}" target="_blank">Visit</a></p>`;
                    }

                    $('#qrProfileData').html(profileHTML);

                    console.log('Opening modal...');
                    // Show modal using Bootstrap 5 API
                    try {
                        const modalElement = document.getElementById('qrCodeModal');
                        if (modalElement) {
                            const qrModal = new bootstrap.Modal(modalElement);
                            qrModal.show();
                            console.log('Modal opened successfully');
                        } else {
                            console.error('Modal element not found');
                        }
                    } catch (error) {
                        console.error('Error opening modal:', error);
                        // Fallback to jQuery method
                        $('#qrCodeModal').modal('show');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('QR Code AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                alert('Failed to load profile data for QR code generation');
            }
        });
    });

    // Handle QR Code download
    $('#downloadQRBtn').on('click', function() {
        const qrCanvas = $('#qrcode canvas')[0];
        if (qrCanvas) {
            const link = document.createElement('a');
            link.download = `profile-qr-${localStorage.getItem('username')}.png`;
            link.href = qrCanvas.toDataURL();
            link.click();
        } else {
            alert('QR Code not generated yet');
        }
    });

    // Handle Profile Preview
    $(document).on('click', '#previewProfileBtn', function(e) {
        e.preventDefault();
        console.log('Profile Preview button clicked');
        
        // Get current profile data
        $.ajax({
            url: '../api/get_profile.php',
            type: 'POST',
            data: JSON.stringify({ sessionToken: sessionToken }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                console.log('Preview data loaded:', response);
                
                if (response.success) {
                    const profile = response.profile || {};
                    
                    // Set basic info
                    $('#previewFullname').text(profile.fullname || localStorage.getItem('username') || 'User');
                    $('#previewEmail').text(localStorage.getItem('email') || '');
                    
                    // Set profile picture
                    if (profile.profile_picture) {
                        $('#previewProfilePicture').attr('src', '../' + profile.profile_picture);
                    } else {
                        $('#previewProfilePicture').attr('src', '../assets/images/default-avatar.png');
                    }
                    
                    // Show/hide bio section
                    if (profile.bio && profile.bio.trim() !== '') {
                        $('#previewBio').html(profile.bio);
                        $('#previewBioSection').show();
                    } else {
                        $('#previewBioSection').hide();
                    }
                    
                    // Show/hide contact section
                    if (profile.contact && profile.contact.trim() !== '') {
                        $('#previewContact').text(profile.contact);
                        $('#previewContactSection').show();
                    } else {
                        $('#previewContactSection').hide();
                    }
                    
                    // Show/hide age section
                    if (profile.age) {
                        let ageText = profile.age + ' years old';
                        if (profile.dob) {
                            ageText += ' (Born: ' + new Date(profile.dob).toLocaleDateString() + ')';
                        }
                        $('#previewAge').text(ageText);
                        $('#previewAgeSection').show();
                    } else {
                        $('#previewAgeSection').hide();
                    }
                    
                    // Show/hide address section
                    if (profile.address && profile.address.trim() !== '') {
                        $('#previewAddress').text(profile.address);
                        $('#previewAddressSection').show();
                    } else {
                        $('#previewAddressSection').hide();
                    }
                    
                    // Build social links
                    let socialHTML = '';
                    let hasSocial = false;
                    
                    if (profile.linkedin && profile.linkedin.trim() !== '') {
                        socialHTML += `
                            <a href="${profile.linkedin}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-linkedin me-1" viewBox="0 0 16 16">
                                    <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854V1.146zm4.943 12.248V6.169H2.542v7.225h2.401zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248-.822 0-1.359.54-1.359 1.248 0 .694.521 1.248 1.327 1.248h.016zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016a5.54 5.54 0 0 1 .016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225h2.4z"/>
                                </svg>
                                LinkedIn
                            </a>
                        `;
                        hasSocial = true;
                    }
                    
                    if (profile.twitter && profile.twitter.trim() !== '') {
                        socialHTML += `
                            <a href="${profile.twitter}" target="_blank" class="btn btn-sm btn-outline-info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter me-1" viewBox="0 0 16 16">
                                    <path d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334 0-.14 0-.282-.006-.422A6.685 6.685 0 0 0 16 3.542a6.658 6.658 0 0 1-1.889.518 3.301 3.301 0 0 0 1.447-1.817 6.533 6.533 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.325 9.325 0 0 1-6.767-3.429 3.289 3.289 0 0 0 1.018 4.382A3.323 3.323 0 0 1 .64 6.575v.045a3.288 3.288 0 0 0 2.632 3.218 3.203 3.203 0 0 1-.865.115 3.23 3.23 0 0 1-.614-.057 3.283 3.283 0 0 0 3.067 2.277A6.588 6.588 0 0 1 .78 13.58a6.32 6.32 0 0 1-.78-.045A9.344 9.344 0 0 0 5.026 15z"/>
                                </svg>
                                Twitter
                            </a>
                        `;
                        hasSocial = true;
                    }
                    
                    if (profile.github && profile.github.trim() !== '') {
                        socialHTML += `
                            <a href="${profile.github}" target="_blank" class="btn btn-sm btn-outline-dark">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-github me-1" viewBox="0 0 16 16">
                                    <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.012 8.012 0 0 0 16 8c0-4.42-3.58-8-8-8z"/>
                                </svg>
                                GitHub
                            </a>
                        `;
                        hasSocial = true;
                    }
                    
                    if (profile.website && profile.website.trim() !== '') {
                        socialHTML += `
                            <a href="${profile.website}" target="_blank" class="btn btn-sm btn-outline-success">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-globe me-1" viewBox="0 0 16 16">
                                    <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm7.5-6.923c-.67.204-1.335.82-1.887 1.855A7.97 7.97 0 0 0 5.145 4H7.5V1.077zM4.09 4a9.267 9.267 0 0 1 .64-1.539 6.7 6.7 0 0 1 .597-.933A7.025 7.025 0 0 0 2.255 4H4.09zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a6.958 6.958 0 0 0-.656 2.5h2.49zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5H4.847zM8.5 5v2.5h2.99a12.495 12.495 0 0 0-.337-2.5H8.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5H4.51zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5H8.5zM5.145 12c.138.386.295.744.468 1.068.552 1.035 1.218 1.65 1.887 1.855V12H5.145zm.182 2.472a6.696 6.696 0 0 1-.597-.933A9.268 9.268 0 0 1 4.09 12H2.255a7.024 7.024 0 0 0 3.072 2.472zM3.82 11a13.652 13.652 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5H3.82zm6.853 3.472A7.024 7.024 0 0 0 13.745 12H11.91a9.27 9.27 0 0 1-.64 1.539 6.688 6.688 0 0 1-.597.933zM8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855.173-.324.33-.682.468-1.068H8.5zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.65 13.65 0 0 1-.312 2.5zm2.802-3.5a6.959 6.959 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5h2.49zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7.024 7.024 0 0 0-3.072-2.472c.218.284.418.598.597.933zM10.855 4a7.966 7.966 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4h2.355z"/>
                                </svg>
                                Website
                            </a>
                        `;
                        hasSocial = true;
                    }
                    
                    if (hasSocial) {
                        $('#previewSocialLinks').html(socialHTML);
                        $('#previewSocialSection').show();
                    } else {
                        $('#previewSocialSection').hide();
                    }
                    
                    // Show modal
                    try {
                        const modalElement = document.getElementById('profilePreviewModal');
                        if (modalElement) {
                            const previewModal = new bootstrap.Modal(modalElement);
                            previewModal.show();
                            console.log('Preview modal opened successfully');
                        } else {
                            console.error('Preview modal element not found');
                        }
                    } catch (error) {
                        console.error('Error opening preview modal:', error);
                        $('#profilePreviewModal').modal('show');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Profile Preview Error:', error);
                alert('Failed to load profile data for preview');
            }
        });
    });

    // Handle logout
    $('#logoutBtn').on('click', function() {
        logout();
    });

    // Handle delete account button click
    $('#deleteAccountBtn').on('click', function() {
        $('#deleteAccountModal').modal('show');
    });

    // Enable/disable confirm delete button based on input
    $('#confirmDeleteInput').on('input', function() {
        const inputValue = $(this).val();
        if (inputValue === 'DELETE') {
            $('#confirmDeleteBtn').prop('disabled', false);
        } else {
            $('#confirmDeleteBtn').prop('disabled', true);
        }
    });

    // Handle confirm delete button
    $('#confirmDeleteBtn').on('click', function() {
        const confirmText = $('#confirmDeleteInput').val();
        
        if (confirmText !== 'DELETE') {
            alert('Please type DELETE to confirm account deletion.');
            return;
        }

        // Disable button and show loading state
        $(this).prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Deleting...'
        );

        $.ajax({
            url: '../api/delete_account.php',
            type: 'POST',
            data: JSON.stringify({ sessionToken: sessionToken }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Clear all session data
                    localStorage.clear();
                    
                    // Close modal and show success message
                    $('#deleteAccountModal').modal('hide');
                    
                    // Redirect to registration page with success message
                    alert(response.message);
                    window.location.replace('register.html');
                } else {
                    alert('Error: ' + response.message);
                    $('#confirmDeleteBtn').prop('disabled', false).html(
                        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill me-2" viewBox="0 0 16 16"><path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5Zm-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5ZM4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5Z"/></svg>I understand, delete my account'
                    );
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred while deleting your account. Please try again.');
                $('#confirmDeleteBtn').prop('disabled', false).html(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill me-2" viewBox="0 0 16 16"><path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5Zm-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5ZM4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5Z"/></svg>I understand, delete my account'
                );
            }
        });
    });

    function loadProfile() {
        $.ajax({
            url: '../api/get_profile.php',
            type: 'POST',
            data: JSON.stringify({ sessionToken: sessionToken }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.profile) {
                    $('#fullname').val(response.profile.fullname || '');
                    $('#age').val(response.profile.age || '');
                    $('#dob').val(response.profile.dob || '');
                    $('#contact').val(response.profile.contact || '');
                    $('#address').val(response.profile.address || '');
                    $('#linkedin').val(response.profile.linkedin || '');
                    $('#twitter').val(response.profile.twitter || '');
                    $('#github').val(response.profile.github || '');
                    $('#website').val(response.profile.website || '');
                    
                    // Load bio into Quill editor
                    if (response.profile.bio) {
                        quill.root.innerHTML = response.profile.bio;
                    } else {
                        quill.setText('');
                    }
                    
                    // Load profile picture
                    if (response.profile.profile_picture) {
                        $('#profilePicturePreview').attr('src', '../' + response.profile.profile_picture);
                        $('#removeProfilePicture').show();
                    } else {
                        $('#profilePicturePreview').attr('src', '../assets/images/default-avatar.png');
                        $('#removeProfilePicture').hide();
                    }
                } else if (response.success && !response.profile && response.defaultGithub) {
                    // New user with no profile yet, but logged in via GitHub
                    $('#github').val(response.defaultGithub);
                } else if (response.message === 'Invalid session') {
                    logout();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading profile:', error);
            }
        });
    }

    function loadAccountStats() {
        $.ajax({
            url: '../api/get_account_stats.php',
            type: 'POST',
            data: JSON.stringify({ sessionToken: sessionToken }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.stats) {
                    // Format and display created date
                    const createdDate = new Date(response.stats.created_at);
                    $('#createdAt').text(formatDateTime(createdDate));
                    
                    // Format and display last login
                    if (response.stats.last_login) {
                        const lastLoginDate = new Date(response.stats.last_login);
                        $('#lastLogin').text(formatDateTime(lastLoginDate));
                    } else {
                        $('#lastLogin').text('First login');
                    }
                } else if (response.message === 'Invalid session') {
                    logout();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading account stats:', error);
            }
        });
    }

    function formatDateTime(date) {
        const options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString('en-US', options);
    }

    function logout() {
        const sessionToken = localStorage.getItem('sessionToken');
        
        // Call logout API to clear Redis session
        $.ajax({
            url: '../api/logout.php',
            type: 'POST',
            data: JSON.stringify({ sessionToken: sessionToken }),
            contentType: 'application/json',
            dataType: 'json',
            complete: function() {
                // Clear localStorage
                localStorage.removeItem('sessionToken');
                localStorage.removeItem('userId');
                localStorage.removeItem('username');
                localStorage.removeItem('email');
                localStorage.removeItem('created_at');
                localStorage.removeItem('last_login');
                
                // Clear browser history and redirect to login
                window.location.replace('login.html');
            }
        });
    }

    function showMessage(message, type) {
        $('#message').html(
            '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>'
        );
    }

    function showUploadMessage(message, type) {
        $('#uploadMessage').html(
            '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>'
        );
    }

    function showPasswordMessage(message, type) {
        $('#passwordMessage').html(
            '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>'
        );
    }

    function togglePasswordVisibility(field, icon) {
        if (field.attr('type') === 'password') {
            field.attr('type', 'text');
            icon.html('<path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/>');
        } else {
            field.attr('type', 'password');
            icon.html('<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>');
        }
    }
});
