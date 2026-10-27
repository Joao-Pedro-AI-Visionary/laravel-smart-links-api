<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreShortLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'alpha_dash', 'min:3', 'max:64', 'unique:short_links,slug'],
            'target_url' => ['required', 'url:http,https', 'max:2048'],
            'utm_source' => ['nullable', 'string', 'max:80'],
            'utm_medium' => ['nullable', 'string', 'max:80'],
            'webhook_url' => [
                'nullable',
                'url:http,https',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }
                    $host = strtolower((string) parse_url($value, PHP_URL_HOST));
                    if ($host === '' || in_array($host, ['localhost', '0.0.0.0', '::1'], true)) {
                        $fail('O webhook_url não pode apontar para hosts locais ou internos.');

                        return;
                    }
                    if (filter_var($host, FILTER_VALIDATE_IP)) {
                        $isPublicIp = filter_var(
                            $host,
                            FILTER_VALIDATE_IP,
                            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                        );
                        if ($isPublicIp === false) {
                            $fail('O webhook_url não pode apontar para endereços IP privados ou reservados (proteção SSRF).');
                        }
                    }
                },
            ],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
