<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ContactController extends Controller
{
    public function index(IndexContactRequest $request): AnonymousResourceCollection
    {
        $conditions = $request->validated();
        $perPage = (int) ($conditions['per_page'] ?? 15);

        $contacts = $this->applyContactSearchConditions(
            Contact::with(['category', 'tags']),
            $conditions
        )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return ContactResource::collection($contacts);
    }

    public function show(Contact $contact): ContactResource
    {
        return new ContactResource($contact->load(['category', 'tags']));
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $contact = Contact::create($validated);
        $contact->tags()->sync($tagIds);

        return (new ContactResource($contact->load(['category', 'tags'])))->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateContactRequest $request, Contact $contact): ContactResource
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $contact->update($validated);
        $contact->tags()->sync($tagIds);

        return new ContactResource($contact->load(['category', 'tags']));
    }

    public function destroy(Contact $contact): Response
    {
        $contact->delete();

        return response()->noContent();
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function applyContactSearchConditions(Builder $query, array $conditions): Builder
    {
        return $query
            ->when($conditions['keyword'] ?? null, function (Builder $query, string $keyword): void {
                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->when($conditions['gender'] ?? null, fn (Builder $query, int $gender): Builder => $query->where('gender', $gender))
            ->when($conditions['category_id'] ?? null, fn (Builder $query, int $categoryId): Builder => $query->where('category_id', $categoryId))
            ->when($conditions['date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', $date));
    }
}
