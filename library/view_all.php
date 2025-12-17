<?php
session_start();
include "../database/database.php";

// Redirect to login if user is not logged in
if (!isset($_SESSION['studentID'])) {
  header("Location: ../login/login.html");
  exit();
}

$studentID = $_SESSION['studentID'];

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : 'my_books'; // 'my_books' or 'saved_books'
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';

// Build the query based on category
if ($category === 'saved_books') {
    // Fetch saved publications
    $query = "SELECT p.*, r.firstName, r.lastName 
              FROM saved_publications sp
              JOIN publications p ON sp.publicationID = p.publicationID
              JOIN registration r ON p.studentID = r.studentID
              WHERE sp.studentID = ?";
} else {
    // Fetch user's own publications
    $query = "SELECT p.*, r.firstName, r.lastName 
              FROM publications p 
              JOIN registration r ON p.studentID = r.studentID
              WHERE p.studentID = ?";
}

$params = [$studentID];
$paramTypes = 's';

// Apply search filter
if (!empty($searchQuery)) {
    $query .= " AND (p.title LIKE ? OR p.authors LIKE ? OR CONCAT(r.firstName, ' ', r.lastName) LIKE ? OR p.abstract LIKE ?)";
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $paramTypes .= 'ssss';
}

// Apply department filter
if (!empty($departmentFilter)) {
    $query .= " AND p.department = ?";
    $params[] = $departmentFilter;
    $paramTypes .= 's';
}

// Apply type filter
if (!empty($typeFilter)) {
    $query .= " AND p.type = ?";
    $params[] = $typeFilter;
    $paramTypes .= 's';
}

// Order by
if ($category === 'saved_books') {
    $query .= " ORDER BY sp.savedID DESC";
} else {
    $query .= " ORDER BY p.publicationID DESC";
}

// Execute query with prepared statement
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $allPublications = [];
    while ($row = $result->fetch_assoc()) {
        $allPublications[] = $row;
    }
    $stmt->close();
} else {
    $allPublications = [];
}

