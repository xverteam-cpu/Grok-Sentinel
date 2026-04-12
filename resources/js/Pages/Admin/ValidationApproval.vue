<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue'
import { computed, ref } from 'vue'

defineProps({
  users: {
    type: Array,
    default: () => [],
  },
  pendingValidations: {
    type: Array,
    default: () => [],
  },
  pendingWithdrawals: {
    type: Array,
    default: () => [],
  },
  activeAccessGrantsCount: {
    type: Number,
    default: 0,
  },
})

const page = usePage()
const generatedAccess = computed(() => page.props.flash?.generatedAccess ?? null)
const flashSuccess = computed(() => page.props.flash?.success ?? null)
const pageErrors = computed(() => page.props.errors ?? {})
const generatingUserId = ref(null)
const fundingUserId = ref(null)
const lastFundedUserId = ref(null)
const fundAmounts = ref({})

const accessForm = useForm({
  name: '',
  email: '',
  password: '',
  withdrawable_balance: '1065000',
})

const decideValidation = (id, action) => {
  router.patch(`/admin/validations/${id}`, { action })
}

const decideWithdrawal = (id, action) => {
  router.patch(`/admin/withdrawals/${id}`, { action })
}

const generateAccessGrant = () => {
  accessForm.post(route('admin.access-grants.store'), {
    preserveScroll: true,
    onSuccess: () => {
      accessForm.reset('password')
    },
  })
}

const grantUserAccess = (userId) => {
  generatingUserId.value = userId

  router.post(route('admin.access-grants.users.store', userId), {}, {
    preserveScroll: true,
    onFinish: () => {
      generatingUserId.value = null
    },
  })
}

const addFunds = (userId) => {
  fundingUserId.value = userId
  lastFundedUserId.value = userId

  router.post(route('admin.users.funds.store', userId), {
    amount: fundAmounts.value[userId] ?? '',
  }, {
    preserveScroll: true,
    onSuccess: () => {
      fundAmounts.value[userId] = ''
    },
    onFinish: () => {
      fundingUserId.value = null
    },
  })
}

const fundErrorForUser = (userId) => {
  if (lastFundedUserId.value !== userId) {
    return null
  }

  return pageErrors.value.amount ?? null
}

