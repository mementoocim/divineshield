---
name: divineshield-design
description: Design system and HTML/CSS component guidelines for the DivineShield project. Use this skill when building or modifying user interfaces to ensure consistency.
---

# DivineShield Design System Guidelines

When building or updating user interfaces within the DivineShield application, you must strictly adhere to the following HTML structures and CSS classes. This ensures visual consistency across all portals (Admin, Staff, and Church Leader).

## 1. Pill Tabs Navigation
Use pill tabs for sub-navigation or filtering data lists instead of standard underline links. 

```html
<div class="pill-tabs" style="margin-bottom: 20px;">
    <a href="?tab=pending" class="pill-tab active" style="text-decoration:none;">
        <i class="fas fa-clock"></i> Pending
    </a>
    <a href="?tab=approved" class="pill-tab" style="text-decoration:none;">
        <i class="fas fa-check-circle"></i> Approved
    </a>
</div>
```

## 2. Standard Data Tables
Always wrap data tables in a `.dark-table-wrap` container and apply the `.dark-table` class.

```html
<div class="dark-table-wrap">
    <table class="dark-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Status</th>
                <th class="text-right">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="fw-semibold text-white">John Doe</td>
                <td><span class="status-badge success"><i class="fas fa-check"></i> Active</span></td>
                <td class="text-right">
                    <a href="#" class="btn btn-primary btn-sm">View</a>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

## 3. Universal Empty State
Whenever a table or list has no data to display, replace it with this universal empty state. Make sure to adjust the icon, title, and description text to match context.

```html
<div class="empty-state" style="padding: 40px; text-align: center;">
    <i class="fas fa-question-circle empty-icon" style="font-size: 3rem; color:var(--gray-500); margin-bottom: 16px;"></i>
    <h4 style="color: var(--white); margin-bottom: 8px;">Nothing to display</h4>
</div>
```

## 4. Dashboard Cards (Main Table/Content Containers)
Main content sections, especially lists and tables, should be wrapped in a `.dashboard-card` instead of generic panels. If you are using `.pill-tabs`, they must be placed **outside** and above the card container.

```html
<!-- Pill tabs float outside the card -->
<div class="pill-tabs" style="margin-bottom: 24px;">
    <a href="?tab=val1" class="pill-tab active">Tab 1</a>
</div>

<!-- Main Table Card -->
<div class="dashboard-card">
    <div class="dashboard-card-header">
        <div class="dashboard-card-title">
            <i class="fas fa-icon" style="color:var(--blue-400);"></i> Section Title
        </div>
        <!-- Optional status badge inside header -->
        <span style="font-size:0.75rem; background:rgba(255,255,255,0.05); padding:4px 10px; border-radius:999px;">Badge</span>
    </div>
    
    <div class="dark-table-wrap">
        <table class="dark-table">
            <!-- Table content -->
        </table>
    </div>
</div>
```

## 5. Alerts & Notifications
Use these predefined alert boxes to display success or error messages.

**Success Alert:**
```html
<div class="auth-alert auth-alert-success" style="margin-bottom:24px;">
    <i class="fas fa-circle-check"></i>
    <div><strong>Success</strong> <span>Your changes have been saved.</span></div>
</div>
```

**Error Alert:**
```html
<div class="auth-alert auth-alert-danger" style="margin-bottom:24px;">
    <i class="fas fa-circle-exclamation"></i>
    <div><strong>Error</strong> <span>Something went wrong!</span></div>
</div>
```

## 6. Status Badges
Used inside tables or detail views to indicate the state of a record.

```html
<span class="status-badge success"><i class="fas fa-check-circle"></i> Active / Qualified</span>
<span class="status-badge error"><i class="fas fa-times-circle"></i> Inactive / Disqualified</span>
<span class="status-badge warning"><i class="fas fa-exclamation-circle"></i> Pending</span>
```

## 7. Action & Row Buttons
Standardize all interface action buttons by using standard utility class variants.

* **Primary Action:** Solid blue button.
  ```html
  <button class="btn btn-primary"><i class="fas fa-plus"></i> Standard Action</button>
  ```
* **Secondary/Back Action:** Translucent white outline. Used for "Close View", "Return", "Back to...", or "Cancel" actions in headers or forms.
  ```html
  <!-- Inside card/panel headers, always use the btn-sm modifier -->
  <a href="#" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Close View</a>
  <a href="#" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Cancel</a>
  ```
* **Success Actions:** Translucent glassmorphic green button (e.g. Approve, Complete, Reactivate).
  ```html
  <a href="#" class="btn btn-success"><i class="fas fa-check"></i> Approve</a>
  ```
* **Danger Actions:** Translucent glassmorphic red button (e.g. Reject, Cancel, Delete).
  ```html
  <a href="#" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</a>
  ```
* **Info/View Actions:** Translucent glassmorphic blue button (e.g. View Profile, Take Attendance).
  ```html
  <a href="#" class="btn btn-info"><i class="fas fa-eye"></i> View Details</a>
  ```
* **Table Row Size Modifier:** Always append the `.btn-sm` class modifier to shrink padding and font-size for table actions.
  ```html
  <a href="#" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> View</a>
  ```

### Important Design Rules
1. Never use inline styles for colors if a CSS variable exists (e.g. use `var(--blue-400)` instead of `#60a5fa`).
2. Dark themes rely heavily on subtle border colors. Use `rgba(255,255,255,0.1)` for borders and dividers instead of solid gray.
3. **Icon usage policy — critical:**
   - **No icons in titles.** Do NOT add `<i class="fas ...">` icons inside `.topbar-title`, `.dashboard-card-title`, `.detail-card-title`, or `.panel-title` elements. Titles must be plain text only.
   - **Icons belong in buttons.** Use FontAwesome icons inside `<button>` and `<a class="btn">` elements to give action context (e.g. `<i class="fas fa-plus"></i> Add Item`).
   - **Icons belong in badges, alerts, and status indicators.** Status badges, auth alerts, and stat box icons are the only non-button places where icons are appropriate.
   - **Icons belong in informational list items.** Using subtle inline icons next to metadata (e.g. a calendar icon before a date) inside list items is acceptable.

```html
<!-- ✅ CORRECT: Icon in a button -->
<button class="btn btn-primary"><i class="fas fa-plus"></i> Add Record</button>

<!-- ✅ CORRECT: Icon in a status badge -->
<span class="status-badge success"><i class="fas fa-check-circle"></i> Qualified</span>

<!-- ✅ CORRECT: Icon in a stat box -->
<div class="stat-box-icon"><i class="fas fa-folder-open"></i></div>

<!-- ❌ WRONG: Icon in a card title -->
<div class="dashboard-card-title"><i class="fas fa-list"></i> Submissions</div>

<!-- ✅ CORRECT: Clean card title, no icon -->
<div class="dashboard-card-title">Submissions</div>

<!-- ❌ WRONG: Icon in topbar title -->
<div class="topbar-title"><i class="fas fa-chart-line"></i> Analytics</div>

<!-- ✅ CORRECT: Clean topbar title -->
<div class="topbar-title">Analytics</div>
```
