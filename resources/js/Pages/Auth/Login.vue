<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const timeLeft = ref(180); // 3 minutes in seconds
const showSecurityFeed = ref(true);

const formatTime = (seconds) => {
    const hours = '00';
    const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
    const secs = (seconds % 60).toString().padStart(2, '0');
    return `${hours}:${mins}:${secs}`;
};

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

onMounted(() => {
    // Timer calculation
    const timer = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
        } else {
            showSecurityFeed.value = false;
            clearInterval(timer);
        }
    }, 1000);

    const canvas = document.getElementById('bg-canvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        if (ctx) {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            
            function drawGrid() {
                ctx.strokeStyle = 'rgba(0, 186, 255, 0.1)';
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
    }

    const overlay = document.getElementById('overlay');
    if (overlay) {
        setTimeout(() => {
            overlay.style.opacity = '0';
            setTimeout(() => {
                if (overlay) overlay.style.display = 'none';
            }, 1000);
        }, 1500);
    }
});
</script>

<template>
    <Head title="Log in" />

    <canvas id="bg-canvas"></canvas>

    <div class="scanning-overlay" id="overlay">
        <div class="scan-line"></div>
        <div class="logo" style="font-size:30px;">
            <img src="/sentinel-bg.png" alt="X" style="height: 40px; width: auto; display: inline-block; vertical-align: middle; margin-right: 10px;">
            GROK SENTINEL
        </div>
        <div style="font-size:12px; color:var(--grok-blue); letter-spacing:3px;">SYSTEM SCANNING...</div>
    </div>

    <div class="login-container">
        <div class="header">
            <div class="logo">
            <img src="/sentinel-bg.png" alt="X" style="height: 30px; width: auto; display: inline-block;">
            <span style="font-weight:100; color:#222;">|</span>
            <span>SENTINEL</span>
        </div>
            <div class="subtitle">Exclusive Access Gateway</div>
        </div>

        <div v-if="showSecurityFeed" class="security-feed-alert">
            <div class="feed-header">
                <span class="feed-dot"></span>
                LIVE SECURITY FEED INITIALIZED
            </div>
            <div class="feed-body">
                SHIELD ACCESS GRANTED
                <div class="feed-timer">ESTIMATED TIME REMAINING: {{ formatTime(timeLeft) }}</div>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div class="form-group">
                <label for="email" class="form-label">Authorized Email</label>
                <input id="email" name="email" type="email" class="form-input" placeholder="authorized_user@example.com" v-model="form.email" required autocomplete="username">
            </div>
            <div class="form-group" style="margin-bottom: 30px;">
                <label for="password" class="form-label">Private Key (Password)</label>
                <input id="password" name="password" type="password" class="form-input" placeholder="•••••••••••••" v-model="form.password" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn-login" :disabled="form.processing">
                <span v-if="form.processing">AUTHENTICATING...</span>
                <span v-else>AUTHENTICATE</span>
            </button>
        </form>

        <div class="status-panel">
            <div class="status-line">
                <span>Gateway Status:</span>
                <span class="status-ok">SECURE</span>
            </div>
            <div class="status-line">
                <span>Scan integrity:</span>
                <span class="status-ok">VERIFIED</span>
            </div>
            <div class="status-line" style="color:#444; font-size:10px;">
                ID: GS-ENTRANCE-A90 | AUTH: ELITE-ONLY
            </div>
        </div>
    </div>
</template>

<style>
:root {
    --bg-black: #000000;
    --panel-bg: rgba(10, 10, 10, 0.95);
    --border-color: #00BAFF;
    --grok-blue: #00BAFF;
    --grok-cyan: #00f2ff;
    --grok-green: #00ff88;
    --text-main: #fff;
    --text-muted: #666;
}

html, body {
    margin: 0;
    padding: 0;
    height: 100%;
    width: 100%;
    background-image: url('/unnamed.png');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

body {
    background-color: var(--bg-black);
    color: var(--text-main);
    margin: 0;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Background overlay for readability */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    z-index: 2;
    pointer-events: none;
}

/* Canvas 背景 (Quantum Grid) */
#bg-canvas {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    opacity: 0.15;
}

/* ログインコンテナ */
.login-container {
    width: 90%;
    max-width: 450px;
    background: var(--panel-bg);
    border: 2px solid var(--border-color);
    padding: 40px;
    border-radius: 4px;
    box-shadow: 
        0 0 80px rgba(0, 186, 255, 0.3),
        0 0 40px rgba(0, 242, 255, 0.2),
        inset 0 0 30px rgba(0, 186, 255, 0.05);
    position: relative;
    z-index: 10;
    text-align: center;
    transition: all 0.5s ease;
}

