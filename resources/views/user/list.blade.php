@extends('layouts.app')
@section('title', 'User Management')

@section('content')
<div class="user-container">
    <!-- Header Section -->
    <div class="user-header">
        <h2 class="user-title">User Management</h2>
        <button class="btn-add-user" id="addUserBtn">
            <span>Add User</span>
        </button>
    </div>

    <!-- Search Box -->
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search by name, email or role...">
        <i class="bi bi-search"></i>
    </div>

    <!-- Table/Card Container -->
    <div class="user-table-wrapper">
        <!-- Desktop Table View -->
        <table class="user-table" id="userTable">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Image</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="loading-row">
                    <td colspan="7" class="loading-cell">
                        <div class="spinner"></div>
                        <p>Loading users...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="user-cards-wrapper" id="userCardsContainer">
        <div class="loading-card">
            <div class="spinner"></div>
            <p>Loading users...</p>
        </div>
    </div>

    <!-- Total User Count -->
    <div class="total-user-count">
        Total Users: <strong id="totalUserCount">0</strong>
    </div>

    <!-- Pagination -->
    <div class="user-pagination">
        <div class="pagination-info">
            <span id="paginationInfo">Showing 0 of 0</span>
        </div>
        <div class="pagination-buttons">
            <button class="pagination-btn" id="prevBtn">← Previous</button>
            <span class="page-info" id="pageInfo">Page 1</span>
            <button class="pagination-btn" id="nextBtn">Next →</button>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="userForm" enctype="multipart/form-data">
                    <input type="hidden" id="userId" name="ID">

                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <div class="form-group" style="flex:1 1 180px; min-width:0;">
                            <label class="form-label">First Name <span class="required">*</span></label>
                            <input type="text" class="form-control" id="firstName" name="FirstName" required>
                            <small class="error-message"></small>
                        </div>

                        <div class="form-group" style="flex:1 1 180px; min-width:0;">
                            <label class="form-label">Last Name <span class="required">*</span></label>
                            <input type="text" class="form-control" id="lastName" name="LastName" required>
                            <small class="error-message"></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mobile Number <span class="required">*</span></label>
                        <input type="tel" class="form-control" id="mobileNo" name="MobileNo" maxlength="15" inputmode="numeric" placeholder="10-digit mobile number" required>
                        <small class="text-muted">Login OTP isi number par aayega.</small>
                        <small class="error-message"></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email <small class="text-muted">(Optional)</small></label>
                        <input type="email" class="form-control" id="emailID" name="EmailID" placeholder="name@example.com">
                        <small class="error-message"></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role <span class="required">*</span></label>
                        <select class="form-control" id="userRole" name="user_role" required>
                            <option value="">Select Role</option>
                            <option value="1">Admin</option>
                            <option value="0">User</option>
                        </select>
                        <small class="error-message"></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Profile Image <span class="required create-only">*</span></label>
                        <input type="file" class="form-control" id="profileImage" name="profileImage" accept="image/jpeg,image/png,image/webp,image/gif">
                        <small class="text-muted" id="imageHint">JPG, PNG, WEBP or GIF, max 5 MB. Required for new users.</small>
                        <small class="error-message"></small>
                        <div class="profile-preview-wrap">
                            <img id="imagePreview" src="" alt="" class="profile-preview" style="display:none;" onerror="this.onerror=null;this.src=window.DEFAULT_AVATAR;">
                            <small class="image-missing-note" id="imageMissingNote" style="display:none;">Saved image file was not found on the server. Please upload the image again.</small>
                        </div>
                    </div>

                    <!-- User Access: Modules + Module Fields + Locations -->
                    <div class="form-group mt-4" id="fieldPermissionSection">
                        <input type="hidden" name="access_submitted" value="1">

                        <!-- 1. Modules -->
                        <div class="access-panel">
                            <div class="access-panel-head">
                                <div>
                                    <h6 class="mb-0">Module Access</h6>
                                    <small class="text-muted">Select the dashboard modules this user can open.</small>
                                </div>
                                <label class="access-select-all">
                                    <input type="checkbox" id="accessAllModules"> Select All
                                </label>
                            </div>
                            <div id="accessModulesBox" class="access-module-grid">
                                <div class="permission-empty">Loading modules...</div>
                            </div>
                        </div>

                        <!-- 2. Fields of selected modules (e.g. Item Stock) -->
                        <div id="accessFieldsBox"></div>

                        <!-- 3. Locations -->
                        <div class="access-panel">
                            <div class="access-panel-head">
                                <div>
                                    <h6 class="mb-0">Location Access</h6>
                                    <small class="text-muted">User will see data only for these locations (branch-wise).</small>
                                </div>
                                <label class="access-select-all">
                                    <input type="checkbox" id="accessAllLocations"> Select All
                                </label>
                            </div>
                            <input type="text" class="form-control form-control-sm mb-2" id="accessLocationSearch" placeholder="Search location...">
                            <div id="accessLocationsBox" class="access-location-grid">
                                <div class="permission-empty">Loading locations...</div>
                            </div>
                        </div>

                        <div class="access-summary" id="accessSummary"></div>
                    </div>

                    <div class="form-group mt-3" id="adminAccessNote" style="display:none;">
                        <div class="access-admin-note">
                            <i class="bi bi-shield-check"></i> Admin has access to all modules, fields and locations.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn-save" id="saveUserBtn">Save User</button>
            </div>
        </div>
    </div>
