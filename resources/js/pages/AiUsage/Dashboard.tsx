import { Head, router } from '@inertiajs/react';

type Totals = {
    records: number;
    prompt_tokens: number;
    completion_tokens: number;
    total_tokens: number;
    total_cost: number;
    failures: number;
};

type TrendPoint = {
    date: string;
    records: number;
    total_tokens: number;
    total_cost: number;
};

type GroupRow = {
    model?: string | null;
    provider?: string | null;
    operation?: string | null;
    records: number;
    total_tokens: number;
    total_cost: number;
};

type ConsumerRow = {
    trackable_type: string | null;
    trackable_id: number | string | null;
    records: number;
    total_tokens: number;
    total_cost: number;
};

type Props = {
    range: { from: string; to: string };
    totals: Totals;
    dailyTrend: TrendPoint[];
    byModel: GroupRow[];
    byProvider: GroupRow[];
    byOperation: GroupRow[];
    topConsumers: ConsumerRow[];
};

function money(value: number): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 4,
    }).format(value);
}

function number(value: number): string {
    return new Intl.NumberFormat().format(value);
}

export default function Dashboard({ range, totals, dailyTrend, byModel, byProvider }: Props) {
    const maxTrendCost = Math.max(0.000001, ...dailyTrend.map((point) => point.total_cost));

    const setRange = (days: number) => {
        router.get(window.location.pathname, { days }, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <>
            <Head title="AI Usage" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">AI Usage</h1>
                    <p className="text-sm text-muted-foreground">Token and cost totals for model calls in this app.</p>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        {range.from} to {range.to}
                    </p>
                    <div className="flex gap-2">
                        {[7, 30, 90].map((days) => (
                            <button
                                key={days}
                                type="button"
                                className="rounded-md border border-sidebar-border bg-background px-3 py-1.5 text-sm hover:bg-muted"
                                onClick={() => setRange(days)}
                            >
                                {days}d
                            </button>
                        ))}
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">Total cost</p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums">{money(totals.total_cost)}</p>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">Requests</p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums">{number(totals.records)}</p>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">Total tokens</p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums">{number(totals.total_tokens)}</p>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">Failures</p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums">{number(totals.failures)}</p>
                    </div>
                </div>

                <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h2 className="mb-3 text-sm font-medium">Daily cost</h2>
                    {dailyTrend.length === 0 ? (
                        <p className="text-sm text-muted-foreground">No usage recorded in this range.</p>
                    ) : (
                        <div className="flex h-52 items-end gap-1.5">
                            {dailyTrend.map((point, index) => (
                                <div
                                    key={point.date}
                                    className="group flex h-full min-w-0 flex-1 flex-col items-center justify-end"
                                >
                                    <span
                                        className={[
                                            'min-h-1 w-full origin-bottom rounded-t-md shadow-sm transition-all duration-200 group-hover:brightness-110 group-hover:ring-2 group-hover:ring-foreground/20',
                                            ['bg-chart-1', 'bg-chart-2', 'bg-chart-4', 'bg-chart-5'][index % 4],
                                        ].join(' ')}
                                        style={{ height: `${Math.max(2, Math.round((point.total_cost / maxTrendCost) * 100))}%` }}
                                        title={`${point.date}: ${money(point.total_cost)}`}
                                    />
                                    <span className="mt-1 truncate text-[10px] text-muted-foreground group-hover:font-medium group-hover:text-foreground">
                                        {point.date.slice(5)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 className="mb-3 text-sm font-medium">By model</h2>
                        <table className="w-full text-left text-sm">
                            <thead className="border-b text-xs text-muted-foreground">
                                <tr>
                                    <th className="py-2 font-medium">Model</th>
                                    <th className="py-2 text-right font-medium">Reqs</th>
                                    <th className="py-2 text-right font-medium">Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                {byModel.map((row) => (
                                    <tr key={row.model ?? 'unknown'} className="border-b last:border-b-0">
                                        <td className="py-2 font-medium">{row.model ?? '—'}</td>
                                        <td className="py-2 text-right tabular-nums">{number(row.records)}</td>
                                        <td className="py-2 text-right tabular-nums">{money(row.total_cost)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </section>

                    <section className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 className="mb-3 text-sm font-medium">By provider</h2>
                        <table className="w-full text-left text-sm">
                            <thead className="border-b text-xs text-muted-foreground">
                                <tr>
                                    <th className="py-2 font-medium">Provider</th>
                                    <th className="py-2 text-right font-medium">Reqs</th>
                                    <th className="py-2 text-right font-medium">Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                {byProvider.map((row) => (
                                    <tr key={row.provider ?? 'unknown'} className="border-b last:border-b-0">
                                        <td className="py-2 font-medium">{row.provider ?? '—'}</td>
                                        <td className="py-2 text-right tabular-nums">{number(row.records)}</td>
                                        <td className="py-2 text-right tabular-nums">{money(row.total_cost)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </section>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'AI Usage', href: '/ai-usage' }],
};
