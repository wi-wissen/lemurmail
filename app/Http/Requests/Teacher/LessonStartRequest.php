<?php

namespace App\Http\Requests\Teacher;

use App\Core\Hour\HourWindow;
use App\Http\Controllers\Teacher\Concerns\OwnsResources;
use App\Models\SenderRule;
use App\Support\Clock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class LessonStartRequest extends FormRequest
{
    use OwnsResources;

    public function authorize(): bool
    {
        $this->owned($this, $this->route('group'));

        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['minutes', 'until', 'date'])],
            'minutes' => ['nullable', 'required_if:mode,minutes', 'integer', 'between:1,'.$this->maxMinutes()],
            'until' => ['nullable', 'required_if:mode,until', 'date_format:H:i'],
            'until_date' => ['nullable', 'required_if:mode,date', 'date_format:Y-m-d'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'minutes.required_if' => __('lesson.minutes_invalid', ['max' => $this->maxMinutes()]),
            'minutes.integer' => __('lesson.minutes_invalid', ['max' => $this->maxMinutes()]),
            'minutes.between' => __('lesson.minutes_invalid', ['max' => $this->maxMinutes()]),
            'until.required_if' => __('lesson.until_invalid'),
            'until.date_format' => __('lesson.until_invalid'),
            'until_date.required_if' => __('lesson.until_date_invalid', ['days' => $this->maxDays()]),
            'until_date.date_format' => __('lesson.until_date_invalid', ['days' => $this->maxDays()]),
        ];
    }

    /** Preconditions and the end range, checked once the format is valid. */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! SenderRule::query()->where('user_id', $this->user()->id)->whereNull('address_id')->exists()) {
                $validator->errors()->add('senders', __('lesson.no_senders'));
            }

            if (! $this->route('group')->addresses()->exists()) {
                $validator->errors()->add('addresses', __('lesson.no_addresses'));
            }

            if ($this->input('mode') === 'until' && ! $validator->errors()->has('until')) {
                try {
                    $this->window();
                } catch (InvalidArgumentException) {
                    $validator->errors()->add('until', __('lesson.until_out_of_range', ['hours' => intdiv($this->maxSeconds(), 3600)]));
                }
            }

            if ($this->input('mode') === 'date' && ! $validator->errors()->has('until_date')) {
                try {
                    $this->window();
                } catch (InvalidArgumentException) {
                    $validator->errors()->add('until_date', __('lesson.until_date_invalid', ['days' => $this->maxDays()]));
                }
            }
        }];
    }

    /** The validated lesson window; only call after validation passed. */
    public function window(): HourWindow
    {
        $now = Clock::now();

        return match ($this->input('mode')) {
            // The end of the chosen day, in the app timezone; the longest validity is the PIN maximum.
            'date' => HourWindow::until($now, $this->endOfDay(), config('lemurmail.max_pin_seconds')),
            'until' => HourWindow::until($now, Carbon::today(config('app.timezone'))
                ->setTimeFromTimeString((string) $this->input('until'))
                ->getTimestamp(), $this->maxSeconds()),
            default => HourWindow::start($now, (int) $this->input('minutes') * 60, $this->maxSeconds()),
        };
    }

    private function endOfDay(): int
    {
        return Carbon::parse($this->input('until_date').' 23:59:00', config('app.timezone'))->getTimestamp();
    }

    private function maxSeconds(): int
    {
        return config('lemurmail.max_lesson_seconds');
    }

    private function maxMinutes(): int
    {
        return intdiv($this->maxSeconds(), 60);
    }

    private function maxDays(): int
    {
        return intdiv(config('lemurmail.max_pin_seconds'), 86400);
    }
}
