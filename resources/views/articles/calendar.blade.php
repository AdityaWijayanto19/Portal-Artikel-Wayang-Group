@extends('layouts.app')

@section('title', 'Kalender Jadwal · ' . $company->name)
@section('subtitle', 'Jadwal publish mendatang beserta riwayat artikel terbit & gagal pada bulan berjalan.')

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('header_actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('articles.index') }}"
            class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3 py-2 rounded-lg transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <span>Tabel Artikel</span>
        </a>
        <a href="{{ route('articles.create') }}"
            class="inline-flex items-center gap-2 bg-brand hover:bg-brand/90 text-brand-text text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tulis Artikel</span>
        </a>
    </div>
@endsection

@section('content')
    <div class="space-y-4" x-data="scheduleCalendar(@js([
        'year' => (int) now()->format('Y'),
        'month' => (int) now()->format('n'),
        'eventsUrl' => route('articles.schedule.events'),
        'scheduleUrl' => route('articles.schedule.update', '__ARTICLE__'),
        'publishUrl' => route('articles.publish', '__ARTICLE__'),
        'csrf' => csrf_token(),
    ]))">

        {{-- Legenda status --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[11px] text-slate-500">
            <span class="inline-flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-sm bg-violet-500"></span> Terjadwal
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span> Terbit
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-sm bg-rose-500"></span> Gagal
            </span>
            <span class="inline-flex items-center gap-1.5 text-slate-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 11l5-5m0 0l5 5m-5-5v12" />
                </svg>
                Seret event terjadwal untuk mengubah tanggal
            </span>
        </div>

        {{-- Kalender --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Header bulan --}}
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-200">
                <div class="flex items-center gap-2">
                    <button type="button" @click="prev()"
                        class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                        title="Bulan sebelumnya">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <h2 class="text-sm font-bold text-slate-800 min-w-[150px] text-center" x-text="monthLabel"></h2>
                    <button type="button" @click="next()"
                        class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                        title="Bulan berikutnya">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <span x-show="loading" x-cloak class="text-[11px] text-slate-400">Memuat…</span>
                    <button type="button" @click="goToday()"
                        class="text-xs font-semibold text-brand hover:text-brand/80 px-3 py-1.5 rounded-lg hover:bg-brand/5 transition">
                        Hari Ini
                    </button>
                </div>
            </div>

            {{-- Header hari (Senin pertama) --}}
            <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-200">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dayName)
                    <div class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        {{ $dayName }}</div>
                @endforeach
            </div>

            {{-- Sel hari --}}
            <div class="grid grid-cols-7">
                <template x-for="(day, di) in days" :key="di">
                    <div class="min-h-[104px] border-r border-b border-slate-100 p-1.5 flex flex-col gap-1"
                        :class="{ 'bg-slate-50/60': !day.inMonth, 'bg-brand/5 ring-2 ring-inset ring-brand/30': day.isToday }"
                        @dragover.prevent @drop="onDrop(day)">
                        <div class="px-0.5">
                            <span class="inline-flex items-center justify-center min-w-[20px] h-5 text-[11px] font-semibold"
                                :class="day.isToday
                                    ? 'bg-brand text-brand-text rounded-full'
                                    : (day.inMonth ? 'text-slate-700' : 'text-slate-300')"
                                x-text="day.day"></span>
                        </div>

                        <template x-for="ev in visibleEvents(day)" :key="ev.id">
                            <button type="button" :draggable="ev.status === 'scheduled'"
                                @dragstart="onDragStart(ev, $event)" @dragend="dragId = null"
                                @click="openDetail(ev)"
                                :class="[borderClass(ev.status),
                                    dragId === ev.id ? 'opacity-40' : '',
                                    ev.status === 'scheduled' ? 'cursor-move' : 'cursor-pointer']"
                                class="w-full text-left bg-white border border-slate-200/70 hover:border-slate-300 rounded-lg px-1.5 py-1 shadow-xs transition">
                                <span class="block text-[9px] font-mono font-bold text-slate-400"
                                    x-text="ev.time"></span>
                                <span class="block text-[10px] font-semibold text-slate-700 truncate"
                                    x-text="ev.title"></span>
                                <span class="block text-[9px] text-slate-400 truncate"
                                    x-text="subtitle(ev)"></span>
                            </button>
                        </template>

                        <button type="button" x-show="day.events.length > maxPerDay" x-cloak
                            @click="toggleExpand(day.iso)"
                            class="px-0.5 text-[9px] font-semibold text-brand hover:underline text-left"
                            x-text="expanded[day.iso]
                                ? 'Lebih sedikit'
                                : '+' + (day.events.length - maxPerDay) + ' lainnya'"></button>
                    </div>
                </template>
            </div>

            {{-- Empty state --}}
            <div x-show="!loading && events.length === 0" x-cloak class="px-5 py-8 text-center">
                <p class="text-sm text-slate-400">Belum ada artikel terjadwal, terbit, atau gagal pada bulan ini.</p>
            </div>
        </div>

        {{-- Modal detail event --}}
        <x-modal name="eventDetail" title="Detail Artikel">
            <div x-show="selected" class="space-y-4">
                <div class="flex items-start gap-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold shrink-0 mt-0.5"
                        :class="selected ? chipClass(selected.status) : ''"
                        x-text="selected ? statusLabel(selected.status) : ''"></span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800" x-text="selected?.title"></p>
                        <p class="text-xs text-slate-400 font-mono" x-text="selected?.slug"></p>
                    </div>
                </div>

                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 space-y-1.5">
                    <p class="text-xs text-slate-600">
                        <span class="font-semibold text-slate-700">Waktu:</span>
                        <span x-text="selected ? formatWhen(selected) : ''"></span>
                    </p>
                    <p class="text-xs text-slate-600">
                        <span class="font-semibold text-slate-700">Situs target:</span>
                        <span x-text="selected && selected.sites.length ? selected.sites.join(', ') : '—'"></span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    {{-- Publish segera (artikel terjadwal diambil lebih awal) --}}
                    <form x-show="selected && selected.status === 'scheduled'" x-cloak
                        :action="publishAction" method="POST">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition">Publish
                            segera</button>
                    </form>

                    {{-- Batalkan jadwalkan --}}
                    <form x-show="selected && selected.status === 'scheduled'" x-cloak
                        :action="scheduleAction" method="POST"
                        onsubmit="return confirm('Batalkan jadwal publish? Artikel akan kembali ke draft.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold rounded-lg border border-slate-200 transition">Batalkan</button>
                    </form>

                    <a :href="selected ? selected.edit_url : '#'"
                        class="px-4 py-2 bg-brand hover:bg-brand/90 text-brand-text text-xs font-semibold rounded-lg transition shadow-sm">Edit
                        Artikel</a>
                </div>
            </div>
        </x-modal>

        {{-- Toast lokal (hasil drag & drop / aksi cepat) --}}
        <div x-show="toastShow" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed top-[12%] right-5 z-[60] max-w-sm w-full pointer-events-none">
            <div class="pointer-events-auto flex items-center gap-3 p-4 bg-white border-l-4 rounded-xl shadow-xl"
                :class="toastType === 'error' ? 'border-rose-500' : 'border-emerald-500'">
                <p class="text-sm font-medium text-slate-700" x-text="toastMsg"></p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        /**
         * Kalender Jadwal — month-view ala screenshot (Senin pertama), event per
         * status warna, expand "+N lainnya", drag & drop reschedule (PATCH), dan
         * modal detail berisi aksi cepat.
         */
        function scheduleCalendar(initial) {
            const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            return {
                year: initial.year,
                month: initial.month,
                events: [],
                loading: false,
                expanded: {},
                selected: null,
                dragId: null,
                maxPerDay: 3,
                toastMsg: '',
                toastType: 'success',
                toastShow: false,
                _toastTimer: null,

                eventsUrl: initial.eventsUrl,
                scheduleUrl: initial.scheduleUrl,
                publishUrl: initial.publishUrl,
                csrf: initial.csrf,

                init() {
                    this.refetch();
                },

                // ===== Data =====
                async refetch() {
                    this.loading = true;

                    try {
                        const url = new URL(this.eventsUrl, window.location.origin);
                        url.searchParams.set('year', this.year);
                        url.searchParams.set('month', this.month);

                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.csrf,
                            },
                        });

                        if (!res.ok) throw new Error('HTTP ' + res.status);

                        const data = await res.json();
                        this.events = data.events || [];
                        this.expanded = {};
                    } catch (e) {
                        console.warn('[ScheduleCalendar] gagal memuat event:', e);
                        this.toast('Gagal memuat event kalender.', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                prev() {
                    this.month--;
                    if (this.month < 1) {
                        this.month = 12;
                        this.year--;
                    }
                    this.refetch();
                },

                next() {
                    this.month++;
                    if (this.month > 12) {
                        this.month = 1;
                        this.year++;
                    }
                    this.refetch();
                },

                goToday() {
                    const now = new Date();
                    this.year = now.getFullYear();
                    this.month = now.getMonth() + 1;
                    this.refetch();
                },

                get monthLabel() {
                    return MONTHS[this.month - 1] + ' ' + this.year;
                },

                // ===== Grid bulan (Senin di kolom pertama, kelipatan 7 sel) =====
                get days() {
                    const first = new Date(this.year, this.month - 1, 1);
                    const offset = (first.getDay() + 6) % 7;
                    const daysInMonth = new Date(this.year, this.month, 0).getDate();
                    const total = Math.ceil((offset + daysInMonth) / 7) * 7;
                    const now = new Date();
                    const today = this.toIso(now);

                    const list = [];
                    for (let i = 0; i < total; i++) {
                        const d = new Date(this.year, this.month - 1, 1 - offset + i);
                        const iso = this.toIso(d);

                        list.push({
                            day: d.getDate(),
                            iso: iso,
                            inMonth: d.getMonth() === this.month - 1,
                            isToday: iso === today,
                            events: this.eventsFor(iso),
                        });
                    }

                    return list;
                },

                toIso(d) {
                    const pad = (n) => String(n).padStart(2, '0');
                    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
                },

                eventsFor(iso) {
                    return this.events
                        .filter(e => e.date === iso)
                        .sort((a, b) => String(a.time).localeCompare(String(b.time)));
                },

                visibleEvents(day) {
                    return this.expanded[day.iso]
                        ? day.events
                        : day.events.slice(0, this.maxPerDay);
                },

                toggleExpand(iso) {
                    this.expanded[iso] = !this.expanded[iso];
                },

                // ===== Warna status =====
                borderClass(status) {
                    return {
                        scheduled: 'border-l-4 border-l-violet-500',
                        published: 'border-l-4 border-l-emerald-500',
                        failed: 'border-l-4 border-l-rose-500',
                    }[status] || 'border-l-4 border-l-slate-300';
                },

                chipClass(status) {
                    return {
                        scheduled: 'bg-violet-50 text-violet-700 border border-violet-200/60',
                        published: 'bg-emerald-50 text-emerald-700 border border-emerald-200/60',
                        failed: 'bg-rose-50 text-rose-700 border border-rose-200/60',
                    }[status] || 'bg-slate-50 text-slate-700 border border-slate-200/60';
                },

                statusLabel(status) {
                    return {
                        scheduled: 'Terjadwal',
                        published: 'Terbit',
                        failed: 'Gagal',
                    }[status] || '—';
                },

                subtitle(ev) {
                    return ev.sites && ev.sites.length ? ev.sites.join(', ') : this.statusLabel(ev.status);
                },

                formatWhen(ev) {
                    const [y, m, d] = String(ev.date).split('-').map(Number);
                    if (!y) return ev.date;
                    return d + ' ' + MONTHS_SHORT[m - 1] + ' ' + y + ', ' + ev.time + ' WIB';
                },

                // ===== Detail =====
                get scheduleAction() {
                    return this.selected
                        ? this.scheduleUrl.replace('__ARTICLE__', this.selected.article_id)
                        : '#';
                },

                get publishAction() {
                    return this.selected
                        ? this.publishUrl.replace('__ARTICLE__', this.selected.article_id)
                        : '#';
                },

                openDetail(ev) {
                    this.selected = ev;
                    this.$dispatch('open-modal', 'eventDetail');
                },

                // ===== Drag & drop reschedule =====
                onDragStart(ev, $event) {
                    if (ev.status !== 'scheduled') return;

                    this.dragId = ev.id;
                    $event.dataTransfer.effectAllowed = 'move';
                    $event.dataTransfer.setData('text/plain', ev.id);
                },

                async onDrop(day) {
                    const id = this.dragId;
                    this.dragId = null;

                    if (!id) return;

                    const ev = this.events.find(e => e.id === id);
                    if (!ev || ev.status !== 'scheduled' || ev.date === day.iso) return;

                    // Lead time minimal 5 menit (cerminan aturan server).
                    const [y, m, d] = day.iso.split('-').map(Number);
                    const [hh, mm] = String(ev.time).split(':').map(Number);
                    const picked = new Date(y, m - 1, d, hh, mm);

                    if (picked.getTime() < Date.now() + 5 * 60 * 1000) {
                        this.toast('Waktu jadwal minimal 5 menit dari sekarang.', 'error');
                        return;
                    }

                    try {
                        const res = await fetch(this.scheduleActionFor(ev.article_id), {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.csrf,
                            },
                            body: JSON.stringify({ scheduled_at: day.iso + ' ' + ev.time }),
                        });

                        const data = await res.json().catch(() => ({}));

                        if (!res.ok) {
                            const message = data.message
                                || Object.values(data.errors || {}).flat()[0]
                                || 'Gagal memperbarui jadwal.';
                            this.toast(message, 'error');
                            return;
                        }

                        ev.date = day.iso;
                        ev.start = data.scheduled_at || ev.start;
                        this.expanded = { ...this.expanded };
                        this.toast(data.message || 'Jadwal berhasil dipindah.', 'success');
                    } catch (e) {
                        console.warn('[ScheduleCalendar] reschedule gagal:', e);
                        this.toast('Koneksi gagal. Coba lagi.', 'error');
                    }
                },

                scheduleActionFor(articleId) {
                    return this.scheduleUrl.replace('__ARTICLE__', articleId);
                },

                // ===== Toast =====
                toast(message, type) {
                    this.toastMsg = message;
                    this.toastType = type || 'success';
                    this.toastShow = true;

                    clearTimeout(this._toastTimer);
                    this._toastTimer = setTimeout(() => {
                        this.toastShow = false;
                    }, 4000);
                },
            };
        }
    </script>
@endpush
