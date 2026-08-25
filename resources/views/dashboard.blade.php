@extends('layouts.app')
@section('title', 'Overview Dashboard')
@section('subtitle', 'Kontrol Manajemen & aktivitas terkini platform.')

@section('content')
<div class="space-y-6">

    {{-- 1. STATS OVERVIEW CARDS (data real) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card label="Total Artikel" value="{{ number_format($metrics['articles_total']) }}" tone="brand">
            <x-slot:icon>
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-newspaper-icon lucide-newspaper"><path d="M15 18h-5"/><path d="M18 14h-8"/><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0v-9a2 2 0 0 1 2-2h2"/><rect width="8" height="4" x="10" y="6" rx="1"/></svg>
            </x-slot:icon>
            <x-slot:sub>SEO: <span class="font-semibold text-brand">{{ $metrics['avg_seo_score'] }}</span> · Readability: <span class="font-semibold text-brand">{{ $metrics['avg_readability_score'] }}</span></x-slot:sub>
        </x-stat-card>

        <x-stat-card label="Published (WP)" value="{{ number_format($metrics['published_total']) }}" tone="emerald">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </x-slot:icon>
            <x-slot:sub>Artikel berhasil terbit di WP</x-slot:sub>
        </x-stat-card>

        <x-stat-card label="Antrean Cron" value="{{ number_format($metrics['queued_total']) }}" tone="amber">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </x-slot:icon>
            <x-slot:sub>Menunggu dikirim ke WP</x-slot:sub>
        </x-stat-card>

        <x-stat-card label="Sync Issue" value="{{ number_format($metrics['failed_total']) }}" tone="rose">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </x-slot:icon>
            <x-slot:sub>Artikel berstatus gagal</x-slot:sub>
        </x-stat-card>
    </div>

    {{-- ROW 1: CALENDAR (2/3) + QUICK ACTION (1/3) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- KALENDER WIDGET (data real: tanggal publikasi WP sukses) --}}
        <div class="lg:col-span-2">
            <div x-data="calendarWidget({{ Js::from($calendarData) }})" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm h-full">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Jadwal Content Release</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="prevMonth()" class="px-2 py-1 text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded transition">&lt;</button>
                        <span class="text-xs font-bold text-brand" x-text="monthNames[currentMonth] + ' ' + currentYear"></span>
                        <button @click="nextMonth()" class="px-2 py-1 text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded transition">&gt;</button>
                    </div>
                </div>

                <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-black uppercase text-slate-400 mb-2">
                    <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
                </div>

                <div class="grid grid-cols-7 gap-1 text-xs">
                    <template x-for="blank in blankDays" :key="'b'+blank">
                        <div class="p-2 text-center text-transparent">.</div>
                    </template>
                    <template x-for="date in daysInMonth" :key="date">
                        <div @click="selectDate(date)"
                             :class="{
                                'bg-brand text-brand-text font-bold shadow-sm': isToday(date),
                                'bg-slate-50 text-slate-700 hover:bg-slate-100 border-slate-200': !isToday(date),
                                'border-brand ring-1 ring-brand': selectedDate === date && !isToday(date)
                             }"
                             class="p-2 text-center rounded-lg border cursor-pointer transition">
                            <span x-text="date"></span>
                            <span x-show="publishedCount(date) > 0" class="mt-1 flex justify-center">
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            </span>
                        </div>
                    </template>
                </div>

                <div class="flex items-center gap-4 mt-3 pt-3 border-t border-slate-100 text-[10px] text-slate-400">
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Ada publikasi WP sukses</span>
                </div>
            </div>
        </div>

        {{-- AKSI CEPAT (route nyata sesuai role) --}}
        <div class="h-full">
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm h-full">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3 pb-2 border-b border-slate-100">Aksi Cepat</h3>
                <div class="grid grid-cols-2 gap-2">
                    <x-quick-action href="{{ route('articles.create') }}" label="Buat Artikel">
                        <x-slot:icon>
                            <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </x-slot:icon>
                    </x-quick-action>

                    @hasrole('super_admin|admin')
                        <x-quick-action href="{{ route('categories.create') }}" label="Buat Kategori">
                            <x-slot:icon>
                                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </x-slot:icon>
                        </x-quick-action>
                        <x-quick-action href="{{ route('wp-sites.create') }}" label="Tambah WP Site">
                            <x-slot:icon>
                                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </x-slot:icon>
                        </x-quick-action>
                        <x-quick-action href="{{ route('users.index') }}" label="Manajemen Pengguna">
                            <x-slot:icon>
                                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </x-slot:icon>
                        </x-quick-action>
                    @endhasrole

                    @hasrole('super_admin')
                        <x-quick-action href="{{ route('companies.index') }}" label="Manajemen Perusahaan">
                            <x-slot:icon>
                                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H7m4 0v10"/></svg>
                            </x-slot:icon>
                        </x-quick-action>
                        <x-quick-action href="{{ route('activity-logs.index') }}" label="Log Aktivitas">
                            <x-slot:icon>
                                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </x-slot:icon>
                        </x-quick-action>
                        <x-quick-action href="{{ route('wp-sync-logs.index') }}" label="Riwayat Sinkronisasi">
                            <x-slot:icon>
                                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </x-slot:icon>
                        </x-quick-action>
                    @endhasrole
                </div>
            </div>
        </div>

    </div>

    {{-- ROW 2: ACTIVITY (1/2) + WP SYNC (1/2) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- AKTIVITAS USER (super admin & admin; author tidak melihat sama sekali) --}}
        @hasrole('super_admin|admin')
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm h-full">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Aktivitas User</h3>
                    <a href="{{ route('activity-logs.index') }}" class="text-[10px] text-brand hover:underline font-semibold">Lihat semua</a>
                </div>

                <div class="max-h-80 overflow-y-auto pr-1 -mr-1 space-y-2.5 text-xs">
                    @forelse($activityLogs as $log)
                        <x-activity-item
                            :meta="trim(($log->user?->name ?? 'Sistem') . ' · ' . ($log->created_at?->diffForHumans() ?? '-'))">
                            {{ $log->description }}
                        </x-activity-item>
                    @empty
                        <p class="text-slate-400">Belum ada aktivitas tercatat.</p>
                    @endforelse
                </div>
            </div>
        @endhasrole

        {{-- RIWAYAT SINKRONISASI WP (semua role; link "lihat semua" hanya super_admin/admin) --}}
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm h-full">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Riwayat Sinkronisasi WP</h3>
                @hasrole('super_admin|admin')
                    <a href="{{ route('wp-sync-logs.index') }}" class="text-[10px] text-brand hover:underline font-semibold">Lihat semua</a>
                @endhasrole
            </div>

            <div class="max-h-80 overflow-y-auto pr-1 -mr-1 space-y-2.5 text-xs">
                @forelse($logs as $log)
                    <x-activity-item
                        :dot="$log->status === 'success' ? 'bg-emerald-500' : 'bg-rose-500'"
                        :meta="($log->status === 'success' ? 'Sukses' : 'Gagal') . ' · ' . ($log->synced_at ?? $log->created_at)?->diffForHumans()">
                        {{ $log->article?->title ?? '(artikel terhapus)' }}
                        <span class="text-slate-400 font-normal">&rarr; {{ $log->wpSite?->site_name ?? 'situs terhapus' }}</span>
                    </x-activity-item>
                @empty
                    <p class="text-slate-400">Belum ada riwayat sinkronisasi.</p>
                @endforelse
            </div>
        </div>

    </div>

</div>

<script>
    function calendarWidget(publishedDates = {}) {
        const today = new Date();
        return {
            publishedDates,
            currentMonth: today.getMonth(),
            currentYear: today.getFullYear(),
            selectedDate: today.getDate(),
            daysInMonth: [],
            blankDays: [],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],

            init() {
                this.getDays();
            },
            getDays() {
                const daysInMonthCount = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
                const firstDayOfWeek = new Date(this.currentYear, this.currentMonth, 1).getDay();

                this.blankDays = Array.from({ length: firstDayOfWeek }, (_, i) => i + 1);
                this.daysInMonth = Array.from({ length: daysInMonthCount }, (_, i) => i + 1);
            },
            pad(n) {
                return String(n).padStart(2, '0');
            },
            publishedCount(date) {
                const key = `${this.currentYear}-${this.pad(this.currentMonth + 1)}-${this.pad(date)}`;
                return this.publishedDates[key] || 0;
            },
            prevMonth() {
                if (this.currentMonth === 0) {
                    this.currentMonth = 11;
                    this.currentYear--;
                } else {
                    this.currentMonth--;
                }
                this.getDays();
            },
            nextMonth() {
                if (this.currentMonth === 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                } else {
                    this.currentMonth++;
                }
                this.getDays();
            },
            isToday(date) {
                return date === today.getDate() &&
                       this.currentMonth === today.getMonth() &&
                       this.currentYear === today.getFullYear();
            },
            selectDate(date) {
                this.selectedDate = date;
            }
        }
    }
</script>
@endsection
