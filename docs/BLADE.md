# KAAL Realtime Blade Documentation

KAAL Realtime provides a set of powerful Blade directives and HTML attributes designed to make your existing Laravel views realtime without rewriting them into complex Javascript components or adopting a heavy Livewire-like framework.

The philosophy is simple: **Keep your existing Blade views, just make them realtime.**

---

## 1. Core Directives

### `@realtime`

The `@realtime` directive defines a fragment of your view that should automatically refresh when certain Eloquent models are broadcasted as changed.

There are two modes for `@realtime`:

#### Auto-Realtime Mode (Recommended)

Pass an array of Eloquent model class strings. KAAL will automatically generate signed URLs and refresh the block whenever *any* instance of the specified models is broadcasted.

```blade
@realtime([App\Models\Message::class])
    <div class="chat-messages">
        @foreach($messages as $message)
            <div class="message">{{ $message->body }}</div>
        @endforeach
    </div>
@endrealtime
```

*Note: This relies on your controllers using the same route URL and query parameters to re-render the view.*

#### Stable Handler Mode

Pass a string ID. You are responsible for registering a server-side closure that tells KAAL how to render this specific block.

```blade
@realtime('user-stats')
    <div>Total Users: {{ $usersCount }}</div>
@endrealtime
```

*In your ServiceProvider:*
```php
use Kaal\Realtime\Facades\Realtime;

Realtime::register('user-stats', function() {
    return view('partials.stats', ['usersCount' => User::count()]);
}, [User::class]);
```

---

## 2. DOM State Preservation

When a `@realtime` block refreshes, its inner HTML is replaced. This can destroy the user's focus, cursor position, input values, and local JavaScript state. KAAL provides directives to solve this.

### `@preserve`

Wraps an element whose current state (and DOM node) should be **saved** right before a refresh, and **spliced back** in immediately after.

Use this for inputs, forms, and interactive UI elements inside a realtime block.

**Syntax:** `@preserve('unique-id', 'wrapper-tag')`

```blade
@preserve('chat-form', 'div')
    <form action="/send" method="POST">
        <input type="text" name="body" placeholder="Type a message...">
    </form>
@endpreserve
```
*   `unique-id`: (Optional) A unique identifier for the element. If omitted, KAAL auto-generates one.
*   `wrapper-tag`: (Optional) The HTML tag to use for the wrapper. Defaults to `div`.

### `@ignore`

Wraps an element that KAAL should **never** touch during a realtime refresh. The old DOM node is completely preserved and left exactly as-is.

Use this for complex JavaScript widgets (like Alpine.js components, rich text editors, or emoji pickers) that manage their own internal state.

```blade
@ignore('emoji-picker', 'div')
    <div x-data="{ open: false }">
        <!-- Alpine.js widget will not be reset by realtime updates -->
        <button @click="open = !open">Emojis</button>
    </div>
@endignore
```

---

## 3. AJAX Forms (`kaal-submit`)

To prevent full page reloads when submitting forms inside (or outside) realtime blocks, use the `kaal-submit` HTML attribute.

This intercepts standard Laravel forms, submits them via AJAX (`fetch`), and handles standard Laravel JSON validation errors.

### Basic Usage

Simply add `kaal-submit` to any standard `<form>`.

```blade
<form action="{{ route('messages.store') }}" method="POST" kaal-submit>
    @csrf
    <input type="text" name="body">
    <button type="submit">Send</button>
</form>
```

### Reset on Success (`kaal-reset`)

Add the `kaal-reset` attribute to automatically clear the form fields when the server responds with a `200 OK` or `3xx Redirect`.

```blade
<form action="/messages" method="POST" kaal-submit kaal-reset>
    <!-- Form will clear after a successful submission -->
</form>
```

---

## 4. Validation Errors

When a form is submitted via `kaal-submit`, KAAL expects your standard Laravel controller to perform validation (e.g., using `$request->validate()`).

If validation fails, Laravel automatically returns a `422 Unprocessable Entity` JSON response. KAAL catches this and maps the errors to specific elements in your form.

### `.kaal-error` & `data-error-for`

Add a container with the class `kaal-error` and the attribute `data-error-for="field_name"` anywhere inside your form. KAAL will automatically inject the first validation error string into this container.

```blade
<form action="/messages" method="POST" kaal-submit>
    @csrf
    
    <label>Message</label>
    <input type="text" name="body">
    
    <!-- KAAL will put the "body" validation error here -->
    <div class="kaal-error text-red-500 text-sm" data-error-for="body"></div>
    
    <button type="submit">Send</button>
</form>
```
*Note: Existing error messages are automatically cleared upon the next form submission attempt.*

---

## 5. JavaScript Events

The KAAL frontend runtime dispatches standard DOM CustomEvents on the `window` object during form submissions. You can listen to these to trigger Alpine.js state changes, toast notifications, or custom animations.

### `kaal:success`
Triggered when a `kaal-submit` form receives a successful response.
```javascript
window.addEventListener('kaal:success', (e) => {
    // e.detail contains the JSON response from the server (if any)
    console.log('Form submitted successfully!', e.detail);
});
```

### `kaal:error`
Triggered when a `kaal-submit` form fails (including 422 validation errors).
```javascript
window.addEventListener('kaal:error', (e) => {
    // e.detail.errors contains the validation error bag from Laravel
    console.error('Validation failed!', e.detail.errors);
});
```

---

## Putting It All Together (Chat Example)

Here is a complete, real-world example of an interactive chat input that handles realtime updates gracefully:

```blade
@realtime([App\Models\Message::class])
    <div class="chat-container">
        <!-- Messages List -->
        <div class="messages">
            @foreach($messages as $msg)
                <p>{{ $msg->body }}</p>
            @endforeach
        </div>
        
        <!-- Input Form (Preserved so typing isn't interrupted) -->
        @preserve('chat-form-wrapper')
            <form action="{{ route('messages.store') }}" method="POST" kaal-submit kaal-reset>
                @csrf
                <input type="hidden" name="receiver_id" value="{{ $user->id }}">
                
                <input type="text" name="body" placeholder="Message..." autocomplete="off">
                <div class="kaal-error" data-error-for="body"></div>
                
                <button type="submit">Send</button>
            </form>
        @endpreserve
    </div>
@endrealtime
```
