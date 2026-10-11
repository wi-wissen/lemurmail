import { describe, expect, it } from 'vitest';
import { FONT_SCALES, stepFontScale } from './fontScale.js';

describe('stepFontScale', () => {
    it('steps up and down', () => {
        expect(stepFontScale(100, 1)).toBe(125);
        expect(stepFontScale(150, -1)).toBe(125);
    });
    it('clamps at the ends', () => {
        expect(stepFontScale(200, 1)).toBe(200);
        expect(stepFontScale(100, -1)).toBe(100);
    });
    it('snaps unknown values to the nearest first', () => {
        expect(stepFontScale(130, 1)).toBe(150);
        expect(stepFontScale(112, 1)).toBe(125); // nearest is 100 (diff 12), then one step up
        expect(stepFontScale(112.5, -1)).toBe(100); // tie between 100 and 125 goes down, then one step down (clamped)
    });
    it('exposes the scale list', () => expect(FONT_SCALES).toEqual([100, 125, 150, 175, 200]));
});
