<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $this->user()->can('update', $product);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->id : $product;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'short_description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'compare_at_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in(ProductStatus::values())],
            'cover_image' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * Compare the discount price against the price being set, falling back to
     * the stored price when the request omits it.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['price', 'compare_at_price'])) {
                    return;
                }

                $price = $this->input('price', $this->product()?->price);
                $compareAtPrice = $this->input('compare_at_price');

                if (is_numeric($price) && $compareAtPrice !== null && (float) $compareAtPrice < (float) $price) {
                    $validator->errors()->add(
                        'compare_at_price',
                        'The compare at price must be greater than or equal to the price.',
                    );
                }
            },
        ];
    }

    private function product(): ?Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : null;
    }
}
