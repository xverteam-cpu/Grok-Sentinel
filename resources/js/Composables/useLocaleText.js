import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const translations = {
    ja: {
        common: {
            menu: 'メニュー',
            profile: 'プロフィール',
            logout: 'ログアウト',
            controlCenter: 'コントロールセンター',
            sentinelScan: 'Sentinelスキャン',
            continue: '続行',
            cancel: 'キャンセル',
        },
        login: {
            title: 'ログイン',
            scanning: 'システムをスキャンしています...',
            gateway: '限定アクセスゲートウェイ',
            liveFeed: 'ライブセキュリティフィードを初期化しました',
            shieldGranted: 'シールドアクセスを許可しました',
            estimated: '推定残り時間',
            authorizedEmail: '認証済みメールアドレス',
            authorizedPlaceholder: 'authorized_user@example.com',
            password: '秘密鍵（パスワード）',
            authenticate: '認証する',
            authenticating: '認証中...',
            gatewayStatus: 'ゲートウェイ状態:',
            secure: '保護済み',
            scanIntegrity: 'スキャン整合性:',
            verified: '確認済み',
        },
        access: {
            title: 'プライベートアクセス',
            description: 'このサイトは制限されています。ログイン前に、管理者発行のアクセスコードまたはデバイスアクセスリンクを利用してください。',
            code: 'アクセスコード',
            token: 'アクセスリンクトークン',
            tokenPlaceholder: 'リンクが自動で開かない場合はトークンを貼り付けてください',
            linkDetected: 'アクセスリンクを検出しました。内容を確認し、このデバイスを手動で承認してください。',
            unlock: 'このデバイスのロックを解除',
            confirm: 'このデバイスを確認',
            validating: 'デバイス確認中...',
        },
        firstLogin: {
            liveFeed: 'シールドアクセス用ライブセキュリティフィードを初期化しました',
            estimated: '推定残り時間',
            scanningComplete: 'スキャン完了',
            handshake: 'ニューラルハンドシェイクが確立されました。すべてのセキュリティプロトコルが有効です。',
            goToDashboard: 'ダッシュボードへ進む',
            activate: '有効化',
        },
        dashboard: {
            availableFunds: '利用可能資金',
            unverified: '未検証',
            validateFunds: '資金を検証',
            withdrawableBalance: '出金可能残高',
            withdraw: '出金',
            verified: '確認済み',
            confirmBankDetails: '銀行情報を確認',
            withdrawAmount: '出金額',
            saveBankDetails: '銀行情報を保存',
            continueWithdrawal: '出金を続行',
            transferFailed: '送金失敗',
            transferInProgress: '送金処理中',
            scanTitle: 'Sentinelスキャン',
            forensicLiveFeed: 'フォレンジックライブフィード',
            threatAnalysis: '脅威分析',
            askSentinel: 'Ask Sentinel',
            secureValidationPortal: 'セキュア検証ポータル',
            timeRemaining: '残り時間',
            bankName: '銀行名',
            japaneseBankName: '日本の銀行名',
            branchCode: '支店コード',
            routingNumber: 'ルーティング番号',
            accountNumber: '口座番号',
            accountHolder: '口座名義人',
            accountNameHolder: '口座名義',
        },
    },
};

export function useLocaleText() {
    const page = usePage();
    const locale = computed(() => page.props.locale ?? 'en');

    const t = (key, fallback) => {
        const segments = key.split('.');
        let current = translations[locale.value];

        for (const segment of segments) {
            current = current?.[segment];
        }

        return current ?? fallback;
    };

    return {
        locale,
        isJapaneseLocale: computed(() => locale.value === 'ja'),
        t,
    };
}