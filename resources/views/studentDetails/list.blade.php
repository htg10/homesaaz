@extends('layouts.app')
@section('title', 'Student Details')

@section('content')
<div class="student-container">
    <!-- Header Section -->
    <div class="student-header">
        <h2 class="student-title">Student Details</h2>
        <!-- <button class="btn-add-student" id="addRentBtn">
            <i class="bi bi-plus-circle"></i>
            <span>Add Student</span>
        </button> -->
    </div>

    <!-- Search Box -->
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search by student name, room no or location...">
        <i class="bi bi-search"></i>
    </div>

    <!-- Table/Card Container -->
    <div class="student-table-wrapper">
        <table class="student-table" id="studentTable">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Student Name</th>
                    <th>Father Name</th>
                    <th>Mobile No</th>
                    <th>Room No</th>
                    <th>Location</th>
                    <th>Rent Amount</th>
                    <th>Rent Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="loading-row">
                    <td colspan="9" class="loading-cell">
                        <div class="spinner"></div>
                        <p>Loading student details...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="student-cards-wrapper" id="studentCardsContainer">
        <div class="loading-card">
            <div class="spinner"></div>
            <p>Loading student details...</p>
        </div>
    </div>

    <!-- Pagination -->
    <div class="student-pagination">
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

    .student-container {
        background: linear-gradient(135deg, #f5f7fa 0%, #f8f9fa 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .student-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        gap: 15px;
    }

    .student-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .btn-add-student {
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
        font-size: 14px;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .btn-add-student:hover {
        background: #0045b3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 82, 204, 0.25);
    }

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
    }

    .student-table-wrapper {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-gray);
        margin-bottom: 20px;
    }

    .student-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .student-table thead {
        background-color: var(--light-gray);
        border-bottom: 2px solid var(--border-gray);
    }

    .student-table thead th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        color: var(--text-dark);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .student-table tbody tr {
        border-bottom: 1px solid var(--border-gray);
    }

    .student-table tbody tr:hover {
        background-color: rgba(0, 82, 204, 0.02);
    }

    .student-table tbody td {
        padding: 14px 15px;
        font-size: 14px;
        color: var(--text-dark);
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

    /* .text-right {
        text-align: right;
    } */

    .action-buttons {
        /* display: flex; */
        gap: 6px;
        justify-content: center;
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
        background: white;
    }

    .btn-icon.view {
        color: #007bff;
        border-color: #007bff;
    }

    .btn-icon.view:hover {
        background: #007bff;
        color: white;
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

    .student-cards-wrapper {
        display: none;
        gap: 15px;
        margin-bottom: 20px;
    }

    .student-card {
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
    }

    .card-label {
        font-weight: 600;
        color: var(--text-light);
        font-size: 12px;
    }

    .card-value {
        color: var(--text-dark);
        font-size: 14px;
        font-weight: 500;
    }

    .loading-card {
        text-align: center;
        padding: 40px 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .student-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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

    /* MODAL STYLES */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        padding: 20px;
    }

    .modal-view {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
        max-width: 700px;
        width: 100%;
        max-height: 85vh;
        overflow-y: auto;
    }

    .view-header {
        padding: 25px;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    }

    .view-header h3 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #333;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 28px;
        color: #999;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .close-btn:hover {
        color: #333;
    }

    .view-content {
        padding: 25px;
    }

    .view-row {
        display: grid;
        grid-template-columns: 35% 65%;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #f5f5f5;
        align-items: center;
    }

    .view-row:last-of-type {
        border-bottom: none;
    }

    .view-row label {
        font-weight: 700;
        color: #5a5a5a;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .view-row span {
        color: #333;
        font-weight: 500;
        font-size: 15px;
        word-break: break-word;
    }

    .view-footer {
        padding: 20px 25px;
        border-top: 1px solid #f0f0f0;
        text-align: right;
        background: #f9f9f9;
    }

    .btn-close {
        padding: 10px 24px;
        background: var(--primary-blue);
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
    }

    .btn-close:hover {
        background: #0045b3;
    }

    @media (max-width: 600px) {
        .modal-view {
            max-width: 95%;
            max-height: calc(100vh - 40px);
        }

        .view-header {
            padding: 20px;
        }

        .view-header h3 {
            font-size: 20px;
        }

        .view-content {
            padding: 20px;
        }

        .view-row {
            grid-template-columns: 1fr;
            gap: 8px;
            padding: 12px 0;
        }

        .view-row label {
            font-size: 12px;
        }

        .view-row span {
            font-size: 14px;
        }

        .view-footer {
            padding: 15px 20px;
        }

        .btn-close {
            width: 100%;
            padding: 12px;
        }
    }

    @media (max-width: 768px) {
        .student-table-wrapper {
            display: none;
        }

        .student-cards-wrapper {
            display: flex;
            flex-direction: column;
        }

        .student-pagination {
            flex-direction: column;
            gap: 15px;
        }

        .pagination-btn {
            flex: 1;
        }
    }
</style>

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let allData = [];
let currentPage = 1;
let pageSize = 10;
let totalRecords = 0;
let searchQuery = '';

$(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    loadStudentData();

    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchQuery = $(this).val();
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadStudentData();
        }, 300);
    });

    $('#prevBtn').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            loadStudentData();
            window.scrollTo(0, 0);
        }
    });

    $('#nextBtn').on('click', function() {
        const totalPages = Math.ceil(totalRecords / pageSize);
        if (currentPage < totalPages) {
            currentPage++;
            loadStudentData();
            window.scrollTo(0, 0);
        }
    });

    $(document).on('click', '.view', function() {
        const id = $(this).data('id');
        const numId = parseInt(id);
        
        let student = allData.find(r => parseInt(r.StudentdetID) === numId);
        if (!student) {
            student = allData.find(r => parseInt(r.ID) === numId);
        }
        if (!student) {
            student = allData.find(r => parseInt(r.id) === numId);
        }
        
        if (student) {
            showViewModal(student);
        }
    });

    $(document).on('click', '.delete', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete Student?',
            text: 'This action cannot be undone',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                
            }
        });
    });

    $(document).on('click', '.modal-overlay', function(e) {
        if (e.target === this) {
            closeViewModal();
        }
    });
});

