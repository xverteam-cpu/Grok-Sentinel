<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useLocaleText } from '@/Composables/useLocaleText';

const props = defineProps({
    prefilledToken: {
        type: String,
        default: '',
    },
    linkDetected: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const { t } = useLocaleText();

const form = useForm({
    code: '',
    token: props.prefilledToken,
});

const submit = () => {
    form.post(route('access.redeem'));
};
</script>

<template>
    <Head :title="t('access.title', 'Private Access')" />

    <div class="min-h-screen bg-slate-950 px-4 py-10 text-slate-100">
        <div class="mx-auto max-w-md rounded-3xl border border-cyan-500/30 bg-slate-900/80 p-8 shadow-2xl shadow-cyan-950/40">
            <div class="mb-8 space-y-3 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-cyan-400/40 bg-cyan-500/10">
                    <img src="/Sentinel-logo.png" alt="Sentinel" class="h-8 w-auto" />
                </div>
                <h1 class="text-2xl font-semibold tracking-[0.2em] text-cyan-200">{{ t('access.title', 'Private Access').toUpperCase() }}</h1>
                <p class="text-sm text-slate-300">
                    {{ t('access.description', 'This site is restricted. Redeem an admin-issued access code or device access link before login.') }}
                </p>
            </div>

            <div v-if="flashSuccess" class="mb-5 rounded-xl border border-emerald-500/30 bg-emerald-950/40 p-4 text-sm text-emerald-200">
                {{ flashSuccess }}
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label for="code" class="mb-2 block text-xs uppercase tracking-[0.2em] text-slate-400">{{ t('access.code', 'Access Code') }}</label>
                    <input
                        id="code"
                        v-model="form.code"
                        name="code"
                        type="text"
                        autocomplete="one-time-code"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-cyan-400"
                        placeholder="SENT-XXXX-XXXX"
                    />
                </div>

                <div>
                    <label for="token" class="mb-2 block text-xs uppercase tracking-[0.2em] text-slate-400">{{ t('access.token', 'Access Link Token') }}</label>
                    <input
                        id="token"
                        v-model="form.token"
                        name="token"
                        type="text"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none transition focus:border-cyan-400"
                        :placeholder="t('access.tokenPlaceholder', 'Paste token if link did not open automatically')"
                    />
                    <p v-if="linkDetected" class="mt-2 text-xs text-cyan-300">{{ t('access.linkDetected', 'Access link detected. Review the request and confirm this device manually.') }}</p>
                </div>

                <p v-if="form.errors.code" class="text-sm text-rose-400">{{ form.errors.code }}</p>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-cyan-400 px-4 py-3 text-sm font-semibold uppercase tracking-[0.2em] text-slate-950 transition hover:bg-cyan-300 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ form.processing ? t('access.validating', 'Validating Device...') : (linkDetected ? t('access.confirm', 'Confirm This Device') : t('access.unlock', 'Unlock This Device')) }}
                </button>
            </form>
        </div>
    </div>
</template>