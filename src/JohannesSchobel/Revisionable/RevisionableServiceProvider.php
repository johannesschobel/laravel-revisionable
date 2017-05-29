<?php

namespace JohannesSchobel\Revisionable;

use Illuminate\Support\ServiceProvider;
use JohannesSchobel\Revisionable\Adapters;
use JohannesSchobel\Revisionable\Interfaces\UserProvider;
use JohannesSchobel\Revisionable\Models\Revision;

class RevisionableServiceProvider extends ServiceProvider
{
    public function boot() {
        $this->publishes([
            __DIR__.'/../../config/revisionable.php'   => config_path('revisionable.php'),
        ], 'config');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->setupConfig();
        $this->bindUserProvider();
        $this->bootModel();
    }

    /**
     * Get the Configuration
     */
    private function setupConfig() {
        $this->mergeConfigFrom(realpath(__DIR__ . '/../../config/revisionable.php'), 'revisionable');
    }

    /**
     * Bind user provider implementation to the IoC.
     */
    protected function bindUserProvider()
    {
        $userProvider = $this->app['config']->get('revisionable.userprovider');

        switch ($userProvider) {
            case 'sentry':
                $this->bindSentryProvider();
                break;

            case 'sentinel':
                $this->bindSentinelProvider();
                break;

            case 'jwt-auth':
                $this->bindJwtAuthProvider();
                break;

            case 'session':
                $this->bindSessionProvider();
                break;

            default:
                $this->bindGuardProvider();
                break;
        }
        $this->app->alias('revisionable.userprovider', UserProvider::class);
    }

    /**
     * Bind adapter for Sentry to the IoC.
     */
    protected function bindSentryProvider()
    {
        $this->app->singleton('revisionable.userprovider', function ($app) {
            $field = $app['config']->get('revisionable.userfield');
            return new Adapters\Sentry($app['sentry'], $field);
        });
    }

    /**
     * Bind adapter for Sentinel to the IoC.
     */
    protected function bindSentinelProvider()
    {
        $this->app->singleton('revisionable.userprovider', function ($app) {
            $field = $app['config']->get('revisionable.userfield');
            return new Adapters\Sentinel($app['sentinel'], $field);
        });
    }

    /**
     * Bind adapter for JWT Auth to the IoC.
     */
    private function bindJwtAuthProvider()
    {
        $this->app->singleton('revisionable.userprovider', function ($app) {
            $field = $app['config']->get('revisionable.userfield');
            return new Adapters\JwtAuth($app['tymon.jwt.auth'], $field);
        });
    }

    /**
     * Bind adapter for Illuminate Guard to the IoC.
     */
    protected function bindGuardProvider()
    {
        $this->app->singleton('revisionable.userprovider', function ($app) {
            $field = $app['config']->get('revisionable.userfield');
            return new Adapters\Guard($app['auth']->guard(), $field);
        });
    }

    /**
     * Bind adapter for Session to the IoC.
     *
     * @return void
     */
    protected function bindSessionProvider()
    {
        $this->app->singleton('revisionable.userprovider', function ($app) {
            $field = $app['config']->get('revisionable.userfield');
            return new Adapters\Session(session(), $field);
        });
    }

    /**
     * Boot the Revision model.
     */
    protected function bootModel()
    {
        $table = $this->app['config']->get('revisionable.table', 'revisions');
        $user = $this->app['config']->get('revisionable.usermodel', 'App\User');

        forward_static_call_array([Revision::class, 'setCustomTable'], [$table]);
        forward_static_call_array([Revision::class, 'setUserModel'], [$user]);
    }

    public function provides()
    {
        return [
            'revisionable.userprovider',
        ];
    }
}