</div>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --primary-blue: #0052cc;
        --danger-red: #dc3545;
        --light-gray: #f5f5f5;
        --border-gray: #ddd;
        --text-dark: #333;
        --text-light: #666;
    }

    .user-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #f8f9fa 100%);
        min-height: 100vh;
        padding: 20px;
    }

    /* ========== HEADER ========== */
    .user-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        gap: 15px;
    }

    .user-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .btn-add-user {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 22px;
        background: var(--primary-blue);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .btn-add-user:hover {
        background: #0045b3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 82, 204, 0.25);
    }

    .btn-add-user i {
        font-size: 18px;
    }

    /* ========== SEARCH BOX ========== */
    .search-box {
        position: relative;
        margin-bottom: 25px;
    }

    .search-box input {
        width: 100%;
        padding: 12px 15px 12px 40px;
        border: 1px solid var(--border-gray);
        border-radius: 6px;
        font-size: 14px;
        background: white;
        transition: all 0.2s ease;
    }

    .search-box input:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
    }

    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-light);
        font-size: 16px;
    }

    /* ========== TABLE ========== */
    .user-table-wrapper {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-gray);
        margin-bottom: 20px;
    }

    .user-table {
        width: 100%;
        border-collapse: collapse;
        display: table;
        table-layout: fixed;
    }

    .user-table thead {
        background-color: var(--light-gray);
        border-bottom: 2px solid var(--border-gray);
    }

    .user-table thead th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        color: var(--text-dark);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .user-table thead th:nth-child(1) { width: 70px; }
    .user-table thead th:nth-child(2) { width: 200px; }
    .user-table thead th:nth-child(3) { width: 230px; }
    .user-table thead th:nth-child(4) { width: 130px; }
    .user-table thead th:nth-child(5) { width: 90px; }
    .user-table thead th:nth-child(6) { width: 90px; }
    .user-table thead th:nth-child(7) { width: 130px; }

    .user-table tbody td:nth-child(2) { white-space: normal; word-break: break-word; }
    .user-table tbody td:nth-child(3) { word-break: break-all; }

    .user-table tbody tr {
        border-bottom: 1px solid var(--border-gray);
        transition: background-color 0.2s ease;
    }

    .user-table tbody tr:hover {
        background-color: rgba(0, 82, 204, 0.02);
    }

    .user-table tbody td {
        padding: 14px 15px;
        font-size: 14px;
        color: var(--text-dark);
        vertical-align: middle;
    }

    .loading-row {
        text-align: center;
    }

    .loading-cell {
        padding: 40px 15px !important;
    }

    .spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto 10px;
        border: 3px solid var(--light-gray);
        border-top-color: var(--primary-blue);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Badge Styles */
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-admin {
        background: #dc3545;
        color: white;
    }

    .badge-user {
        background: #6c757d;
        color: white;
    }

    /* User Image */
    .user-image {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 50%;
        object-fit: cover;
        object-position: center;
        border: 2px solid var(--border-gray);
        background: #f1f3f6;
        display: inline-block;
        vertical-align: middle;
    }

    .profile-preview-wrap {
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .image-missing-note {
        color: #b26a00;
        font-size: 12px;
        font-weight: 600;
    }

    .profile-preview {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        object-fit: cover;
        object-position: center;
        border: 3px solid #e3e7ee;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        background: #f1f3f6;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 6px;
        justify-content: center;
        align-items: center;
    }

    .btn-icon {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s ease;
        text-decoration: none;
        background: white;
        flex-shrink: 0;
    }

    .btn-icon.edit {
        color: var(--primary-blue);
        border-color: var(--primary-blue);
    }

    .btn-icon.edit:hover {
        background: var(--primary-blue);
        color: white;
    }

    .btn-icon.delete {
        color: var(--danger-red);
        border-color: var(--danger-red);
    }

    .btn-icon.delete:hover {
        background: var(--danger-red);
        color: white;
    }

    /* ========== MOBILE CARDS ========== */
    .user-cards-wrapper {
        display: none;
        gap: 15px;
        margin-bottom: 20px;
    }

    .user-card {
        background: white;
        border-radius: 8px;
        padding: 15px;
        border-left: 4px solid var(--primary-blue);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .card-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding: 8px 0;
        border-bottom: 1px solid var(--light-gray);
    }

    .card-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .card-label {
        font-weight: 600;
        color: var(--text-light);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-value {
        color: var(--text-dark);
        font-size: 14px;
        font-weight: 500;
        text-align: right;
    }

    .loading-card {
        text-align: center;
        padding: 40px 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .loading-card .spinner {
        margin: 0 auto 15px;
    }

    .loading-card p {
        color: var(--text-light);
        font-size: 14px;
    }


    .total-user-count {
        background: white;
        padding: 12px 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
        font-size: 15px;
        font-weight: 600;
        color: #333;
    }

    .total-user-count strong {
        color: #0052cc;
        font-size: 18px;
    }

    /* ========== PAGINATION ========== */
    .user-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .pagination-info {
        font-size: 13px;
        color: var(--text-light);
    }

    .pagination-buttons {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .pagination-btn {
        padding: 8px 16px;
        background: var(--primary-blue);
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .pagination-btn:hover:not(:disabled) {
        background: #0045b3;
    }

    .pagination-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .page-info {
        font-size: 13px;
        color: var(--text-dark);
        font-weight: 600;
        min-width: 70px;
        text-align: center;
    }

    /* ========== MODAL ========== */
    .modal-header {
        background: var(--primary-blue);
        color: white;
        border: none;
    }

    .modal-title {
        font-weight: 600;
    }

    .btn-close {
        filter: brightness(0) invert(1);
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-label {
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 8px;
        display: block;
        color: var(--text-dark);
    }

    .required {
        color: var(--danger-red);
    }

    .form-control {
        padding: 10px 12px;
        border: 1px solid var(--border-gray);
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.2s ease;
        width: 100%;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
    }

    .form-control.error {
        border-color: var(--danger-red);
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }

    .error-message {
        color: var(--danger-red);
        font-size: 12px;
        display: none;
    }

    .error-message.show {
        display: block;
        margin-top: 4px;
    }

    .text-muted {
        color: var(--text-light);
        font-size: 12px;
    }

    .modal-footer {
        border-top: 1px solid var(--border-gray);
        padding: 15px;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .btn-cancel {
        padding: 10px 20px;
        background: white;
        color: var(--text-light);
        border: 1px solid var(--border-gray);
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .btn-cancel:hover {
        background: var(--light-gray);
    }

    .btn-save {
        padding: 10px 20px;
        background: var(--primary-blue);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .btn-save:hover {
        background: #0045b3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 82, 204, 0.25);
    }

    /* ========== TABLET ========== */
    @media (max-width: 1024px) {
        .user-container {
            padding: 15px;
        }

        .user-header {
            flex-direction: column;
            align-items: stretch;
            margin-bottom: 20px;
        }

        .btn-add-user {
            width: 100%;
            justify-content: center;
        }

        .user-title {
            font-size: 24px;
        }
    }

    /* ========== MOBILE ========== */
    @media (max-width: 768px) {
        .user-container {
            padding: 12px;
        }

        .user-header {
            gap: 10px;
            margin-bottom: 20px;
        }

        .user-title {
            font-size: 20px;
        }

        .btn-add-user {
            width: 100%;
            justify-content: center;
            padding: 11px;
            font-size: 13px;
        }

        .search-box input {
            padding: 11px 15px 11px 38px;
            font-size: 13px;
        }

        /* Hide desktop table */
        .user-table-wrapper {
            display: none;
        }

        /* Show mobile cards */
        .user-cards-wrapper {
            display: flex;
            flex-direction: column;
        }

        .user-card {
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .user-pagination {
            flex-direction: column;
            gap: 15px;
        }

        .pagination-buttons {
            width: 100%;
        }

        .pagination-btn {
            flex: 1;
            font-size: 11px;
            padding: 7px 12px;
        }

        .pagination-info {
            width: 100%;
            text-align: center;
        }

        .page-info {
            width: 100%;
        }

        .modal-dialog {
            margin: 10px !important;
        }

        .modal-body {
            padding: 15px;
        }

        .form-group {
            margin-bottom: 12px;
        }
    }

    /* ========== SMALL MOBILE ========== */
    @media (max-width: 480px) {
        .user-container {
            padding: 10px;
        }

        .user-title {
            font-size: 18px;
        }

        .btn-add-user {
            padding: 9px 12px;
            font-size: 12px;
        }

        .search-box input {
            padding: 10px 15px 10px 34px;
            font-size: 12px;
        }

        .user-card {
            padding: 12px;
        }

        .card-label {
            font-size: 10px;
        }

        .card-value {
            font-size: 12px;
        }

        .pagination-btn {
            font-size: 10px;
            padding: 6px 8px;
        }

        .pagination-info {
            font-size: 11px;
        }

        .form-label {
            font-size: 12px;
        }

        .form-control {
            font-size: 13px;
            padding: 8px 10px;
        }

        .modal-header {
            padding: 12px;
        }

        .modal-title {
            font-size: 16px;
        }

        .modal-body {
            padding: 12px;
        }

        .modal-footer {
            padding: 10px;
            gap: 8px;
        }

        .btn-cancel, .btn-save {
            padding: 8px 12px;
            font-size: 12px;
        }
    }

    .text-center {
        text-align: center;
    }

    .permission-panel {
        border: 1px solid var(--border-gray);
        border-radius: 8px;
        padding: 16px;
        background: #fafafa;
    }

    .permission-table-selector {
        border: 1px solid var(--border-gray);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }

    .permission-table-selector-head {
        padding: 10px 12px;
        background: var(--light-gray);
        border-bottom: 1px solid var(--border-gray);
        font-weight: 600;
        font-size: 13px;
    }

    .permission-table-grid {
        max-height: 220px;
        overflow-y: auto;
        padding: 12px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px 12px;
    }

    .permission-table-check {
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 7px 8px;
        border-radius: 5px;
        cursor: pointer;
        margin: 0;
        font-size: 13px;
        word-break: break-word;
    }

    .permission-table-check:hover {
        background: #f5f7fa;
    }

    .permission-fields-card {
        border: 1px solid var(--border-gray);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }

    .permission-fields-header {
        padding: 12px 14px;
        background: var(--light-gray);
        border-bottom: 1px solid var(--border-gray);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .permission-fields-grid {
        max-height: 300px;
        overflow-y: auto;
        padding: 12px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px 12px;
    }

    .permission-check {
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 7px 8px;
        border-radius: 5px;
        cursor: pointer;
        margin: 0;
        font-size: 13px;
    }

    .permission-actions {
        padding: 0 12px 12px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .selected-permission-card {
        border: 1px solid var(--border-gray);
        border-left: 4px solid var(--primary-blue);
        border-radius: 7px;
        background: #fff;
        margin-bottom: 10px;
    }

    .selected-permission-header {
        padding: 10px 12px;
        border-bottom: 1px solid var(--border-gray);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .selected-permission-body {
        padding: 10px 12px;
    }

    .permission-chip {
        display: inline-block;
        background: #eaf2ff;
        color: #003d99;
        border: 1px solid #cfe0ff;
        border-radius: 20px;
        padding: 5px 9px;
        font-size: 12px;
        margin: 3px;
    }

    .permission-empty {
        padding: 12px;
        border: 1px dashed var(--border-gray);
        border-radius: 6px;
        background: #fff;
        color: var(--text-light);
        font-size: 13px;
    }

    @media (max-width: 768px) {
        .permission-fields-grid,
        .permission-table-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 480px) {
        .permission-fields-grid,
        .permission-table-grid {
            grid-template-columns: 1fr;
        }
    }
    /* ---------- User Access (modules / fields / locations) ---------- */
    .access-panel {
        border: 1px solid #e3e7ee;
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 14px;
        background: #fbfcfe;
    }

    .access-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 12px;
    }

    .access-select-all {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
        cursor: pointer;
    }

    .access-module-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .access-module {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #dfe3ea;
        border-radius: 8px;
        padding: 8px 10px;
        background: #fff;
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
        margin: 0;
    }

    .access-module:hover {
        border-color: #0052cc;
    }

    .access-module.checked {
        border-color: #0052cc;
        box-shadow: 0 0 0 2px rgba(0, 82, 204, .15);
        background: #f3f7ff;
    }

    .access-module img {
        width: 38px;
        height: 38px;
        object-fit: cover;
        border-radius: 6px;
        flex: 0 0 38px;
        background: #f1f1f1;
    }

    .access-module span {
        font-size: 12px;
        font-weight: 600;
        line-height: 1.2;
    }

    .access-module input {
        margin-left: auto;
    }

    .access-fields-grid,
    .access-location-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    .access-location-grid {
        max-height: 220px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .access-check {
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #e3e7ee;
        border-radius: 6px;
        padding: 7px 10px;
        background: #fff;
        font-size: 13px;
        cursor: pointer;
        margin: 0;
    }

    .access-check small {
        color: #888;
        margin-left: auto;
    }

    .access-summary {
        font-size: 12px;
        color: #555;
    }

    .access-admin-note {
        background: #eef6ee;
        color: #2e7d32;
        border: 1px solid #cde5cd;
        border-radius: 6px;
        padding: 10px 12px;
        font-size: 13px;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .access-module-grid,
        .access-fields-grid,
        .access-location-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 480px) {
        .access-module-grid,
        .access-fields-grid,
        .access-location-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@push('scripts')
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let allData = [];
let currentPage = 1;
let pageSize = 10;
let totalRecords = 0;
let searchQuery = '';

let permissionData = {};

$(function() {
    // CSRF Token
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Load data
    loadUserData();

    // New user access (modules / fields / locations)
    loadAccessOptions();

    $('#userRole').on('change', function() {
        togglePermissionSection();
    });

    // Search
    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchQuery = $(this).val();
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadUserData();
        }, 300);
    });

    // Add User Button
    $('#addUserBtn').on('click', function() {
        resetForm();
        applyAccess({ modules: [], fields: {}, locations: [] });
        permissionData = {};
        renderSelectedPermissions();
        $('#permissionFieldsBox').html('');
        $('#selectAllPermissionTables').prop('checked', false);
        $('.permission-table-checkbox').prop('checked', false);
        $('#userModalLabel').text('Add User');
        $('#profileImage').prop('required', true);
        $('.create-only').show();
        $('#imageHint').text('JPG, PNG, WEBP or GIF, max 5 MB. Profile image is required.');
        $('#imageMissingNote').hide();
        togglePermissionSection();
        $('#userModal').modal('show');
    });

    // Edit User Button
    $(document).on('click', '.editBtn', function() {
        const id = $(this).data('id');

        $.ajax({
            url: '{{ url("/users") }}/' + id,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const user = response.data;

                    $('#userId').val(user.ID);
                    $('#firstName').val(user.FirstName);
                    $('#lastName').val(user.LastName);
                    $('#emailID').val(user.EmailID);
                    $('#mobileNo').val(user.MobileNo || '');
                    $('#userRole').val(user.user_role);

                    applyAccess(user.access || { modules: [], fields: {}, locations: [] });
                    togglePermissionSection();

                    // Current image (server checks the file exists).
                    // Missing file -> default avatar + a note to upload again.
                    showEditPreview(user);
                    $('.create-only').hide();
                    $('#imageHint').text('JPG, PNG, WEBP or GIF, max 5 MB. Choose a file only to change the image.');
                    $('#profileImage').prop('required', false);

                    $('#userModalLabel').text('Edit User');
                    $('#userModal').modal('show');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to load user data', 'error');
            }
        });
    });

    // Dynamic field permission events

    $(document).on('change', '#selectAllPermissionTables', function() {
        const checked = $(this).is(':checked');
        $('.permission-table-checkbox').prop('checked', checked);

        const tables = [];
        $('.permission-table-checkbox:checked').each(function() {
            tables.push($(this).val());
        });

        loadMultipleTableFields(tables);
    });

    $(document).on('change', '.permission-table-checkbox', function() {
        const tables = [];

        $('.permission-table-checkbox:checked').each(function() {
            tables.push($(this).val());
        });

        const total = $('.permission-table-checkbox').length;
        const checked = $('.permission-table-checkbox:checked').length;

        $('#selectAllPermissionTables').prop(
            'checked',
            total > 0 && total === checked
        );

        loadMultipleTableFields(tables);
    });

    $(document).on('change', '.select-all-fields-table', function() {
        const table = $(this).data('table');
        const checked = $(this).is(':checked');

        $('.permission-field-checkbox').filter(function() {
            return $(this).data('table') === table;
        }).prop('checked', checked);

        updatePermissionDataFromTable(table);
    });

    $(document).on('change', '.permission-field-checkbox', function() {
        const table = $(this).data('table');
        updatePermissionDataFromTable(table);
    });

    $(document).on('click', '.clearPermissionFields', function() {
        const table = $(this).data('table');

        delete permissionData[table];

        $('.permission-field-checkbox').filter(function() {
            return $(this).data('table') === table;
        }).prop('checked', false);

        $('.select-all-fields-table').filter(function() {
            return $(this).data('table') === table;
        }).prop('checked', false);

        renderSelectedPermissions();
    });

    $(document).on('click', '.editPermissionTable', function() {
        const table = $(this).data('table');

        $('.permission-table-checkbox').filter(function() {
            return $(this).val() === table;
        }).prop('checked', true);

        const tables = [];
        $('.permission-table-checkbox:checked').each(function() {
            tables.push($(this).val());
        });

        loadMultipleTableFields(tables);
    });

    $(document).on('click', '.removePermissionTable', function() {
        const table = $(this).data('table');

        delete permissionData[table];

        $('.permission-table-checkbox').filter(function() {
            return $(this).val() === table;
        }).prop('checked', false);

        renderSelectedPermissions();

        const tables = [];
        $('.permission-table-checkbox:checked').each(function() {
            tables.push($(this).val());
        });

        loadMultipleTableFields(tables);
    });

    // Save User
    $('#saveUserBtn').on('click', function() {
        if (!validateForm()) return;

        const formData = new FormData($('#userForm')[0]);

        $.ajax({
            url: '{{ route("users.save") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire('Success', response.message, 'success').then(() => {
                    $('#userModal').modal('hide');
                    currentPage = 1;
                    loadUserData();
                });
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        const $field = $('[name="' + key + '"]');
                        $field.addClass('error');
                        $field.next('.error-message').text(value[0]).addClass('show');
                    });
                } else {
                    Swal.fire('Error', 'Something went wrong', 'error');
                }
            }
        });
    });

    // Delete User
    $(document).on('click', '.deleteBtn', function() {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete User?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("/delete/users") }}/' + id,
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => {
                                loadUserData();
                            });
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to delete user', 'error');
                    }
                });
            }
        });
    });

    // Image Preview
    $('#profileImage').on('change', function() {
        const file = this.files[0];
        $(this).removeClass('error').siblings('.error-message').removeClass('show').text('');
        if (file && !/^image\/(jpeg|png|webp|gif)$/.test(file.type)) {
            markError('#profileImage', 'Please choose a JPG, PNG, WEBP or GIF image.');
            this.value = '';
            return;
        }
        if (file && file.size > 5 * 1024 * 1024) {
            markError('#profileImage', 'Image must be less than 5 MB.');
            this.value = '';
            return;
        }
        if (file) {
            $('#imageMissingNote').hide();
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });

    // Pagination
    $('#prevBtn').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            loadUserData();
            window.scrollTo(0, 0);
        }
    });

    $('#nextBtn').on('click', function() {
        if (currentPage < Math.ceil(totalRecords / pageSize)) {
            currentPage++;
            loadUserData();
            window.scrollTo(0, 0);
        }
    });
});


