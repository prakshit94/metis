<?php

use App\Modules\Users\Models\User;
use Storefront\Models\CustomerAccount;

return ['defaults' => ['guard' => 'web', 'passwords' => 'users'],
    'guards' => ['web' => ['driver' => 'session', 'provider' => 'users'], 'customer' => ['driver' => 'session', 'provider' => 'customers']],
    'providers' => ['users' => ['driver' => 'eloquent', 'model' => User::class], 'customers' => ['driver' => 'customer-eloquent', 'model' => CustomerAccount::class]]];
