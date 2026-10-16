<?php
/**
 * KaamMitra - Unified Icon Renderer Component
 * Renders Lucide icon markup with robust inline SVG fallback
 */

if (!function_exists('renderIcon')) {
    function renderIcon(string $name, int $size = 20): string {
        static $lucideMap = [
            'wrench' => 'wrench',
            'droplet' => 'droplet',
            'zap' => 'zap',
            'cpu' => 'cpu',
            'hammer' => 'hammer',
            'sparkles' => 'sparkles',
            'paint-brush' => 'palette',
            'lock' => 'lock',
            'bolt' => 'zap',
            'check' => 'check',
            'shield-check' => 'shield-check',
            'badge-check' => 'badge-check',
            'star' => 'star',
            'clock' => 'clock',
            'phone' => 'phone',
            'map-pin' => 'map-pin',
            'calendar-clock' => 'calendar',
            'user-check' => 'user-check',
            'tool' => 'wrench',
            'shield' => 'shield',
            'users' => 'users',
            'home' => 'home',
            'receipt' => 'receipt',
            'headset' => 'headphones',
            'wallet' => 'wallet',
            'gift' => 'gift',
            'shirt' => 'shirt',
            'heart-handshake' => 'heart-handshake',
            'search' => 'search',
            'chevron-down' => 'chevron-down'
        ];

        static $icons = [
            'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>',
            'droplet' => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>',
            'zap' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>',
            'cpu' => '<rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line>',
            'hammer' => '<path d="M15 12l-8.5 8.5a2.12 2.12 0 1 1-3-3L12 9"></path><path d="M17.64 6.36a3 3 0 0 1 0 4.24l-1.41 1.41-4.24-4.24 1.41-1.41a3 3 0 0 1 4.24 0z"></path><path d="M14 2l4 4"></path>',
            'sparkles' => '<path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>',
            'paint-brush' => '<path d="M18.37 2.63 14 7l-1.59-1.59a2 2 0 0 0-2.82 0L8 7l9 9 1.59-1.59a2 2 0 0 0 0-2.82L17 10l4.37-4.37a2.12 2.12 0 1 0-3-3Z"></path><path d="M9 8c-2 3-4 3.5-7 4l8 8c.5-3 1-5 4-7"></path><path d="M14.5 17.5 4.5 15"></path>',
            'lock' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
            'bolt' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>',
            'check' => '<polyline points="20 6 9 17 4 12"></polyline>',
            'shield-check' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path>',
            'badge-check' => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"></path><path d="m9 12 2 2 4-4"></path>',
            'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>',
            'clock' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
            'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>',
            'map-pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>',
            'calendar-clock' => '<path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"></path><path d="M16 2v4"></path><path d="M8 2v4"></path><path d="M3 10h18"></path><circle cx="17" cy="17" r="5"></circle><polyline points="17 14 17 17 19 19"></polyline>',
            'user-check' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline>',
            'search' => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
            'chevron-down' => '<polyline points="6 9 12 15 18 9"></polyline>'
        ];

        $lucideName = $lucideMap[$name] ?? $name;
        $content = $icons[$name] ?? '<circle cx="12" cy="12" r="10"></circle>';

        return sprintf(
            '<i data-lucide="%s" class="km-lucide km-icon-%s" style="width: %dpx; height: %dpx; display: inline-flex; align-items: center; justify-content: center;"><svg width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%s</svg></i>',
            htmlspecialchars($lucideName), htmlspecialchars($name), $size, $size, $size, $size, $content
        );
    }
}
