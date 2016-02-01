<?php
namespace Quazardous\Silex\UserPack\Entity;

interface UserInterface
{

    /**
     * Set username
     *
     * @param string $username
     *
     * @return UserInterface
     */
    public function setUsername($username);

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername();

    /**
     * Set password
     *
     * @param string $password
     *
     * @return UserInterface
     */
    public function setPassword($password);

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword();

    /**
     * Set roles
     *
     * @param array $roles
     *
     * @return UserInterface
     */
    public function setRoles($roles);

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles();

    /**
     * Set email
     *
     * @param string $email
     *
     * @return UserInterface
     */
    public function setEmail($email);

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail();

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return UserInterface
     */
    public function setEnabled($enabled);

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled();

    /**
     * Set emailVerified
     *
     * @param boolean $emailVerified
     *
     * @return UserInterface
     */
    public function setEmailVerified($emailVerified);

    /**
     * Get emailVerified
     *
     * @return boolean
     */
    public function getEmailVerified();
}