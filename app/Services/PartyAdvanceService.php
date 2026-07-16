<?php

namespace App\Services;

use App\Models\PartyAdvance;
use App\Models\PartyAdvanceAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartyAdvanceService
{
    public function availableForParty(int $partyId, string $direction, ?string $to = null): Collection
    {
        $companyId = auth()->user()->current_company_id;

        return PartyAdvance::with(['payment.bankAccount', 'allocations'])
            ->where('company_id', $companyId)
            ->where('party_id', $partyId)
            ->where('direction', $direction)
            ->where('remaining_amount', '>', 0)
            ->when($to, fn($query) => $query->whereDate('advance_date', '<=', $to))
            ->orderBy('advance_date')
            ->orderBy('id')
            ->get()
            ->map(fn(PartyAdvance $advance) => [
                'id' => $advance->id,
                'advance_date' => $advance->advance_date?->format('Y-m-d'),
                'advance_date_label' => $advance->advance_date?->format('d M Y'),
                'reference_no' => $advance->reference_no ?: '-',
                'payment_mode' => $advance->payment_mode ?: '-',
                'description' => $advance->description ?: '-',
                'original_amount' => round((float) $advance->original_amount, 2),
                'remaining_amount' => round((float) $advance->remaining_amount, 2),
                'used_amount' => round((float) $advance->original_amount - (float) $advance->remaining_amount, 2),
                'direction' => $advance->direction,
                'history' => $advance->allocations->map(fn($allocation) => [
                    'document_no' => $allocation->document_no ?: '-',
                    'document_type' => class_basename($allocation->document_type),
                    'amount' => round((float) $allocation->amount, 2),
                ])->values(),
            ])
            ->values();
    }

    public function applyForDocument(
        int $partyId,
        string $direction,
        string $documentType,
        int $documentId,
        string $documentNo,
        array $applications
    ): void {
        $companyId = auth()->user()->current_company_id;
        $cleanApplications = collect($applications)
            ->filter(fn($row) => (float) ($row['amount'] ?? 0) > 0 && !empty($row['party_advance_id']))
            ->values();

        if ($cleanApplications->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($companyId, $partyId, $direction, $documentType, $documentId, $documentNo, $cleanApplications) {
            $advances = PartyAdvance::where('company_id', $companyId)
                ->where('party_id', $partyId)
                ->where('direction', $direction)
                ->whereIn('id', $cleanApplications->pluck('party_advance_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cleanApplications as $row) {
                $advance = $advances->get((int) $row['party_advance_id']);
                abort_if(!$advance, 422, 'Selected advance not found.');
                $amount = round((float) $row['amount'], 2);
                abort_if($amount > (float) $advance->remaining_amount, 422, 'Advance amount exceeds remaining balance.');
                abort_if($amount <= 0, 422, 'Advance amount must be greater than zero.');

                PartyAdvanceAllocation::create([
                    'company_id' => $companyId,
                    'party_id' => $partyId,
                    'party_advance_id' => $advance->id,
                    'document_type' => $documentType,
                    'document_id' => $documentId,
                    'document_no' => $documentNo,
                    'amount' => $amount,
                    'created_by' => auth()->id(),
                ]);

                $advance->update([
                    'remaining_amount' => max(0, (float) $advance->remaining_amount - $amount),
                ]);
            }
        });
    }

    public function releaseForDocument(string $documentType, int $documentId): void
    {
        DB::transaction(function () use ($documentType, $documentId) {
            $allocations = PartyAdvanceAllocation::where('document_type', $documentType)
                ->where('document_id', $documentId)
                ->lockForUpdate()
                ->get();

            foreach ($allocations as $allocation) {
                $advance = PartyAdvance::whereKey($allocation->party_advance_id)->lockForUpdate()->first();
                if ($advance) {
                    $advance->update([
                        'remaining_amount' => (float) $advance->remaining_amount + (float) $allocation->amount,
                    ]);
                }
                $allocation->delete();
            }
        });
    }

    public function createAdvanceFromPayment(object $payment): void
    {
        PartyAdvance::create([
            'company_id' => $payment->company_id,
            'party_id' => $payment->party_id,
            'party_payment_id' => $payment->id,
            'direction' => $payment->payment_type === 'payment_in' ? 'in' : 'out',
            'advance_date' => $payment->payment_date,
            'original_amount' => $payment->total_amount,
            'remaining_amount' => $payment->total_amount,
            'reference_no' => $payment->reference_no,
            'payment_mode' => $payment->payment_mode,
            'description' => $payment->description ?: 'Advance payment.',
            'created_by' => $payment->created_by,
        ]);
    }
}
