<?php

namespace App\Http\Controllers;

use App\Models\AboutPageContent;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    public function show(): View
    {
        $about = AboutPageContent::current()
            ?? new AboutPageContent(['title' => 'من نحن', 'body' => '']);

        return view('public.about', compact('about'));
    }
}
