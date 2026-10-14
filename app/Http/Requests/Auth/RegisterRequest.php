<?php

namespace App\Http\Requests\Auth;

use App\Models\Shop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'slug' => mb_strtolower(trim((string) $this->input('slug'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'shop_name' => ['required', 'string', 'max:80'],
            'slug' => Shop::slugRules(),
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return ['terms.accepted' => __('account.agree_required')];
    }

    public function attributes(): array
    {
        return [
            'name' => __('app.auth.owner_name'),
            'shop_name' => __('app.auth.shop_name'),
            'slug' => __('app.auth.slug'),
            'email' => __('app.auth.email'),
            'password' => __('app.auth.password'),
        ];
    }
}
