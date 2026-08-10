@extends('layouts.app')

@section('header')
    <div class="flex sm:items-center">
        <div>
            <h1 class="text-2xl font-serif font-bold text-brand-brown tracking-tight">Overview Dashboard</h1>
            <p class="text-xs font-sans text-slate-500 mt-1">Control hub & aktivitas terkini platform.</p>
        </div>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- 1. STATS OVERVIEW CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block">Total Artikel</span>
                <p class="text-2xl font-bold text-slate-900">1,248</p>
            </div>
            <div class="p-2.5 bg-amber-50 border border-amber-200/60 rounded-lg text-brand">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block">Published (WP)</span>
                <p class="text-2xl font-bold text-emerald-600">1,102</p>
            </div>
            <div class="p-2.5 bg-emerald-50 border border-emerald-200/60 rounded-lg text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block">Antrean Cron</span>
                <p class="text-2xl font-bold text-amber-600">42</p>
            </div>
            <div class="p-2.5 bg-amber-50 border border-amber-200/60 rounded-lg text-amber-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block">Sync Issue</span>
                <p class="text-2xl font-bold text-rose-600">4</p>
            </div>
            <div class="p-2.5 bg-rose-50 border border-rose-200/60 rounded-lg text-rose-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>
    </div>

    <!-- 2. MAIN GRID LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- KOLOM KIRI (2/3 Grid) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- KALENDER WIDGET -->
            <div x-data="calendarWidget()" class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
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
                    <template x-for="blank in blankDays">
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
                        </div>
                    </template>
                </div>
            </div>

        </div>

        <!-- KOLOM KANAN (1/3 Grid) -->
        <div class="space-y-6">

            <!-- AKSI CEPAT -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">Aksi Cepat</h3>
                <div class="grid grid-cols-2 gap-2">
                    <a href="#" class="p-3 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-xs font-medium text-center text-slate-800 flex flex-col items-center gap-1.5 transition">
                        <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Buat Artikel</span>
                    </a>
                </div>
            </div>

            <!-- AKTIVITAS TERBARU -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">Aktivitas Terbaru</h3>

                <div class="space-y-3.5 text-xs">
                    <div class="flex items-start gap-3 pb-3 border-b border-slate-100">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 shrink-0"></div>
                        <div>
                            <p class="text-slate-800 font-medium">Auto-sync 5 artikel ke WP Sukses</p>
                            <span class="text-[10px] text-slate-400 block mt-0.5">3 menit yang lalu</span>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 pb-3 border-b border-slate-100">
                        <div class="w-2 h-2 rounded-full bg-brand mt-1.5 shrink-0"></div>
                        <div>
                            <p class="text-slate-800 font-medium">Tenant dipilih: <span class="text-brand font-semibold">PT Wayang Transport</span></p>
                            <span class="text-[10px] text-slate-400 block mt-0.5">20 menit yang lalu</span>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 shrink-0"></div>
                        <div>
                            <p class="text-slate-800 font-medium">Budi (Author) menerbitkan draft baru</p>
                            <span class="text-[10px] text-slate-400 block mt-0.5">1 jam yang lalu</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<script>
    function calendarWidget() {
        const today = new Date();
        return {
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
