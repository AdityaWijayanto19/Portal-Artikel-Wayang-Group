@props(['name'])

<input type="date" name="{{ $name }}" value="{{ request($name) }}" onchange="this.form.submit()"
    class="px-2.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand text-slate-700">
