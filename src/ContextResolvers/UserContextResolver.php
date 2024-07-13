<?php

namespace YouCanShop\Foggle\ContextResolvers;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Authenticatable;

class UserContextResolver
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    public function resolve(): ?Authenticatable
    {
        return $this->authManager->user();
    }
}
