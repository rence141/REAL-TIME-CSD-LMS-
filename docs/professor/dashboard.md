# Professor Dashboard Documentation

## Overview
The professor dashboard provides a centralized interface for managing courses, viewing schedules, and accessing key teaching functions. The interface supports both light and dark modes, as well as colorblind-friendly color schemes for enhanced accessibility.

## Theme Support
The dashboard implements a comprehensive theming system with the following features:

### Dark Mode
- Toggle located in the navigation bar
- Automatically saves preference
- Smooth transitions between light and dark themes
- Optimized contrast ratios for readability
- Consistent styling across all dashboard components

### Colorblind Mode
- Accessible through the visibility toggle
- Compatible with both light and dark modes
- Uses carefully selected color palettes for:
  - Primary actions (Blue/Bright Blue)
  - Success states (Orange/Bright Orange)
  - Warning states (Teal/Bright Teal)
  - Error states (Magenta/Bright Magenta)

## Page Components

### Header Section
```html
<!-- Include theme support in header -->
<?php 
require_once('includes/theme_header.php');
outputThemeHeaders();
?>
```

### Navigation Bar
- Theme toggle controls
- Material Design icons
- Responsive layout
- Smooth hover effects

### Dashboard Cards
Each card component features:
- Consistent padding and spacing
- Proper contrast in both themes
- Hover animations
- Shadow effects
- Accessible text sizes

### Interactive Elements
All interactive elements maintain:
- Clear focus states
- Hover feedback
- Touch-friendly tap targets
- Proper ARIA labels

## Theme Implementation

### CSS Variables
The dashboard uses CSS custom properties for consistent theming:
```css
/* Light Mode */
--bg-primary: #f5f5f5
--text-primary: #3c4043
--card-bg: #ffffff

/* Dark Mode */
--bg-primary: #202124
--text-primary: #e8eaed
--card-bg: #35363a
```

### JavaScript Integration
Theme preferences are managed through:
```javascript
// Initialize theme manager
window.themeManager = new ThemeManager();
```

## Best Practices

### Accessibility
- Maintain WCAG 2.1 compliance
- Support keyboard navigation
- Provide proper contrast ratios
- Include screen reader support

### Performance
- Smooth transitions (0.3s duration)
- Optimized animations
- Efficient theme switching
- Local storage for preferences

### Responsive Design
- Fluid layouts
- Mobile-friendly controls
- Adaptive card grids
- Flexible spacing

## Usage Instructions

1. Theme Switching:
   - Click the moon icon to toggle dark mode
   - Click the eye icon to toggle colorblind mode
   - Changes are saved automatically

2. Navigation:
   - Use the sidebar for main navigation
   - Cards provide quick access to key functions
   - Action buttons feature clear iconography

3. Customization:
   - Theme preferences persist across sessions
   - Interface adapts to system preferences
   - Color schemes respect accessibility needs

## Technical Details

### Required Files
- `theme_header.php`: Common theme components
- `themes.css`: Style definitions
- `theme-manager.js`: Theme switching logic

### Integration
```php
// Header
outputThemeHeaders();

// Navigation
outputThemeToggle();

// Footer
outputThemeScripts();
```

### Browser Support
- Chrome 80+
- Firefox 75+
- Safari 13.1+
- Edge 80+

## Troubleshooting

### Common Issues
1. Theme not persisting:
   - Clear browser cache
   - Check localStorage permissions
   - Verify JavaScript execution

2. Incorrect colors:
   - Refresh the page
   - Check CSS loading
   - Verify theme attribute values

3. Animation issues:
   - Check browser compatibility
   - Verify CSS transition support
   - Review performance settings

## Support

For technical assistance or bug reports:
1. Contact system administrator
2. Check browser console for errors
3. Verify all required files are loaded
4. Ensure proper PHP includes 