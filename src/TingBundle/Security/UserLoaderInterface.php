<?php

namespace CCMBenchmark\TingBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

interface UserLoaderInterface
{
    /**
     * Loads the user for the given user identifier (e.g. username or email).
     *
     * This method must return null if the user is not found.
     */
    public function loadUserByIdentifier(string $identifier): ?UserInterface;
}