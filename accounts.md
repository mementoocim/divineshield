# DivineShield – Test Accounts

This document contains the default credentials for testing the role-based portals in the DivineShield system.

---

## 👥 Accounts Registry

### 1. Administrator
* **Username:** `admin`
* **Password:** `admin123`
* **Role:** `admin` (Full system access)
* **Secondary Verification (MFA PIN):** `1234`
* **Email:** `admin@mainpi.org`
* **Status:** `active`

### 2. Staff / Encoder
* **Username:** `encoder1`
* **Password:** `admin123`
* **Role:** `staff` (Nutritional data entry and verification)
* **Email:** `maria.encoder@mainpi.org`
* **Status:** `active`

### 3. Church Leader
* **Username:** `rina123`
* **Password:** `rina123`
* **Role:** `church_leader` (Submit and track children beneficiaries)
* **Status:** `active`

---

## 🛡️ Access Workflow

1. Go to `login.php`.
2. Click any row in the **Testing Credentials** helper panel at the bottom to auto-fill the credentials.
3. Click **Verify Credentials**.
4. **For Administrator (`admin`):**
   * After checking credentials, you will be redirected to Step 2: **MFA PIN Verification**.
   * Enter the digits `1` `2` `3` `4` to access the portal.
5. **For Staff & Active Church Leaders:**
   * You will log in directly.
