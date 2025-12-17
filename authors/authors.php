<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['studentID'])) {
    header("Location: ../login/login.html");
    exit();
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Set layout variables
$pageTitle = 'Authors';
$activeNav = 'authors';
$additionalCSS = ['authors_page.css'];

// Fetch authors data
include "../database/database.php";

// Get filter parameters
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';

// Build the query with filters
$authorsQuery = "
    SELECT
        r.studentID,
        r.firstName,
        r.lastName,
        r.emailAddress,
        r.contactNumber,
        r.currentAddress,
        r.department,
        up.profileImage,
        up.theme_preference,
        up.is_public,
        COUNT(p.publicationID) as totalPublications
    FROM registration r
    LEFT JOIN user_profiles up ON r.studentID = up.studentID
    LEFT JOIN publications p ON r.studentID = p.studentID
    WHERE r.studentID != ?
";

$params = [$_SESSION['studentID']];
$paramTypes = 's';

// Apply search filter
if (!empty($searchQuery)) {
    $authorsQuery .= " AND (r.firstName LIKE ? OR r.studentID LIKE ?)";
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $paramTypes .= 'ss';
}

// Apply department filter
if (!empty($departmentFilter)) {
    $authorsQuery .= " AND r.department = ?";
    $params[] = $departmentFilter;
    $paramTypes .= 's';
}

$authorsQuery .= " GROUP BY r.studentID, r.firstName, r.lastName, r.emailAddress, r.contactNumber, r.currentAddress, r.department, up.profileImage, up.theme_preference, up.is_public ORDER BY r.studentID";

// Execute query with prepared statement
$stmt = $conn->prepare($authorsQuery);
if ($stmt) {
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $authorsResult = $stmt->get_result();
$authors = [];
    while ($row = $authorsResult->fetch_assoc()) {
        $authors[] = $row;
    }
    $stmt->close();
} else {
    $authors = [];
}

// Get unique departments for filter dropdown
$deptResult = $conn->query("SELECT DISTINCT department FROM registration WHERE department IS NOT NULL AND department != '' ORDER BY department");
$departments = [];
if ($deptResult) {
    while ($row = $deptResult->fetch_assoc()) {
        $departments[] = $row['department'];
    }
    $deptResult->free();
}

$conn->close();

// Include layout header
include "../layout/layout.php";
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Authors</h1>
    <div class="header-actions">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search by First Name or Student ID..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <img src="../icons/authors/search.png" class="search-icon" alt="Search">
        </div>
        <div class="filter-box" onclick="toggleFilterModal()">
            <img src="../icons/authors/filter.png" alt="Filter">
            <span>Filter</span>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div id="filterModal" class="filter-modal">
    <div class="filter-modal-content">
        <div class="filter-modal-header">
            <h3>Filter Authors</h3>
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
        </div>
        <div class="filter-modal-footer">
            <button onclick="applyFilters()" class="btn btn-primary">Apply Filters</button>
            <button onclick="clearFilters()" class="btn btn-secondary">Clear</button>
        </div>
    </div>
</div>

<!-- Content Box -->
<div class="content-section">
    <div class="authors-grid" id="authorsGrid">
        <?php if (!empty($authors)): ?>
            <?php foreach ($authors as $author): ?>
                <div class="author-card" 
                     data-firstname="<?php echo htmlspecialchars(strtolower($author['firstName'])); ?>"
                     data-studentid="<?php echo htmlspecialchars(strtolower($author['studentID'])); ?>"
                     data-department="<?php echo htmlspecialchars(strtolower($author['department'] ?? '')); ?>">
                    <img src="../icons/authors/card_bg.png" class="banner-img" alt="Author Banner">
                    <div class="profile-circle" style="background-image: url('<?php echo htmlspecialchars(!empty($author['profileImage']) ? '../' . $author['profileImage'] : '../uploads/profiles/profile.png'); ?>');"></div>
                    <h3><?php echo htmlspecialchars($author['firstName'] . ' ' . $author['lastName']); ?></h3>
                    <p class="username"><?php echo htmlspecialchars($author['studentID']); ?></p>
                    <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($author['studentID']); ?>" class="visit-btn">Visit</a>
                    <div class="stats">
                        <div><strong><?php echo htmlspecialchars($author['totalPublications']); ?></strong><br>Books</div>
                        <div>
                            <button onclick="toggleFavorite('<?php echo htmlspecialchars($author['studentID']); ?>', this)" class="favorite-btn" id="fav-<?php echo htmlspecialchars($author['studentID']); ?>">
                                <i class="fas fa-heart"></i>
                            </button><br>Favorite
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-authors">
                <p>No authors found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Favorite author functionality
function toggleFavorite(authorID, button) {
    const icon = button.querySelector('i');

    // Toggle visual state immediately
    const isFavorited = button.classList.contains('favorited');
    if (isFavorited) {
        button.classList.remove('favorited');
        icon.className = 'far fa-heart'; // Empty heart when not favorited
    } else {
        button.classList.add('favorited');
        icon.className = 'fas fa-heart'; // Filled heart when favorited
    }

    // Send request to server
    fetch('toggle_favorite_author.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'authorID=' + encodeURIComponent(authorID)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // Revert visual state on error
            if (isFavorited) {
                button.classList.add('favorited');
                icon.className = 'fas fa-heart';
            } else {
                button.classList.remove('favorited');
                icon.className = 'far fa-heart';
            }
            alert('Error updating favorite: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revert visual state on error
        if (isFavorited) {
            button.classList.add('favorited');
            icon.className = 'fas fa-heart';
        } else {
            button.classList.remove('favorited');
            icon.className = 'far fa-heart';
        }
        alert('An error occurred while updating favorite.');
    });
}

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
    
    let url = 'authors.php?';
    if (searchQuery) url += 'search=' + encodeURIComponent(searchQuery) + '&';
    if (department) url += 'department=' + encodeURIComponent(department) + '&';
    
    // Remove trailing &
    url = url.replace(/&$/, '');
    
    window.location.href = url;
}

