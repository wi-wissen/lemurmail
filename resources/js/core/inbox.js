// Pure inbox state helpers for the polling inbox. Messages are plain objects:
// { id: number, from: string, subject: string, receivedAt: number (unix seconds) }

/**
 * Merges freshly polled messages into the known ones. Result is sorted newest first
 * (receivedAt desc, then id desc), unique by id; a polled message replaces the known one
 * with the same id. Messages missing from `incoming` are dropped (the server is the truth).
 * `newIds` lists ids that were not in `known`, in result order. Inputs are not mutated.
 * @param {Array<object>} known
 * @param {Array<object>} incoming
 * @returns {{messages: Array<object>, newIds: number[]}}
 */
export function mergeMessages(known, incoming) {
    const byId = new Map();
    for (const message of incoming) {
        byId.set(message.id, message);
    }
    const messages = [...byId.values()].sort((a, b) => b.receivedAt - a.receivedAt || b.id - a.id);
    const knownIds = new Set(known.map((message) => message.id));
    const newIds = messages.filter((message) => !knownIds.has(message.id)).map((message) => message.id);
    return { messages, newIds };
}

/**
 * Which message is selected after a poll/removal: keep `selectedId` if it still exists,
 * otherwise the first message, otherwise null.
 * @param {Array<object>} messages
 * @param {number|null} selectedId
 * @returns {number|null}
 */
export function nextSelection(messages, selectedId) {
    if (messages.some((message) => message.id === selectedId)) {
        return selectedId;
    }
    return messages.length > 0 ? messages[0].id : null;
}

/**
 * Polling delay in milliseconds: baseMs while healthy; after n consecutive failures
 * baseMs * 2^n, capped at maxMs. failures < 0 counts as 0.
 * @param {number} baseMs
 * @param {number} failures
 * @param {number} maxMs
 * @returns {number}
 */
export function pollDelay(baseMs, failures, maxMs) {
    const n = Math.max(0, failures);
    return Math.min(baseMs * 2 ** n, maxMs);
}
