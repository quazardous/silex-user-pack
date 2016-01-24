<?php
namespace Quazardous\Silex\UserPack\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
// use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProvider implements UserProviderInterface
{

    protected $userLoader;
    /**
     * 
     * @param callable $userLoader takes the username and return a UserInterface
     */
    public function __construct(callable $userLoader)
    {
        $this->userLoader = $userLoader;
    }
    
    public function loadUserByUsername($username)
    {
        $user = call_user_func($this->userLoader, $username);
        if (empty($user)) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }
        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return in_array('Symfony\Component\Security\Core\User\UserInterface', class_implements($class));
    }

}