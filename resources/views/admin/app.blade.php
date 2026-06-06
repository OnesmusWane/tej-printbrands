<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel | {{ config('app.name') }}</title>
    @php $co = \App\Models\SiteSetting::where('key','company')->value('value') ?? []; @endphp
    @if (!empty($co['favicon_url']))
        <link rel="icon" href="{{ $co['favicon_url'] }}">
    @else
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/admin/main.ts'])
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased">
    <div id="admin-app"></div>

    @php
        $adminUser = [
            'id'          => auth()->id(),
            'name'        => auth()->user()?->name,
            'email'       => auth()->user()?->email,
            'role'        => auth()->user()?->role ?? 'admin',
            'is_admin'    => auth()->user()?->is_admin ?? false,
            'permissions' => auth()->user()?->permissions ?? [],
        ];
    @endphp
    <script>
        window.__ADMIN_USER__ = @json($adminUser);
        window.__PERMISSIONS__ = @json(config('permissions'));
    </script>
</body>
</html>
