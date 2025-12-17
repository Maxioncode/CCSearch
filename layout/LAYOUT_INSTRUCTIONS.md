# Layout System Instructions

## Overview

This layout system provides a **consistent, fixed layout structure** across all pages to prevent layout shifts when navigating between pages. The layout is optimized for **1440×900 resolution** and uses modern CSS (Flexbox) for a stable, centered design.

## Files Created

1. **`layout/layout.php`** - Header template with sidebar and main content structure
2. **`layout/layout_footer.php`** - Footer template that closes the layout
3. **`layout/layout.css`** - Main stylesheet with fixed dimensions and consistent styling

## Key Features

✅ **Fixed Sidebar** - 240px width, always visible, doesn't change size  
✅ **Fixed Main Content Area** - Consistent width and height across all pages  
✅ **Auto-scroll** - Content area scrolls independently if content is long  
✅ **Consistent Spacing** - Same padding, margins, and container sizes on every page  
✅ **No Layout Shifts** - Switching pages maintains the same layout position  

## How to Apply the Layout to Your Pages

### Step 1: Set Layout Variables

At the top of your PHP file (after session/database includes), set these variables:

```php
<?php
session_start();
include "../database/database.php";

// ... your existing code ...

// Set layout variables
$pageTitle = 'Your Page Title';
$activeNav = 'home'; // Options: 'home', 'profile', 'library', 'publication', 'authors', 'notification'
$additionalCSS = ['path/to/your/page.css']; // Optional: array of additional CSS files
```

### Step 2: Include Layout Header

Replace your existing HTML structure with:

```php
// Include layout header
include "../layout/layout.php";
```

### Step 3: Add Your Page Content

Add your page-specific content between the layout includes:

```php
<!-- Your page content here -->
<div class="content-section">
    <h2>Your Content</h2>
    <!-- ... -->
</div>
```

### Step 4: Include Layout Footer

At the end of your file, before any closing tags:

```php
// Include layout footer
include "../layout/layout_footer.php";
```

## Complete Example

Here's a complete example of a page using the layout:

```php
<?php
session_start();
include "../database/database.php";

// Redirect to login if not logged in
if (!isset($_SESSION['studentID'])) {
    header("Location: ../login/login.html");
    exit();
}

// Your page logic here...

// Set layout variables
$pageTitle = 'My Page';
$activeNav = 'home';
$additionalCSS = ['myfolder/mypage.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Your page content -->
<div class="content-section">
    <div class="page-header">
        <h1>Page Title</h1>
    </div>
    
    <div class="content-box">
        <!-- Your content here -->
    </div>
</div>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>
```

## Available CSS Classes

### Layout Classes

- `.content-container` - Main content wrapper (already included, auto-scrolls)
- `.content-section` - White box container for your content
- `.page-header` - Header bar with title and actions

### Utility Classes

- `.card-grid` - Grid layout for cards (auto-responsive)
- `.card` - Card component with hover effects
- `.btn`, `.btn-primary`, `.btn-secondary` - Button styles

## Updating Existing Pages

### For `home.php`:

1. Remove the old HTML structure (DOCTYPE, head, body, sidebar, etc.)
2. Keep your PHP logic at the top
3. Set `$pageTitle = 'CCSearch Dashboard'` and `$activeNav = 'home'`
4. Include `layout.php` before your content
5. Wrap your content in `.content-section` divs
6. Include `layout_footer.php` at the end

### For `authors.php`, `publication.php`, etc.:

Follow the same pattern:
1. Remove old HTML structure
2. Set layout variables
3. Include layout files
4. Wrap content appropriately

## CSS Customization

### Adding Page-Specific Styles

Create a separate CSS file for each page (e.g., `home_layout.css`, `authors_layout.css`) and include it via `$additionalCSS`:

```php
$additionalCSS = ['home/home_layout.css'];
```

### Overriding Layout Styles

The layout CSS uses specific selectors. To override, use more specific selectors in your page CSS:

```css
/* In your page CSS file */
.content-section {
    /* Your custom styles */
}
```

## Active Navigation

Set the `$activeNav` variable to highlight the current page in the sidebar:

- `'home'` - Home page
- `'profile'` - Profile page
- `'library'` - My Library page
- `'publication'` - Publication page
- `'authors'` - Authors page
- `'notification'` - Notification page

## Troubleshooting

### Layout Still Shifts?

1. **Check container heights** - Make sure you're not setting fixed heights on content containers
2. **Check margins/padding** - Use the provided classes instead of custom spacing
3. **Check CSS conflicts** - Make sure your page CSS doesn't override layout dimensions

### Sidebar Not Fixed?

1. Check that `layout.css` is loaded
2. Verify the sidebar has `position: fixed` in the CSS
3. Check browser console for CSS errors

### Content Not Scrolling?

1. Make sure content is inside `.content-container`
2. Check that `overflow-y: auto` is applied (it should be by default)
3. Verify content height exceeds container height

## Example Files

Reference these example files:

- `home/home_new.php` - Example home page using layout
- `authors/authors_new.php` - Example authors page using layout
- `upload/upload.php` - Example upload page using layout

## Migration Checklist

For each page you want to migrate:

- [ ] Remove old HTML structure (DOCTYPE, head, body tags)
- [ ] Keep PHP logic at the top
- [ ] Set `$pageTitle` variable
- [ ] Set `$activeNav` variable
- [ ] Include `layout.php`
- [ ] Wrap content in appropriate divs
- [ ] Include `layout_footer.php`
- [ ] Test navigation between pages
- [ ] Verify no layout shifts occur

## Support

If you encounter issues:

1. Check browser console for errors
2. Verify all file paths are correct
3. Ensure PHP includes are working
4. Check that CSS files are loading

---

**Remember**: The goal is consistency. All pages should use the same layout structure to prevent any visual shifts when navigating.