// Clear filters
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('departmentFilter').value = '';
    window.location.href = 'authors.php';
}

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const departmentFilter = document.getElementById('departmentFilter');
    
    // Search on Enter key
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
        
        // Filter authors on client side for instant feedback
        searchInput.addEventListener('input', function() {
            filterAuthors();
        });
    }
    
    // Filter on department change
    if (departmentFilter) {
        departmentFilter.addEventListener('change', function() {
            filterAuthors();
        });
    }
    
    // Load current favorite states
    fetch('get_favorite_authors.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.favorites) {
            data.favorites.forEach(authorID => {
                const button = document.getElementById(`fav-${authorID}`);
                if (button) {
                    button.classList.add('favorited');
                    const icon = button.querySelector('i');
                    if (icon) icon.className = 'fas fa-heart'; // Filled heart for favorited
                }
            });

            // Set empty heart for non-favorited authors
            const allButtons = document.querySelectorAll('.favorite-btn');
            allButtons.forEach(button => {
                if (!button.classList.contains('favorited')) {
                    const icon = button.querySelector('i');
                    if (icon) icon.className = 'far fa-heart'; // Empty heart for not favorited
                }
            });
        }
    })
    .catch(error => {
        console.error('Error loading favorites:', error);
    });
});

// Filter authors function
function filterAuthors() {
    const searchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
    const departmentFilter = document.getElementById('departmentFilter').value.toLowerCase();
    
    const cards = document.querySelectorAll('#authorsGrid .author-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const firstName = card.getAttribute('data-firstname') || '';
        const studentID = card.getAttribute('data-studentid') || '';
        const department = card.getAttribute('data-department') || '';
        
        const matchesSearch = !searchQuery || 
            firstName.includes(searchQuery) || 
            studentID.includes(searchQuery);
        
        const matchesDepartment = !departmentFilter || department === departmentFilter;
        
        if (matchesSearch && matchesDepartment) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show empty state if no cards visible
    let noAuthors = document.querySelector('.no-authors');
    if (visibleCount === 0 && !noAuthors) {
        const grid = document.getElementById('authorsGrid');
        noAuthors = document.createElement('div');
        noAuthors.className = 'no-authors';
        noAuthors.innerHTML = '<p>No authors found.</p>';
        grid.appendChild(noAuthors);
    } else if (visibleCount > 0 && noAuthors) {
        noAuthors.remove();
    }
}
</script>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>
