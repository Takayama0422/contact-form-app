<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\IndexContactRequest;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function index(IndexContactRequest $request): View
    {
        $conditions = $request->validated();

        $contacts = $this->applyContactSearchConditions(
            Contact::with(['category', 'tags']),
            $conditions
        )
            ->latest()
            ->paginate(7)
            ->withQueryString();

        return view('admin.index', [
            'categories' => Category::all(),
            'contacts' => $contacts,
            'tags' => Tag::all(),
        ]);
    }

    public function export(ExportContactRequest $request): StreamedResponse
    {
        $contacts = $this->applyContactSearchConditions(
            Contact::with('category'),
            $request->validated()
        )
            ->latest()
            ->get();

        $fileName = 'contacts_'.now()->format('YmdHis').'.csv';
        $genderLabels = [
            1 => '男性',
            2 => '女性',
            3 => 'その他',
        ];

        return response()->streamDownload(function () use ($contacts, $genderLabels): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', '氏名', '性別', 'メール', '電話', '住所', '建物', 'カテゴリ', '内容', '作成日時']);

            foreach ($contacts as $contact) {
                fputcsv($handle, [
                    $contact->id,
                    $contact->first_name.' '.$contact->last_name,
                    $genderLabels[$contact->gender] ?? '',
                    $contact->email,
                    $contact->tel,
                    $contact->address,
                    $contact->building,
                    $contact->category?->content ?? '',
                    $contact->detail,
                    $contact->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Contact $contact): View
    {
        return view('admin.show', [
            'contact' => $contact->load(['category', 'tags']),
        ]);
    }

    public function storeTag(StoreTagRequest $request): RedirectResponse
    {
        Tag::create($request->validated());

        return redirect('/admin');
    }

    public function editTag(Tag $tag): View
    {
        return view('admin.tags.edit', [
            'tag' => $tag,
        ]);
    }

    public function updateTag(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $tag->update($request->validated());

        return redirect('/admin');
    }

    public function destroyTag(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect('/admin');
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
            ->when(
                isset($conditions['gender']) && (int) $conditions['gender'] !== 0,
                fn (Builder $query): Builder => $query->where('gender', $conditions['gender'])
            )
            ->when($conditions['category_id'] ?? null, fn (Builder $query, int $categoryId): Builder => $query->where('category_id', $categoryId))
            ->when($conditions['date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', $date));
    }
}
