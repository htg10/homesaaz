@extends('layouts.app')
@section('title', $title ?? 'Add Vendor')

@section('content')
<div class="form-container">
    <!-- Header Section -->
    <div class="form-header">
        <div class="header-left">
            <h2 class="form-title">
                <span id="formTitle">{{ isset($invoice) ? 'Edit' : 'Add' }}</span> Vendor
            </h2>
        </div>
        <div class="header-right">
            <a href="{{ route('vendorInvoice.list') }}" class="btn-back">
                <i class="bi bi-list-check"></i>
                <span>View List</span>
            </a>
        </div>
    </div>
    <div class="form-card">
        <form id="vendorInvoiceForm" autocomplete="off" novalidate>
            @csrf
            <input type="hidden" name="Id" value="{{ old('Id', $invoice->Id ?? '') }}">
            <div class="form-group">
                <label for="SupplierMobileNo" class="form-label">Supplier Mobile No.<span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="bi bi-telephone"></i>
                    <input type="tel" id="SupplierMobileNo" name="SupplierMobileNo" maxlength="10" class="form-input" value="{{ old('SupplierMobileNo', $invoice->SupplierMobileNo ?? '') }}" placeholder="9876543210" inputmode="numeric" pattern="[6-9][0-9]{9}" required>
                </div>
                <small class="form-hint">10-digit mobile number (6-9 followed by 9 digits)</small>
                <div class="error-message"></div>
            </div>
            <div class="form-group">
                <label for="BillNo" class="form-label">Bill No.<span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="bi bi-file-earmark-text"></i>
                    <input type="text" id="BillNo" name="BillNo" class="form-input" value="{{ old('BillNo', $invoice->BillNo ?? '') }}" placeholder="INV-001 or BILL-2024-001" maxlength="100" required>
                </div>
                <div class="error-message"></div>
            </div>
            <div class="form-group">
                <label for="Amount" class="form-label">Amount<span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="currency-symbol">₹</span>
                    <input type="number" id="Amount" name="Amount" step="0.01" min="0.01" class="form-input" value="{{ old('Amount', $invoice->Amount ?? '') }}" placeholder="5000.00" required>
                </div>
                <small class="form-hint">Amount with up to 2 decimal places</small>
                <div class="error-message"></div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit" id="submitBtn">
                    <span id="submitText">{{ isset($invoice) ? 'Update' : 'Save' }}</span>
                </button>
                <a href="{{ route('vendorInvoice.list') }}" class="btn-cancel">
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

    /* ========== HEADER ========== */
    .form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        gap: 20px;
    }

    .header-left {
        flex: 1;
    }

    .form-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .header-right {
        display: flex;
        gap: 10px;
    }

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

    /* ========== FORM CARD ========== */
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

    /* ========== FORM GROUPS ========== */
    .form-group {
        margin-bottom: 24px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

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

    /* Input Wrapper */
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

    .form-input::placeholder {
        color: var(--text-hint);
    }

    /* Input States */
    .form-input.error {
        border-color: var(--danger-red);
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }

    .form-input.error:focus {
        border-color: var(--danger-red);
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }

    /* Helper Text */
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

    .error-message.show {
        display: block;
    }

    /* ========== FORM ACTIONS ========== */
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

    .btn-submit:active:not(:disabled) {
        transform: translateY(0);
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

    /* ========== TABLET (768px - 1024px) ========== */
    @media (max-width: 1024px) {
        .form-card {
            padding: 24px;
            max-width: 95%;
        }

        .form-title {
            font-size: 24px;
        }

        .form-input {
            padding: 10px 15px 10px 38px;
            font-size: 13px;
        }

        .form-actions {
            gap: 10px;
        }
    }

    /* ========== MOBILE (480px - 768px) ========== */
    @media (max-width: 768px) {
        .form-container {
            padding: 12px;
        }

        .form-header {
            flex-direction: column;
            align-items: stretch;
            margin-bottom: 20px;
            gap: 12px;
        }

        .header-left {
            width: 100%;
        }

        .header-right {
            width: 100%;
        }

        .btn-back {
            width: 100%;
            justify-content: center;
        }

        .form-title {
            font-size: 22px;
        }

        .form-card {
            padding: 20px;
            max-width: 100%;
            width: 100%;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-size: 13px;
            margin-bottom: 7px;
        }

        .form-input {
            padding: 10px 15px 10px 36px;
            font-size: 13px;
        }

        .input-wrapper i {
            font-size: 15px;
            left: 10px;
        }

        .currency-symbol {
            font-size: 15px;
            left: 10px;
        }

        .form-hint {
            font-size: 11px;
            margin-top: 5px;
        }

        .error-message {
            font-size: 11px;
            margin-top: 5px;
        }

        .form-actions {
            flex-direction: column;
            gap: 10px;
            margin-top: 24px;
        }

        /* .btn-submit,
        .btn-cancel {
            width: 100%;
        } */
    }

    /* ========== SMALL MOBILE (< 480px) ========== */
    @media (max-width: 480px) {
        .form-container {
            padding: 10px;
        }

        .form-header {
            margin-bottom: 15px;
            gap: 8px;
        }

        .form-title {
            font-size: 18px;
        }

        .btn-back {
            padding: 8px 12px;
            font-size: 12px;
        }

        .btn-back span {
            display: none;
        }

        .btn-back i {
            font-size: 16px;
        }

        .form-card {
            padding: 16px;
            border-radius: 6px;
            max-width: 100%;
            width: 100%;
            margin: 0;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            font-size: 12px;
            margin-bottom: 6px;
        }

        .form-input {
            padding: 9px 15px 9px 34px;
            font-size: 12px;
        }

        .input-wrapper i,
        .currency-symbol {
            font-size: 14px;
            left: 9px;
        }

        .form-hint {
            font-size: 10px;
            margin-top: 4px;
        }

        .error-message {
            font-size: 10px;
            margin-top: 4px;
        }

        .form-actions {
            margin-top: 20px;
            gap: 8px;
        }

        .btn-submit,
        .btn-cancel {
            padding: 10px 16px;
            font-size: 12px;
        }
    }

    /* Utility Classes */
    .d-flex {
        display: flex;
    }

    .gap-2 {
        gap: 8px;
    }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#vendorInvoiceForm').on('submit', function(e) {
            e.preventDefault();

            $('.form-input').removeClass('error');
            $('.error-message').removeClass('show').html('');

            const $form = $(this);
            const $submitBtn = $('#submitBtn');
            const originalBtnText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Processing...');

            $.ajax({
                url: "{{ route('vendorInvoice.save') }}",
                type: "POST",
                data: $form.serialize(),
                success: function(response) {
                    console.log('Success:', response);

                    if (response.status) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function() {
                            window.location.href = "{{ route('vendorInvoice.list') }}";
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                    $submitBtn.prop('disabled', false).text(originalBtnText);

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
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: 'Please check the form and try again.',
                            confirmButtonColor: '#0052cc'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'An error occurred. Please try again.',
                            confirmButtonColor: '#0052cc'
                        });
                    }
                }
            });
        });

        $('#SupplierMobileNo').on('input', function() {
            let value = $(this).val().replace(/[^\d]/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            $(this).val(value);
        });
        $('#Amount').on('blur', function() {
            if ($(this).val()) {
                const amount = parseFloat($(this).val());
                if (!isNaN(amount)) {
                    $(this).val(amount.toFixed(2));
                }
            }
        });
        $('.form-input').on('input', function() {
            if ($(this).val().trim() !== '') {
                $(this).removeClass('error');
                $(this).closest('.form-group').find('.error-message').removeClass('show').html('');
            }
        });
    });
</script>
@endpush
@endsection