function loadPermissionTables() {
    $('#permissionTablesBox').html('<div class="permission-empty">Loading tables...</div>');

    $.ajax({
        url: '{{ route("users.permission.tables") }}',
        type: 'GET',
        success: function(response) {
            if (!response.status || !Array.isArray(response.data) || response.data.length === 0) {
                $('#permissionTablesBox').html('<div class="permission-empty">No tables found.</div>');
                return;
            }

            let html = '';

            response.data.forEach(function(table) {
                html += `
                    <label class="permission-table-check">
                        <input
                            type="checkbox"
                            class="permission-table-checkbox"
                            value="${escapeHtml(table)}"
                        >
                        <span>${escapeHtml(table)}</span>
                    </label>
                `;
            });

            $('#permissionTablesBox').html(html);
            syncSelectedTableCheckboxes();
        },
        error: function(xhr) {
            $('#permissionTablesBox').html(
                '<div class="permission-empty text-danger">Unable to load tables.</div>'
            );
            console.error(xhr.responseText);
        }
    });
}

function loadMultipleTableFields(tables) {
    $('#permissionFieldsBox').html('');

    if (!Array.isArray(tables) || tables.length === 0) {
        return;
    }

    tables.forEach(function(table) {
        loadSingleTableFields(table);
    });
}

