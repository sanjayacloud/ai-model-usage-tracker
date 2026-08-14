<script setup lang="ts">
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'

interface Totals {
  records: number
  prompt_tokens: number
  completion_tokens: number
  total_tokens: number
  total_cost: number
  failures: number
}

interface TrendPoint {
  date: string
  records: number
  total_tokens: number
  total_cost: number
}

interface GroupRow {
  model?: string | null
  provider?: string | null
  operation?: string | null
  records: number
  total_tokens: number
  total_cost: number
}

interface ConsumerRow {
  trackable_type: string | null
  trackable_id: number | string | null
  records: number
  total_tokens: number
  total_cost: number
}

const props = defineProps<{
  range: { from: string; to: string }
  totals: Totals
  dailyTrend: TrendPoint[]
  byModel: GroupRow[]
  byProvider: GroupRow[]
  byOperation: GroupRow[]
  topConsumers: ConsumerRow[]
}>()

const maxTrendCost = computed(() =>
  Math.max(0.000001, ...props.dailyTrend.map((point) => point.total_cost)),
)

const money = (value: number) =>
  new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 4 }).format(value)

const number = (value: number) => new Intl.NumberFormat().format(value)

const setRange = (days: number) => {
  router.get(window.location.pathname, { days }, { preserveState: true, preserveScroll: true, replace: true })
}
</script>

<template>
  <Head title="AI Usage" />

  <div class="au-wrap">
    <header class="au-header">
      <div>
        <h1>AI Model Usage</h1>
        <p class="au-muted">{{ props.range.from }} &rarr; {{ props.range.to }}</p>
      </div>
      <div class="au-ranges">
        <button type="button" @click="setRange(7)">7d</button>
        <button type="button" @click="setRange(30)">30d</button>
        <button type="button" @click="setRange(90)">90d</button>
      </div>
    </header>

    <section class="au-cards">
      <div class="au-card">
        <span class="au-muted">Total cost</span>
        <strong>{{ money(props.totals.total_cost) }}</strong>
      </div>
      <div class="au-card">
        <span class="au-muted">Requests</span>
        <strong>{{ number(props.totals.records) }}</strong>
      </div>
      <div class="au-card">
        <span class="au-muted">Total tokens</span>
        <strong>{{ number(props.totals.total_tokens) }}</strong>
      </div>
      <div class="au-card">
        <span class="au-muted">Failures</span>
        <strong>{{ number(props.totals.failures) }}</strong>
      </div>
    </section>

    <section class="au-panel">
      <h2>Daily cost</h2>
      <div class="au-chart">
        <div v-for="point in props.dailyTrend" :key="point.date" class="au-bar-col" :title="`${point.date}: ${money(point.total_cost)}`">
          <div class="au-bar" :style="{ height: `${(point.total_cost / maxTrendCost) * 100}%` }" />
          <span class="au-bar-label">{{ point.date.slice(5) }}</span>
        </div>
        <p v-if="props.dailyTrend.length === 0" class="au-muted">No usage recorded in this range.</p>
      </div>
    </section>

    <div class="au-grid">
      <section class="au-panel">
        <h2>By model</h2>
        <table>
          <thead>
            <tr><th>Model</th><th>Reqs</th><th>Tokens</th><th>Cost</th></tr>
          </thead>
          <tbody>
            <tr v-for="row in props.byModel" :key="row.model ?? 'unknown'">
              <td>{{ row.model ?? '(unknown)' }}</td>
              <td>{{ number(row.records) }}</td>
              <td>{{ number(row.total_tokens) }}</td>
              <td>{{ money(row.total_cost) }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="au-panel">
        <h2>By provider</h2>
        <table>
          <thead>
            <tr><th>Provider</th><th>Reqs</th><th>Tokens</th><th>Cost</th></tr>
          </thead>
          <tbody>
            <tr v-for="row in props.byProvider" :key="row.provider ?? 'unknown'">
              <td>{{ row.provider ?? '(unknown)' }}</td>
              <td>{{ number(row.records) }}</td>
              <td>{{ number(row.total_tokens) }}</td>
              <td>{{ money(row.total_cost) }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="au-panel">
        <h2>Top consumers</h2>
        <table>
          <thead>
            <tr><th>Owner</th><th>Reqs</th><th>Cost</th></tr>
          </thead>
          <tbody>
            <tr v-for="row in props.topConsumers" :key="`${row.trackable_type}-${row.trackable_id}`">
              <td>{{ row.trackable_type }}#{{ row.trackable_id }}</td>
              <td>{{ number(row.records) }}</td>
              <td>{{ money(row.total_cost) }}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  </div>
</template>

<style scoped>
.au-wrap { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; font-family: ui-sans-serif, system-ui, sans-serif; }
.au-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.au-header h1 { font-size: 1.5rem; font-weight: 700; margin: 0; }
.au-muted { color: #6b7280; font-size: 0.85rem; }
.au-ranges button { border: 1px solid #d1d5db; background: #fff; border-radius: 0.5rem; padding: 0.35rem 0.75rem; margin-left: 0.35rem; cursor: pointer; }
.au-ranges button:hover { background: #f3f4f6; }
.au-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.au-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; display: flex; flex-direction: column; gap: 0.35rem; }
.au-card strong { font-size: 1.5rem; }
.au-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem 1.25rem; margin-bottom: 1.5rem; }
.au-panel h2 { font-size: 1rem; font-weight: 600; margin: 0 0 0.75rem; }
.au-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
.au-chart { display: flex; align-items: flex-end; gap: 4px; height: 180px; }
.au-bar-col { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; flex: 1; height: 100%; }
.au-bar { width: 100%; min-height: 2px; background: #6366f1; border-radius: 3px 3px 0 0; }
.au-bar-label { font-size: 0.6rem; color: #9ca3af; margin-top: 4px; white-space: nowrap; }
table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
th, td { text-align: left; padding: 0.4rem 0.5rem; border-bottom: 1px solid #f3f4f6; }
th { color: #6b7280; font-weight: 600; }
td:nth-child(n+2), th:nth-child(n+2) { text-align: right; }
</style>
