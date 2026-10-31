<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiBusinessAssistant
{
    private const TABLES = [
        'items', 'product_types', 'product_categories', 'parties', 'buyers',
        'sales_invoices', 'sales_invoice_items', 'sales_return_items', 'sales_returns',
        'purchase_bills', 'purchase_bill_items', 'purchase_returns', 'purchase_return_items',
        'estimates', 'estimate_items', 'purchase_estimates', 'purchase_estimate_items',
        'delivery_challans', 'delivery_challan_items', 'stock_out_challans', 'stock_out_challan_items',
        'production_batches', 'stock_movements', 'stock_adjustments', 'stock_transfers', 'stock_transfer_items',
        'party_payments', 'party_payment_allocations', 'party_advances', 'expenses', 'other_transactions',
        'bank_accounts', 'bank_transactions', 'replacements', 'credit_notes', 'credit_note_items',
        'audit_logs', 'companies',
    ];

    private const WORKFLOWS = [
        'sale' => ['route' => 'admin.sales.create', 'view' => 'sales.view', 'permission' => 'sales.create', 'label' => 'Sale invoice'],
        'sale_return' => ['route' => 'admin.sales-returns.create', 'view' => 'sales.view', 'permission' => 'sales.create', 'label' => 'Sales return'],
        'purchase' => ['route' => 'admin.purchases.create', 'view' => 'purchase.view', 'permission' => 'purchase.create', 'label' => 'Purchase bill'],
        'purchase_return' => ['route' => 'admin.purchase-returns.create', 'view' => 'purchase.view', 'permission' => 'purchase.create', 'label' => 'Purchase return'],
        'purchase_estimate' => ['route' => 'admin.purchase-estimates.create', 'view' => 'purchase_estimates.view', 'permission' => 'purchase_estimates.create', 'label' => 'Purchase estimate'],
        'item' => ['route' => 'admin.items.create', 'view' => 'items.view', 'permission' => 'items.create', 'label' => 'Item'],
        'estimate' => ['route' => 'admin.estimates.create', 'view' => 'estimates.view', 'permission' => 'estimates.create', 'label' => 'Estimate'],
        'delivery_challan' => ['route' => 'admin.delivery-challans.create', 'view' => 'delivery_challans.view', 'permission' => 'delivery_challans.create', 'label' => 'Delivery challan'],
        'special_stock_out' => ['route' => 'admin.stock-out-challans.create', 'view' => 'stock_out_challans.view', 'permission' => 'stock_out_challans.create', 'label' => 'Special stock out'],
        'crm' => ['route' => 'admin.production-batches.create', 'view' => 'production.view', 'permission' => 'production.create', 'label' => 'CRM / production batch'],
        'crm_revert' => ['route' => 'admin.production-reverts.index', 'view' => 'production.view', 'permission' => 'production_reverts.view', 'label' => 'CRM revert'],
        'payment_in' => ['route' => 'admin.party-payments.create', 'view' => 'party_payments.view', 'permission' => 'party_payments.create', 'label' => 'Payment in'],
        'payment_out' => ['route' => 'admin.party-payments.create', 'view' => 'party_payments.view', 'permission' => 'party_payments.create', 'label' => 'Payment out'],
        'party' => ['route' => 'admin.parties.create', 'view' => 'parties.view', 'permission' => 'parties.create', 'label' => 'Party / customer / supplier'],
        'expense' => ['route' => 'admin.expenses.create', 'view' => 'expenses.view', 'permission' => 'expenses.create', 'label' => 'Expense'],
        'other_transaction' => ['route' => 'admin.other-transactions.create', 'view' => 'other_transactions.view', 'permission' => 'other_transactions.create', 'label' => 'Other income / expense'],
        'bank_transaction' => ['route' => 'admin.bank-transactions.create', 'view' => 'banking.view', 'permission' => 'banking.manage', 'label' => 'Bank transaction'],
        'credit_note' => ['route' => 'admin.credit-notes.create', 'view' => 'credit_notes.view', 'permission' => 'credit_notes.create', 'label' => 'Credit note'],
        'replacement' => ['route' => 'admin.replacements.create', 'view' => 'replacements.view', 'permission' => 'replacements.create', 'label' => 'Replacement'],
        'buyer' => ['route' => 'admin.buyers.create', 'view' => 'production.view', 'permission' => 'production.view', 'label' => 'CRM buyer'],
        'stock' => ['route' => 'admin.stocks.index', 'view' => 'stocks.view', 'permission' => 'stocks.view', 'label' => 'Stock'],
    ];

    private const CREATE_ACTIONS = [
        'sale' => ['controller' => \App\Http\Controllers\Admin\Sales\SalesInvoiceController::class, 'path' => '/admin/sales', 'label' => 'Sale invoice'],
        'sale_return' => ['controller' => \App\Http\Controllers\Admin\Sales\SalesReturnController::class, 'path' => '/admin/sales-returns', 'label' => 'Sales return'],
        'purchase' => ['controller' => \App\Http\Controllers\Admin\Purchase\PurchaseBillController::class, 'path' => '/admin/purchases', 'label' => 'Purchase bill'],
        'purchase_return' => ['controller' => \App\Http\Controllers\Admin\Purchase\PurchaseReturnController::class, 'path' => '/admin/purchase-returns', 'label' => 'Purchase return'],
        'item' => ['controller' => \App\Http\Controllers\Admin\Inventory\ItemController::class, 'path' => '/admin/items', 'label' => 'Item'],
        'estimate' => ['controller' => \App\Http\Controllers\Admin\Sales\EstimateController::class, 'path' => '/admin/estimates', 'label' => 'Estimate'],
        'purchase_estimate' => ['controller' => \App\Http\Controllers\Admin\Purchase\PurchaseEstimateController::class, 'path' => '/admin/purchase-estimates', 'label' => 'Purchase estimate'],
        'delivery_challan' => ['controller' => \App\Http\Controllers\Admin\Sales\DeliveryChallanController::class, 'path' => '/admin/delivery-challans', 'label' => 'Delivery challan'],
        'special_stock_out' => ['controller' => \App\Http\Controllers\Admin\Sales\StockOutChallanController::class, 'path' => '/admin/stock-out-challans', 'label' => 'Special stock out'],
        'crm' => ['controller' => \App\Http\Controllers\Admin\Production\ProductionBatchController::class, 'path' => '/admin/production-batches', 'label' => 'CRM / production batch'],
        'payment_in' => ['controller' => \App\Http\Controllers\Admin\Accounts\PartyPaymentController::class, 'path' => '/admin/party-payments', 'label' => 'Payment in'],
        'payment_out' => ['controller' => \App\Http\Controllers\Admin\Accounts\PartyPaymentController::class, 'path' => '/admin/party-payments', 'label' => 'Payment out'],
        'party' => ['controller' => \App\Http\Controllers\Admin\Accounts\PartyController::class, 'path' => '/admin/parties', 'label' => 'Party / customer / supplier'],
        'expense' => ['controller' => \App\Http\Controllers\Admin\ExpenseController::class, 'path' => '/admin/expenses', 'label' => 'Expense'],
        'other_transaction' => ['controller' => \App\Http\Controllers\Admin\OtherTransactionController::class, 'path' => '/admin/other-transactions', 'label' => 'Other income / expense'],
        'bank_transaction' => ['controller' => \App\Http\Controllers\Admin\Accounts\BankTransactionController::class, 'path' => '/admin/bank-transactions', 'label' => 'Bank transaction'],
        'credit_note' => ['controller' => \App\Http\Controllers\Admin\Sales\CreditNoteController::class, 'path' => '/admin/credit-notes', 'label' => 'Credit note'],
        'replacement' => ['controller' => \App\Http\Controllers\Admin\ReplacementController::class, 'path' => '/admin/replacements', 'label' => 'Replacement'],
        'buyer' => ['controller' => \App\Http\Controllers\Admin\Inventory\BuyerController::class, 'path' => '/admin/buyers', 'label' => 'CRM buyer'],
    ];

    public function reply(User $user, array $messages): array
    {
        $latestUserMessage = collect($messages)->where('role', 'user')->last()['content'] ?? '';
        $latestSalesReply = $this->latestSalesReply($user, (string) $latestUserMessage);
        if ($latestSalesReply !== null) return ['reply' => $latestSalesReply, 'drafts' => []];
        $todaySalesReply = $this->todaySalesReply($user, (string) $latestUserMessage);
        if ($todaySalesReply !== null) return ['reply' => $todaySalesReply, 'drafts' => []];

        $key = (string) config('services.nvidia.key');
        if ($key === '') {
            return ['reply' => 'NVIDIA AI service abhi configure nahi hai. Server ke .env me NVIDIA_API_KEY set karke application config cache refresh karein. | NVIDIA AI is not configured yet. Set NVIDIA_API_KEY in the server .env and refresh the application config cache.', 'drafts' => []];
        }

        $input = collect($messages)->filter(fn ($m) => in_array($m['role'] ?? '', ['user', 'assistant'], true))
            ->take(-12)->map(fn ($m) => ['role' => $m['role'], 'content' => mb_substr((string) ($m['content'] ?? ''), 0, 4000)])->values()->all();
        $conversation = array_merge([['role' => 'system', 'content' => $this->instructions($user)]], $input);
        $tools = $this->tools();
        $drafts = [];
        $response = $this->request($key, [
            'model' => config('services.nvidia.model'),
            'messages' => $conversation,
            'tools' => $tools,
            'tool_choice' => 'auto',
            'stream' => false,
            'max_tokens' => 4096,
        ]);

        for ($round = 0; $round < 4; $round++) {
            $assistantMessage = $response['choices'][0]['message'] ?? [];
            $calls = collect($assistantMessage['tool_calls'] ?? []);
            if ($calls->isEmpty()) {
                return ['reply' => (string) ($assistantMessage['content'] ?? '') ?: 'Mujhe is request ka jawab nahi mila. Kripya thoda aur detail dein. | I could not produce an answer. Please add a little more detail.', 'drafts' => $drafts];
            }

            $conversation[] = $assistantMessage;
            $outputs = [];
            foreach ($calls as $call) {
                $function = $call['function'] ?? [];
                $args = json_decode($function['arguments'] ?? '{}', true) ?: [];
                $result = match ($function['name'] ?? '') {
                    'search_company_records' => $this->search($user, $args),
                    'diagnose_serial_number' => $this->diagnoseSerial($user, (string) ($args['serial'] ?? '')),
                    'inspect_company_schema' => $this->inspectSchema($user, $args),
                    'summarize_company_table' => $this->summarize($user, $args),
                    'prepare_transaction' => $this->prepareTransaction($user, $args),
                    'open_business_workflow' => $this->workflow($user, $args),
                    default => ['error' => 'Unsupported action.'],
                };
                if (($function['name'] ?? '') === 'prepare_transaction' && empty($result['error']) && !empty($result['draft_id'])) $drafts[] = $result;
                $outputs[] = ['role' => 'tool', 'tool_call_id' => $call['id'], 'content' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)];
            }
            $conversation = array_merge($conversation, $outputs);
            $response = $this->request($key, [
                'model' => config('services.nvidia.model'),
                'messages' => $conversation,
                'tools' => $tools,
                'tool_choice' => 'auto',
                'stream' => false,
                'max_tokens' => 4096,
            ]);
        }

        $lastMessage = $response['choices'][0]['message']['content'] ?? '';
        return ['reply' => (string) $lastMessage ?: 'Jawab tayyar nahi ho saka. Sawal ko ek baar phir bhejein. | I could not prepare a reply. Please try sending your question again.', 'drafts' => $drafts];
    }

    private function todaySalesReply(User $user, string $question): ?string
    {
        $asksTodaySales = preg_match('/\b(aaj|today)\b.*\b(sales?|bikri)\b|\b(sales?|bikri)\b.*\b(aaj|today)\b/i', $question);
        if (!$asksTodaySales || (!$user->isSuperAdmin() && !$user->hasPermission('sales.view'))) return null;

        $companyId = (int) $user->current_company_id;
        if (!$companyId) return null;
        $date = now(config('app.timezone', 'Asia/Kolkata'))->toDateString();
        $sales = \App\Models\SalesInvoice::query()->where('company_id', $companyId)
            ->whereDate('billing_date', $date)->where('status', 'posted');
        $count = (clone $sales)->count();
        $total = (float) $sales->sum('grand_total');
        $amount = '₹'.number_format($total, 2, '.', ',');
        $isHindi = preg_match('/[\x{0900}-\x{097F}]/u', $question)
            || preg_match('/\b(aaj|batao|bataiye|kitna|hai|ka|ki)\b/i', $question);

        return $isHindi
            ? ($count ? "Aaj ({$date}) ki posted sales **{$amount}** hai ({$count} invoice)." : "Aaj ({$date}) abhi tak koi posted sales invoice nahi hai. Total sales **{$amount}** hai.")
            : ($count ? "Posted sales for today ({$date}) total **{$amount}** across {$count} invoice(s)." : "There are no posted sales invoices for today ({$date}) so far. Total sales: **{$amount}**.");
    }

    private function latestSalesReply(User $user, string $question): ?string
    {
        $asksLatest = preg_match('/\b(last|latest|recent|pichhl[ei]|aakhri)\b.*\b(sales?|sale|bikri)\b|\b(sales?|sale|bikri)\b.*\b(last|latest|recent|pichhl[ei]|aakhri)\b/i', $question);
        if (!$asksLatest || (!$user->isSuperAdmin() && !$user->hasPermission('sales.view'))) return null;

        $companyId = (int) $user->current_company_id;
        if (!$companyId) return null;
        $invoice = \App\Models\SalesInvoice::query()->with('party:id,display_name')
            ->where('company_id', $companyId)->where('status', 'posted')
            ->orderByDesc('billing_date')->orderByDesc('id')->first();
        $isHindi = preg_match('/[\x{0900}-\x{097F}]/u', $question)
            || preg_match('/\b(kab|kitn[ae]|kisko|hua|thi|tha|batao|bataiye)\b/i', $question);
        if (!$invoice) return $isHindi
            ? 'Is company ke records mein abhi koi posted sale invoice nahi mila.'
            : 'No posted sale invoice was found in this company’s records.';

        $date = $invoice->billing_date?->format('Y-m-d') ?? 'date unavailable';
        $amount = '₹'.number_format((float) $invoice->grand_total, 2, '.', ',');
        $customer = $invoice->party?->display_name ?: 'Cash customer';
        $number = $invoice->invoice_no ?: ('#'.$invoice->id);
        return $isHindi
            ? "Sabse latest posted sale **{$date}** ko hui thi. Invoice **{$number}**, amount **{$amount}**, customer **{$customer}**."
            : "The latest posted sale was on **{$date}**. Invoice **{$number}**, amount **{$amount}**, customer **{$customer}**.";
    }

    private function request(string $key, array $payload): array
    {
        try {
            $response = Http::withOptions(['verify' => config('services.nvidia.ca_bundle')])
                ->withToken($key)->acceptJson()->connectTimeout(15)
                ->timeout(max(30, (int) config('services.nvidia.timeout', 120)))
                ->post(rtrim(config('services.nvidia.base_url'), '/').'/chat/completions', $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            report($e);
            throw new \RuntimeException('NVIDIA AI ka jawab aane me zyada waqt lag gaya. Kripya dobara bhejein; agar baar-baar ho to NVIDIA free API par load ho sakta hai. | NVIDIA AI took too long to respond. Please retry; repeated timeouts may mean the NVIDIA free API is busy.');
        }
        if ($response->failed()) {
            report(new \RuntimeException('NVIDIA AI provider returned HTTP '.$response->status().': '.mb_substr($response->body(), 0, 1200)));
            throw new \RuntimeException('NVIDIA AI service se jawab nahi mila. Thodi der baad dobara koshish karein. | NVIDIA AI did not respond. Please try again shortly.');
        }
        return $response->json() ?? [];
    }

    private function tools(): array
    {
        return [
            ['type' => 'function', 'function' => ['name' => 'search_company_records', 'description' => 'Search records inside the signed-in user company. Use this for factual questions, serial tracking, CRM audit history, current stock and reports. Search is read-only.', 'parameters' => [
                'type' => 'object', 'properties' => [
                    'tables' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Relevant table names from the business database.'],
                    'term' => ['type' => 'string', 'description' => 'Exact ID, serial, invoice number, party/item name or other search value.'],
                    'limit' => ['type' => 'integer', 'description' => 'Maximum rows, 1 to 30.'],
                ], 'required' => ['tables', 'term'], 'additionalProperties' => false,
            ]]],
            ['type' => 'function', 'function' => ['name' => 'diagnose_serial_number', 'description' => 'Trace one exact serialized unit through CRM/production creation, CRM edit audit, original stock movement and current serialized stock. Always use this for a serial number history question. This tool searches the exact application tables and should be used before reporting not found.', 'parameters' => [
                'type' => 'object', 'properties' => ['serial' => ['type' => 'string', 'description' => 'Exact serial number to trace.']], 'required' => ['serial'], 'additionalProperties' => false,
            ]]],
            ['type' => 'function', 'function' => ['name' => 'inspect_company_schema', 'description' => 'Get names and data types of fields in authorized company tables before searching or building a report.', 'parameters' => [
                'type' => 'object', 'properties' => ['tables' => ['type' => 'array', 'items' => ['type' => 'string']]], 'required' => ['tables'], 'additionalProperties' => false,
            ]]],
            ['type' => 'function', 'function' => ['name' => 'summarize_company_table', 'description' => 'Read-only date filtered count and numeric totals from authorized company records. Use for report totals. Inspect schema first.', 'parameters' => [
                'type' => 'object', 'properties' => [
                    'table' => ['type' => 'string'], 'date_field' => ['type' => 'string'], 'date_from' => ['type' => 'string'], 'date_to' => ['type' => 'string'],
                    'group_by' => ['type' => 'string'], 'sum_fields' => ['type' => 'array', 'items' => ['type' => 'string']],
                ], 'required' => ['table'], 'additionalProperties' => false,
            ]]],
            ['type' => 'function', 'function' => ['name' => 'prepare_transaction', 'description' => 'Prepare a create transaction after the user supplied all required details. Use exact existing form payload keys: sale uses sale_type,billing_date,item_id[],quantity[],unit_price[]; purchase uses purchase_type,billing_date,item_id[],quantity[],unit_price[]; item uses item_type,item_code,name,unit,status; estimate/purchase_estimate use estimate_date,item_id[],quantity[],unit_price[]; delivery_challan/special_stock_out use challan_date,item_id[],quantity[]; CRM uses finished_item_id,production_date,quantity and optional unit_serial[]/unit_batch[]; payment_in/out uses payment_type,party_id,payment_date,bank_account_id,amount,allocations; party uses display_name,party_type,tax_type,status and contact/tax fields; expense uses expense_date,expense_ledger_id,bank_account_id,amount,payment_mode; other_transaction uses transaction_date,transaction_kind,expense_ledger_id,bank_account_id,amount; bank_transaction uses transaction_type,transaction_date,amount and relevant account IDs; credit_note uses source sale and lines; replacement uses source sale/item/serial and reason; buyer uses buyer name and code; returns require source invoice/bill and source line IDs and quantities. Prepare only after all required user details are gathered. This creates a reviewable draft only. The user must click Confirm & Save before any write happens.', 'parameters' => [
                'type' => 'object', 'properties' => [
                    'workflow' => ['type' => 'string', 'enum' => array_keys(self::CREATE_ACTIONS)],
                    'summary' => ['type' => 'string'],
                    'data' => ['type' => 'object', 'description' => 'Complete form payload using existing create form field names. Use database IDs for foreign keys; search records first. For item arrays, use parallel arrays such as item_id, quantity, unit_price, tax_percent.'],
                ], 'required' => ['workflow', 'summary', 'data'], 'additionalProperties' => false,
            ]]],
            ['type' => 'function', 'function' => ['name' => 'open_business_workflow', 'description' => 'Open a read-only stock page or the CRM revert page when explicitly requested. Do not open create forms; use prepare_transaction for all creation work.', 'parameters' => [
                'type' => 'object', 'properties' => [
                    'workflow' => ['type' => 'string', 'enum' => ['stock', 'crm_revert']],
                ], 'required' => ['workflow'], 'additionalProperties' => false,
            ]]],
        ];
    }

    private function instructions(User $user): string
    {
        $company = $user->currentCompany?->name ?? 'current company';
        $today = now(config('app.timezone', 'Asia/Kolkata'))->format('Y-m-d (l)');
        $allowed = collect(self::WORKFLOWS)->filter(fn ($w) => $user->isSuperAdmin() || ($user->hasPermission($w['view']) && $user->hasPermission($w['permission'])))->keys()->implode(', ');
        $createable = collect(self::CREATE_ACTIONS)->filter(function ($action, $key) use ($user) {
            $flow = self::WORKFLOWS[$key];
            return $user->isSuperAdmin() || ($user->hasPermission($flow['view']) && $user->hasPermission($flow['permission']));
        })->keys()->implode(', ');
        return "You are AI, a bilingual accounting assistant inside an accounting application. User language: answer in the same language/script as the latest user message (Hindi/Hinglish in Hindi/Hinglish; English in English). Current date in the application timezone (".config('app.timezone', 'Asia/Kolkata').") is {$today}. For 'today'/'aaj', use this exact date; never ask the user what today's date is. If reporting today's sales, query the database for this date and state clearly if there are none. Current company is {$company}. All data tools are automatically restricted to this company and to the user's module view permissions. Exact application tables include production_batches (CRM batches and units_data), stock_movements (stock additions/removals and movement_units), audit_logs (CRM edit old_values/new_values), items (item name and current_stock), and users (actor names). For any serial history request call diagnose_serial_number with the exact number first; do not guess table names or report not found without using its result. Never invent amounts, dates, document numbers, identities or database results. For other factual questions inspect the relevant schema and use search_company_records; for reports use summarize_company_table, then search rows if needed. Creation work: never send the user to a create page. Instead, have a conversation: determine the requested record type, search existing parties/items/invoices and resolve names to IDs, then ask concise follow-up questions for every missing required field. For a sale ask party/customer (or cash sale), date, each item, quantity, price and tax; for purchase ask supplier, date, items, quantities, prices and tax; for returns ask the original bill/invoice and returned lines/quantities; for an item ask type, unique code, name, unit and status; for an estimate ask customer, date, items, quantities and prices; for a challan ask party, date, items and quantities; for CRM ask finished item, production date, quantity and any serial/batch details; for payment ask party, direction, date, amount, payment mode and bank/account details when relevant. Ask about any ambiguity; do not guess financial values, customer/vendor, date, payment method, serials, or stock units. Only after enough details are present, call prepare_transaction with the exact existing-form payload and a short summary. This prepares a draft only. Tell the user to review the displayed summary and press Confirm & Save; never say complete before that confirmation returns success. After successful confirmation, clearly say the work is complete and report the saved result. If save returns validation errors, explain them and ask the user for the missing/correct information, then prepare a new draft. Create actions you may prepare: {$createable}. If open_business_workflow returns a URL, use it only for read-only stock or CRM revert requests. Do not expose credentials, passwords, tokens, or secrets.";
    }

    private function search(User $user, array $args): array
    {
        $companyId = (int) $user->current_company_id;
        if (!$companyId) return ['error' => 'No current company is selected.'];
        $term = trim((string) ($args['term'] ?? ''));
        if ($term === '') return ['error' => 'A search term is required.'];
        $results = [];
        $tablePermissions = $this->tablePermissions();
        $allowedTables = array_filter(array_intersect((array) ($args['tables'] ?? []), self::TABLES), function ($table) use ($user, $tablePermissions) {
            $permission = $tablePermissions[$table] ?? null;
            return $permission && $this->canReadTable($user, $table) && ($user->isSuperAdmin() || $user->hasPermission($permission));
        });
        foreach (array_slice($allowedTables, 0, 5) as $table) {
            if (!Schema::hasTable($table)) continue;
            $columns = Schema::getColumnListing($table);
            if (!in_array('company_id', $columns, true)) continue;
            $searchable = array_values(array_filter($columns, fn ($c) => preg_match('/(char|text|json|int|decimal|date|timestamp|float|double)/i', Schema::getColumnType($table, $c)) && !in_array($c, ['password', 'remember_token', 'screen_pin', 'api_token'], true)));
            if (!$searchable) continue;
            $query = \Illuminate\Support\Facades\DB::table($table)->where('company_id', $companyId)->where(function ($q) use ($searchable, $term) {
                foreach ($searchable as $column) $q->orWhere($column, 'like', '%'.$term.'%');
            });
            if (in_array('deleted_at', $columns, true)) $query->whereNull('deleted_at');
            $rows = $query->orderByDesc(in_array('id', $columns, true) ? 'id' : $columns[0])->limit(min(30, max(1, (int) ($args['limit'] ?? 15))))->get()->map(function ($row) {
                $data = (array) $row;
                foreach (['password', 'remember_token', 'screen_pin', 'api_token', 'user_agent', 'ip_address'] as $secret) unset($data[$secret]);
                foreach ($data as $key => $value) if (is_string($value) && mb_strlen($value) > 2400) $data[$key] = mb_substr($value, 0, 2400).'…';
                return $data;
            });
            if ($rows->isNotEmpty()) $results[$table] = $rows;
        }
        return ['company' => $user->currentCompany?->name, 'searched_term' => $term, 'records' => $results, 'note' => 'Read-only results; only current-company rows were searched.'];
    }

    public function diagnoseSerial(User $user, string $serial): array
    {
        $serial = trim($serial);
        $companyId = (int) $user->current_company_id;
        if (!preg_match('/^\d{6,30}$/', $serial)) return ['error' => 'Serial number format is invalid.'];
        if (!$companyId || !$user->currentCompany?->has_crm_access) return ['error' => 'CRM access is not available for the selected company.'];
        if (!$user->isSuperAdmin() && !$user->hasPermission('production.view')) return ['error' => 'You do not have permission to view CRM records.'];
        if (!$user->isSuperAdmin() && !$user->hasPermission('audit.view')) return ['error' => 'You do not have permission to view CRM edit history.'];

        $db = \Illuminate\Support\Facades\DB::class;
        $batches = $db::table('production_batches')->where('company_id', $companyId)
            ->where('units_data', 'like', '%'.$serial.'%')->orderByDesc('id')->limit(100)->get();
        $batch = $batches->first(function ($candidate) use ($serial) {
            foreach (json_decode($candidate->units_data ?? '[]', true) ?? [] as $unit) {
                if ((string) ($unit['serial_no'] ?? '') === $serial) return true;
            }
            return false;
        });

        $auditLogs = collect();
        if ($user->isSuperAdmin() || $user->hasPermission('audit.view')) {
            $auditLogs = $db::table('audit_logs')->where('company_id', $companyId)
                ->where('model', \App\Models\ProductionBatch::class)
                ->where(function ($q) use ($serial) { $q->where('old_values', 'like', '%'.$serial.'%')->orWhere('new_values', 'like', '%'.$serial.'%'); })
                ->orderBy('created_at')->limit(200)->get();
            if (!$batch) {
                $matchingLog = $auditLogs->first(function ($log) use ($serial) {
                    foreach (['old_values', 'new_values'] as $field) {
                        $values = json_decode($log->{$field} ?? '{}', true) ?? [];
                        foreach ($values['units_data'] ?? [] as $unit) if ((string) ($unit['serial_no'] ?? '') === $serial) return true;
                    }
                    return false;
                });
                if ($matchingLog) $batch = $db::table('production_batches')->where('company_id', $companyId)->where('id', $matchingLog->model_id)->first();
            }
        }

        if (!$batch) return ['found' => false, 'serial' => $serial, 'company' => $user->currentCompany?->name];

        $batchUnits = json_decode($batch->units_data ?? '[]', true) ?? [];
        $unitIndex = null;
        foreach ($batchUnits as $i => $unit) if ((string) ($unit['serial_no'] ?? '') === $serial) { $unitIndex = $i; break; }
        $edit = null;
        $priorSerial = null;
        foreach ($auditLogs->where('model_id', $batch->id) as $log) {
            $newValues = json_decode($log->new_values ?? '{}', true) ?? [];
            $oldValues = json_decode($log->old_values ?? '{}', true) ?? [];
            foreach ($newValues['units_data'] ?? [] as $i => $unit) {
                if ((string) ($unit['serial_no'] ?? '') === $serial) {
                    $edit = $log;
                    $unitIndex = $i;
                    $priorSerial = $oldValues['units_data'][$i]['serial_no'] ?? null;
                }
            }
        }

        $canReadItem = $user->isSuperAdmin() || $user->hasPermission('items.view') || $user->hasPermission('stocks.view');
        $item = $canReadItem ? $db::table('items')->where('company_id', $companyId)->where('id', $batch->finished_item_id)->first() : null;
        $movement = null;
        $movementUnit = null;
        if ($user->isSuperAdmin() || $user->hasPermission('stocks.view')) {
            $movement = $db::table('stock_movements')->where('company_id', $companyId)
                ->where('reference_type', \App\Models\ProductionBatch::class)->where('reference_id', $batch->id)
                ->where('movement_type', 'production_output')->orderBy('created_at')->first();
            $movementUnits = json_decode($movement->movement_units ?? '[]', true) ?? [];
            $movementUnit = $unitIndex !== null ? ($movementUnits[$unitIndex] ?? null) : null;
            if (!$movementUnit && $priorSerial) foreach ($movementUnits as $candidate) {
                if ((string) ($candidate['serial_no'] ?? '') === (string) $priorSerial) { $movementUnit = $candidate; break; }
            }
        }

        $currentNew = null;
        $currentPrior = null;
        if ($item && ($user->isSuperAdmin() || $user->hasPermission('stocks.view'))) {
            $currentUnits = app(SerialUnitService::class)->currentStockUnitsByItem($companyId)[$item->id] ?? [];
            foreach ($currentUnits as $unit) {
                if ((string) ($unit['serial_no'] ?? '') === $serial) $currentNew = $unit;
                if ($priorSerial && (string) ($unit['serial_no'] ?? '') === (string) $priorSerial) $currentPrior = $unit;
            }
        }

        $creator = $batch->created_by ? $db::table('users')->where('id', $batch->created_by)->value('name') : null;
        $editor = $edit?->user_id ? $db::table('users')->where('id', $edit->user_id)->value('name') : null;
        return [
            'found' => true, 'serial' => $serial, 'company' => $user->currentCompany?->name,
            'batch_id' => $batch->id, 'batch_no' => $batch->batch_no, 'production_date' => $batch->production_date,
            'batch_created_at' => $batch->created_at, 'created_by' => $creator, 'batch_status' => $batch->status, 'quantity' => $batch->quantity,
            'item_name' => $item->name ?? null, 'item_current_stock' => $item->current_stock ?? null,
            'unit_number' => $unitIndex === null ? null : $unitIndex + 1,
            'batch_number' => $batchUnits[$unitIndex]['batch_no'] ?? $movementUnit['batch_no'] ?? null,
            'prior_serial' => $priorSerial ?? ($movementUnit['serial_no'] ?? null),
            'edit_at' => $edit->created_at ?? null, 'edited_by' => $editor,
            'edit_description' => $edit->description ?? null,
            'stock_added' => $movement ? ['id' => $movement->id, 'date' => $movement->movement_date, 'created_at' => $movement->created_at, 'movement_type' => $movement->movement_type, 'direction' => $movement->direction, 'quantity' => $movement->quantity, 'reference_no' => $movement->reference_no, 'unit' => $movementUnit] : null,
            'current_stock_has_requested_serial' => $currentNew !== null,
            'current_stock_has_prior_serial' => $currentPrior !== null,
            'creation_audit_found' => $auditLogs->contains(fn ($log) => (int) $log->model_id === (int) $batch->id && $log->action === 'created'),
        ];
    }

    public function diagnoseSerialReply(User $user, string $serial, bool $hindi): string
    {
        $data = $this->diagnoseSerial($user, $serial);
        if (isset($data['error'])) return $data['error'];
        if (empty($data['found'])) return $hindi
            ? "Company **{$data['company']}** ke CRM/production, audit aur stock records me serial **{$serial}** nahi mila."
            : "Serial **{$serial}** was not found in the CRM/production, audit, or stock records for **{$data['company']}**.";

        $before = $data['prior_serial'] ?: 'audit me purana serial nahi mila';
        $item = $data['item_name'] ?: 'item details unavailable';
        $unit = $data['unit_number'] ? 'unit #'.$data['unit_number'] : 'unit number unavailable';
        $editAt = $data['edit_at'] ?: 'audit record me nahi mila';
        $editedBy = $data['edited_by'] ?: 'audit record me nahi mila';
        $quantity = $data['quantity'] ?? 'unknown';
        $movement = $data['stock_added'];
        $created = $movement
            ? "Stock movement **{$movement['movement_type']} ({$movement['direction']})** par **{$movement['quantity']} units** {$movement['created_at']} ko add hue (ref {$movement['reference_no']}). Movement ke is unit me serial **{$before}** tha."
            : 'Is CRM batch ki stock movement mujhe available permission ke saath nahi mili.';
        $stock = $data['stock_added']
            ? "Current stock me requested serial **{$serial}: ".($data['current_stock_has_requested_serial'] ? 'HAAN' : 'NAHI')."**; purana serial **{$before}: ".($data['current_stock_has_prior_serial'] ? 'HAAN' : 'NAHI')."**. Item ki total current quantity **{$data['item_current_stock']}** hai."
            : 'Current stock serial status permission ke karan verify nahi ho saka.';

        if ($hindi) {
            $reply = "Haan, serial **{$serial}** ka record mila.\n\nCRM **{$data['batch_no']}** ({$item}), production date **{$data['production_date']}**, quantity **{$quantity}**: batch **{$data['batch_created_at']}** ko create hua; creator **{$data['created_by']}**.\n\nIs unit (**{$unit}**, batch no **{$data['batch_number']}**) ka purana serial **{$before}** tha. CRM edit **{$editAt}** ko **{$editedBy}** ne kiya; audit description: ".($data['edit_description'] ?: 'available nahi')." Naya serial **{$serial}** hua.\n\n{$created}\n\n{$stock}";
            if (!$data['creation_audit_found']) $reply .= "\n\nNote: create ka separate audit log nahi mila; create time aur initial serial ko CRM record aur stock movement se verify kiya.";
            return $reply;
        }

        $reply = "Serial **{$serial}** is present in the records.\n\nCRM batch **{$data['batch_no']}** ({$item}), production date **{$data['production_date']}**, quantity **{$quantity}**, was created at **{$data['batch_created_at']}** by **{$data['created_by']}**.\n\nThis is **{$unit}** (batch no **{$data['batch_number']}**). Its prior serial was **{$before}**. The CRM edit was made at **{$editAt}** by **{$editedBy}**; audit description: ".($data['edit_description'] ?: 'not available')." The serial became **{$serial}**.\n\n{$created}\n\n{$stock}";
        if (!$data['creation_audit_found']) $reply .= "\n\nNote: there is no separate creation audit log; the create time and original serial are verified from the CRM record and stock movement.";
        return $reply;
    }

    private function tablePermissions(): array
    {
        return [
            'items' => 'items.view', 'product_types' => 'product_types.view', 'product_categories' => 'product_types.view', 'parties' => 'parties.view', 'buyers' => 'production.view',
            'sales_invoices' => 'sales.view', 'sales_invoice_items' => 'sales.view', 'sales_return_items' => 'sales.view', 'sales_returns' => 'sales.view',
            'purchase_bills' => 'purchase.view', 'purchase_bill_items' => 'purchase.view', 'purchase_returns' => 'purchase.view', 'purchase_return_items' => 'purchase.view',
            'estimates' => 'estimates.view', 'estimate_items' => 'estimates.view', 'purchase_estimates' => 'purchase_estimates.view', 'purchase_estimate_items' => 'purchase_estimates.view',
            'delivery_challans' => 'delivery_challans.view', 'delivery_challan_items' => 'delivery_challans.view', 'stock_out_challans' => 'stock_out_challans.view', 'stock_out_challan_items' => 'stock_out_challans.view',
            'production_batches' => 'production.view', 'stock_movements' => 'stocks.view', 'stock_adjustments' => 'stocks.view', 'stock_transfers' => 'stocks.view', 'stock_transfer_items' => 'stocks.view',
            'party_payments' => 'party_payments.view', 'party_payment_allocations' => 'party_payments.view', 'party_advances' => 'party_payments.view', 'expenses' => 'expenses.view', 'other_transactions' => 'other_transactions.view',
            'bank_accounts' => 'banking.view', 'bank_transactions' => 'banking.view', 'replacements' => 'replacements.view', 'credit_notes' => 'credit_notes.view', 'credit_note_items' => 'credit_notes.view',
            'audit_logs' => 'audit.view', 'companies' => 'companies.view',
        ];
    }

    private function inspectSchema(User $user, array $args): array
    {
        $schema = [];
        $permissions = $this->tablePermissions();
        foreach (array_slice(array_unique((array) ($args['tables'] ?? [])), 0, 8) as $table) {
            if (!in_array($table, self::TABLES, true) || !Schema::hasTable($table)) continue;
            $permission = $permissions[$table] ?? null;
            if (!$permission || !$this->canReadTable($user, $table) || (!$user->isSuperAdmin() && !$user->hasPermission($permission))) continue;
            $columns = Schema::getColumnListing($table);
            if (!in_array('company_id', $columns, true)) continue;
            $schema[$table] = collect($columns)->mapWithKeys(fn ($column) => [$column => Schema::getColumnType($table, $column)])->all();
        }
        return ['authorized_company_tables' => $schema];
    }

    private function summarize(User $user, array $args): array
    {
        $table = (string) ($args['table'] ?? '');
        $permissions = $this->tablePermissions();
        $permission = $permissions[$table] ?? null;
        if (!$permission || !in_array($table, self::TABLES, true) || !Schema::hasTable($table) || !$this->canReadTable($user, $table) || (!$user->isSuperAdmin() && !$user->hasPermission($permission))) return ['error' => 'Table is not available to this user.'];
        $columns = Schema::getColumnListing($table);
        if (!in_array('company_id', $columns, true)) return ['error' => 'Table is not company scoped.'];
        $query = \Illuminate\Support\Facades\DB::table($table)->where('company_id', $user->current_company_id);
        if (in_array('deleted_at', $columns, true)) $query->whereNull('deleted_at');
        $dateField = (string) ($args['date_field'] ?? '');
        if ($dateField !== '' && in_array($dateField, $columns, true) && preg_match('/date|timestamp/i', Schema::getColumnType($table, $dateField))) {
            foreach (['date_from' => '>=', 'date_to' => '<='] as $key => $operator) {
                if (!empty($args[$key])) {
                    try { $date = \Illuminate\Support\Carbon::parse($args[$key])->toDateString(); }
                    catch (\Throwable) { return ['error' => 'Invalid report date.']; }
                    $query->whereDate($dateField, $operator, $date);
                }
            }
        }
        $grammar = \Illuminate\Support\Facades\DB::connection()->getQueryGrammar();
        $selects = ['COUNT(*) as record_count'];
        foreach (array_slice((array) ($args['sum_fields'] ?? []), 0, 8) as $field) {
            if (in_array($field, $columns, true) && preg_match('/int|decimal|float|double/i', Schema::getColumnType($table, $field))) $selects[] = 'SUM('.$grammar->wrap($field).') as '.$grammar->wrap('sum_'.$field);
        }
        $groupBy = (string) ($args['group_by'] ?? '');
        if ($groupBy !== '' && in_array($groupBy, $columns, true)) {
            $query->select($groupBy)->selectRaw(implode(', ', $selects))->groupBy($groupBy)->orderByDesc('record_count')->limit(50);
            $rows = $query->get();
        } else {
            $query->selectRaw(implode(', ', $selects));
            $rows = [$query->first()];
        }
        return ['table' => $table, 'date_field' => $dateField ?: null, 'date_from' => $args['date_from'] ?? null, 'date_to' => $args['date_to'] ?? null, 'group_by' => $groupBy ?: null, 'totals' => $rows, 'note' => 'Read-only aggregate over the current company.'];
    }

    private function prepareTransaction(User $user, array $args): array
    {
        $workflowKey = (string) ($args['workflow'] ?? '');
        $action = self::CREATE_ACTIONS[$workflowKey] ?? null;
        if (!$action || !isset(self::WORKFLOWS[$workflowKey])) return ['error' => 'This create action is not supported.'];
        if (!$this->workflowAuthorized($user, $workflowKey)) return ['error' => 'You do not have permission for '.$action['label'].'.'];
        if (in_array($workflowKey, ['crm', 'buyer'], true) && !$user->currentCompany?->has_crm_access) return ['error' => 'CRM access is disabled for this company.'];

        $payload = $args['data'] ?? null;
        if (!is_array($payload) || $payload === []) return ['error' => 'Create details are missing. Ask the user for the required fields first.'];
        unset($payload['company_id'], $payload['created_by'], $payload['current_stock'], $payload['stock_value']);
        if ($workflowKey === 'payment_in') $payload['payment_type'] = 'payment_in';
        if ($workflowKey === 'payment_out') $payload['payment_type'] = 'payment_out';

        $draftId = (string) Str::uuid();
        $draft = [
            'id' => $draftId, 'user_id' => $user->id, 'company_id' => $user->current_company_id,
            'workflow' => $workflowKey, 'label' => $action['label'],
            'summary' => mb_substr(trim((string) ($args['summary'] ?? $action['label'])), 0, 1200),
            'payload' => $payload, 'created_at' => now()->toDateTimeString(),
        ];
        Cache::put($this->draftCacheKey($user, $draftId), $draft, now()->addMinutes(20));

        return [
            'draft_id' => $draftId, 'label' => $action['label'], 'summary' => $draft['summary'], 'data' => $payload,
            'status' => 'awaiting_user_confirmation',
            'message' => 'Draft prepared but not saved. Show the summary and tell the user to press Confirm & Save. Do not claim completion.',
        ];
    }

    public function confirmDraft(User $user, string $draftId): array
    {
        if (!Str::isUuid($draftId)) return ['success' => false, 'message' => 'Draft ID invalid hai. Naya draft banayein.'];
        $draft = Cache::pull($this->draftCacheKey($user, $draftId));
        if (!$draft || (int) ($draft['user_id'] ?? 0) !== (int) $user->id || (int) ($draft['company_id'] ?? 0) !== (int) $user->current_company_id) {
            return ['success' => false, 'message' => 'Draft expire ho gaya ya available nahi hai. Details dobara share karke naya draft banayein.'];
        }
        $key = (string) ($draft['workflow'] ?? '');
        $action = self::CREATE_ACTIONS[$key] ?? null;
        if (!$action || !$this->workflowAuthorized($user, $key)) return ['success' => false, 'message' => 'Is action ki permission available nahi hai.'];
        if (in_array($key, ['crm', 'buyer'], true) && !$user->currentCompany?->has_crm_access) return ['success' => false, 'message' => 'Is company ke liye CRM access band hai.'];

        $originalRequest = app('request');
        $session = $originalRequest->hasSession() ? $originalRequest->session() : null;
        $oldError = $session?->get('error');
        $oldSuccess = $session?->get('success');
        $subRequest = Request::create($action['path'], 'POST', $draft['payload'], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_REFERER' => route('admin.ai.index'),
            'REMOTE_ADDR' => $originalRequest->ip() ?: '127.0.0.1',
            'HTTP_USER_AGENT' => $originalRequest->userAgent() ?: 'AI Assistant',
        ]);
        $subRequest->setUserResolver(fn () => $user);
        if ($session) $subRequest->setLaravelSession($session);

        try {
            $route = app('router')->getRoutes()->match($subRequest);
            $route->bind($subRequest);
            $subRequest->setRouteResolver(fn () => $route);
            app()->instance('request', $subRequest);
            $controller = app($action['controller']);
            $response = app()->call([$controller, 'store'], ['request' => $subRequest]);
            $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;
            $error = $session?->get('error');
            if ($status >= 400 || ($error && $error !== $oldError)) {
                return ['success' => false, 'message' => 'Save nahi hua: '.($error ?: 'Application ne request reject ki. Details dobara check karein.')];
            }
            $success = $session?->get('success');
            if ($status !== 201 && ($status < 300 || $status >= 400)) return ['success' => false, 'message' => 'Application ne save confirm nahi kiya. Records check karke phir try karein.'];
            if ($success === $oldSuccess) $success = null;
            $jsonResult = $status === 201 && method_exists($response, 'getContent') ? (json_decode($response->getContent(), true) ?: []) : [];
            $recordName = $jsonResult['display_name'] ?? null;
            return ['success' => true, 'message' => 'Kaam complete ho gaya. '.$action['label'].' application ke existing save, validation, stock aur ledger rules se save hua.'.($recordName ? ' Record: '.$recordName.'.' : '').($success ? ' '.$success : '')];
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->unique()->implode(' ');
            return ['success' => false, 'message' => 'Save nahi hua; record create nahi kiya gaya. In details ko correct karein: '.$messages];
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            return ['success' => false, 'message' => 'Save nahi hua: '.($e->getMessage() ?: 'Application ne request reject ki.')];
        } catch (\Throwable $e) {
            report($e);
            return ['success' => false, 'message' => 'Application save complete nahi kar saki. Pehle records check karein; same request ko dobara submit karne se pehle status verify karein.'];
        } finally {
            app()->instance('request', $originalRequest);
        }
    }

    private function workflowAuthorized(User $user, string $workflowKey): bool
    {
        $workflow = self::WORKFLOWS[$workflowKey] ?? null;
        if (!$workflow) return false;
        return $user->isSuperAdmin() || ($user->hasPermission($workflow['view']) && $user->hasPermission($workflow['permission']));
    }

    private function draftCacheKey(User $user, string $draftId): string
    {
        return 'ai-create-draft:'.$user->id.':'.$draftId;
    }

    private function workflow(User $user, array $args): array
    {
        $workflow = self::WORKFLOWS[$args['workflow'] ?? ''] ?? null;
        if (!$workflow) return ['error' => 'Unknown workflow.'];
        if (in_array($args['workflow'], ['crm', 'crm_revert', 'buyer'], true) && !$user->currentCompany?->has_crm_access) return ['error' => 'CRM access is disabled for this company.'];
        if (!$user->isSuperAdmin() && (!$user->hasPermission($workflow['view']) || !$user->hasPermission($workflow['permission']))) return ['error' => 'You do not have permission for '.$workflow['label'].'.'];
        $params = match ($args['workflow']) {
            'payment_in' => ['type' => 'payment_in'],
            'payment_out' => ['type' => 'payment_out'],
            default => [],
        };
        return ['label' => $workflow['label'], 'url' => route($workflow['route'], $params, false), 'message' => 'Form opened. Review and submit it yourself; no record has been saved yet.'];
    }

    private function canReadTable(User $user, string $table): bool
    {
        return !in_array($table, ['buyers', 'production_batches'], true) || (bool) $user->currentCompany?->has_crm_access;
    }

    private function outputText(array $response): string
    {
        return collect($response['output'] ?? [])->where('type', 'message')->flatMap(fn ($item) => $item['content'] ?? [])->where('type', 'output_text')->pluck('text')->implode("\n");
    }
}