function loadStudentData() {
    $.ajax({
        url: '{{ route("studentDetails.datatable") }}',
        type: 'GET',
        data: {
            draw: currentPage,
            start: (currentPage - 1) * pageSize,
            length: pageSize,
            'search[value]': searchQuery
        },
        success: function(response) {
            allData = response.data || [];
            totalRecords = response.recordsTotal || 0;

            renderDesktopTable();
            renderMobileCards();
            updatePagination();
        },
        error: function(xhr) {
            $('#studentTable tbody').html('<tr class="loading-row"><td colspan="9">Error loading data</td></tr>');
        }
    });
}

function renderDesktopTable() {
    const tbody = $('#studentTable tbody');
    tbody.empty();

    if (allData.length === 0) {
        tbody.html('<tr class="loading-row"><td colspan="9" class="text-center">No students found</td></tr>');
        return;
    }

    allData.forEach((student, index) => {
        const studentId = student.StudentdetID || student.ID || student.id;
        
        const tr = $(`
            <tr>
                <td>${(currentPage - 1) * pageSize + index + 1}</td>
                <td>${student.StudentName || '-'}</td>
                <td>${student.FatherName || '-'}</td>
                <td>${student.MobileNo || '-'}</td>
                <td>${student.RoomNo || '-'}</td>
                <td>${student.Location || '-'}</td>
                <td class="text-right">₹ ${parseFloat(student.RentAmt || 0).toFixed(2)}</td>
                <td>${student.RentDate ? new Date(student.RentDate).toLocaleDateString('en-IN') : '-'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon view" data-id="${studentId}" title="View">
                            <i class="bi bi-eye"></i>
                        </button>
                       <!-- <button class="btn-icon edit" data-id="${studentId}" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-icon delete" data-id="${studentId}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>-->
                    </div>
                </td>
            </tr>
        `);
        tbody.append(tr);
    });
}

