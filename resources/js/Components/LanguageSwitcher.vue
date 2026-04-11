<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useLocaleText } from '@/Composables/useLocaleText'

const props = defineProps({
  tone: {
    type: String,
    default: 'dark',
  },
})

const page = usePage()
const { t } = useLocaleText()
const currentLocale = computed(() => page.props.locale ?? 'en')

const switchLocale = (locale) => {
  if (locale === currentLocale.value) {
    return
  }

  router.post(route('locale.update'), { locale }, {
    preserveScroll: true,
    preserveState: false,
  })
}

const wrapperClass = computed(() => props.tone === 'light'
  ? 'border-slate-200 bg-white/90 text-slate-900 shadow-sm'
  : 'border-cyan-500/30 bg-slate-900/80 text-slate-100 shadow-lg shadow-cyan-950/20')

const inactiveButtonClass = computed(() => props.tone === 'light'
  ? 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
  : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100')
</script>

<template>
  <div :class="wrapperClass" class="inline-flex items-center gap-1 rounded-full border px-2 py-1.5 backdrop-blur-sm">
    <span class="px-2 text-[10px] font-semibold uppercase tracking-[0.24em]">
      {{ t('common.language', 'Language') }}
    </span>
    <button
      type="button"
      class="rounded-full px-3 py-1 text-xs font-semibold transition"
      :class="currentLocale === 'en' ? 'bg-cyan-400 text-slate-950' : inactiveButtonClass"
      @click="switchLocale('en')"
    >
      {{ t('common.english', 'English') }}
    </button>
    <button
      type="button"
      class="rounded-full px-3 py-1 text-xs font-semibold transition"
      :class="currentLocale === 'ja' ? 'bg-cyan-400 text-slate-950' : inactiveButtonClass"
      @click="switchLocale('ja')"
    >
      {{ t('common.japanese', 'Japanese') }}
    </button>
  </div>
</template>