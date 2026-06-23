# Getting Started with KAAL Realtime

Make your Laravel pages **realtime in under 10 minutes**.

## What is KAAL Realtime?

KAAL Realtime keeps your Laravel pages up-to-date **without page reloads**. When your data changes, browsers automatically update—instantly, securely, and with zero configuration.

```php
@realtime([Product::class, Inventory::class])
    <div id="products">
        @foreach($products as $product)
            <div class="card">{{ $product->name }}</div>
        @endforeach
    </div>
@endrealtime
```

That's it. When a product or inventory changes, **all browsers see the update in milliseconds**.

---

## Installation (2 minutes)

### 1. Install the package

```bash
composer require kaal/realtime
```

### 2. Publish the service provider

The package auto-registers via Composer. If needed, manually publish:

```bash
php artisan vendor:publish --provider="Kaal\Realtime\KaalRealtimeServiceProvider"
```

### 3. Done ✅

Minimal configuration: you may need to create and run migrations for any example models used in this guide. No external services or environment variables are required to use KAAL Realtime.

---

## Your First Realtime Page (5 minutes)

### Step 1: Create a simple model

```bash
php artisan make:model Product -m
```

Add to migration:

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

Run:

```bash
php artisan migrate
```

### Step 2: Create a controller

```bash
php artisan make:controller ProductController
```

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::paginate(10);
        return view('products.index', compact('products'));
    }
}
```

### Step 3: Create a realtime view

`resources/views/products/index.blade.php`:

```blade
<html>
<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <h1>Products</h1>

    @realtime([App\Models\Product::class])
        <ul id="product-list">
            @foreach($products as $product)
                <li id="product-{{ $product->id }}">
                    {{ $product->name }}
                </li>
            @endforeach
        </ul>
    @endrealtime
</body>
</html>
```

### Step 4: Add a route

`routes/web.php`:

```php
Route::get('/products', [\App\Http\Controllers\ProductController::class, 'index']);
```

### Step 5: Test it

Start your app:

```bash
php artisan serve
```

Open two browser tabs to `http://localhost:8000/products`.

**In one tab**, create a product via Tinker:

```bash
php artisan tinker
Product::create(['name' => 'Widget']);
exit
```

**Watch both tabs update instantly.** ✨

---

## How It Works

1. **You wrap content** with `@realtime([Model::class])`
2. **KAAL observes changes** to those models
3. **Browsers receive updates** securely and instantly
4. **Your HTML re-renders** without a page reload

That's it. No JavaScript. No API calls. No configuration.

---

## What Gets Updated?

- ✅ New records
- ✅ Updated records
- ✅ Deleted records
- ✅ Relationships
- ✅ Calculated properties

All automatically.

---

## Real-World Example

### Orders Dashboard

```blade
@realtime([Order::class, OrderItem::class])
    <table>
        <thead>
            <tr><th>Order</th><th>Total</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
                <tr>
                    <td>Order #{{ $order->id }}</td>
                    <td>${{ $order->total }}</td>
                    <td>{{ $order->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endrealtime
```

When an order status changes, **all staff see it instantly**.

---

## Common Questions

### Q: Do I need to configure anything?

No. KAAL works out of the box.

### Q: What if I'm already using Laravel Echo / Reverb?

KAAL is a **drop-in replacement**. No changes needed.

### Q: Does it work with pagination?

Yes. Each page updates independently and securely.

### Q: Is my data secure?

Yes. KAAL uses **signed URLs** by default. Each browser gets a unique token that expires.

### Q: Can I refresh specific blocks?

Yes. Advanced mode in docs.

### Q: What about multiple users?

Works out of the box. Each user sees their own paginated data.

---

## Troubleshooting

### Updates not showing?

1. Check the browser console for errors
2. Verify models have observers enabled
3. Restart your Laravel dev server

### Nothing appears on page load?

1. Clear browser cache
2. Run `php artisan view:clear`
3. Refresh the page

### Still having issues?

Check the [Troubleshooting Guide](./TROUBLESHOOTING.md).

---

## Next Steps

- Read the [Architecture Guide](./ARCHITECTURE.md)
- Explore [Advanced Usage](./ADVANCED.md)
- See [API Reference](./API.md)

---

## Support

- 📖 [Full Documentation](./README.md)
- 🐛 [Report Issues](https://github.com/kaal/realtime/issues)
- 💬 [Discussions](https://github.com/kaal/realtime/discussions)

---

**Happy building!** 🚀
