<?php

namespace App\Services\DataTable;

use App\Models\User;

class ActionBuilder
{
    public function build(object $row, array $rules, User $user): string
    {
        $buttons = [];

        foreach ($rules as $rule) {
            if (! $this->isAllowed($user, $rule) || ! $this->matches($row, $rule['when'] ?? null)) {
                continue;
            }

            $url = $this->resolveUrl((string) ($rule['url'] ?? '#'), $row);
            $label = e((string) ($rule['label'] ?? 'Action'));
            $class = e((string) ($rule['class'] ?? 'btn btn-sm btn-outline-secondary'));
            $method = strtoupper((string) ($rule['method'] ?? 'GET'));

            if ($method === 'GET') {
                $buttons[] = '<a class="'.$class.'" href="'.e($url).'">'.$label.'</a>';

                continue;
            }

            $confirm = e((string) ($rule['confirm'] ?? 'Are you sure?'));
            $buttons[] = '<button type="button" class="'.$class
                .' js-dt-action" data-url="'.e($url)
                .'" data-method="'.e($method)
                .'" data-confirm="'.$confirm.'">'.$label.'</button>';
        }

        return implode(' ', $buttons);
    }

    private function isAllowed(User $user, array $rule): bool
    {
        $permission = $rule['permission'] ?? null;

        return is_string($permission) && $user->can($permission);
    }

    private function matches(object $row, ?array $condition): bool
    {
        if ($condition === null) {
            return true;
        }

        $value = data_get($row, $condition['field'] ?? '');
        $expected = $condition['value'] ?? null;

        return match ($condition['operator'] ?? 'equals') {
            'equals' => (string) $value === (string) $expected,
            'not_equals' => (string) $value !== (string) $expected,
            'truthy' => (bool) $value,
            'falsy' => ! (bool) $value,
            default => false,
        };
    }

    private function resolveUrl(string $url, object $row): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function (array $matches) use ($row): string {
            return rawurlencode((string) data_get($row, $matches[1], ''));
        }, $url);
    }
}
