<?php

namespace Kaal\Realtime;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Kaal\Realtime\Console\InstallCommand;
use Kaal\Realtime\Events\ModelChanged;
use Kaal\Realtime\Listeners\ModelChangedListener;
use Kaal\Realtime\Registry\RealtimeRegistry;

class KaalRealtimeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('kaal-realtime', function ($app) {
            return new RealtimeRegistry();
        });

        $this->app->singleton('kaal-cluster', function ($app) {
            return new \Kaal\Realtime\Cluster();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            ModelChanged::class,
            ModelChangedListener::class
        );

        \Illuminate\Support\Facades\Broadcast::extend('kaal', function ($app, $config) {
            return new \Kaal\Realtime\Broadcasting\KaalBroadcaster($config);
        });

        // Inject kaal config into the host app if it's missing
        if (is_null(config('broadcasting.connections.kaal'))) {
            \Illuminate\Support\Facades\Config::set('broadcasting.connections.kaal', [
                'driver' => 'kaal',
                'app_id' => env('VITE_KAAL_APP_ID'),
                'key' => env('VITE_KAAL_APP_KEY'),
                'secret' => env('VITE_KAAL_APP_SECRET'),
                'url' => env('KAAL_GATEWAY_URL', 'https://ws.kaalrealtime.com'),
                'api_url' => env('KAAL_API_URL', 'https://api.kaalrealtime.com'),
            ]);
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'kaal');

        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->pushMiddlewareToGroup('web', \Kaal\Realtime\Http\Middleware\ExtractRealtimeFragment::class);

        $this->publishes([
            __DIR__.'/../config/kaal-realtime.php' => config_path('kaal-realtime.php'),
        ], 'kaal-realtime-config');

        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/vendor/kaal-realtime'),
        ], 'kaal-realtime-assets');

        $this->publishes([
            __DIR__.'/../resources/js/pwa' => public_path('vendor/kaal-realtime/pwa'),
        ], 'kaal-realtime-pwa-assets');

        $this->publishes([
            __DIR__.'/../resources/views/pwa/offline.blade.php' => resource_path('views/vendor/kaal/pwa/offline.blade.php'),
            __DIR__.'/../resources/views/components/pwa' => resource_path('views/vendor/kaal/components/pwa'),
        ], 'kaal-realtime-views');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                \Kaal\Realtime\Console\InspectCommand::class,
                \Kaal\Realtime\Console\StatsCommand::class,
                \Kaal\Realtime\Console\PwaCommand::class,
            ]);
        }

        Blade::directive('realtime', function ($expression) {
            return "<?php 
                if (!isset(\$__kaal_stack)) \$__kaal_stack = [];
                \$__args = fn(\$idOrModels, \$strategy = 'replace') => ['idOrModels' => \$idOrModels, 'strategy' => \$strategy];
                \$__kaal_params = \$__args({$expression});
                \$__kaal_strategy = \$__kaal_params['strategy'];

                if (is_array(\$__kaal_params['idOrModels'])) {
                    // Auto mode
                    \$__kaal_models = implode(',', \$__kaal_params['idOrModels']);
                    static \$__kaal_counter = 0;
                    \$__kaal_counter++;
                    \$__kaal_fragment = 'kaal-fragment-' . \$__kaal_counter;
                    array_push(\$__kaal_stack, 'auto');
                    
                    \$__kaal_route_name = request()->route() ? request()->route()->getName() : null;
                    if (\$__kaal_route_name) {
                        \$__kaal_url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                            \$__kaal_route_name,
                            now()->addHours(2),
                            array_merge(
                                request()->route()->parameters(),
                                array_diff_key(request()->query(), ['signature' => 1, 'expires' => 1])
                            )
                        );
                    } else {
                        // Fallback for unnamed routes
                        \$__kaal_params = array_merge(
                            request()->query(),
                            ['expires' => now()->addHours(2)->getTimestamp()]
                        );
                        unset(\$__kaal_params['signature']);
                        ksort(\$__kaal_params);
                        \$__kaal_query_string = \Illuminate\Support\Arr::query(\$__kaal_params);
                        \$__kaal_original = rtrim(request()->url() . '?' . \$__kaal_query_string, '?');
                        \$__kaal_key = config('app.key');
                        \$__kaal_key = is_array(\$__kaal_key) ? \$__kaal_key[0] : \$__kaal_key;
                        \$__kaal_signature = hash_hmac('sha256', \$__kaal_original, \$__kaal_key);
                        \$__kaal_url = \$__kaal_original . (str_contains(\$__kaal_original, '?') ? '&' : '?') . 'signature=' . \$__kaal_signature;
                    }
                    // Cluster support
                    \$__kaal_cluster_attr = '';
                    if (!empty(\$__kaal_cluster_stack)) {
                        \$__kaal_cluster_attr = ' data-kaal-parent-cluster=\"' . htmlspecialchars(end(\$__kaal_cluster_stack)) . '\"';
                    }

                    echo '<div data-kaal-fragment=\"' . \$__kaal_fragment . '\" data-kaal-models=\"' . \$__kaal_models . '\" data-kaal-url=\"' . htmlspecialchars(\$__kaal_url) . '\" data-kaal-strategy=\"' . htmlspecialchars(\$__kaal_strategy) . '\"' . \$__kaal_cluster_attr . '>';
                    \$__env->startFragment(\$__kaal_fragment);
                } else {
                    // Manual mode
                    array_push(\$__kaal_stack, 'manual');
                    \$__realtime_id = (string) \$__kaal_params['idOrModels'];
                    \$__realtime_block = \Kaal\Realtime\Facades\Realtime::get(\$__realtime_id);
                    \$__realtime_models = \$__realtime_block ? implode(',', \$__realtime_block['models']) : '';
                    
                    // Cluster support
                    \$__kaal_cluster_attr = '';
                    if (!empty(\$__kaal_cluster_stack)) {
                        \$__kaal_cluster_attr = ' data-kaal-parent-cluster=\"' . htmlspecialchars(end(\$__kaal_cluster_stack)) . '\"';
                    }
                    
                    echo '<div data-realtime-id=\"' . \$__realtime_id . '\" data-models=\"' . \$__realtime_models . '\" data-kaal-strategy=\"' . htmlspecialchars(\$__kaal_strategy) . '\"' . \$__kaal_cluster_attr . '>';
                }
            ?>";
        });

        Blade::directive('endrealtime', function () {
            return "<?php 
                \$__kaal_mode = array_pop(\$__kaal_stack);
                if (\$__kaal_mode === 'auto') {
                    echo \$__env->stopFragment();
                }
                echo '</div>'; 
            ?>";
        });

        Blade::directive('preserve', function ($expression) {
            $args = array_map('trim', explode(',', $expression));
            $idRaw = (isset($args[0]) && $args[0] !== '') ? $args[0] : 'null';
            $tagRaw = (isset($args[1]) && $args[1] !== '') ? $args[1] : "'div'";
            
            return "<?php
                if (!isset(\$__kaal_preserve_stack)) \$__kaal_preserve_stack = [];
                \$__kaal_preserve_tag = {$tagRaw};
                \$__kaal_preserve_id = {$idRaw};
                if (is_null(\$__kaal_preserve_id) || \$__kaal_preserve_id === '') {
                    static \$__kaal_preserve_counter = 0;
                    \$__kaal_preserve_counter++;
                    \$__kaal_preserve_id = 'kaal-preserve-' . \$__kaal_preserve_counter;
                }
                array_push(\$__kaal_preserve_stack, \$__kaal_preserve_tag);
                echo '<' . \$__kaal_preserve_tag . ' data-kaal-preserve=\"' . e(\$__kaal_preserve_id) . '\">';
            ?>";
        });

        Blade::directive('endpreserve', function () {
            return "<?php
                \$__kaal_preserve_tag = array_pop(\$__kaal_preserve_stack);
                echo '</' . \$__kaal_preserve_tag . '>';
            ?>";
        });

        Blade::directive('ignore', function ($expression) {
            $args = array_map('trim', explode(',', $expression));
            $idRaw = (isset($args[0]) && $args[0] !== '') ? $args[0] : 'null';
            $tagRaw = (isset($args[1]) && $args[1] !== '') ? $args[1] : "'div'";
            
            return "<?php
                if (!isset(\$__kaal_ignore_stack)) \$__kaal_ignore_stack = [];
                \$__kaal_ignore_tag = {$tagRaw};
                \$__kaal_ignore_id = {$idRaw};
                if (is_null(\$__kaal_ignore_id) || \$__kaal_ignore_id === '') {
                    static \$__kaal_ignore_counter = 0;
                    \$__kaal_ignore_counter++;
                    \$__kaal_ignore_id = 'kaal-ignore-' . \$__kaal_ignore_counter;
                }
                array_push(\$__kaal_ignore_stack, \$__kaal_ignore_tag);
                echo '<' . \$__kaal_ignore_tag . ' data-kaal-ignore=\"' . e(\$__kaal_ignore_id) . '\">';
            ?>";
        });

        Blade::directive('endignore', function () {
            return "<?php
                \$__kaal_ignore_tag = array_pop(\$__kaal_ignore_stack);
                echo '</' . \$__kaal_ignore_tag . '>';
            ?>";
        });

        Blade::directive('presence', function ($expression) {
            // Supports:
            //   @presence('room')
            //   @presence('room', $users)   ← $users is the server-rendered fallback list
            // Emits a wrapper div with the live user JSON from Presence::users().
            $args = array_map('trim', explode(',', $expression, 2));
            $roomExpr = $args[0];
            $fallbackExpr = isset($args[1]) ? $args[1] : '[]';

            return "<?php
                \$__kaal_presence_room = {$roomExpr};
                \$__kaal_presence_users = \\Kaal\\Realtime\\Presence::users(\$__kaal_presence_room);
                if (empty(\$__kaal_presence_users) && !empty({$fallbackExpr})) {
                    \$__kaal_presence_users = is_array({$fallbackExpr}) ? {$fallbackExpr} : [];
                }
                \$__kaal_presence_json = htmlspecialchars(json_encode(array_values(\$__kaal_presence_users)), ENT_QUOTES, 'UTF-8');
                echo '<div data-kaal-presence=\"' . htmlspecialchars(\$__kaal_presence_room, ENT_QUOTES, 'UTF-8') . '\" data-kaal-presence-users=\"' . \$__kaal_presence_json . '\">';
            ?>";
        });

        Blade::directive('endpresence', function () {
            return "<?php echo '</div>'; ?>";
        });

        Blade::directive('cluster', function ($expression) {
            return "<?php
                if (!isset(\$__kaal_cluster_stack)) \$__kaal_cluster_stack = [];
                array_push(\$__kaal_cluster_stack, {$expression});
                echo '<div data-kaal-cluster=\"' . htmlspecialchars({$expression}) . '\">';
            ?>";
        });

        Blade::directive('endcluster', function () {
            return "<?php
                array_pop(\$__kaal_cluster_stack);
                echo '</div>';
            ?>";
        });

        Blade::directive('kaalDebug', function () {
            return "<?php if (env('KAAL_DEBUG', config('app.debug'))): ?>
                <div id=\"kaal-debug-root\"></div>
                <script>
                    window.KAAL_DEBUG = true;
                </script>
            <?php endif; ?>";
        });

        Blade::directive('realtimePwa', function ($expression) {
            $options = $expression ?: '[]';
            return "<?php \Kaal\Realtime\Facades\Realtime::pwa({$options}); ?>";
        });

        Blade::component('kaal::components.pwa.install', 'kaal-pwa-install');
        Blade::component('kaal::components.pwa.offline', 'kaal-pwa-offline');
        Blade::component('kaal::components.pwa.status', 'kaal-pwa-status');
    }
}
