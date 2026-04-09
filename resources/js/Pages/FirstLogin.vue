<template>
    <div class="min-h-screen flex items-center justify-center bg-slate-950 text-white px-4">
        <div class="w-full max-w-4xl">
            <div v-if="showScan" class="rounded-3xl border border-cyan-500/30 bg-gray-950/95 p-8 shadow-2xl shadow-cyan-500/10 backdrop-blur-lg">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-semibold text-white">Sentinel Activation</h1>
                        <p class="mt-2 text-sm text-gray-400">Running final code and IP threat analysis.</p>
                    </div>
                    <div class="text-xs uppercase tracking-wider text-cyan-400">Secure Scan</div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl bg-slate-900/80 p-6 border border-cyan-500/20">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-xs uppercase tracking-wider text-cyan-400">Scan Progress</p>
                                <p class="text-sm text-gray-300">{{ status }}</p>
                            </div>
                            <div class="text-lg font-mono text-white">{{ Math.round(progress) }}%</div>
                        </div>
                        <div class="h-3 w-full rounded-full bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 via-indigo-500 to-blue-500 transition-all"
                                 :style="{ width: Math.min(progress, 100) + '%' }"></div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-900/80 p-6 border border-cyan-500/20">
                        <div class="mb-3 text-xs uppercase tracking-wider text-cyan-400">Scanning details</div>
                        <div class="space-y-3 text-sm text-gray-300">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-green-400 animate-pulse"></span>
                                <span>Checking remote IP reputation and routing integrity</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-cyan-400 animate-pulse"></span>
                                <span>Verifying code signatures against threat database</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-blue-400 animate-pulse"></span>
                                <span>{{ scanMessage }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-900/80 p-6 border border-cyan-500/20">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div class="rounded-2xl bg-slate-950/90 p-4 border border-slate-800">
                                <div class="text-xs uppercase tracking-wider text-cyan-400 mb-2">Threats Blocked</div>
                                <div class="text-2xl font-mono text-white">12,472</div>
                            </div>
                            <div class="rounded-2xl bg-slate-950/90 p-4 border border-slate-800">
                                <div class="text-xs uppercase tracking-wider text-cyan-400 mb-2">System Uptime</div>
                                <div class="text-2xl font-mono text-green-400">99.99%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center">
                <video
                    ref="video"
                    src="/make-only-the-glowing-x-crystal-logo-at-the-center.mp4"
                    autoplay
                    muted
                    @ended="showButton = true"
                    class="mx-auto max-w-full max-h-[70vh] rounded-3xl border border-cyan-500/30 shadow-2xl shadow-cyan-500/20"
                ></video>
                <button
                    v-if="showButton"
                    @click="activate"
                    class="mt-8 inline-flex items-center justify-center rounded-full bg-cyan-600 px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-500"
                >
                    Activate
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';
import axios from 'axios';

export default {
    setup() {
        const showButton = ref(false);
        const showScan = ref(false);
        const progress = ref(0);
        const status = ref('Initializing scan sequence...');
        const scanMessage = ref('Preparing secure channel for code/IP analysis...');
        let progressInterval = null;
        let statusInterval = null;

        const activate = async () => {
            try {
                await axios.post('/activate');
                showScan.value = true;
                progress.value = 0;
                status.value = 'Launching Sentinel scan...';
                scanMessage.value = 'Establishing secure telemetry stream.';

                const scanSteps = [
                    'Analyzing packet headers...',
                    'Scanning IP vectors for anomalies...',
                    'Correlating threat signatures...',
                    'Verifying code integrity...',
                    'Finalizing secure response plan...'
                ];
                let stepIndex = 0;

                statusInterval = window.setInterval(() => {
                    status.value = scanSteps[stepIndex % scanSteps.length];
                    scanMessage.value = scanSteps[(stepIndex + 1) % scanSteps.length];
                    stepIndex += 1;
                }, 4000);

                progressInterval = window.setInterval(() => {
                    progress.value = Math.min(progress.value + 3.33, 100);
                }, 1000);

                window.setTimeout(() => {
                    window.clearInterval(progressInterval);
                    window.clearInterval(statusInterval);
                    window.location.href = '/dashboard';
                }, 30000);
            } catch (error) {
                console.error('Error activating:', error);
            }
        };

        return {
            showButton,
            showScan,
            progress,
            status,
            scanMessage,
            activate,
        };
    },
};
</script>
