<!-- Standard Dashboard Header -->
<header class="dash-header">
    <div class="dash-header-search">
        <input type="text" id="adminSearchInput" placeholder="Search across dashboard..." oninput="filterDashboardTable && filterDashboardTable()">
    </div>
    <div class="dash-header-actions" style="display: flex; align-items: center; gap: 0.75rem;">
        <!-- Modern Theme Switcher -->
        <div class="theme-switcher" id="themeSwitcher">
            <button type="button" class="theme-btn" data-theme-opt="light" title="Light Theme">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
            </button>
            <button type="button" class="theme-btn" data-theme-opt="dark" title="Dark Theme">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </button>
            <button type="button" class="theme-btn" data-theme-opt="system" title="System Theme">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
            </button>
            <div class="theme-slider"></div>
        </div>
        <a href="index.php" class="btn btn-outline" style="font-size:0.85rem; padding: 0.4rem 1rem;">View Public Site</a>
    </div>
</header>
