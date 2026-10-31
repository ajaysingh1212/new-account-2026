@extends('layouts.admin')

@section('title', 'AI Assistant')
@section('page_title', 'AI Assistant')

@section('content')
<style>
    .ai-shell{max-width:1100px;margin:0 auto}.ai-hero{background:linear-gradient(120deg,#201047,#4c1d95);color:#fff;border-radius:18px;padding:24px 28px;margin-bottom:18px}.ai-hero h2{font-weight:700;margin:0 0 6px}.ai-hero p{margin:0;color:#e9ddff}.ai-chat{height:min(62vh,680px);min-height:380px;overflow:auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:22px;box-shadow:0 8px 28px #1f12330c}.ai-msg{display:flex;margin:0 0 16px}.ai-msg.user{justify-content:flex-end}.ai-bubble{max-width:82%;padding:12px 15px;border-radius:15px;white-space:pre-wrap;line-height:1.55}.ai-msg.assistant .ai-bubble{background:#f3f0fa;color:#21163c;border-bottom-left-radius:4px}.ai-msg.user .ai-bubble{background:#5b21b6;color:#fff;border-bottom-right-radius:4px}.ai-bubble a{font-weight:700;color:#5b21b6;text-decoration:underline}.ai-compose{display:flex;gap:10px;margin-top:12px}.ai-compose textarea{resize:vertical;min-height:54px;max-height:150px;border-radius:12px}.ai-compose button{min-width:112px;border-radius:12px}.ai-examples{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0}.ai-example{border:1px solid #ddd6fe;background:#fff;color:#5b21b6;border-radius:999px;padding:6px 11px;font-size:13px}.ai-note{font-size:12px;color:#6b7280;margin-top:12px}.ai-draft{max-width:88%;margin:0 0 18px 0;padding:16px 18px;border:1px solid #c4b5fd;background:#fbfaff;border-radius:14px}.ai-draft h4{font-size:16px;font-weight:700;margin:0 0 6px;color:#32156d}.ai-draft p{margin:0 0 10px;white-space:pre-wrap}.ai-draft pre{max-height:180px;overflow:auto;background:#fff;border:1px solid #e9e2fa;padding:10px;border-radius:8px;font-size:12px;white-space:pre-wrap}.ai-draft .btn{border-radius:9px}
</style>
<div class="ai-shell">
    <div class="ai-hero"><h2><i class="fas fa-sparkles mr-2"></i>AI Assistant</h2><p>Hindi ya English me poochhein. Company ke records, CRM history, stock aur reports dhoondhne me madad milegi.</p></div>
    <div class="ai-chat" id="aiChat" aria-live="polite">
        <div class="ai-msg assistant"><div class="ai-bubble">Namaste! Main current company ke records dhoondh sakta hoon. Sale, purchase, item, estimate, CRM, challan ya payment banane ke liye pehle zaroori details aapse poochunga, phir review ke liye draft dikhaunga. Aap Confirm &amp; Save dabayenge tabhi application me save hoga.

Hello! I can search company records and gather details for a new record. I’ll show a draft for review; it is saved only after you confirm. Try: “Create a sale invoice.”</div></div>
    </div>
    <div class="ai-examples">
        <button class="ai-example" type="button" data-prompt="Serial 1211419935 ki CRM aur stock history batao">Serial history</button>
        <button class="ai-example" type="button" data-prompt="Is mahine ki sale report batao">Monthly sales</button>
        <button class="ai-example" type="button" data-prompt="Naya sale invoice banana hai">Create sale</button>
        <button class="ai-example" type="button" data-prompt="Naya estimate banana hai">Create estimate</button>
    </div>
    <form class="ai-compose" id="aiForm">
        @csrf
        <textarea class="form-control" id="aiPrompt" placeholder="Apna sawal ya kaam yahan likhein… / Ask a question or describe a task…" required maxlength="4000"></textarea>
        <button class="btn btn-primary" id="aiSend" type="submit"><i class="fas fa-paper-plane mr-1"></i> Send</button>
    </form>
    <div class="ai-note">AI replies may be wrong; verify financial details. For a creation, review the draft fields carefully. Confirm &amp; Save posts it through the application's normal validation, stock, and ledger rules.</div>
</div>
<script>
(() => {
    const chat = document.getElementById('aiChat');
    const form = document.getElementById('aiForm');
    const prompt = document.getElementById('aiPrompt');
    const send = document.getElementById('aiSend');
    const token = form.querySelector('input[name="_token"]').value;
    const history = [];
    const confirmUrl = @json(route('admin.ai.drafts.confirm', ['draft' => '__DRAFT__']));
    function addMessage(role, content) {
        const row = document.createElement('div'); row.className = `ai-msg ${role}`;
        const bubble = document.createElement('div'); bubble.className = 'ai-bubble';
        const linkPattern = /\[([^\]]+)\]\((\/admin\/[^\s)]+)\)/g;
        let cursor = 0; let match;
        while ((match = linkPattern.exec(content)) !== null) {
            bubble.appendChild(document.createTextNode(content.slice(cursor, match.index)));
            const link = document.createElement('a'); link.href = match[2]; link.textContent = match[1];
            bubble.appendChild(link); cursor = match.index + match[0].length;
        }
        bubble.appendChild(document.createTextNode(content.slice(cursor)));
        row.appendChild(bubble); chat.appendChild(row); chat.scrollTop = chat.scrollHeight;
    }
    function addDraft(draft) {
        const card = document.createElement('section'); card.className = 'ai-draft'; card.dataset.draftId = draft.draft_id;
        const heading = document.createElement('h4'); heading.textContent = `Review ${draft.label} before saving`;
        const summary = document.createElement('p'); summary.textContent = draft.summary || 'Review the fields below.';
        const details = document.createElement('pre'); details.textContent = JSON.stringify(draft.data || {}, null, 2);
        const button = document.createElement('button'); button.type = 'button'; button.className = 'btn btn-success'; button.textContent = 'Confirm & Save';
        button.addEventListener('click', () => confirmDraft(draft.draft_id, card, button));
        const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'btn btn-light ml-2'; cancel.textContent = 'Cancel'; cancel.addEventListener('click', () => card.remove());
        card.append(heading, summary, details, button, cancel); chat.appendChild(card); chat.scrollTop = chat.scrollHeight;
    }
    async function confirmDraft(id, card, button) {
        button.disabled = true; button.textContent = 'Saving…';
        try {
            const response = await fetch(confirmUrl.replace('__DRAFT__', encodeURIComponent(id)), {
                method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':token}
            });
            const data = await response.json();
            const message = data.message || (data.success ? 'Kaam complete ho gaya.' : 'Save nahi hua.');
            addMessage('assistant', message); history.push({role:'assistant',content:message});
            if (data.success) card.remove(); else { card.remove(); }
        } catch (e) {
            addMessage('assistant', e.message || 'Save request fail ho gayi.');
            button.disabled = false; button.textContent = 'Try Save Again';
        }
    }
    async function submit(value) {
        const text = value.trim(); if (!text || send.disabled) return;
        prompt.value = ''; addMessage('user', text); history.push({role:'user',content:text});
        send.disabled = true; send.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        try {
            const response = await fetch(@json(route('admin.ai.chat')), {
                method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token},
                body:JSON.stringify({messages:history.slice(-12)})
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Request failed.');
            const answer = data.reply || 'No response.'; history.push({role:'assistant',content:answer}); addMessage('assistant', answer);
            (data.drafts || []).forEach(addDraft);
        } catch (e) { addMessage('assistant', e.message || 'AI request failed.'); }
        finally { send.disabled = false; send.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Send'; prompt.focus(); }
    }
    form.addEventListener('submit', e => { e.preventDefault(); submit(prompt.value); });
    document.querySelectorAll('.ai-example').forEach(button => button.addEventListener('click', () => submit(button.dataset.prompt)));
    prompt.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); submit(prompt.value); } });
})();
</script>
@endsection
