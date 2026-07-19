import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

function ProgressRing({ percent }) {
    const r = 54;
    const c = 2 * Math.PI * r;
    const offset = c - (Math.min(100, Math.max(0, percent)) / 100) * c;

    return (
        <div className="relative h-40 w-40">
            <svg className="-rotate-90" viewBox="0 0 120 120">
                <circle
                    cx="60"
                    cy="60"
                    r={r}
                    fill="none"
                    stroke="rgba(232,235,230,0.08)"
                    strokeWidth="8"
                />
                <circle
                    cx="60"
                    cy="60"
                    r={r}
                    fill="none"
                    stroke="#2dd4bf"
                    strokeWidth="8"
                    strokeLinecap="round"
                    strokeDasharray={c}
                    strokeDashoffset={offset}
                    className="progress-fill"
                />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
                <span className="font-display text-3xl font-bold text-paper">{percent}%</span>
                <span className="font-mono text-[10px] uppercase tracking-wider text-muted">
                    solved
                </span>
            </div>
        </div>
    );
}

export default function Dashboard({ stats, recent, sync }) {
    const syncing = ['pending', 'running'].includes(sync?.run_status);

    const syncNow = () => {
        router.post(route('sync.progress'));
    };

    return (
        <AppLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="grid gap-10 lg:grid-cols-[220px_1fr] fade-rise">
                <div className="flex flex-col items-start gap-6">
                    <ProgressRing percent={stats.percent} />
                    <button
                        type="button"
                        onClick={syncNow}
                        disabled={syncing}
                        className="border border-accent/40 bg-accent/10 px-4 py-2 font-mono text-xs uppercase tracking-wider text-accent transition hover:bg-accent/20 disabled:opacity-50"
                    >
                        <span className={syncing ? 'sync-pulse' : ''}>
                            {syncing ? 'Syncing…' : 'Sync now'}
                        </span>
                    </button>
                    <p className="font-mono text-[11px] text-muted">
                        Last sync:{' '}
                        {sync?.at ? new Date(sync.at).toLocaleString() : 'never'}
                        {sync?.status ? ` · ${sync.status}` : ''}
                    </p>
                    {sync?.error && (
                        <p className="max-w-xs font-mono text-[11px] text-danger">{sync.error}</p>
                    )}
                </div>

                <div>
                    <div className="grid gap-6 sm:grid-cols-3">
                        {[
                            { label: 'Solved', value: stats.solved, tone: 'text-accent' },
                            { label: 'Attempted', value: stats.attempted, tone: 'text-warn' },
                            { label: 'Catalog', value: stats.total, tone: 'text-paper' },
                        ].map((item) => (
                            <div key={item.label} className="border-t border-line pt-4">
                                <div className="font-mono text-[11px] uppercase tracking-wider text-muted">
                                    {item.label}
                                </div>
                                <div className={`mt-2 font-display text-4xl font-bold ${item.tone}`}>
                                    {item.value}
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="mt-12">
                        <h2 className="font-mono text-xs uppercase tracking-[0.2em] text-muted">
                            Recent activity
                        </h2>
                        <ul className="mt-4 divide-y divide-line/80">
                            {recent.length === 0 && (
                                <li className="py-6 font-mono text-sm text-muted">
                                    No submissions synced yet. Hit Sync now after login.
                                </li>
                            )}
                            {recent.map((row) => (
                                <li
                                    key={`${row.id}-${row.at}`}
                                    className="flex items-center justify-between gap-4 py-3"
                                >
                                    <div>
                                        <a
                                            href={row.url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="font-display text-lg text-paper hover:text-accent transition-colors"
                                        >
                                            {row.title}
                                        </a>
                                        <div className="font-mono text-[11px] text-muted">
                                            #{row.id} · {row.status}
                                        </div>
                                    </div>
                                    <div className="font-mono text-sm text-accent">{row.score}</div>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
