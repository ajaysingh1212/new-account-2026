<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TermsTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TermsTemplateController extends Controller
{
    public function index()
    {
        $templates = TermsTemplate::where('company_id', auth()->user()->current_company_id)->latest()->get();
        return view('admin.terms.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.terms.create', ['template' => new TermsTemplate(['document_type' => 'all', 'status' => 'active'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->clearDefault($data);
        TermsTemplate::create(array_merge($data, [
            'company_id' => auth()->user()->current_company_id,
            'is_default' => !empty($data['is_default']),
            'attachment' => $request->hasFile('attachment') ? $request->file('attachment')->store('terms', 'public') : null,
            'created_by' => auth()->id(),
        ]));
        return redirect()->route('admin.terms.index')->with('success', 'Terms template saved.');
    }

    public function edit(TermsTemplate $term)
    {
        abort_unless($term->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
        return view('admin.terms.edit', ['template' => $term]);
    }

    public function update(Request $request, TermsTemplate $term)
    {
        abort_unless($term->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
        $data = $this->validated($request);
        $this->clearDefault($data, $term->id);
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('terms', 'public');
        }
        $term->update(array_merge($data, ['is_default' => !empty($data['is_default'])]));
        return redirect()->route('admin.terms.index')->with('success', 'Terms template updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required','string','max:255'],
            'document_type' => ['required', Rule::in(['all','sales','purchase'])],
            'content' => ['required','string'],
            'status' => ['required', Rule::in(['active','inactive'])],
            'is_default' => ['nullable','boolean'],
            'attachment' => ['nullable','file','max:4096'],
        ]);
    }

    private function clearDefault(array $data, ?int $ignoreId = null): void
    {
        if (empty($data['is_default'])) {
            return;
        }
        TermsTemplate::where('company_id', auth()->user()->current_company_id)
            ->whereIn('document_type', [$data['document_type'], 'all'])
            ->when($ignoreId, fn($q) => $q->where('id', '<>', $ignoreId))
            ->update(['is_default' => false]);
    }
}
