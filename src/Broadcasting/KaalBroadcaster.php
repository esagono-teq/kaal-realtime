<?php

namespace Kaal\Realtime\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\Broadcasters\UsePusherChannelConventions;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

class KaalBroadcaster extends Broadcaster
{
    use UsePusherChannelConventions;

    protected $config;

    /**
     * Create a new broadcaster instance.
     *
     * @param  array  $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function auth($request)
    {
        return parent::verifyUserCanAccessChannel(
            $request, $request->channel_name
        );
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        return $result;
    }

    /**
     * Broadcast the given event.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $url = $this->config['url'] . '/control/publish';

        foreach ($this->formatChannels($channels) as $channel) {
            $response = Http::post($url, [
                'api_secret' => $this->config['api_secret'],
                'app_id' => $this->config['app_id'],
                'key' => $this->config['key'],
                'secret' => $this->config['secret'],
                'channel' => $channel,
                'event' => $event,
                'data' => $payload,
            ]);

            if ($response->failed()) {
                throw new HttpException(
                    $response->status(),
                    'Failed to connect to KAAL Gateway: ' . $response->body()
                );
            }
        }
    }
}
