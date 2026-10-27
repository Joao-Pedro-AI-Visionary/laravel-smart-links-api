<?php

namespace App\Http\Requests;

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
            'webhook_url' => ['nullable', 'url:http,https', 'max:2048'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
