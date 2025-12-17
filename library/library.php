<?php
session_start();
include "../database/database.php";

// Redirect to login if user is not logged in
if (!isset($_SESSION['studentID'])) {
  header("Location: ../login/login.html");
  exit();
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$studentID = $_SESSION['studentID'];

// Fetch publications in student's library
$stmt = $conn->prepare("SELECT p.*, r.firstName, r.lastName FROM publications p JOIN registration r ON p.studentID = r.studentID WHERE p.studentID = ? ORDER BY p.publicationID DESC");
if ($stmt) {
  $stmt->bind_param("s", $studentID);
  $stmt->execute();
  $result = $stmt->get_result();
  $libraryPublications = [];
  while ($row = $result->fetch_assoc()) {
    $libraryPublications[] = $row;
  }
  $stmt->close();
} else {
  die("Failed to prepare statement: " . $conn->error);
}

// Set layout variables
$pageTitle = 'CCSearch Library';
$activeNav = 'library';
$additionalCSS = ['library_page.css'];
$additionalExternalCSS = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Welcome Header -->
<div class="welcome-header library-header">
  
    <img src="../image/home_images/welcome-header.png" alt="Wavy blue background" class="banner-background" />
    <div class="welcome-content">
        <h2>Welcome to CCSearch, Jelly! 👋</h2>
        <p>Where you can share credible knowledge and discover reliable sources — all in one place!</p>
        <div class="search-box">
            <input type="text" id="librarySearchInput" placeholder="Search titles, authors, student IDs..." />
            <img src="../icons/authors/search.png" class="search-icon" alt="Search">
        </div>
    </div>
    />
</div>

<!-- Content Section -->
<div class="content-section">
    <div class="category-box">
        <div class="category-header">
            <h3>My Books</h3>
            <a href="view_all.php?category=my_books">View all →</a>
        </div>
        <div class="card-grid" id="myBooksGrid">
            <?php if (!empty($libraryPublications)): ?>
                <?php foreach ($libraryPublications as $pub): ?>
                    <div class="card" data-filepath="<?php echo htmlspecialchars($pub['file_path']); ?>" data-title="<?php echo htmlspecialchars($pub['title']); ?>" data-author="<?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?>" data-studentid="<?php echo htmlspecialchars($pub['studentID']); ?>" data-date="<?php echo htmlspecialchars($pub['published_datetime']); ?>" data-abstract="<?php echo htmlspecialchars($pub['abstract'] ?? ''); ?>" data-department="<?php echo htmlspecialchars($pub['department'] ?? ''); ?>" data-type="<?php echo htmlspecialchars($pub['type'] ?? ''); ?>" data-thumbnail="<?php echo htmlspecialchars($pub['thumbnail'] ?? ''); ?>" onclick="previewPublication(this)">
                        <?php
                        $imageSrc = isset($pub['thumbnail']) && !empty($pub['thumbnail']) ? '../' . $pub['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
                        $imageSrc .= '?t=' . time(); // Cache busting
                        ?>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="cover-img" alt="Publication cover">
                        <div class="card-info">
                            <h4 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h4>
                            <div class="posted-by">
                                Posted by: <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($pub['studentID']); ?>" onclick="event.stopPropagation()"><?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?></a>
                            </div>
                            <div class="posted-by">Published: <?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                            <div class="card-actions">
                                <button onclick="event.stopPropagation(); previewPublication(this.closest('.card'))" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <?php if (isset($_SESSION['studentID']) && $_SESSION['studentID'] === $pub['studentID']): ?>
                                    <button onclick="event.stopPropagation(); deletePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book-open" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>Your library is empty</h3>
                    <p>Publications you upload will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Saved Books -->
    <div class="category-box">
        <div class="category-header">
            <h3>Saved Books</h3>
            <a href="view_all.php?category=saved_books">View all →</a>
        </div>
    <div class="card-grid" id="savedBooksGrid">
            <?php
            // Fetch saved publications with full details
            $stmt2 = $conn->prepare("
                SELECT p.*, r.firstName, r.lastName
                FROM saved_publications sp
                JOIN publications p ON sp.publicationID = p.publicationID
                JOIN registration r ON p.studentID = r.studentID
                WHERE sp.studentID = ?
                ORDER BY sp.savedID DESC
                LIMIT 10
            ");
            if ($stmt2) {
                $stmt2->bind_param("s", $studentID);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                $savedPublications = [];
                while ($row2 = $res2->fetch_assoc()) {
                    $savedPublications[] = $row2;
                }
                $stmt2->close();
            }

            if (!empty($savedPublications)):
                foreach ($savedPublications as $pub):
            ?>
                    <div class="card" data-filepath="<?php echo htmlspecialchars($pub['file_path']); ?>" data-title="<?php echo htmlspecialchars($pub['title']); ?>" data-author="<?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?>" data-studentid="<?php echo htmlspecialchars($pub['studentID']); ?>" data-date="<?php echo htmlspecialchars($pub['published_datetime']); ?>" data-abstract="<?php echo htmlspecialchars($pub['abstract'] ?? ''); ?>" data-department="<?php echo htmlspecialchars($pub['department'] ?? ''); ?>" data-type="<?php echo htmlspecialchars($pub['type'] ?? ''); ?>" data-thumbnail="<?php echo htmlspecialchars($pub['thumbnail'] ?? ''); ?>" onclick="previewPublication(this)">
                        <?php
                        $imageSrc = isset($pub['thumbnail']) && !empty($pub['thumbnail']) ? '../' . $pub['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
                        $imageSrc .= '?t=' . time(); // Cache busting
                        ?>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="cover-img" alt="Publication cover">
                        <div class="card-info">
                            <h4 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h4>
                            <div class="posted-by">
                                Posted by: <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($pub['studentID']); ?>" onclick="event.stopPropagation()"><?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?></a>
                            </div>
                            <div class="posted-by">Published: <?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                            <div class="card-actions">
                                <button onclick="event.stopPropagation(); previewPublication(this.closest('.card'))" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button onclick="event.stopPropagation(); unsavePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-danger btn-sm">
                                    <i class="fas fa-times"></i> Unsave
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bookmark" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No saved publications</h3>
                    <p>Publications you save will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Favorite Authors -->
    <div class="category-box favorite-authors-box">
        <div class="category-header">
            <h3>Favorite Authors</h3>
            <a href="../authors/authors.php">View all →</a>
        </div>
        <div class="authors-grid" id="favoriteAuthorsGrid">
            <?php
            // Fetch favorite authors with their info
            $favoriteAuthors = [];
            try {
                $stmt3 = $conn->prepare("
                SELECT r.*, up.profileImage, up.theme_preference, COUNT(p.publicationID) as totalPublications
                FROM favorite_authors fa
                JOIN registration r ON fa.favorite_studentID = r.studentID
                LEFT JOIN user_profiles up ON fa.favorite_studentID = up.studentID
                LEFT JOIN publications p ON fa.favorite_studentID = p.studentID
                WHERE fa.studentID = ?
                GROUP BY r.studentID, r.firstName, r.lastName, r.emailAddress, r.contactNumber, r.currentAddress, r.department, up.profileImage, up.theme_preference
                ORDER BY fa.added_datetime DESC
                    LIMIT 10
                ");
                if ($stmt3) {
                    $stmt3->bind_param("s", $studentID);
                    $stmt3->execute();
                    $res3 = $stmt3->get_result();
                    while ($row3 = $res3->fetch_assoc()) {
                        $favoriteAuthors[] = $row3;
                    }
                    $stmt3->close();
                }
            } catch (Exception $e) {
                // Table doesn't exist yet, create it
                $createTableSQL = "
                    CREATE TABLE IF NOT EXISTS `favorite_authors` (
                      `favoriteID` int(11) NOT NULL AUTO_INCREMENT,
                      `studentID` varchar(20) NOT NULL,
                      `favorite_studentID` varchar(20) NOT NULL,
                      `added_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`favoriteID`),
                      UNIQUE KEY `unique_favorite` (`studentID`, `favorite_studentID`),
                      KEY `fk_favorite_student` (`studentID`),
                      KEY `fk_favorite_author` (`favorite_studentID`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
                ";
                $conn->query($createTableSQL);
                // favoriteAuthors remains empty
            }

            if (!empty($favoriteAuthors)):
                foreach ($favoriteAuthors as $author):
            ?>
                <div class="author-card" data-name="<?php echo htmlspecialchars(strtolower($author['firstName'] . ' ' . $author['lastName'])); ?>" data-studentid="<?php echo htmlspecialchars(strtolower($author['studentID'])); ?>">
                    <img src="../icons/authors/card_bg.png" class="banner-img" alt="Author Banner">
                    <div class="profile-circle" style="background-image: url('<?php echo htmlspecialchars(isset($author['profileImage']) && !empty($author['profileImage']) ? '../' . $author['profileImage'] : '../uploads/profiles/profile.png'); ?>');"></div>
                    <h3><?php echo htmlspecialchars($author['firstName'] . ' ' . $author['lastName']); ?></h3>
                    <p class="username"><?php echo htmlspecialchars($author['studentID']); ?></p>
                    <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($author['studentID']); ?>" class="visit-btn">Visit</a>
                    <div class="stats">
                        <div><strong><?php echo htmlspecialchars($author['totalPublications']); ?></strong><br>Books</div>
                        <div>
                            <button onclick="unfavoriteAuthor('<?php echo htmlspecialchars($author['studentID']); ?>')" class="favorite-btn unfavorited">
                                <i class="fas fa-heart-broken"></i>
                            </button><br>Unfavorite
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php else: ?>
                <div class="no-authors">
                    <i class="fas fa-heart" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No favorite authors</h3>
                    <p>Authors you favorite will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Delete Publication Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Publication</h3>
            <span class="close-modal" onclick="closeDeleteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete "<span id="deleteTitle"></span>"?</p>
            <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
            <button id="confirmDeleteBtn" onclick="confirmDelete()" class="btn btn-danger">Delete</button>
        </div>
    </div>
</div>

<!-- Unsave Publication Modal -->
<div id="unsaveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Unsave Publication</h3>
            <span class="close-modal" onclick="closeUnsaveModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Remove "<span id="unsaveTitle"></span>" from your saved publications?</p>
            <p>You can always save it again later.</p>
        </div>
        <div class="modal-footer">
            <button onclick="closeUnsaveModal()" class="btn btn-secondary">Cancel</button>
            <button id="confirmUnsaveBtn" onclick="confirmUnsave()" class="btn btn-danger">Remove</button>
        </div>
    </div>
</div>

<script>
// Delete publication functionality
let publicationToDelete = null;

function deletePublication(publicationID, title) {
    publicationToDelete = publicationID;
    document.getElementById('deleteTitle').textContent = title;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    publicationToDelete = null;
}

function confirmDelete() {
    if (!publicationToDelete) return;

    // Disable button to prevent double-clicks
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    btn.textContent = 'Deleting...';

    fetch('../home/delete_publication.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'publicationID=' + encodeURIComponent(publicationToDelete)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to reflect changes
            location.reload();
        } else {
            alert('Error deleting publication: ' + (data.message || 'Unknown error'));
            closeDeleteModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the publication.');
        closeDeleteModal();
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Delete';
    });
}

// Unsave publication functionality
let publicationToUnsave = null;

function unsavePublication(publicationID, title) {
    publicationToUnsave = publicationID;
    document.getElementById('unsaveTitle').textContent = title;
    document.getElementById('unsaveModal').style.display = 'flex';
}

function closeUnsaveModal() {
    document.getElementById('unsaveModal').style.display = 'none';
    publicationToUnsave = null;
}

function confirmUnsave() {
    if (!publicationToUnsave) return;

    // Disable button to prevent double-clicks
    const btn = document.getElementById('confirmUnsaveBtn');
    btn.disabled = true;
    btn.textContent = 'Removing...';

    fetch('unsave_publication.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'publicationID=' + encodeURIComponent(publicationToUnsave)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to reflect changes
            location.reload();
        } else {
            alert('Error removing publication: ' + (data.message || 'Unknown error'));
            closeUnsaveModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while removing the publication.');
        closeUnsaveModal();
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Remove';
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteModal');
    const unsaveModal = document.getElementById('unsaveModal');
    const previewModal = document.getElementById('previewModal');
    const filterModal = document.getElementById('filterModal');

    if (event.target === deleteModal) {
        closeDeleteModal();
    }
    if (event.target === unsaveModal) {
        closeUnsaveModal();
    }
    if (previewModal && event.target === previewModal) {
        closePreviewModal();
    }
    if (filterModal && event.target === filterModal) {
        filterModal.style.display = 'none';
    }
}

    // Unfavorite author functionality
    function unfavoriteAuthor(authorID) {
        if (confirm('Remove this author from your favorites?')) {
            fetch('../authors/toggle_favorite_author.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'authorID=' + encodeURIComponent(authorID)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the author card from the page
                    const authorCard = document.querySelector(`button[onclick*="${authorID}"]`).closest('.author-card');
                    if (authorCard) {
                        authorCard.remove();
                    }
                    // Check if there are no more favorite authors
                    const remainingCards = document.querySelectorAll('.authors-grid .author-card');
                    if (remainingCards.length === 0) {
                        const authorsGrid = document.querySelector('.authors-grid');
                        authorsGrid.innerHTML = `
                            <div class="no-authors">
                                <i class="fas fa-heart" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                                <h3>No favorite authors</h3>
                                <p>Authors you favorite will appear here.</p>
                            </div>
                        `;
                    }
                } else {
                    alert('Error removing author: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while removing the author.');
            });
        }
    }

    // Publication preview functionality
    function previewPublication(element) {
        const filePath = element.getAttribute('data-filepath');
        const title = element.getAttribute('data-title');
        const author = element.getAttribute('data-author');
        const publishDate = element.getAttribute('data-date');
        const abstract = element.getAttribute('data-abstract');
        const department = element.getAttribute('data-department');
        const type = element.getAttribute('data-type');
        const thumbnail = element.getAttribute('data-thumbnail');

        console.log('previewPublication called with:', {filePath, title, author, publishDate, abstract, department, type, thumbnail});

        // Create modal with proper CSS classes
        const modal = document.createElement('div');
        modal.id = 'previewModal';
        modal.className = 'modal';

        const formattedDate = new Date(publishDate).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        let html = '<div class="modal-content preview-modal-content">';
        html += '<div class="modal-header">';
        html += '<h3>' + title + '</h3>';
        html += '<span class="close-modal" onclick="closePreviewModal()">&times;</span>';
        html += '</div>';
        html += '<div class="modal-body">';
        html += '<div class="preview-content-wrapper">';

        // Thumbnail on left side
        if (thumbnail) {
            html += '<div class="preview-thumbnail-container">';
            html += '<img src="../' + thumbnail + '?t=' + Date.now() + '" alt="Document preview" class="preview-thumbnail">';
            html += '</div>';
        }

        // Publication details on right side
        html += '<div class="preview-details-container">';
        html += '<div class="publication-details">';
        html += '<div class="detail-row"><strong>Author:</strong> <span>' + author + '</span></div>';
        html += '<div class="detail-row"><strong>Published:</strong> <span>' + formattedDate + '</span></div>';

        if (department) {
            html += '<div class="detail-row"><strong>Department:</strong> <span>' + department + '</span></div>';
        }
        if (type) {
            html += '<div class="detail-row"><strong>Type:</strong> <span>' + type + '</span></div>';
        }
        html += '</div>';

        // Abstract section (separated from publication details)
            html += '<div class="abstract-section">';
            html += '<div class="abstract-label"><strong>Abstract:</strong></div>';
        html += '<div class="abstract-text">' + (abstract || 'No abstract available.') + '</div>';
            html += '</div>';
        html += '</div>'; // Close preview-details-container
        html += '</div>'; // Close preview-content-wrapper

        // Action buttons
        html += '<div class="preview-actions">';
        html += '<a href="../' + filePath + '" target="_blank" class="btn btn-primary">';
        html += '<i class="fas fa-external-link-alt"></i> View Full Document';
        html += '</a>';
        html += '<a href="../' + filePath + '" download class="btn btn-secondary">';
        html += '<i class="fas fa-download"></i> Download';
        html += '</a>';
        html += '</div>';

        html += '</div>';
        html += '</div>';

        modal.innerHTML = html;

        document.body.appendChild(modal);
        modal.style.display = 'flex';
        console.log('Modal created and displayed:', modal);
    }

    function closePreviewModal() {
        const modal = document.getElementById('previewModal');
        if (modal) {
            modal.remove();
        }
    }

// Library search and filter (titles, authors, student IDs across My Books, Saved Books, Favorite Authors)
function filterLibrarySearch() {
    const query = (document.getElementById('librarySearchInput')?.value || '').toLowerCase().trim();

    const sections = [
        { gridId: 'myBooksGrid', emptyClass: 'empty-state', emptyText: '<h3>Your library is empty</h3><p>Publications you upload will appear here.</p>' },
        { gridId: 'savedBooksGrid', emptyClass: 'empty-state', emptyText: '<h3>No saved publications</h3><p>Publications you save will appear here.</p>' },
        { gridId: 'favoriteAuthorsGrid', emptyClass: 'no-authors', emptyText: '<p>No favorite authors</p>' },
    ];

    sections.forEach(section => {
        const grid = document.getElementById(section.gridId);
        if (!grid) return;

        const cards = grid.querySelectorAll('.card, .author-card');
        let visible = 0;

        cards.forEach(card => {
            const title = (card.getAttribute('data-title') || '').toLowerCase();
            const author = (card.getAttribute('data-author') || card.getAttribute('data-name') || '').toLowerCase();
            const studentId = (card.getAttribute('data-studentid') || '').toLowerCase();

            const match = !query || title.includes(query) || author.includes(query) || studentId.includes(query);
            card.style.display = match ? 'block' : 'none';
            if (match) visible++;
        });

        let emptyState = grid.querySelector(`.${section.emptyClass}`);
        if (visible === 0) {
            if (!emptyState) {
                emptyState = document.createElement('div');
                emptyState.className = section.emptyClass;
                emptyState.innerHTML = section.emptyText;
                grid.appendChild(emptyState);
            }
        } else if (emptyState) {
            emptyState.remove();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('librarySearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', filterLibrarySearch);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') e.preventDefault();
        });
    }
    filterLibrarySearch();
});
</script>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>
