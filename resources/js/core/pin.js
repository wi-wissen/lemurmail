// Pure PIN helpers (mirror of App\Core\Pin\PinFormat for the browser).

/** "K7M4QX9R" -> "K7M4 QX9R". Input is first normalized; anything not 8 chars is returned normalized, ungrouped except in blocks of four. */
export function formatPin(pin) {
    return normalizePin(pin).replace(/(.{4})(?=.)/g, '$1 ');
}

/** Upper-case and strip every character that is not A-Z or 0-9. */
export function normalizePin(input) {
    return String(input ?? '').toUpperCase().replace(/[^A-Z0-9]/g, '');
}
