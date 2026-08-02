<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LotteryApp — Panel de gestión de loterías y rifas</title>
    <meta name="description" content="Plataforma de administración para gestionar rifas, sorteos, boletos, pagos y ganadores desde un panel único, seguro y en tiempo real.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS Vite / App -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #090d16;
            color: #f3f4f6;
            overflow-x: hidden;
        }

        .font-heading {
            font-family: 'Space Grotesk', sans-serif;
        }

        /* Glassmorphism utility */
        .glass-card {
            background: rgba(17, 24, 39, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .glass-card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card-hover:hover {
            transform: translateY(-4px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 20px 40px -15px rgba(99, 102, 241, 0.15);
        }

        /* Modern Gradient Blobs */
        .glow-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            z-index: 0;
        }

        /* Shimmer button effect */
        .btn-shimmer {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%);
            position: relative;
            overflow: hidden;
        }

        .btn-shimmer::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                60deg,
                transparent 30%,
                rgba(255, 255, 255, 0.25) 50%,
                transparent 70%
            );
            transform: rotate(30deg);
            animation: shimmer 4s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(30deg); }
            20% { transform: translateX(100%) rotate(30deg); }
            100% { transform: translateX(100%) rotate(30deg); }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #090d16;
        }
        ::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #334155;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>
<body class="antialiased selection:bg-indigo-500 selection:text-white" x-data="{ mobileMenu: false }">

    <!-- Ambient background glow elements -->
    <div class="glow-blob w-[500px] h-[500px] bg-indigo-600/20 top-[-100px] left-[-100px]"></div>
    <div class="glow-blob w-[600px] h-[600px] bg-purple-600/15 top-[20%] right-[-150px]"></div>
    <div class="glow-blob w-[500px] h-[500px] bg-blue-600/15 bottom-[10%] left-[20%]"></div>

    <!-- Navigation Header -->
    <header class="sticky top-0 z-50 backdrop-blur-md bg-slate-950/70 border-b border-slate-800/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 via-indigo-600 to-purple-500 flex items-center justify-center shadow-lg shadow-indigo-500/25">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z" />
                        </svg>
                    </div>
                    <span class="font-heading text-xl font-bold tracking-tight text-white">
                        Lottery<span class="text-indigo-400">App</span>
                    </span>
                </div>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="#modulos" class="text-sm font-medium text-slate-300 hover:text-white transition-colors">Módulos</a>
                    <a href="#rifas" class="text-sm font-medium text-slate-300 hover:text-white transition-colors">Rifas</a>
                    <a href="#funciona" class="text-sm font-medium text-slate-300 hover:text-white transition-colors">¿Cómo funciona?</a>
                    <a href="#resultados" class="text-sm font-medium text-slate-300 hover:text-white transition-colors">Resultados</a>
                </nav>

                <!-- Action Button -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="/admin" class="btn-shimmer px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/40 transition-all">
                        Ingresar al panel
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenu = !mobileMenu" class="text-slate-400 hover:text-white focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenu" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Dropdown -->
        <div x-show="mobileMenu" x-cloak class="md:hidden glass-card border-t border-slate-800 px-4 pt-4 pb-6 space-y-3">
            <a href="#modulos" @click="mobileMenu = false" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Módulos</a>
            <a href="#rifas" @click="mobileMenu = false" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Rifas</a>
            <a href="#funciona" @click="mobileMenu = false" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">¿Cómo funciona?</a>
            <a href="#resultados" @click="mobileMenu = false" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-200 hover:bg-slate-800">Resultados</a>
            <div class="pt-2 border-t border-slate-800/80">
                <a href="/admin" @click="mobileMenu = false" class="w-full text-center btn-shimmer px-4 py-2.5 rounded-xl text-sm font-medium text-white shadow-lg">Ingresar al panel</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-12 pb-24 lg:pt-20 lg:pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">

                <!-- Left Column: Copy -->
                <div class="lg:col-span-7 space-y-8 text-center lg:text-left">
                    <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold uppercase tracking-wider">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                        <span>Sistema de administración · Acceso exclusivo</span>
                    </div>

                    <h1 class="font-heading text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-[1.15]">
                        Gestiona tus <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">rifas, sorteos y boletos</span> desde un solo panel.
                    </h1>

                    <p class="text-slate-400 text-lg sm:text-xl font-normal max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                        Plataforma completa para administradores de loterías y rifas: crea sorteos, configura premios,
                        vende y cobra boletos, controla las tasas de cambio y registra ganadores con total transparencia.
                    </p>

                    <!-- CTA Group -->
                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a href="/admin" class="btn-shimmer w-full sm:w-auto px-8 py-4 rounded-xl font-bold text-white text-base shadow-xl shadow-indigo-600/30 hover:shadow-indigo-600/50 transition-all flex items-center justify-center space-x-2">
                            <span>Ingresar al panel</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                        <a href="#modulos" class="w-full sm:w-auto px-8 py-4 rounded-xl font-semibold text-slate-300 hover:text-white bg-slate-900/80 hover:bg-slate-800 border border-slate-800 transition-all flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            <span>Explorar módulos</span>
                        </a>
                    </div>

                    <!-- Trust Stats Badge -->
                    <div class="pt-6 border-t border-slate-800/80 grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-xl mx-auto lg:mx-0">
                        <div>
                            <div class="font-heading text-2xl lg:text-3xl font-bold text-white">{{ number_format($stats['lotteries']) }}</div>
                            <div class="text-xs text-slate-400 font-medium mt-1">Rifas registradas</div>
                        </div>
                        <div>
                            <div class="font-heading text-2xl lg:text-3xl font-bold text-indigo-400">{{ number_format($stats['tickets']) }}</div>
                            <div class="text-xs text-slate-400 font-medium mt-1">Boletos emitidos</div>
                        </div>
                        <div>
                            <div class="font-heading text-2xl lg:text-3xl font-bold text-purple-400">{{ number_format($stats['clients']) }}</div>
                            <div class="text-xs text-slate-400 font-medium mt-1">Clientes cargados</div>
                        </div>
                        <div>
                            <div class="font-heading text-2xl lg:text-3xl font-bold text-emerald-400">{{ number_format($stats['winners']) }}</div>
                            <div class="text-xs text-slate-400 font-medium mt-1">Ganadores</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Admin Dashboard Preview -->
                <div class="lg:col-span-5 relative">
                    <div class="glass-card p-6 sm:p-8 rounded-3xl relative z-10 shadow-2xl border border-slate-800">
                        <div class="flex items-center justify-between pb-6 border-b border-slate-800">
                            <div>
                                <span class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">Panel de administración</span>
                                <h3 class="font-heading text-xl font-bold text-white mt-0.5">Control total de tu lotería</h3>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold border border-emerald-500/20 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                En línea
                            </span>
                        </div>

                        <!-- Modules list -->
                        <div class="py-5 space-y-3">
                            @php
                                $modules = [
                                    ['name' => 'Rifas y sorteos', 'desc' => 'Crea sorteos y configura premios', 'icon' => 'M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                                    ['name' => 'Boletos', 'desc' => 'Vende y gestiona cada boleto', 'icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z'],
                                    ['name' => 'Pagos y abonos', 'desc' => 'Cobra en Bs o divisas', 'icon' => 'M3 10h18M7 15h2m4 0h4M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z'],
                                    ['name' => 'Clientes', 'desc' => 'Historial y seguimiento', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                                    ['name' => 'Tasas de cambio', 'desc' => 'Actualiza la tasa oficial diaria', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                                    ['name' => 'Ganadores', 'desc' => 'Sortea y registra resultados', 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.539 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                                ];
                            @endphp

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($modules as $module)
                                    <div class="p-3.5 rounded-2xl bg-slate-900/70 border border-slate-800 flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $module['icon'] }}"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="font-heading text-sm font-bold text-white">{{ $module['name'] }}</h4>
                                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $module['desc'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="text-center border-t border-slate-800/80 pt-4">
                            <span class="text-xs text-slate-500">Gestión completa de rifas, sorteos y boletos en un solo lugar</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Modules Section -->
    <section id="modulos" class="py-20 relative bg-slate-950/50 border-t border-slate-800/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-semibold text-indigo-400 uppercase tracking-widest">Panel de administración</span>
                <h2 class="font-heading text-3xl sm:text-4xl font-bold text-white mt-2">Todo lo que necesitas para gestionar tu lotería</h2>
                <p class="text-slate-400 mt-3 text-base">Cada módulo del panel está diseñado para que el administrador opere sorteos de principio a fin sin fricciones.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Module: Rifas -->
                <div class="glass-card glass-card-hover rounded-3xl p-8 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="font-heading text-xl font-bold text-white">Rifas y sorteos</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Crea rifas con hasta 10.000 boletos, define fechas, descripción y número de ganadores. Edita y elimina sorteos en cualquier momento.</p>
                </div>

                <!-- Module: Boletos -->
                <div class="glass-card glass-card-hover rounded-3xl p-8 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z"/></svg>
                    </div>
                    <h3 class="font-heading text-xl font-bold text-white">Boletos</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Vende boletos a tus clientes seleccionando los números disponibles, cancela boletos y mantén el control de la grilla en tiempo real.</p>
                </div>

                <!-- Module: Pagos -->
                <div class="glass-card glass-card-hover rounded-3xl p-8 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h2m4 0h4M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                    </div>
                    <h3 class="font-heading text-xl font-bold text-white">Pagos y abonos</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Registra pagos en bolívares o divisas con conversión automática según la tasa del día. Abona, calcula vueltos y marca boletos como pagados.</p>
                </div>

                <!-- Module: Clientes -->
                <div class="glass-card glass-card-hover rounded-3xl p-8 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="font-heading text-xl font-bold text-white">Clientes</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Registra y edita clientes con documento, teléfono y dirección. Consulta su historial de boletos, pagos, deudas y premios ganados.</p>
                </div>

                <!-- Module: Tasas -->
                <div class="glass-card glass-card-hover rounded-3xl p-8 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="font-heading text-xl font-bold text-white">Tasas de cambio</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Registra la tasa oficial diaria y el sistema la aplica automáticamente al registrar pagos en bolívares, manteniendo todo consistente.</p>
                </div>

                <!-- Module: Ganadores -->
                <div class="glass-card glass-card-hover rounded-3xl p-8 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-pink-500/10 border border-pink-500/20 text-pink-400 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.539 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </div>
                    <h3 class="font-heading text-xl font-bold text-white">Ganadores y premiación</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Ejecuta el sorteo aleatorio entre los boletos pagados, asigna automáticamente los premios configurados y registra el orden de los ganadores.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Active Raffles Section -->
    <section id="rifas" class="py-20 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-semibold text-indigo-400 uppercase tracking-widest">Gestión de rifas</span>
                <h2 class="font-heading text-3xl sm:text-4xl font-bold text-white mt-2">Rifas administradas en el sistema</h2>
                <p class="text-slate-400 mt-3 text-base">Estas son las rifas cargadas en el panel. Ingresa para gestionar boletos, pagos y resultados.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                @forelse($lotteries as $lottery)
                    <div class="glass-card glass-card-hover rounded-3xl p-6 flex flex-col justify-between relative overflow-hidden">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $lottery->isActive == 'Disponible' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                                    {{ $lottery->isActive }}
                                </span>
                                <span class="text-xs font-medium text-slate-400">Rifa #{{ $lottery->id }}</span>
                            </div>

                            <h3 class="font-heading text-2xl font-bold text-white hover:text-indigo-400 transition-colors">
                                {{ $lottery->name }}
                            </h3>

                            <p class="text-slate-400 text-sm line-clamp-2">
                                {{ $lottery->description ?? 'Rifa administrada desde el panel del sistema.' }}
                            </p>

                            <div class="pt-4 border-t border-slate-800/80 space-y-3">
                                <div class="flex justify-between items-baseline">
                                    <span class="text-xs text-slate-400">Precio por boleto:</span>
                                    <span class="font-heading text-xl font-bold text-white">${{ number_format($lottery->ticket_price, 2) }}</span>
                                </div>

                                <div class="flex justify-between text-xs text-slate-400">
                                    <span>Boletos totales:</span>
                                    <span class="text-slate-200 font-semibold">{{ number_format($lottery->total_tickets) }}</span>
                                </div>

                                <div class="flex justify-between text-xs text-slate-400">
                                    <span>Fecha del sorteo:</span>
                                    <span class="text-indigo-400 font-medium">{{ $lottery->lottery_date }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 mt-6 border-t border-slate-800">
                            <a href="/admin/lotteries/{{ $lottery->id }}" class="w-full py-3 rounded-xl bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-center font-semibold text-sm transition-all block">
                                Gestionar rifa
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="glass-card rounded-3xl p-12 col-span-full text-center border border-slate-800">
                        <div class="w-16 h-16 rounded-2xl bg-slate-800/80 border border-slate-700 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <h3 class="font-heading text-xl font-bold text-white">No hay rifas registradas todavía</h3>
                        <p class="text-slate-400 text-sm mt-2 max-w-md mx-auto">Inicia sesión en el panel de administración para crear tu primera rifa y comenzar a gestionar boletos.</p>
                        <a href="/admin" class="btn-shimmer inline-flex items-center space-x-2 px-6 py-3 rounded-xl font-bold text-white text-sm mt-6">
                            <span>Ir al panel</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>
                @endforelse

            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="funciona" class="py-24 relative bg-slate-950/50 border-t border-slate-800/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-semibold text-indigo-400 uppercase tracking-widest">Flujo de trabajo</span>
                <h2 class="font-heading text-3xl sm:text-4xl font-bold text-white mt-2">¿Cómo gestiona una rifa el administrador?</h2>
                <p class="text-slate-400 mt-3 text-base">Desde la creación del sorteo hasta la entrega de premios, todo se controla desde el panel.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Step 1 -->
                <div class="glass-card p-8 rounded-3xl space-y-4 border border-slate-800 relative">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 font-heading text-2xl font-bold flex items-center justify-center">01</div>
                    <h3 class="font-heading text-xl font-bold text-white">Crea la rifa</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Registra la rifa con nombre, descripción, total de boletos, precio, fechas y los premios correspondientes.</p>
                </div>

                <!-- Step 2 -->
                <div class="glass-card p-8 rounded-3xl space-y-4 border border-slate-800 relative">
                    <div class="w-14 h-14 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-400 font-heading text-2xl font-bold flex items-center justify-center">02</div>
                    <h3 class="font-heading text-xl font-bold text-white">Vende boletos</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Selecciona un cliente y los números disponibles, registra los pagos en Bs o divisas y entrega los boletos al instante.</p>
                </div>

                <!-- Step 3 -->
                <div class="glass-card p-8 rounded-3xl space-y-4 border border-slate-800 relative">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-heading text-2xl font-bold flex items-center justify-center">03</div>
                    <h3 class="font-heading text-xl font-bold text-white">Ejecuta el sorteo</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">El día de la rifa, ejecuta el sorteo aleatorio entre los boletos pagados y el sistema asigna automáticamente los ganadores y premios.</p>
                </div>

                <!-- Step 4 -->
                <div class="glass-card p-8 rounded-3xl space-y-4 border border-slate-800 relative">
                    <div class="w-14 h-14 rounded-2xl bg-pink-500/10 border border-pink-500/20 text-pink-400 font-heading text-2xl font-bold flex items-center justify-center">04</div>
                    <h3 class="font-heading text-xl font-bold text-white">Revisa resultados</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">Consulta el listado de ganadores, su orden y los premios asignados. Todo queda registrado para auditoría.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Results / Winners Section -->
    <section id="resultados" class="py-24 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-semibold text-indigo-400 uppercase tracking-widest">Registro de resultados</span>
                <h2 class="font-heading text-3xl sm:text-4xl font-bold text-white mt-2">Ganadores registrados en el sistema</h2>
                <p class="text-slate-400 mt-3 text-base">Resultados de los sorteos ejecutados desde el panel, con el cliente, boleto y premio asignado de forma transparente.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($winners as $ticket)
                    @php
                        $winnerName = $ticket->client?->full_name ?? 'Ganador Anónimo';
                        $initials = str($winnerName)
                            ->split('/\s+/')
                            ->take(2)
                            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->implode('');
                        $prizeName = $ticket->prize?->name ?? 'Premio';
                        $prizeValue = (float) ($ticket->prize?->value ?? 0);
                        $lotteryName = $ticket->lottery?->name ?? 'Sorteo';
                    @endphp

                    <div class="glass-card p-6 rounded-3xl border border-slate-800 space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-500/20 text-emerald-300 font-bold flex items-center justify-center border border-emerald-500/30">
                                {{ $initials }}
                            </div>
                            <div>
                                <h4 class="font-heading font-bold text-white text-sm">{{ $winnerName }}</h4>
                                <span class="text-xs text-slate-400">Boleto #{{ $ticket->number }} — {{ $lotteryName }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-800/80">
                            <span class="text-xs font-bold text-amber-300 uppercase tracking-wider">🏆 {{ $prizeName }}</span>
                            <span class="font-heading text-sm font-bold text-white">${{ number_format($prizeValue) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="glass-card rounded-3xl p-12 col-span-full text-center border border-slate-800">
                        <div class="w-16 h-16 rounded-2xl bg-slate-800/80 border border-slate-700 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.539 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                        <h3 class="font-heading text-xl font-bold text-white">Aún no hay ganadores registrados</h3>
                        <p class="text-slate-400 text-sm mt-2 max-w-md mx-auto">Cuando ejecutes un sorteo desde el panel, los ganadores aparecerán aquí automáticamente.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Demo Access Section -->
    <section id="demo" class="py-20 bg-slate-950/80 border-t border-slate-800">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="glass-card rounded-3xl p-8 sm:p-12 border border-indigo-500/20 relative overflow-hidden">
                <div class="glow-blob w-72 h-72 bg-indigo-600/15 top-[-80px] right-[-80px]"></div>

                <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                    <div class="space-y-5">
                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/25 text-amber-300 text-xs font-bold uppercase tracking-wider">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Modo Demostración
                        </span>

                        <h2 class="font-heading text-3xl sm:text-4xl font-bold text-white">Explora el panel de administración</h2>

                        <p class="text-slate-400 text-sm leading-relaxed">
                            Accede con las credenciales de demostración y explora la gestión completa: tasas de cambio, clientes,
                            rifas, venta de boletos, cobros, premios y sorteo de ganadores.
                        </p>

                        <a href="/admin/login" class="btn-shimmer inline-flex items-center space-x-2 px-7 py-3.5 rounded-xl font-bold text-white text-sm shadow-lg shadow-indigo-500/25 transition-all hover:shadow-indigo-500/50">
                            <span>Ingresar al panel</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>

                    <div class="glass-card rounded-2xl p-6 border border-slate-700/60 space-y-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-300 uppercase tracking-wider">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            Credenciales del demo
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between rounded-xl bg-slate-900/70 border border-slate-700/60 px-4 py-3" x-data="{ copied: false }">
                                <span class="text-slate-400">Usuario</span>
                                <button type="button" @click="navigator.clipboard.writeText('admin@demo.com'); copied = true; setTimeout(() => copied = false, 1600)" class="group flex items-center gap-2 focus:outline-none">
                                    <code class="font-heading font-semibold text-indigo-300">admin@demo.com</code>
                                    <svg x-show="!copied" class="w-4 h-4 text-slate-500 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </div>

                            <div class="flex items-center justify-between rounded-xl bg-slate-900/70 border border-slate-700/60 px-4 py-3" x-data="{ copied: false }">
                                <span class="text-slate-400">Contraseña</span>
                                <button type="button" @click="navigator.clipboard.writeText('demo'); copied = true; setTimeout(() => copied = false, 1600)" class="group flex items-center gap-2 focus:outline-none">
                                    <code class="font-heading font-semibold text-indigo-300">demo</code>
                                    <svg x-show="!copied" class="w-4 h-4 text-slate-500 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-slate-800/80 pt-16 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 pb-12 border-b border-slate-800/80">

                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z"/></svg>
                        </div>
                        <span class="font-heading text-lg font-bold text-white">LotteryApp</span>
                    </div>
                    <p class="text-slate-400 text-xs leading-relaxed max-w-sm">
                        Plataforma de administración para la gestión integral de loterías y rifas: sorteos, boletos, pagos, clientes y premiación desde un único panel.
                    </p>
                </div>

                <div>
                    <h5 class="font-heading text-xs font-bold text-white uppercase tracking-wider mb-4">Navegación</h5>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><a href="#modulos" class="hover:text-white transition-colors">Módulos</a></li>
                        <li><a href="#rifas" class="hover:text-white transition-colors">Rifas</a></li>
                        <li><a href="#funciona" class="hover:text-white transition-colors">¿Cómo funciona?</a></li>
                        <li><a href="#resultados" class="hover:text-white transition-colors">Resultados</a></li>
                    </ul>
                </div>

                <div>
                    <h5 class="font-heading text-xs font-bold text-white uppercase tracking-wider mb-4">Administración</h5>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><a href="/admin" class="hover:text-white transition-colors">Panel de administración</a></li>
                        <li><a href="/admin/login" class="hover:text-white transition-colors">Iniciar sesión</a></li>
                        <li><a href="#demo" class="hover:text-white transition-colors">Credenciales de la demo</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500">
                <p>&copy; {{ date('Y') }} LotteryApp. Todos los derechos reservados.</p>
                <p class="mt-2 sm:mt-0">Sistema de gestión de loterías y rifas</p>
            </div>
        </div>
    </footer>

</body>
</html>









