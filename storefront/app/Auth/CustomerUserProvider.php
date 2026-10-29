<?php

namespace Storefront\Auth;

use Illuminate\Auth\EloquentUserProvider;

class CustomerUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        if (isset($credentials['email']) && is_string($credentials['email'])) {
            $credentials['email'] = mb_strtolower(trim($credentials['email']));
        }

        return parent::retrieveByCredentials($credentials);
    }

    protected function newModelQuery($model = null)
    {
        // Applies to passwords, existing sessions and remember-me retrieval alike.
        return parent::newModelQuery($model)->storefrontEligible();
    }
}
