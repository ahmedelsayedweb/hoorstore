<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class FacebookFeedController extends Controller
{
    /**
     * Generate Facebook/Meta Product Catalog CSV Feed
     */
    public function feed(Request $request)
    {
        $siteUrl = 'https://hoorstore.org';
        $brand = 'Hoor Store';
        $currency = 'EGP';

        $products = Product::all();

        $handle = fopen('php://temp', 'r+');

        // Facebook required CSV headers
        $headers = [
            'id',
            'title',
            'description',
            'availability',
            'condition',
            'price',
            'sale_price',
            'link',
            'image_link',
            'additional_image_link',
            'brand',
            'product_type',
        ];

        fputcsv($handle, $headers);

        foreach ($products as $product) {
            $availability = $product->inStock ? 'in stock' : 'out of stock';

            $price = number_format($product->price, 2, '.', '') . ' ' . $currency;

            $salePrice = '';
            if ($product->salePrice && $product->salePrice > 0 && $product->salePrice < $product->price) {
                $salePrice = number_format($product->salePrice, 2, '.', '') . ' ' . $currency;
            }

            $link = $siteUrl . '/collections/' . $product->id;

            $imageLink = $product->image ?: '';

            // Additional images (comma-separated)
            $additionalImages = '';
            if ($product->images && is_array($product->images)) {
                $additionalImages = implode(',', $product->images);
            }

            $description = $product->description ?: $product->name;

            fputcsv($handle, [
                $product->code ?: $product->id,
                $product->name,
                $description,
                $availability,
                'new',
                $price,
                $salePrice,
                $link,
                $imageLink,
                $additionalImages,
                $brand,
                is_array($product->category) ? implode(', ', $product->category) : $product->category,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="facebook-feed.csv"',
        ]);
    }
}
