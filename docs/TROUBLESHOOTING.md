# Troubleshooting KAAL Realtime

## Common Issues & Solutions

### Updates Not Appearing

**Symptom:** New/updated records don't appear on the page.

**Possible Causes:**

1. **Browser console shows errors**
   - Check browser console (F12 → Console tab)
   - Fix any JavaScript errors
   - Restart your dev server: `Ctrl+C` and `php artisan serve` again

2. **Model not in observer**
   - Verify the model is listed in `@realtime()`
   - Check the model has an Observer class
   - Verify the Observer fires the correct event

3. **Caching issue**
   - Clear config cache: `php artisan config:clear`
   - Clear view cache: `php artisan view:clear`
   - Clear browser cache: `Ctrl+Shift+Delete` → "Clear now"

4. **Wrong model namespace**
   - Use full namespaced path: `@realtime([App\Models\Product::class])`
   - NOT: `@realtime(['Product'])`

**Solution:**
```php
// ✅ Correct
@realtime([App\Models\Product::class])

// ❌ Wrong
@realtime(['Product'])
```

---

### "Invalid signature" Error

**Symptom:** Fragment refresh returns 403 Forbidden.

**Cause:** Signed URL expired or was tampered with.

**Solutions:**

1. Refresh the page (gets a new signed URL)
2. If it persists, restart your app:
   ```bash
   php artisan cache:clear
   ```

3. Check that `APP_KEY` is set in `.env`:
   ```bash
   php artisan key:generate
   ```

---

### Page Shows "No Updates" or Empty

**Symptom:** Initial page load shows content, but never updates.

**Possible Causes:**

1. **No records exist**
   - Create a record using Tinker:
     ```bash
     php artisan tinker
     Product::create(['name' => 'Test']);
     ```

2. **Incorrect observer setup**
   - Verify `app/Observers` exists
   - Verify models register observers in `AppServiceProvider`

3. **Broadcasting not configured**
   - Check `config/broadcasting.php`
   - Verify `BROADCAST_CONNECTION` is set in `.env`

**Debug:**
```bash
php artisan tinker
Product::create(['name' => 'Debug Test']);
# Go back to browser - should see it appear
```

---

### "Realtime block timeout"

**Symptom:** Page load times out or hangs.

**Cause:** Refresh URL generation is too slow.

**Solutions:**

1. Check database query performance:
   ```bash
   php artisan tinker
   DB::enableQueryLog();
   Product::paginate(10);
   dd(DB::getQueryLog());
   ```

2. Add indexes to frequently filtered columns:
   ```php
   Schema::table('products', function (Blueprint $table) {
       $table->index('category_id');
   });
   ```

3. Simplify the query in your controller:
   ```php
   // Use select() to limit columns
   $products = Product::select('id', 'name', 'price')
       ->paginate(10);
   ```

---

### Mixed Content Error (HTTPS)

**Symptom:** Browser console shows mixed content warning on HTTPS site.

**Cause:** WebSocket connection trying to use `ws://` instead of `wss://`

**Solution:** Set environment variables for HTTPS:
```bash
# .env
VITE_KAAL_PROTOCOL=wss
VITE_KAAL_PORT=443
```

---

### Network Errors in Console

**Symptom:** Console shows WebSocket connection errors.

**Possible Causes:**

1. **KAAL Cloud server not running**
   - Verify server is up:
     ```bash
     curl http://localhost:8081/health
     ```

2. **Wrong port or host**
   - Check `.env`:
     ```bash
     VITE_KAAL_HOST=localhost
     VITE_KAAL_PORT=8081
     ```

3. **Firewall blocking**
   - Check OS firewall settings
   - Allow port 8081 inbound

---

### Single Browser Works, Multi-Browser Doesn't

**Symptom:** Updates work on one browser, not when you open a second tab.

**Possible Causes:**

1. **Different pagination pages**
   - Products on page 1 in tab 1, page 2 in tab 2
   - Different pages don't see each other's updates
   - **This is by design** for security

2. **Race condition**
   - Rapid successive updates might miss one
   - Wait 1-2 seconds between updates

3. **Browser session issues**
   - Each browser gets a unique signed URL
   - Signed URLs expire after 24 hours
   - Reload the page to get a fresh URL

**Debug:**
```bash
# Test in single browser
# Open DevTools → Network tab
# Look for refresh requests when you create/update records
```

---

### "Page Refresh Failed"

**Symptom:** After update, console shows fetch error.

**Possible Causes:**

1. **Signed URL expired**
   - Reload the page

2. **Network disconnected**
   - Check internet connection
   - Check browser network tab for failed requests

3. **Server error**
   - Check Laravel logs:
     ```bash
     tail -f storage/logs/laravel.log
     ```

---

### Phantom Updates (False Positives)

**Symptom:** Page reloads but content is identical.

**Cause:** Observer fired but data didn't actually change.

**Solution:** Check observer logic:
```php
// Make sure observer only fires on data changes
public function updated(Product $product)
{
    // Only broadcast if specific fields changed
    if ($product->isDirty(['name', 'price'])) {
        $product->broadcast(new RealtimeUpdate());
    }
}
```

---

### Pagination Links Broken

**Symptom:** Pagination links appear but clicking them doesn't work.

**Cause:** Dynamic pagination needs special handling.

**Solution:**
```blade
@realtime([Product::class])
    @foreach($products as $product)
        <div>{{ $product->name }}</div>
    @endforeach

    <!-- Make pagination part of the realtime block -->
    {{ $products->links() }}
@endrealtime
```

---

## Performance Issues

### Page Load Slow

1. **Reduce records**: Paginate to 10-20 per page
2. **Use indexes**: Add indexes to frequently queried columns
3. **Profile queries**:
   ```bash
   php artisan tinker
   DB::enableQueryLog();
   # ... do your operation
   dd(DB::getQueryLog());
   ```

### Too Many Updates

If your data changes very frequently:

```blade
<!-- Use debouncing for real-time blocks -->
@realtime([Product::class])
    <!-- Content -->
@endrealtime
```

---

## Development vs Production

### Development

Works out of the box. KAAL auto-detects your setup.

### Production

Make sure to set:

```bash
# .env
APP_ENV=production
BROADCAST_DRIVER=kaal
VITE_BROADCAST_DRIVER=kaal
VITE_KAAL_HOST=your.domain.com
VITE_KAAL_PORT=443
VITE_KAAL_PROTOCOL=wss
```

And deploy KAAL Cloud gateway on a separate server or container.

---

## Still Stuck?

1. Check [Documentation](../GETTING_STARTED.md)
2. Review [API Reference](./API.md)
3. Open an [Issue](https://github.com/kaal/realtime/issues)
4. Join [Discussions](https://github.com/kaal/realtime/discussions)

We're here to help! 🚀
