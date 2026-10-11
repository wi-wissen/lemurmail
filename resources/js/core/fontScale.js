// Beamer font scale in integer percent steps: 100, 125, 150, 175, 200.
export const FONT_SCALES = [100, 125, 150, 175, 200];

/**
 * Next/previous scale. direction > 0 larger, < 0 smaller, clamped to the ends.
 * A `current` that is not one of FONT_SCALES snaps to the nearest first (ties go down).
 * @param {number} current
 * @param {number} direction
 * @returns {number}
 */
export function stepFontScale(current, direction) {
    // Nearest scale; a strictly smaller distance is required to move, so ties keep the lower scale.
    let index = 0;
    for (let i = 1; i < FONT_SCALES.length; i++) {
        if (Math.abs(FONT_SCALES[i] - current) < Math.abs(FONT_SCALES[index] - current)) {
            index = i;
        }
    }
    if (direction > 0) {
        index = Math.min(index + 1, FONT_SCALES.length - 1);
    } else if (direction < 0) {
        index = Math.max(index - 1, 0);
    }
    return FONT_SCALES[index];
}
