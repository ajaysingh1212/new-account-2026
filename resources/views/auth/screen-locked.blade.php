<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Screen Locked</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body{min-height:100vh;margin:0;display:grid;place-items:center;background:#0f172a;color:#fff;font-family:Arial,sans-serif;overflow:hidden}.lock-wave{position:fixed;inset:auto -20% -30% -20%;height:46vh;background:linear-gradient(90deg,#22d3ee,#6366f1,#22c55e);opacity:.5;border-radius:50%;animation:float 5s ease-in-out infinite}.box{position:relative;z-index:2;text-align:center;width:min(420px,92vw)}.avatar{width:84px;height:84px;border-radius:24px;background:rgba(255,255,255,.14);display:grid;place-items:center;margin:0 auto 18px;font-size:34px;font-weight:800}.pin{letter-spacing:10px;text-align:center;font-size:24px}.error{color:#fecaca;min-height:24px}@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-24px)}}
    </style>
</head>
<body>
<div class="lock-wave"></div>
<form class="box" method="POST" action="{{ route('screen-lock.unlock') }}" id="unlockForm">
    @csrf
    <div class="avatar">{{ substr($user->name, 0, 1) }}</div>
    <h2>{{ $user->name }}</h2>
    <p class="text-light">Enter your 6 digit PIN to continue.</p>
    <input class="form-control pin" name="pin" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autofocus required>
    <div class="error mt-3" id="pinError"></div>
    <button class="btn btn-light btn-block mt-2">Unlock</button>
</form>
<script>
document.getElementById('unlockForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const res = await fetch(this.action, {method:'POST', body:new FormData(this), headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content, 'Accept':'application/json'}});
    if(res.ok){ location.href = "{{ route('admin.dashboard') }}"; return; }
    document.getElementById('pinError').textContent = 'Invalid PIN.';
});
</script>
</body>
</html>
