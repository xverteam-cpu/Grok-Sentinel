<template>
    <div class="min-h-screen bg-slate-950 text-white py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 rounded-3xl border border-cyan-500/20 bg-gray-950/80 p-8 shadow-2xl shadow-cyan-500/10">
                <h1 class="text-3xl font-semibold">Admin Validation Console</h1>
                <p class="mt-2 text-sm text-gray-400">Approve or decline pending user validation requests.</p>
            </div>

            <div class="space-y-4">
                <div v-if="!users.length" class="rounded-3xl border border-slate-800 bg-slate-900/80 p-8 text-center text-gray-300">
                    No pending validation requests at the moment.
                </div>
                <div v-for="user in users" :key="user.id" class="rounded-3xl border border-cyan-500/20 bg-gray-950/80 p-6 shadow-lg shadow-cyan-500/5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-sm uppercase tracking-wider text-cyan-400 mb-2">Pending Validation</div>
                            <h2 class="text-xl font-semibold text-white">{{ user.name }}</h2>
                            <p class="text-sm text-gray-400">{{ user.email }}</p>
                        </div>
                        <div class="text-xs rounded-full border border-cyan-500/30 bg-slate-900 px-3 py-1 text-cyan-300">{{ user.validation_status }}</div>
                    </div>
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <form :action="`/admin/validations/${user.id}`" method="post" class="sm:inline-flex">
                            <input type="hidden" name="_token" :value="csrf" />
                            <input type="hidden" name="action" value="approve" />
                            <button type="submit" class="inline-flex items-center justify-center rounded-full bg-emerald-500 px-5 py-2 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400">Approve</button>
                        </form>
                        <form :action="`/admin/validations/${user.id}`" method="post" class="sm:inline-flex">
                            <input type="hidden" name="_token" :value="csrf" />
                            <input type="hidden" name="action" value="decline" />
                            <button type="submit" class="inline-flex items-center justify-center rounded-full bg-red-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-red-500">Decline</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    users: Array,
});

const { props: pageProps } = usePage();
const users = pageProps.value.users || [];
const csrf = pageProps.value.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
</script>
