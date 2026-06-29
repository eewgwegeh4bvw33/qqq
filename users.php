<?php
date_default_timezone_set('Asia/Tehran');
header('Cache-Control: no-store, no-cache, must-revalidate');

$ghostSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><path d="M32 8c-10.5 0-19 8.5-19 19v21c0 2.4 2.8 3.7 4.6 2.1l4.4-3.8 4.4 3.8c1.2 1 2.9 1 4.1 0l4-3.4 4 3.4c1.2 1 2.9 1 4.1 0l4.4-3.8 4.4 3.8c1.8 1.6 4.6.3 4.6-2.1V27C51 16.5 42.5 8 32 8Z" stroke="currentColor" stroke-width="3.2" fill="rgba(255,255,255,0.08)"/><circle cx="25" cy="28" r="3.2" fill="currentColor"/><circle cx="39" cy="28" r="3.2" fill="currentColor"/><path d="M24 39c2.3 1.8 5 2.7 8 2.7s5.7-.9 8-2.7" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/></svg>';
$favicon = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($ghostSvg);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function render_ghost_icon($class = 'h-10 w-10') {
    echo '<svg class="' . h($class) . '" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M32 8c-10.5 0-19 8.5-19 19v21c0 2.4 2.8 3.7 4.6 2.1l4.4-3.8 4.4 3.8c1.2 1 2.9 1 4.1 0l4-3.4 4 3.4c1.2 1 2.9 1 4.1 0l4.4-3.8 4.4 3.8c1.8 1.6 4.6.3 4.6-2.1V27C51 16.5 42.5 8 32 8Z" stroke="currentColor" stroke-width="3.2" fill="rgba(255,255,255,0.08)"/><circle cx="25" cy="28" r="3.2" fill="currentColor"/><circle cx="39" cy="28" r="3.2" fill="currentColor"/><path d="M24 39c2.3 1.8 5 2.7 8 2.7s5.7-.9 8-2.7" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/></svg>';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#04060f">
    <title>پنل کاربران - Neon Timer Lab</title>
    <link rel="icon" href="<?php echo h($favicon); ?>">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: dark; --cyan:#22d3ee; --violet:#a855f7; --green:#34d399; --gold:#fbbf24; --danger:#fb7185; --text:#e6ebff; --muted:#93a0c4; --line:rgba(255,255,255,.09); }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { min-height: 100%; }
        body { font-family: "Vazirmatn", system-ui, sans-serif; color: var(--text);
            background:
                radial-gradient(circle at 82% -8%, rgba(34,211,238,.16), transparent 28%),
                radial-gradient(circle at 8% 18%, rgba(168,85,247,.16), transparent 26%),
                radial-gradient(circle at 50% 112%, rgba(52,211,153,.12), transparent 30%),
                linear-gradient(180deg, #04060f 0%, #070b1c 55%, #03050d 100%);
            background-attachment: fixed; overflow-x: hidden; }
        #bg-canvas { position: fixed; inset: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
        .bg-grid { position: fixed; inset: 0; z-index: 1; pointer-events: none;
            background-image: linear-gradient(rgba(120,180,255,.06) 1px, transparent 1px), linear-gradient(90deg, rgba(120,180,255,.06) 1px, transparent 1px);
            background-size: 64px 64px; opacity:.5;
            mask-image: radial-gradient(circle at 50% 26%, #000 0%, transparent 80%);
            -webkit-mask-image: radial-gradient(circle at 50% 26%, #000 0%, transparent 80%);
            animation: gridMove 22s linear infinite; }
        .orb { position: fixed; border-radius: 9999px; filter: blur(50px); opacity:.5; mix-blend-mode: screen; z-index: 1; pointer-events: none; }
        .orb-1 { top: -9rem; right: -7rem; width: 22rem; height: 22rem; background: radial-gradient(circle, rgba(34,211,238,.4), transparent 70%); animation: driftA 16s ease-in-out infinite; }
        .orb-2 { left: -9rem; top: 14%; width: 20rem; height: 20rem; background: radial-gradient(circle, rgba(168,85,247,.36), transparent 70%); animation: driftB 18s ease-in-out infinite; }
        .orb-3 { right: 14%; bottom: -12rem; width: 26rem; height: 26rem; background: radial-gradient(circle, rgba(52,211,153,.22), transparent 72%); animation: driftC 20s ease-in-out infinite; }

        .ghost-label { letter-spacing: .4em; font-weight: 800; color: var(--cyan); text-shadow: 0 0 16px rgba(34,211,238,.5); }
        .glow-title { text-shadow: 0 0 24px rgba(34,211,238,.4); }

        .panel { position: relative; overflow: hidden; background: rgba(10,14,31,.74); backdrop-filter: blur(20px);
            border: 1px solid var(--line); border-radius: 1.5rem;
            box-shadow: 0 0 0 1px rgba(255,255,255,.02), 0 24px 70px rgba(0,0,0,.55), inset 0 1px 0 rgba(255,255,255,.05); }
        .panel::before { content:''; position:absolute; inset:0; border-radius: inherit; padding:1px;
            background: linear-gradient(135deg, rgba(34,211,238,.5), transparent 40%, transparent 60%, rgba(168,85,247,.42));
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none; }
        .panel > * { position: relative; }

        .field { width: 100%; border-radius: .9rem; background: rgba(255,255,255,.05); border: 1px solid var(--line); padding: .9rem 1rem; color: #fff; outline: none; transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; font-size: 1rem; }
        .field::placeholder { color: #5d6b8f; }
        .field:focus { border-color: var(--cyan); background: rgba(255,255,255,.07); box-shadow: 0 0 0 3px rgba(34,211,238,.16), 0 0 22px rgba(34,211,238,.14); }
        input[type="text"].code-field { letter-spacing: .6em; text-align: center; font-weight: 800; font-size: 1.4rem; }

        .btn { display:inline-flex; align-items:center; justify-content:center; gap:.45rem; border-radius:1rem; padding:.95rem 1.15rem; font-weight:800; width:100%; transition: transform .18s ease, filter .18s ease, opacity .18s ease; user-select:none; font-size:1.02rem; }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.08); }
        .btn:active { transform: translateY(0) scale(.99); }
        .btn:disabled { opacity:.45; cursor:not-allowed; transform:none; filter:none; }
        .btn-primary { background: linear-gradient(135deg, #22d3ee, #34d399); color: #04121a; box-shadow: 0 12px 32px rgba(34,211,238,.3); }
        .btn-secondary { background: linear-gradient(135deg, #a855f7, #6366f1); color:#fff; box-shadow: 0 12px 32px rgba(168,85,247,.3); }
        .btn-gold { background: linear-gradient(135deg, #fbbf24, #f59e0b); color:#2a1a02; box-shadow: 0 12px 32px rgba(251,191,36,.34); }
        .btn-ghost { background: rgba(255,255,255,.06); color:#cbd5f5; border:1px solid var(--line); }

        .nav-tab { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; border-radius:1rem; padding:.8rem .4rem; font-size:.82rem; font-weight:800; color: rgba(226,232,240,.8); border:1px solid rgba(255,255,255,.08); background: rgba(255,255,255,.04); transition: transform .18s ease, background .18s ease, border-color .18s ease, color .18s ease, box-shadow .18s ease; text-align:center; }
        .nav-tab:hover { transform: translateY(-2px); }
        .nav-tab.active { color: #06131a; border-color: rgba(34,211,238,.6); background: linear-gradient(135deg, rgba(34,211,238,.95), rgba(52,211,153,.92)); box-shadow: 0 10px 26px rgba(34,211,238,.3); }

        .tab-panel { animation: fadeSlide .26s ease both; }

        .status-active { display:flex; align-items:center; justify-content:center; gap:.5rem; padding:.85rem; border-radius:1rem; background: linear-gradient(135deg, rgba(52,211,153,.18), rgba(34,211,238,.1)); border:1px solid rgba(52,211,153,.4); color:#86f0c4; font-weight:800; }
        .status-inactive { display:flex; align-items:center; justify-content:center; gap:.5rem; padding:.85rem; border-radius:1rem; background: rgba(244,63,94,.12); border:1px solid rgba(244,63,94,.4); color:#fda4af; font-weight:800; }
        .status-dot { width:.7rem; height:.7rem; border-radius:9999px; background: currentColor; box-shadow:0 0 12px currentColor; }
        .status-active .status-dot { animation: pulseChip 1.5s ease-in-out infinite; }

        .info-grid { display:grid; grid-template-columns: repeat(2, 1fr); gap:.6rem; }
        .info-cell { background: rgba(255,255,255,.04); border:1px solid var(--line); border-radius:.9rem; padding:.75rem .8rem; }
        .info-cell span { display:block; font-size:.72rem; color: var(--muted); margin-bottom:.25rem; }
        .info-cell b { font-size:1rem; font-weight:800; word-break: break-word; }

        .card-box { border-radius:1.2rem; border:1px solid rgba(251,191,36,.4); background: linear-gradient(135deg, rgba(251,191,36,.12), rgba(168,85,247,.08)); padding:1.1rem; box-shadow:0 0 30px rgba(251,191,36,.12); }
        .card-num { font-size:1.5rem; font-weight:900; letter-spacing:.08em; direction:ltr; text-align:center; background: linear-gradient(135deg,#fbbf24,#fde68a); -webkit-background-clip:text; background-clip:text; color:transparent; }

        .upload-zone { border:2px dashed var(--line); border-radius:1.2rem; padding:1.6rem 1rem; text-align:center; transition: border-color .2s ease, background .2s ease; cursor:pointer; }
        .upload-zone:hover { border-color: var(--cyan); background: rgba(34,211,238,.05); }
        .preview-wrap { margin-top:.8rem; border-radius:1rem; overflow:hidden; border:1px solid var(--line); }
        .preview-wrap img { width:100%; display:block; }

        .step-pill { display:inline-flex; align-items:center; gap:.4rem; font-size:.78rem; font-weight:800; padding:.35rem .7rem; border-radius:9999px; background: rgba(34,211,238,.12); color:#7dd3fc; border:1px solid rgba(34,211,238,.25); }

        .alert { border-radius:1rem; padding:.85rem 1rem; font-size:.92rem; }
        .alert-error { background: rgba(244,63,94,.12); border:1px solid rgba(244,63,94,.35); color:#fda4af; }
        .alert-success { background: rgba(52,211,153,.12); border:1px solid rgba(52,211,153,.35); color:#86f0c4; }
        .alert-info { background: rgba(34,211,238,.1); border:1px solid rgba(34,211,238,.3); color:#7dd3fc; }

        /* کنسول‌ها */
        .console-card { border-radius:1.3rem; padding:1.3rem; text-align:center; border:1px solid var(--line); background: rgba(10,14,31,.6); position:relative; overflow:hidden; }
        .console-card.full { border-color: rgba(52,211,153,.45); background: linear-gradient(135deg, rgba(52,211,153,.14), rgba(10,14,31,.6)); box-shadow: 0 0 30px rgba(52,211,153,.14); }
        .console-card.empty { border-color: rgba(244,63,94,.35); background: linear-gradient(135deg, rgba(244,63,94,.1), rgba(10,14,31,.6)); }
        .console-icon { font-size:2.2rem; }
        .console-name { margin-top:.5rem; font-weight:800; font-size:1.05rem; }
        .console-state { margin-top:.4rem; font-weight:900; font-size:1.1rem; }
        .console-state.full { color:#86f0c4; }
        .console-state.empty { color:#fda4af; }

        /* تعرفه و خوبی‌ها */
        .price-row { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.95rem 1.05rem; border-radius:1rem; border:1px solid var(--line); background: rgba(255,255,255,.035); }
        .price-row .pr-name { font-weight:700; font-size:.95rem; }
        .price-row .pr-value { font-weight:900; color:#fbbf24; white-space:nowrap; }
        .feature-item { display:flex; gap:.7rem; align-items:flex-start; padding:.9rem 1rem; border-radius:1rem; border:1px solid var(--line); background: rgba(255,255,255,.035); }
        .feature-emoji { font-size:1.3rem; line-height:1.4; }
        .feature-text { font-size:.92rem; line-height:1.7; }

        .spin { display:inline-block; width:1.1rem; height:1.1rem; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:9999px; animation: spin .7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes pulseChip { 0%,100%{transform:scale(1)} 50%{transform:scale(1.15)} }
        @keyframes gridMove { from{transform:translateY(0)} to{transform:translateY(64px)} }
        @keyframes driftA { 0%,100%{transform:translate3d(0,0,0) scale(1)} 50%{transform:translate3d(-24px,18px,0) scale(1.08)} }
        @keyframes driftB { 0%,100%{transform:translate3d(0,0,0) scale(1)} 50%{transform:translate3d(18px,-20px,0) scale(.94)} }
        @keyframes driftC { 0%,100%{transform:translate3d(0,0,0) scale(1)} 50%{transform:translate3d(-18px,-14px,0) scale(1.06)} }
        @keyframes fadeSlide { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .confetti-layer { position: fixed; inset:0; z-index:95; pointer-events:none; overflow:hidden; }
        .confetti-piece { position:absolute; top:-14px; width:9px; height:14px; border-radius:2px; animation: confettiFall linear forwards; }
        @keyframes confettiFall { to { transform: translateY(108vh) rotate(760deg); opacity:.95; } }
    </style>
</head>
<body class="text-white">
    <canvas id="bg-canvas"></canvas>
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <main class="relative z-10 mx-auto max-w-md px-4 py-6 sm:py-10">
        <header class="panel rounded-[1.6rem] px-5 py-5">
            <div class="flex items-center gap-4">
                <?php render_ghost_icon('h-12 w-12 text-cyan-200'); ?>
                <div>
                    <div class="ghost-label text-[0.7rem]">NEON TIMER LAB</div>
                    <div class="mt-1 text-base font-bold">پورتال کاربران</div>
                </div>
            </div>
        </header>

        <nav class="mt-4 grid grid-cols-3 sm:grid-cols-5 gap-2">
            <button class="nav-tab active" data-tab="login" type="button">🔑 ورود اشتراک</button>
            <button class="nav-tab" data-tab="recharge" type="button">⚡ شارژ اشتراک</button>
            <button class="nav-tab" data-tab="consoles" type="button">🎮 وضعیت کنسول‌ها</button>
            <button class="nav-tab" data-tab="pricing" type="button">💵 تعرفه‌ها</button>
            <button class="nav-tab" data-tab="benefits" type="button">🎁 خوبی‌ها</button>
        </nav>

        <div class="mt-4">
            <!-- ===== تب ورود اشتراک ===== -->
            <section id="panel-login" class="tab-panel">
                <div class="panel rounded-[1.6rem] p-5 sm:p-6">
                    <h2 class="text-xl font-black glow-title">🔑 ورود به اشتراک</h2>
                    <p class="mt-1 text-sm text-slate-400">شماره اشتراک خود را وارد کن تا اطلاعات و وضعیت فعال بودن نمایش داده شود.</p>

                    <div class="mt-5 grid gap-3">
                        <input class="field text-center tracking-widest" id="login-sub-id" inputmode="text" dir="ltr" placeholder="شماره اشتراک">
                        <button class="btn btn-primary" id="login-btn" type="button">ورود</button>
                        <div id="login-error" class="hidden"></div>
                    </div>

                    <!-- بخش کد تایید دو مرحله‌ای (در صورت نیاز) -->
                    <div id="login-2fa" class="mt-5 hidden">
                        <div class="alert alert-info">🔐 برای این اشتراک تایید دو مرحله‌ای فعال است. کد ۶ رقمی گوگل احراز هویت را وارد کن.</div>
                        <div class="mt-3 grid gap-3">
                            <input class="field code-field" id="login-2fa-code" inputmode="numeric" dir="ltr" maxlength="6" placeholder="------">
                            <button class="btn btn-primary" id="login-2fa-btn" type="button">تایید و ورود</button>
                            <button class="btn btn-ghost" id="login-2fa-back" type="button">بازگشت</button>
                            <div id="login-2fa-error" class="hidden"></div>
                        </div>
                    </div>

                    <div id="login-result" class="mt-5 hidden"></div>
                </div>
            </section>

            <!-- ===== تب شارژ اشتراک ===== -->
            <section id="panel-recharge" class="tab-panel hidden">
                <div class="panel rounded-[1.6rem] p-5 sm:p-6">
                    <h2 class="text-xl font-black glow-title">⚡ شارژ اشتراک</h2>
                    <p class="mt-1 text-sm text-slate-400">شماره اشتراک و مبلغ را وارد کن، سپس رسید پرداخت را ارسال کن.</p>

                    <div id="rc-error" class="hidden"></div>

                    <!-- مرحله ۱: اطلاعات -->
                    <div class="mt-5 grid gap-3" id="rc-step1">
                        <label class="grid gap-2">
                            <span class="text-sm font-medium text-slate-300">شماره اشتراک</span>
                            <input class="field text-center tracking-widest" id="rc-sub-id" inputmode="text" dir="ltr" placeholder="شماره اشتراک">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-sm font-medium text-slate-300">مبلغ به تومان</span>
                            <input class="field text-center" id="rc-amount" inputmode="numeric" dir="ltr" placeholder="مثلا ۱۰۰۰۰۰">
                        </label>
                        <button class="btn btn-primary" id="rc-pay-btn" type="button">💳 پرداخت</button>
                    </div>

                    <!-- مرحله ۲: اطلاعات کارت -->
                    <div class="mt-5 hidden" id="rc-step2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="step-pill">مرحله ۲ از ۴</span>
                            <button class="text-xs text-slate-400 underline" id="rc-back-btn" type="button">تغییر مبلغ</button>
                        </div>
                        <div class="card-box mt-3">
                            <div class="text-xs text-amber-200/80 text-center mb-2">شماره کارت</div>
                            <div class="card-num" id="rc-card-num">—</div>
                            <div class="mt-3 text-center text-sm font-bold text-amber-100" id="rc-card-name">—</div>
                            <button class="btn btn-ghost btn-copy mt-3" data-copy="rc-card-num" type="button">📋 کپی شماره کارت</button>
                            <div class="mt-3 text-xs leading-6 text-slate-300/90 text-center" id="rc-card-desc">—</div>
                        </div>
                        <button class="btn btn-gold mt-4" id="rc-paid-btn" type="button">✅ پرداخت کردم</button>
                    </div>

                    <!-- مرحله ۳: ارسال رسید -->
                    <div class="mt-5 hidden" id="rc-step3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="step-pill">مرحله ۳ از ۴</span>
                            <span class="text-xs text-slate-400">مبلغ: <b id="rc-confirm-amount" class="text-amber-300">—</b></span>
                        </div>
                        <label class="upload-zone mt-3" for="rc-file">
                            <div class="text-3xl">🖼️</div>
                            <div class="mt-2 font-bold">انتخاب عکس رسید</div>
                            <div class="mt-1 text-xs text-slate-400">از گالری گوشی عکس رسید را انتخاب کن</div>
                            <input type="file" id="rc-file" accept="image/*" class="hidden">
                        </label>
                        <div class="preview-wrap hidden" id="rc-preview"><img alt="پیش‌نمایش رسید"></div>
                        <button class="btn btn-primary mt-4" id="rc-send-btn" type="button" disabled>📨 ارسال رسید</button>
                    </div>

                    <!-- مرحله ۴: موفقیت -->
                    <div class="mt-5 hidden" id="rc-step4">
                        <div class="alert alert-success text-center" style="padding:1.4rem">
                            <div class="text-4xl">🎉</div>
                            <div class="mt-2 text-lg font-black">سفارش شما ثبت شد!</div>
                            <div class="mt-2 text-sm leading-7">رسید شما ارسال شد. نهایتاً تا <b>۱۰ دقیقه</b> دیگر اشتراک شما شارژ خواهد شد.</div>
                        </div>
                        <button class="btn btn-secondary mt-4" id="rc-restart-btn" type="button">شارژ جدید</button>
                    </div>
                </div>
            </section>

            <!-- ===== تب وضعیت کنسول‌ها ===== -->
            <section id="panel-consoles" class="tab-panel hidden">
                <div class="panel rounded-[1.6rem] p-5 sm:p-6">
                    <h2 class="text-xl font-black glow-title">🎮 وضعیت کنسول‌ها</h2>
                    <p class="mt-1 text-sm text-slate-400">وضعیت لحظه‌ای کنسول‌های گیم‌نت. در صورت شروع یا توقف، بلافاصله به‌روز می‌شود.</p>
                    <div class="mt-5 grid gap-3" id="consoles-list">
                        <div class="text-center text-slate-400 py-6">در حال بارگذاری وضعیت کنسول‌ها…</div>
                    </div>
                </div>
            </section>

            <!-- ===== تب تعرفه‌ها ===== -->
            <section id="panel-pricing" class="tab-panel hidden">
                <div class="panel rounded-[1.6rem] p-5 sm:p-6">
                    <h2 class="text-xl font-black glow-title">💵 تعرفه‌ها</h2>
                    <p class="mt-1 text-sm text-slate-400">قیمت بازی بر اساس مدت و تعداد دسته.</p>
                    <div class="mt-5 grid gap-3">
                        <div class="price-row"><span class="pr-name">نیم ساعت تک یا دو دسته</span><span class="pr-value">۴۰٬۰۰۰ تومان</span></div>
                        <div class="price-row"><span class="pr-name">یک ساعت تک دسته</span><span class="pr-value">۷۰٬۰۰۰ تومان</span></div>
                        <div class="price-row"><span class="pr-name">یک ساعت دو دسته</span><span class="pr-value">۷۵٬۰۰۰ تومان</span></div>
                        <div class="price-row"><span class="pr-name">هر دسته اضافه</span><span class="pr-value">۲۰٬۰۰۰ تومان</span></div>
                        <div class="price-row"><span class="pr-name">یک ساعت مرتال کمبَد یا بی‌عدالتی</span><span class="pr-value">۱۲۰٬۰۰۰ تومان</span></div>
                    </div>
                    <div class="alert alert-info mt-4">
                        <b>علت گرون‌تر بودن مرتال کمبَد و بی‌عدالتی:</b><br>
                        درگیر بودن تمام دکمه‌های دسته و وارد شدن فشار و آسیب زیاد به دسته باعث این تفاوت قیمت شده است.
                    </div>
                </div>
            </section>

            <!-- ===== تب خوبی‌های اشتراک داشتن ===== -->
            <section id="panel-benefits" class="tab-panel hidden">
                <div class="panel rounded-[1.6rem] p-5 sm:p-6">
                    <h2 class="text-xl font-black glow-title">🎁 خوبی‌های اشتراک داشتن</h2>
                    <p class="mt-1 text-sm text-slate-400">با اشتراک Neon Timer Lab از این مزایا بهره‌مند شو.</p>
                    <div class="mt-5 grid gap-3">
                        <div class="feature-item"><span class="feature-emoji">👥</span><span class="feature-text">قابلیت دعوت دوستان تا بی‌نهایت</span></div>
                        <div class="feature-item"><span class="feature-emoji">💰</span><span class="feature-text">هر ۱۰۰ تومان شارژ دوستان شما باعث می‌شود ۱۵ تومان رایگان دریافت کنید و تا بی‌نهایت می‌توانید این‌طور مجانی بازی کنید.</span></div>
                        <div class="feature-item"><span class="feature-emoji">🏆</span><span class="feature-text">اگر مجموع شارژهای دوستان شما در یک ماه به ۵ میلیون تومان برسد، ۳۰۰ هزار تومان هدیه دریافت می‌کنید.</span></div>
                        <div class="feature-item"><span class="feature-emoji">🎂</span><span class="feature-text">روز تولدتان ۱ ساعت رایگان دارید.</span></div>
                        <div class="feature-item"><span class="feature-emoji">🎟️</span><span class="feature-text">هر ماه قرعه‌کشی داریم بین اشتراک‌ها.</span></div>
                        <div class="feature-item"><span class="feature-emoji">🎁</span><span class="feature-text">هر ۷۵۰ هزار تومان که اشتراک خودتان را شارژ کنید، ۷۵ هزار تومان هدیه دریافت می‌کنید.</span></div>
                    </div>
                    <div class="alert alert-success mt-4 text-center font-bold">پس همین الان با مراجعه به گیم‌نت رخش، اشتراک خودت را ایجاد کن! 🚀</div>
                </div>
            </section>
        </div>

        <footer class="mt-6 text-center text-xs text-slate-500">Neon Timer Lab © <?php echo h(date('Y')); ?> — پورتال کاربران</footer>
    </main>

    <script>
    const API = 'index.php?api=';
    const nf = new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 0 });
    const refs = {};
    const app = { activeTab: 'login', lastId: '', consolesTimer: null };

    function moneyText(v) { return nf.format(Math.max(0, Math.round(Number(v) || 0))) + ' تومان'; }
    function toFa(n) { return nf.format(Number(n) || 0); }
    function escapeHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }
    function parseFaNum(v) {
        v = String(v ?? '').replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[^0-9.\-]/g, '');
        const n = Number(v);
        return Number.isFinite(n) && n > 0 ? n : 0;
    }
    function timeAgoOrFull(ts) {
        if (!ts) return '—';
        try { return new Date(Number(ts) * 1000).toLocaleDateString('fa-IR'); } catch (e) { return '—'; }
    }

    async function api(action, body = null) {
        const opts = body ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) } : {};
        const res = await fetch(API + encodeURIComponent(action), opts);
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) {
            const err = new Error(data.error || 'request_failed');
            err.data = data;
            throw err;
        }
        return data;
    }

    function setError(elId, msg) {
        const el = document.getElementById(elId);
        if (!el) return;
        if (!msg) { el.classList.add('hidden'); el.innerHTML = ''; return; }
        el.className = 'alert alert-error';
        el.innerHTML = '⚠️ ' + escapeHtml(msg);
    }
    function showEl(id, show) { const el = document.getElementById(id); if (el) el.classList.toggle('hidden', !show); }

    function setTab(tab) {
        app.activeTab = tab;
        document.querySelectorAll('[data-tab]').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
        ['login','recharge','consoles','pricing','benefits'].forEach(t => showEl('panel-' + t, t === tab));
        if (tab === 'consoles') { fetchConsoles(); startConsoleTimer(); } else { stopConsoleTimer(); }
    }

    function fireConfetti() {
        const colors = ['#22d3ee', '#a855f7', '#fbbf24', '#34d399', '#fb7185'];
        const layer = document.createElement('div');
        layer.className = 'confetti-layer';
        for (let i = 0; i < 60; i++) {
            const c = document.createElement('span');
            c.className = 'confetti-piece';
            c.style.left = Math.random() * 100 + '%';
            c.style.background = colors[Math.floor(Math.random() * colors.length)];
            c.style.animationDelay = (Math.random() * .5) + 's';
            c.style.animationDuration = (1.6 + Math.random() * 1.4) + 's';
            layer.appendChild(c);
        }
        document.body.appendChild(layer);
        setTimeout(() => layer.remove(), 3400);
    }

    // ===== وضعیت کنسول‌ها =====
    async function fetchConsoles() {
        try {
            const data = await api('user_console_status');
            const list = data.consoles || [];
            refs.consolesList.innerHTML = list.length ? list.map((c, i) => {
                const full = !!c.running;
                return `<div class="console-card ${full ? 'full' : 'empty'}">
                    <div class="console-icon">${full ? '🎮' : '🟦'}</div>
                    <div class="console-name">${escapeHtml(c.name || ('کنسول ' + (i + 1)))}</div>
                    <div class="console-state ${full ? 'full' : 'empty'}">${full ? 'کنسول پر هست' : 'کنسول خالی هست'}</div>
                </div>`;
            }).join('') : '<div class="text-center text-slate-400 py-6">کنسولی یافت نشد.</div>';
        } catch (e) {
            refs.consolesList.innerHTML = '<div class="text-center text-slate-400 py-6">خطا در دریافت وضعیت کنسول‌ها.</div>';
        }
    }
    function startConsoleTimer() {
        stopConsoleTimer();
        app.consolesTimer = setInterval(fetchConsoles, 4000);
    }
    function stopConsoleTimer() {
        if (app.consolesTimer) { clearInterval(app.consolesTimer); app.consolesTimer = null; }
    }

    // ===== ورود اشتراک =====
    function resetLogin() {
        showEl('login-2fa', false);
        showEl('login-result', false);
        refs.result.innerHTML = '';
        setError('login-error', '');
        setError('login-2fa-error', '');
        refs.codeInput.value = '';
    }

    async function doLogin() {
        const id = (refs.loginId.value || '').trim();
        app.lastId = id;
        resetLogin();
        if (!id) { setError('login-error', 'شماره اشتراک را وارد کن.'); return; }
        refs.loginBtn.disabled = true;
        refs.loginBtn.innerHTML = '<span class="spin"></span> در حال بررسی...';
        try {
            const data = await api('user_lookup', { id });
            if (data.twofa_required) {
                showEl('login-2fa', true);
                refs.codeInput.focus();
                return;
            }
            renderLoginResult(data, !!data.twofa_enabled);
        } catch (e) {
            const map = { subscription_not_found: 'اشتراکی با این شماره پیدا نشد.' };
            setError('login-error', map[e.data?.error] || 'خطا در دریافت اطلاعات.');
        } finally {
            refs.loginBtn.disabled = false;
            refs.loginBtn.textContent = 'ورود';
        }
    }

    async function do2faLogin() {
        const id = app.lastId;
        const code = (refs.codeInput.value || '').trim();
        setError('login-2fa-error', '');
        if (code.length !== 6) { setError('login-2fa-error', 'کد ۶ رقمی را وارد کن.'); return; }
        refs.codeBtn.disabled = true;
        refs.codeBtn.innerHTML = '<span class="spin"></span> بررسی...';
        try {
            const data = await api('user_2fa_login', { id, code });
            renderLoginResult(data, true);
            showEl('login-2fa', false);
        } catch (e) {
            const map = { invalid_code: 'کد اشتباه است. دوباره تلاش کن.', twofa_not_enabled: 'تایید دو مرحله‌ای فعال نیست.' };
            setError('login-2fa-error', map[e.data?.error] || 'خطا در تایید.');
        } finally {
            refs.codeBtn.disabled = false;
            refs.codeBtn.textContent = 'تایید و ورود';
        }
    }

    function renderLoginResult(data, twofaEnabled) {
        const s = data.subscription;
        const active = !!data.active;
        const twofaBlock = twofaEnabled
            ? `<div class="alert alert-success mt-4">🔐 تایید دو مرحله‌ای برای این اشتراک <b>فعال</b> است. <button id="2fa-disable-btn" class="underline font-bold text-rose-300" type="button">غیرفعال کردن</button></div>`
            : `<button class="btn btn-secondary mt-4" id="2fa-setup-btn" type="button">🔐 تایید دو مرحله‌ای (گوگل احراز هویت)</button><div id="2fa-setup-area" class="mt-3"></div>`;
        refs.result.innerHTML = `
            <div class="${active ? 'status-active' : 'status-inactive'}">
                <span class="status-dot"></span>
                <span>${active ? 'این اشتراک در حال حاضر فعال است' : 'این اشتراک غیرفعال است'}</span>
            </div>
            <div class="info-grid mt-3">
                <div class="info-cell"><span>موجودی فعلی</span><b style="color:#7dd3fc">${moneyText(s.balance)}</b></div>
                <div class="info-cell"><span>کل شارژ شده</span><b>${moneyText(s.recharged_total)}</b></div>
                <div class="info-cell"><span>تعداد دعوت‌شده‌ها</span><b>${toFa(s.referral_count || 0)} نفر</b></div>
                <div class="info-cell"><span>کد دعوت اختصاصی</span><b dir="ltr">${escapeHtml(s.invite_code || '—')}</b></div>
                <div class="info-cell"><span>شارژ دعوت‌شده‌ها (هفته)</span><b>${moneyText(s.ref_week_total || 0)}</b></div>
                <div class="info-cell"><span>شارژ دعوت‌شده‌ها (ماه)</span><b>${moneyText(s.ref_month_total || 0)}</b></div>
                <div class="info-cell"><span>معرف</span><b>${escapeHtml(s.referred_by_id || '—')}</b></div>
                <div class="info-cell"><span>تاریخ ساخت</span><b>${timeAgoOrFull(s.created_at)}</b></div>
            </div>
            ${s.note ? `<div class="alert alert-info mt-3">📝 ${escapeHtml(s.note)}</div>` : ''}
            <button class="btn btn-gold mt-4" id="login-goto-recharge" type="button">⚡ شارژ این اشتراک</button>
            ${twofaBlock}`;
        refs.result.classList.remove('hidden');
        const go = document.getElementById('login-goto-recharge');
        if (go) go.addEventListener('click', () => { refs.rcId.value = (refs.loginId.value || '').trim(); setTab('recharge'); });
        const setupBtn = document.getElementById('2fa-setup-btn');
        if (setupBtn) setupBtn.addEventListener('click', start2faSetup);
        const disBtn = document.getElementById('2fa-disable-btn');
        if (disBtn) disBtn.addEventListener('click', start2faDisable);
    }

    // ===== راه‌اندازی تایید دو مرحله‌ای =====
    async function start2faSetup() {
        const area = document.getElementById('2fa-setup-area');
        const setupBtn = document.getElementById('2fa-setup-btn');
        if (setupBtn) setupBtn.disabled = true;
        area.innerHTML = '<div class="text-sm text-slate-400 text-center py-2"><span class="spin"></span> در حال ساخت کد…</div>';
        try {
            const data = await api('user_2fa_setup', { id: app.lastId });
            area.innerHTML = `
                <div class="alert alert-info">۱) برنامه Google Authenticator را باز کن.<br>۲) روی + بزن و «Scan QR code» را انتخاب کن و کد زیر را اسکن کن.</div>
                <div class="mt-3 flex justify-center"><img src="${escapeHtml(data.qr_url)}" alt="QR کد تایید دو مرحله‌ای" style="width:200px;height:200px;border-radius:1rem;border:1px solid var(--line)"></div>
                <div class="mt-3 text-center">
                    <div class="text-xs text-slate-400">یا کلید را دستی وارد کن:</div>
                    <div class="mt-1 font-mono font-bold tracking-widest text-amber-300" dir="ltr" id="2fa-secret">${escapeHtml(data.secret)}</div>
                    <button class="btn btn-ghost mt-2 btn-copy" data-copy="2fa-secret" type="button">📋 کپی کلید</button>
                </div>
                <div class="alert alert-info mt-4">۳) کد ۶ رقمی نمایش داده شده در برنامه را وارد کن:</div>
                <input class="field code-field mt-2" id="2fa-verify-code" inputmode="numeric" dir="ltr" maxlength="6" placeholder="------">
                <button class="btn btn-primary mt-3" id="2fa-verify-btn" type="button">✅ تایید و فعال‌سازی</button>
                <div id="2fa-verify-error" class="hidden mt-2"></div>`;
            document.getElementById('2fa-verify-btn').addEventListener('click', verify2faSetup);
            bindCopyButtons(area);
        } catch (e) {
            area.innerHTML = `<div class="alert alert-error">خطا در ساخت کد تایید دو مرحله‌ای.</div>`;
        } finally {
            if (setupBtn) setupBtn.disabled = false;
        }
    }

    async function verify2faSetup() {
        const code = (document.getElementById('2fa-verify-code').value || '').trim();
        const errEl = document.getElementById('2fa-verify-error');
        errEl.classList.add('hidden');
        if (code.length !== 6) { errEl.className = 'alert alert-error mt-2'; errEl.textContent = 'کد ۶ رقمی را وارد کن.'; errEl.classList.remove('hidden'); return; }
        const btn = document.getElementById('2fa-verify-btn');
        btn.disabled = true; btn.innerHTML = '<span class="spin"></span> بررسی...';
        try {
            await api('user_2fa_verify', { id: app.lastId, code });
            fireConfetti();
            // بازخوانی وضعیت با 2FA فعال
            const data = await api('user_2fa_login', { id: app.lastId, code });
            renderLoginResult(data, true);
        } catch (e) {
            const map = { invalid_code: 'کد اشتباه است.', setup_not_started: 'ابتدا کد را بساز.' };
            errEl.className = 'alert alert-error mt-2'; errEl.textContent = map[e.data?.error] || 'خطا در تایید.'; errEl.classList.remove('hidden');
            btn.disabled = false; btn.textContent = '✅ تایید و فعال‌سازی';
        }
    }

    async function start2faDisable() {
        const code = prompt('برای غیرفعال کردن تایید دو مرحله‌ای، کد ۶ رقمی فعلی گوگل احراز هویت را وارد کن:');
        if (!code || code.trim().length !== 6) { if (code !== null) alert('کد نامعتبر است.'); return; }
        try {
            await api('user_2fa_disable', { id: app.lastId, code: code.trim() });
            const data = await api('user_lookup', { id: app.lastId });
            renderLoginResult(data, false);
        } catch (e) {
            alert('کد اشتباه است یا خطایی رخ داد.');
        }
    }

    // ===== شارژ اشتراک =====
    let compressedReceipt = '';
    function resetRecharge(toStep1 = true) {
        compressedReceipt = '';
        if (refs.fileInput) refs.fileInput.value = '';
        refs.sendBtn.disabled = true;
        refs.previewWrap.classList.add('hidden');
        refs.previewWrap.querySelector('img').src = '';
        if (toStep1) { showEl('rc-step1', true); showEl('rc-step2', false); showEl('rc-step3', false); showEl('rc-step4', false); }
    }
    async function rcShowCard() {
        const id = (refs.rcId.value || '').trim();
        const amount = parseFaNum(refs.amount.value);
        setError('rc-error', '');
        if (!id) { setError('rc-error', 'شماره اشتراک را وارد کن.'); return; }
        if (amount <= 0) { setError('rc-error', 'مبلغ شارژ را به درستی وارد کن.'); return; }
        refs.payBtn.disabled = true; refs.payBtn.innerHTML = '<span class="spin"></span> در حال بارگذاری...';
        try {
            const card = await api('user_card_info');
            try { await api('user_lookup', { id }); }
            catch (e) { setError('rc-error', 'اشتراکی با این شماره پیدا نشد.'); return; }
            refs.cardNum.textContent = card.card_number || '—';
            refs.cardName.textContent = card.card_name || '—';
            refs.cardDesc.textContent = card.card_description || 'مبلغ را به شماره کارت واریز کرده و رسید را ارسال کنید.';
            refs.confirmAmount.textContent = moneyText(amount);
            showEl('rc-step1', false); showEl('rc-step2', true); showEl('rc-step3', false); showEl('rc-step4', false);
        } catch (e) {
            setError('rc-error', 'خطا در دریافت اطلاعات کارت.');
        } finally {
            refs.payBtn.disabled = false; refs.payBtn.textContent = '💳 پرداخت';
        }
    }
    function rcGoUpload() { showEl('rc-step1', false); showEl('rc-step2', false); showEl('rc-step3', true); showEl('rc-step4', false); }
    function compressImage(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = e => {
                const img = new Image();
                img.onload = () => {
                    let w = img.width, h = img.height; const max = 1280;
                    if (w > max) { h = Math.round(h * max / w); w = max; }
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    resolve(canvas.toDataURL('image/jpeg', 0.82));
                };
                img.onerror = reject; img.src = e.target.result;
            };
            reader.onerror = reject; reader.readAsDataURL(file);
        });
    }
    async function onFileChange() {
        const file = refs.fileInput.files && refs.fileInput.files[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) { setError('rc-error', 'فقط فایل تصویری مجاز است.'); return; }
        setError('rc-error', '');
        refs.sendBtn.disabled = true; refs.sendBtn.innerHTML = '<span class="spin"></span> آماده‌سازی...';
        try {
            compressedReceipt = await compressImage(file);
            refs.previewWrap.querySelector('img').src = compressedReceipt;
            refs.previewWrap.classList.remove('hidden');
            refs.sendBtn.disabled = false;
        } catch (e) {
            setError('rc-error', 'خواندن تصویر ناموفق بود.');
        } finally {
            refs.sendBtn.textContent = '📨 ارسال رسید';
        }
    }
    async function rcSend() {
        const id = (refs.rcId.value || '').trim();
        const amount = parseFaNum(refs.amount.value);
        setError('rc-error', '');
        if (!compressedReceipt) { setError('rc-error', 'ابتدا عکس رسید را انتخاب کن.'); return; }
        refs.sendBtn.disabled = true; refs.sendBtn.innerHTML = '<span class="spin"></span> در حال ارسال...';
        try {
            await api('user_recharge_request', { id, amount, receipt: compressedReceipt });
            showEl('rc-step1', false); showEl('rc-step2', false); showEl('rc-step3', false); showEl('rc-step4', true);
            fireConfetti();
        } catch (e) {
            const map = { subscription_not_found: 'اشتراکی با این شماره پیدا نشد.', amount_invalid: 'مبلغ نامعتبر است.', receipt_invalid: 'تصویر رسید نامعتبر است.', receipt_too_large: 'حجم تصویر زیاد است.' };
            setError('rc-error', map[e.data?.error] || 'ارسال ناموفق بود. دوباره تلاش کن.');
            refs.sendBtn.disabled = false;
        } finally {
            refs.sendBtn.textContent = '📨 ارسال رسید';
        }
    }

    function bindCopyButtons(scope) {
        (scope || document).querySelectorAll('.btn-copy').forEach(btn => {
            if (btn.dataset.bound) return; btn.dataset.bound = '1';
            btn.addEventListener('click', async () => {
                const target = document.getElementById(btn.dataset.copy);
                const text = (target?.textContent || '').replace(/\s/g, '');
                try {
                    await navigator.clipboard.writeText(text);
                    const old = btn.textContent; btn.textContent = '✅ کپی شد';
                    setTimeout(() => btn.textContent = old, 1500);
                } catch (e) { alert('مقدار: ' + text); }
            });
        });
    }

    function setupEvents() {
        document.querySelectorAll('[data-tab]').forEach(b => b.addEventListener('click', () => setTab(b.dataset.tab)));
        refs.loginBtn.addEventListener('click', doLogin);
        refs.loginId.addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });
        refs.codeBtn.addEventListener('click', do2faLogin);
        refs.codeInput.addEventListener('keydown', e => { if (e.key === 'Enter') do2faLogin(); });
        refs.codeBack.addEventListener('click', () => { showEl('login-2fa', false); refs.codeInput.value = ''; refs.loginId.focus(); });

        refs.payBtn.addEventListener('click', rcShowCard);
        refs.backBtn.addEventListener('click', () => resetRecharge(true));
        refs.paidBtn.addEventListener('click', rcGoUpload);
        refs.fileInput.addEventListener('change', onFileChange);
        refs.sendBtn.addEventListener('click', rcSend);
        refs.restartBtn.addEventListener('click', () => { resetRecharge(true); });
        bindCopyButtons();
    }

    function bindRefs() {
        refs.loginId = document.getElementById('login-sub-id');
        refs.loginBtn = document.getElementById('login-btn');
        refs.result = document.getElementById('login-result');
        refs.codeInput = document.getElementById('login-2fa-code');
        refs.codeBtn = document.getElementById('login-2fa-btn');
        refs.codeBack = document.getElementById('login-2fa-back');
        refs.consolesList = document.getElementById('consoles-list');

        refs.rcId = document.getElementById('rc-sub-id');
        refs.amount = document.getElementById('rc-amount');
        refs.payBtn = document.getElementById('rc-pay-btn');
        refs.cardNum = document.getElementById('rc-card-num');
        refs.cardName = document.getElementById('rc-card-name');
        refs.cardDesc = document.getElementById('rc-card-desc');
        refs.confirmAmount = document.getElementById('rc-confirm-amount');
        refs.backBtn = document.getElementById('rc-back-btn');
        refs.paidBtn = document.getElementById('rc-paid-btn');
        refs.fileInput = document.getElementById('rc-file');
        refs.previewWrap = document.getElementById('rc-preview');
        refs.sendBtn = document.getElementById('rc-send-btn');
        refs.restartBtn = document.getElementById('rc-restart-btn');
    }

    function setupCanvas() {
        const canvas = document.getElementById('bg-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];
        function resize() {
            const dpr = Math.max(1, window.devicePixelRatio || 1);
            canvas.width = window.innerWidth * dpr; canvas.height = window.innerHeight * dpr;
            canvas.style.width = window.innerWidth + 'px'; canvas.style.height = window.innerHeight + 'px';
            ctx.setTransform(dpr,0,0,dpr,0,0);
            particles = [];
            const total = Math.min(70, Math.max(34, Math.floor(window.innerWidth / 20)));
            for (let i = 0; i < total; i++) {
                particles.push({ x: Math.random()*window.innerWidth, y: Math.random()*window.innerHeight, vx:(Math.random()-.5)*.24, vy:(Math.random()-.5)*.24, r:Math.random()*1.7+.6, hue: Math.random()>.5?190:280, alpha: Math.random()*.5+.16 });
            }
        }
        function draw() {
            ctx.clearRect(0,0,window.innerWidth,window.innerHeight);
            particles.forEach(p => {
                p.x += p.vx; p.y += p.vy;
                if (p.x < -20) p.x = window.innerWidth + 20;
                if (p.x > window.innerWidth + 20) p.x = -20;
                if (p.y < -20) p.y = window.innerHeight + 20;
                if (p.y > window.innerHeight + 20) p.y = -20;
                const g = ctx.createRadialGradient(p.x,p.y,0,p.x,p.y,p.r*14);
                g.addColorStop(0, `hsla(${p.hue},100%,70%,${p.alpha})`);
                g.addColorStop(1, 'rgba(0,0,0,0)');
                ctx.fillStyle = g; ctx.beginPath(); ctx.arc(p.x,p.y,p.r*14,0,Math.PI*2); ctx.fill();
            });
            requestAnimationFrame(draw);
        }
        window.addEventListener('resize', resize, { passive: true });
        resize(); draw();
    }

    bindRefs();
    setupEvents();
    setupCanvas();
    setTab('login');
    </script>
</body>
</html>
