import { Link, usePage } from '@inertiajs/react';

export default function AppLayout({ children, title }) {
    const { auth, flash } = usePage().props;
    const path = typeof window !== 'undefined' ? window.location.pathname : '';

    const nav = [
        { href: route('dashboard'), label: 'Dashboard', match: '/dashboard' },
        { href: route('problems.index'), label: 'Problems', match: '/problems' },
        { href: route('categories.index'), label: 'Categories', match: '/categories' },
    ];

    return (
        <div className="min-h-screen text-paper">
            <header className="border-b border-line/80">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-6 px-5 py-5">
                    <Link href={route('dashboard')} className="group flex items-baseline gap-3">
                        <span className="font-display text-2xl font-bold tracking-tight text-paper group-hover:text-accent transition-colors">
                            PbTrack
                        </span>
                        <span className="hidden font-mono text-[11px] uppercase tracking-[0.18em] text-muted sm:inline">
                            pbinfo progress
                        </span>
                    </Link>

                    <nav className="flex items-center gap-1 sm:gap-2">
                        {nav.map((item) => {
                            const active = path.startsWith(item.match);
                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={`rounded-sm px-3 py-1.5 font-mono text-xs uppercase tracking-wider transition-colors ${
                                        active
                                            ? 'bg-accent/15 text-accent'
                                            : 'text-muted hover:text-paper'
                                    }`}
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>

                    <div className="flex items-center gap-3">
                        <span className="hidden font-mono text-xs text-muted md:inline">
                            @{auth.user?.username}
                        </span>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="font-mono text-xs uppercase tracking-wider text-muted hover:text-accent transition-colors"
                        >
                            Log out
                        </Link>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-6xl px-5 py-10">
                {title && (
                    <div className="mb-8 fade-rise">
                        <h1 className="font-display text-3xl font-bold tracking-tight sm:text-4xl">
                            {title}
                        </h1>
                    </div>
                )}

                {flash?.status && (
                    <div className="mb-6 border border-accent/30 bg-accent/10 px-4 py-3 font-mono text-sm text-accent fade-rise">
                        {flash.status}
                    </div>
                )}

                {children}
            </main>
        </div>
    );
}
