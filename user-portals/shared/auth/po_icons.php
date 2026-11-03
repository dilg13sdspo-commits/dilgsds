<?php

function po_icon($name, $class = '') {
    $paths = [
        'dashboard' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5M9 20v-5h6v5"/>',
        'submissions' => '<path d="M7 3h7l4 4v14H7z"/><path d="M14 3v5h4M10 12h5M10 16h5"/>',
        'office' => '<path d="M4 20V6l8-3 8 3v14M2 20h20M8 9h2M14 9h2M8 13h2M14 13h2M10 20v-4h4v4"/>',
        'announcement' => '<path d="m4 11 12-5v12L4 13zM4 11v2M16 9h3a2 2 0 0 1 0 4h-3M7 14l1.5 5h3L10 15"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01"/>',
        'reports' => '<path d="M5 20V10M12 20V4M19 20v-7"/>',
        'notifications' => '<path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.9 1.9-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V20h-2.7v-.09a1.7 1.7 0 0 0-1.03-1.56 1.7 1.7 0 0 0-1.88.34l-.06.06-1.9-1.9.06-.06A1.7 1.7 0 0 0 7.8 15a1.7 1.7 0 0 0-1.56-1.03H6v-2.7h.24A1.7 1.7 0 0 0 7.8 10a1.7 1.7 0 0 0-.34-1.88L7.4 8.06l1.9-1.9.06.06a1.7 1.7 0 0 0 1.88.34 1.7 1.7 0 0 0 1.03-1.56V5h2.7v.09A1.7 1.7 0 0 0 16 6.65a1.7 1.7 0 0 0 1.88-.34l.06-.06 1.9 1.9-.06.06A1.7 1.7 0 0 0 19.4 10a1.7 1.7 0 0 0 1.56 1.03H21v2.7h-.09A1.7 1.7 0 0 0 19.4 15Z"/>',
        'logout' => '<path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/>',
        'search' => '<circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/>',
        'create' => '<path d="M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4M4 20l1-4"/>',
        'document' => '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 13h6M9 17h6"/>',
        'download' => '<path d="M12 4v10M8 10l4 4 4-4M5 19h14"/>',
        'phone' => '<path d="M6 4h3l1.5 4-2 1.5a14 14 0 0 0 5 5l1.5-2 4 1.5v3a2 2 0 0 1-2 2C10.4 19.5 4.5 13.6 4.5 6a2 2 0 0 1 1.5-2Z"/>',
        'total' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h8M8 17h5"/>',
        'pending' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/>',
        'submitted' => '<circle cx="12" cy="12" r="8"/><path d="m8 12 2.5 2.5L16 9"/>',
        'approved' => '<circle cx="12" cy="12" r="8"/><path d="m8 12 2.5 2.5L16 9"/>',
        'returned' => '<path d="M9 7H5v4M5 11a7 7 0 1 0 2-5"/><path d="M5 11 2.5 8.5"/>',
        'overdue' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5M12 16h.01"/>',
        'user' => '<circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'lock' => '<rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2"/>',
        'shield' => '<path d="M12 3 19 6v5c0 4.5-2.8 8-7 10-4.2-2-7-5.5-7-10V6zM9 12l2 2 4-4"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/>',
        'eye-off' => '<path d="m3 3 18 18M10.6 6.2A9.8 9.8 0 0 1 12 6c6.5 0 10 6 10 6a17.7 17.7 0 0 1-3.1 3.8M6.2 6.2C3.6 8.2 2 12 2 12s3.5 6 10 6c1.5 0 2.8-.3 4-.8M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4.2 1.8c-1.2 1-1.7 1.4-1.7 3M12 17h.01"/>',
        'clock' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/>',
        'location' => '<path d="M19 10c0 5-7 10-7 10S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'view' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/>',
        'tip' => '<path d="M9 18h6M10 21h4M8 14a5 5 0 1 1 8 0c-.8.8-1 1.5-1 2H9c0-.5-.2-1.2-1-2Z"/><path d="M12 2v2M4.9 4.9l1.4 1.4M2 12h2M19.1 4.9l-1.4 1.4M20 12h2"/>',
    ];

    $path = $paths[$name] ?? $paths['document'];
    $classAttribute = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
    return '<svg' . $classAttribute . ' viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}
