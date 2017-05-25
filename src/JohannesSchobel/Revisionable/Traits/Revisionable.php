<?php

namespace JohannesSchobel\Revisionable\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use JohannesSchobel\Revisionable\Laravel\Listener;
use JohannesSchobel\Revisionable\Laravel\Presenter;
use JohannesSchobel\Revisionable\Models\Revision;

trait Revisionable
{
    /**
     * Boot the trait for a model.
     */
    protected static function bootRevisionable()
    {
        static::observe(Listener::class);
    }

    /**
     * Get record version at given timestamp.
     *
     * @param \DateTime|string $timestamp DateTime|Carbon object or parsable date string @see strtotime()
     *
     * @return Revision|null
     */
    public function snapshot($timestamp)
    {
        $revision = $this->revisions()
                         ->where('created_at', '<=', Carbon::parse($timestamp))
                         ->first();

        return $this->wrapRevision($revision);
    }

    /**
     * Get record version at given step back in history.
     *
     * @param int $step
     *
     * @return Revision|null
     */
    public function historyStep($step)
    {
        return $this->wrapRevision($this->revisions()->skip($step)->first());
    }

    /**
     * Determine if model has history at given timestamp if provided or any at all.
     *
     * @param \DateTime|string $timestamp DateTime|Carbon object or parsable date string @see strtotime()
     *
     * @return bool
     */
    public function hasHistory($timestamp = null)
    {
        if ($timestamp) {
            return (bool) $this->snapshot($timestamp);
        }

        return $this->revisions()->exists();
    }

    /**
     * Get an array of updated revisionable attributes.
     *
     * @return array
     */
    public function getDiff()
    {
        return array_diff_assoc($this->getNewAttributes(), $this->getOldAttributes());
    }

    /**
     * Get an array of original revisionable attributes.
     *
     * @return array
     */
    public function getOldAttributes()
    {
        $attributes = $this->getRevisionableItems($this->original);

        return $this->prepareAttributes($attributes);
    }

    /**
     * Get an array of current revisionable attributes.
     *
     * @return array
     */
    public function getNewAttributes()
    {
        $attributes = $this->getRevisionableItems($this->attributes);

        return $this->prepareAttributes($attributes);
    }

    /**
     * Stringify revisionable attributes.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function prepareAttributes(array $attributes)
    {
        return array_map(function ($attribute) {
            return ($attribute instanceof DateTime)
                ? $this->fromDateTime($attribute)
                : (string) $attribute;
        }, $attributes);
    }

    /**
     * Get an array of revisionable attributes.
     *
     * @param array $values
     *
     * @return array
     */
    protected function getRevisionableItems(array $values)
    {
        if (count($this->getRevisionable()) > 0) {
            return array_intersect_key($values, array_flip($this->getRevisionable()));
        }

        return array_diff_key($values, array_flip($this->getNonRevisionable()));
    }

    /**
     * Attributes being revisioned.
     *
     * @var array
     * @return array
     */
    public function getRevisionable()
    {
        return property_exists($this, 'revisionable') ? (array) $this->revisionable : [];
    }

    /**
     * Attributes hidden from revisioning if revisionable are not provided.
     *
     * @var array
     * @return array
     */
    public function getNonRevisionable()
    {
        return property_exists($this, 'nonRevisionable')
                ? (array) $this->nonRevisionable
                : ['created_at', 'updated_at', 'deleted_at'];
    }

    /**
     * Model has many Revisions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function revisions()
    {
        return $this->morphMany(Revision::class, 'revisionable', 'revisionable_type', 'revisionable_id')
                    ->ordered();
    }

    /**
     * Model has one latestRevision.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function latestRevision()
    {
        return $this->morphOne(Revision::class, 'revisionable', 'revisionable_type', 'revisionable_id')
                    ->ordered();
    }

    /**
     * Accessor for revisions property.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRevisionsAttribute()
    {
        if (!$this->relationLoaded('revisions')) {
            $this->load('revisions');
        }

        return $this->wrapRevision($this->getRelation('revisions'));
    }

    /**
     * Accessor for latestRevision attribute.
     *
     * @return Revision|null
     */
    public function getLatestRevisionAttribute()
    {
        if (!$this->relationLoaded('latestRevision')) {
            $this->load('latestRevision');
        }

        return $this->wrapRevision($this->getRelation('latestRevision'));
    }

    /**
     * Wrap revision model with the presenter if provided.
     *
     * @param Revision|\Illuminate\Database\Eloquent\Collection $history
     *
     * @return Presenter|Revision
     */
    public function wrapRevision($history)
    {
        if ($history && $presenter = $this->getRevisionPresenter()) {
            return $presenter::make($history, $this);
        }

        return $history;
    }

    /**
     * Get revision presenter class for the model.
     *
     * @return string|null
     */
    public function getRevisionPresenter()
    {
        if (!property_exists($this, 'revisionPresenter')) {
            return;
        }

        return class_exists($this->revisionPresenter)
                ? $this->revisionPresenter
                : Presenter::class;
    }

    /**
     * checks if revisions for this model shall be created
     *
     * @return bool
     */
    public function enableRevisionForModel() {
        if(is_null($this->revisionEnabled)) {
            // the parameter is not set - default behaviour is to create revisions
            return true;
        }

        if($this->revisionEnabled === false) {
            return false;
        }
        return true;
    }

    /**
     * Returns, how many revisions of a model must be kept
     *
     * @return int
     */
    public function getRevisionLimit() {
        $default = Config::get('revisionable.revisions.limit');
        if(is_null($this->revisionLimit)) {
            // the parameter is not set - default behaviour is to create revisions
            return $default;
        }

        if(! is_numeric($this->revisionLimit)) {
            return $default;
        }

        return $this->revisionLimit;
    }

    /**
     * Returns if older revisions shall be removed
     * @return bool
     */
    public function getRevisionLimitCleanup() {
        $default = Config::get('revisionable.revisions.limitCleanup');
        if(is_null($this->revisionLimitCleanup)) {
            // the parameter is not set - default behaviour is to create revisions
            return $default;
        }

        if($this->revisionLimitCleanup == false) {
            return false;
        }

        return true;
    }
}
