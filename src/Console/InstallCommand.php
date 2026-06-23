<?php

namespace Kaal\Realtime\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class InstallCommand extends Command
{
    protected $signature = 'kaal:install';
    protected $description = 'Install and configure the KAAL Realtime package';

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=blue;options=bold>KAAL Realtime — Installer</>');
        $this->newLine();

        // 1. Publish config
        $this->task('Publishing config', function () {
            $this->callSilently('vendor:publish', [
                '--tag' => 'kaal-realtime-config',
                '--force' => true,
            ]);
            return File::exists(config_path('kaal-realtime.php'));
        });

        // 2. Publish JS
        $this->task('Publishing JS assets', function () {
            $this->callSilently('vendor:publish', [
                '--tag' => 'kaal-realtime-assets',
                '--force' => true,
            ]);
            return File::exists(resource_path('js/vendor/kaal-realtime/realtime.js'));
        });

        // 3. Auto-Provision App Credentials
        $credentials = null;
        $this->task('Provisioning App from Gateway', function () use (&$credentials) {
            try {
                $gatewayUrl = env('KAAL_GATEWAY_URL', 'https://ws.kaalrealtime.com');
                $apiSecret = env('GATEWAY_API_SECRET', 'alpha-control-secret');

                $response = Http::timeout(3)->post("{$gatewayUrl}/api/apps", [
                    'name' => config('app.name', 'Laravel') . ' (Auto-Provisioned)',
                    'api_secret' => $apiSecret,
                ]);
                if ($response->successful()) {
                    $credentials = $response->json();
                    return true;
                }
            } catch (\Exception $e) {
                // Gateway unreachable — credentials will be left blank
            }
            return false;
        });

        // 4. Configure .env
        $this->task('Configuring .env variables', function () use ($credentials) {
            $envPath = base_path('.env');
            if (!File::exists($envPath))
                return false;

            $env = File::get($envPath);

            // Set broadcast driver
            if (str_contains($env, 'BROADCAST_CONNECTION=')) {
                $env = preg_replace('/^BROADCAST_CONNECTION=.*$/m', 'BROADCAST_CONNECTION=kaal', $env);
            } else {
                $env .= "\nBROADCAST_CONNECTION=kaal";
            }

            // Append KAAL variables
            if (!str_contains($env, 'VITE_KAAL_APP_ID')) {
                $env .= "\n\n# KAAL Cloud Realtime Config\n";

                if ($credentials) {
                    $env .= "VITE_KAAL_APP_ID=" . $credentials['app_id'] . "\n";
                    $env .= "VITE_KAAL_APP_KEY=" . $credentials['key'] . "\n";
                    $env .= "VITE_KAAL_APP_SECRET=" . $credentials['secret'] . "\n";
                } else {
                    $env .= "VITE_KAAL_APP_ID=\n";
                    $env .= "VITE_KAAL_APP_KEY=\n";
                    $env .= "VITE_KAAL_APP_SECRET=\n";
                }
            }

            File::put($envPath, $env);
            return true;
        });

        $this->newLine();
        $this->line('  <fg=green;options=bold>✓ KAAL Realtime installed successfully!</>');

        if (!$credentials) {
            $this->newLine();
            $this->line('  <fg=yellow;options=bold>⚠ Gateway was unreachable. You must manually create an App in the Dashboard and paste the credentials into your .env file.</>');
        }

        $this->newLine();
        $this->line('  <options=bold>Next steps:</>');
        $this->newLine();
        $this->line('  <fg=gray>1.</> Add <fg=yellow>HasRealtime</> to your model:');
        $this->line('');
        $this->line('     <fg=blue>use Kaal\Realtime\Traits\HasRealtime;</>');
        $this->line('');
        $this->line('     <fg=blue>class Product extends Model</>');
        $this->line('     <fg=blue>{</>');
        $this->line('     <fg=blue>    use HasRealtime;</>');
        $this->line('     <fg=blue>}</>');
        $this->newLine();
        $this->line('  <fg=gray>2.</> Wrap your Blade block:');
        $this->line('');
        $this->line('     <fg=blue>@realtime([Product::class])</>');
        $this->line('     <fg=blue>    @foreach($products as $product)</>');
        $this->line('     <fg=blue>        ...</>');
        $this->line('     <fg=blue>    @endforeach</>');
        $this->line('     <fg=blue>@endrealtime</>');
        $this->newLine();
        $this->line('  <fg=gray>3.</> Import realtime.js in your app.js:');
        $this->line('');
        $this->line('     <fg=blue>import \'./vendor/kaal-realtime/realtime\';</>');
        $this->newLine();
        $this->line('  <fg=gray>Docs:</> https://github.com/kaal/realtime');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Run a labeled task and show pass/fail.
     */
    private function task(string $label, callable $fn): bool
    {
        $this->output->write("  <fg=gray>checking</> {$label}...");
        $result = $fn();
        if ($result) {
            $this->line(" <fg=green>✓</>");
        } else {
            $this->line(" <fg=red>✗</>");
        }
        return (bool) $result;
    }
}
