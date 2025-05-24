class ThemeManager {
    constructor() {
        console.log('ThemeManager initializing...');
        this.darkMode = localStorage.getItem('darkMode') === 'true';
        this.colorblindMode = localStorage.getItem('colorblindMode') === 'true';
        console.log('Initial state:', { darkMode: this.darkMode, colorblindMode: this.colorblindMode });
        this.init();
    }

    init() {
        // Apply saved preferences
        this.applyTheme();
        
        // Initialize theme toggles if they exist
        const darkToggle = document.getElementById('darkModeToggle');
        const colorblindToggle = document.getElementById('colorblindModeToggle');
        
        console.log('Found toggles:', { darkToggle: !!darkToggle, colorblindToggle: !!colorblindToggle });
        
        if (darkToggle) {
            darkToggle.checked = this.darkMode;
            darkToggle.addEventListener('change', () => {
                console.log('Dark mode toggle clicked');
                this.toggleDarkMode();
            });
        }
        
        if (colorblindToggle) {
            colorblindToggle.checked = this.colorblindMode;
            colorblindToggle.addEventListener('change', () => {
                console.log('Colorblind mode toggle clicked');
                this.toggleColorblindMode();
            });
        }

        // Observe theme changes
        this.observeThemeChanges();
    }

    observeThemeChanges() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'data-theme' || mutation.attributeName === 'data-colorblind') {
                    console.log('Theme attribute changed:', mutation.attributeName);
                    const darkToggle = document.getElementById('darkModeToggle');
                    const colorblindToggle = document.getElementById('colorblindModeToggle');
                    
                    if (darkToggle) {
                        darkToggle.checked = document.documentElement.getAttribute('data-theme') === 'dark';
                    }
                    if (colorblindToggle) {
                        colorblindToggle.checked = document.documentElement.getAttribute('data-colorblind') === 'true';
                    }
                }
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme', 'data-colorblind']
        });
    }

    applyTheme() {
        console.log('Applying theme:', { darkMode: this.darkMode, colorblindMode: this.colorblindMode });
        document.documentElement.setAttribute('data-theme', this.darkMode ? 'dark' : 'light');
        document.documentElement.setAttribute('data-colorblind', this.colorblindMode);
    }

    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        console.log('Toggling dark mode:', this.darkMode);
        localStorage.setItem('darkMode', this.darkMode);
        this.applyTheme();
    }

    toggleColorblindMode() {
        this.colorblindMode = !this.colorblindMode;
        console.log('Toggling colorblind mode:', this.colorblindMode);
        localStorage.setItem('colorblindMode', this.colorblindMode);
        this.applyTheme();
    }
}

// Initialize theme manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing ThemeManager...');
    window.themeManager = new ThemeManager();
}); 