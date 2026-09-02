<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="csrf" content="{{ csrf_token() }}" />
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

<script>
    window.Laravel = {
        csrfToken: '{{ csrf_token() }}'
    };
    
    // Ensure Livewire can access CSRF token
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.Livewire !== 'undefined') {
            window.Livewire.csrf = '{{ csrf_token() }}';
        }
    });
</script>
