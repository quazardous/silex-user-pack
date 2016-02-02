<?php

namespace Quazardous\Silex\UserPack\Entity;

use Quazardous\Silex\UserPack\Entity\AbstractUserBase;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

use Quazardous\Silex\UserPack\Entity\UserInterface;


/**
 * @Entity
 * @Table(
 *     name="silex_user",
 *     uniqueConstraints={
 *         @UniqueConstraint(name="silex_user_username", columns={"username"}),
 *         @UniqueConstraint(name="silex_user_email", columns={"email"})
 *     }
 * )
 */
class User extends AbstractUserBase implements UserInterface
{
    
//     public static function loadValidatorMetadata(ClassMetadata $metadata)
//     {
//         $metadata->addConstraint(new UniqueEntity([
//             'fields'  => 'email',
//             'message' => ''
//         ]));
    
//         $metadata->addPropertyConstraint('email', new Assert\Email());
//     }
    
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set roles
     *
     * @param array $roles
     *
     * @return User
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return User
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set emailVerified
     *
     * @param boolean $emailVerified
     *
     * @return User
     */
    public function setEmailVerified($emailVerified)
    {
        $this->email_verified = $emailVerified;

        return $this;
    }

    /**
     * Get emailVerified
     *
     * @return boolean
     */
    public function getEmailVerified()
    {
        return $this->email_verified;
    }
}
