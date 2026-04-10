
# Sentinel - GROK Secure Gateway

A full-featured Laravel application with a futuristic, secure authentication interface.

## Live Server Deployment Commands

To deploy this on your live server, run the following sequence of commands in your terminal:

### 1. Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
```

### 2. Database Setup & Seeding

```bash
php artisan migrate --force
php artisan db:seed --class=ExclusiveUserSeeder --force
```

### Emergency Admin Access

To generate a one-time, expiring admin bootstrap link for the private site:

```bash
php artisan sentinel:admin-link --email=admin@sentinel.grok --minutes=10
```

Open the generated link on the device/browser you want to use for admin access. The link expires automatically and is invalid after first use.

### 3. Production Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Serve the Application

```bash
php artisan serve --port=8000
```

---

## Registered Access Keys (Credentials)

| Name | Neural Address (Email) | Access Key (Password) | Role |
| :--- | :--- | :--- | :--- |
| **System Admin** | `admin@sentinel.grok` | `AdminSecure2026` | Admin |
| **Sentinel Operator** | `operator@sentinel.grok` | `QuantumSecure2026` | Admin |
| **サワダ カズキ** | `sawada.kazuki@sentinel.grok` | `SawadaSecure2026!` | User |
| **Kenneth Nagac** | `kennethnagac18@gmail.com` | `kepler-452b` | User |
| **Nagac Clark** | `nagacclark@gmail.com` | `kepler-452b` | User |

---

## Key Features

- **Futuristic UI**: Sentinel login design with holographic 3D effects.
- **2-Minute Security Scan**: Real-time terminal log feed with a focus on deep security analysis.
- **Private Device Access**: Admin-generated access credentials can be redeemed and bound to a single device.
- **Multi-Language Support**: Automatic locale switching (English/Japanese) based on user location.
- **Responsive Design**: Optimized for both desktop and mobile devices.
- **Secure Authentication**: Built on Laravel 11 and Inertia.js.

