import { describe, expect, it } from 'vitest';
import { mergeMessages, nextSelection, pollDelay } from './inbox.js';

const m = (id, receivedAt, subject = 's' + id) => ({ id, from: 'a@b.example', subject, receivedAt });

describe('mergeMessages', () => {
    it('sorts newest first and reports new ids', () => {
        const r = mergeMessages([m(1, 100)], [m(1, 100), m(3, 300), m(2, 200)]);
        expect(r.messages.map((x) => x.id)).toEqual([3, 2, 1]);
        expect(r.newIds).toEqual([3, 2]);
    });
    it('breaks time ties by id desc', () => {
        expect(mergeMessages([], [m(1, 100), m(2, 100)]).messages.map((x) => x.id)).toEqual([2, 1]);
    });
    it('drops messages the server no longer has', () => {
        const r = mergeMessages([m(1, 100), m(2, 200)], [m(2, 200)]);
        expect(r.messages.map((x) => x.id)).toEqual([2]);
        expect(r.newIds).toEqual([]);
    });
    it('replaces known messages with the polled version', () => {
        const r = mergeMessages([m(1, 100, 'old')], [m(1, 100, 'new')]);
        expect(r.messages[0].subject).toBe('new');
    });
    it('deduplicates ids in incoming', () => {
        expect(mergeMessages([], [m(1, 100), m(1, 100)]).messages).toHaveLength(1);
    });
    it('does not mutate its inputs', () => {
        const known = [m(1, 100)];
        const incoming = [m(2, 200), m(1, 100)];
        mergeMessages(known, incoming);
        expect(known).toHaveLength(1);
        expect(incoming.map((x) => x.id)).toEqual([2, 1]);
    });
    it('handles empty lists', () => {
        expect(mergeMessages([], [])).toEqual({ messages: [], newIds: [] });
    });
});

describe('nextSelection', () => {
    const list = [m(3, 300), m(2, 200)];
    it('keeps an existing selection', () => expect(nextSelection(list, 2)).toBe(2));
    it('falls back to the first', () => {
        expect(nextSelection(list, 9)).toBe(3);
        expect(nextSelection(list, null)).toBe(3);
    });
    it('returns null for an empty list', () => expect(nextSelection([], 3)).toBeNull());
});

describe('pollDelay', () => {
    it('is the base while healthy', () => expect(pollDelay(10000, 0, 60000)).toBe(10000));
    it('backs off exponentially and caps', () => {
        expect(pollDelay(10000, 1, 60000)).toBe(20000);
        expect(pollDelay(10000, 2, 60000)).toBe(40000);
        expect(pollDelay(10000, 3, 60000)).toBe(60000);
        expect(pollDelay(10000, 50, 60000)).toBe(60000);
    });
    it('treats negative failures as zero', () => expect(pollDelay(10000, -2, 60000)).toBe(10000));
});
