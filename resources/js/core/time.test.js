import { describe, expect, it } from 'vitest';
import { clockOffset, formatClock, formatRemaining, remainingDays, remainingMinutes, secondsLeft, serverNow } from './time.js';

describe('formatRemaining', () => {
    it('formats minutes and seconds', () => {
        expect(formatRemaining(1421)).toBe('23:41');
        expect(formatRemaining(5)).toBe('0:05');
        expect(formatRemaining(0)).toBe('0:00');
        expect(formatRemaining(60)).toBe('1:00');
        expect(formatRemaining(3599)).toBe('59:59');
    });
    it('formats hours', () => {
        expect(formatRemaining(3600)).toBe('1:00:00');
        expect(formatRemaining(3909)).toBe('1:05:09');
        expect(formatRemaining(43200)).toBe('12:00:00');
    });
    it('clamps bad input to zero', () => {
        expect(formatRemaining(-10)).toBe('0:00');
        expect(formatRemaining(NaN)).toBe('0:00');
        expect(formatRemaining(Infinity)).toBe('0:00');
    });
    it('floors fractions', () => {
        expect(formatRemaining(59.9)).toBe('0:59');
    });
});

describe('remainingMinutes', () => {
    it('rounds up', () => {
        expect(remainingMinutes(1380)).toBe(23);
        expect(remainingMinutes(1381)).toBe(24);
        expect(remainingMinutes(1)).toBe(1);
        expect(remainingMinutes(0)).toBe(0);
        expect(remainingMinutes(-5)).toBe(0);
    });
});

describe('clock offset', () => {
    it('computes and applies the offset', () => {
        const off = clockOffset(1000, 990);
        expect(off).toBe(10);
        expect(serverNow(2000, off)).toBe(2010);
    });
    it('computes seconds left on the server clock', () => {
        expect(secondsLeft(1100, 990, 10)).toBe(100);
        expect(secondsLeft(1100, 2000, 10)).toBe(0);
    });
});

describe('formatClock', () => {
    it('applies the utc offset', () => {
        expect(formatClock(1791706320, 120)).toBe('10:12');
        expect(formatClock(1791706320, 0)).toBe('08:12');
        expect(formatClock(1791706320, -300)).toBe('03:12');
    });
    it('wraps around midnight', () => {
        expect(formatClock(1791763200 - 60, 120)).toBe('01:59');
    });
});

describe('remainingDays', () => {
    it('rounds up to whole days', () => {
        expect(remainingDays(86400)).toBe(1);
        expect(remainingDays(86401)).toBe(2);
        expect(remainingDays(29 * 86400 + 1)).toBe(30);
        expect(remainingDays(1)).toBe(1);
    });
    it('is zero for nothing left or bad input', () => {
        expect(remainingDays(0)).toBe(0);
        expect(remainingDays(-5)).toBe(0);
        expect(remainingDays(NaN)).toBe(0);
        expect(remainingDays(Infinity)).toBe(0);
    });
});