// Get unique departments and types for filter dropdowns
$deptResult = $conn->query("SELECT DISTINCT department FROM publications WHERE department IS NOT NULL AND department != '' ORDER BY department");
$departments = [];
if ($deptResult) {
    while ($row = $deptResult->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

$typeResult = $conn->query("SELECT DISTINCT type FROM publications WHERE type IS NOT NULL AND type != '' ORDER BY type");
$types = [];
if ($typeResult) {
    while ($row = $typeResult->fetch_assoc()) {
        $types[] = $row['type'];
    }
}

// Set layout variables
$pageTitle = $category === 'saved_books' ? 'View All Saved Books' : 'View All My Books';
$activeNav = 'library';
$additionalCSS = ['view_all_page.css'];
$additionalExternalCSS = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Top Bar -->
<div class="top-bar">
  <span class="back" onclick="goBack()">&larr; Back</span>
  <h1><?php echo $category === 'saved_books' ? 'View All Saved Books' : 'View All My Books'; ?></h1>
  <div class="search-box">
    <input type="text" id="searchInput" placeholder="Search" value="<?php echo htmlspecialchars($searchQuery); ?>">
    <img src="../icons/authors/search.png" class="search-icon" alt="Search">
  </div>
  <i class="fa fa-filter filter-icon" onclick="toggleFilterModal()"></i>
  <i class="fa fa-bars menu-icon"></i>
</div>

<!-- Filter Modal -->
<div id="filterModal" class="filter-modal">
  <div class="filter-modal-content">
    <div class="filter-modal-header">
      <h3>Filter Publications</h3>
      <span class="close-filter" onclick="toggleFilterModal()">&times;</span>
    </div>
    <div class="filter-modal-body">
      <div class="filter-group">
        <label>Department:</label>
        <select id="departmentFilter" class="filter-select">
          <option value="">All Departments</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $departmentFilter === $dept ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($dept); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <label>Type:</label>
        <select id="typeFilter" class="filter-select">
          <option value="">All Types</option>
          <?php foreach ($types as $type): ?>
            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $typeFilter === $type ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($type); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="filter-modal-footer">
      <button onclick="applyFilters()" class="btn btn-primary">Apply Filters</button>
      <button onclick="clearFilters()" class="btn btn-secondary">Clear</button>
    </div>
  </div>
</div>

<!-- Scroll Area -->
<div class="scroll-area">
  <section class="section-box">
    <h2><?php echo $category === 'saved_books' ? 'All Saved Books' : 'All My Books'; ?></h2>
    <div class="card-grid" id="publicationsGrid">
      <?php if (!empty($allPublications)): ?>
        <?php foreach ($allPublications as $pub): ?>
          <div class="card" 
               data-title="<?php echo htmlspecialchars(strtolower($pub['title'])); ?>"
               data-author="<?php echo htmlspecialchars(strtolower($pub['firstName'] . ' ' . $pub['lastName'])); ?>"
               data-department="<?php echo htmlspecialchars(strtolower($pub['department'] ?? '')); ?>"
               data-type="<?php echo htmlspecialchars(strtolower($pub['type'] ?? '')); ?>">
            <?php
            $imageSrc = isset($pub['thumbnail']) && !empty($pub['thumbnail']) ? '../' . $pub['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
            $imageSrc .= '?t=' . time();
            ?>
            <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="cover-img" alt="Publication cover">
            <div class="card-info">
              <h4 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h4>
              <div class="posted-by">
                Posted by: <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($pub['studentID']); ?>"><?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?></a>
              </div>
              <div class="posted-by">Published: <?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
              <?php if (!empty($pub['department'])): ?>
                <div class="posted-by">Department: <?php echo htmlspecialchars($pub['department']); ?></div>
              <?php endif; ?>
              <?php if (!empty($pub['type'])): ?>
                <div class="posted-by">Type: <?php echo htmlspecialchars($pub['type']); ?></div>
              <?php endif; ?>
              <div class="card-actions">
                <button onclick="previewPublication('<?php echo htmlspecialchars($pub['file_path']); ?>', '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['firstName'] . ' ' . $pub['lastName'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['published_datetime'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['abstract'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['department'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['type'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['thumbnail'] ?? '')); ?>')" class="btn btn-primary btn-sm">
                  <i class="fas fa-eye"></i> Preview
                </button>
                <?php if ($category === 'saved_books'): ?>
                  <button onclick="unsavePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-danger btn-sm">
                    <i class="fas fa-times"></i> Unsave
                  </button>
                <?php elseif (isset($_SESSION['studentID']) && $_SESSION['studentID'] === $pub['studentID']): ?>
                  <button onclick="deletePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash"></i> Delete
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-book-open empty-icon"></i>
          <h3>No publications found</h3>
          <p>Try adjusting your search or filter criteria.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>
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
// Filter modal functionality
function toggleFilterModal() {
    const modal = document.getElementById('filterModal');
    if (modal.style.display === 'flex') {
        modal.style.display = 'none';
    } else {
        modal.style.display = 'flex';
    }
}

// Close filter modal when clicking outside
window.onclick = function(event) {
    const filterModal = document.getElementById('filterModal');
    if (event.target === filterModal) {
        filterModal.style.display = 'none';
    }
}

// Apply filters
function applyFilters() {
    const searchQuery = document.getElementById('searchInput').value.trim();
    const department = document.getElementById('departmentFilter').value;
    const type = document.getElementById('typeFilter').value;
    
    let url = 'view_all.php?category=<?php echo $category; ?>';
    if (searchQuery) url += '&search=' + encodeURIComponent(searchQuery);
    if (department) url += '&department=' + encodeURIComponent(department);
    if (type) url += '&type=' + encodeURIComponent(type);
    
    window.location.href = url;
}

// Clear filters
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('departmentFilter').value = '';
    document.getElementById('typeFilter').value = '';
    window.location.href = 'view_all.php?category=<?php echo $category; ?>';
}

// Search functionality
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});

