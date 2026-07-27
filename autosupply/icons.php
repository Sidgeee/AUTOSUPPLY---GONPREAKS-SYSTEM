<?php
// icons.php
// Inline SVG icons - these render even if the Font Awesome CDN can't load
// (which is what was causing blank boxes instead of icons).
// Usage: include 'icons.php'; then echo icon('edit');

function icon($name, $size = 18) {
    $icons = [
        'dashboard'   => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
        'cash-register'=> '<rect x="2" y="9" width="20" height="12" rx="1"/><path d="M5 9V6a2 2 0 012-2h10a2 2 0 012 2v3"/><path d="M7 14h2M15 14h2M7 17h2M15 17h2"/>',
        'boxes'       => '<path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/>',
        'truck'       => '<rect x="1" y="6" width="13" height="11" rx="1"/><path d="M14 10h4l4 4v3h-8z"/><circle cx="6" cy="19" r="2"/><circle cx="17.5" cy="19" r="2"/>',
        'users-gear'  => '<circle cx="9" cy="7" r="3.2"/><path d="M2.5 21v-1.5A4.5 4.5 0 017 15h4"/><circle cx="18" cy="15.5" r="2.4"/><path d="M18 11.7v1M18 18.3v1M15 13.3l.9.5M21 16.2l.9.5M15 17.7l.9-.5M21 14.8l.9-.5"/>',
        'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5l3.2 3"/>',
        'logout'      => '<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'edit'        => '<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.4 2.6a2.1 2.1 0 013 3L11 16l-4.3 1.2L8 12.8z"/>',
        'trash'       => '<path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',
        'history'     => '<path d="M3.5 12a8.5 8.5 0 108-8.7 9 9 0 00-6.4 2.7L3.5 8"/><path d="M3.5 3v5h5"/><path d="M12 7.5v5l3.5 2"/>',
        'user-check'  => '<circle cx="8.5" cy="7" r="4"/><path d="M2 21v-1.5A5 5 0 017 14.5h3"/><path d="M15.5 12.5l2 2 4-4"/>',
        'user-slash'  => '<path d="M2 21v-1.5A5 5 0 017 14.5h1"/><circle cx="8.5" cy="7" r="4"/><path d="M17 8l6 6M23 8l-6 6"/>',
        'coins'       => '<circle cx="8.5" cy="8.5" r="6.5"/><path d="M14.5 12.3a6.5 6.5 0 108.2 8.2"/>',
        'warning'     => '<path d="M12 2.5L1.5 21h21z"/><path d="M12 9.5v4.5M12 17.2h.01"/>',
        'database'    => '<ellipse cx="12" cy="5.5" rx="8.5" ry="3"/><path d="M3.5 5.5v13a8.5 3 0 0017 0v-13"/><path d="M3.5 12a8.5 3 0 0017 0"/>',
        'bell'        => '<path d="M6 8.5a6 6 0 1112 0c0 6.5 2.5 8.5 2.5 8.5H3.5s2.5-2 2.5-8.5z"/><path d="M10.3 20.5a1.8 1.8 0 003.4 0"/>',
        'check-circle'=> '<circle cx="12" cy="12" r="9.5"/><path d="M7.5 12.2l3 3 6-6.4"/>',
        'plus'        => '<path d="M12 5v14M5 12h14"/>',
        'search'      => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'location'    => '<path d="M12 21.5s7.2-6.6 7.2-12.4a7.2 7.2 0 10-14.4 0c0 5.8 7.2 12.4 7.2 12.4z"/><circle cx="12" cy="9.1" r="2.6"/>',
        'phone'       => '<path d="M21.5 16.4v3a2 2 0 01-2.2 2 19.4 19.4 0 01-8.4-3 19.1 19.1 0 01-5.9-5.9 19.4 19.4 0 01-3-8.5A2 2 0 013.9 2h3a2 2 0 012 1.7c.1.9.3 1.8.6 2.7a2 2 0 01-.5 2L7.6 9.7a15.6 15.6 0 006.2 6.2l1.3-1.3a2 2 0 012.1-.5c.9.3 1.8.5 2.7.6a2 2 0 011.6 2.7z"/>',
        'envelope'    => '<rect x="2" y="4.5" width="20" height="15" rx="2"/><path d="M2.5 6.5l9.5 6.7 9.5-6.7"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'user-minus'  => '<circle cx="9" cy="7" r="4"/><path d="M2 21v-1.5A5 5 0 017 14.5h3"/><path d="M17 11.5h6"/>',
        'route'       => '<circle cx="6" cy="19" r="2.5"/><circle cx="18" cy="5" r="2.5"/><path d="M8.3 19H15a4 4 0 004-4 4 4 0 00-4-4H9a4 4 0 01-4-4 4 4 0 014-4h6.7"/>',
        'package'     => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v10l9 5 9-5V8"/><path d="M12 13v10"/>',
        'flag'        => '<path d="M5 21V4"/><path d="M5 4h13l-3 4 3 4H5"/>',
        'x-circle'    => '<circle cx="12" cy="12" r="9.5"/><path d="M9 9l6 6M15 9l-6 6"/>',
        'power'       => '<path d="M12 3v9"/><path d="M18 6.3a8 8 0 11-12 0"/>',
        'sort'        => '<path d="M8 9l4-5 4 5"/><path d="M16 15l-4 5-4-5"/>',
    ];
    $path = $icons[$name] ?? '<circle cx="12" cy="12" r="9"/>';
    return '<svg width="'.(int)$size.'" height="'.(int)$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;">'.$path.'</svg>';
}
?>