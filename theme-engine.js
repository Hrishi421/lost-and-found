// theme-engine.js
// Handles Light, Dark, and System theme selections with smooth transitions and persistent storage

(function() {
    // 1. Core Engine Logic
    function applyTheme(theme) {
        const root = document.documentElement;
        root.setAttribute('data-theme-preference', theme);
        
        if (theme === 'system') {
            const systemIsDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            root.setAttribute('data-theme', systemIsDark ? 'dark' : 'light');
        } else {
            root.setAttribute('data-theme', theme);
        }
        
        updateSwitcherUI(theme);
    }

    function updateSwitcherUI(theme) {
        const switcher = document.getElementById('themeSwitcher');
        if (!switcher) return;
        
        const buttons = switcher.querySelectorAll('.theme-btn');
        const slider = switcher.querySelector('.theme-slider');
        
        buttons.forEach((btn, index) => {
            if (btn.getAttribute('data-theme-opt') === theme) {
                btn.classList.add('active');
                if (slider) {
                    slider.style.transform = `translateX(${index * 32}px)`;
                }
            } else {
                btn.classList.remove('active');
            }
        });
    }

    // Initialize state
    const savedTheme = localStorage.getItem('theme') || 'system';
    
    // Listen for system changes dynamically
    const systemMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    try {
        systemMediaQuery.addEventListener('change', () => {
            const activePref = localStorage.getItem('theme') || 'system';
            if (activePref === 'system') {
                applyTheme('system');
            }
        });
    } catch (e) {
        // Fallback for older browsers
        systemMediaQuery.addListener(() => {
            const activePref = localStorage.getItem('theme') || 'system';
            if (activePref === 'system') {
                applyTheme('system');
            }
        });
    }

    // Attach to window so it is accessible on interactive clicks
    window.ThemeEngine = {
        apply: applyTheme,
        initSwitcher: function() {
            const switcher = document.getElementById('themeSwitcher');
            if (!switcher) return;
            
            const buttons = switcher.querySelectorAll('.theme-btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const chosen = this.getAttribute('data-theme-opt');
                    localStorage.setItem('theme', chosen);
                    applyTheme(chosen);
                });
            });
            
            // Sync switcher view initially
            const current = localStorage.getItem('theme') || 'system';
            updateSwitcherUI(current);
        }
    };

    // Run engine setup once DOM is ready to sync widgets
    document.addEventListener('DOMContentLoaded', function() {
        window.ThemeEngine.initSwitcher();
    });
})();