// Filter publications on client side for instant feedback
document.getElementById('searchInput').addEventListener('input', function() {
    filterPublications();
});

document.getElementById('departmentFilter').addEventListener('change', function() {
    filterPublications();
});

document.getElementById('typeFilter').addEventListener('change', function() {
    filterPublications();
});

function filterPublications() {
    const searchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
    const departmentFilter = document.getElementById('departmentFilter').value.toLowerCase();
    const typeFilter = document.getElementById('typeFilter').value.toLowerCase();
    
    const cards = document.querySelectorAll('#publicationsGrid .card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const title = card.getAttribute('data-title') || '';
        const author = card.getAttribute('data-author') || '';
        const department = card.getAttribute('data-department') || '';
        const type = card.getAttribute('data-type') || '';
        
        const matchesSearch = !searchQuery || 
            title.includes(searchQuery) || 
            author.includes(searchQuery);
        
        const matchesDepartment = !departmentFilter || department === departmentFilter;
        const matchesType = !typeFilter || type === typeFilter;
        
        if (matchesSearch && matchesDepartment && matchesType) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show empty state if no cards visible
    let emptyState = document.querySelector('.empty-state');
    if (visibleCount === 0 && !emptyState) {
        const grid = document.getElementById('publicationsGrid');
        emptyState = document.createElement('div');
        emptyState.className = 'empty-state';
        emptyState.innerHTML = `
            <i class="fas fa-book-open empty-icon"></i>
            <h3>No publications found</h3>
            <p>Try adjusting your search or filter criteria.</p>
        `;
        grid.appendChild(emptyState);
    } else if (visibleCount > 0 && emptyState) {
        emptyState.remove();
    }
}

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

// Publication preview functionality
function previewPublication(filePath, title, author, publishDate, abstract, department, type, thumbnail) {
    const formattedDate = new Date(publishDate).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    const modal = document.createElement('div');
    modal.id = 'previewModal';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content preview-modal-content">
            <div class="modal-header">
                <h3>${title}</h3>
                <span class="close-modal" onclick="closePreviewModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="preview-content-wrapper">
                    ${thumbnail ? `<div class="preview-thumbnail-container">
                        <img src="../${thumbnail}?t=${Date.now()}" alt="Document preview" class="preview-thumbnail">
                    </div>` : ''}
                    <div class="preview-details-container">
                <div class="publication-details">
                    <div class="detail-row">
                        <strong>Author:</strong> <span>${author}</span>
                    </div>
                    <div class="detail-row">
                        <strong>Published:</strong> <span>${formattedDate}</span>
                    </div>
                    ${department ? `<div class="detail-row"><strong>Department:</strong> <span>${department}</span></div>` : ''}
                    ${type ? `<div class="detail-row"><strong>Type:</strong> <span>${type}</span></div>` : ''}
                </div>
                <div class="abstract-section">
                    <div class="abstract-label"><strong>Abstract:</strong></div>
                    <div class="abstract-text">${abstract || 'No abstract available.'}</div>
                </div>
                    </div>
                </div>
                <div class="preview-actions">
                    <a href="../${filePath}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> View Full Document
                    </a>
                    <a href="../${filePath}" download class="btn btn-secondary">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    modal.style.display = 'flex';
}

function closePreviewModal() {
    const modal = document.getElementById('previewModal');
    if (modal) {
        modal.remove();
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteModal');
    const unsaveModal = document.getElementById('unsaveModal');
    const previewModal = document.getElementById('previewModal');

    if (deleteModal && event.target === deleteModal) {
        closeDeleteModal();
    }
    if (unsaveModal && event.target === unsaveModal) {
        closeUnsaveModal();
    }
    if (previewModal && event.target === previewModal) {
        closePreviewModal();
    }
}
</script>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>


