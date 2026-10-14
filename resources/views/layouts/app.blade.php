<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>@yield('title', 'HomeSaaz')</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <!-- Material Symbols -->
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-200..200"
    >

    <!-- SweetAlert2 CSS -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.min.css"
    >

    <link rel="stylesheet" href="{{ asset('assets/css/homesaaz.css') }}">

    {{-- Hide header on login page --}}
    <style>
        .hs-login-page .hs-topbar {
            display: none !important;
        }
    </style>

    @stack('styles')
</head>

<body class="@yield('body_class')">

    @include('layouts.header')

    @yield('content')

    {{-- Flash toast (shared) --}}
    @if(session('success') || session('error'))
        <div
            class="hs-toast"
            style="background-color: {{ session('success') ? '#2e7d32' : '#d9534f' }};"
        >
            {{ session('success') ?? session('error') }}
        </div>
    @endif

    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    {{-- SweetAlert2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.all.min.js"></script>

    {{-- Shared HomeSaaz scripts --}}
    <script src="{{ asset('assets/js/homesaaz.js') }}"></script>

    @auth

        @php
            $permissionContextForJs = $fieldPermissionContext ?? [
                'is_admin' => ((int) (auth()->user()?->user_role ?? 0) === 1),
                'tables' => [],
                'allowed_keys' => [],
                'all_keys' => [],
            ];
        @endphp

        <script>
            // Central field-permission context injected by ApplyFieldPermissions middleware.
            window.HS_FIELD_PERMISSIONS =
                {{ \Illuminate\Support\Js::from($permissionContextForJs) }};

            (function () {
                const ALWAYS_VISIBLE_LABELS = new Set([
                    'sno',
                    'srno',
                    'serial',
                    'action',
                    'actions',
                    'view',
                    'edit',
                    'delete',
                    'download',
                    'image',
                    'photo',
                    'status'
                ]);

                function normalize(value) {
                    return String(value ?? '')
                        .toLowerCase()
                        .replace(/&/g, 'and')
                        .replace(/[^a-z0-9]/g, '');
                }

                function sets() {
                    const ctx = window.HS_FIELD_PERMISSIONS || {};

                    return {
                        admin: !!ctx.is_admin,
                        allowed: new Set(
                            (ctx.allowed_keys || []).map(normalize)
                        ),
                        known: new Set(
                            (ctx.all_keys || []).map(normalize)
                        )
                    };
                }

                function allowedLabel(label) {
                    const state = sets();

                    if (state.admin) {
                        return true;
                    }

                    const key = normalize(label);

                    if (!key || ALWAYS_VISIBLE_LABELS.has(key)) {
                        return true;
                    }

                    /*
                     * Only hide labels that are known permission fields.
                     * Unknown/static UI labels remain visible.
                     */
                    if (!state.known.has(key)) {
                        return true;
                    }

                    return state.allowed.has(key);
                }

                function applyToTables(root = document) {
                    const state = sets();

                    if (state.admin) {
                        return;
                    }

                    root.querySelectorAll('table').forEach(function (table) {
                        const headers = Array.from(
                            table.querySelectorAll('thead th')
                        );

                        headers.forEach(function (th, index) {
                            const explicit = th.getAttribute('data-field');
                            const label = explicit || th.textContent;
                            const shouldShow = allowedLabel(label);

                            th.style.display = shouldShow ? '' : 'none';

                            table.querySelectorAll('tbody tr').forEach(function (tr) {
                                const cell = tr.children[index];

                                if (cell) {
                                    cell.style.display = shouldShow ? '' : 'none';
                                }
                            });
                        });
                    });
                }

                function applyToExplicitFields(root = document) {
                    const state = sets();

                    if (state.admin) {
                        return;
                    }

                    /*
                     * Explicit data-field support.
                     */
                    root.querySelectorAll('[data-field]').forEach(function (el) {
                        const field = el.getAttribute('data-field');

                        if (!field) {
                            return;
                        }

                        const key = normalize(field);

                        if (
                            state.known.has(key) &&
                            !state.allowed.has(key)
                        ) {
                            el.style.display = 'none';
                        } else {
                            el.style.display = '';
                        }
                    });

                    /*
                     * Mobile cards:
                     * .card-row + .card-label
                     */
                    root.querySelectorAll('.card-row').forEach(function (row) {
                        const label = row.querySelector('.card-label');

                        if (!label) {
                            return;
                        }

                        row.style.display =
                            allowedLabel(label.textContent)
                                ? ''
                                : 'none';
                    });

                    /*
                     * Form labels.
                     */
                    root.querySelectorAll('.form-label').forEach(function (label) {
                        const labelText = String(
                            label.textContent || ''
                        )
                            .replace(/[*:]+$/g, '')
                            .trim();

                        const key = normalize(labelText);

                        if (!key || !state.known.has(key)) {
                            return;
                        }

                        const wrapper =
                            label.closest(
                                '.form-group, .form-field, [class*="col-"]'
                            ) || label.parentElement;

                        if (wrapper) {
                            wrapper.style.display =
                                state.allowed.has(key)
                                    ? ''
                                    : 'none';
                        }
                    });

                    /*
                     * Inputs/selects/textareas using their name attribute.
                     *
                     * Example:
                     * field_permissions[HSStock][]
                     * becomes:
                     * field_permissions
                     */
                    root
                        .querySelectorAll(
                            'input[name], select[name], textarea[name]'
                        )
                        .forEach(function (control) {
                            const rawName = String(
                                control.getAttribute('name') || ''
                            ).replace(/\[.*$/, '');

                            const key = normalize(rawName);

                            if (!key || !state.known.has(key)) {
                                return;
                            }

                            const wrapper =
                                control.closest(
                                    '.form-group, .form-field, [class*="col-"]'
                                ) || control;

                            wrapper.style.display =
                                state.allowed.has(key)
                                    ? ''
                                    : 'none';
                        });

                    /*
                     * Detail views:
                     * <p><strong>Field:</strong> value</p>
                     */
                    root
                        .querySelectorAll(
                            'p > strong, li > strong, .detail-row strong, .detail-item strong'
                        )
                        .forEach(function (strong) {
                            const label = String(
                                strong.textContent || ''
                            ).replace(/:\s*$/, '');

                            const key = normalize(label);

                            if (!key || !state.known.has(key)) {
                                return;
                            }

                            const wrapper =
                                strong.closest(
                                    'p, li, .detail-row, .detail-item'
                                ) || strong;

                            wrapper.style.display =
                                state.allowed.has(key)
                                    ? ''
                                    : 'none';
                        });
                }

                function applyPermissions(root = document) {
                    applyToTables(root);
                    applyToExplicitFields(root);
                }

                function updateContextFromAjax(xhr) {
                    try {
                        const json =
                            xhr.responseJSON ||
                            JSON.parse(xhr.responseText || '{}');

                        if (json && json._field_permissions) {
                            window.HS_FIELD_PERMISSIONS =
                                json._field_permissions;

                            applyPermissions(document);
                        }
                    } catch (_) {
                        // Ignore non-JSON responses.
                    }
                }

                document.addEventListener(
                    'DOMContentLoaded',
                    function () {
                        applyPermissions(document);
                    }
                );

                /*
                 * Re-apply when AJAX/DataTables add rows dynamically.
                 */
                const observer = new MutationObserver(function (mutations) {
                    let needsApply = false;

                    for (const mutation of mutations) {
                        if (
                            mutation.addedNodes &&
                            mutation.addedNodes.length
                        ) {
                            needsApply = true;
                            break;
                        }
                    }

                    if (needsApply) {
                        applyPermissions(document);
                    }
                });

                observer.observe(
                    document.documentElement,
                    {
                        childList: true,
                        subtree: true
                    }
                );

                /*
                 * Re-apply after jQuery AJAX requests.
                 */
                if (window.jQuery) {
                    jQuery(document).ajaxComplete(
                        function (_event, xhr) {
                            updateContextFromAjax(xhr);

                            setTimeout(
                                function () {
                                    applyPermissions(document);
                                },
                                0
                            );
                        }
                    );
                }

                /*
                 * Can also be called manually from a Blade/DataTable page.
                 */
                window.applyHomeSaazFieldPermissions =
                    applyPermissions;
            })();
        </script>

    @endauth

    @stack('scripts')

</body>
</html>
