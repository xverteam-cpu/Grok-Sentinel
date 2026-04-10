<template>
    <div class="first-login-page">
        <div v-if="showSecurityFeed" class="access-box">
            <div class="logo">
                𝕏 <span :class="{ 'logo-complete': scanningComplete }">GROK</span> SENTINEL
            </div>

            <p class="portal-copy">LIVE SECURITY FEED INITIALIZED FOR SHIELD ACCESS</p>

            <div v-if="loaderVisible" class="loader">
                <div class="loader-bar" :style="{ width: loaderProgress + '%' }"></div>
            </div>

            <div class="terminal">
                <div v-for="(line, index) in terminalLines" :key="index" class="terminal-line">
                    {{ line }}
                </div>
            </div>

            <div v-if="loaderVisible" class="estimation-box">
                <p class="estimation-text">ESTIMATED TIME REMAINING: {{ formattedTimeRemaining }}</p>
            </div>

            <div v-if="scanningComplete" class="completion-box">
                <div class="completion-title">SCANNING COMPLETE</div>
                <p class="completion-copy">
                    Neural handshake established. All security protocols are active.
                </p>
                <button class="btn-access btn-success" @click="goToDashboard">
                    GO TO DASHBOARD
                </button>
            </div>
        </div>

        <div v-else class="intro-stage">
            <video
                ref="video"
                src="/make-only-the-glowing-x-crystal-logo-at-the-center.mp4"
                autoplay
                muted
                playsinline
                @ended="revealActivateButton"
                @error="revealActivateButton"
                class="intro-video"
            ></video>
            <button
                v-if="showButton"
                @click="activate"
                class="activate-button"
            >
                Activate
            </button>
        </div>
    </div>
</template>

<script>
import { usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref, computed } from 'vue';
import axios from 'axios';

const randomLogs = [
    '> Establishing encrypted neural handshake...',
    '> Firewall bypass authorized by Admin.',
    '> Threats detected on 横浜銀行 perimeter nodes...',
    '> Scanning account for malicious ghost scripts...',
    '> Analyzing packet headers for neural drift...',
    '> Synchronizing satellite link for uplink parity...',
    '> Scrubbing cache for unauthorized telemetry...',
    '> Verifying quantum signature against threat database...',
    '> Monitoring real-time traffic for IP spoofing...',
    '> Shifting cipher keys for improved entropy...',
    '> Hardening node against speculative execution...',
    '> Optimizing secure tunnel for high-bandwidth relay...',
    '> Rotating proxy nodes for stealth coverage...',
];

const SECURITY_FEED_DURATION_SECONDS = 120;

export default {
    setup() {
        const page = usePage();
        const video = ref(null);
        const showButton = ref(false);
        const showSecurityFeed = ref(false);
        const terminalLines = ref([]);
        const loaderProgress = ref(0);
        const loaderVisible = ref(false);
        const scanningComplete = ref(false);
        const timeLeft = ref(SECURITY_FEED_DURATION_SECONDS);
        let buttonFallbackTimeout = null;
        let logInterval = null;
        let timerInterval = null;

        const formattedTimeRemaining = computed(() => {
            const h = Math.floor(timeLeft.value / 3600);
            const m = Math.floor((timeLeft.value % 3600) / 60);
            const s = timeLeft.value % 60;
            return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        });

        const clearIntervals = () => {
            if (logInterval) window.clearInterval(logInterval);
            if (timerInterval) window.clearInterval(timerInterval);
        };

        const revealActivateButton = () => {
            showButton.value = true;
            if (buttonFallbackTimeout) {
                window.clearTimeout(buttonFallbackTimeout);
                buttonFallbackTimeout = null;
            }
        };

        const activate = async () => {
            try {
                await axios.post('/activate');
                showSecurityFeed.value = true;
                loaderVisible.value = true;
                scanningComplete.value = false;
                terminalLines.value = [];
                loaderProgress.value = 0;
                timeLeft.value = SECURITY_FEED_DURATION_SECONDS;

                clearIntervals();

                // Timer interval
                timerInterval = window.setInterval(() => {
                    if (timeLeft.value > 0) {
                        timeLeft.value -= 1;
                        loaderProgress.value = ((SECURITY_FEED_DURATION_SECONDS - timeLeft.value) / SECURITY_FEED_DURATION_SECONDS) * 100;
                    } else {
                        window.clearInterval(timerInterval);
                        loaderVisible.value = false;
                        scanningComplete.value = true;
                        terminalLines.value.push('> SCANNING COMPLETE. Handshake stable.');
                    }
                }, 1000);

                // Log interval - show a random log
                logInterval = window.setInterval(() => {
                    if (timeLeft.value > 0) {
                        const randomLog = randomLogs[Math.floor(Math.random() * randomLogs.length)];
                        terminalLines.value.push(randomLog);
                        if (terminalLines.value.length > 8) {
                            terminalLines.value.shift();
                        }
                    } else {
                        window.clearInterval(logInterval);
                    }
                }, 3000);

            } catch (error) {
                console.error('Error activating:', error);
            }
        };

        const goToDashboard = () => {
            window.location.href = page.props.auth?.user?.is_admin ? '/admin/dashboard' : '/dashboard';
        };

        onMounted(() => {
            buttonFallbackTimeout = window.setTimeout(() => {
                revealActivateButton();
            }, 5000);

            if (video.value?.readyState >= 2 && video.value.paused) {
                revealActivateButton();
            }
        });

        onBeforeUnmount(() => {
            clearIntervals();
            if (buttonFallbackTimeout) {
                window.clearTimeout(buttonFallbackTimeout);
            }
        });

        return {
            video,
            showButton,
            showSecurityFeed,
            terminalLines,
            loaderProgress,
            loaderVisible,
            scanningComplete,
            timeLeft,
            formattedTimeRemaining,
            activate,
            goToDashboard,
            revealActivateButton,
        };
    },
};
</script>

