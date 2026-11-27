<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search'      => ['nullable', 'string', 'max:100'],
            'type'        => ['nullable', 'in:lost,found'],
            'status'      => ['nullable', 'in:open,in_verification,completed'],
            'category_id' => ['nullable', 'integer'],
            'location_id' => ['nullable', 'integer'],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $items = Item::query()
            ->with(['category', 'location', 'reporter:id,name'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['location_id'] ?? null, fn ($q, $v) => $q->where('location_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('event_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('event_date', '<=', $v))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return ItemResource::collection($items);
    }

    public function show(Item $item): ItemResource
    {
        return new ItemResource($item->load(['category', 'location', 'reporter:id,name']));
    }

    public function store(Request $request): JsonResponse
{
    $data = $request->validate([
        'type'        => ['required', 'in:lost,found'],
        'name'        => ['required', 'string', 'max:255'],
        'description' => ['required', 'string'],
        'category_id' => ['required', 'exists:item_categories,id'],
        'location_id' => ['required', 'exists:locations,id'],
        'event_date'  => ['required', 'date', 'before_or_equal:today'],
        'event_time'  => ['nullable', 'date_format:H:i'],
        'photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
    ]);

    if ($request->hasFile('photo')) {
        $data['photo'] = $request->file('photo')->store('items', 'public');
    }

    $item = $request->user()->items()->create($data + ['status' => 'open']);

    return (new ItemResource($item->load(['category', 'location', 'reporter:id,name'])))
        ->response()
        ->setStatusCode(201);
}

public function update(Request $request, Item $item): ItemResource
{
    $this->ensureCanModify($request, $item);

    $data = $request->validate([
        'name'        => ['sometimes', 'required', 'string', 'max:255'],
        'description' => ['sometimes', 'required', 'string'],
        'category_id' => ['sometimes', 'required', 'exists:item_categories,id'],
        'location_id' => ['sometimes', 'required', 'exists:locations,id'],
        'event_date'  => ['sometimes', 'required', 'date', 'before_or_equal:today'],
        'event_time'  => ['nullable', 'date_format:H:i'],
        'photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
    ]);

    if ($request->hasFile('photo')) {
        if ($item->photo) {
            Storage::disk('public')->delete($item->photo);
        }
        $data['photo'] = $request->file('photo')->store('items', 'public');
    } else {
        unset($data['photo']);
    }

    $item->update($data);

    return new ItemResource($item->load(['category', 'location', 'reporter:id,name']));
}

public function destroy(Request $request, Item $item): JsonResponse
{
    $this->ensureCanModify($request, $item);

    if ($item->photo) {
        Storage::disk('public')->delete($item->photo);
    }

    $item->delete();

    return response()->json(['message' => 'Laporan berhasil dihapus']);
}

private function ensureCanModify(Request $request, Item $item): void
{
    $user = $request->user();

    abort_if(
        $item->user_id !== $user->id && $user->role !== 'admin',
        403,
        'Anda tidak berhak mengubah laporan ini'
    );

    abort_if(
        $item->status !== 'open',
        422,
        'Laporan yang sedang diverifikasi atau sudah selesai tidak bisa diubah'
    );
}

public function mine(Request $request): AnonymousResourceCollection
{
    return ItemResource::collection(
        $request->user()->items()
            ->with(['category', 'location'])
            ->withCount(['claims as pending_claims_count' => fn ($q) => $q->where('status', 'pending')])
            ->latest()
            ->paginate(10)
    );
}
}