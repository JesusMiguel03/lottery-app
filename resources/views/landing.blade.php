<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LotteryApp — Gestión de rifas, sorteos y boletos</title>
    <meta name="description" content="Plataforma de gestión editorial y operativa para rifas, sorteos, boletos y ganadores en tiempo real.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS Vite / App -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #F6F5F1;
            color: #111315;
            overflow-x: hidden;
        }

        .font-editorial {
            font-family: 'Instrument Serif', Georgia, serif;
        }

        .font-mono-tabular {
            font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;
            font-variant-numeric: tabular-nums;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>
<body class="antialiased bg-[#F6F5F1] text-[#111315] selection:bg-[#0F766E] selection:text-white" x-data="{ mobileMenu: false }">

    <!-- Sticky Minimalist Navbar -->
    <header class="sticky top-0 z-50 bg-[#F6F5F1]/95 backdrop-blur-sm border-b border-[#E4E2DC]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <!-- Typographic Brand Logo + Emerald Dot -->
                <a href="#" class="inline-flex items-center gap-2 group">
                    <span class="font-editorial text-2xl sm:text-[1.75rem] tracking-tight text-[#111315] leading-none">
                        LotteryApp
                    </span>
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex items-center gap-8" aria-label="Navegación principal">
                    <a href="#sorteos" class="text-sm font-medium text-[#575B62] hover:text-[#111315] transition-colors">Sorteos</a>
                    <a href="#como-funciona" class="text-sm font-medium text-[#575B62] hover:text-[#111315] transition-colors">Cómo funciona</a>
                    <a href="#ganadores" class="text-sm font-medium text-[#575B62] hover:text-[#111315] transition-colors">Ganadores</a>
                </nav>

                <!-- Desktop CTA -->
                <div class="hidden md:flex items-center">
                    <a href="/admin" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#111315] hover:bg-[#0F766E] text-white text-sm font-medium transition-colors rounded-sm">
                        <span>Ir al Panel</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                </div>

                <!-- Mobile Menu Toggle -->
                <div class="md:hidden">
                    <button
                        type="button"
                        @click="mobileMenu = !mobileMenu"
                        :aria-expanded="mobileMenu.toString()"
                        aria-label="Abrir menú"
                        class="p-2 text-[#111315] border border-[#E4E2DC] bg-white rounded-sm focus:outline-none"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7h16M4 12h16M4 17h16" />
                            <path x-show="mobileMenu" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div x-show="mobileMenu" x-cloak class="md:hidden bg-white border-t border-[#E4E2DC] px-4 py-5 space-y-3">
            <a href="#sorteos" @click="mobileMenu = false" class="block py-2 text-sm font-medium text-[#111315] border-b border-[#E4E2DC]">Sorteos</a>
            <a href="#como-funciona" @click="mobileMenu = false" class="block py-2 text-sm font-medium text-[#111315] border-b border-[#E4E2DC]">Cómo funciona</a>
            <a href="#ganadores" @click="mobileMenu = false" class="block py-2 text-sm font-medium text-[#111315] border-b border-[#E4E2DC]">Ganadores</a>
            <div class="pt-2">
                <a href="/admin" @click="mobileMenu = false" class="block w-full text-center px-5 py-3 bg-[#111315] text-white text-sm font-medium rounded-sm">
                    Ir al Panel
                </a>
            </div>
        </div>
    </header>

    <!-- Asymmetric Hero Section -->
    <section class="border-b border-[#E4E2DC]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-28">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-14 items-center">

                <!-- Left Column: Editorial Headline & Actions -->
                <div class="lg:col-span-7 space-y-8">
                    <div class="inline-flex items-center gap-2.5 border border-[#E4E2DC] bg-white px-3 py-1.5 rounded-sm">
                        <span class="font-mono-tabular text-[11px] font-medium uppercase tracking-[0.14em] text-[#575B62]">
                            SISTEMA DE GESTIÓN DE RIFAS
                        </span>
                    </div>

                    <h1 class="font-editorial text-5xl sm:text-6xl lg:text-[4.5rem] leading-[1.02] tracking-tight text-[#111315]">
                        Cada boleto cuenta.<br>
                        <span class="italic text-[#0F766E]">Cada sorteo, transparente.</span>
                    </h1>

                    <p class="text-base sm:text-lg text-[#575B62] max-w-xl leading-relaxed">
                        Administra rifas, controla la emisión de boletos, registra pagos en bolívares o divisas y audita cada ganador desde un panel preciso y en tiempo real.
                    </p>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-2">
                        <a href="/admin" class="inline-flex items-center justify-center gap-2.5 px-7 py-3.5 bg-[#111315] hover:bg-[#0F766E] text-white text-sm font-semibold transition-colors rounded-sm">
                            <span>Ir al Panel</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </a>
                        <a href="#sorteos" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-white hover:bg-[#F6F5F1] text-[#111315] border border-[#E4E2DC] text-sm font-semibold transition-colors rounded-sm">
                            <span>Ver sorteos activos</span>
                            <span class="font-mono-tabular text-xs text-[#0F766E]">↓</span>
                        </a>
                    </div>
                </div>

                <!-- Right Column: Physical Ticket Card with Live $stats -->
                <div class="lg:col-span-5">
                    <div class="relative bg-white border border-[#E4E2DC] rounded-sm">
                        <!-- Ticket Header -->
                        <div class="px-6 py-5 flex items-center justify-between border-b border-[#E4E2DC]">
                            <div>
                                <span class="font-mono-tabular text-[11px] uppercase tracking-[0.14em] text-[#575B62] block">
                                    BOLETO DE CONTROL · EN VIVO
                                </span>
                                <h2 class="font-editorial text-2xl text-[#111315] mt-0.5">
                                    Estado del sistema
                                </h2>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 border border-[#0F766E]/30 bg-[#0F766E]/5 text-[#0F766E] font-mono-tabular text-[11px] font-medium uppercase tracking-wider rounded-sm">
                                Activo
                            </span>
                        </div>

                        <!-- Perforated Divider with Side Notches -->
                        <div class="relative flex items-center px-6 py-3">
                            <span class="absolute -left-2.5 w-5 h-5 rounded-full bg-[#F6F5F1] border-r border-[#E4E2DC]" aria-hidden="true"></span>
                            <div class="w-full border-t border-dashed border-[#E4E2DC]"></div>
                            <span class="absolute -right-2.5 w-5 h-5 rounded-full bg-[#F6F5F1] border-l border-[#E4E2DC]" aria-hidden="true"></span>
                        </div>

                        <!-- Live Metrics Grid (2x2) -->
                        <div class="px-6 pb-6 pt-2 grid grid-cols-2 gap-px bg-[#E4E2DC] border-y border-[#E4E2DC]">
                            <div class="bg-white p-5">
                                <span class="font-mono-tabular text-[11px] uppercase tracking-[0.12em] text-[#575B62] block">
                                    01 / Rifas
                                </span>
                                <div class="font-mono-tabular text-3xl sm:text-4xl font-semibold text-[#111315] mt-2 tracking-tight">
                                    {{ number_format($stats['lotteries'] ?? 0) }}
                                </div>
                                <p class="text-xs text-[#575B62] mt-1">Sorteos registrados</p>
                            </div>

                            <div class="bg-white p-5">
                                <span class="font-mono-tabular text-[11px] uppercase tracking-[0.12em] text-[#575B62] block">
                                    02 / Boletos
                                </span>
                                <div class="font-mono-tabular text-3xl sm:text-4xl font-semibold text-[#0F766E] mt-2 tracking-tight">
                                    {{ number_format($stats['tickets'] ?? 0) }}
                                </div>
                                <p class="text-xs text-[#575B62] mt-1">Boletos emitidos</p>
                            </div>

                            <div class="bg-white p-5">
                                <span class="font-mono-tabular text-[11px] uppercase tracking-[0.12em] text-[#575B62] block">
                                    03 / Clientes
                                </span>
                                <div class="font-mono-tabular text-3xl sm:text-4xl font-semibold text-[#111315] mt-2 tracking-tight">
                                    {{ number_format($stats['clients'] ?? 0) }}
                                </div>
                                <p class="text-xs text-[#575B62] mt-1">Participantes</p>
                            </div>

                            <div class="bg-white p-5">
                                <span class="font-mono-tabular text-[11px] uppercase tracking-[0.12em] text-[#575B62] block">
                                    04 / Ganadores
                                </span>
                                <div class="font-mono-tabular text-3xl sm:text-4xl font-semibold text-[#B45309] mt-2 tracking-tight">
                                    {{ number_format($stats['winners'] ?? 0) }}
                                </div>
                                <p class="text-xs text-[#575B62] mt-1">Premios asignados</p>
                            </div>
                        </div>

                        <!-- Ticket Stub Footer -->
                        <div class="px-6 py-4 flex items-center justify-between text-xs text-[#575B62]">
                            <span class="font-mono-tabular uppercase tracking-wider">REGISTRO AUDITADO</span>
                            <span class="font-mono-tabular text-[#111315]">SERIE {{ date('Y') }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Active Lotteries Section (#sorteos) -->
    <section id="sorteos" class="py-20 sm:py-24 border-b border-[#E4E2DC]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-10 mb-12 border-b border-[#E4E2DC]">
                <div class="max-w-2xl">
                    <span class="font-mono-tabular text-xs uppercase tracking-[0.14em] text-[#0F766E] font-medium">
                        CATÁLOGO DE SORTEOS
                    </span>
                    <h2 class="font-editorial text-4xl sm:text-5xl text-[#111315] mt-2 leading-tight">
                        Sorteos activos y programados
                    </h2>
                </div>
                <p class="text-sm text-[#575B62] max-w-sm leading-relaxed">
                    Consulta disponibilidad, valor del boleto y fecha oficial de cada rifa administrada en la plataforma.
                </p>
            </div>

            <!-- Lotteries Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($lotteries as $lottery)
                    <article class="bg-white border border-[#E4E2DC] rounded-sm flex flex-col justify-between transition-colors hover:border-[#111315]/40">
                        <div class="p-6 sm:p-7 space-y-5">
                            <!-- Card Meta Bar -->
                            <div class="flex items-center justify-between gap-3 pb-4 border-b border-[#E4E2DC]">
                                @if($lottery->isActive == 'Disponible')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#0F766E]/10 text-[#0F766E] border border-[#0F766E]/25 font-mono-tabular text-[11px] font-medium uppercase tracking-wider rounded-sm">
                                        {{ $lottery->isActive }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#B45309]/10 text-[#B45309] border border-[#B45309]/25 font-mono-tabular text-[11px] font-medium uppercase tracking-wider rounded-sm">
                                        {{ $lottery->isActive }}
                                    </span>
                                @endif
                                <span class="font-mono-tabular text-xs text-[#575B62]">
                                    #{{ $lottery->id }}
                                </span>
                            </div>

                            <!-- Title & Description -->
                            <div class="space-y-2">
                                <h3 class="font-editorial text-2xl sm:text-[1.75rem] text-[#111315] leading-snug">
                                    {{ $lottery->name }}
                                </h3>
                                <p class="text-sm text-[#575B62] leading-relaxed line-clamp-2">
                                    {{ $lottery->description ?? 'Rifa administrada desde el panel del sistema.' }}
                                </p>
                            </div>

                            <!-- Spec Table -->
                            <dl class="pt-4 border-t border-dashed border-[#E4E2DC] space-y-2.5 text-sm">
                                <div class="flex items-baseline justify-between">
                                    <dt class="text-xs uppercase tracking-wider text-[#575B62]">Precio por boleto</dt>
                                    <dd class="font-mono-tabular text-lg font-semibold text-[#111315]">
                                        ${{ number_format($lottery->ticket_price, 2) }}
                                    </dd>
                                </div>
                                <div class="flex items-baseline justify-between">
                                    <dt class="text-xs uppercase tracking-wider text-[#575B62]">Emisión total</dt>
                                    <dd class="font-mono-tabular font-medium text-[#111315]">
                                        {{ number_format($lottery->total_tickets) }} boletos
                                    </dd>
                                </div>
                                <div class="flex items-baseline justify-between">
                                    <dt class="text-xs uppercase tracking-wider text-[#575B62]">Fecha del sorteo</dt>
                                    <dd class="font-mono-tabular font-medium text-[#0F766E]">
                                        {{ $lottery->lottery_date }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Action Footer -->
                        <div class="px-6 py-4 bg-[#F6F5F1]/60 border-t border-[#E4E2DC]">
                            <a href="/admin/lotteries/{{ $lottery->id }}" class="w-full inline-flex items-center justify-between text-sm font-semibold text-[#111315] hover:text-[#0F766E] transition-colors">
                                <span>Gestionar rifa</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full bg-white border border-[#E4E2DC] rounded-sm p-12 text-center">
                        <span class="font-mono-tabular text-xs uppercase tracking-[0.14em] text-[#575B62]">
                            SIN REGISTROS DISPONIBLES
                        </span>
                        <h3 class="font-editorial text-3xl text-[#111315] mt-2">
                            No hay sorteos activos por el momento
                        </h3>
                        <p class="text-sm text-[#575B62] mt-2 max-w-md mx-auto leading-relaxed">
                            Ingresa al panel de administración para configurar una nueva rifa, definir el rango de boletos y publicar la fecha del sorteo.
                        </p>
                        <div class="mt-6">
                            <a href="/admin" class="inline-flex items-center gap-2 px-6 py-3 bg-[#111315] hover:bg-[#0F766E] text-white text-sm font-medium transition-colors rounded-sm">
                                <span>Ir al Panel</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 12h14m-6-6 6 6-6 6"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- How It Works Section (#como-funciona) -->
    <section id="como-funciona" class="py-20 sm:py-24 bg-white border-b border-[#E4E2DC]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mb-14">
                <span class="font-mono-tabular text-xs uppercase tracking-[0.14em] text-[#0F766E] font-medium">
                    FLUJO OPERATIVO
                </span>
                <h2 class="font-editorial text-4xl sm:text-5xl text-[#111315] mt-2 leading-tight">
                    Tres pasos claros, del boleto al premio
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 border-t border-b border-[#E4E2DC] divide-y md:divide-y-0 md:divide-x divide-[#E4E2DC]">
                <!-- Step 01 -->
                <div class="py-8 md:py-10 md:pr-8 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="font-mono-tabular text-sm font-semibold text-[#0F766E]">01</span>
                        <span class="font-mono-tabular text-[11px] uppercase tracking-[0.12em] text-[#575B62]">CONFIGURACIÓN</span>
                    </div>
                    <h3 class="font-editorial text-2xl sm:text-3xl text-[#111315]">
                        Define la rifa y sus premios
                    </h3>
                    <p class="text-sm text-[#575B62] leading-relaxed">
                        Establece el nombre del sorteo, el valor unitario del boleto, la emisión total de números y la fecha oficial de cierre desde el panel.
                    </p>
                </div>

                <!-- Step 02 -->
                <div class="py-8 md:py-10 md:px-8 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="font-mono-tabular text-sm font-semibold text-[#0F766E]">02</span>
                        <span class="font-mono-tabular text-[11px] uppercase tracking-[0.12em] text-[#575B62]">EMISIÓN Y COBRO</span>
                    </div>
                    <h3 class="font-editorial text-2xl sm:text-3xl text-[#111315]">
                        Asigna boletos y registra pagos
                    </h3>
                    <p class="text-sm text-[#575B62] leading-relaxed">
                        Selecciona los números disponibles para cada cliente, aplica la tasa del día automáticamente y lleva control exacto de abonos y solvencia.
                    </p>
                </div>

                <!-- Step 03 -->
                <div class="py-8 md:py-10 md:pl-8 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="font-mono-tabular text-sm font-semibold text-[#B45309]">03</span>
                        <span class="font-mono-tabular text-[11px] uppercase tracking-[0.12em] text-[#575B62]">ADJUDICACIÓN</span>
                    </div>
                    <h3 class="font-editorial text-2xl sm:text-3xl text-[#111315]">
                        Ejecuta el sorteo y publica actas
                    </h3>
                    <p class="text-sm text-[#575B62] leading-relaxed">
                        Realiza la selección verificada entre boletos pagados, asigna los premios correspondientes y deja constancia pública del resultado.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Winners Section (#ganadores) -->
    <section id="ganadores" class="py-20 sm:py-24 border-b border-[#E4E2DC]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-10 mb-12 border-b border-[#E4E2DC]">
                <div class="max-w-2xl">
                    <span class="font-mono-tabular text-xs uppercase tracking-[0.14em] text-[#B45309] font-medium">
                        HISTORIAL VERIFICADO
                    </span>
                    <h2 class="font-editorial text-4xl sm:text-5xl text-[#111315] mt-2 leading-tight">
                        Últimos ganadores registrados
                    </h2>
                </div>
                <p class="text-sm text-[#575B62] max-w-sm leading-relaxed">
                    Registro público de boletos favorecidos en sorteos ejecutados dentro del sistema.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($winners as $ticket)
                    @php
                        $winnerName = $ticket->client?->full_name ?? 'Ganador Anónimo';
                        $initials = str($winnerName)
                            ->split('/\s+/')
                            ->take(2)
                            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->implode('');
                        $lotteryName = $ticket->lottery?->name ?? 'Sorteo';
                        $prizeName = $ticket->prize?->name ?? null;
                        $prizeValue = (float) ($ticket->prize?->value ?? 0);
                    @endphp

                    <article class="bg-white border border-[#E4E2DC] rounded-sm p-6 flex flex-col justify-between gap-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-11 h-11 rounded-sm bg-[#F6F5F1] border border-[#E4E2DC] text-[#111315] font-mono-tabular text-sm font-semibold flex items-center justify-center shrink-0">
                                    {{ $initials }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-[#111315] text-base truncate">
                                        {{ $winnerName }}
                                    </h3>
                                    <p class="text-xs text-[#575B62] truncate mt-0.5">
                                        {{ $lotteryName }}
                                    </p>
                                </div>
                            </div>

                            <span class="font-mono-tabular text-xs font-semibold px-2.5 py-1 bg-[#B45309]/10 text-[#B45309] border border-[#B45309]/25 rounded-sm shrink-0">
                                Boleto #{{ $ticket->number }}
                            </span>
                        </div>

                        @if($prizeName || $prizeValue > 0)
                            <div class="pt-4 border-t border-dashed border-[#E4E2DC] flex items-center justify-between gap-3 text-xs">
                                <span class="font-medium text-[#575B62] uppercase tracking-wider truncate">
                                    {{ $prizeName ?? 'Premio adjudicado' }}
                                </span>
                                @if($prizeValue > 0)
                                    <span class="font-mono-tabular font-semibold text-[#111315] shrink-0">
                                        ${{ number_format($prizeValue) }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="col-span-full bg-white border border-[#E4E2DC] rounded-sm p-12 text-center">
                        <span class="font-mono-tabular text-xs uppercase tracking-[0.14em] text-[#575B62]">
                            REGISTRO EN ESPERA
                        </span>
                        <h3 class="font-editorial text-3xl text-[#111315] mt-2">
                            Aún no hay ganadores registrados
                        </h3>
                        <p class="text-sm text-[#575B62] mt-2 max-w-md mx-auto leading-relaxed">
                            Cuando se ejecute el próximo sorteo desde el panel de administración, el boleto favorecido y su titular aparecerán publicados aquí.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Clean Editorial Footer -->
    <footer class="bg-[#F6F5F1] py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div class="space-y-1.5">
                    <div class="inline-flex items-center gap-2">
                        <span class="font-editorial text-2xl text-[#111315] leading-none">LotteryApp</span>
                    </div>
                    <p class="text-xs text-[#575B62]">
                        &copy; {{ date('Y') }} LotteryApp. Gestión de rifas, sorteos y boletos en tiempo real.
                    </p>
                </div>

                <div class="flex items-center gap-6 text-sm">
                    <a href="#sorteos" class="text-[#575B62] hover:text-[#111315] transition-colors">Sorteos</a>
                    <a href="#como-funciona" class="text-[#575B62] hover:text-[#111315] transition-colors">Cómo funciona</a>
                    <a href="#ganadores" class="text-[#575B62] hover:text-[#111315] transition-colors">Ganadores</a>
                    <a href="/admin" class="font-semibold text-[#0F766E] hover:text-[#111315] transition-colors">Panel de administración →</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
