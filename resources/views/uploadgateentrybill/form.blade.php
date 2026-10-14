@extends('layouts.app')
@section('title', $title ?? 'Add Upload Gate Entry Bill')

@section('content')
<div class="form-container">
    <div class="form-header">
        <div class="header-left">
            <h2 class="form-title">
                <span id="formTitle">{{ isset($invoice) ? 'Edit' : 'Add' }}</span> Upload Gate Entry Bill
            </h2>
        </div>
        <div class="header-right">
            <a href="{{ route('uploadgateentrybill.list') }}" class="btn-back">
                <i class="bi bi-list-check"></i>
                <span>View List</span>
            </a>
        </div>
    </div>
    <div class="form-card">
        <form id="uploadgateentrybillForm" autocomplete="off" novalidate enctype="multipart/form-data">
            <input type="hidden" name="Id" value="{{ old('Id', $invoice->Id ?? '') }}">

            <!-- Auto Generated Invoice Number -->
            <div class="form-group">
                <label class="form-label">Invoice Number</label>
                <div class="input-wrapper">
                    <i class="bi bi-receipt"></i>
                    <input type="text" 
                           class="form-input" 
                           value="{{ $invoice->InvoiceNumber ?? $nextInvoiceNumber }}" 
                           readonly>
                </div>
                <small class="form-hint">
                    Invoice number will be generated automatically in series (INV-001, INV-002...)
                </small>
            </div>


            <!-- Image Upload Section -->
            <div class="form-group">
                <label class="form-label">Invoice Image<span class="required">*</span></label>
                
                <ul class="nav nav-tabs mb-3" id="imageUploadTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#fileUpload">Select File</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#cameraCapture">Take Photo</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- File Upload Tab -->
                    <div id="fileUpload" class="tab-pane fade show active">
                        <div class="input-wrapper" style="position: relative;">
                            <i class="bi bi-image" style="position: absolute; left: 12px;"></i>
                            <input type="file" id="image" name="image" class="form-input" accept="image/*" style="padding-left: 40px;" required>
                        </div>
                        <small class="form-hint">JPG, PNG, GIF up to 5MB</small>
                        <div class="error-message"></div>
                    </div>

                    <!-- Camera Capture Tab -->
                    <div id="cameraCapture" class="tab-pane fade">
                        <div class="mb-3">
                            <video id="cameraVideo" style="width: 100%; max-width: 400px; border: 1px solid #ddd; border-radius: 6px; display: none;"></video>
                            <canvas id="captureCanvas" style="display: none;"></canvas>
                        </div>
                        <div class="camera-buttons" style="display: flex; gap: 10px;">
                            <button type="button" class="btn-submit" id="startCameraBtn" style="flex: 1;">Start Camera</button>
                            <button type="button" class="btn-submit" id="capturePhotoBtn" style="flex: 1; display: none;">Capture Photo</button>
                            <button type="button" class="btn-cancel" id="stopCameraBtn" style="flex: 1; display: none;">Stop Camera</button>
                        </div>
                    </div>
                </div>

                <!-- Current Image Display -->
                @if($invoice && $invoice->ImagePath)
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                        <p style="font-size: 12px; color: #666; margin-bottom: 8px;"><strong>Current Image:</strong></p>
                        <img src="{{ asset($invoice->ImagePath) }}" style="max-width: 200px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                @endif

                <!-- Image Preview -->
                <div id="imagePreview" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                    <p style="font-size: 12px; color: #666; margin-bottom: 8px;"><strong>Image Preview:</strong></p>
                    <img id="preview" style="max-width: 200px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn-submit" id="submitBtn">
                    <span id="submitText">{{ isset($invoice) ? 'Update' : 'Save' }}</span>
                </button>
                <a href="{{ route('uploadgateentrybill.list') }}" class="btn-cancel">
                    Cancel
                </a>
            </div>
        </form>
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
        --light-bg: #f5f7fa;
        --border-gray: #ddd;
        --text-dark: #333;
        --text-light: #666;
        --text-hint: #999;
    }

    .form-container {
        background: linear-gradient(135deg, var(--light-bg) 0%, #f8f9fa 100%);
        min-height: 100vh;
        padding: 20px;
        display: flex;
        flex-direction: column;
    }

    .form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        gap: 20px;
    }

    .header-left { flex: 1; }

    .form-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .header-right { display: flex; gap: 10px; }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        background: white;
        color: var(--text-light);
        border: 1px solid var(--border-gray);
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .btn-back:hover {
        border-color: var(--primary-blue);
        color: var(--primary-blue);
        background: rgba(0, 82, 204, 0.02);
    }

    .form-card {
        background: white;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-gray);
        width: 100%;
        max-width: 90%;
        margin: 0 auto;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group:last-child { margin-bottom: 0; }

    .form-label {
        display: block;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
        font-size: 14px;
    }

    .required {
        color: var(--danger-red);
        font-weight: 700;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-wrapper i {
        position: absolute;
        left: 12px;
        color: var(--text-hint);
        font-size: 16px;
        pointer-events: none;
    }

    .currency-symbol {
        position: absolute;
        left: 12px;
        color: var(--text-dark);
        font-weight: 600;
        font-size: 16px;
        pointer-events: none;
    }

    .form-input {
        width: 100%;
        padding: 11px 15px 11px 40px;
        border: 1px solid var(--border-gray);
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
        transition: all 0.2s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
        background: rgba(0, 82, 204, 0.01);
    }

    .form-input:read-only {
        background-color: #f5f5f5;
        cursor: not-allowed;
    }

    .form-input::placeholder { color: var(--text-hint); }

    .form-input.error {
        border-color: var(--danger-red);
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }

    .form-hint {
        display: block;
        font-size: 12px;
        color: var(--text-hint);
        margin-top: 6px;
    }

    .error-message {
        display: none;
        font-size: 12px;
        color: var(--danger-red);
        margin-top: 6px;
        font-weight: 500;
    }

    .error-message.show { display: block; }

    .nav-tabs {
        border-bottom: 1px solid var(--border-gray);
        display: flex;
    }

    .nav-item { list-style: none; }

    .nav-link {
        display: block;
        padding: 10px 16px;
        color: var(--text-light);
        text-decoration: none;
        border-bottom: 2px solid transparent;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .nav-link:hover { color: var(--primary-blue); }

    .nav-link.active {
        color: var(--primary-blue);
        border-bottom-color: var(--primary-blue);
    }

    .tab-content { margin-top: 15px; }

    .tab-pane { display: none; }

    .tab-pane.active { display: block; }

    .mb-3 { margin-bottom: 15px; }

    .form-actions {
        /* display: flex; */
        gap: 12px;
        margin-top: 30px;
        padding-top: 24px;
        border-top: 1px solid var(--border-gray);
    }

    .btn-submit {
        flex: 1;
        padding: 12px 24px;
        background: var(--primary-blue);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-submit:hover:not(:disabled) {
        background: #0045b3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 82, 204, 0.25);
    }

    .btn-submit:disabled {
        background: #ccc;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .btn-cancel {
        flex: 1;
        padding: 12px 24px;
        background: white;
        color: var(--text-light);
        border: 1px solid var(--border-gray);
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
        transition: all 0.2s ease;
    }

    .btn-cancel:hover {
        background: var(--light-bg);
        border-color: var(--text-light);
    }

    @media (max-width: 768px) {
        .form-container { padding: 12px; }
        .form-header { flex-direction: column; align-items: stretch; gap: 12px; }
        .btn-back { width: 100%; justify-content: center; }
        .form-card { padding: 20px; max-width: 100%; }
        .form-group { margin-bottom: 20px; }
        .form-label { font-size: 13px; }
        .form-input { padding: 10px 15px 10px 36px; font-size: 13px; }
        .camera-buttons { flex-direction: column; }
    }

    @media (max-width: 480px) {
        .form-container { padding: 10px; }
        .form-title { font-size: 18px; }
        .form-card { padding: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-label { font-size: 12px; }
        .form-input { padding: 9px 15px 9px 34px; font-size: 12px; }
        .btn-submit, .btn-cancel { padding: 10px 16px; font-size: 12px; }
        .camera-buttons button { padding: 10px 16px !important; }
    }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let cameraStream = null;
    let capturedImageData = null;

    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('[data-bs-toggle="tab"]').on('click', function(e) {
            e.preventDefault();
            const target = $($(this).attr('href'));
            $('.tab-pane').removeClass('active');
            target.addClass('active');
        });

        $('#image').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    $('#preview').attr('src', event.target.result);
                    $('#imagePreview').show();
                };
                reader.readAsDataURL(file);
            }
        });

        $('#startCameraBtn').on('click', async function(e) {
            e.preventDefault();
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment' } 
                });
                const video = document.getElementById('cameraVideo');
                video.srcObject = cameraStream;
                video.style.display = 'block';
                $('#startCameraBtn').hide();
                $('#capturePhotoBtn').show();
                $('#stopCameraBtn').show();
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Camera Error', text: 'Unable to access camera: ' + error.message });
            }
        });

        $('#capturePhotoBtn').on('click', function(e) {
            e.preventDefault();
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('captureCanvas');
            const ctx = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0);
            capturedImageData = canvas.toDataURL('image/png');
            $('#preview').attr('src', capturedImageData);
            $('#imagePreview').show();
            video.style.display = 'none';
            $('#capturePhotoBtn').hide();
            $('#stopCameraBtn').text('Retake Photo');
        });

        $('#stopCameraBtn').on('click', function(e) {
            e.preventDefault();
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }
            document.getElementById('cameraVideo').style.display = 'none';
            $('#startCameraBtn').show();
            $('#capturePhotoBtn').hide();
            $('#stopCameraBtn').hide().text('Stop Camera');
            capturedImageData = null;
        });

        $('#uploadgateentrybillForm').on('submit', function(e) {
            e.preventDefault();
            $('.form-input').removeClass('error');
            $('.error-message').removeClass('show').html('');

            const $submitBtn = $('#submitBtn');
            const originalBtnText = $submitBtn.text();
            $submitBtn.prop('disabled', true).html('Processing...');

            const formData = new FormData(this);

            if (capturedImageData) {
                fetch(capturedImageData)
                    .then(res => res.blob())
                    .then(blob => {
                        formData.set('image', blob, 'camera_' + Date.now() + '.png');
                        submitForm(formData, $submitBtn, originalBtnText);
                    });
            } else {
                submitForm(formData, $submitBtn, originalBtnText);
            }
        });

    });

    function submitForm(formData, $submitBtn, originalBtnText) {
        $.ajax({
            url: "{{ route('uploadgateentrybill.save') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function() {
                        window.location.href = "{{ route('uploadgateentrybill.list') }}";
                    });
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).html(originalBtnText);
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        const $field = $('[name="' + field + '"]');
                        $field.addClass('error');
                        const $errorMsg = $field.closest('.form-group').find('.error-message');
                        if ($errorMsg.length) {
                            $errorMsg.html(messages.join('<br>')).addClass('show');
                        }
                    });
                    Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Please check the form and try again.', confirmButtonColor: '#0052cc' });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'An error occurred. Please try again.', confirmButtonColor: '#0052cc' });
                }
            }
        });
    }
</script>
@endpush
@endsection