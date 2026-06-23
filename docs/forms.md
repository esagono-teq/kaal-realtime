# Forms & Interactions

KAAL makes handling form submissions, validation, and loading states completely seamless without requiring any custom Javascript. It intercepts standard HTML forms and processes them via AJAX, tightly integrating with Laravel's built-in validation system.

## The `kaal-submit` Attribute

To make any standard HTML form submit via AJAX, just add the `kaal-submit` attribute.

```blade
<form action="/posts/1/comments" method="POST" kaal-submit>
    @csrf
    <textarea name="body"></textarea>
    <button type="submit">Post Comment</button>
</form>
```

KAAL will:
1. Intercept the submission.
2. Send the data via a `fetch` request.
3. Automatically handle Laravel's `422 Unprocessable Entity` validation responses.
4. Broadcast `kaal:success` or `kaal:error` events.

## Loading States

Preventing double-submissions and providing user feedback is critical. KAAL provides declarative attributes to manage UI states during form submission.

### Disabling Elements

Use `kaal-loading-disable` to disable an element (like the submit button) while the form is in flight.

```blade
<button type="submit" kaal-loading-disable>
    Submit
</button>
```

### Changing Text

Use `kaal-loading-text` to swap the text of an element while loading.

```blade
<button type="submit" kaal-loading-disable kaal-loading-text="Sending...">
    Send Message
</button>
```

## Toast Notifications

You can easily trigger toast notifications by adding `kaal-success` or `kaal-error` attributes to your form.

```blade
<form 
    action="/profile" 
    method="POST" 
    kaal-submit
    kaal-success="Profile updated successfully!"
    kaal-error="Please check the form for errors."
>
    ...
</form>
```

These dispatch a `kaal:toast` CustomEvent on the `window`. You can listen to this event in your app's Javascript to trigger your favorite toast library (like SweetAlert, Toastify, or a custom Alpine component).

```javascript
window.addEventListener('kaal:toast', (event) => {
    const { type, message } = event.detail;
    // type is 'success' or 'error'
    
    MyToastLibrary.show(message, type);
});
```

## Validation Handling

When Laravel returns validation errors, KAAL automatically looks for elements with the `.kaal-error` class and a `data-error-for` attribute matching the failed field name. It will inject the first error message into that element.

```blade
<form action="/login" method="POST" kaal-submit>
    @csrf
    
    <input type="email" name="email">
    <div class="kaal-error text-red-500" data-error-for="email"></div>

    <input type="password" name="password">
    <div class="kaal-error text-red-500" data-error-for="password"></div>

    <button type="submit">Login</button>
</form>
```

## Auto-Resetting Forms

If you want the form to automatically clear its inputs upon a **successful** submission (useful for chat boxes or comment forms), add the `kaal-reset` attribute.

```blade
<form action="/messages" method="POST" kaal-submit kaal-reset>
    ...
</form>
```
