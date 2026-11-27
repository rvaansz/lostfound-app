<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClaimResource;
use App\Models\Item;
use App\Models\Claim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

    public function index(Request $request, Item $item): AnonymousResourceCollection
    {
        $user = $request->user();

        abort_if(
            $item->user_id !== $user->id && $user->role !== 'admin',
            403,
            'Anda tidak berhak melihat klaim pada laporan ini'
        );

        return ClaimResource::collection(
            $item->claims()->with(self::RELATIONS)->latest()->get()
        );
    }

    public function show(Request $request, Claim $claim): ClaimResource
    {
        $claim->load(self::RELATIONS);
        $user = $request->user();

        abort_if(
            $claim->claimant_id !== $user->id
                && $claim->item->user_id !== $user->id
                && $user->role !== 'admin',
            403,
            'Anda tidak berhak melihat klaim ini'
        );

        return new ClaimResource($claim);
    }

    public function approve(Request $request, Claim $claim): ClaimResource
    {
        $claim->load('item');
        $this->ensureCanVerify($request, $claim);

        $data = $request->validate([
            'verifier_note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($claim, $request, $data) {
            $claim->update([
                'status'        => 'approved',
                'verified_by'   => $request->user()->id,
                'verifier_note' => $data['verifier_note'] ?? null,
                'verified_at'   => now(),
            ]);

            // Klaim lain yang masih menunggu pada barang yang sama otomatis ditolak
            Claim::where('item_id', $claim->item_id)
                ->where('id', '!=', $claim->id)
                ->where('status', 'pending')
                ->update([
                    'status'        => 'rejected',
                    'verified_by'   => $request->user()->id,
                    'verifier_note' => 'Barang sudah diserahkan kepada pemilik lain',
                    'verified_at'   => now(),
                ]);

            $claim->item->update(['status' => 'completed']);
        });

        return new ClaimResource($claim->fresh(self::RELATIONS));
    }

    public function reject(Request $request, Claim $claim): ClaimResource
    {
        $claim->load('item');
        $this->ensureCanVerify($request, $claim);

        $data = $request->validate([
            'verifier_note' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($claim, $request, $data) {
            $claim->update([
                'status'        => 'rejected',
                'verified_by'   => $request->user()->id,
                'verifier_note' => $data['verifier_note'],
                'verified_at'   => now(),
            ]);

            $stillPending = Claim::where('item_id', $claim->item_id)
                ->where('status', 'pending')
                ->exists();

            if (! $stillPending) {
                $claim->item->update(['status' => 'open']);
            }
        });

        return new ClaimResource($claim->fresh(self::RELATIONS));
    }

    private function ensureCanVerify(Request $request, Claim $claim): void
    {
        $user = $request->user();

        abort_if(
            $claim->item->user_id !== $user->id && $user->role !== 'admin',
            403,
            'Anda tidak berhak memverifikasi klaim ini'
        );

        abort_if(
            $claim->claimant_id === $user->id,
            403,
            'Anda tidak bisa memverifikasi klaim milik sendiri'
        );

        abort_if(
            $claim->status !== 'pending',
            422,
            'Klaim ini sudah diproses'
        );
    }

    public function mine(Request $request): AnonymousResourceCollection
    {
        return ClaimResource::collection(
            $request->user()->claims()->with(self::RELATIONS)->latest()->paginate(10)
        );
    }
}