function loadSingleTableFields(table) {
    const safeId = makeSafeId(table);

    $('#permissionFieldsBox').append(`
        <div class="permission-fields-card mb-3" id="permission-card-${safeId}">
            <div class="permission-empty">Loading ${escapeHtml(table)} fields...</div>
        </div>
    `);

    $.ajax({
        url: '{{ route("users.permission.fields") }}',
        type: 'GET',
        data: { table: table },

        success: function(response) {
            if (!response.status || !Array.isArray(response.fields)) {
                $('#permission-card-' + safeId).html(
                    '<div class="permission-empty">No fields found.</div>'
                );
                return;
            }

            const selectedFields = permissionData[table] || [];
            const allChecked =
                response.fields.length > 0 &&
                response.fields.every(function(field) {
                    return selectedFields.includes(field);
                });

            let html = `
                <div class="permission-fields-header">
                    <strong>${escapeHtml(table)}</strong>

                    <label class="mb-0">
                        <input
                            type="checkbox"
                            class="select-all-fields-table"
                            data-table="${escapeHtml(table)}"
                            ${allChecked ? 'checked' : ''}
                        >
                        Select All Fields
                    </label>
                </div>

                <div class="permission-fields-grid">
            `;

            response.fields.forEach(function(field) {
                const checked = selectedFields.includes(field) ? 'checked' : '';

                html += `
                    <label class="permission-check">
                        <input
                            type="checkbox"
                            class="permission-field-checkbox"
                            data-table="${escapeHtml(table)}"
                            value="${escapeHtml(field)}"
                            ${checked}
                        >
                        <span>${escapeHtml(field)}</span>
                    </label>
                `;
            });

            html += `
                </div>

                <div class="permission-actions">
                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm clearPermissionFields"
                        data-table="${escapeHtml(table)}"
                    >
                        Clear ${escapeHtml(table)}
                    </button>
                </div>
            `;

            $('#permission-card-' + safeId).html(html);
        },

        error: function(xhr) {
            $('#permission-card-' + safeId).html(
                '<div class="permission-empty text-danger">Unable to load fields.</div>'
            );
            console.error(xhr.responseText);
        }
    });
}

