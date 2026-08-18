@extends('layouts.app')
@section('title', 'Panduan Penulis')
@section('subtitle', 'Alur dan tata cara menulis serta mempublikasikan artikel ke WordPress.')

@push('styles')
    <style>
        .toc-item {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.45rem 0.6rem;
            border-radius: 0.5rem;
            font-size: 13px;
            line-height: 1.375;
            color: #64748b;
            transition: all .15s ease;
            border-left: 2px solid transparent;
        }

        .toc-item:hover {
            color: #0f172a;
            background: #f8fafc;
        }

        .toc-active {
            color: var(--color-brand);
            font-weight: 600;
            background: color-mix(in srgb, var(--color-brand) 10%, transparent);
            border-color: var(--color-brand);
        }

        .toc-sub {
            padding-left: 1.25rem;
            font-size: 12px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const scroller = document.querySelector('main')?.parentElement;
            const links = document.querySelectorAll('[data-toc-link]');
            const sections = Array.from(document.querySelectorAll('[data-section]'));
            const OFFSET = 96;

            links.forEach(function (link) {
                link.addEventListener('click', function (e) {
                    const target = document.querySelector(link.getAttribute('href'));
                    if (!target || !scroller) return;
                    e.preventDefault();
                    const rect = target.getBoundingClientRect();
                    const scrollRect = scroller.getBoundingClientRect();
                    scroller.scrollTo({
                        top: scroller.scrollTop + (rect.top - scrollRect.top) - OFFSET,
                        behavior: 'smooth',
                    });
                });
            });

            if (scroller && sections.length) {
                const offsets = sections.map(function (s) {
                    return s.getBoundingClientRect().top - scroller.getBoundingClientRect().top;
                });

                function updateActive() {
                    let current = sections[0];
                    for (let i = 0; i < sections.length; i++) {
                        if (scroller.scrollTop + OFFSET + 12 >= offsets[i]) current = sections[i];
                    }
                    links.forEach(function (l) { l.classList.remove('toc-active'); });
                    const active = document.querySelector('[data-toc-link][href="#' + current.id + '"]');
                    if (active) active.classList.add('toc-active');
                }

                scroller.addEventListener('scroll', updateActive, { passive: true });
                window.addEventListener('resize', function () {
                    const base = scroller.getBoundingClientRect().top;
                    sections.forEach(function (s, i) { offsets[i] = s.getBoundingClientRect().top - base; });
                    updateActive();
                });
                updateActive();
            }
        })();
    </script>
@endpush

@section('content')
    <div class="max-w-5xl mx-auto">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start w-full">

            <aside class="lg:col-span-3 lg:sticky lg:top-24 space-y-4 min-w-0">
                <div class="bg-white border border-slate-200 rounded-xl p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-brand mb-1">Daftar Isi</p>
                    <p class="text-[11px] text-slate-400 mb-3">Navigasi cepat halaman</p>
                    <ul class="space-y-1">
                        <li><a data-toc-link href="#workflow" class="toc-item">
                                <span class="text-brand font-bold shrink-0">1</span> Workflow Penulisan
                            </a></li>
                        <li><a data-toc-link href="#form" class="toc-item">
                                <span class="text-brand font-bold shrink-0">2</span> Panduan Form Artikel
                            </a></li>
                        <li><a data-toc-link href="#form-judul" class="toc-item toc-sub">
                                <span class="text-slate-300 font-bold shrink-0">2.1</span> Judul &amp; Isi
                            </a></li>
                        <li><a data-toc-link href="#form-gambar" class="toc-item toc-sub">
                                <span class="text-slate-300 font-bold shrink-0">2.2</span> Gambar Unggulan
                            </a></li>
                        <li><a data-toc-link href="#form-seo" class="toc-item toc-sub">
                                <span class="text-slate-300 font-bold shrink-0">2.3</span> Optimasi SEO &amp; Preview
                            </a></li>
                        <li><a data-toc-link href="#form-kategori" class="toc-item toc-sub">
                                <span class="text-slate-300 font-bold shrink-0">2.4</span> Kategori, Tag &amp; Situs
                            </a></li>
                        <li><a data-toc-link href="#dos-donts" class="toc-item">
                                <span class="text-brand font-bold shrink-0">3</span> Do's &amp; Don'ts
                            </a></li>
                        <li><a data-toc-link href="#faq" class="toc-item">
                                <span class="text-brand font-bold shrink-0">4</span> FAQ
                            </a></li>
                    </ul>
                </div>
            </aside>

            <div class="lg:col-span-9 space-y-6 min-w-0">
                @include('guidelines.partials.sections')
            </div>
        </div>
    </div>
@endsection
