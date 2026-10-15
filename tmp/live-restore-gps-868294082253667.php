<?php

// CLI only: recover the single GPS explicitly approved by the account owner.
// Default is a preview. --apply commits both normal sales returns together.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$options = getopt('', ['root:', 'user:', 'apply']);
$root = $options['root'] ?? '/home/u173129352/domains/eemotrack.com/public_html/accounting';
if (!is_file($root.'/artisan') || !is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "Laravel application not found. Pass --root=/full/project/path\n");
    exit(1);
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apply = array_key_exists('apply', $options);
$connection = Illuminate\Support\Facades\DB::connection();
$connection->beginTransaction();

try {
    $needle = '868294082253667';
    $matches = fn ($unit) => is_array($unit)
        && in_array($needle, preg_split('/\s+/', trim((string) ($unit['vts_sim'] ?? ''))) ?: [], true);
    $sourceCompanies = App\Models\Company::whereRaw('UPPER(name) = ?', ['EEMOTRACK INDIA'])->get();
    $targetCompanies = App\Models\Company::whereRaw('UPPER(name) = ?', ['MARUTI SUZUKI VENTURES'])->get();
    if ($sourceCompanies->count() !== 1 || $targetCompanies->count() !== 1) {
        throw new RuntimeException('Could not identify exactly one Eemotrack and Maruti company.');
    }
    $source = $sourceCompanies->first();
    $target = $targetCompanies->first();
    App\Models\Company::whereIn('id', [$source->id, $target->id])->orderBy('id')->lockForUpdate()->get();
    $users = App\Models\User::whereKey((int) ($options['user'] ?? 2))
        ->whereRaw('UPPER(name) = ?', ['RAVI KUMAR'])->where('is_active', true)->get();
    if ($users->count() !== 1) {
        throw new RuntimeException('Could not identify exactly one active RAVI KUMAR account.');
    }
    $user = $users->first();
    Illuminate\Support\Facades\Auth::setUser($user);
    $serials = app(App\Services\SerialUnitService::class);
    $stockMatches = fn ($companyId) => collect($serials->unitPool($companyId))->flatten(1)->filter($matches)->values();
    $sourceStockBefore = $stockMatches($source->id)->count();
    $controller = app(App\Http\Controllers\Admin\Sales\SalesReturnController::class);
    $accounting = app(App\Services\AccountingService::class);
    $visibility = app(App\Services\EntryVisibilityService::class);
    $posted = [];

    foreach ([[$target, '00000081', 2200.0], [$source, '00000090', 1750.0]] as [$company, $invoiceNo, $approvedAmount]) {
        // Company selection is in memory only; the user's saved session/company
        // setting is not changed by this maintenance operation.
        $user->current_company_id = $company->id;
        if (!$user->isCompanyAdmin($company->id)) {
            throw new RuntimeException('RAVI KUMAR does not have company admin access.');
        }
        $invoices = App\Models\SalesInvoice::with(['items.item', 'party'])
            ->where('company_id', $company->id)->where('invoice_no', $invoiceNo)->lockForUpdate()->get();
        if ($invoices->count() !== 1) {
            throw new RuntimeException("Could not uniquely identify invoice {$invoiceNo} in {$company->name}.");
        }
        $invoice = $invoices->first();
        if ($company->id === $source->id && (!$invoice->inter_company_transfer
            || !in_array((int) $target->id, array_map('intval', $invoice->inter_company_target_company_ids ?? []), true))) {
            throw new RuntimeException('Source invoice does not transfer to Maruti.');
        }
        if ($company->id === $target->id && strtoupper(trim($invoice->party?->display_name ?? '')) !== 'ALFALAH CAR ACCESSORIES') {
            throw new RuntimeException('Customer invoice party differs from the approved return.');
        }
        $lines = $invoice->items->filter(fn ($line) => collect($line->selected_units ?? [])->contains($matches));
        if ($lines->count() !== 1) {
            throw new RuntimeException("Expected one GPS line in invoice {$invoiceNo}.");
        }
        $line = $lines->first();
        App\Models\Item::whereKey($line->item_id)->lockForUpdate()->firstOrFail();
        $units = collect($line->selected_units)->filter($matches)->values()->all();
        if (count($units) !== 1 || empty($units[0]['key']) || (float) $line->quantity <= 0) {
            throw new RuntimeException('GPS identity/quantity could not be verified.');
        }
        if (abs((float) $line->line_total / (float) $line->quantity - $approvedAmount) > 0.01) {
            throw new RuntimeException("Invoice {$invoiceNo} amount differs from the approved return amount.");
        }
        $key = (string) $units[0]['key'];
        $existing = App\Models\SalesReturnItem::where('sales_invoice_item_id', $line->id)
            ->whereHas('salesReturn', fn ($q) => $q->where('company_id', $company->id))->lockForUpdate()->get()
            ->filter(fn ($row) => collect($row->selected_units ?? [])->contains(fn ($unit) => $matches($unit) || (string) ($unit['key'] ?? '') === $key));
        if ($existing->count() > 1) {
            throw new RuntimeException('Duplicate existing returns found. No changes saved.');
        }
        if ($existing->isNotEmpty()) {
            $return = $existing->first()->salesReturn;
            $state = 'already posted';
        } else {
            if ($sourceStockBefore > 0) {
                throw new RuntimeException('GPS is already in Eemotrack stock but return history differs. Stopped to prevent double posting.');
            }
            $request = Illuminate\Http\Request::create('/admin/sales-returns', 'POST', [
                'sales_invoice_id' => $invoice->id, 'return_no' => '',
                'return_date' => Illuminate\Support\Carbon::now('Asia/Kolkata')->toDateString(),
                'reason' => 'User-authorized return of GPS 868294082253667 (SIM 5754195000566), customer to Maruti and then to Eemotrack stock.',
                'line_id' => [$line->id], 'quantity' => [1], 'returned_units' => [json_encode($units)],
                'visible_to_all_company' => 1,
            ]);
            $controller->store($request, $accounting, $visibility);
            $return = App\Models\SalesReturn::where('company_id', $company->id)
                ->where('sales_invoice_id', $invoice->id)->latest('id')->firstOrFail();
            if (abs((float) $return->grand_total - $approvedAmount) > 0.01) {
                throw new RuntimeException('Return amount verification failed.');
            }
            if ($invoice->sale_type === 'credit') {
                $credit = App\Models\PartyLedger::where('reference_type', App\Models\SalesReturn::class)
                    ->where('reference_id', $return->id)->sum('credit');
                if (abs((float) $credit - $approvedAmount) > 0.01) {
                    throw new RuntimeException('Return party ledger verification failed.');
                }
            }
            App\Models\AuditLog::log('created', [
                'company_id' => $company->id, 'model' => App\Models\SalesReturn::class, 'model_id' => $return->id,
                'new_values' => ['gps' => $needle, 'unit_key' => $key, 'quantity' => 1, 'amount' => $approvedAmount, 'return_no' => $return->return_no],
                'description' => 'Both GPS sales returns explicitly authorized by user; executed through the existing sales return workflow.',
            ]);
            $state = $apply ? 'posted' : 'preview';
        }
        $posted[] = ['company' => $company->name, 'return_no' => $return->return_no, 'invoice' => $invoiceNo, 'unit_return_amount' => $approvedAmount, 'state' => $state];
    }

    $sourceStock = $stockMatches($source->id);
    $targetStock = $stockMatches($target->id);
    if ($sourceStock->count() !== 1 || $sourceStock->where('sold', false)->count() !== 1 || $targetStock->count() !== 0) {
        throw new RuntimeException('Final stock check failed: expected Eemotrack 1 available, Maruti 0.');
    }
    if ($apply) {
        $connection->commit();
    } else {
        $connection->rollBack();
    }
    echo json_encode([
        'result' => $apply ? 'SAVED' : 'PREVIEW ONLY - no database changes saved',
        'returns' => $posted, 'gps' => $needle,
        'verified_stock' => ['EEMOTRACK INDIA' => 1, 'MARUTI SUZUKI VENTURES' => 0],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $e) {
    if ($connection->transactionLevel() > 0) {
        $connection->rollBack();
    }
    $message = $e instanceof Illuminate\Validation\ValidationException ? json_encode($e->errors()) : $e->getMessage();
    fwrite(STDERR, 'FAILED: '.$message."\nNo changes from this run were saved.\n");
    exit(1);
}
