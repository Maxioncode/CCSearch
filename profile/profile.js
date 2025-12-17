// Check if we're in public view and disable update button
document.addEventListener('DOMContentLoaded', function () {
  console.log('DOM loaded, checking public view status');
  console.log('Body classes:', document.body.className);

  // Log current profile image source
  const profilePic = document.getElementById('profilePic');
  if (profilePic) {
    console.log('Current profile image src:', profilePic.src);
  }

  // Check if URL contains public=1
  const urlParams = new URLSearchParams(window.location.search);
  const isPublicView = urlParams.get('public') === '1';

  if (isPublicView) {
    console.log('Public view detected, adding class and disabling update button');
    document.body.classList.add('public-view');

    const updateButton = document.getElementById("updateProfile");
    if (updateButton) {
      updateButton.disabled = true;
      updateButton.textContent = 'Updates Disabled (Public View)';
      console.log('Update button disabled');
    } else {
      console.log('Update button not found');
    }
  } else {
    console.log('Private view detected');
  }

  // Check for URL hash to activate specific tab
  const hash = window.location.hash.substring(1); // Remove the '#'
  if (hash) {
    console.log('URL hash detected:', hash);
    const targetTab = document.querySelector(`[data-tab="${hash}"]`);
    if (targetTab) {
      console.log('Activating tab:', hash);
      // Simulate click on the target tab
      targetTab.click();
    }
  }
});

// Tab switch
const tabs = document.querySelectorAll(".tab");
const contents = document.querySelectorAll(".tab-content");

tabs.forEach(tab => {
  tab.addEventListener("click", () => {
    // Remove active tab highlight
    tabs.forEach(t => t.classList.remove("active"));
    tab.classList.add("active");

    // Hide all content & fade out
    contents.forEach(c => {
      c.classList.add("hidden");
      c.style.opacity = 0;
    });

    // Show selected panel
    const activeContent = document.getElementById(tab.dataset.tab);
    activeContent.classList.remove("hidden");

    // Fade-in effect
    setTimeout(() => {
      activeContent.style.opacity = 1;
    }, 50);
  });
});

// Button action for public/private view toggle is handled inline in HTML
// The onclick attribute in the HTML handles the navigation

// Profile update functionality
document.addEventListener('DOMContentLoaded', function () {
  const updateButton = document.getElementById("updateProfile");
  if (updateButton) {
    updateButton.addEventListener("click", function (e) {
      e.preventDefault(); // Prevent any default form submission

      console.log('Update button clicked'); // Debug log

      // Check if we're in public view - if so, don't allow updates
      if (document.body.classList.contains('public-view')) {
        showNotification('Cannot update profile in public view', 'error');
        return;
      }

      // Get form data using field names
      const firstName = document.querySelector('input[name="firstName"]')?.value?.trim() || '';
      const lastName = document.querySelector('input[name="lastName"]')?.value?.trim() || '';
      const contactNumber = document.querySelector('input[name="contactNumber"]')?.value?.trim() || '';
      const emailAddress = document.querySelector('input[name="emailAddress"]')?.value?.trim() || '';
      const currentAddress = document.querySelector('input[name="currentAddress"]')?.value?.trim() || '';
      const department = document.querySelector('input[name="department"]')?.value?.trim() || '';

      console.log('Form data:', { firstName, lastName, contactNumber, emailAddress, currentAddress, department }); // Debug log

      // Validate required fields
      if (!firstName || !lastName || !contactNumber || !emailAddress || !currentAddress || !department) {
        showNotification('Please fill in all required fields', 'error');
        return;
      }

      // Validate email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(emailAddress)) {
        showNotification('Please enter a valid email address', 'error');
        return;
      }

      // Validate contact number (11 digits)
      if (!/^[0-9]{11}$/.test(contactNumber)) {
        showNotification('Contact number must be exactly 11 digits', 'error');
        return;
      }

      // Create form data
      const formData = new FormData();
      formData.append('firstName', firstName);
      formData.append('lastName', lastName);
      formData.append('contactNumber', contactNumber);
      formData.append('emailAddress', emailAddress);
      formData.append('currentAddress', currentAddress);
      formData.append('department', department);

      // Handle profile image upload (optional)
      const fileInput = document.getElementById('fileInput');
      if (fileInput && fileInput.files && fileInput.files[0]) {
        const file = fileInput.files[0];

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
          showNotification('Invalid file type. Only JPG, PNG, and GIF images are allowed.', 'error');
          return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          showNotification('File size too large. Maximum size is 5MB.', 'error');
          return;
        }

        formData.append('profileImage', file);
        console.log('Profile image included in form data:', file.name);
      }

      // Show loading state
      const originalText = updateButton.textContent;
      updateButton.textContent = 'Updating...';
      updateButton.disabled = true;

      console.log('Sending request to profile_update.php'); // Debug log

      // Send update request
      fetch('profile_update.php', {
        method: 'POST',
        body: formData
      })
        .then(response => {
          console.log('Response received:', response); // Debug log
          return response.json();
        })
        .then(data => {
          console.log('Response data:', data); // Debug log
          showNotification(data.message, data.status);
          if (data.status === 'success') {
            // If profile image was updated, update the image source before reload
            if (data.profile_image) {
              console.log('Updating profile image to:', '../' + data.profile_image);
              const profilePic = document.getElementById('profilePic');
              if (profilePic) {
                profilePic.src = '../' + data.profile_image;
                profilePic.style.animation = "fadeIn 0.6s ease";
                console.log('Profile image updated successfully');
              } else {
                console.log('Profile picture element not found');
              }
            }
            // Refresh the page to show updated data
            setTimeout(() => {
              window.location.reload();
            }, 1500);
          }
        })
        .catch(error => {
          console.error('Fetch error:', error);
          showNotification('An error occurred. Please try again.', 'error');
          // Clear file input on error
          const fileInput = document.getElementById('fileInput');
          if (fileInput) {
            fileInput.value = '';
          }
        })
        .finally(() => {
          // Reset button state
          updateButton.textContent = originalText;
          updateButton.disabled = false;
        });
    });
  } else {
    console.log('Update button not found'); // Debug log
  }
});

