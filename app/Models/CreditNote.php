<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'sales_invoice_id', 'party_id', 'credit_note_no', 'credit_note_date',
        'reason', 'subtotal', 'tax_amount', 'grand_total', 'return_status', 'created_by',
    ];

    protected $casts = ['credit_note_date' => 'date'];

    public function company() { return $this->belongsTo(Company::class); }
    public function invoice() { return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id'); }
    public function party() { return $this->belongsTo(Party::class); }
    public function items() { return $this->hasMany(CreditNoteItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function hasCompleteSalesReturn(): bool
    {
        $this->loadMissing(['items', 'invoice.returns.items']);
        foreach ($this->items as $line) {
            $returned = $this->invoice->returns->flatMap->items->where('sales_invoice_item_id', $line->sales_invoice_item_id);
            $selectedKeys = collect($line->selected_units ?? [])->pluck('key')->filter();
            if ($selectedKeys->isNotEmpty()) {
                $returnedKeys = $returned->flatMap(fn ($row) => $row->selected_units ?? [])->pluck('key')->filter();
                if (!$selectedKeys->every(fn ($key) => $returnedKeys->contains($key))) return false;
            } elseif ((float) $line->quantity > (float) $returned->sum('quantity') + 0.0001) {
                return false;
            }
        }
        return $this->items->isNotEmpty();
    }
}
