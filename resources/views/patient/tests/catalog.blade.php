@extends('layouts.main')
@section('content')
<style>
    .t-item { display:flex; gap:14px; align-items:flex-start; background:#fff; border:1px solid #dfe8ee; border-radius:14px; padding:14px 16px; cursor:pointer; height:100%; margin:0; }
    .t-item:hover { border-color:#0f6e56; }
    .t-item.on { border-color:#0f6e56; background:#f1faf7; }
    .t-item input { width:20px; height:20px; margin-top:3px; accent-color:#0f6e56; flex:none; }
    .t-price { margin-left:auto; font-weight:800; color:#0f6e56; white-space:nowrap; }
    .t-summary { position:sticky; top:16px; background:#fff; border:2px solid #0f6e56; border-radius:18px; padding:20px; }
</style>

<div class="page-card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
        <h3 class="mb-0"><i class="ti ti-test-pipe"></i> Lab tests &amp; prices</h3>
        <a href="{{ route('patient.tests.requests') }}" class="btn btn-outline-secondary"><i class="ti ti-file-text"></i> My test reports</a>
    </div>
    <p class="text-muted">Tick the tests your doctor prescribed. Your total updates as you go.</p>

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="GET" action="{{ route('patient.tests.review') }}">
        <div class="row g-4">
            <div class="col-lg-8">
                <input type="search" id="tSearch" class="form-control mb-2" placeholder="Search tests…">
                @foreach($tests as $category => $list)
                    <div class="mt-4" data-cat>
                        <h5 class="mb-2">{{ $category }}</h5>
                        <div class="row g-2">
                            @foreach($list as $t)
                                <div class="col-md-6" data-test="{{ strtolower($t->name) }}">
                                    <label class="t-item {{ in_array($t->id, $selected) ? 'on' : '' }}">
                                        <input type="checkbox" name="tests[]" value="{{ $t->id }}"
                                               data-name="{{ $t->name }}" data-price="{{ $t->price }}"
                                               {{ in_array($t->id, $selected) ? 'checked' : '' }}>
                                        <div>
                                            <div class="fw-semibold">{{ $t->name }}</div>
                                            @if($t->preparation)<small class="text-danger d-block"><i class="ti ti-info-circle"></i> {{ $t->preparation }}</small>@endif
                                            @if($t->room_no)<small class="text-muted">Room {{ $t->room_no }}</small>@endif
                                        </div>
                                        <div class="t-price">৳ {{ number_format($t->price) }}</div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="col-lg-4">
                <div class="t-summary">
                    <h5>Your selection</h5>
                    <ul id="selList" class="list-unstyled small mb-3"><li class="text-muted">No tests selected yet.</li></ul>
                    <div class="d-flex justify-content-between fs-5 fw-bold border-top pt-2">
                        <span>Total</span><span id="selTotal">৳ 0</span>
                    </div>
                    <button class="btn btn-brand w-100 mt-3" id="selBtn" disabled><i class="ti ti-file-invoice"></i> Make report</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    const boxes = [...document.querySelectorAll('input[name="tests[]"]')];
    const list = document.getElementById('selList'), total = document.getElementById('selTotal'), btn = document.getElementById('selBtn');
    const money = n => '৳ ' + Number(n).toLocaleString('en-US');
    const esc = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

    function update() {
        const on = boxes.filter(b => b.checked);
        boxes.forEach(b => b.closest('.t-item').classList.toggle('on', b.checked));
        list.innerHTML = on.length
            ? on.map(b => `<li class="d-flex justify-content-between mb-1"><span>${esc(b.dataset.name)}</span><span>${money(b.dataset.price)}</span></li>`).join('')
            : '<li class="text-muted">No tests selected yet.</li>';
        total.textContent = money(on.reduce((s, b) => s + parseFloat(b.dataset.price), 0));
        btn.disabled = on.length === 0;
    }
    boxes.forEach(b => b.addEventListener('change', update));
    update();

    document.getElementById('tSearch').addEventListener('input', e => {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('[data-test]').forEach(el => el.style.display = el.dataset.test.includes(q) ? '' : 'none');
        document.querySelectorAll('[data-cat]').forEach(cat =>
            cat.style.display = [...cat.querySelectorAll('[data-test]')].some(el => el.style.display !== 'none') ? '' : 'none');
    });
})();
</script>
@endsection