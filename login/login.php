<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CCSearch Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="login.css">
</head>

<body>
  <div class="container">
    <div class="page-bg"></div>

    <!-- LEFT SIDE -->
    <div class="left-section">
      <div class="back-btn" onclick="window.location.href='../landing/landing.php'">
        <span>&larr;</span>
      </div>

      <div class="form-box">
        <img src="../image/Icon.png" alt="Logo" class="logo">
        <h2>Login to your CCSearch Account</h2>

        <!-- Login Form -->
        <form id="loginForm" action="login_auth.php" method="POST">
          <input type="text" name="studentID" id="studentId" placeholder="Student ID" required>

          <div class="password-field">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <i class="fa-solid fa-eye toggle" onclick="togglePassword('password', this)"></i>
          </div>

          <button type="submit">Login</button>


          <p class="redirect-text">
            Don’t have an account?
            <a href="../register/register.php">Sign up</a>
          </p>
        </form>
      </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right-section">
      <div class="overlay"></div>
      <div class="quote">
        <p>Share credible knowledge.<br>Search reliable sources.</p>
      </div>
    </div>
  </div>

  <!-- Login Modal (Outside container for proper overlay) -->
  <div id="loginModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <span class="close-modal">&times;</span>
      </div>
      <div class="modal-body">
        <div class="modal-icon">
          <i id="modalIcon" class="fas"></i>
        </div>
        <h3 id="modalTitle">Login Status</h3>
        <p id="modalMessage"></p>
      </div>
      <div class="modal-footer">
        <button id="modalButton" class="modal-btn">OK</button>
      </div>
    </div>
  </div>

  <script>
    // Modal functionality
    const modal = document.getElementById('loginModal');
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
      modalTitle.textContent = status === 'success' ? 'Login Successful!' : 'Login Failed';
      modalMessage.textContent = message;
      modalIcon.className = 'fas ' + (status === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle');

      // Show modal with flexbox for centering
      modal.style.display = 'flex';
      // Force reflow to ensure transition works
      modal.offsetHeight;
      modal.style.opacity = '1';

      // Set button text and action
      if (status === 'success') {
        modalButton.textContent = 'Go In';
        modalButton.onclick = () => {
          window.location.href = '../home/home.php';
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

    // 👁 Toggle password visibility
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

    // AJAX form submission
    document.getElementById("loginForm").addEventListener("submit", function(e) {
      e.preventDefault();

      // Ensure modal is hidden before starting
      if (modal) {
        modal.style.display = 'none';
      }

      const formData = new FormData(this);

      // Show loading state
      const submitButton = this.querySelector('button[type="submit"]');
      const originalText = submitButton.textContent;
      submitButton.textContent = 'Logging in...';
      submitButton.disabled = true;

      fetch('login_auth.php', {
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

    // Clear input fields when page is loaded from Back/Forward cache
    window.addEventListener("pageshow", function (event) {
      if (event.persisted) {
        document.querySelectorAll("input").forEach(input => input.value = "");
      }
    });

  </script>
</body>

</html>