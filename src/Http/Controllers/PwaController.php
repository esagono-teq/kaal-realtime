<?php

namespace Kaal\Realtime\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kaal\Realtime\PWA\Manifest\ManifestBuilder;
use Kaal\Realtime\PWA\ServiceWorker\ServiceWorkerBuilder;

class PwaController extends Controller
{
    public function manifest(ManifestBuilder $builder)
    {
        return response()->json($builder->generate())
            ->header('Content-Type', 'application/manifest+json');
    }

    public function serviceWorker(ServiceWorkerBuilder $builder)
    {
        return response($builder->generate())
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=0, must-revalidate'); // SW should not be cached tightly
    }

    public function offline()
    {
        return view('kaal::pwa.offline');
    }
}
