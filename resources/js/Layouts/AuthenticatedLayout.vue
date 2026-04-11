<script setup>
import { ref, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import { useLocaleText } from '@/Composables/useLocaleText';

const showingNavigationDropdown = ref(false);
const { t } = useLocaleText();

onMounted(() => {
    initBackground();
});

function initBackground() {
    const canvas = document.getElementById('bg-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    function drawGrid() {
        ctx.strokeStyle = 'rgba(0, 186, 255, 0.05)';
        ctx.lineWidth = 0.5;
        for (let i = 0; i < canvas.width; i += 50) {
            ctx.beginPath();
            ctx.moveTo(i, 0);
            ctx.lineTo(i, canvas.height);
            ctx.stroke();
        }
        for (let i = 0; i < canvas.height; i += 50) {
            ctx.beginPath();
            ctx.moveTo(0, i);
            ctx.lineTo(canvas.width, i);
            ctx.stroke();
        }
    }
    drawGrid();
}
</script>

<template>
    <div class="min-h-screen bg-black text-white">
        <canvas id="bg-canvas" class="fixed inset-0 z-0"></canvas>

        <nav class="border-b border-cyan-500/30 bg-gray-900/95 backdrop-blur-sm relative z-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')" class="flex items-center space-x-2">
                                <span class="text-2xl text-cyan-400">𝕏</span>
                                <span class="text-white font-bold">SENTINEL</span>
                            </Link>
                        </div>

                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink
                                :href="route('dashboard')"
                                :active="route().current('dashboard')"
                                class="text-cyan-400 hover:text-cyan-300"
                            >
                                {{ t('common.controlCenter', 'Control Center') }}
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center sm:gap-4">
                        <LanguageSwitcher />
                        <div class="relative ms-3">
                            <div class="text-cyan-400">{{ $page.props.auth.user.name }}</div>
                        </div>
                    </div>

                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown">
                            {{ t('common.menu', 'Menu') }}
                        </button>
                    </div>
                </div>

                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                            class="text-cyan-400"
                        >
                            {{ t('common.controlCenter', 'Control Center') }}
                        </ResponsiveNavLink>
                    </div>

                    <div class="border-t border-cyan-500/20 pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-white">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-cyan-400">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <div class="px-4 pb-3">
                                <LanguageSwitcher />
                            </div>
                            <ResponsiveNavLink :href="route('profile.edit')" class="text-white">
                                {{ t('common.profile', 'Profile') }}
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="text-red-400"
                            >
                                {{ t('common.logout', 'Log Out') }}
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <header
            class="bg-gray-900/50 border-b border-cyan-500/20 backdrop-blur-sm"
            v-if="$slots.header"
        >
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main class="relative z-10">
            <slot />
        </main>
    </div>
</template>

<style scoped>
</style>