/* ヘッダー */
.header {
    margin-bottom: 40px;
}

.logo {
    font-size: 22px;
    font-weight: 900;
    letter-spacing: 4px;
    color: var(--text-main);
    margin-bottom: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.logo-x {
    color: var(--grok-cyan);
    text-shadow: 0 0 20px var(--grok-cyan);
}

.subtitle {
    font-size: 12px;
    color: var(--grok-blue);
    text-transform: uppercase;
    letter-spacing: 2px;
    text-shadow: 0 0 10px rgba(0, 186, 255, 0.5);
}

.security-feed-alert {
    background: rgba(0, 186, 255, 0.05);
    border: 1px solid var(--grok-blue);
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 30px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.feed-header {
    color: var(--grok-cyan);
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 1px;
    margin-bottom: 5px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
}

.feed-dot {
    width: 6px;
    height: 6px;
    background-color: #ff3e3e;
    border-radius: 50%;
    animation: pulse 1s infinite;
    box-shadow: 0 0 10px #ff3e3e;
}

.feed-body {
    color: #fff;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 1px;
}

.feed-timer {
    color: var(--grok-green);
    font-size: 10px;
    margin-top: 5px;
    font-family: monospace;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.3; }
    100% { opacity: 1; }
}

/* 入力フォーム */
.form-group {
    margin-bottom: 25px;
    text-align: left;
}

.form-label {
    font-size: 11px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
    display: block;
}

.form-input {
    width: 100%;
    background: rgba(0, 30, 60, 0.4);
    border: 1px solid var(--border-color);
    padding: 15px;
    color: var(--text-main);
    font-size: 14px;
    border-radius: 2px;
    box-sizing: border-box;
    transition: all 0.3s;
}

.form-input::placeholder {
    color: rgba(102, 102, 102, 0.7);
}

.form-input:focus {
    outline: none;
    border-color: var(--grok-cyan);
    background: rgba(0, 242, 255, 0.05);
    box-shadow: 0 0 15px rgba(0, 242, 255, 0.3);
}

/* ログインボタン */
.btn-login {
    width: 100%;
    background: linear-gradient(135deg, var(--grok-cyan), var(--grok-blue));
    color: var(--bg-black);
    border: 1px solid var(--grok-cyan);
    padding: 15px;
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 2px;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 10px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 0 20px rgba(0, 242, 255, 0.4);
}

.btn-login:hover:not(:disabled) {
    background: linear-gradient(135deg, var(--grok-green), var(--grok-cyan));
    box-shadow: 0 0 40px rgba(0, 255, 136, 0.6);
}

.btn-login:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* エントランス・ステータス */
.status-panel {
    font-family: monospace;
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 30px;
    border-top: 1px solid var(--border-color);
    padding-top: 20px;
}

.status-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.status-ok {
    color: var(--grok-green);
    text-shadow: 0 0 10px var(--grok-green);
}

/* ローディング演出 */
.scanning-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--bg-black);
    z-index: 100;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    transition: opacity 1s ease;
}

.scan-line {
    width: 100%;
    height: 2px;
    background: var(--grok-cyan);
    position: absolute;
    top: 0;
    animation: scan 2s infinite;
    box-shadow: 0 0 20px var(--grok-cyan);
}

@keyframes scan {
    0% { top: 0vh; }
    100% { top: 100vh; }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    body {
        overflow-y: auto !important;
        align-items: flex-start;
        padding: 40px 10px;
    }

    .login-container {
        width: 100%;
        max-width: 100%;
        padding: 25px 15px;
        margin-top: 20px;
    }

    .logo {
        font-size: 18px;
        gap: 8px;
    }

    .form-input {
        padding: 12px;
        font-size: 16px; /* Prevents auto-zoom on iOS */
    }

    .btn-login {
        padding: 12px;
        font-size: 13px;
    }

    .subtitle {
        font-size: 11px;
    }

    .status-panel {
        font-size: 10px;
    }
}

@media (max-width: 480px) {
    .login-container {
        width: 90%;
        padding: 20px;
    }

    .logo {
        font-size: 16px;
    }

    .header {
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-size: 10px;
    }

    .btn-login {
        padding: 12px;
        letter-spacing: 1px;
    }
}
</style>
