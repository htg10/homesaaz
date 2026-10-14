@extends('layouts.app')
@section('title', 'Bed Occupancy')

@section('content')
<div class="student-container">
    <!-- Header Section -->
    <div class="student-header">
        <h2 class="student-title">Bed Occupancy</h2>
    </div>

    <!-- Search Box -->
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search by location...">
        <i class="bi bi-search"></i>
    </div>

    <!-- Table/Card Container -->
    <div class="student-table-wrapper">
        <table class="student-table" id="occupancyTable">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Location Name</th>
                    <th>Total Bed</th>
                    <th>Vacant</th>
                    <th>Let Out</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="loading-row">
                    <td colspan="6" class="loading-cell">
                        <div class="spinner"></div>
                        <p>Loading bed occupancy...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="student-cards-wrapper" id="occupancyCardsContainer">
        <div class="loading-card">
            <div class="spinner"></div>
            <p>Loading bed occupancy...</p>
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
        --light-gray: #f5f5f5;
        --border-gray: #ddd;
        --text-dark: #333;
        --text-light: #666;
    }

    .student-container {
        background: white;
        min-height: 100vh;
        padding: 30px 40px;
    }

    .student-header {
        margin-bottom: 25px;
    }

    .student-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .search-box {
        position: relative;
        margin-bottom: 25px;
    }

    .search-box input {
        width: 100%;
        padding: 12px 15px 12px 40px;
        border: 1px solid var(--border-gray);
        border-radius: 4px;
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
        border-radius: 0;
        overflow-x: auto;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-gray);
        margin-bottom: 20px;
    }

    .student-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
    }

    .student-table thead {
        background-color: var(--light-gray);
        border-bottom: 1px solid var(--border-gray);
    }

    .student-table thead th {
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        font-size: 12px;
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
        padding: 12px 15px;
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

    /* .text-center {
        text-align: center;
    } */

    .student-cards-wrapper {
        display: none;
        gap: 15px;
        margin-bottom: 20px;
    }

    .student-card {
        background: white;
        border-radius: 0;
        padding: 15px;
        border-left: 4px solid var(--primary-blue);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-gray);
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
        border-radius: 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .student-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: white;
        border-radius: 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-gray);
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

    @media (max-width: 768px) {
        .student-container {
            padding: 20px;
        }

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

    loadOccupancyData();

    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchQuery = $(this).val();
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadOccupancyData();
        }, 300);
    });

    $('#prevBtn').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            loadOccupancyData();
            window.scrollTo(0, 0);
        }
    });

    $('#nextBtn').on('click', function() {
        const totalPages = Math.ceil(totalRecords / pageSize);
        if (currentPage < totalPages) {
            currentPage++;
            loadOccupancyData();
            window.scrollTo(0, 0);
        }
    });

    $(document).on('click', '.view', function() {
        const id = $(this).data('id');
        const numId = parseInt(id);
        
        let occupancy = allData.find(r => parseInt(r.BedOccID) === numId);
        if (!occupancy) {
            occupancy = allData[numId];
        }
        
        if (occupancy) {
            showViewModal(occupancy);
        }
    });

    $(document).on('click', '.modal-overlay', function(e) {
        if (e.target === this) {
            closeViewModal();
        }
    });
});

function loadOccupancyData() {
    $.ajax({
        url: '{{ route("bedOccupancy.datatable") }}',
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
            $('#occupancyTable tbody').html('<tr class="loading-row"><td colspan="6">Error loading data</td></tr>');
        }
    });
}

function renderDesktopTable() {
    const tbody = $('#occupancyTable tbody');
    tbody.empty();

    if (allData.length === 0) {
        tbody.html('<tr class="loading-row"><td colspan="6" class="text-center">No occupancy data found</td></tr>');
        return;
    }

    allData.forEach((item, index) => {
        const itemId = item.BedOccID || index;
        const tr = $(`
            <tr>
                <td>${(currentPage - 1) * pageSize + index + 1}</td>
                <td>${item.LocationName || '-'}</td>
                <td>${item.TOTALBED}</td>
                <td>${item.Vacant}</td>
                <td>${item.Letout}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon view" data-id="${itemId}" title="View">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
        tbody.append(tr);
    });
}

function renderMobileCards() {
    const container = $('#occupancyCardsContainer');
    container.empty();

    if (allData.length === 0) {
        container.html('<div class="loading-card"><p>No occupancy data found</p></div>');
        return;
    }

    allData.forEach((item, index) => {
        const itemId = item.BedOccID || index;
        const card = $(`
            <div class="student-card">
                <div class="card-row">
                    <span class="card-label">Location Name</span>
                    <span class="card-value">${item.LocationName || '-'}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Total Bed</span>
                    <span class="card-value">${item.TOTALBED}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Vacant</span>
                    <span class="card-value">${item.Vacant}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Let Out</span>
                    <span class="card-value">${item.Letout}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Action</span>
                    <span class="card-value">
                        <button class="btn-icon view" data-id="${itemId}" title="View">
                            <i class="bi bi-eye"></i>
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

    $('#paginationInfo').text(`Showing ${start} to ${end} of ${totalRecords}`);
    $('#pageInfo').text(`Page ${currentPage} of ${totalPages}`);
    $('#prevBtn').prop('disabled', currentPage <= 1);
    $('#nextBtn').prop('disabled', currentPage >= totalPages);
}

function showViewModal(item) {
    const modalHTML = `
        <div id="viewModal" class="modal-overlay">
            <div class="modal-view">
                <div class="view-header">
                    <h3>Bed Occupancy Details</h3>
                    <button type="button" class="close-btn" onclick="closeViewModal()">×</button>
                </div>
                <div class="view-content">
                    <div class="view-row">
                        <label>Location Name</label>
                        <span>${item.LocationName || '-'}</span>
                    </div>
                    <div class="view-row">
                        <label>Total Bed</label>
                        <span>${item.TOTALBED || '-'}</span>
                    </div>
                    <div class="view-row">
                        <label>Let Out</label>
                        <span>${item.Letout || '-'}</span>
                    </div>
                    <div class="view-row">
                        <label>Vacant</label>
                        <span>${item.Vacant || '-'}</span>
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