const resetRegisteredDevices = () => {
  if (!window.confirm('This will clear all registered device bindings. Existing users will need to redeem their access again. Continue?')) {
    return
  }

  router.post(route('admin.access-grants.reset-devices'), {}, {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Admin Dashboard" />

  <div class="min-h-screen bg-slate-950 p-4 text-slate-100 md:p-8">
    <div class="mx-auto max-w-7xl space-y-8">
      <div class="flex justify-end">
        <LanguageSwitcher />
      </div>

      <header class="rounded-2xl border border-cyan-500/30 bg-slate-900/70 p-6">
        <h1 class="text-2xl font-semibold text-cyan-300">Admin Dashboard</h1>
        <p class="mt-1 text-sm text-slate-300">Pending Validation and Pending Withdrawal Queue</p>
      </header>

      <section class="rounded-2xl border border-amber-500/20 bg-slate-900/70 p-6">
        <div>
          <h2 class="text-lg font-semibold text-amber-300">Private Access Control</h2>
          <p class="mt-1 text-sm text-slate-300">Create the user credentials first, then generate a one-device access link. When the user opens the link, they still need to log in using the credentials you entered here.</p>
          <p class="mt-2 text-xs uppercase tracking-[0.2em] text-slate-400">Active device grants: {{ activeAccessGrantsCount }}</p>
        </div>

        <form class="mt-6" @submit.prevent="generateAccessGrant">
          <div class="grid gap-4 md:grid-cols-4">
            <div>
              <label class="mb-2 block text-xs uppercase tracking-[0.2em] text-slate-400">User Name</label>
              <input
                v-model="accessForm.name"
                type="text"
                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-amber-400"
                placeholder="User full name"
              >
              <p v-if="accessForm.errors.name" class="mt-2 text-sm text-rose-400">{{ accessForm.errors.name }}</p>
            </div>
            <div>
              <label class="mb-2 block text-xs uppercase tracking-[0.2em] text-slate-400">Login Email</label>
              <input
                v-model="accessForm.email"
                type="email"
                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-amber-400"
                placeholder="user@example.com"
              >
              <p v-if="accessForm.errors.email" class="mt-2 text-sm text-rose-400">{{ accessForm.errors.email }}</p>
            </div>
            <div>
              <label class="mb-2 block text-xs uppercase tracking-[0.2em] text-slate-400">Temporary Password</label>
              <input
                v-model="accessForm.password"
                type="text"
                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-amber-400"
                placeholder="Minimum 8 characters"
              >
              <p v-if="accessForm.errors.password" class="mt-2 text-sm text-rose-400">{{ accessForm.errors.password }}</p>
            </div>
            <div>
              <label class="mb-2 block text-xs uppercase tracking-[0.2em] text-slate-400">Withdrawable Balance</label>
              <input
                v-model="accessForm.withdrawable_balance"
                type="number"
                min="0"
                step="0.01"
                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-amber-400"
                placeholder="1065000"
              >
              <p v-if="accessForm.errors.withdrawable_balance" class="mt-2 text-sm text-rose-400">{{ accessForm.errors.withdrawable_balance }}</p>
            </div>
          </div>

          <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
            <button
              type="button"
              class="rounded-md border border-rose-500/40 bg-rose-950/40 px-4 py-2 text-sm font-semibold text-rose-200 hover:bg-rose-900/60"
              @click="resetRegisteredDevices"
            >
              Reset All Registered Devices
            </button>
            <button
              type="submit"
              class="rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="accessForm.processing"
            >
              {{ accessForm.processing ? 'Generating...' : 'Generate Access Link' }}
            </button>
          </div>
        </form>

        <div v-if="flashSuccess" class="mt-4 rounded-xl border border-emerald-500/30 bg-emerald-950/40 p-4 text-sm text-emerald-200">
          {{ flashSuccess }}
        </div>

        <div v-if="generatedAccess" class="mt-4 rounded-xl border border-amber-500/30 bg-slate-950/80 p-4 text-sm text-slate-200">
          <p><span class="text-slate-400">Name:</span> {{ generatedAccess.name }}</p>
          <p class="mt-2"><span class="text-slate-400">Email:</span> {{ generatedAccess.email }}</p>
          <p v-if="generatedAccess.password" class="mt-2"><span class="text-slate-400">Password:</span> {{ generatedAccess.password }}</p>
          <p class="mt-2"><span class="text-slate-400">Withdrawable Balance:</span> ¥{{ generatedAccess.withdrawable_balance }}</p>
          <p class="mt-2"><span class="text-slate-400">Code:</span> {{ generatedAccess.code }}</p>
          <p class="mt-2 break-all"><span class="text-slate-400">Link Token:</span> {{ generatedAccess.link_token }}</p>
          <p class="mt-2 break-all"><span class="text-slate-400">Link:</span> {{ generatedAccess.link }}</p>
        </div>
      </section>

      <section class="rounded-2xl border border-cyan-500/20 bg-slate-900/70 p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-cyan-300">All Users</h2>
            <p class="mt-1 text-sm text-slate-300">Complete account list for the secure gateway.</p>
          </div>
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Total users: {{ users.length }}</p>
        </div>

        <div v-if="users.length === 0" class="mt-4 rounded-lg border border-slate-700 bg-slate-900 p-4 text-sm text-slate-300">
          No users found.
        </div>

        <div v-else class="mt-4 overflow-hidden rounded-xl border border-slate-700">
          <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full divide-y divide-slate-800 bg-slate-950/80 text-sm">
              <thead class="bg-slate-900/90 text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                <tr>
                  <th class="px-4 py-3 font-medium">Name</th>
                  <th class="px-4 py-3 font-medium">Email</th>
                  <th class="px-4 py-3 font-medium">Role</th>
                  <th class="px-4 py-3 font-medium">Validation</th>
                  <th class="px-4 py-3 font-medium">Balance</th>
                  <th class="px-4 py-3 font-medium">First Login</th>
                  <th class="px-4 py-3 font-medium">Verified</th>
                  <th class="px-4 py-3 font-medium">Created</th>
                  <th class="px-4 py-3 font-medium">Funding</th>
                  <th class="px-4 py-3 font-medium">Access</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800 text-slate-200">
                <tr v-for="user in users" :key="`user-row-${user.id}`" class="align-top">
                  <td class="px-4 py-3 font-medium text-slate-100">{{ user.name }}</td>
                  <td class="px-4 py-3 text-slate-300">{{ user.email }}</td>
                  <td class="px-4 py-3">
                    <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="user.is_admin ? 'bg-amber-500/20 text-amber-200' : 'bg-cyan-500/20 text-cyan-200'">
                      {{ user.is_admin ? 'Admin' : 'User' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 capitalize text-slate-300">{{ user.validation_status || 'pending' }}</td>
                  <td class="px-4 py-3 text-slate-300">¥{{ user.withdrawable_balance }}</td>
                  <td class="px-4 py-3 text-slate-300">{{ user.is_first_login ? 'Yes' : 'No' }}</td>
                  <td class="px-4 py-3 text-slate-300">{{ user.email_verified_at ? 'Yes' : 'No' }}</td>
                  <td class="px-4 py-3 text-slate-300">{{ new Date(user.created_at).toLocaleString() }}</td>
                  <td class="px-4 py-3">
                    <div class="space-y-2">
                      <div class="flex gap-2">
                        <input
                          v-model="fundAmounts[user.id]"
                          type="number"
                          min="0.01"
                          step="0.01"
                          class="w-32 rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-slate-100 outline-none transition focus:border-cyan-400"
                          placeholder="Amount"
                        >
                        <button
                          class="rounded-md bg-cyan-500 px-3 py-2 text-xs font-semibold text-slate-950 hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-60"
                          :disabled="fundingUserId === user.id"
                          @click="addFunds(user.id)"
                        >
                          {{ fundingUserId === user.id ? 'Adding...' : 'Add Funds' }}
                        </button>
                      </div>
                      <p v-if="fundErrorForUser(user.id)" class="text-xs text-rose-400">{{ fundErrorForUser(user.id) }}</p>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <button
                      class="rounded-md bg-amber-500 px-3 py-2 text-xs font-semibold text-slate-950 hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-60"
                      :disabled="generatingUserId === user.id"
                      @click="grantUserAccess(user.id)"
                    >
                      {{ generatingUserId === user.id ? 'Generating...' : 'Grant Permission' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="grid gap-3 bg-slate-950/60 p-3 lg:hidden">
            <div
              v-for="user in users"
              :key="`user-card-${user.id}`"
              class="rounded-xl border border-slate-800 bg-slate-900/80 p-4"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="font-medium text-slate-100">{{ user.name }}</p>
                  <p class="mt-1 break-all text-sm text-slate-300">{{ user.email }}</p>
                </div>
                <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="user.is_admin ? 'bg-amber-500/20 text-amber-200' : 'bg-cyan-500/20 text-cyan-200'">
                  {{ user.is_admin ? 'Admin' : 'User' }}
                </span>
              </div>

              <div class="mt-4 grid grid-cols-2 gap-3 text-sm text-slate-300">
                <p><span class="text-slate-400">Validation:</span> {{ user.validation_status || 'pending' }}</p>
                <p><span class="text-slate-400">Balance:</span> ¥{{ user.withdrawable_balance }}</p>
                <p><span class="text-slate-400">First Login:</span> {{ user.is_first_login ? 'Yes' : 'No' }}</p>
                <p><span class="text-slate-400">Verified:</span> {{ user.email_verified_at ? 'Yes' : 'No' }}</p>
              </div>

              <p class="mt-3 text-xs uppercase tracking-[0.18em] text-slate-500">Created {{ new Date(user.created_at).toLocaleString() }}</p>

              <div class="mt-4 space-y-2">
                <div class="flex gap-2">
                  <input
                    v-model="fundAmounts[user.id]"
                    type="number"
                    min="0.01"
                    step="0.01"
                    class="min-w-0 flex-1 rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 outline-none transition focus:border-cyan-400"
                    placeholder="Fund amount"
                  >
                  <button
                    class="rounded-md bg-cyan-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="fundingUserId === user.id"
                    @click="addFunds(user.id)"
                  >
                    {{ fundingUserId === user.id ? 'Adding...' : 'Add Funds' }}
                  </button>
                </div>
                <p v-if="fundErrorForUser(user.id)" class="text-sm text-rose-400">{{ fundErrorForUser(user.id) }}</p>
              </div>

              <button
                class="mt-4 w-full rounded-md bg-amber-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="generatingUserId === user.id"
                @click="grantUserAccess(user.id)"
              >
                {{ generatingUserId === user.id ? 'Generating...' : 'Grant Permission' }}
              </button>
            </div>
          </div>
        </div>
      </section>

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
