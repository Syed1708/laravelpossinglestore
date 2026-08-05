<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ route($dashboardRoute::name('index')) }}" class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <span class="sidebar-logo-text">{{ $branding['app_name'] ?? config('app.name', 'Laravel') }}</span>
        </a>
        @if (config('tyro-dashboard.collapsible_sidebar', false))
            <button class="sidebar-collapse-btn" onclick="toggleSidebarCollapse()" aria-label="Collapse sidebar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        @endif
    </div>
    @if (config('tyro-dashboard.collapsible_sidebar', false))
        <button class="sidebar-expand-btn" onclick="toggleSidebarCollapse()" aria-label="Expand sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    @endif

    <nav class="sidebar-nav sidebar-accordion" data-sidebar-accordion
        data-sidebar-accordion-compact="{{ config('tyro-dashboard.branding.sidebar_accordion_compact', false) ? 'true' : 'false' }}">
        
        <!-- 1. MAIN MENU -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Main Menu</div>
            <a href="{{ route($dashboardRoute::name('index')) }}"
                class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('index')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>
            
            <a href="{{ route($dashboardRoute::name('profile')) }}"
                class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('profile*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ __('My Profile') }}
            </a>

            <!-- Web POS Launch Link -->
            <a href="/pos" target="_blank" class="sidebar-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.559c.211.135.442.2.673.2.226 0 .453-.064.661-.19a1.122 1.122 0 00.465-.916c0-.528-.4-.954-.925-1.042l-.4-.067c-.525-.088-.925-.514-.925-1.042 0-.376.183-.728.497-.918a1.121 1.121 0 011.077-.14l.879.56M12 3v18" />
                </svg>
                ⌨️ Web POS Terminal
            </a>
        </div>

        <!-- 2. KITCHEN & POS DISPLAYS -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Kitchen & POS Display</div>
            <a href="{{ route('admin.kds.chef') }}" target="_blank" class="sidebar-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                👨‍🍳 Chef Screen (KDS)
            </a>

            <a href="{{ route('admin.kds.packer') }}" target="_blank" class="sidebar-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25M19.5 3H4.5A1.5 1.5 0 003 4.5v3a1.5 1.5 0 001.5 1.5h15A1.5 1.5 0 0021 7.5v-3A1.5 1.5 0 0019.5 3z" />
                </svg>
                📦 Cashier/Packer Screen
            </a>
        </div>

        <!-- 🚀 3. DYNAMICALLY GROUPED RESOURCES BY 'group' KEY -->
        @php
            $resourcesByGroup = [];
            $rawResources = $allResources ?? config('tyro-dashboard.resources', []);
            foreach ($rawResources as $key => $resource) {
                $groupName = $resource['group'] ?? 'Resources';
                $resourcesByGroup[$groupName][$key] = $resource;
            }
        @endphp

        @foreach ($resourcesByGroup as $groupName => $groupResources)
            <div class="sidebar-section">
                <div class="sidebar-section-title">{{ $groupName }}</div>
                @foreach ($groupResources as $key => $resource)
                    @php
                        $canAccess = true;
                        if (isset($resource['roles']) && !empty($resource['roles'])) {
                            $canAccess = false;
                            $user = auth()->user();
                            if ($user && method_exists($user, 'tyroRoleSlugs')) {
                                $userRoles = $user->tyroRoleSlugs();
                                foreach ($resource['roles'] as $role) {
                                    if (in_array($role, $userRoles)) {
                                        $canAccess = true;
                                        break;
                                    }
                                }
                                if (!$canAccess && isset($resource['readonly']) && !empty($resource['readonly'])) {
                                    foreach ($resource['readonly'] as $role) {
                                        if (in_array($role, $userRoles)) {
                                            $canAccess = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    @endphp

                    @if ($canAccess)
                        <a href="{{ route($dashboardRoute::name('resources.index'), $key) }}"
                            class="sidebar-link {{ request()->is('*resources/' . $key . '*') ? 'active' : '' }}">
                            @if (isset($resource['icon']))
                                {!! $resource['icon'] !!}
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            @endif
                            {{ $resource['title'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endforeach

        <!-- 4. ADMINISTRATION SECTION -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Administration</div>
            
            <a href="{{ route($dashboardRoute::name('users.index')) }}"
                class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('users.*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                {{ __('Users') }}
            </a>
            
            <a href="{{ route($dashboardRoute::name('roles.index')) }}"
                class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('roles.*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                {{ __('Roles') }}
            </a>
            
            <a href="{{ route($dashboardRoute::name('privileges.index')) }}"
                class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('privileges.*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                {{ __('Privileges') }}
            </a>
        </div>

        <!-- 5. REPORTS SECTION -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Reports & Analysis</div>
            <a href="{{ route('admin.reports.index') }}"
                class="sidebar-link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                Reports (PDF)
            </a>

            <a href="{{ route('admin.menu-engineering.index') }}" 
                class="sidebar-link {{ request()->routeIs('admin.menu-engineering.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.559c.211.135.442.2.673.2.226 0 .453-.064.661-.19a1.122 1.122 0 00.465-.916c0-.528-.4-.954-.925-1.042l-.4-.067c-.525-.088-.925-.514-.925-1.042 0-.376.183-.728.497-.918a1.121 1.121 0 011.077-.14l.879.56M12 3v18" />
                </svg>
                Margin & Profitability
            </a>
        </div>
    </nav>
</aside>