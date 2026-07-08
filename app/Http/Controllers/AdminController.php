<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.index', [
            'categories' => Category::all(),
            'contacts' => Contact::with(['category', 'tags'])
                ->latest()
                ->paginate(7),
            'tags' => Tag::all(),
        ]);
    }
}
