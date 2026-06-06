<aside class="h-max rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
    <nav class="space-y-1 text-sm font-bold">
        <a href="{{ route('account.dashboard') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('account.dashboard') ? 'bg-cyan-50 text-primary' : 'text-slate-700 hover:bg-slate-50' }}">Dashboard</a>
        <a href="{{ route('account.orders') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('account.orders*') ? 'bg-cyan-50 text-primary' : 'text-slate-700 hover:bg-slate-50' }}">Purchase History</a>
        <a href="{{ route('account.profile') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('account.profile') ? 'bg-cyan-50 text-primary' : 'text-slate-700 hover:bg-slate-50' }}">Profile & Security</a>
        <a href="{{ route('products') }}" class="block rounded-lg px-4 py-3 text-slate-700 hover:bg-slate-50">Premium Products</a>
    </nav>
</aside>
