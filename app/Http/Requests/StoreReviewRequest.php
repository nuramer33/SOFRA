<?php

namespace App\Http\Requests;

use App\Models\Shop;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A customer's review, sent from the public menu.
 * The photo is re-encoded by ImageStore, so only "is it an image" is checked here.
 */
class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public: anyone looking at the menu can review
    }

    protected function prepareForValidation(): void
    {
        if (in_array($this->input('lang'), $this->shop()->contentLocales(), true)) {
            app()->setLocale($this->input('lang'));
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'name' => ['nullable', 'string', 'max:60'],
            'contact' => ['nullable', 'string', 'max:80'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('sofra.images.max_upload_kb')],
            'website' => ['nullable', 'max:0'], // honeypot: real people leave it empty
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rating' => __('app.review.rating'),
            'comment' => __('app.review.comment'),
            'name' => __('app.review.name'),
            'contact' => __('app.review.contact'),
            'photo' => __('app.review.photo'),
        ];
    }

    private function shop(): Shop
    {
        return $this->route('shop');
    }
}
