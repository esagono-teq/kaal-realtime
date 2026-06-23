<?php

namespace Kaal\Realtime\Observers;

use Illuminate\Database\Eloquent\Model;
use Kaal\Realtime\Events\ModelChanged;

class RealtimeObserver
{
    /**
     * Handle the Model "created" event.
     *
     * @param Model $model
     * @return void
     */
    public function created(Model $model): void
    {
        ModelChanged::dispatch($model, 'created');
    }

    /**
     * Handle the Model "updated" event.
     *
     * @param Model $model
     * @return void
     */
    public function updated(Model $model): void
    {
        ModelChanged::dispatch($model, 'updated');
    }

    /**
     * Handle the Model "deleted" event.
     *
     * @param Model $model
     * @return void
     */
    public function deleted(Model $model): void
    {
        ModelChanged::dispatch($model, 'deleted');
    }
}
