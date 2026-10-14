<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Mail\ReviewReceived;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use App\Support\ImageStore;
use App\Tenancy\CurrentShop;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

/**
 * Customers rate a shop from its public menu. Reviews are private: they go
 * to the owner (dashboard + email), never onto the public page.
 */
class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Shop $shop, CurrentShop $currentShop, ImageStore $images): JsonResponse
    {
        abort_unless($shop->isPubliclyVisible(), 404);

        $currentShop->set($shop);

        $review = new Review($request->safe()->only(['rating', 'comment', 'name', 'contact']));
        $review->locale = $request->input('lang');

        if ($request->hasFile('photo')) {
            $review->photo_path = $images->store($request->file('photo'), $shop, 'review');
        }

        $review->save();

        // After the response is sent, so a slow mail server never delays the customer.
        defer(function () use ($shop, $review) {
            User::query()->where('shop_id', $shop->id)->get()->each(
                fn (User $owner) => Mail::to($owner->email)->send(new ReviewReceived($review, $shop, $owner->locale ?? $shop->default_locale)),
            );
        });

        return response()->json(['ok' => true], 201);
    }
}
