<?php
// Common header file with theme support
function outputThemeHeaders() {
    ?>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined" rel="stylesheet">
    
    <!-- Material Components -->
    <link href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css" rel="stylesheet">
    
    <!-- Theme Support -->
    <link href="css/themes.css" rel="stylesheet">
    
    <!-- Common Meta Tags -->
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#4285F4">
    <?php
}

function outputThemeToggle() {
    ?>
    <!-- Theme Toggles -->
    <div class="theme-toggles">
        <div class="theme-toggle">
            <input type="checkbox" id="darkModeToggle" class="theme-toggle-input">
            <label for="darkModeToggle" class="theme-toggle-label">
                <i class="material-icons">dark_mode</i>
                <span>Dark Mode</span>
            </label>
        </div>
        <div class="theme-toggle">
            <input type="checkbox" id="colorblindModeToggle" class="theme-toggle-input">
            <label for="colorblindModeToggle" class="theme-toggle-label">
                <i class="material-icons">visibility</i>
                <span>Colorblind Mode</span>
            </label>
        </div>
    </div>

    <style>
    /* Theme Toggle Styles */
    .theme-toggles {
        padding: 8px 16px;
        margin-bottom: 16px;
        border-bottom: 1px solid var(--border-color);
    }

    .theme-toggle {
        display: flex;
        align-items: center;
        margin: 8px 0;
        padding: 8px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .theme-toggle:hover {
        background-color: var(--hover-bg, rgba(0, 0, 0, 0.05));
    }

    .theme-toggle-input {
        position: relative;
        width: 40px;
        height: 20px;
        margin: 0 8px;
        appearance: none;
        background-color: var(--text-secondary, #5F6368);
        border-radius: 20px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .theme-toggle-input:checked {
        background-color: var(--primary-color, #1a73e8);
    }

    .theme-toggle-input::before {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        top: 2px;
        left: 2px;
        background-color: white;
        transition: transform 0.3s;
    }

    .theme-toggle-input:checked::before {
        transform: translateX(20px);
    }

    .theme-toggle-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-primary, #2c3e50);
        cursor: pointer;
    }

    .theme-toggle-label i {
        font-size: 20px;
        color: var(--text-secondary, #5F6368);
    }

    .theme-toggle-label span {
        font-size: 14px;
        font-weight: 500;
    }
    </style>
    <?php
}

function outputThemeScripts() {
    ?>
    <!-- Theme Manager -->
    <script src="js/theme-manager.js"></script>
    <?php
}
?> 