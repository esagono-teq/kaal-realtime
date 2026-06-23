# KAAL Realtime

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kaal/realtime.svg)](https://packagist.org/packages/kaal/realtime)
[![License](https://img.shields.io/packagist/l/kaal/realtime.svg)](https://github.com/kaal/realtime/blob/main/LICENSE)

**Keep Apps Always Live**

KAAL Realtime keeps Laravel pages up-to-date without page reloads. When your data changes, all open browsers automatically update — instantly, securely, and with a single command to install.

Make any Blade block realtime in **one line**:

```blade
@realtime([Product::class, Inventory::class])
    @foreach($products as $product)
        <div class="card">{{ $product->name }} — ${{ $product->price }}</div>
    @endforeach
@endrealtime
```

When a `Product` or `Inventory` changes, **all open browsers update instantly**. No page reload.

---

## ⚡ Features

- **Automatic Updates** — Add one Blade directive, get realtime pages
- **One-Command Setup** — `php artisan kaal:install` auto-provisions your app and writes your `.env`
- **No Reverb, No Echo** — Uses your running KAAL Cloud Gateway directly via native WebSocket
- **Secure by Default** — Signed URLs, token-based validation, and app-level isolation
- **Lightweight** — Works with any Laravel 11+ app, no breaking changes

---

## 📦 Requirements

| Dependency | Version |
|---|---|
| PHP | 8.2+ |
| Laravel | 11+ |
| [KAAL Cloud Gateway](https://github.com/kaal/cloud-alpha) | Running on `localhost:8081` |

> The KAAL Cloud Gateway must be running before you install. Start it with `npm start` inside the `gateway/` directory.

---

## 🚀 Installation

### Step 1 — Start the KAAL Cloud Gateway

```bash
cd kaal-cloud-alpha/gateway
npm install
npm start
```

The gateway runs at **http://localhost:8081** and the dashboard at **http://localhost:8081/dashboard**.

---

### Step 2 — Require the package

```bash
composer require kaal/realtime
```

---

### Step 3 — Run the installer

```bash
php artisan kaal:install
```

The installer automatically:

1. ✅ Publishes `config/kaal-realtime.php`
2. ✅ Publishes `resources/js/vendor/kaal-realtime/realtime.js`
3. ✅ **Creates a new App** on the running Gateway and retrieves its credentials
4. ✅ **Writes all variables** to your `.env` file:

```ini
BROADCAST_CONNECTION=kaal

VITE_KAAL_APP_ID=app-xxxxxxxx
VITE_KAAL_APP_KEY=key-xxxxxxxxxxxxxxxx
VITE_KAAL_APP_SECRET=sec-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

> If the Gateway is unreachable, the variables are written with blank values. You can fill them in manually from the [Dashboard](http://localhost:8081/dashboard).

---

### Step 4 — Import the frontend runtime

In `resources/js/app.js`:

```js
import './bootstrap';
import './vendor/kaal-realtime/realtime';
```

> **Do not import `./echo`** — KAAL does not use Laravel Echo or Pusher.

---

### Step 5 — Rebuild your assets

```bash
npm run build
# or during development:
npm run dev
```

---

## 🏗️ Usage

### 1. Add `HasRealtime` to your model

```php
use Kaal\Realtime\Traits\HasRealtime;

class Product extends Model
{
    use HasRealtime;
}
```

No observers. No events. No extra registration.

---

### 2. Wrap your Blade block with `@realtime`

```blade
@realtime([App\Models\Product::class])
    <ul>
        @foreach($products as $product)
            <li>{{ $product->name }}</li>
        @endforeach
    </ul>
@endrealtime
```

> **Requirement:** Your route must be a named `GET` route:
> ```php
> Route::get('/products', [ProductController::class, 'index'])
>     ->name('products.index');
> ```

---

### 3. Test it

```bash
php artisan serve
```

Open **two browser tabs** at `http://localhost:8000/products`. Then in a terminal:

```bash
php artisan tinker
Product::create(['name' => 'Widget']);
```

**Both tabs update instantly.** ✨

---

## 🎨 Interactive UI State

Realtime updates shouldn't destroy the user's typing experience or widget state. KAAL provides tools to seamlessly preserve interactions.

### 1. Preserve Inputs (`@preserve`)

Wrap any input, form, or UI element with `@preserve`. When a realtime refresh happens, the element's focus, cursor position, and typed value are perfectly retained.

```blade
@preserve('chat-input')
    <input type="text" name="message" placeholder="Type a message...">
@endpreserve
```

### 2. Ignore Widgets (`@ignore`)

Have an Alpine.js component, rich text editor, or emoji picker that manages its own state? Wrap it in `@ignore`. KAAL will completely skip updating this block during refreshes.

```blade
@ignore('emoji-picker')
    <div x-data="{ open: false }">...</div>
@endignore
```

### 3. AJAX Forms (`kaal-submit`)

Prevent full-page Laravel redirects when submitting forms. Add the `kaal-submit` attribute, and KAAL will intercept the submission, send it via native `fetch`, and render your Laravel validation errors automatically.

```blade
<form action="/messages" method="POST" kaal-submit kaal-reset>
    @csrf
    <input type="text" name="body">
    
    <!-- Validation errors automatically appear here -->
    <div class="kaal-error text-red-500" data-error-for="body"></div>
    
    <button type="submit">Send</button>
</form>
```
- `kaal-reset`: Automatically clears the form on a successful `200` or `302` response.
- `.kaal-error`: Automatically injects `422` JSON validation errors from your standard `$request->validate()` controller calls.

---

## 🔁 How It Works

```
Model::create() / update() / delete()
    ↓
HasRealtime broadcasts to KAAL Cloud Gateway  (HTTP POST /control/publish)
    ↓
Gateway pushes event to all subscribed WebSocket clients
    ↓
realtime.js finds matching @realtime blocks
    ↓
Browser fetches signed URL → your controller runs → block re-renders
```

Your controller is **completely untouched**:

```php
public function index(Request $request)
{
    $products = Product::where('shop', $request->shop)
        ->with(['category', 'prices'])
        ->paginate(20);

    return view('products.index', compact('products'));
}
```

---

## 💡 Examples

### Multiple models

```blade
@realtime([Order::class, OrderItem::class])
    @foreach($orders as $order)
        <div>Order #{{ $order->id }} — {{ $order->status }}</div>
    @endforeach
@endrealtime
```

### Multiple independent blocks

Each block updates separately when its models change:

```blade
@realtime([Order::class])
    @foreach($orders as $order) ... @endforeach
@endrealtime

@realtime([Product::class, Inventory::class])
    @foreach($products as $product) ... @endforeach
@endrealtime
```

### Stable Mode (named handlers)

For full control, register named handlers in a service provider:

```php
Realtime::register('products-list', ProductHandler::class, [
    Product::class,
    Inventory::class,
]);
```

```blade
@realtime('products-list')
```

---

## 🔒 Security

- **App Isolation** — Each Laravel app has its own `app_id`, `key`, and `secret`. The gateway enforces app-level isolation — no app can receive another app's events.
- **Signed URLs** — Refresh requests use `URL::temporarySignedRoute` (2-hour expiry).
- **Expired URLs** — The browser automatically reloads the page to get a fresh signed URL.
- **Auth Context** — `Auth::user()` is fully preserved on every refresh.

---

## ✅ Tested

| Scenario | Status |
|:---|:---:|
| Simple `Model::all()` | ✅ |
| Complex scoped queries with request params | ✅ |
| Eager loading (`with(['category', 'images', 'prices'])`) | ✅ |
| Pagination | ✅ |
| Authentication context | ✅ |
| Multiple browsers | ✅ |
| Concurrency (20 rapid updates, avg 286ms) | ✅ |
| Tampered signatures blocked (403) | ✅ |
| Invalid fragment IDs blocked (404) | ✅ |
| Expired URL triggers page reload | ✅ |
| Stable Mode backward-compatible | ✅ |

---

## 📚 Documentation

- **[Architecture](./docs/ARCHITECTURE.md)** — How KAAL works internally
- **[Security Guide](./docs/SECURITY.md)** — Full security model
- **[Advanced Usage](./docs/ADVANCED.md)** — Auth scoping, stable mode, etc.
- **[Troubleshooting](./docs/TROUBLESHOOTING.md)** — Common issues & solutions

---

## 🤝 Contributing

Contributions welcome! See [CONTRIBUTING.md](./CONTRIBUTING.md).

---

## License

MIT
