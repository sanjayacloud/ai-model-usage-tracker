<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const props = defineProps<{
    range: { from: string; to: string };
    totals: Totals;
    dailyTrend: TrendPoint[];
    byModel: GroupRow[];
    byProvider: GroupRow[];
    byOperation: GroupRow[];
    topConsumers: ConsumerRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'AI Usage', href: '/ai-usage' }],
    },
});

const rangeOptions = [7, 30, 90] as const;

const maxTrendCost = computed(() =>
    Math.max(0.000001, ...props.dailyTrend.map((point) => point.total_cost)),
);

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

function setRange(days: number): void {
    router.get(window.location.pathname, { days }, { preserveState: true, preserveScroll: true, replace: true });
}
</script>

<template>
    <Head title="AI Usage" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">AI Usage</h1>
            <p class="text-sm text-muted-foreground">Token and cost totals for model calls in this app.</p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted-foreground">{{ props.range.from }} to {{ props.range.to }}</p>
            <div class="flex gap-2">
                <button
                    v-for="days in rangeOptions"
                    :key="days"
                    type="button"
                    class="rounded-md border border-sidebar-border bg-background px-3 py-1.5 text-sm hover:bg-muted"
                    @click="setRange(days)"
                >
                    {{ days }}d
                </button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-sm text-muted-foreground">Total cost</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ money(props.totals.total_cost) }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-sm text-muted-foreground">Requests</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ number(props.totals.records) }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-sm text-muted-foreground">Total tokens</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ number(props.totals.total_tokens) }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-sm text-muted-foreground">Failures</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ number(props.totals.failures) }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <h2 class="mb-3 text-sm font-medium">Daily cost</h2>
            <p v-if="props.dailyTrend.length === 0" class="text-sm text-muted-foreground">No usage recorded in this range.</p>
            <div v-else class="flex h-52 items-end gap-1.5">
                <div
                    v-for="(point, index) in props.dailyTrend"
                    :key="point.date"
                    class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end"
                >
                    <span
                        class="w-full min-h-1 origin-bottom rounded-t-md shadow-sm transition-all duration-200 group-hover:brightness-110 group-hover:ring-2 group-hover:ring-foreground/20"
                        :class="['bg-chart-1', 'bg-chart-2', 'bg-chart-4', 'bg-chart-5'][index % 4]"
                        :style="{ height: `${Math.max(2, Math.round((point.total_cost / maxTrendCost) * 100))}%` }"
                        :title="`${point.date}: ${money(point.total_cost)} · ${number(point.total_tokens)} tokens`"
                    />
                    <span class="mt-1 truncate text-[10px] text-muted-foreground group-hover:font-medium group-hover:text-foreground">
                        {{ point.date.slice(5) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <h2 class="mb-3 text-sm font-medium">By model</h2>
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-xs text-muted-foreground">
                        <tr>
                            <th class="py-2 font-medium">Model</th>
                            <th class="py-2 text-right font-medium">Reqs</th>
                            <th class="py-2 text-right font-medium">Tokens</th>
                            <th class="py-2 text-right font-medium">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.byModel" :key="row.model ?? 'unknown'" class="border-b last:border-b-0">
                            <td class="py-2 font-medium">{{ row.model ?? '—' }}</td>
                            <td class="py-2 text-right tabular-nums">{{ number(row.records) }}</td>
                            <td class="py-2 text-right tabular-nums">{{ number(row.total_tokens) }}</td>
                            <td class="py-2 text-right tabular-nums">{{ money(row.total_cost) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <h2 class="mb-3 text-sm font-medium">By provider</h2>
                <table class="w-full text-left text-sm">
                    <thead class="border-b text-xs text-muted-foreground">
                        <tr>
                            <th class="py-2 font-medium">Provider</th>
                            <th class="py-2 text-right font-medium">Reqs</th>
                            <th class="py-2 text-right font-medium">Tokens</th>
                            <th class="py-2 text-right font-medium">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.byProvider" :key="row.provider ?? 'unknown'" class="border-b last:border-b-0">
                            <td class="py-2 font-medium">{{ row.provider ?? '—' }}</td>
                            <td class="py-2 text-right tabular-nums">{{ number(row.records) }}</td>
                            <td class="py-2 text-right tabular-nums">{{ number(row.total_tokens) }}</td>
                            <td class="py-2 text-right tabular-nums">{{ money(row.total_cost) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</template>
