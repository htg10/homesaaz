@if (Auth::check())

@php
$user = Auth::user();
$userName = trim(($user->FirstName ?? 'User') . ' ' . ($user->LastName ?? ''));
if (empty($userName)) {
    $userName = $user->EmailID ?? 'User';
}
$initials = collect(explode(' ', trim($userName)))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp


<header class="hs-topbar">

    <a href="{{ route('dashboard') }}" class="logo">
        <img src="{{ asset('assets/images/homesaaz-logo.png') }}"
            alt="HomeSaaz">
    </a>
    <div class="hs-user-area">
        <ul class="hs-menu">
            <li>
                <a href="{{ route('dashboard') }}">
                    Dashboard
                </a>
            </li>
             <li>
                <a href="{{ route('locations.list') }}">
                    Locations
                </a>
            </li>
            @if((int)$user->user_role === 1)
            <li>
                <a href="{{ route('users.list') }}">
                    User
                </a>
            </li>
            @endif
        </ul>
        <div class="hs-user">
            @if(!empty($user->profileImage))
            <img src="{{ asset($user->profileImage) }}"
                class="hs-avatar-img"
                alt="{{ $userName }}"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <span class="hs-avatar" style="display: none;">
                {{ strtoupper($initials) }}
            </span>
            @else
            <span class="hs-avatar">
                {{ strtoupper($initials) }}
            </span>
            @endif
            <span class="hs-user-name">
                {{ $userName }}
            </span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="hs-logoff">
                <i class="bi bi-box-arrow-right"></i>
                Logoff
            </button>
        </form>
    </div>
</header>
@else
<header class="hs-topbar">
    <a href="{{ route('login') }}" class="logo">
        <img src="{{ asset('assets/images/homesaaz-logo.png') }}"
            alt="HomeSaaz">
    </a>
</header>
@endif
<style>
    .hs-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 30px;
        background: white;
        border-bottom: 1px solid #ddd;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .logo img {
        height: 40px;
        width: auto;
    }

    .hs-user-area {
        display: flex;
        align-items: center;
        gap: 30px;
    }

    .hs-menu {
        display: flex;
        list-style: none;
        gap: 20px;
        margin: 0;
        padding: 0;
    }

    .hs-menu a {
        text-decoration: none;
        color: #333;
        font-weight: 600;
        font-size: 14px;
        transition: color 0.2s ease;
    }

    .hs-menu a:hover {
        color: #dc3545;
    }

    .hs-avatar-img {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #ddd;
    }

    .hs-user {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .hs-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #dc3545;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        flex-shrink: 0;
    }

    .hs-user-name {
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .hs-logoff {
        padding: 8px 16px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }

    .hs-logoff:hover {
        background: #dc3545;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(90, 157, 138, 0.25);
    }

    .hs-logoff i {
        font-size: 16px;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .hs-topbar {
            padding: 12px 15px;
            gap: 15px;
        }

        .hs-user-area {
            gap: 15px;
        }

        .hs-menu {
            gap: 12px;
            flex-direction: column;
        }

        .hs-menu a {
            font-size: 12px;
        }

        .hs-user-name {
            display: none;
        }

        .hs-logoff {
            padding: 6px 12px;
            font-size: 12px;
        }
    }
</style>