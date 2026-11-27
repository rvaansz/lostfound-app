<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClaimResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimController extends Controller
{
    private const RELATIONS = [
        'item.category',
        'item.location',
        'item.reporter:id,name,phone',
        'claimant:id,name,phone',
    ];

    public function store(Request $request, Item $item): JsonResponse
    {
        $user = $request->user();

        abort_if($item->type !== 'found', 422, 'Klaim hanya bisa diajukan pada laporan penemuan');
        abort_if(! in_array($item->status, ['open', 'in_verification']), 422, 'Barang ini sudah tidak bisa diklaim');
        abort_if($item->user_id === $user->id, 422, 'Anda tidak bisa mengklaim laporan milik sendiri');

        $alreadyClaimed = $item->claims()
            ->where('claimant_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        abort_if($alreadyClaimed, 422, 'Anda sudah mengajukan klaim untuk barang ini');

        $data = $request->validate([
            'proof_description' => ['required', 'string', 'min:10'],
            'proof_photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('proof_photo')) {
            $data['proof_photo'] = $request->file('proof_photo')->store('claims', 'public');
        }

        $claim = DB::transaction(function () use ($item, $user, $data) {
            $claim = $item->claims()->create($data + [
                'claimant_id' => $user->id,
                'status'      => 'pending',
            ]);

            $item->update(['status' => 'in_verification']);

            return $claim;
        });

        return (new ClaimResource($claim->load(self::RELATIONS)))
            ->response()
            ->setStatusCode(201);
    }
}