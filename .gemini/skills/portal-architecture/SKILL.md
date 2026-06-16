---
name: divineshield-portal-architecture
description: Structural architecture and layout integration guidelines for the DivineShield project. Use this skill when building or managing portals (e.g., Staff or Admin) to maintain a DRY codebase.
---

# DivineShield Portal Architecture Guidelines

This skill documents how portals are structured in the DivineShield system. It is critical for ensuring that global UI components (like sidebars and topbars) remain consistent without duplicating HTML wrapper code.

## 1. Directory Structure
When building a new portal (e.g., Staff or Admin), extract the layout into reusable components inside an `includes/` directory:

* `includes/header.php`: Contains the `<html>` head, `<div class="admin-layout">`, loads the sidebar, and includes the `<header class="admin-topbar">`.
* `includes/sidebar.php`: Contains the `<aside class="admin-sidebar">` and all navigation links.
* `includes/footer.php`: Closes the main content wrapper (`</div></main></div></body></html>`).

Any main page in that portal (e.g., `dashboard.php`) should only contain its unique core logic and content:
```php
<?php
// ... session logic ...
include 'includes/header.php';
?>

<!-- Unique page content here -->

<?php include 'includes/footer.php'; ?>
```

## 2. Topbar Profile Picture Logic
The `admin-topbar` (usually located inside `includes/header.php`) must dynamically fetch and display the logged-in user's profile picture.

**PHP Logic:**
Use the following logic before rendering the HTML to pull the picture from the database:
```php
// Fetch profile picture for topbar
$stmtProfile = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmtProfile->execute([$_SESSION['user_id']]);
$userProfilePic = $stmtProfile->fetchColumn();
```

**HTML Rendering Block:**
Render it using this HTML block to correctly support the design system's fallback:
```html
<div class="topbar-user">
  <div class="user-badge-group">
    <div class="user-badge-name"><?php echo htmlspecialchars($fullName); ?></div>
    <div class="user-badge-role">Role Name</div>
  </div>
  <?php if (!empty($userProfilePic) && file_exists('../../' . $userProfilePic)): ?>
    <img src="../../<?php echo htmlspecialchars($userProfilePic); ?>" alt="Profile" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.15);" />
  <?php else: ?>
    <!-- Fallback icon -->
    <div class="logo-mark small"><i class="fas fa-user"></i></div>
  <?php endif; ?>
</div>
```

## 3. Git Operations Guideline
* **CRITICAL RULE**: Do not automatically commit or push code changes (e.g. `git commit`, `git push`) until the user explicitly requests or gives permission to do so. Always wait for user instruction first.