<style scoped>
:root {
    --grok-cyan: #00f2ff;
    --grok-blue: #00baff;
    --danger-red: #ff4b4b;
    --success-green: #00ff88;
    --bg-deep: #050505;
    --panel-bg: rgba(15, 15, 15, 0.95);
}

.first-login-page {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #050505;
    color: #fff;
    font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    padding: 1.5rem;
}

.first-login-page::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%),
        linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
    background-size: 100% 2px, 3px 100%;
    pointer-events: none;
    z-index: 1;
}

.intro-stage {
    position: relative;
    z-index: 10;
    text-align: center;
}

.intro-video {
    max-width: min(100%, 70rem);
    max-height: 70vh;
    border: 1px solid rgba(0, 186, 255, 0.3);
    border-radius: 1.5rem;
    box-shadow: 0 0 32px rgba(0, 186, 255, 0.2);
}

.activate-button,
.btn-access {
    width: 100%;
    padding: 0.95rem 1rem;
    border: none;
    font-weight: 700;
    letter-spacing: 0.2em;
    cursor: pointer;
    transition: 0.3s ease;
}

.activate-button {
    max-width: 16rem;
    margin-top: 2rem;
    background: #fff;
    color: #000;
}

.activate-button:hover,
.btn-access:hover {
    background: #00f2ff;
    box-shadow: 0 0 20px #00f2ff;
}

.access-box {
    position: relative;
    z-index: 10;
    width: min(100%, 28rem);
    background: rgba(15, 15, 15, 0.95);
    border: 1px solid #222;
    padding: 2.5rem;
    text-align: center;
    box-shadow: 0 0 50px rgba(0, 186, 255, 0.1);
}

.logo {
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: 0.32em;
}

.logo span {
    color: #00f2ff;
    transition: color 0.3s ease;
}

.logo span.logo-complete {
    color: #00ff88;
}

.portal-copy {
    margin: 0 0 1.875rem;
    color: #888;
    font-size: 0.75rem;
    letter-spacing: 0.08em;
}

.loader {
    width: 100%;
    height: 2px;
    margin: 1.25rem 0;
    background: #222;
    position: relative;
}

.loader-bar {
    height: 100%;
    background: #00f2ff;
    box-shadow: 0 0 10px #00f2ff;
    transition: width 0.3s ease;
}

.terminal {
    min-height: 9.5rem;
    margin-top: 1.25rem;
    padding: 0.95rem;
    border: 1px solid #222;
    background: #000;
    color: #00f2ff;
    font-family: Consolas, 'Courier New', monospace;
    font-size: 0.72rem;
    line-height: 1.6;
    text-align: left;
    overflow: hidden;
}

.terminal-line + .terminal-line {
    margin-top: 0.3rem;
}

.estimation-box {
    margin-top: 1rem;
    text-align: left;
}

.instruction-text {
    color: #888;
    font-size: 0.65rem;
    margin-top: 0.5rem;
    letter-spacing: 0.05em;
    font-style: italic;
}

.instruction-text { color: #888; font-size: 0.65rem; margin-top: 0.5rem; letter-spacing: 0.05em; font-style: italic; } .instruction-text { color: #888; font-size: 0.65rem; margin-top: 0.5rem; letter-spacing: 0.05em; font-style: italic; } .instruction-text { color: #888; font-size: 0.65rem; margin-top: 0.5rem; display: block; } .estimation-text {
    color: #00f2ff;
    font-family: Consolas, monospace;
    font-size: 0.8rem;
    letter-spacing: 0.1em;
}

.completion-box {
    margin-top: 1.25rem;
    border: 1px solid #00ff88;
    background: rgba(0, 255, 136, 0.1);
    padding: 1rem;
    text-align: left;
}

.completion-title {
    margin-bottom: 0.4rem;
    color: #00ff88;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
}

.completion-copy {
    margin: 0;
    color: #ccc;
    font-size: 0.7rem;
    line-height: 1.6;
}

.btn-success {
    margin-top: 1rem;
    background: #00ff88;
    color: #000;
    font-size: 0.72rem;
}

.btn-success:hover {
    background: #5cffb4;
    box-shadow: 0 0 18px rgba(0, 255, 136, 0.55);
}

@media (max-width: 640px) {
    .access-box {
        padding: 1.5rem;
    }

    .logo {
        font-size: 1.2rem;
        letter-spacing: 0.18em;
    }
}
</style>
