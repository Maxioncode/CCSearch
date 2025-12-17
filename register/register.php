<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CCSearch Registration</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="register.css">
</head>

<body>
  <div class="container">
    <div class="page-bg"></div>

    <!-- LEFT SECTION -->
    <div class="left-section">
      <div class="back-btn" onclick="window.location.href='/login/login.html'">
        <span>&larr;</span>
      </div>

      <div class="form-box">
        <img src="../image/Icon.png" alt="Logo" class="logo">
        <h2>Create your CCSearch Account</h2>

        <!-- FIXED FORM -->
        <form id="registerForm" action="registration.php" method="POST">
  <div class="form-grid">
    <!-- Left Column: Personal Information -->
    <div class="col">
      <input type="text" name="firstName" id="firstName" placeholder="First Name" required maxlength="50" class="form-field">
      <input type="text" name="lastName" id="lastName" placeholder="Last Name" required maxlength="50" class="form-field">
      <input type="text" name="contactNumber" id="contactNumber" placeholder="Contact Number" required maxlength="15" pattern="[0-9+\-\s]+" title="Please enter a valid phone number" class="form-field">
      <input type="email" name="emailAddress" id="emailAddress" placeholder="Email Address" required maxlength="100" class="form-field">
      <input type="text" name="currentAddress" id="currentAddress" placeholder="Current Address" required maxlength="255" class="form-field">
    </div>

    <!-- Right Column: Account Information -->
    <div class="col">
      <input type="text" name="department" id="department" placeholder="Department" required maxlength="100" class="form-field">
      <input type="text" name="studentID" id="studentID" placeholder="Student ID" required maxlength="20" class="form-field">
      <div class="password-field">
        <input type="password" name="password" id="password" placeholder="Password" required minlength="6" class="form-field">
        <i class="fa-solid fa-eye toggle" onclick="togglePassword('password', this)"></i>
      </div>
      <div class="password-field">
        <input type="password" name="confirmPassword" id="confirm" placeholder="Confirm Password" required minlength="6" class="form-field">
        <i class="fa-solid fa-eye toggle" onclick="togglePassword('confirm', this)"></i>
      </div>
    </div>
  </div>

          <button type="submit">Register</button>

          <p class="redirect-text">
            Already have an account? <a href="../login/login.php">Login</a>
          </p>
        </form>
      </div>
    </div>

    <!-- RIGHT SECTION -->
    <div class="right-section">
      <div class="overlay"></div>
      <div class="quote">
        <h3>Join CCSearch today<br>and share credible knowledge.</h3>
      </div>
    </div>
  </div>

  <!-- Registration Modal (Outside container for proper overlay) -->
  <div id="registrationModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <span class="close-modal">&times;</span>
      </div>
      <div class="modal-body">
        <div class="modal-icon">
          <i id="modalIcon" class="fas"></i>
        </div>
        <h3 id="modalTitle">Registration Status</h3>
        <p id="modalMessage"></p>
      </div>
      <div class="modal-footer">
        <button id="modalButton" class="modal-btn">OK</button>
      </div>
    </div>
  </div>

  <script>
    // Modal functionality
    const modal = document.getElementById('registrationModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalIcon = document.getElementById('modalIcon');
    const modalMessage = document.getElementById('modalMessage');
    const modalButton = document.getElementById('modalButton');
    const closeModal = document.querySelector('.close-modal');

    // Ensure modal is hidden by default
    if (modal) {
      modal.style.display = 'none';
    }

    function showModal(status, message) {
      // Set modal type and content
      modal.className = 'modal ' + status;
      modalTitle.textContent = status === 'success' ? 'Success!' : 'Error!';
      modalMessage.textContent = message;
      modalIcon.className = 'fas ' + (status === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle');

      // Show modal with flexbox for centering
      modal.style.display = 'flex';
      // Force reflow to ensure transition works
      modal.offsetHeight;
      modal.style.opacity = '1';

      // Set button text and action
      if (status === 'success') {
        modalButton.textContent = 'Go to Login';
        modalButton.onclick = () => {
          window.location.href = '../login/login.php';
        };
      } else {
        modalButton.textContent = 'Try Again';
        modalButton.onclick = hideModal;
      }
    }

    function hideModal() {
      modal.style.opacity = '0';
      // Wait for transition to complete before hiding
      setTimeout(() => {
        modal.style.display = 'none';
      }, 300);
    }

    // Event listeners
    closeModal.onclick = hideModal;

    // Close modal when clicking outside
    window.onclick = function(event) {
      if (event.target === modal) {
        hideModal();
      }
    }

    // Password toggle functionality
    function togglePassword(id, el) {
      const input = document.getElementById(id);
      if (input.type === "password") {
        input.type = "text";
        el.classList.replace("fa-eye", "fa-eye-slash");
      } else {
        input.type = "password";
        el.classList.replace("fa-eye-slash", "fa-eye");
      }
    }

    // AJAX form submission - Only triggered by Register button click
    document.getElementById("registerForm").addEventListener("submit", function(e) {
      e.preventDefault();

      // Ensure modal is hidden before starting
      if (modal) {
        modal.style.display = 'none';
      }

      const formData = new FormData(this);

      // Show loading state
      const submitButton = this.querySelector('button[type="submit"]');
      const originalText = submitButton.textContent;
      submitButton.textContent = 'Registering...';
      submitButton.disabled = true;

      fetch('registration.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        showModal(data.status, data.message);
      })
      .catch(error => {
        console.error('Error:', error);
        showModal('error', 'An error occurred. Please try again.');
      })
      .finally(() => {
        // Reset button state
        submitButton.textContent = originalText;
        submitButton.disabled = false;
      });
    });
  </script>
  </script>
</body>

</html>