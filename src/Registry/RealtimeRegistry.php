<?php

namespace Kaal\Realtime\Registry;

class RealtimeRegistry
{
    /**
     * Registered realtime blocks.
     *
     * @var array
     */
    protected array $blocks = [];

    /**
     * Registered server actions.
     *
     * @var array
     */
    protected array $actions = [];

    /**
     * Register a realtime block.
     *
     * @param string $id
     * @param string $handler
     * @param array $models
     * @return void
     */
    public function register(string $id, string $handler, array $models = []): void
    {
        $this->blocks[$id] = [
            'handler' => $handler,
            'models' => $models,
        ];
    }

    /**
     * Register a server action.
     *
     * @param string $name
     * @param \Closure $callback
     * @return void
     */
    public function action(string $name, \Closure $callback): void
    {
        $this->actions[$name] = $callback;
    }

    /**
     * Get a registered server action callback.
     *
     * @param string $name
     * @return \Closure|null
     */
    public function getAction(string $name): ?\Closure
    {
        return $this->actions[$name] ?? null;
    }

    /**
     * Get all registered server actions.
     *
     * @return array
     */
    public function allActions(): array
    {
        return $this->actions;
    }

    /**
     * Get a specific block by ID.
     *
     * @param string $id
     * @return array|null
     */
    public function get(string $id): ?array
    {
        return $this->blocks[$id] ?? null;
    }

    /**
     * Get all registered blocks.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->blocks;
    }

    /**
     * Get blocks associated with a specific model.
     *
     * @param string $modelClass
     * @return array
     */
    public function getBlocksForModel(string $modelClass): array
    {
        return array_filter($this->blocks, function ($block) use ($modelClass) {
            return in_array($modelClass, $block['models']);
        });
    }

    /**
     * Get the PwaManager instance.
     *
     * @return \Kaal\Realtime\PWA\PwaManager
     */
    public function getPwaManager(): \Kaal\Realtime\PWA\PwaManager
    {
        static $pwaManager;
        if (!$pwaManager) {
            $pwaManager = new \Kaal\Realtime\PWA\PwaManager();
        }
        return $pwaManager;
    }

    /**
     * Render PWA setup scripts and tags.
     *
     * @param array $options
     * @return void
     */
    public function pwa(array $options = []): void
    {
        $this->getPwaManager()->pwa($options);
    }

    /**
     * Send a Web Push Notification to a user.
     *
     * @param mixed $user
     * @param mixed $notification
     * @return void
     */
    public function notify($user, $notification): void
    {
        $this->getPwaManager()->notify($user, $notification);
    }

    /**
     * Register a model for realtime sync in the service worker.
     *
     * @param string $modelClass
     * @return void
     */
    public function sync(string $modelClass): void
    {
        $this->getPwaManager()->sync($modelClass);
    }
}
