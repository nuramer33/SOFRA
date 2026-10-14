<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ordering availability: pause switch, time zone and the weekly hours.
 */
class OrderingRequest extends FormRequest
{
    private const TIME = '/^([01]\d|2[0-3]):[0-5]\d$/';

    public function authorize(): bool
    {
        return true; // the tenant scope limits this to the signed-in owner's shop
    }

    public function rules(): array
    {
        return [
            'orders_paused' => ['boolean'],
            'hours_enabled' => ['boolean'],
            'timezone' => ['required', Rule::in(config('sofra.timezones'))],
            'hours' => ['nullable', 'array'],
            'hours.*.open' => ['nullable', 'boolean'],
            'hours.*.from' => ['nullable', 'regex:'.self::TIME],
            'hours.*.to' => ['nullable', 'regex:'.self::TIME],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->boolean('hours_enabled')) {
                return;
            }

            $open = 0;
            foreach (range(0, 6) as $day) {
                $row = $this->input("hours.{$day}", []);
                if (! filter_var($row['open'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                $open++;
                $from = $row['from'] ?? null;
                $to = $row['to'] ?? null;

                if (blank($from) || blank($to)) {
                    $validator->errors()->add("hours.{$day}.from", __('quick.hours.errors.times', ['day' => __("quick.days.{$day}")]));
                } elseif ($from === $to) {
                    $validator->errors()->add("hours.{$day}.to", __('quick.hours.errors.same', ['day' => __("quick.days.{$day}")]));
                }
            }

            if ($open === 0) {
                $validator->errors()->add('hours', __('quick.hours.errors.none_open'));
            }
        });
    }

    public function attributes(): array
    {
        return ['timezone' => __('quick.hours.timezone')];
    }

    /**
     * The schedule to store: all seven days, or null when hours are off.
     *
     * @return array<string, array{open: bool, from: string, to: string}>|null
     */
    public function schedule(): ?array
    {
        if (! $this->boolean('hours_enabled')) {
            return null;
        }

        $schedule = [];
        foreach (range(0, 6) as $day) {
            $row = $this->input("hours.{$day}", []);
            $open = filter_var($row['open'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $schedule[(string) $day] = [
                'open' => $open,
                'from' => $open ? $row['from'] : ($row['from'] ?: '09:00'),
                'to' => $open ? $row['to'] : ($row['to'] ?: '23:00'),
            ];
        }

        return $schedule;
    }
}
