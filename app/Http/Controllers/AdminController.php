<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(IndexContactRequest $request): View
    {
        $conditions = $request->validated();

        $contacts = Contact::with(['category', 'tags'])
            ->when($conditions['keyword'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->when(
                isset($conditions['gender']) && (int) $conditions['gender'] !== 0,
                fn ($query) => $query->where('gender', $conditions['gender'])
            )
            ->when($conditions['category_id'] ?? null, fn ($query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($conditions['date'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', $date))
            ->latest()
            ->paginate(7)
            ->withQueryString();

        return view('admin.index', [
            'categories' => Category::all(),
            'contacts' => $contacts,
            'tags' => Tag::all(),
        ]);
    }

    public function show(Contact $contact): View
    {
        return view('admin.show', [
            'contact' => $contact->load(['category', 'tags']),
        ]);
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        DB::transaction(function () use ($contact): void {
            $contact->tags()->detach();
            $contact->delete();
        });

        return redirect('/admin');
    }
}