function renderMobileCards() {
    const container = $('#studentCardsContainer');
    container.empty();

    if (allData.length === 0) {
        container.html('<div class="loading-card"><p>No students found</p></div>');
        return;
    }

    allData.forEach(student => {
        const studentId = student.StudentdetID || student.ID || student.id;
        
        const card = $(`
            <div class="student-card">
                <div class="card-row">
                    <span class="card-label">Name</span>
                    <span class="card-value">${student.StudentName || '-'}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Room</span>
                    <span class="card-value">${student.RoomNo || '-'}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Location</span>
                    <span class="card-value">${student.Location || '-'}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Rent Amount</span>
                    <span class="card-value">₹ ${parseFloat(student.RentAmt || 0).toFixed(2)}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Father Name</span>
                    <span class="card-value">${student.FatherName || '-'}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Mobile</span>
                    <span class="card-value">${student.MobileNo || '-'}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Action</span>
                    <span class="card-value">
                        <button class="btn-icon view" data-id="${studentId}" title="View">
                            <i class="bi bi-eye"></i>
                        </button>
                       <!--  <button class="btn-icon edit" data-id="${studentId}" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-icon delete" data-id="${studentId}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>-->
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

    $('#paginationInfo').text(`Showing ${start} to ${end} of ${totalRecords}`);
    $('#pageInfo').text(`Page ${currentPage} of ${totalPages}`);
    $('#prevBtn').prop('disabled', currentPage <= 1);
    $('#nextBtn').prop('disabled', currentPage >= totalPages);
}

function showViewModal(student) {
    const rentDate = student.RentDate ? new Date(student.RentDate).toLocaleDateString('en-IN') : '-';
    const startDate = student.StartingDate ? new Date(student.StartingDate).toLocaleDateString('en-IN') : '-';
    const endDate = student.EndingDate ? new Date(student.EndingDate).toLocaleDateString('en-IN') : '-';
    
    const modalHTML = `
        <div id="viewModal" class="modal-overlay">
            <div class="modal-view">
                <div class="view-header">
                    <h3>Student Details</h3>
                    <button type="button" class="close-btn" onclick="closeViewModal()">×</button>
                </div>
                <div class="view-content">
                    <div class="view-row">
                        <label>Student Name</label>
                        <span>${student.StudentName || '-'}</span>
                    </div>
                    <div class="view-row">
                        <label>Father Name</label>
                        <span>${student.FatherName || '-'}</span>
                    </div>
                    <div class="view-row">
                        <label>Mobile No</label>
                        <span>${student.MobileNo || '-'}</span>
                    </div>
                    <div class="view-row">
                        <label>Room No</label>
                        <span>${student.RoomNo || '-'}</span>
                    </div>
                    <div class="view-row">
                        <label>Bed No</label>
                        <span>${student.BedNo || '-'}</span>
                    </div>
                    <div class="view-row">
                        <label>Location</label>
                        <span>${student.Location || '-'}</span>
                    </div>
                    <div class="view-row">
                        <label>Rent Amount</label>
                        <span>₹ ${parseFloat(student.RentAmt || 0).toFixed(2)}</span>
                    </div>
                    <div class="view-row">
                        <label>Rent Date</label>
                        <span>${rentDate}</span>
                    </div>
                    <div class="view-row">
                        <label>Starting Date</label>
                        <span>${startDate}</span>
                    </div>
                    <div class="view-row">
                        <label>Ending Date</label>
                        <span>${endDate}</span>
                    </div>
                    <div class="view-row">
                        <label>Address</label>
                        <span>${student.address || '-'}</span>
                    </div>
                </div>
                <div class="view-footer">
                    <button type="button" class="btn-close" onclick="closeViewModal()">Close</button>
                </div>
            </div>
        </div>
    `;
    
    $('#viewModal').remove();
    $('body').append(modalHTML);
}

function closeViewModal() {
    $('#viewModal').fadeOut(300, function() {
        $(this).remove();
    });
}
</script>
@endpush

@endsection