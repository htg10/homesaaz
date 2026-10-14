@extends('layouts.app')
@section('title', 'Upload Gate Entry Bill List')

@section('content')
<div class="list-container">
    <div class="list-header">
        <div class="header-left">
            <h2 class="list-title">Upload Gate Entry Bill</h2>
        </div>
        <div class="header-right">
            <a href="{{ route('uploadgateentrybill.form') }}" class="btn-add">
                <span>Add Upload Gate Entry Bill</span>
            </a>
        </div>
    </div>

    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search...">
        <i class="bi bi-search"></i>
    </div>

    <!-- TABLE WITH HORIZONTAL SCROLL -->
    <div class="table-wrapper">
        <div class="table-scroll">
            <table class="invoices-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Status</th>
                        <th>Approved By</th>
                        <th>Date</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr class="loading-row">
                        <td colspan="6" class="text-center">
                            <div class="spinner"></div>
                            <p>Loading...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="pagination-section">
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

<!-- Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Gate Entry Bill Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bs-dismiss="modal">Close</button>
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
        --success-green: #28a745;
        --warning-orange: #ffc107;
        --light-bg: #f5f7fa;
        --border-gray: #ddd;
        --text-dark: #333;
        --text-light: #666;
        --text-hint: #999;
    }

    .list-container {
        background: linear-gradient(135deg, var(--light-bg) 0%, #f8f9fa 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .list-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        gap: 15px;
    }

    .header-left { flex: 1; }

    .list-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .header-right { display: flex; gap: 10px; }

    .btn-add {
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

    .btn-add:hover {
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
        font-size: 16px;
    }

    .table-wrapper {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-gray);
        margin-bottom: 20px;
    }

    .table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .invoices-table {
        width: 100%;
        min-width: 600px;
        border-collapse: collapse;
    }

    .invoices-table thead {
        background-color: #f5f5f5;
        border-bottom: 2px solid var(--border-gray);
    }

    .invoices-table thead th {
        padding: 15px 12px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        color: var(--text-dark);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .invoices-table tbody tr {
        border-bottom: 1px solid var(--border-gray);
        transition: background-color 0.2s ease;
    }

    .invoices-table tbody tr:hover {
        background-color: rgba(0, 82, 204, 0.02);
    }

    .invoices-table tbody td {
        padding: 14px 12px;
        font-size: 14px;
        color: var(--text-dark);
    }

    .status-dropdown {
        padding: 6px 10px;
        border: 1px solid var(--border-gray);
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        background: white;
        min-width: 100px;
    }

    .status-dropdown:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
    }

    .action-buttons {
        display: flex;
        gap: 4px;
    }

    .btn-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s ease;
        text-decoration: none;
        background: white;
        flex-shrink: 0;
    }

    .btn-icon.view {
        color: var(--primary-blue);
        border-color: var(--primary-blue);
    }

    .btn-icon.view:hover {
        background: var(--primary-blue);
        color: white;
    }

    .btn-icon.edit {
        color: #ffc107;
        border-color: #ffc107;
    }

    .btn-icon.edit:hover {
        background: #ffc107;
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

    .loading-row {
        text-align: center;
        padding: 30px;
    }

    .spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto 10px;
        border: 3px solid var(--light-bg);
        border-top-color: var(--primary-blue);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .pagination-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        gap: 15px;
        flex-wrap: wrap;
    }

    .pagination-info {
        font-size: 13px;
        color: var(--text-light);
    }

    .pagination-buttons {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        justify-content: center;
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
        white-space: nowrap;
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
    }

    .modal-content {
        border: 1px solid var(--border-gray);
        border-radius: 8px;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--light-bg) 0%, #f8f9fa 100%);
        border-bottom: 1px solid var(--border-gray);
        padding: 20px;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-dark);
    }

    .modal-body {
        padding: 20px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-gray);
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 600;
        color: var(--text-light);
        font-size: 13px;
    }

    .detail-value {
        color: var(--text-dark);
        font-size: 14px;
        text-align: right;
    }

    .modal-image {
        max-width: 100%;
        max-height: 400px;
        border: 1px solid var(--border-gray);
        border-radius: 6px;
        margin-top: 10px;
    }

    .btn-cancel {
        padding: 8px 16px;
        background: white;
        color: var(--text-light);
        border: 1px solid var(--border-gray);
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
    }

    .btn-cancel:hover {
        background: var(--light-bg);
    }

    .text-center { text-align: center; }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .list-container { padding: 12px; }
        
        .list-header { 
            flex-direction: column; 
            align-items: stretch; 
            gap: 12px; 
        }
        
        .list-title { font-size: 20px; }
        
        .btn-add { 
            width: 100%; 
            justify-content: center;
            font-size: 13px;
            padding: 10px 16px;
        }
        
        .btn-add span { display: none; }
        
        .search-box input { 
            padding: 10px 15px 10px 36px; 
            font-size: 13px; 
        }

        .invoices-table thead th {
            padding: 12px 8px;
            font-size: 11px;
        }

        .invoices-table tbody td {
            padding: 10px 8px;
            font-size: 12px;
        }

        .status-dropdown {
            padding: 4px 8px;
            font-size: 12px;
            min-width: 85px;
        }

        .btn-icon {
            width: 28px;
            height: 28px;
            font-size: 12px;
        }

        .pagination-section { 
            flex-direction: column; 
            gap: 12px;
        }

        .pagination-buttons { width: 100%; }

        .pagination-info { text-align: center; width: 100%; }
    }

    @media (max-width: 480px) {
        .list-container { padding: 10px; }
        .list-title { font-size: 16px; }
        .btn-add { padding: 8px 12px; font-size: 12px; }
        
        .invoices-table thead th {
            padding: 8px 6px;
            font-size: 10px;
        }

        .invoices-table tbody td {
            padding: 8px 6px;
            font-size: 11px;
        }

        .status-dropdown {
            padding: 4px 6px;
            font-size: 11px;
            min-width: 70px;
        }

        .btn-icon {
            width: 24px;
            height: 24px;
            font-size: 10px;
        }

        .pagination-btn {
            padding: 6px 12px;
            font-size: 11px;
        }
    }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
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

        loadInvoices();

        let searchTimeout;
        $('#searchInput').on('keyup', function() {
            clearTimeout(searchTimeout);
            searchQuery = $(this).val();
            searchTimeout = setTimeout(function() {
                currentPage = 1;
                loadInvoices();
            }, 300);
        });

        $('#prevBtn').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadInvoices();
                window.scrollTo(0, 0);
            }
        });

        $('#nextBtn').on('click', function() {
            const totalPages = Math.ceil(totalRecords / pageSize);
            if (currentPage < totalPages) {
                currentPage++;
                loadInvoices();
                window.scrollTo(0, 0);
            }
        });
    });

    function loadInvoices() {
        const start = (currentPage - 1) * pageSize;
        
        $.ajax({
            url: "{{ route('uploadgateentrybill.datatable') }}",
            type: "GET",
            data: {
                start: start,
                length: pageSize,
                draw: currentPage,
                'search[value]': searchQuery
            },
            success: function(response) {
                const data = response.data || [];
                totalRecords = response.recordsTotal || 0;
                
                renderTable(data);
                updatePagination();
            },
            error: function() {
                $('#tableBody').html('<tr><td colspan="6" class="text-center">Error loading data</td></tr>');
            }
        });
    }

    function renderTable(data) {
        const tbody = $('#tableBody');
        tbody.empty();
        
        if (data.length === 0) {
            tbody.html('<tr><td colspan="6" class="text-center">No invoices found</td></tr>');
            return;
        }
        
        data.forEach((row) => {
            const tr = $(`
                <tr>
                    <td>${row.InvoiceNumber}</td>
                    <td>
                        <select class="status-dropdown" onchange="changeStatus(${row.Id}, this.value, '${row.InvoiceNumber}')">
                            <option value="Pending" ${row.ApprovalStatus === 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="Approved" ${row.ApprovalStatus === 'Approved' ? 'selected' : ''}>Approved</option>
                            <option value="Rejected" ${row.ApprovalStatus === 'Rejected' ? 'selected' : ''}>Rejected</option>
                        </select>
                    </td>
                    <td>${row.ApprovedByAdminName || row.ApprovedByAdminID || '-'}</td>
                    <td>${row.CreatedDate}</td>
                    <td>
                        ${row.ImagePath ? `<a href="${row.ImagePath}" target="_blank" class="btn-icon view" title="View"><i class="bi bi-image"></i></a>` : '-'}
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon edit" title="Edit" onclick="editInvoice(${row.Id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn-icon delete" title="Delete" onclick="deleteInvoice(${row.Id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `);
            tbody.append(tr);
        });
    }

    function changeStatus(id, newStatus, invoiceNumber) {
        fetch(`/uploadgateentrybill/${id}/updateStatus`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]')?.value || ''
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: invoiceNumber + ' → ' + newStatus,
                    timer: 1500,
                    showConfirmButton: false
                });
                loadInvoices();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
                loadInvoices();
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to update status'
            });
            loadInvoices();
        });
    }

    function editInvoice(id) {
        window.location.href = `/uploadgateentrybill/form/${id}`;
    }

    function deleteInvoice(id) {

        fetch(`/uploadgateentrybill/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]')?.value || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                loadInvoices();
            }
        });
    }

    function updatePagination() {
        const totalPages = Math.ceil(totalRecords / pageSize);
        const start = (currentPage - 1) * pageSize + 1;
        const end = Math.min(currentPage * pageSize, totalRecords);
        
        if (totalRecords === 0) {
            $('#paginationInfo').text('Showing 0 of 0');
            $('#pageInfo').text('Page 0 of 0');
            $('#prevBtn').prop('disabled', true);
            $('#nextBtn').prop('disabled', true);
        } else {
            $('#paginationInfo').text(`Showing ${start} to ${end} of ${totalRecords}`);
            $('#pageInfo').text(`Page ${currentPage} of ${totalPages}`);
            $('#prevBtn').prop('disabled', currentPage === 1);
            $('#nextBtn').prop('disabled', currentPage === totalPages);
        }
    }
</script>
@endpush
@endsection