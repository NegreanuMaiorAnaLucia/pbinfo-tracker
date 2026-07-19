import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';

const statusTone = {
    solved: 'text-accent',
    attempted: 'text-warn',
    unsolved: 'text-muted',
};

export default function ProblemsIndex({ problems, filters, categories, difficulties }) {
    const [q, setQ] = useState(filters.q || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [category, setCategory] = useState(filters.category || '');
    const [difficulty, setDifficulty] = useState(filters.difficulty || 'all');

    const apply = (overrides = {}) => {
        router.get(
            route('problems.index'),
            {
                q,
                status,
                category: category || undefined,
                difficulty,
                ...overrides,
            },
            { preserveState: true, replace: true }
        );
    };

    return (
        <AppLayout title="Problems">
            <Head title="Problems" />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    apply();
                }}
                className="mb-8 grid gap-3 fade-rise sm:grid-cols-2 lg:grid-cols-5"
            >
                <input
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    placeholder="Search title or ID"
                    className="border border-line bg-ink-soft/60 px-3 py-2.5 font-mono text-sm text-paper placeholder:text-muted/60 focus:border-accent focus:ring-0"
                />
                <select
                    value={status}
                    onChange={(e) => {
                        setStatus(e.target.value);
                        apply({ status: e.target.value });
                    }}
                    className="border border-line bg-ink-soft/60 px-3 py-2.5 font-mono text-sm text-paper focus:border-accent focus:ring-0"
                >
                    <option value="all">All statuses</option>
                    <option value="solved">Solved</option>
                    <option value="attempted">Attempted</option>
                    <option value="unsolved">Unsolved</option>
                </select>
                <select
                    value={category}
                    onChange={(e) => {
                        setCategory(e.target.value);
                        apply({ category: e.target.value || undefined });
                    }}
                    className="border border-line bg-ink-soft/60 px-3 py-2.5 font-mono text-sm text-paper focus:border-accent focus:ring-0"
                >
                    <option value="">All categories</option>
                    {categories.map((c) => (
                        <option key={c.id} value={c.id}>
                            {c.name}
                        </option>
                    ))}
                </select>
                <select
                    value={difficulty}
                    onChange={(e) => {
                        setDifficulty(e.target.value);
                        apply({ difficulty: e.target.value });
                    }}
                    className="border border-line bg-ink-soft/60 px-3 py-2.5 font-mono text-sm text-paper focus:border-accent focus:ring-0"
                >
                    <option value="all">All difficulties</option>
                    {difficulties.map((d) => (
                        <option key={d} value={d}>
                            {d}
                        </option>
                    ))}
                </select>
                <button
                    type="submit"
                    className="bg-accent px-4 py-2.5 font-mono text-xs uppercase tracking-wider text-ink hover:bg-accent-dim"
                >
                    Filter
                </button>
            </form>

            <div className="overflow-x-auto fade-rise" style={{ animationDelay: '80ms' }}>
                <table className="min-w-full text-left">
                    <thead>
                        <tr className="border-b border-line font-mono text-[11px] uppercase tracking-wider text-muted">
                            <th className="py-3 pr-4 font-medium">ID</th>
                            <th className="py-3 pr-4 font-medium">Title</th>
                            <th className="py-3 pr-4 font-medium">Category</th>
                            <th className="py-3 pr-4 font-medium">Status</th>
                            <th className="py-3 font-medium">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        {problems.data.map((p) => (
                            <tr key={p.id} className="border-b border-line/60 hover:bg-white/[0.02]">
                                <td className="py-3 pr-4 font-mono text-xs text-muted">#{p.id}</td>
                                <td className="py-3 pr-4">
                                    <a
                                        href={p.url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="font-display text-base text-paper hover:text-accent transition-colors"
                                    >
                                        {p.title}
                                    </a>
                                </td>
                                <td className="py-3 pr-4 font-mono text-xs text-muted">
                                    {p.category || '—'}
                                </td>
                                <td className={`py-3 pr-4 font-mono text-xs uppercase ${statusTone[p.status]}`}>
                                    {p.status}
                                </td>
                                <td className="py-3 font-mono text-sm text-accent">{p.score}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="mt-8 flex items-center justify-between font-mono text-xs text-muted">
                <span>
                    Page {problems.current_page} / {problems.last_page}
                </span>
                <div className="flex gap-3">
                    {problems.prev_page_url && (
                        <button
                            type="button"
                            onClick={() => router.get(problems.prev_page_url)}
                            className="hover:text-accent"
                        >
                            Prev
                        </button>
                    )}
                    {problems.next_page_url && (
                        <button
                            type="button"
                            onClick={() => router.get(problems.next_page_url)}
                            className="hover:text-accent"
                        >
                            Next
                        </button>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
