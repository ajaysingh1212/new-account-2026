<style>
.pro-modal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 26px 80px rgba(15,23,42,.28)}.pro-modal .modal-header{background:linear-gradient(135deg,#101827,#0f766e);color:#fff;border:0;padding:20px 24px}.pro-modal .modal-body{background:#f8fafc}.segment-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px}.segment-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;box-shadow:0 10px 24px rgba(15,23,42,.07)}.segment-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}.segment-icon{width:40px;height:40px;border-radius:12px;background:#e0f2fe;color:#0369a1;display:flex;align-items:center;justify-content:center}.modal-table-wrap{max-height:340px;overflow:auto;border:1px solid #e2e8f0;border-radius:12px;background:#fff}
/* ============================================================
   Total Collection Modal — Professional Redesign
   Scoped under .collection-pro-modal taaki AdminLTE ke styles se
   clash na ho. Color system CSS variables mein defined hai.
   ============================================================ */

.collection-pro-modal{
  --cp-primary:#7C3AED;      /* violet */
  --cp-primary-2:#6366F1;    /* indigo */
  --cp-cyan:#06B6D4;
  --cp-emerald:#10B981;
  --cp-amber:#F59E0B;
  --cp-rose:#F43F5E;
  --cp-sky:#0EA5E9;
  --cp-ink:#0F172A;
  --cp-muted:#64748B;
  --cp-surface:#FFFFFF;
  --cp-bg:#F8FAFC;
  --cp-border:#E2E8F0;
  --cp-radius:16px;
  font-family:'Inter','Outfit',system-ui,sans-serif;
}

/* ---------- Modal shell ---------- */
.collection-pro-modal .modal-content{
  border:0;
  border-radius:20px;
  overflow:hidden;
  box-shadow:0 30px 90px rgba(76,29,149,.25);
}

.collection-pro-modal .modal-header{
  background:linear-gradient(135deg,var(--cp-primary) 0%,var(--cp-primary-2) 55%,#4338CA 100%);
  color:#fff;
  border:0;
  padding:22px 26px;
  position:relative;
  overflow:hidden;
}
.collection-pro-modal .modal-header::before{
  /* subtle decorative glow, sirf visual polish ke liye */
  content:"";
  position:absolute; inset:-40% -10% auto auto;
  width:220px; height:220px;
  background:radial-gradient(circle,rgba(255,255,255,.22),transparent 65%);
}
.collection-pro-modal .modal-title{
  font-weight:800;
  font-size:20px;
  letter-spacing:.2px;
}
.collection-pro-modal .modal-header small{
  color:rgba(255,255,255,.82);
  font-weight:500;
}
.collection-pro-modal .modal-header .close{
  color:#fff;
  opacity:.85;
  text-shadow:none;
}
.collection-pro-modal .modal-header .close:hover{ opacity:1; }

.collection-pro-modal .modal-body{
  background:var(--cp-bg);
  padding:24px;
}

/* ---------- Filters row ---------- */
.cp-filter-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(170px,1fr));
  gap:12px;
  margin-bottom:20px;
}
.cp-filter-grid select,
.cp-filter-grid input{
  background:var(--cp-surface);
  border:1.5px solid var(--cp-border);
  border-radius:10px;
  padding:10px 12px;
  font-size:13px;
  font-weight:500;
  color:var(--cp-ink);
  transition:border-color .15s, box-shadow .15s;
}
.cp-filter-grid select:focus,
.cp-filter-grid input:focus{
  border-color:var(--cp-primary);
  box-shadow:0 0 0 3px rgba(124,58,237,.12);
  outline:none;
}

/* ---------- KPI metric cards ---------- */
.cp-metric-row{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:14px;
  margin-bottom:22px;
}
.cp-metric-card{
  background:var(--cp-surface);
  border:1px solid var(--cp-border);
  border-radius:var(--cp-radius);
  padding:16px 18px;
  display:flex;
  align-items:center;
  gap:14px;
  box-shadow:0 10px 26px rgba(15,23,42,.06);
  position:relative;
  overflow:hidden;
}
.cp-metric-card::after{
  content:"";
  position:absolute; left:0; top:0; bottom:0; width:4px;
  background:var(--cp-accent,var(--cp-primary));
}
.cp-metric-card.cp-accent-violet{ --cp-accent:var(--cp-primary); }
.cp-metric-card.cp-accent-cyan{ --cp-accent:var(--cp-cyan); }
.cp-metric-card.cp-accent-emerald{ --cp-accent:var(--cp-emerald); }

.cp-metric-icon{
  width:46px; height:46px; border-radius:12px;
  display:flex; align-items:center; justify-content:center;
  font-size:18px;
  background:color-mix(in srgb, var(--cp-accent,var(--cp-primary)) 14%, white);
  color:var(--cp-accent,var(--cp-primary));
  flex-shrink:0;
}
.cp-metric-label{
  font-size:11px; font-weight:700; text-transform:uppercase;
  letter-spacing:.6px; color:var(--cp-muted); margin-bottom:2px;
}
.cp-metric-value{
  font-size:22px; font-weight:800; color:var(--cp-ink);
  line-height:1.15;
}

/* ---------- Visualization shell ---------- */
.cp-viz-shell{
  background:linear-gradient(155deg,#1E1B4B 0%,#312E81 55%,#3730A3 100%);
  border-radius:var(--cp-radius);
  padding:20px;
  color:#fff;
  box-shadow:0 24px 60px rgba(30,27,75,.35);
  margin-bottom:20px;
}
.cp-viz-top{
  display:flex; justify-content:space-between; align-items:center;
  flex-wrap:wrap; gap:12px; margin-bottom:16px;
}
.cp-viz-total-label{
  font-size:11px; font-weight:700; text-transform:uppercase;
  letter-spacing:.6px; color:#C7D2FE;
}
.cp-viz-total-value{
  font-size:26px; font-weight:800; color:#fff;
}

.cp-viz-tabs{ display:flex; gap:6px; flex-wrap:wrap; }
.cp-viz-tab{
  border:1px solid rgba(255,255,255,.18);
  background:rgba(255,255,255,.08);
  color:#E0E7FF;
  border-radius:999px;
  padding:8px 14px;
  font-size:12px;
  font-weight:700;
  transition:all .15s;
}
.cp-viz-tab i{ margin-right:5px; }
.cp-viz-tab.active,
.cp-viz-tab:hover{
  background:#fff;
  color:var(--cp-primary);
  border-color:#fff;
}

.cp-viz-pane{ display:none; min-height:340px; }
.cp-viz-pane.active{ display:block; }

/* Pie */
.cp-pie-wrap{
  display:grid;
  grid-template-columns:minmax(230px,320px) 1fr;
  gap:24px;
  align-items:center;
}
.cp-pie{
  width:min(300px,70vw); aspect-ratio:1; border-radius:50%;
  background:var(--pie-gradient, conic-gradient(#334155 0 100%));
  position:relative; margin:auto;
  box-shadow:inset 0 0 0 16px rgba(255,255,255,.06), 0 22px 60px rgba(0,0,0,.3);
  animation:cpSpin .8s cubic-bezier(.2,.9,.2,1);
}
.cp-pie::after{
  content:""; position:absolute; inset:28%; border-radius:50%;
  background:#1E1B4B; box-shadow:inset 0 0 20px rgba(255,255,255,.08);
}
.cp-pie-center{
  position:absolute; inset:34%; z-index:2;
  display:flex; align-items:center; justify-content:center;
  text-align:center; font-weight:800; font-size:20px; color:#fff;
}
.cp-pie-center span{ display:block; font-size:11px; color:#C7D2FE; font-weight:600; margin-top:2px; }

.cp-legend{ display:grid; gap:8px; }
.cp-legend-row{
  display:grid; grid-template-columns:12px 1fr auto; gap:10px; align-items:center;
  background:rgba(255,255,255,.06);
  border:1px solid rgba(255,255,255,.08);
  border-radius:10px; padding:9px 12px;
}
.cp-legend-dot{ width:11px; height:11px; border-radius:50%; background:var(--c,#7C3AED); }
.cp-legend-name{ font-size:12.5px; font-weight:600; color:#E0E7FF; }
.cp-legend-amount{ font-size:12.5px; font-weight:800; color:#fff; }
.cp-legend-meter{ grid-column:1/-1; height:6px; background:rgba(255,255,255,.1); border-radius:999px; overflow:hidden; margin-top:2px; }
.cp-legend-meter span{ display:block; height:100%; width:var(--w,0%); background:var(--c,#7C3AED); animation:cpGrow 1s ease; }

/* Bar */
.cp-bar-stage{
  display:flex; align-items:flex-end; gap:14px; height:300px;
  padding:18px; background:rgba(255,255,255,.04); border-radius:14px;
  overflow-x:auto;
}
.cp-bar-col{
  flex:1; min-width:52px; display:flex; flex-direction:column;
  justify-content:flex-end; align-items:center; gap:8px;
}
.cp-bar-fill{
  width:100%; max-width:60px; height:var(--h,10%);
  background:linear-gradient(180deg,var(--c,#7C3AED),rgba(255,255,255,.15));
  border-radius:10px 10px 4px 4px;
  box-shadow:0 10px 24px color-mix(in srgb, var(--c,#7C3AED), transparent 55%);
  animation:cpRise .9s ease;
}
.cp-bar-value{ font-size:11px; font-weight:700; color:#E0E7FF; }
.cp-bar-label{ font-size:11px; color:#A5B4FC; text-align:center; max-width:90px; }

/* Candle */
.cp-candle-stage{
  display:flex; align-items:flex-end; gap:14px; height:300px;
  padding:18px; background:rgba(255,255,255,.04); border-radius:14px;
  overflow-x:auto;
}
.cp-candle{
  flex:1; min-width:40px; display:flex; flex-direction:column;
  align-items:center; justify-content:flex-end; gap:8px;
}
.cp-candle-wick{ width:3px; height:var(--wick,20px); background:rgba(255,255,255,.35); border-radius:99px; }
.cp-candle-body{
  width:32px; height:var(--h,10%);
  background:var(--c,#7C3AED);
  border-radius:7px;
  box-shadow:0 8px 22px color-mix(in srgb, var(--c,#7C3AED), transparent 55%);
  animation:cpRise .9s ease;
}

/* Wave */
.cp-wave{ width:100%; height:320px; background:rgba(255,255,255,.04); border-radius:14px; }
.cp-wave line{ stroke:rgba(255,255,255,.12); }
.cp-wave path{
  fill:none; stroke-width:4.5; stroke-linecap:round; stroke-linejoin:round;
  stroke:var(--cp-cyan);
  stroke-dasharray:1200; stroke-dashoffset:1200;
  animation:cpDraw 1.6s ease forwards;
}
.cp-wave circle{ fill:var(--cp-cyan); }

/* Content list view */
.cp-content-row{
  display:grid; grid-template-columns:42px 1fr auto; gap:12px; align-items:center;
  background:#fff; border:1px solid var(--cp-border); border-radius:12px;
  padding:12px 14px; margin-bottom:10px; color:var(--cp-ink);
}
.cp-content-icon{
  width:40px; height:40px; border-radius:11px;
  display:flex; align-items:center; justify-content:center;
  background:color-mix(in srgb, var(--c,var(--cp-primary)) 14%, white);
  color:var(--c,var(--cp-primary));
  font-weight:700;
}
.cp-content-name{ font-weight:700; font-size:13.5px; }
.cp-content-sub{ font-size:11.5px; color:var(--cp-muted); }
.cp-content-amount{ font-weight:800; font-size:14px; color:var(--cp-ink); }

/* ---------- Data table ---------- */
.cp-table-wrap{
  max-height:340px; overflow:auto;
  border:1px solid var(--cp-border); border-radius:var(--cp-radius);
  background:var(--cp-surface);
}
.cp-table{ margin:0; }
.cp-table thead th{
  position:sticky; top:0;
  background:#F1F5F9; color:var(--cp-ink);
  font-size:11.5px; text-transform:uppercase; letter-spacing:.5px;
  font-weight:800; border-bottom:2px solid var(--cp-border);
  padding:12px 14px;
}
.cp-table tbody td{
  padding:11px 14px; font-size:13px; color:var(--cp-ink);
  border-top:1px solid var(--cp-border);
}
.cp-table tbody tr:hover{ background:#F8FAFC; }
.cp-table .text-right{ text-align:right; }

/* ---------- Animations ---------- */
@keyframes cpSpin{ from{ transform:scale(.86) rotate(-24deg); opacity:.4;} to{ transform:scale(1) rotate(0); opacity:1;} }
@keyframes cpGrow{ from{ width:0; } to{ width:var(--w,0%); } }
@keyframes cpRise{ from{ height:0; opacity:.3; } to{ height:var(--h,10%); opacity:1; } }
@keyframes cpDraw{ to{ stroke-dashoffset:0; } }

/* ---------- Responsive ---------- */
@media(max-width:768px){
  .cp-pie-wrap{ grid-template-columns:1fr; }
  .cp-viz-pane{ min-height:auto; }
}
</style>
