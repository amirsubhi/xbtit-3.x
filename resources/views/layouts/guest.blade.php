<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') &mdash; @endif{{ config('app.name', 'xbtit') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="w-100" style="max-width: 420px;">
        <div class="text-center mb-4">
            <a class="fw-bold fs-4 text-decoration-none text-dark" href="/">{{ config('app.name', 'xbtit') }}</a>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