function updatePermissionDataFromTable(table) {
    const fields = [];

    $('.permission-field-checkbox').filter(function() {
        return $(this).data('table') === table && $(this).is(':checked');
    }).each(function() {
        fields.push($(this).val());
    });

    if (fields.length > 0) {
        permissionData[table] = fields;
    } else {
        delete permissionData[table];
    }

    const allForTable = $('.permission-field-checkbox').filter(function() {
        return $(this).data('table') === table;
    });

    const checkedForTable = allForTable.filter(':checked');

    $('.select-all-fields-table').filter(function() {
        return $(this).data('table') === table;
    }).prop(
        'checked',
        allForTable.length > 0 && allForTable.length === checkedForTable.length
    );

    renderSelectedPermissions();
}

function syncSelectedTableCheckboxes() {
    $('.permission-table-checkbox').each(function() {
        const table = $(this).val();

        $(this).prop(
            'checked',
            Object.prototype.hasOwnProperty.call(permissionData, table)
        );
    });

    const total = $('.permission-table-checkbox').length;
    const checked = $('.permission-table-checkbox:checked').length;

    $('#selectAllPermissionTables').prop(
        'checked',
        total > 0 && total === checked
    );
}

function makeSafeId(value) {
    return String(value ?? '').replace(/[^a-zA-Z0-9_-]/g, '_');
}

