# Server Actions

KAAL Realtime provides a lightweight way to execute backend logic directly from frontend interactions without writing any custom Javascript or setting up dedicated routes. This is called **Server Actions**.

## Defining a Server Action

You can define a Server Action anywhere in your application, typically in a `ServiceProvider` or a dedicated `routes/actions.php` file.

```php
use Kaal\Realtime\Facades\Realtime;
use App\Models\Post;

Realtime::action('archive-post', function ($request) {
    // Standard Laravel validation works automatically!
    $request->validate([
        'id' => 'required|exists:posts,id'
    ]);

    $post = Post::find($request->id);
    $post->archive();
    
    // You can return data to the frontend if needed
    return [
        'message' => 'Post archived successfully!',
        'post_id' => $post->id
    ];
});
```

## Triggering from the Frontend

To trigger a Server Action, simply add the `kaal-action` attribute to any clickable element (like a `<button>`). Any `data-*` attributes on the button will be automatically bundled and sent as the payload.

```blade
<button
    kaal-action="archive-post"
    data-id="{{ $post->id }}"
    class="btn-danger"
>
    Archive Post
</button>
```

When this button is clicked:
1. KAAL intercepts the click.
2. An AJAX POST request is made to `/kaal/realtime/action/archive-post`.
3. The payload `{ "id": 15 }` is sent based on `data-id`.
4. The backend closure executes.
5. If successful, any relevant models updated during the closure will automatically trigger broadcast events, causing any `@realtime` blocks listening to those models to refresh instantly.

## Loading States

Server Actions fully support KAAL's Loading State system. You can disable the button or change its text while the action is processing.

```blade
<button
    kaal-action="archive-post"
    data-id="15"
    kaal-loading-disable
    kaal-loading-text="Archiving..."
>
    Archive
</button>
```

When clicked, the button will become disabled and say "Archiving...". Once the network request finishes (success or failure), it reverts to its original state.

## Error Handling & Toasts

If your Server Action throws a `ValidationException` (e.g., from `$request->validate()`) or an `AuthorizationException`, KAAL automatically catches it and dispatches global events.

If you want an automatic toast notification on success or error, you can use the `kaal-success` and `kaal-error` attributes directly on the action button!

```blade
<button
    kaal-action="archive-post"
    data-id="15"
    kaal-success="Post has been archived!"
    kaal-error="Failed to archive post."
>
    Archive
</button>
```
