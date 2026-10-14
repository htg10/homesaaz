@extends('layouts.app')
@section('title', 'Locations')

@section('content')
<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"> Location Management
                    </h2>
                    <p class="text-muted mb-0">Manage warehouse locations with GST numbers and color badges</p>
                </div>
                <button class="btn btn-primary btn-sm" id="addLocationBtn" data-bs-toggle="modal" data-bs-target="#locationModal"> Add Location
                </button>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer"></div>

    <!-- Search -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-white">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control form-control-lg" id="searchInput" 
                       placeholder="Search by location name, location code...">
            </div>
        </div>
    </div>

    <!-- Table Section (Full Width) -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div style="max-height: 700px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 50px;">S.No</th>
                                    <th>Location Code</th>
                                    <th>Location Name</th>
                                    <th>Address</th>
                                    <th style="width: 160px;">GST Number</th>
                                    <th>Sales Database</th>
                                    <th style="width: 60px;">Color</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="locationsList">
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading locations...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Total Location Count -->
                <div class="px-3 py-2 bg-white border-top border-bottom">
                    <strong>Total Locations:</strong>
                    <span id="totalLocationCount">0</span>
                </div>

                <!-- Pagination -->
                <div class="card-footer bg-light">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <span id="paginationInfo">Showing 0 of 0</span>
                            </small>
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Page navigation" class="d-flex justify-content-end">
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item" id="prevPageItem">
                                        <button class="page-link" id="prevBtn">← Previous</button>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link" id="pageInfo">Page 1</span>
                                    </li>
                                    <li class="page-item" id="nextPageItem">
                                        <button class="page-link" id="nextBtn">Next →</button>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======== LOCATION MODAL ======== -->