function loadExistingPermissions(userId) {
    permissionData = {};
    renderSelectedPermissions();

    $.ajax({
        url: '{{ url("/users") }}/' + userId + '/field-permissions',
        type: 'GET',
        success: function(response) {
            permissionData = (response.status && response.data) ? response.data : {};

            renderSelectedPermissions();
            syncSelectedTableCheckboxes();
            loadMultipleTableFields(Object.keys(permissionData));
        },
        error: function(xhr) {
            permissionData = {};
            renderSelectedPermissions();
            console.error(xhr.responseText);
        }
    });
}

function renderSelectedPermissions() {
    let html = '';
    let hasPermissions = false;

    Object.keys(permissionData).sort().forEach(function(table) {
        const fields = permissionData[table];
        if (!Array.isArray(fields) || !fields.length) return;

        hasPermissions = true;

        html += `
            <div class="selected-permission-card">
                <div class="selected-permission-header">
                    <strong>${escapeHtml(table)}</strong>
                    <div>
                        <button type="button"
                                class="btn btn-sm btn-outline-primary editPermissionTable"
                                data-table="${escapeHtml(table)}">Edit</button>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger removePermissionTable"
                                data-table="${escapeHtml(table)}">Remove</button>
                    </div>
                </div>
                <div class="selected-permission-body">
        `;

        fields.forEach(function(field) {
            html += `
                <span class="permission-chip">${escapeHtml(field)}</span>
                <input type="hidden"
                       name="field_permissions[${escapeHtml(table)}][]"
                       value="${escapeHtml(field)}">
            `;
        });

        html += `</div></div>`;
    });

    if (!hasPermissions) {
        html = '<div class="permission-empty">No field permissions selected.</div>';
    }

    $('#selectedPermissionsBox').html(html);
}

// Image URLs that also work when the app runs in a sub-folder
const ASSET_BASE = '{{ rtrim(asset(''), '/') }}';
const DEFAULT_AVATAR = ASSET_BASE + '/assets/images/default-avatar.svg';
window.DEFAULT_AVATAR = DEFAULT_AVATAR; // used by inline onerror handlers

