<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('contact.index', [
            'categories' => Category::all(),
            'tags' => Tag::all(),
        ]);
    }

    public function confirm(StoreContactRequest $request): View
    {
        $request->flash();

        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];

        return view('contact.confirm', [
            'validated' => $validated,
            'category' => Category::findOrFail($validated['category_id']),
            'tags' => Tag::whereIn('id', $tagIds)->get(),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $contact = Contact::create($validated);
        $contact->tags()->sync($tagIds);

        return redirect('/thanks');
    }

    public function thanks(): View
    {
        return view('contact.thanks');
    }
}
