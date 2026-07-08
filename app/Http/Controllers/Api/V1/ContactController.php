<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ContactController extends Controller
{
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
}
