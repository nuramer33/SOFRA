<?php

namespace App\Http\Requests;

use App\Models\Shop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A customer's cart, sent from the public menu.
 * Only ids + quantities come from the browser; prices come from the database.
 */
class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public: anyone looking at the menu can order
    }

    protected function prepareForValidation(): void
    {
        // The table number can arrive from a link (?table=5): keep letters, digits, spaces and hyphens only.
        if (is_string($this->input('table'))) {
            $table = trim((string) preg_replace('/[^\p{L}\p{N}\- ]/u', '', $this->input('table')));
            $this->merge(['table' => $table === '' ? null : $table]);
        }

        // Validation messages in the language the customer is reading.
        if (in_array($this->input('lang'), $this->shop()->contentLocales(), true)) {
            app()->setLocale($this->input('lang'));
        }
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
            'type' => ['required', Rule::in($this->shop()->orderTypes())],
            'table' => ['nullable', 'string', 'max:20'],
            'name' => ['nullable', 'required_unless:type,dine_in', 'string', 'max:60'],
            'address' => ['nullable', 'required_if:type,delivery', 'string', 'max:300'],
            'note' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required_unless' => __('app.cart.name_required'),
            'address.required_if' => __('app.cart.address_required'),
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => __('app.cart.type'),
            'table' => __('app.cart.table'),
            'name' => __('app.cart.name'),
            'address' => __('app.cart.address'),
            'note' => __('app.cart.note'),
        ];
    }

    public function shop(): Shop
    {
        return $this->route('shop');
    }

    /**
     * @return array<int, int> product id => quantity
     */
    public function quantities(): array
    {
        return collect($this->validated('items'))
            ->mapWithKeys(fn (array $item) => [(int) $item['id'] => (int) $item['qty']])
            ->all();
    }
}
