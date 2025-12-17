# Quick Start Guide - Layout System

## 3-Step Setup

### 1. Set Variables (at top of your PHP file)
```php
$pageTitle = 'Page Title';
$activeNav = 'home'; // Change to match your page
$additionalCSS = ['yourfolder/yourpage.css']; // Optional
```

### 2. Include Layout Header
```php
include "../layout/layout.php";
```

### 3. Add Content & Footer
```php
<!-- Your content here -->
<div class="content-section">
    <!-- Page content -->
</div>

<?php include "../layout/layout_footer.php"; ?>
```

## Active Nav Options
- `'home'`
- `'profile'`
- `'library'`
- `'publication'`
- `'authors'`
- `'notification'`

## That's It!

Your page will now have:
- ✅ Fixed sidebar (240px)
- ✅ Consistent main content area
- ✅ Auto-scrolling content
- ✅ No layout shifts when navigating

See `LAYOUT_INSTRUCTIONS.md` for detailed documentation.

