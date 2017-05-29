<?php

namespace JohannesSchobel\Revisionable\Laravel;

use JohannesSchobel\Revisionable\Interfaces\UserProvider;
use JohannesSchobel\Revisionable\Models\Revision;

class Listener
{
    /**
     * @param UserProvider $userProvider
     */
    public function __construct(UserProvider $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    /**
     * Handle created event.
     *
     * @param \Illuminate\Database\Eloquent\Model $revisioned
     */
    public function created($revisioned)
    {
        $this->log('created', $revisioned);
    }

    /**
     * Handle updated event.
     *
     * @param \Illuminate\Database\Eloquent\Model $revisioned
     */
    public function updated($revisioned)
    {
        if (count($revisioned->getDiff())) {
            $this->log('updated', $revisioned);
        }
    }

    /**
     * Handle deleted event.
     *
     * @param \Illuminate\Database\Eloquent\Model $revisioned
     */
    public function deleted($revisioned)
    {
        $this->log('deleted', $revisioned);
    }

    /**
     * Handle restored event.
     *
     * @param \Illuminate\Database\Eloquent\Model $revisioned
     */
    public function restored($revisioned)
    {
        $this->log('restored', $revisioned);
    }

    /**
     * Log the revision.
     *
     * @param string $action
     * @param  \Illuminate\Database\Eloquent\Model
     */
    protected function log($action, $revisioned)
    {
        if(! $revisioned->getConfigRevisionForModel()) {
            return;
        }

        $old = $new = [];

        switch ($action) {
            case 'created':
                $new = $revisioned->getNewAttributes();
                break;
            case 'deleted':
                $old = $revisioned->getOldAttributes();
                break;
            case 'updated':
                $old = $revisioned->getOldAttributes();
                $new = $revisioned->getNewAttributes();
                break;
        }

        $revisioned->revisions()->create([
            'action' => $action,
            'table_name' => $revisioned->getTable(),

            'user_id' => $this->userProvider->getUserId(),

            'old' => json_encode($old),
            'new' => json_encode($new),

            'ip' => data_get($_SERVER, 'REMOTE_ADDR'),
            'ip_forwarded' => data_get($_SERVER, 'HTTP_X_FORWARDED_FOR'),
        ]);

        // check if we need to cleanup old revisions
        if($revisioned->getConfigRevisionLimitCleanup()) {
            // we need to cleanup
            if($revisioned->revisions()->count() > $revisioned->getConfigRevisionLimit()) {
                // we currently have more revisions than we want to have
                $diff = $revisioned->revisions()->count() - $revisioned->getConfigRevisionLimit();
                // we need to delete the oldest N revisions
                $revsToDelete = $revisioned->revisions()->get()->reverse()->take($diff);
                foreach($revsToDelete as $rev) {
                    Revision::destroy($rev->id);
                }
            }
        }
    }
}
