# JohannesSchobel/Laravel-Revisionable

Easy and conventient way to handle revisions of your models within the database.

* Handles the revisions in **bulk** - one entry covers all the created/updated fields, what makes it really **easy to 
e.g., compare 2 given versions** or get all the data changed during one single transaction.


## Requirements

* This package requires PHP 5.4+
* Currently it works out of the box with Laravel5.4 + generic Illuminate Guard, tymon/jwt-auth OR cartalyst/sentry 2/sentinel 2

## Usage (Laravel 5 basic example - see Customization below as well)

### 1. Download the package or require in your `composer.json`:

```
composer require johannesschobel/laravel-revisionable
```

### 2. Add the service provider to your `app/config/app.php`:

```php
    'providers' => array(
        ...
        'JohannesSchobel\Revisionable\RevisionableServiceProvider',
    ),
```

### 3. Publish the package config file:

```
~$ php artisan vendor:publish [--provider="JohannesSchobel\Revisionable\RevisionableServiceProvider"]
```

this will create `config/revisionable.php` file, where you can adjust a few settings.

### 4. Run the migration in order to create the revisions table:

```
~$ php artisan migrate
```

### 5. Add revisionable trait to the models you wish to keep track of:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use JohannesSchobel\Revisionable\Traits\Revisionable;

class User extends Model
{
    use Revisionable;
}    
```

And that's all to get you started!

## Customization

The package offers a set of configuration options.

### White-Listing Fields
If you would like to revision only specific fields of the model, you can define them like so:

```php
    /*
     * Set revisionable whitelist - only changes to any
     * of these fields will be tracked during updates.
     */
    protected $revisionable = [
        'email',
        'name'
    ];

```

This way, only the fields `email` and `name` are tracked and store as revision in the database. The default behaviour
is to revision all fields.

### Disable Revisions

If you want to disable revisions for a specific model just add the following variables to your model
```php
    protected $revisionEnabled = false;
```

### Revision Cleanup

You can further specify that you only want to clean up old revisions of a model. By doing so, you can further define, 
how many revisions of one model you would like to keep in your database (default is set to 20). Say, if you add the 21st
revision, the first one is deleted.

You may customize this behaviour for each model by changing the variables
```php
    protected $revisionLimitCleanup = true; // only works with revisionLimit
    protected $revisionLimit = 50;  // keep 50 instead of 20 revisions of this model
```

## Rollback (aka load old revisions)

Of course, you can rollback to an old revision of your model. The `Trait` added earlier (e.g., the `Revisionable` trait) 
already provides handy methods for you - so you don't need to worry about this.

You can either use the `$model->rollbackToTimestamp($timestamp)` or `$model->rollbackSteps($steps)` functions for this 
purpose. Note that there is a configuration flag `revisionable.rollback.cleanup` (default `false`) that indicates, 
whether the revisions rolled back should be deleted (`true`) or not (`false`).

Both functions return the rolled back model, which is already persisted in the database.

## Demonstration

```php
$ php artisan tinker

>>> $ticket = App\Models\Ticket::first();
=> <App\Models\Ticket>

>>> $revision->getDiff();
=> [
       "customer_id"    => [
           "old" => "1",
           "new" => "101"
       ],
       "item_id"        => [
           "old" => "2",
           "new" => "1"
       ],
       "responsible_id" => [
           "old" => "8",
           "new" => "2"
       ]
   ]

>>> $revision->old('item_id');
=> "2"

>>> $revision->new('item_id');
=> "1"

>>> $revision->isUpdated('item_id');
=> true

>>> $revision->isUpdated('note');
=> false

>>> $revision->label('item_id');
=> "item_id"

>>> $revision->old;
=> [
       "defect"         => "foo",
       "note"           => "bar",
       "customer_id"    => "1",
       "item_id"        => "2",
       "responsible_id" => "8",
       "status_id"      => "6"
   ]

>>> $revision->action;
=> "updated"
```
