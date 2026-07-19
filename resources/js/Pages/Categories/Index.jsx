import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function CategoriesIndex({ categories }) {
    return (
        <AppLayout title="Categories">
            <Head title="Categories" />

            <p className="mb-8 max-w-2xl text-muted fade-rise">
                Progress by syllabus section — how far along you are in each PbInfo category.
            </p>

            <div className="space-y-6">
                {categories.length === 0 && (
                    <p className="font-mono text-sm text-muted fade-rise">
                        No categories yet. Run a catalog sync after deploying, or wait for the daily
                        job.
                    </p>
                )}

                {categories.map((c, index) => (
                    <div
                        key={c.id}
                        className="fade-rise border-t border-line pt-5"
                        style={{ animationDelay: `${index * 40}ms` }}
                    >
                        <div className="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <Link
                                    href={route('problems.index', { category: c.id })}
                                    className="font-display text-2xl text-paper hover:text-accent transition-colors"
                                >
                                    {c.name}
                                </Link>
                                <div className="mt-1 font-mono text-[11px] text-muted">
                                    {c.solved} solved · {c.attempted} attempted · {c.total} total
                                </div>
                            </div>
                            <div className="font-display text-2xl text-accent">{c.percent}%</div>
                        </div>
                        <div className="mt-4 h-1.5 w-full overflow-hidden bg-white/5">
                            <div
                                className="progress-fill h-full bg-accent"
                                style={{ width: `${c.percent}%` }}
                            />
                        </div>
                    </div>
                ))}
            </div>
        </AppLayout>
    );
}
