# Clusters

Clusters allow you to group multiple realtime fragments into a single logical unit. Instead of refreshing fragments individually, you can refresh the entire cluster with a single command. 

This is incredibly useful for interfaces where multiple components depend on the same domain event but don't share a common HTML wrapper, like a chat interface, a dashboard, or a complex data table with an external summary counter.

## Defining a Cluster

You can define a cluster using the `@cluster` Blade directive. Any `@realtime` or `@preserve` blocks rendered inside this directive will automatically register themselves as belonging to the parent cluster.

```blade
@cluster('chat-room')

    <!-- The messages list -->
    @realtime([Message::class])
        @include('chat.messages')
    @endrealtime

    <!-- A completely separate block showing online users -->
    @realtime([User::class])
        @include('chat.online-users')
    @endrealtime

@endcluster
```

## Refreshing a Cluster

### From PHP / Controllers

You can refresh a cluster from anywhere in your backend using the `Cluster` facade. This will trigger a broadcast event that instructs the connected Javascript clients to reload all fragments within that cluster.

```php
use Kaal\Realtime\Facades\Cluster;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $message = Message::create($request->all());

        // Refresh the entire cluster at once!
        Cluster::refresh('chat-room');
        
        return response()->json(['status' => 'success']);
    }
}
```

### Dynamic Clusters

Clusters often need to be dynamic, such as tying them to a specific database record. You can pass variables directly to the `@cluster` directive.

```blade
@cluster("conversation-{$conversation->id}")
    ...
@endcluster
```

And refresh it dynamically:

```php
Cluster::refresh("conversation-{$message->conversation_id}");
```

### Broadcast Integration

If your models broadcast their events (like Laravel's Broadcastable events), you can override the `broadcastToClusters` method to have the model automatically refresh specific KAAL clusters when updated.

```php
class Message extends Model
{
    protected function broadcastToClusters(): array
    {
        return [
            "conversation-{$this->conversation_id}",
        ];
    }
}
```

## Nested Clusters

Clusters can be nested. If a fragment is inside multiple clusters, it will bind to the **closest** parent cluster.

```blade
@cluster('global-dashboard')
    ...
    @cluster('user-stats')
        ...
    @endcluster
@endcluster
```

Refreshing `global-dashboard` will NOT automatically refresh `user-stats` unless you explicitly refresh both, or if we define inheritance rules. By default, KAAL binds a fragment to its immediate parent cluster.

## Cluster API

The `Cluster` facade provides additional utilities for monitoring your clusters in production:

```php
// Get statistics like active connections and messages per second
$stats = Cluster::stats('chat-room');

// Get active internal channel names associated with this cluster
$channels = Cluster::channels('chat-room');
```
