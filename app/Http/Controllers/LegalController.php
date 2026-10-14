<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Static legal pages. The text lives in lang/{locale}/legal.php.
 */
class LegalController extends Controller
{
    public function privacy(): View
    {
        return $this->page('privacy');
    }

    public function terms(): View
    {
        return $this->page('terms');
    }

    /**
     * @param  'privacy'|'terms'  $page
     */
    private function page(string $page): View
    {
        return view('legal.show', ['page' => $page]);
    }
}
