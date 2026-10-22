@php
    // Prefill modal "Pilih tanggal & waktu": input lama (gagal validasi) → jadwal
    // aktif artikel → kosong (pilih baru). Partial ini berdiri sendiri di luar
    // form utama, jadi ia menghitung prefill-nya sendiri (tidak bergantung pada
    // variabel lokal partial _form).
    $isEditArticle = isset($article);
    $scheduleModalAt = null;
    if (old('scheduled_at')) {
        try {
            $scheduleModalAt = \Illuminate\Support\Carbon::parse((string) old('scheduled_at'));
        } catch (\Throwable) {
            $scheduleModalAt = null;
        }
    } elseif ($isEditArticle && $article->scheduled_at) {
        $scheduleModalAt = $article->scheduled_at;
    }
@endphp

{{-- Modal: Pilih tanggal & waktu jadwal publish.
     Sengaja dirender DI LUAR <form> utama (create/edit) agar:
     1. tak ada tombol di dalamnya yang bisa ikut submit form utama, dan
     2. tak terjebak stacking context kolom sticky sehingga selalu di depan. --}}
<x-modal name="schedulePicker" title="Pilih tanggal & waktu">
    <div x-data="schedulePicker({
            date: @js($scheduleModalAt?->format('Y-m-d')),
            time: @js($scheduleModalAt?->format('H:i')),
        })"
        x-on:open-schedule-picker.window="onOpen($event.detail)"
        class="space-y-5">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            {{-- Kalender mini (Senin di kolom pertama) --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-slate-800" x-text="monthLabel"></span>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="prevMonth()"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                            title="Bulan sebelumnya">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button type="button" @click="nextMonth()"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                            title="Bulan berikutnya">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-7 gap-0.5">
                    <template x-for="(head, hi) in dayHeads" :key="'h' + hi">
                        <div class="py-1 text-center text-[10px] font-bold text-slate-400 uppercase"
                            x-text="head"></div>
                    </template>
                    <template x-for="(day, di) in days" :key="di">
                        <button type="button" @click="pick(day.iso)"
                            :class="day.iso === date
                                ? 'bg-brand text-brand-text font-bold'
                                : (day.isToday
                                    ? 'bg-violet-50 text-violet-700 font-semibold'
                                    : (day.inMonth
                                        ? 'text-slate-700 hover:bg-slate-100'
                                        : 'text-slate-300 hover:bg-slate-50'))"
                            class="aspect-square flex items-center justify-center rounded-lg text-xs transition"
                            x-text="day.day"></button>
                    </template>
                </div>
            </div>

            {{-- Tanggal & waktu --}}
            <div class="space-y-3">
                <div>
                    <label
                        class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal</label>
                    <input type="text" :value="displayDate" readonly
                        class="w-full bg-slate-50 text-slate-800 text-sm border border-slate-200 rounded-xl px-3.5 py-2.5 cursor-default">
                </div>
                <div>
                    <label
                        class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Waktu
                        (WIB)</label>
                    <input type="time" x-model="time" step="60" @keydown.enter.prevent @input="error = ''"
                        class="w-full bg-white text-slate-800 text-sm border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition">
                </div>

                <p x-show="error" x-cloak x-transition
                    class="text-[11px] font-medium text-rose-500" x-text="error"></p>
                <p x-show="!error" class="text-[10px] text-slate-400 leading-relaxed">
                    Dikirim otomatis ke seluruh situs target saat waktu tiba (dicek tiap menit).
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <button type="button" @click="$dispatch('close-modal', 'schedulePicker')"
                class="text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 px-3 py-2 rounded-lg transition">Batal</button>
            <button type="button" @click="confirm()" :disabled="!date || !time"
                :class="(!date || !time)
                    ? 'opacity-50 cursor-not-allowed bg-slate-300 text-white'
                    : 'bg-brand hover:bg-brand/90 text-brand-text'"
                class="inline-flex items-center gap-2 text-xs font-semibold px-5 py-2.5 rounded-xl transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Jadwalkan pengiriman</span>
            </button>
        </div>
    </div>
</x-modal>

@push('scripts')
    <script>
        /**
         * Schedule Picker — mini calendar (Senin pertama) + input waktu WIB
         * untuk modal "Pilih tanggal & waktu".
         *
         * Komunikasi lewat event window (mengikuti pola x-modal):
         *   open-schedule-picker → prefill & buka modal
         *   schedule-confirmed   → serahkan "YYYY-MM-DD HH:MM" ke articleEditor
         */
        function schedulePicker(initial) {
            const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            return {
                date: initial.date || '',
                time: initial.time || '',
                viewYear: new Date().getFullYear(),
                viewMonth: new Date().getMonth(),
                error: '',
                dayHeads: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],

                init() {
                    if (!this.time) this.time = this.defaultTime();

                    const base = this.date ? this.parseDate(this.date) : new Date();
                    this.viewYear = base.getFullYear();
                    this.viewMonth = base.getMonth();
                },

                onOpen(detail) {
                    const d = detail || {};
                    this.date = d.date || this.date || this.todayIso();
                    this.time = d.time || this.time || this.defaultTime();

                    const base = this.parseDate(this.date);
                    this.viewYear = base.getFullYear();
                    this.viewMonth = base.getMonth();
                    this.error = '';

                    this.$dispatch('open-modal', 'schedulePicker');
                },

                // ===== Util tanggal (semua lokal/None-UTC, tanpa parsing ambigu) =====
                pad(n) {
                    return String(n).padStart(2, '0');
                },

                toIso(d) {
                    return d.getFullYear() + '-' + this.pad(d.getMonth() + 1) + '-' + this.pad(d.getDate());
                },

                todayIso() {
                    return this.toIso(new Date());
                },

                parseDate(iso) {
                    const [y, m, d] = String(iso).split('-').map(Number);
                    return new Date(y, (m || 1) - 1, d || 1);
                },

                /** Waktu default: 10 menit ke depan (lolos lead time minimal 5 menit). */
                defaultTime() {
                    const d = new Date(Date.now() + 10 * 60 * 1000);
                    return this.pad(d.getHours()) + ':' + this.pad(d.getMinutes());
                },

                // ===== Navigasi & pemilihan =====
                get monthLabel() {
                    return MONTHS[this.viewMonth] + ' ' + this.viewYear;
                },

                get displayDate() {
                    if (!this.date) return '-';
                    const d = this.parseDate(this.date);
                    return d.getDate() + ' ' + MONTHS_SHORT[d.getMonth()] + ' ' + d.getFullYear();
                },

                prevMonth() {
                    if (this.viewMonth === 0) {
                        this.viewMonth = 11;
                        this.viewYear--;
                    } else {
                        this.viewMonth--;
                    }
                },

                nextMonth() {
                    if (this.viewMonth === 11) {
                        this.viewMonth = 0;
                        this.viewYear++;
                    } else {
                        this.viewMonth++;
                    }
                },

                /** Grid bulan 7 kolom, Senin di kolom pertama (selalu kelipatan 7). */
                get days() {
                    const first = new Date(this.viewYear, this.viewMonth, 1);
                    const offset = (first.getDay() + 6) % 7;
                    const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
                    const total = Math.ceil((offset + daysInMonth) / 7) * 7;
                    const today = this.todayIso();

                    const list = [];
                    for (let i = 0; i < total; i++) {
                        const d = new Date(this.viewYear, this.viewMonth, 1 - offset + i);
                        const iso = this.toIso(d);
                        list.push({
                            day: d.getDate(),
                            iso: iso,
                            inMonth: d.getMonth() === this.viewMonth,
                            isToday: iso === today,
                        });
                    }

                    return list;
                },

                pick(iso) {
                    this.date = iso;
                    this.error = '';
                },

                /**
                 * Validasi klien (lead time 5 menit, horizon 1 tahun) — versi
                 * identik tetap dijalankan server sebagai penjaga otoritatif.
                 */
                confirm() {
                    if (!this.date || !this.time) return;

                    const [y, m, d] = this.date.split('-').map(Number);
                    const [hh, mm] = this.time.split(':').map(Number);
                    const picked = new Date(y, m - 1, d, hh, mm);
                    const nowTs = Date.now();

                    if (isNaN(picked.getTime())) {
                        this.error = 'Tanggal & waktu jadwal tidak valid.';
                        return;
                    }

                    if (picked.getTime() < nowTs + 5 * 60 * 1000) {
                        this.error = 'Waktu jadwal minimal 5 menit dari sekarang.';
                        return;
                    }

                    if (picked.getTime() > nowTs + 365 * 24 * 60 * 60 * 1000) {
                        this.error = 'Waktu jadwal maksimal 1 tahun dari sekarang.';
                        return;
                    }

                    this.error = '';
                    this.$dispatch('close-modal', 'schedulePicker');
                    this.$dispatch('schedule-confirmed', { value: this.date + ' ' + this.time });
                },
            };
        }
    </script>
@endpush
