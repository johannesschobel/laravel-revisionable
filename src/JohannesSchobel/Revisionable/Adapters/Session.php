<?php

namespace JohannesSchobel\Revisionable\Adapters;

use Illuminate\Session\SessionManager;
use JohannesSchobel\Revisionable\Interfaces\UserProvider;

class Session implements UserProvider
{
    /**
     * Auth provider instance.
     *
     * @var SessionManager
     */
    protected $provider;

    /**
     * Field from the user to be saved as author of the action.
     *
     * @var string
     */
    protected $field;

    /**
     * Create adapter instance for Global Session
     *
     * @param SessionManager $provider
     * @param string                 $field
     */

    public function __construct(SessionManager $provider, $field = null)
    {

        $this->provider = $provider;
        $this->field = $field;
    }

    /**
     * Get identifier of the currently logged in user.
     *
     * @return string|null
     */
    public function getUser()
    {
        if ($user = $this->provider) {
            return ($field = $this->field) ? (string) $user->get($field)  : $user->get('id');
        }
    }

    /**
     * Get id of the currently logged in user.
     *
     * @return integer|null
     */
    public function getUserId()
    {
        if ($user = $this->provider->user()) {
            return $user->getKey();
        }
    }
}
