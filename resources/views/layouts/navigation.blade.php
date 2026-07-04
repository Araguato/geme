@php
    $navIsLight = \App\Models\Setting::get('theme_variant', 'classic') === 'light';
    $navBg = $navIsLight ? 'bg-white' : 'bg-slate-950';
    $navBorder = $navIsLight ? 'border-gray-200' : 'border-slate-800';
    $navText = $navIsLight ? 'text-gray-900' : 'text-gray-100';
    $navMuted = $navIsLight ? 'text-gray-600' : 'text-gray-300';
    $navHoverBg = $navIsLight ? 'hover:bg-gray-100' : 'hover:bg-slate-800';
    $navDropdownBg = $navIsLight ? 'bg-white' : 'bg-slate-900';
@endphp
<nav x-data="{ open: false }" class="{{ $navBg }} border-b {{ $navBorder }}" style="background-color: {{ $navIsLight ? '#ffffff' : '#0b1220' }};">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current {{ $navText }}" style="color: {{ $navIsLight ? '#111827' : '#f3f4f6' }};" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="{{ $navText }}" style="color: {{ $navIsLight ? '#111827' : '#f3f4f6' }};">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md {{ $navMuted }} {{ $navBg }} {{ $navHoverBg }} focus:outline-none transition ease-in-out duration-150" style="background-color: {{ $navIsLight ? '#ffffff' : '#0b1220' }}; color: {{ $navIsLight ? '#4b5563' : '#d1d5db' }};">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="{{ $navDropdownBg }} {{ $navText }} border {{ $navBorder }}" style="background-color: {{ $navIsLight ? '#ffffff' : '#0f172a' }}; border-color: {{ $navIsLight ? '#e5e7eb' : '#1e293b' }};">
                            <x-dropdown-link :href="route('profile.edit')" class="{{ $navText }} {{ $navHoverBg }}" style="color: {{ $navIsLight ? '#111827' : '#f3f4f6' }};">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();"
                                        class="{{ $navText }} {{ $navHoverBg }}" style="color: {{ $navIsLight ? '#111827' : '#f3f4f6' }};">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md {{ $navMuted }} {{ $navHoverBg }} focus:outline-none transition duration-150 ease-in-out" style="color: {{ $navIsLight ? '#4b5563' : '#d1d5db' }};">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1 {{ $navDropdownBg }} border-b {{ $navBorder }}" style="background-color: {{ $navIsLight ? '#ffffff' : '#0f172a' }}; border-color: {{ $navIsLight ? '#e5e7eb' : '#1e293b' }};">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="{{ $navText }}" style="color: {{ $navIsLight ? '#111827' : '#f3f4f6' }};">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 {{ $navDropdownBg }}" style="background-color: {{ $navIsLight ? '#ffffff' : '#0f172a' }};">
            <div class="px-4">
                <div class="font-medium text-base {{ $navText }}" style="color: {{ $navIsLight ? '#111827' : '#f3f4f6' }};">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm {{ $navMuted }}" style="color: {{ $navIsLight ? '#4b5563' : '#9ca3af' }};">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')" class="{{ $navText }}" style="color: {{ $navIsLight ? '#111827' : '#f3f4f6' }};">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();"
                            class="{{ $navText }}" style="color: {{ $navIsLight ? '#111827' : '#f3f4f6' }};">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
