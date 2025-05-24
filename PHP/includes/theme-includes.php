<?php
// Theme includes file - Add this to all pages that need theme support

// Include the theme header functions
require_once 'theme_header.php';

// Function to add all theme-related headers
function addThemeHeaders() {
    // Output theme headers (CSS and meta tags)
    outputThemeHeaders();
}

// Function to add theme toggle controls
function addThemeToggles() {
    // Output theme toggle controls
    outputThemeToggle();
}

// Function to add theme-related scripts
function addThemeScripts() {
    // Output theme scripts
    outputThemeScripts();
}

// Function to add all theme components at once
function addAllThemeComponents() {
    addThemeHeaders();
    addThemeToggles();
    addThemeScripts();
}
?> 