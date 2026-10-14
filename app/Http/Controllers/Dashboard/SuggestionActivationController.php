<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ProductSuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One-tap on/off switch for an offer ("goes well with" suggestion).
 * {suggestion} is limited to the owner's shop by the tenant scope.
 */
class SuggestionActivationController extends Controller
{
    public function update(Request $request, ProductSuggestion $suggestion): JsonResponse
    {
        $active = (bool) $request->validate(['is_active' => ['required', 'boolean']])['is_active'];

        $suggestion->update(['is_active' => $active]);

        return response()->json(['is_active' => $suggestion->is_active]);
    }
}
