<?php

namespace App\Services\DataTable;

use Carbon\Carbon;

class DataTableRowFormatter
{
    public function format(object $row, array $rules): object
    {
        foreach ($rules as $field => $rule) {
            if (! property_exists($row, $field)) {
                continue;
            }

            $value = $row->{$field};
            $row->{$field} = match ($rule['type'] ?? null) {
                'badge' => $this->badge($value, $rule),
                'currency' => $this->currency($value, $rule),
                'date' => $this->date($value, $rule),
                default => $value,
            };
        }

        return $row;
    }

    private function badge(mixed $value, array $rule): string
    {
        $state = $rule['values'][(string) $value] ?? [
            'label' => ucfirst((string) $value),
            'class' => 'bg-secondary',
        ];

        return '<span class="badge '.e($state['class'] ?? 'bg-secondary').'">'
            .e($state['label'] ?? (string) $value).'</span>';
    }

    private function currency(mixed $value, array $rule): string
    {
        return e((string) ($rule['symbol'] ?? '৳').number_format((float) $value, (int) ($rule['decimals'] ?? 2)));
    }

    private function date(mixed $value, array $rule): string
    {
        return $value === null ? '' : e(Carbon::parse($value)->format($rule['format'] ?? 'd M Y'));
    }
}
