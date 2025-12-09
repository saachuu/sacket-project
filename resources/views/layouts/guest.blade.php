<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Culvert') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased">

    <div class="flex h-screen w-full overflow-hidden">
        <div class="hidden lg:flex w-1/2 relative bg-blue-900 justify-center items-center">

            <div class="absolute inset-0 opacity-60">
                <img src="https://images.unsplash.com/photo-1459749411177-33450b52e8d2?q=80&w=2070&auto=format&fit=crop"
                    class="w-full h-full object-cover" alt="Concert Background">
            </div>

            <div class="absolute inset-0 bg-gradient-to-tr from-blue-900/90 to-purple-900/40"></div>

            <div class="relative z-10 text-white p-12 text-center">
                <h2 class="text-4xl font-bold mb-4">Welcome to Culvert</h2>
                <p class="text-lg text-blue-100 max-w-md mx-auto">
                    Platform manajemen tiket event termudah dan terpercaya.
                    Kelola eventmu sekarang.
                </p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center bg-white px-8">
            <div class="w-full max-w-md space-y-8">

                <div class="flex justify-center lg:hidden mb-6">
                    <a href="/">
                        <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                    </a>
                </div>

                <div class="text-center">
                    <h2 class="mt-6 text-3xl font-bold tracking-tight text-gray-900">
                        {{ request()->routeIs('register') ? 'Buat Akun Baru' : 'Masuk ke Akun' }}
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        @if (request()->routeIs('register'))
                            Sudah punya akun? <a href="{{ route('login') }}"
                                class="font-medium text-blue-600 hover:text-blue-500">Login disini</a>
                        @else
                            Belum punya akun? <a href="{{ route('register') }}"
                                class="font-medium text-blue-600 hover:text-blue-500">Daftar sekarang</a>
                        @endif
                    </p>
                </div>

                <div class="mt-8">
                    {{ $slot }}
                </div>

            </div>
        </div>
    </div>
</body>

</html>
