import { describe, expect, it } from 'vitest';
import { formatPin, normalizePin } from './pin.js';

describe('pin', () => {
    it('formats in groups of four', () => {
        expect(formatPin('K7M4QX9R')).toBe('K7M4 QX9R');
        expect(formatPin('k7m4 qx9r')).toBe('K7M4 QX9R');
        expect(formatPin('K7M4Q')).toBe('K7M4 Q');
        expect(formatPin('')).toBe('');
    });
    it('normalizes input', () => {
        expect(normalizePin(' k7m4-qx9r ')).toBe('K7M4QX9R');
        expect(normalizePin('')).toBe('');
        expect(normalizePin('ä!')).toBe('');
    });
});
