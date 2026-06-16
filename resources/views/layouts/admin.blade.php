<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — BizAccount Pro</title>

    <!-- AdminLTE & Dependencies -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Bootstrap -->
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- AdminLTE -->
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <!-- DataTables -->
    <link rel="stylesheet"
    href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">

    <!-- Select2 -->
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    <!-- SweetAlert -->
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary: #7C3AED;
            --primary-light: #8B5CF6;
            --primary-dark: #5B21B6;
            --primary-bg: #F5F0FF;
            --accent: #06B6D4;
            --sidebar-bg: #1A0A3D;
            --sidebar-text: rgba(255,255,255,0.75);
            --sidebar-hover: rgba(124,58,237,0.2);
            --sidebar-active-bg: linear-gradient(90deg, #7C3AED, #5B21B6);
        }

        body, .wrapper { font-family: 'Outfit', sans-serif !important; }

        /* ── Sidebar ───────────────────────────── */
        .main-sidebar, .main-sidebar::before { background: var(--sidebar-bg) !important; }
        .brand-link { background: rgba(0,0,0,0.3) !important; border-bottom: 1px solid rgba(255,255,255,0.05) !important; padding: 16px 20px !important; }
        .brand-text { font-family: 'Outfit', sans-serif !important; font-weight: 700 !important; font-size: 18px !important; letter-spacing: -0.3px; }
        .brand-text span { color: #8B5CF6; }

        .nav-sidebar .nav-item .nav-link {
            color: var(--sidebar-text) !important;
            border-radius: 10px !important;
            margin: 2px 8px !important;
            padding: 9px 14px !important;
            font-size: 13.5px !important;
            font-weight: 500 !important;
            transition: all 0.25s !important;
        }
        .nav-sidebar .nav-item .nav-link:hover {
            background: var(--sidebar-hover) !important;
            color: #fff !important;
        }
        .nav-sidebar .nav-item .nav-link.active {
            background: linear-gradient(90deg, #7C3AED, #5B21B6) !important;
            color: #fff !important;
            box-shadow: 0 4px 12px rgba(124,58,237,0.4) !important;
        }
        .nav-sidebar .nav-item .nav-link .nav-icon { font-size: 14px !important; }

        .nav-header {
            font-size: 10px !important;
            font-weight: 700 !important;
            letter-spacing: 1.5px !important;
            color: rgba(255,255,255,0.3) !important;
            padding: 16px 16px 6px !important;
            margin-top: 6px !important;
        }

        .nav-treeview { background: rgba(0,0,0,0.15) !important; border-radius: 8px !important; margin: 2px 8px !important; }
        .nav-treeview .nav-item .nav-link { margin: 1px 4px !important; padding: 7px 14px 7px 24px !important; font-size: 13px !important; }

        /* ── Top Navbar ─────────────────────────── */
        .main-header {
            background: #fff !important;
            border-bottom: 1px solid #F0EAF8 !important;
            box-shadow: 0 2px 16px rgba(124,58,237,0.06) !important;
        }
        .navbar-nav .nav-link { color: #555 !important; }
        .navbar-nav .nav-link:hover { color: var(--primary) !important; }

        /* ── Content ────────────────────────────── */
        .content-wrapper { background: #F8F6FF !important; }
        .content-header h1 { font-family: 'Outfit', sans-serif; font-weight: 700; color: #1A0A3D; font-size: 22px; }
        .breadcrumb { background: transparent !important; }
        .breadcrumb-item + .breadcrumb-item::before { color: #aaa; }

        /* ── Cards ───────────────────────────────── */
        .card { border: none !important; border-radius: 16px !important; box-shadow: 0 4px 20px rgba(124,58,237,0.08) !important; overflow: hidden; }
        .card-header { border-bottom: 1px solid rgba(124,58,237,0.08) !important; background: #fff !important; padding: 16px 20px !important; }
        .card-header h3, .card-title { font-family: 'Outfit', sans-serif !important; font-weight: 700 !important; color: #1A0A3D !important; font-size: 15px !important; }
        .card-body { padding: 20px !important; }

        /* ── Stat Cards ────────────────────────── */
        .stat-card { border-radius: 16px; padding: 24px; color: #fff; position: relative; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.2) !important; }
        .stat-card .stat-icon { font-size: 36px; opacity: 0.25; position: absolute; right: 16px; top: 16px; }
        .stat-card .stat-value { font-size: 32px; font-weight: 800; letter-spacing: -1px; }
        .stat-card .stat-label { font-size: 13px; opacity: 0.85; margin-top: 4px; }
        .stat-card .stat-change { font-size: 12px; margin-top: 12px; opacity: 0.9; }

        .stat-purple { background: linear-gradient(135deg, #7C3AED, #5B21B6); box-shadow: 0 8px 24px rgba(124,58,237,0.35); }
        .stat-cyan   { background: linear-gradient(135deg, #06B6D4, #0891B2); box-shadow: 0 8px 24px rgba(6,182,212,0.35); }
        .stat-pink   { background: linear-gradient(135deg, #EC4899, #DB2777); box-shadow: 0 8px 24px rgba(236,72,153,0.35); }
        .stat-green  { background: linear-gradient(135deg, #10B981, #059669); box-shadow: 0 8px 24px rgba(16,185,129,0.35); }
        .stat-orange { background: linear-gradient(135deg, #F59E0B, #D97706); box-shadow: 0 8px 24px rgba(245,158,11,0.35); }

        /* ── Buttons ────────────────────────────── */
        .btn-primary { background: linear-gradient(135deg, #7C3AED, #5B21B6) !important; border: none !important; border-radius: 10px !important; font-family: 'Outfit', sans-serif !important; font-weight: 600 !important; box-shadow: 0 4px 12px rgba(124,58,237,0.3) !important; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(124,58,237,0.45) !important; }
        .btn-danger { border-radius: 8px !important; }
        .btn-warning { border-radius: 8px !important; }
        .btn-info { border-radius: 8px !important; }
        .btn-sm { padding: 5px 12px !important; font-size: 12px !important; }

        /* ── Badges ─────────────────────────────── */
        .badge-active { background: rgba(16,185,129,0.15); color: #059669; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-inactive { background: rgba(239,68,68,0.1); color: #DC2626; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-admin { background: rgba(124,58,237,0.15); color: #7C3AED; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-super { background: linear-gradient(135deg,#7C3AED,#06B6D4); color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-user { background: rgba(107,114,128,0.1); color: #6B7280; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }

        /* ── Table ──────────────────────────────── */
        .table th { font-size: 12px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: #9090B0; border-bottom: 2px solid #F0EAF8 !important; padding: 12px 16px !important; }
        .table td { padding: 12px 16px !important; vertical-align: middle !important; font-size: 14px; border-bottom: 1px solid #F8F6FF !important; }
        .table tbody tr:hover { background: rgba(124,58,237,0.03) !important; }

        /* ── Avatar ────────────────────────────── */
        .user-avatar { width: 36px; height: 36px; border-radius: 10px; object-fit: cover; background: linear-gradient(135deg,#7C3AED,#06B6D4); display: inline-flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 14px; }

        /* ── Forms ─────────────────────────────── */
        .form-control { border-radius: 10px !important; border-color: #E5E0F5 !important; padding: 10px 14px !important; font-family: 'Outfit', sans-serif !important; font-size: 14px !important; }
        .form-control:focus { border-color: var(--primary-light) !important; box-shadow: 0 0 0 3px rgba(124,58,237,0.1) !important; }
        .form-group label { font-size: 13px; font-weight: 600; color: #5B4A7A; margin-bottom: 6px; }

        /* ── Alerts ─────────────────────────────── */
        .alert { border-radius: 12px !important; border: none !important; font-size: 14px; }
        .alert-success { background: rgba(16,185,129,0.1); color: #059669; }
        .alert-danger { background: rgba(239,68,68,0.1); color: #DC2626; }

        /* ── Scrollbar ──────────────────────────── */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(124,58,237,0.3); border-radius: 10px; }

        /* ── Page animations ─────────────────── */
        .content-wrapper { animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes wave { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-24px)} }

        /* ── Sidebar user panel ─────────────── */
        .user-panel { border-bottom: 1px solid rgba(255,255,255,0.05) !important; padding: 12px 16px !important; }
        .user-panel .info a { color: rgba(255,255,255,0.85) !important; font-size: 13px !important; font-weight: 600 !important; }
        .user-panel img { border-radius: 10px !important; width: 36px !important; height: 36px !important; object-fit: cover; }

        /* Sidebar section labels */
        .sidebar-mini-child .nav-link span { display: none; }
    </style>

    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed sidebar-collapse-lg">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="navbar-text text-muted" style="font-size:13px;">
                    @if(auth()->user()->currentCompany)
                                    <img
                                        src="{{ Storage::url(auth()->user()->currentCompany->logo) }}"
                                        alt="Company Logo"
                                        style="height:60px;width:auto;"
                                    >

                        {{ auth()->user()->currentCompany->name }}
                    @endif
                </span>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <!-- Search -->
            <li class="nav-item">
                @if(auth()->user()->screen_pin)
                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="screenLockBtn"><i class="fas fa-lock"></i> Screen Lock</button>
                @else
                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" data-toggle="modal" data-target="#pinSetupModal"><i class="fas fa-key"></i> Set PIN</button>
                @endif
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.profile.edit') }}" title="Profile">
                    @if(auth()->user()->profile_pic)
                        <img src="{{ auth()->user()->profile_pic_url }}" class="user-avatar" alt="avatar">
                    @else
                        <div class="user-avatar">{{ substr(auth()->user()->name, 0, 1) }}</div>
                    @endif
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-toggle="dropdown">
                    <span class="d-none d-md-inline" style="font-size:13px;font-weight:600;color:#1A0A3D;">
                        {{ auth()->user()->name }}
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" style="border-radius:12px;border:1px solid #F0EAF8;box-shadow:0 8px 24px rgba(124,58,237,0.12);">
                    <a href="{{ route('admin.profile.edit') }}" class="dropdown-item">
                        <i class="fas fa-user-edit me-2 text-purple"></i> My Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    @include('admin.partials.sidebar')

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">@yield('title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                            @yield('breadcrumb')
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
                @endif
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
                @endif

                @yield('content')
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer" style="background:#fff;border-top:1px solid #F0EAF8;font-size:12px;color:#9090B0;">
        <strong>BizAccount Pro</strong> &copy; {{ date('Y') }} — All rights reserved.
        <div class="float-right">v1.0.0</div>
    </footer>
</div>

<div class="modal fade" id="pinSetupModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content" id="pinSetupForm">
            <div class="modal-header"><h5 class="modal-title">Set 6 Digit PIN</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <input type="password" name="pin" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" class="form-control text-center mb-2" placeholder="PIN" required>
                <input type="password" name="pin_confirmation" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" class="form-control text-center" placeholder="Confirm PIN" required>
                <div class="text-danger small mt-2" id="pinSetupError"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary btn-block">Save PIN</button></div>
        </form>
    </div>
</div>

<div id="liveLockOverlay" style="display:none;position:fixed;inset:0;z-index:5000;background:#0f172a;color:#fff;place-items:center;text-align:center;overflow:hidden;">
    <div style="position:absolute;inset:auto -20% -30% -20%;height:45vh;background:linear-gradient(90deg,#22d3ee,#6366f1,#22c55e);opacity:.5;border-radius:50%;animation:wave 5s ease-in-out infinite"></div>
    <form id="liveUnlockForm" style="position:relative;width:min(420px,92vw);">
        <div style="width:84px;height:84px;border-radius:24px;background:rgba(255,255,255,.14);display:grid;place-items:center;margin:0 auto 18px;font-size:34px;font-weight:800;">{{ substr(auth()->user()->name, 0, 1) }}</div>
        <h2>{{ auth()->user()->name }}</h2>
        <p>Enter PIN to continue.</p>
        <input class="form-control text-center" style="letter-spacing:10px;font-size:24px" name="pin" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required>
        <div class="text-danger mt-2" id="liveUnlockError"></div>
        <button class="btn btn-light btn-block mt-3">Unlock</button>
    </form>
</div>

<!-- Scripts -->
<!-- JQUERY FIRST -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- AdminLTE -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>

<!-- Select2 -->


<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.2/sweetalert2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // Auto-dismiss alerts
    setTimeout(() => { $('.alert').fadeOut(500); }, 4000);

    // Setup AJAX CSRF
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // Delete confirmation
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#7C3AED',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            borderRadius: '16px',
        }).then(r => { if(r.isConfirmed) form.submit(); });
    });

    // Init Select2
    $('.select2').select2({ theme: 'classic' });
    $('#pinSetupForm').on('submit', async function(e) {
        e.preventDefault();
        const res = await fetch('{{ route('screen-lock.pin') }}', { method:'POST', body:new FormData(this), headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content'), 'Accept':'application/json'} });
        if (res.ok) location.reload();
        else $('#pinSetupError').text('PIN must be 6 digits and confirmation must match.');
    });
    $('#screenLockBtn').on('click', async function() {
        const res = await fetch('{{ route('screen-lock.lock') }}', { method:'POST', headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content'), 'Accept':'application/json'} });
        if (res.ok) $('#liveLockOverlay').css('display','grid').find('input[name="pin"]').focus();
    });
    $('#liveUnlockForm').on('submit', async function(e) {
        e.preventDefault();
        const res = await fetch('{{ route('screen-lock.unlock') }}', { method:'POST', body:new FormData(this), headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content'), 'Accept':'application/json'} });
        if (res.ok) { $('#liveLockOverlay').hide(); this.reset(); return; }
        $('#liveUnlockError').text('Invalid PIN.');
    });
    @if(session('screen_locked'))
        $('#liveLockOverlay').css('display','grid');
    @endif
</script>

@stack('scripts')
</body>
</html>
