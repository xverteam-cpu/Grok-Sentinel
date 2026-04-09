<script setup>
import { Head, router } from '@inertiajs/vue3'

defineProps({
  pendingValidations: {
    type: Array,
    default: () => [],
  },
  pendingWithdrawals: {
    type: Array,
    default: () => [],
  },
})

const decideValidation = (id, action) => {
  router.patch(`/admin/validations/${id}`, { action })
}

const decideWithdrawal = (id, action) => {
  router.patch(`/admin/withdrawals/${id}`, { action })
}
</script>

<template>
  <Head title="Admin Dashboard" />

  <div class="min-h-screen bg-slate-950 p-4 text-slate-100 md:p-8">
    <div class="mx-auto max-w-7xl space-y-8">
      <header class="rounded-2xl border border-cyan-500/30 bg-slate-900/70 p-6">
        <h1 class="text-2xl font-semibold text-cyan-300">Admin Dashboard</h1>
        <p class="mt-1 text-sm text-slate-300">Pending Validation and Pending Withdrawal Queue</p>
      </header>

      <section class="rounded-2xl border border-cyan-500/20 bg-slate-900/70 p-6">
        <h2 class="text-lg font-semibold text-cyan-300">Pending Validation</h2>

        <div v-if="pendingValidations.length === 0" class="mt-4 rounded-lg border border-slate-700 bg-slate-900 p-4 text-sm text-slate-300">
          No pending validation requests.
        </div>

        <div v-else class="mt-4 space-y-3">
          <div
            v-for="request in pendingValidations"
            :key="`validation-${request.id}`"
            class="rounded-xl border border-slate-700 bg-slate-900 p-4"
          >
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div class="space-y-1 text-sm">
                <p><span class="text-slate-400">User:</span> {{ request.user?.name }} ({{ request.user?.email }})</p>
                <p><span class="text-slate-400">Method:</span> {{ request.method }}</p>
                <p><span class="text-slate-400">Amount:</span> ¥{{ request.amount }}</p>
                <p><span class="text-slate-400">Gift Card Code:</span> {{ request.gift_card_code }}</p>
              </div>

              <div class="flex items-center gap-2">
                <button
                  class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-500"
                  @click="decideValidation(request.id, 'approved')"
                >
                  Accept
                </button>
                <button
                  class="rounded-md bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-500"
                  @click="decideValidation(request.id, 'rejected')"
                >
                  Reject
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="rounded-2xl border border-cyan-500/20 bg-slate-900/70 p-6">
        <h2 class="text-lg font-semibold text-cyan-300">Pending Withdrawal</h2>

        <div v-if="pendingWithdrawals.length === 0" class="mt-4 rounded-lg border border-slate-700 bg-slate-900 p-4 text-sm text-slate-300">
          No pending withdrawal requests.
        </div>

        <div v-else class="mt-4 space-y-3">
          <div
            v-for="request in pendingWithdrawals"
            :key="`withdrawal-${request.id}`"
            class="rounded-xl border border-slate-700 bg-slate-900 p-4"
          >
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div class="space-y-1 text-sm">
                <p><span class="text-slate-400">User:</span> {{ request.user?.name }} ({{ request.user?.email }})</p>
                <p><span class="text-slate-400">Amount:</span> ¥{{ request.amount }}</p>
                <p><span class="text-slate-400">Destination:</span> {{ request.destination || 'N/A' }}</p>
                <p><span class="text-slate-400">Reference:</span> {{ request.reference || 'N/A' }}</p>
              </div>

              <div class="flex items-center gap-2">
                <button
                  class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-500"
                  @click="decideWithdrawal(request.id, 'approved')"
                >
                  Accept
                </button>
                <button
                  class="rounded-md bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-500"
                  @click="decideWithdrawal(request.id, 'rejected')"
                >
                  Reject
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