function userImageUrl(path) {
    if (!path) return DEFAULT_AVATAR;
    path = String(path);
    if (/^(https?:)?\/\//.test(path) || path.startsWith('data:')) return path;
    return ASSET_BASE + '/' + path.replace(/^\/+/, '');
}

/**
 * Edit preview: load the image in the background first. If it fails
 * (file missing / wrong URL) fall back to the list thumbnail, then to
 * the default avatar, then to the user's initials - never a broken icon.
 */
function showEditPreview(user) {
    const $img = $('#imagePreview');
    const listThumb = $(`.user-image[data-user-id="${user.ID}"]`).attr('src');
    const candidates = [
        user.profileImageUrl,
        user.profileImage ? userImageUrl(user.profileImage) : null,
        listThumb,
        DEFAULT_AVATAR
    ].filter(function(u, i, arr) { return u && arr.indexOf(u) === i; });

    $('#imageMissingNote').hide();
    $img.hide();

    (function tryNext(i) {
        if (i >= candidates.length) {
            // nothing loads -> initials
            const initials = ((user.FirstName || '?')[0] + (user.LastName || '')[0] || '').toUpperCase();
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="90" height="90"><rect width="90" height="90" fill="#e7efff"/><text x="50%" y="55%" font-family="Arial" font-size="32" font-weight="700" fill="#0052cc" text-anchor="middle" dominant-baseline="middle">${initials}</text></svg>`;
            $img.attr('src', 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg)).show();
            if (user.profileImage) $('#imageMissingNote').show();
            return;
        }
        const url = candidates[i];
        const probe = new Image();
        probe.onload = function() {
            $img.attr('src', url).show();
            // real image not found -> tell the user to upload again
            const isOwnImage = url === user.profileImageUrl || (user.profileImage && url === userImageUrl(user.profileImage));
            if (user.profileImage && !isOwnImage) $('#imageMissingNote').show();
        };
        probe.onerror = function() {
            console.warn('Profile image not loading:', url);
            tryNext(i + 1);
        };
        probe.src = url;
    })(0);
}

function togglePermissionSection() {
    const isAdmin = $('#userRole').val() == '1';
    if (isAdmin) {
        $('#fieldPermissionSection').hide();
        $('#adminAccessNote').show();
    } else {
        $('#fieldPermissionSection').show();
        $('#adminAccessNote').hide();
    }
}

/* =====================================================================
 |  USER ACCESS - modules, module fields (e.g. Item Stock), locations
 * ===================================================================== */
let accessOptions = { modules: [], locations: [] };
let pendingAccess = null; // access to apply once options are loaded

function loadAccessOptions() {
    $.ajax({
        url: '{{ route("users.access.options") }}',
        type: 'GET',
        success: function(response) {
            if (!response.success) {
                $('#accessModulesBox, #accessLocationsBox').html('<div class="permission-empty">Unable to load options.</div>');
                return;
            }
            accessOptions = response.data || { modules: [], locations: [] };
            renderAccessModules();
            renderAccessLocations();
            if (pendingAccess) {
                applyAccess(pendingAccess);
            } else {
                renderAccessFields();
                updateAccessSummary();
            }
        },
        error: function() {
            $('#accessModulesBox, #accessLocationsBox').html('<div class="permission-empty">Unable to load options.</div>');
        }
    });
}

function renderAccessModules() {
    let html = '';
    (accessOptions.modules || []).forEach(function(m) {
        const img = m.image
            ? `<img src="${escapeHtml(m.image)}" alt="" onerror="this.style.visibility='hidden'">`
            : '<img alt="">';
        html += `
            <label class="access-module">
                ${img}
                <span>${escapeHtml(m.label)}</span>
                <input type="checkbox" class="access-module-cb" name="access_modules[]" value="${escapeHtml(m.slug)}">
            </label>`;
    });
    $('#accessModulesBox').html(html || '<div class="permission-empty">No modules configured.</div>');
}

function renderAccessLocations() {
    let html = '';
    (accessOptions.locations || []).forEach(function(l) {
        html += `
            <label class="access-check access-location-item" data-search="${escapeHtml((l.name + ' ' + l.code).toLowerCase())}">
                <input type="checkbox" class="access-location-cb" name="access_locations[]" value="${l.id}">
                ${escapeHtml(l.name || ('Location ' + l.id))}
                ${l.code ? `<small>${escapeHtml(l.code)}</small>` : ''}
            </label>`;
    });
    $('#accessLocationsBox').html(html || '<div class="permission-empty">No locations found.</div>');
}

// Field panels for every CHECKED module that has fields (keeps ticks when re-rendered)
function renderAccessFields() {
    const kept = {};
    $('.access-field-cb:checked').each(function() {
        kept[$(this).data('module') + '|' + $(this).val()] = true;
    });

    let html = '';
    (accessOptions.modules || []).forEach(function(m) {
        if (!m.fields || !m.fields.length) return;
        if (!$(`.access-module-cb[value="${m.slug}"]`).is(':checked')) return;

        let checks = '';
        m.fields.forEach(function(f) {
            const on = kept[m.slug + '|' + f.key] ? 'checked' : '';
            checks += `
                <label class="access-check">
                    <input type="checkbox" class="access-field-cb" data-module="${escapeHtml(m.slug)}"
                           name="access_fields[${escapeHtml(m.slug)}][]" value="${escapeHtml(f.key)}" ${on}>
                    ${escapeHtml(f.label)}
                </label>`;
        });

        html += `
            <div class="access-panel">
                <div class="access-panel-head">
                    <div>
                        <h6 class="mb-0">${escapeHtml(m.label)} - Field Access</h6>
                        <small class="text-muted">Unticked fields (and their Save buttons) will be hidden for this user.</small>
                    </div>
                    <label class="access-select-all">
                        <input type="checkbox" class="access-fields-all" data-module="${escapeHtml(m.slug)}"> Select All
                    </label>
                </div>
                <div class="access-fields-grid">${checks}</div>
            </div>`;
    });

    $('#accessFieldsBox').html(html);
    syncSelectAll();
}

function applyAccess(access) {
    if (!accessOptions.modules.length) {
        pendingAccess = access; // options not loaded yet
        return;
    }
    pendingAccess = null;

    const modules = access.modules || [];
    const locations = (access.locations || []).map(String);
    const fields = access.fields || {};

    $('.access-module-cb').each(function() {
        $(this).prop('checked', modules.indexOf($(this).val()) !== -1);
    });
    $('.access-location-cb').each(function() {
        $(this).prop('checked', locations.indexOf(String($(this).val())) !== -1);
    });

    $('#accessFieldsBox').html('');
    renderAccessFields();
    $('.access-field-cb').each(function() {
        const list = fields[$(this).data('module')] || [];
        $(this).prop('checked', list.indexOf($(this).val()) !== -1);
    });

    $('#accessLocationSearch').val('');
    $('.access-location-item').show();
    syncSelectAll();
}

function syncSelectAll() {
    $('.access-module').each(function() {
        $(this).toggleClass('checked', $(this).find('.access-module-cb').is(':checked'));
    });

    const mAll = $('.access-module-cb').length;
    $('#accessAllModules').prop('checked', mAll > 0 && $('.access-module-cb:checked').length === mAll);

    const lAll = $('.access-location-cb').length;
    $('#accessAllLocations').prop('checked', lAll > 0 && $('.access-location-cb:checked').length === lAll);

    $('.access-fields-all').each(function() {
        const mod = $(this).data('module');
        const all = $(`.access-field-cb[data-module="${mod}"]`);
        $(this).prop('checked', all.length > 0 && all.filter(':checked').length === all.length);
    });

    updateAccessSummary();
}

function updateAccessSummary() {
    const m = $('.access-module-cb:checked').length;
    const l = $('.access-location-cb:checked').length;
    $('#accessSummary').text(`Selected: ${m} module(s), ${l} location(s).`);
}

$(document).on('change', '.access-module-cb', function() {
    renderAccessFields();
});

$(document).on('change', '#accessAllModules', function() {
    $('.access-module-cb').prop('checked', $(this).is(':checked'));
    renderAccessFields();
});

$(document).on('change', '#accessAllLocations', function() {
    // Only the locations visible in the current search
    $('.access-location-item:visible .access-location-cb').prop('checked', $(this).is(':checked'));
    syncSelectAll();
});

$(document).on('change', '.access-location-cb, .access-field-cb', syncSelectAll);

$(document).on('change', '.access-fields-all', function() {
    const mod = $(this).data('module');
    $(`.access-field-cb[data-module="${mod}"]`).prop('checked', $(this).is(':checked'));
    syncSelectAll();
});

$(document).on('input', '#accessLocationSearch', function() {
    const q = $(this).val().trim().toLowerCase();
    $('.access-location-item').each(function() {
        $(this).toggle(!q || String($(this).data('search')).indexOf(q) !== -1);
    });
});

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function loadUserData() {
    $.ajax({
        url: '{{ route("users.datatable") }}',
        type: 'GET',
        data: {
            draw: currentPage,
            start: (currentPage - 1) * pageSize,
            length: pageSize,
            'search[value]': searchQuery
        },
        success: function(response) {
            console.log('Response:', response);
            
            if (response.success) {
                allData = response.data || [];
                totalRecords = response.total || 0;

                $('#totalUserCount').text(response.total || 0);

                renderDesktopTable();
                renderMobileCards();
                updatePagination();
            } else {
                $('#userTable tbody').html('<tr class="loading-row"><td colspan="7" class="text-center">No data received</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            console.error('Status:', xhr.status);
            console.error('Response:', xhr.responseText);
            
            let errorMsg = 'Error loading data';
            if (xhr.status === 404) {
                errorMsg = 'Route not found - check your routes configuration';
            } else if (xhr.status === 500) {
                errorMsg = 'Server error - check Laravel logs';
            }
            
            $('#userTable tbody').html('<tr class="loading-row"><td colspan="7" class="text-center">' + errorMsg + '</td></tr>');
        }
    });
}

function renderDesktopTable() {
    const tbody = $('#userTable tbody');
    tbody.empty();

    if (allData.length === 0) {
        tbody.html('<tr class="loading-row"><td colspan="7" class="text-center">No users found</td></tr>');
        return;
    }

    allData.forEach((user, index) => {
        const roleBadge = user.user_role == 1 ? 'badge-admin' : 'badge-user';
        const roleText = user.user_role == 1 ? 'Admin' : 'User';
        
        // Proper image path handling
        let imageUrl = userImageUrl(user.profileImage);
        if (!imageUrl.startsWith('http') && !imageUrl.startsWith('/')) {
            imageUrl = '/' + imageUrl;
        }

        const tr = $(`
            <tr>
                <td>${(currentPage - 1) * pageSize + index + 1}</td>
                <td>${user.FirstName} ${user.LastName}</td>
                <td>${user.EmailID || '-'}</td>
                <td>${user.MobileNo || '-'}</td>
                <td><span class="badge ${roleBadge}">${roleText}</span></td>
                <td><img src="${imageUrl}" class="user-image" data-user-id="${user.ID}" alt="User" onerror="this.onerror=null;this.src=window.DEFAULT_AVATAR"></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon edit editBtn" data-id="${user.ID}" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-icon delete deleteBtn" data-id="${user.ID}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
        tbody.append(tr);
    });
}

function renderMobileCards() {
    const container = $('#userCardsContainer');
    container.empty();

    if (allData.length === 0) {
        container.html('<div class="loading-card"><p>No users found</p></div>');
        return;
    }

    allData.forEach(user => {
        const roleBadge = user.user_role == 1 ? 'badge-admin' : 'badge-user';
        const roleText = user.user_role == 1 ? 'Admin' : 'User';
        
        // Proper image path handling
        let imageUrl = userImageUrl(user.profileImage);
        if (!imageUrl.startsWith('http') && !imageUrl.startsWith('/')) {
            imageUrl = '/' + imageUrl;
        }

        const card = $(`
            <div class="user-card">
                <div class="card-row">
                    <span class="card-label">Name</span>
                    <span class="card-value">${user.FirstName} ${user.LastName}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Email</span>
                    <span class="card-value">${user.EmailID || '-'}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Phone</span>
                    <span class="card-value">${user.MobileNo || '-'}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Role</span>
                    <span class="card-value"><span class="badge ${roleBadge}">${roleText}</span></span>
                </div>
                <div class="card-row">
                    <span class="card-label">Image</span>
                    <span class="card-value"><img src="${imageUrl}" class="user-image" data-user-id="${user.ID}" alt="User" onerror="this.onerror=null;this.src=window.DEFAULT_AVATAR"></span>
                </div>
                <div class="card-row">
                    <span class="card-label">Action</span>
                    <span class="card-value">
                        <button class="btn-icon edit editBtn" data-id="${user.ID}" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-icon delete deleteBtn" data-id="${user.ID}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </span>
                </div>
            </div>
        `);
        container.append(card);
    });
}

function updatePagination() {
    const totalPages = Math.ceil(totalRecords / pageSize);
    const start = (currentPage - 1) * pageSize + 1;
    const end = Math.min(currentPage * pageSize, totalRecords);

    if (totalRecords === 0) {
        $('#paginationInfo').text('Showing 0 of 0');
        $('#pageInfo').text('Page 0 of 0');
    } else {
        $('#paginationInfo').text(`Showing ${start} to ${end} of ${totalRecords}`);
        $('#pageInfo').text(`Page ${currentPage} of ${totalPages}`);
    }

    $('#prevBtn').prop('disabled', currentPage <= 1);
    $('#nextBtn').prop('disabled', currentPage >= totalPages);
}

function validateForm() {
    $('.form-control').removeClass('error');
    $('.error-message').removeClass('show').text('');

    let valid = true;

    if (!$('#firstName').val().trim()) {
        markError('#firstName', 'First Name is required');
        valid = false;
    }

    if (!$('#lastName').val().trim()) {
        markError('#lastName', 'Last Name is required');
        valid = false;
    }

    // Mobile number (required, used for OTP login)
    const mobile = $('#mobileNo').val().replace(/\D/g, '').slice(-10);
    if (!/^[6-9]\d{9}$/.test(mobile)) {
        markError('#mobileNo', 'Enter a valid 10-digit mobile number');
        valid = false;
    }

    // Email optional - only check the format when filled
    const email = $('#emailID').val().trim();
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        markError('#emailID', 'Please enter a valid email address');
        valid = false;
    }

    if (!$('#userRole').val()) {
        markError('#userRole', 'Role is required');
        valid = false;
    }

    if ($('#userRole').val() == '0' && $('.access-module-cb:checked').length === 0) {
        Swal.fire('Module required', 'Please select at least one module for this user.', 'warning');
        valid = false;
    }

    const isEdit = $('#userId').val() !== '';

    if (!isEdit && !$('#profileImage')[0].files.length) {
        markError('#profileImage', 'Profile image is required');
        valid = false;
    }

    return valid;
}

function markError(selector, message) {
    $(selector).addClass('error');
    // error text is a sibling (there may be a hint text in between)
    $(selector).siblings('.error-message').first().text(message).addClass('show');
}

function resetForm() {
    $('#userForm')[0].reset();
    $('#userId').val('');
    $('#imagePreview').hide().attr('src', '');
    $('.form-control').removeClass('error');
    $('.error-message').removeClass('show').text('');
    $('#profileImage').prop('required', true);

    permissionData = {};
    $('#selectAllPermissionTables').prop('checked', false);
    $('.permission-table-checkbox').prop('checked', false);
    $('#permissionFieldsBox').html('');
    renderSelectedPermissions();
    togglePermissionSection();
}
</script>
@endpush

@endsection