<div class="modal fade" id="locationModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle"> Add Location
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <form id="locationForm">
                    <input type="hidden" id="locationId" name="LocationID">

                    <!-- Location Name Dropdown -->
                    <div class="mb-4">
                        <label for="locationName" class="form-label">
                            <strong>Location Name <span class="text-danger">*</span></strong>
                        </label>
                        <select class="form-select form-select-lg" id="locationName" name="LocationName" required>
                            <option value="">-- Select Location --</option>
                        </select>
                        <small class="text-muted d-block mt-2">Select a location from the list</small>
                        <div class="invalid-feedback">Please select a location.</div>
                    </div>

                    <!-- Location Code (Read-only) -->
                    <div class="mb-4">
                        <label for="locationCode" class="form-label">
                            <strong>Location Code</strong>
                            <span class="badge bg-secondary ms-2">Auto-fill</span>
                        </label>
                        <input type="text" class="form-control form-control-lg" id="locationCode" 
                               readonly style="background-color: #f8f9fa;">
                        <small class="text-muted d-block mt-2">Automatically filled from database</small>
                    </div>

                    <!-- Location Address (Read-only) -->
                    <div class="mb-4">
                        <label for="locationAddress" class="form-label">
                            <strong>Location Address</strong>
                            <span class="badge bg-secondary ms-2">Auto-fill</span>
                        </label>
                        <textarea class="form-control form-control-lg" id="locationAddress" 
                                  rows="2" readonly style="background-color: #f8f9fa;"></textarea>
                        <small class="text-muted d-block mt-2">Automatically filled from database</small>
                    </div>

                    <!-- GST Number -->
                    <div class="mb-4">
                        <label for="gstNumber" class="form-label">
                            <strong>GST Number</strong>
                            <span class="badge bg-success ms-2">Auto-fill</span>
                        </label>
                        <input
                            type="text"
                            class="form-control form-control-lg"
                            id="gstNumber"
                            name="GST"
                            maxlength="50"
                            autocomplete="off"
                            style="text-transform: uppercase;"
                        >
                    </div>

                    <!-- Sales Database (used by Floor Wise Sales) -->
                    <div class="mb-4">
                        <label for="databaseName" class="form-label">
                            <strong>Sales Database</strong>
                            <span class="badge bg-info text-dark ms-2">Floor Wise Sales</span>
                        </label>
                        <input
                            type="text"
                            class="form-control form-control-lg"
                            id="databaseName"
                            name="DatabaseName"
                            maxlength="100"
                            autocomplete="off"
                            placeholder="e.g. HOMESAAZ_LJP"
                            style="text-transform: uppercase;"
                        >
                        <small class="text-muted">SQL Server database of this location. Leave empty if this location has no sales database.</small>
                    </div>

                    <!-- Color Badge -->
                    <div class="mb-4">
                        <label for="colorPicker" class="form-label">
                            <strong>Color Badge</strong>
                            <span class="badge bg-warning text-dark ms-2">Color Picker</span>
                        </label>
                        <div class="d-flex gap-3 align-items-start">
                            <div>
                                <input type="color" class="form-control form-control-color" 
                                       id="colorPicker" name="Color" value="#007bff" 
                                       style="height: 60px; width: 80px; cursor: pointer;">
                                <small class="text-muted d-block mt-2">Pick color</small>
                            </div>
                            <div class="flex-grow-1">
                                <input type="text" class="form-control form-control-lg" id="colorCode" 
                                       placeholder="#007bff" readonly>
                                <small class="text-muted d-block mt-2">Hex format (#RRGGBB)</small>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clearColorBtn" 
                                    style="margin-top: 4px;"> Clear
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"> Cancel
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="saveLocationBtn"> Save Location
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .color-badge {
        display: inline-block;
        width: 30px;
        height: 30px;
        border-radius: 4px;
        border: 2px solid #ddd;
        vertical-align: middle;
    }

    .color-badge-null {
        background-color: #f0f0f0;
        border: 2px dashed #ccc;
    }

    .form-select-lg, .form-control-lg {
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }

    .table-hover tbody tr:hover {
        background-color: #f5f5f5 !important;
    }

    .form-control-color {
        border: 2px solid #dee2e6;
    }

    .form-control-color:hover {
        border-color: #0d6efd;
    }

    .modal-lg {
        max-width: 600px;
    }
</style>

@push('scripts')
<script>
// Global variables
let allLocations = [];
let currentPage = 1;
let pageSize = 10;
let totalRecords = 0;
let searchQuery = '';
let locationModal = null;

// Initialize
$(document).ready(function() {
    locationModal = new bootstrap.Modal(document.getElementById('locationModal'));
    loadLocations();
    loadLocationsDropdown();
    setupEventListeners();
});

/**
 * Load locations for dropdown
 */
function loadLocationsDropdown() {
    $.ajax({
        url: "{{ route('locations.api.all') }}",
        type: 'GET',
        success: function(response) {
            if (response.success && response.data) {
                const dropdown = $('#locationName');
                dropdown.empty();
                dropdown.append('<option value="">-- Select Location --</option>');

                response.data.forEach(function(location) {
                    const name = location.LocationName || '';
                    const code = location.Locationcode || '';
                    const address = location.LocationAddress || '';
                    const gst = location.GST ?? '';
                    const color = location.Color || '';

                    const option = $('<option>', {
                        value: location.LocationID,
                        text: `${name} (${code || 'N/A'})`
                    });

                    option.attr('data-code', code);
                    option.attr('data-name', name);
                    option.attr('data-address', address);
                    option.attr('data-gst', gst);
                    option.attr('data-color', color);

                    dropdown.append(option);
                });
            }
        }
    });
}

/**
 * Load locations datatable
 */
function loadLocations() {
    const start = (currentPage - 1) * pageSize;

    $.ajax({
        url: "{{ route('locations.api.datatable') }}",
        type: 'GET',
        data: {
            draw: currentPage,
            start: start,
            length: pageSize,
            'search[value]': searchQuery
        },
        success: function(response) {
            if (response.success) {
                allLocations = response.data || [];
                totalRecords = Number(response.recordsFiltered ?? response.total ?? 0);
                $('#totalLocationCount').text(totalRecords);
                renderLocationsTable();
                updatePagination();
            }
        },
        error: function() {
            showAlert('Error loading locations', 'danger');
        }
    });
}

/**
 * Render locations table
 */
function renderLocationsTable() {
    const tbody = $('#locationsList');
    tbody.empty();

    if (allLocations.length === 0) {
        tbody.html('<tr><td colspan="8" class="text-center py-4 text-muted">No locations found</td></tr>');
        return;
    }

    allLocations.forEach(function(location) {
        const colorBadge = location.Color ? 
            `<span class="color-badge" style="background-color: ${location.Color};" title="${location.Color}"></span>` 
            : `<span class="color-badge color-badge-null" title="No color"></span>`;

        const row = `
            <tr>
                <td>${location.DT_RowIndex}</td>
                <td><span class="badge bg-primary">${location.Locationcode || '-'}</span></td>
                <td><strong>${location.LocationName || '-'}</strong></td>
                <td><small>${location.LocationAddress || '-'}</small></td>
                <td>${location.GSTDisplay || '-'}</td>
                <td>${location.DatabaseName ? `<span class="badge bg-light text-dark border">${location.DatabaseName}</span>` : '<small class="text-muted">-</small>'}</td>
                <td>${colorBadge}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary edit-btn" data-id="${location.LocationID}">
                        <i class="bi bi-pencil"></i> 
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${location.LocationID}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    // Attach listeners
    $('.edit-btn').off('click').on('click', function() {
        const id = $(this).data('id');
        loadLocationToModal(id);
    });

    $('.delete-btn').off('click').on('click', function() {
        const id = $(this).data('id');
        deleteLocation(id);
    });
}

/**
 * Load location to modal for editing
 */
function loadLocationToModal(locationId) {
    $.ajax({
        url: `{{ route('locations.api.show', ['id' => '__ID__']) }}`.replace('__ID__', locationId),
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const location = response.data;

                // Update modal title
                $('#modalTitle').html('Edit Location');

                // Set values
                $('#locationId').val(location.LocationID);
                $('#locationName').val(String(location.LocationID));
                $('#locationCode').val(location.Locationcode || '');
                $('#locationAddress').val(location.LocationAddress || '');
                $('#gstNumber').val(location.GST ?? '');
                $('#databaseName').val(location.DatabaseName ?? '');

                if (location.Color) {
                    $('#colorPicker').val(location.Color);
                    $('#colorCode').val(location.Color);
                    $('#colorPreview')
                        .css('background-color', location.Color)
                        .css('border', '');
                } else {
                    $('#colorPicker').val('#007bff');
                    $('#colorCode').val('');
                    $('#colorPreview').css('background-color', '#007bff');
                }

                // Show modal
                locationModal.show();
            }
        },
        error: function() {
            showAlert('Error loading location', 'danger');
        }
    });
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Location dropdown change
    $('#locationName').on('change', function() {
        const option = $(this).find('option:selected');
        const code = option.attr('data-code') || '';
        const name = option.attr('data-name') || '';
        const address = option.attr('data-address') || '';
        const gst = option.attr('data-gst') || '';
        const color = option.attr('data-color') || '';

        $('#locationCode').val(code);
        $('#locationAddress').val(address);
        $('#gstNumber').val(gst);

        if (color) {
            $('#colorPicker').val(color);
            $('#colorCode').val(color);
            $('#colorPreview')
                .css('background-color', color)
                .css('border', '');
        } else {
            $('#colorPicker').val('#007bff');
            $('#colorCode').val('');
            $('#colorPreview').css('background-color', '#007bff');
        }
    });

    // Color picker
    $('#colorPicker').on('change', function() {
        const color = $(this).val();
        $('#colorCode').val(color);
        $('#colorPreview')
            .css('background-color', color)
            .css('border', '');
    });

    // Clear color
    $('#clearColorBtn').on('click', function() {
        $('#colorPicker').val('#ffffff');
        $('#colorCode').val('');
        $('#colorPreview')
            .css('background-color', '#f0f0f0')
            .css('border', '2px dashed #ccc');
    });

    // Add Location button
    $('#addLocationBtn').on('click', function() {
        $('#modalTitle').html('Add Location');
        $('#locationForm')[0].reset();
        $('#locationId').val('');
        $('#locationName').val('');
        $('#colorPicker').val('#007bff');
        $('#colorCode').val('');
        $('#colorPreview').css('background-color', '#007bff');
    });

    // Form submit
    $('#saveLocationBtn').on('click', function() {
        saveLocation();
    });

    // Search
    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchQuery = $(this).val();
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadLocations();
        }, 300);
    });

    // Pagination
    $('#prevBtn').on('click', function(e) {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            loadLocations();
        }
    });

    $('#nextBtn').on('click', function(e) {
        e.preventDefault();
        const totalPages = Math.ceil(totalRecords / pageSize);
        if (currentPage < totalPages) {
            currentPage++;
            loadLocations();
        }
    });
}

/**
 * Save location
 */
function saveLocation() {
    const locationId = $('#locationId').val();
    const selected = $('#locationName').find('option:selected');

    const locationName = selected.attr('data-name') || '';
    const locationCode = selected.attr('data-code') || $('#locationCode').val() || '';
    const locationAddress = selected.attr('data-address') || $('#locationAddress').val() || '';
    const gstValue = $('#gstNumber').val();
    const colorValue = $('#colorCode').val();

    if (!locationName || !locationId) {
        showAlert('Please select a location', 'warning');
        return;
    }

    const data = {
        LocationID: locationId,
        LocationName: locationName,
        Locationcode: locationCode,
        LocationAddress: locationAddress,
        GST: gstValue === '' ? null : gstValue,
        Color: colorValue === '' ? null : colorValue,
        DatabaseName: ($('#databaseName').val() || '').trim().toUpperCase() || null
    };

    const saveBtn = $('#saveLocationBtn');
    saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

    $.ajax({
        url: "{{ route('locations.api.save') }}",
        type: 'POST',
        data: JSON.stringify(data),
        contentType: 'application/json; charset=UTF-8',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                showAlert(response.message || 'Location saved successfully', 'success');
                locationModal.hide();

                currentPage = 1;
                loadLocations();
                loadLocationsDropdown();

                $('#locationForm')[0].reset();
                $('#locationId').val('');
                $('#locationCode').val('');
                $('#locationAddress').val('');
                $('#gstNumber').val('');
                $('#databaseName').val('');
                $('#colorPicker').val('#007bff');
                $('#colorCode').val('');
                $('#colorPreview').css('background-color', '#007bff');
            } else {
                showAlert(response.message || 'Error saving location', 'danger');
            }
        },
        error: function(xhr) {
            let error = 'Error saving location';

            if (xhr.responseJSON) {
                error = xhr.responseJSON.message || error;

                if (xhr.responseJSON.errors) {
                    const messages = Object.values(xhr.responseJSON.errors).flat();
                    if (messages.length) {
                        error = messages.join('<br>');
                    }
                }
            }

            showAlert(error, 'danger');
        },
        complete: function() {
            saveBtn.prop('disabled', false).html('Save Location');
        }
    });
}

/**
 * Delete location
 */
function deleteLocation(id) {
    if (!confirm('Are you sure you want to delete this location?')) {
        return;
    }

    $.ajax({
        url: `{{ route('locations.api.delete', ['id' => '__ID__']) }}`.replace('__ID__', id),
        type: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                setTimeout(() => {
                    loadLocations();
                    loadLocationsDropdown();
                }, 800);
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Error deleting location';
            showAlert(error, 'danger');
        }
    });
}

/**
 * Update pagination
 */
function updatePagination() {
    const totalPages = Math.ceil(totalRecords / pageSize);
    const start = (currentPage - 1) * pageSize + 1;
    const end = Math.min(currentPage * pageSize, totalRecords);

    $('#paginationInfo').text(totalRecords === 0 ? 'Showing 0 of 0' : `Showing ${start} to ${end} of ${totalRecords}`);
    $('#pageInfo').text(`Page ${currentPage} of ${Math.max(1, totalPages)}`);

    $('#prevBtn').prop('disabled', currentPage <= 1);
    $('#prevPageItem').toggleClass('disabled', currentPage <= 1);

    $('#nextBtn').prop('disabled', currentPage >= totalPages);
    $('#nextPageItem').toggleClass('disabled', currentPage >= totalPages);
}

/**
 * Show alert
 */
function showAlert(message, type = 'info') {
    const icon = type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'exclamation-triangle';
    const alert = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="bi bi-${icon}"></i>
            <strong class="ms-2">${message}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('#alertContainer').prepend(alert);
    
    setTimeout(() => {
        $('#alertContainer').find('.alert:first').fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
}
</script>
@endpush

@endsection