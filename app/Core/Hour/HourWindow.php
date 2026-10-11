<?php

namespace App\Core\Hour;

/** An immutable lesson window. All values are Unix seconds / whole seconds. */
final class HourWindow
{
    public function __construct(
        public readonly int $startedAt,
        public readonly int $endsAt,
    ) {}

    /**
     * @throws \InvalidArgumentException if $durationSeconds < 1 or > $maxSeconds
     */
    public static function start(int $now, int $durationSeconds, int $maxSeconds): self
    {
        self::assertDuration($durationSeconds, $maxSeconds);

        return new self($now, $now + $durationSeconds);
    }

    /**
     * Until an absolute timestamp.
     *
     * @throws \InvalidArgumentException if $endsAt <= $now or $endsAt - $now > $maxSeconds
     */
    public static function until(int $now, int $endsAt, int $maxSeconds): self
    {
        self::assertDuration($endsAt - $now, $maxSeconds);

        return new self($now, $endsAt);
    }

    public function isActive(int $now): bool
    {
        return $this->startedAt <= $now && $now < $this->endsAt; // startedAt <= now < endsAt
    }

    /** Seconds left, never negative. */
    public function remaining(int $now): int
    {
        return max(0, $this->endsAt - $now);
    }

    /**
     * Pushes the end by $seconds. The end never exceeds startedAt + $maxSeconds (clamped, not an error).
     * Extending an already expired window restarts it: startedAt = $now, endsAt = $now + $seconds (clamped to $maxSeconds).
     */
    public function extend(int $now, int $seconds, int $maxSeconds): self
    {
        if ($this->endsAt <= $now) {
            return new self($now, $now + min($seconds, $maxSeconds));
        }

        return new self($this->startedAt, min($this->endsAt + $seconds, $this->startedAt + $maxSeconds));
    }

    /** Ends the window immediately: endsAt = min($now, endsAt) (an expired window stays as is). */
    public function end(int $now): self
    {
        return new self($this->startedAt, min($now, $this->endsAt));
    }

    private static function assertDuration(int $seconds, int $maxSeconds): void
    {
        if ($seconds < 1 || $seconds > $maxSeconds) {
            throw new \InvalidArgumentException('Duration out of range.');
        }
    }
}
