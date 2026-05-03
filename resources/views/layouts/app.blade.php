@php
    $theme = auth()->user()?->theme ?? session('theme', app(\App\Services\SettingService::class)->get('default_theme', 'xbtit-default'));
    $bsTheme = match($theme) {
        'darklair', 'modern' => 'dark',
        default              => 'light',
    };
    $locale     = app()->getLocale();
    $localeInfo = \App\Models\User::LOCALES[$locale] ?? ['dir' => 'ltr'];
    $isRtl      = $localeInfo['dir'] === 'rtl';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}"
      dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
      data-bs-theme="{{ $bsTheme }}"
      data-xbt-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') &mdash; @endif{{ config('app.name', 'xbtit') }}</title>
    @if($isRtl)
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    @else
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/xbtit.css') }}">
    <link rel="stylesheet" href="{{ asset('css/themes/' . $theme . '.css') }}">
    @stack('styles')
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="{{ route('home') }}">{{ config('app.name', 'xbtit') }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('torrents.*', 'home') ? 'active' : '' }}"
                       href="{{ route('torrents.index') }}">
                        <i class="bi bi-magnet"></i> {{ __('nav.torrents') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('news.*') ? 'active' : '' }}"
                       href="{{ route('news.index') }}">
                        <i class="bi bi-newspaper"></i> {{ __('nav.news') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('forum.*', 'threads.*', 'posts.*') ? 'active' : '' }}"
                       href="{{ route('forum.index') }}">
                        <i class="bi bi-chat-left-dots"></i> {{ __('nav.forum') }}
                    </a>
                </li>
                @auth
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('torrents.create') ? 'active' : '' }}"
                           href="{{ route('torrents.create') }}">
                            <i class="bi bi-cloud-upload"></i> {{ __('nav.upload') }}
                        </a>
                    </li>
                @endauth
                @auth
                    @if(auth()->user()->isAdmin())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.*') ? 'active' : '' }}"
                               href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-shield-lock"></i> {{ __('nav.admin') }}
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">{{ __('admin.dashboard') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.settings.index') }}">{{ __('admin.settings') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('admin.users.index') }}">Users</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.torrents.index') }}">Torrents</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.categories.index') }}">Categories</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.bans.index') }}">IP Bans</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('admin.forum.index') }}">Forum</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.polls.index') }}">Polls</a></li>
                            </ul>
                        </li>
                    @endif
                @endauth
            </ul>
            <ul class="navbar-nav ms-auto">
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> {{ auth()->user()->username }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('users.show', auth()->id()) }}">
                                <i class="bi bi-person"></i> {{ __('nav.profile') }}
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('account.edit') }}">
                                <i class="bi bi-gear"></i> {{ __('nav.settings') }}
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right"></i> {{ __('nav.logout') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">{{ __('nav.login') }}</a>
                    </li>
                    @if(Route::has('register'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">{{ __('nav.register') }}</a>
                        </li>
                    @endif
                @endauth

                {{-- Language switcher (compact globe dropdown) --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle px-2" href="#" data-bs-toggle="dropdown" title="Language">
                        <i class="bi bi-globe2"></i>
                        <span class="d-none d-lg-inline ms-1">{{ \App\Models\User::LOCALES[$locale]['name'] ?? 'EN' }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach(\App\Models\User::LOCALES as $code => $info)
                            <li>
                                <form method="POST" action="{{ route('locale.switch') }}">
                                    @csrf
                                    <input type="hidden" name="locale" value="{{ $code }}">
                                    <button type="submit"
                                            class="dropdown-item {{ $locale === $code ? 'fw-bold' : '' }}">
                                        {{ $info['flag'] }} {{ $info['name'] }}
                                        @if($locale === $code) <i class="bi bi-check ms-1"></i> @endif
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @yield('content')
    {{ $slot ?? '' }}
</main>

<footer class="border-top py-3 mt-4 text-center text-muted small">
    &copy; {{ date('Y') }} {{ config('app.name', 'xbtit') }}
    &mdash; <a href="{{ route('account.edit') }}#theme" class="text-muted">{{ ucfirst(str_replace('-', ' ', $theme)) }}</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
