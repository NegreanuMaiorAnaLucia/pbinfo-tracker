import { useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';

export default function Login({ status }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        username: '',
        password: '',
        remember: true,
    });

    useEffect(() => {
        return () => reset('password');
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route('login'));
    };

    return (
        <>
            <Head title="Sign in" />
            <div className="relative min-h-screen overflow-hidden">
                <div
                    className="pointer-events-none absolute inset-0 opacity-40"
                    style={{
                        backgroundImage:
                            'linear-gradient(rgba(232,235,230,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(232,235,230,0.04) 1px, transparent 1px)',
                        backgroundSize: '48px 48px',
                    }}
                />
                <div className="relative mx-auto flex min-h-screen max-w-6xl flex-col justify-between px-5 py-10 lg:flex-row lg:items-stretch lg:gap-16 lg:py-0">
                    <section className="flex flex-1 flex-col justify-center py-10 fade-rise lg:py-20">
                        <p className="font-mono text-xs uppercase tracking-[0.28em] text-accent">
                            PbTrack
                        </p>
                        <h1 className="mt-5 max-w-xl font-display text-5xl font-extrabold leading-[0.95] tracking-tight text-paper sm:text-6xl lg:text-7xl">
                            Your PbInfo progress, finally readable.
                        </h1>
                        <p className="mt-6 max-w-md text-base leading-relaxed text-muted">
                            Sign in with your PbInfo account. We sync the problem catalog and your
                            journal so you can see how far you are — by syllabus, score, and status.
                        </p>
                    </section>

                    <section className="flex flex-1 items-center justify-center py-8 lg:justify-end lg:py-20">
                        <form
                            onSubmit={submit}
                            className="w-full max-w-md border border-line bg-ink-soft/70 p-8 backdrop-blur-sm fade-rise"
                            style={{ animationDelay: '120ms' }}
                        >
                            <h2 className="font-display text-2xl font-bold text-paper">Sign in</h2>
                            <p className="mt-2 font-mono text-xs text-muted">
                                Uses your PbInfo username + password to sync your progress only.
                            </p>

                            {status && (
                                <div className="mt-4 font-mono text-sm text-accent">{status}</div>
                            )}

                            <label className="mt-8 block">
                                <span className="font-mono text-[11px] uppercase tracking-wider text-muted">
                                    Username
                                </span>
                                <input
                                    type="text"
                                    value={data.username}
                                    autoComplete="username"
                                    autoFocus
                                    onChange={(e) => setData('username', e.target.value)}
                                    className="mt-2 w-full border-0 border-b border-line bg-transparent px-0 py-2.5 font-mono text-sm text-paper placeholder:text-muted/50 focus:border-accent focus:ring-0"
                                    placeholder="pbinfo_user"
                                />
                                {errors.username && (
                                    <div className="mt-2 font-mono text-xs text-danger">
                                        {errors.username}
                                    </div>
                                )}
                            </label>

                            <label className="mt-6 block">
                                <span className="font-mono text-[11px] uppercase tracking-wider text-muted">
                                    Password
                                </span>
                                <input
                                    type="password"
                                    value={data.password}
                                    autoComplete="current-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="mt-2 w-full border-0 border-b border-line bg-transparent px-0 py-2.5 font-mono text-sm text-paper placeholder:text-muted/50 focus:border-accent focus:ring-0"
                                    placeholder="••••••••"
                                />
                                {errors.password && (
                                    <div className="mt-2 font-mono text-xs text-danger">
                                        {errors.password}
                                    </div>
                                )}
                            </label>

                            <label className="mt-6 flex items-center gap-2 font-mono text-xs text-muted">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="rounded-sm border-line bg-transparent text-accent focus:ring-accent"
                                />
                                Keep me signed in
                            </label>

                            <button
                                type="submit"
                                disabled={processing}
                                className="mt-8 w-full bg-accent px-4 py-3 font-display text-sm font-bold uppercase tracking-[0.14em] text-ink transition hover:bg-accent-dim disabled:opacity-60"
                            >
                                {processing ? 'Connecting…' : 'Continue with PbInfo'}
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </>
    );
}
