<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactController extends Controller
{
    public function index(IndexContactRequest $request): AnonymousResourceCollection
    {
        $conditions = $request->validated();
        $perPage = (int) ($conditions['per_page'] ?? 20);

        $contacts = Contact::with(['category', 'tags'])
            ->when($conditions['keyword'] ?? null, function ($query, string $keyword): void {
                $query->where(function ($query) use ($keyword): void {
                    $query->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->when($conditions['gender'] ?? null, fn ($query, int $gender) => $query->where('gender', $gender))
            ->when($conditions['category_id'] ?? null, fn ($query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($conditions['date'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', $date))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return ContactResource::collection($contacts);
    }

    public function show(int $contact): ContactResource
    {
        $contact = Contact::with(['category', 'tags'])->findOrFail($contact);

        return new ContactResource($contact);
    }
}
