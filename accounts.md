# DivineShield – Test Accounts

This document contains the default credentials for testing the role-based portals in the DivineShield system. All accounts share the same default password.

---

## 🔐 General Password
* **Password:** `admin123`

---

## 👥 Accounts Registry

### 1. Administrator
* **Username:** `admin`
* **Role:** `admin` (Full system access)
* **Secondary Verification (MFA PIN):** `1234`
* **Email:** `admin@mainpi.org`
* **Status:** `active`

### 2. Staff / Encoder
* **Username:** `encoder1`
* **Role:** `staff` (Nutritional data entry and verification)
* **Email:** `maria.encoder@mainpi.org`
* **Status:** `active`

### 3. Church Leader (Active)
* **Username:** `pastor_juan`
* **Role:** `church_leader` (Submit and track children beneficiaries)
* **Email:** `juan.delacruz@church.org`
* **Status:** `active`
* **Seeded Church Site:** `Grace Born-Again Church` (Batasan Hills, Quezon City, Metro Manila)

### 4. Church Leader (Pending Approval)
* **Username:** `pastor_pedro`
* **Role:** `church_leader` (Awaiting activation by the Admin)
* **Email:** `pedro.penduko@church.org`
* **Status:** `pending`

---

## 🛡️ Access Workflow

1. Go to `login.php`.
2. Enter the **Username** and **Password** (`admin123`).
3. **For Administrator (`admin`):**
   * After checking credentials, you will be redirected to Step 2: **MFA PIN Verification**.
   * Enter the digits `1` `2` `3` `4` to access the portal.
4. **For Staff & Active Church Leaders:**
   * You will log in directly.
5. **For Pending Church Leaders (`pastor_pedro`):**
   * The login attempt will be blocked with a notice stating the account is pending administrator activation.
