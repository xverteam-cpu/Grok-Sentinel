<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-cyan-400 text-start text-base font-medium text-cyan-400 bg-gray-800/50 focus:outline-none focus:text-cyan-300 focus:bg-gray-700/50 focus:border-cyan-300 transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-300 hover:text-cyan-400 hover:bg-gray-800/50 hover:border-cyan-400/50 focus:outline-none focus:text-cyan-400 focus:bg-gray-800/50 focus:border-cyan-400/50 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