// Simple notification function
function showNotification(message, type) {
  console.log('Showing notification:', message, type);

  // Remove existing notification
  const existingNotification = document.querySelector('.notification');
  if (existingNotification) {
    existingNotification.remove();
  }

  // Create notification element
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  notification.textContent = message;
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 10000;
    max-width: 400px;
    background-color: ${type === 'success' ? '#28a745' : '#dc3545'};
  `;

  document.body.appendChild(notification);

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.parentNode.removeChild(notification);
    }
  }, 5000);
}

// Profile image upload preview
const fileInput = document.getElementById("fileInput");
const profilePic = document.getElementById("profilePic");

if (fileInput && profilePic) {
  fileInput.addEventListener("change", (event) => {
    const file = event.target.files[0];
    if (file) {
      // Validate file type
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
      if (!allowedTypes.includes(file.type)) {
        showNotification('Invalid file type. Only JPG, PNG, and GIF images are allowed.', 'error');
        // Clear the file input
        fileInput.value = '';
        return;
      }

      // Validate file size (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        showNotification('File size too large. Maximum size is 5MB.', 'error');
        // Clear the file input
        fileInput.value = '';
        return;
      }

      // Additional validation: check if it's actually an image
      if (!file.type.startsWith('image/')) {
        showNotification('Please select a valid image file.', 'error');
        fileInput.value = '';
        return;
      }

      // Show preview
      const reader = new FileReader();
      reader.onload = (e) => {
        profilePic.src = e.target.result;
        profilePic.style.animation = "fadeIn 0.6s ease";
        showNotification('Image selected. Click "Update Profile" to save changes.', 'success');
      };
      reader.readAsDataURL(file);
    }
  });
}

// Smooth transitions for tab content
contents.forEach(content => {
  content.style.transition = "opacity 0.3s ease";
});

// Detect page show from back/forward cache
window.addEventListener("pageshow", function (event) {
  if (event.persisted) {
    window.location.reload(0);
  }
});

// Account Settings Functionality
document.addEventListener('DOMContentLoaded', function () {
  // Modal functionality
  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'block';
      document.body.style.overflow = 'hidden';
    }
  }

  function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'none';
      document.body.style.overflow = 'auto';
    }
  }

  // Close modal when clicking outside or on close button
  document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal')) {
      const modalId = e.target.id;
      closeModal(modalId);
    } else if (e.target.classList.contains('close-modal')) {
      const modalId = e.target.getAttribute('data-modal');
      closeModal(modalId);
    } else if (e.target.classList.contains('cancel-btn')) {
      const modalId = e.target.getAttribute('data-modal');
      closeModal(modalId);
    }
  });

  // Account Settings Buttons
  const changePasswordBtn = document.getElementById('changePasswordBtn');
  const deleteAccountBtn = document.getElementById('deleteAccountBtn');
  const themeBtn = document.getElementById('themeBtn');

  if (changePasswordBtn) {
    changePasswordBtn.addEventListener('click', () => openModal('changePasswordModal'));
  }

  if (deleteAccountBtn) {
    deleteAccountBtn.addEventListener('click', () => openModal('deleteAccountModal'));
  }

  if (themeBtn) {
    themeBtn.addEventListener('click', () => {
      openModal('themeModal');
      loadCurrentTheme();
    });
  }

  // Change Password Functionality
  const changePasswordForm = document.getElementById('changePasswordForm');
  const newPasswordInput = document.getElementById('newPassword');
  const confirmPasswordInput = document.getElementById('confirmNewPassword');

  function validatePassword() {
    const password = newPasswordInput.value;
    const lengthCheck = document.getElementById('lengthCheck');
    const uppercaseCheck = document.getElementById('uppercaseCheck');
    const numberCheck = document.getElementById('numberCheck');
    const matchCheck = document.getElementById('matchCheck');
    const confirmPassword = confirmPasswordInput.value;

    // Check length
    const hasLength = password.length >= 8;
    lengthCheck.className = hasLength ? 'validation-item valid' : 'validation-item invalid';

    // Check uppercase
    const hasUppercase = /[A-Z]/.test(password);
    uppercaseCheck.className = hasUppercase ? 'validation-item valid' : 'validation-item invalid';

    // Check number
    const hasNumber = /\d/.test(password);
    numberCheck.className = hasNumber ? 'validation-item valid' : 'validation-item invalid';

    // Check match
    const matches = password === confirmPassword && password.length > 0;
    matchCheck.className = matches ? 'validation-item valid' : 'validation-item invalid';

    return hasLength && hasUppercase && hasNumber && matches;
  }

  if (newPasswordInput) {
    newPasswordInput.addEventListener('input', function () {
      document.getElementById('passwordValidation').style.display = 'block';
      validatePassword();
    });
  }

  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', validatePassword);
  }

  if (changePasswordForm) {
    changePasswordForm.addEventListener('submit', function (e) {
      e.preventDefault();

      if (!validatePassword()) {
        showNotification('Please ensure all password requirements are met.', 'error');
        return;
      }

      const formData = new FormData(this);

      fetch('account_actions.php?action=change_password', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            showNotification(data.message, 'success');
            closeModal('changePasswordModal');
            changePasswordForm.reset();
            document.getElementById('passwordValidation').style.display = 'none';
          } else {
            showNotification(data.message, 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showNotification('An error occurred. Please try again.', 'error');
        });
    });
  }

  // Delete Account Functionality
  const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  const deleteConfirmationInput = document.getElementById('deleteConfirmation');

  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', function () {
      const confirmation = deleteConfirmationInput.value.trim();

      if (confirmation !== 'DELETE') {
        showNotification('Please type "DELETE" to confirm account deletion.', 'error');
        return;
      }

      if (confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.')) {
        fetch('account_actions.php?action=delete_account', {
          method: 'POST'
        })
          .then(response => response.json())
          .then(data => {
            if (data.status === 'success') {
              showNotification('Account deleted successfully. Redirecting...', 'success');
              setTimeout(() => {
                window.location.href = '../login/login.php';
              }, 2000);
            } else {
              showNotification(data.message, 'error');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
          });
      }
    });
  }

  // Theme Selection Functionality
  function loadCurrentTheme() {
    const currentTheme = window.themeManager ? window.themeManager.getCurrentTheme() : 'light';
    document.querySelector(`input[name="theme"][value="${currentTheme}"]`).checked = true;
  }

  // Theme option selection
  document.querySelectorAll('.theme-option').forEach(option => {
    option.addEventListener('click', function () {
      const theme = this.getAttribute('data-theme');
      document.querySelector(`input[name="theme"][value="${theme}"]`).checked = true;

      // Update selected state
      document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('selected'));
      this.classList.add('selected');
    });
  });

  const applyThemeBtn = document.getElementById('applyThemeBtn');
  if (applyThemeBtn) {
    applyThemeBtn.addEventListener('click', function () {
      const selectedTheme = document.querySelector('input[name="theme"]:checked');
      if (selectedTheme) {
        const theme = selectedTheme.value;
        // Use global theme manager
        if (window.themeManager) {
          window.themeManager.applyTheme(theme);
        }
        closeModal('themeModal');
        showNotification(`Theme changed to ${theme === 'dark' ? 'Dark' : 'Light'} Mode`, 'success');
      } else {
        showNotification('Please select a theme.', 'error');
      }
    });
  }

  // Load theme on page load (theme manager handles this globally)
  loadCurrentTheme();
});
