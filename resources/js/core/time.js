// Pure time helpers. All values are integer seconds (Unix time or durations). No Date, no DOM.

const pad = (n) => String(n).padStart(2, '0');

/**
 * Remaining time as a clock string. Under one hour "M:SS" -> "23:41", "0:05";
 * one hour or more "H:MM:SS" -> "1:05:09". Negative or non-finite input counts as 0 ("0:00").
 * @param {number} seconds
 * @returns {string}
 */
export function formatRemaining(seconds) {
    const total = Number.isFinite(seconds) && seconds > 0 ? Math.floor(seconds) : 0;
    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const secs = total % 60;
    return hours > 0 ? `${hours}:${pad(minutes)}:${pad(secs)}` : `${minutes}:${pad(secs)}`;
}

/**
 * Whole minutes left, rounded up ("noch 23 Min."); 0 for <= 0.
 * @param {number} seconds
 * @returns {number}
 */
export function remainingMinutes(seconds) {
    return seconds > 0 ? Math.ceil(seconds / 60) : 0;
}

/**
 * Offset in whole seconds between the server clock and the local clock: serverNow - localNow.
 * @param {number} serverNow
 * @param {number} localNow
 * @returns {number}
 */
export function clockOffset(serverNow, localNow) {
    return serverNow - localNow;
}

/**
 * Best estimate of the server time: localNow + offset.
 * @param {number} localNow
 * @param {number} offset
 * @returns {number}
 */
export function serverNow(localNow, offset) {
    return localNow + offset;
}

/**
 * Seconds until `endsAt` on the server clock (never negative).
 * @param {number} endsAt
 * @param {number} localNow
 * @param {number} offset
 * @returns {number}
 */
export function secondsLeft(endsAt, localNow, offset) {
    return Math.max(0, endsAt - serverNow(localNow, offset));
}

/**
 * Wall-clock label "HH:MM" of a Unix timestamp in the given UTC offset (minutes east of UTC, integer).
 * formatClock(1791706320, 120) -> "10:12" (08:12 UTC + 2h)
 * @param {number} unixSeconds
 * @param {number} utcOffsetMinutes
 * @returns {string}
 */
export function formatClock(unixSeconds, utcOffsetMinutes) {
    const DAY = 86400;
    const local = (((unixSeconds + utcOffsetMinutes * 60) % DAY) + DAY) % DAY;
    return `${pad(Math.floor(local / 3600))}:${pad(Math.floor((local % 3600) / 60))}`;
}

/**
 * Whole days left, rounded up ("noch 29 Tage"); 0 for <= 0 or non-finite input.
 * @param {number} seconds
 * @returns {number}
 */
export function remainingDays(seconds) {
    if (!Number.isFinite(seconds) || seconds <= 0) return 0;
    return Math.ceil(seconds / 86400);
}
