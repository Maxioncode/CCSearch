/**
 * Notification management functions
 */

let notificationToDelete = null;

function markAsRead(notificationID) {
    const path = window.location.pathname.includes('/notification/') ? 'mark_read.php' : '../notification/mark_read.php';
    fetch(path, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notificationID=' + encodeURIComponent(notificationID)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI
                const notificationRow = document.querySelector(`[data-id="${notificationID}"]`);
                if (notificationRow) {
                    notificationRow.classList.remove('unread');
                    const markReadBtn = notificationRow.querySelector('.mark-read-btn');
                    if (markReadBtn) {
                        markReadBtn.remove();
                    }
                }
                updateNotificationBadge();
            } else {
                alert('Failed to mark notification as read: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while marking notification as read');
        });
}

function deleteNotification(notificationID) {
    // Show confirmation modal instead of confirm()
    console.log('Delete notification clicked:', notificationID);
    notificationToDelete = notificationID;
    const modal = document.getElementById('deleteNotificationModal');
    if (modal) {
        modal.style.display = 'flex';
        console.log('Modal should be visible now');
            } else {
        console.error('Modal not found!');
        alert('Error: Modal not found. Please refresh the page.');
    }
}

function updateNotificationBadge() {
    // Update the notification badge in the navigation
    const path = window.location.pathname.includes('/notification/') ? 'get_unread_count.php' : '../notification/get_unread_count.php';
    fetch(path)
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            if (data.count > 0) {
                const count = data.count > 99 ? '99+' : data.count;
                if (badge) {
                    badge.textContent = count;
                } else {
                    // Create badge if it doesn't exist
                    const navLink = document.querySelector('a[href*="notification.php"]');
                    if (navLink) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'notification-badge';
                        newBadge.textContent = count;
                        navLink.appendChild(newBadge);
                    }
                }
            } else {
                // Remove badge if no unread notifications
                if (badge) {
                    badge.remove();
                }
            }
        })
        .catch(error => {
            console.error('Error updating notification badge:', error);
        });
}

// Hover effects are handled by CSS, but we can add additional functionality here if needed
// Modal event handlers (ensure these run after DOM loaded)
document.addEventListener('DOMContentLoaded', function () {
    // Update badge on page load
    updateNotificationBadge();

    const modal = document.getElementById('deleteNotificationModal');
    const closeBtn = document.getElementById('closeDeleteNotifModal');
    const cancelBtn = document.getElementById('cancelDeleteNotifBtn');
    const confirmBtn = document.getElementById('confirmDeleteNotifBtn');

    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
        }
        notificationToDelete = null;
        // Reset button state
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Delete';
        }
    }

    closeBtn && closeBtn.addEventListener('click', closeModal);
    cancelBtn && cancelBtn.addEventListener('click', closeModal);
    window.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });

    confirmBtn && confirmBtn.addEventListener('click', function () {
        if (!notificationToDelete) {
            console.error('No notification ID to delete');
            return;
        }
        
        // Disable button during deletion
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Deleting...';
        
        const path = window.location.pathname.includes('/notification/') ? 'delete_notification.php' : '../notification/delete_notification.php';
        console.log('Deleting notification:', notificationToDelete, 'via:', path);
        
        fetch(path, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notificationID=' + encodeURIComponent(notificationToDelete)
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Delete response:', data);
            if (data.success) {
                // Remove from UI
                const notificationRow = document.querySelector(`[data-id="${notificationToDelete}"]`);
                if (notificationRow) {
                    notificationRow.style.transition = 'opacity 0.3s';
                    notificationRow.style.opacity = '0';
                    setTimeout(() => {
                        notificationRow.remove();
                        // Check if section is now empty
                        const section = notificationRow.closest('.notif-section');
                        if (section) {
                            const remainingRows = section.querySelectorAll('.notif-row');
                            if (remainingRows.length === 0) {
                                section.remove();
                            }
                        }
                    }, 300);
                } else {
                    console.warn('Notification row not found in DOM');
                }
                updateNotificationBadge();
                closeModal();
            } else {
                alert('Failed to delete notification: ' + (data.message || 'Unknown error'));
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Delete';
            }
        })
        .catch(error => {
            console.error('Error deleting notification:', error);
            alert('An error occurred while deleting the notification. Please try again.');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Delete';
        });
    });
});
