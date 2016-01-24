<?php

namespace Quazardous\Silex\UserPack\Entity;

/**
 * @Entity
 * @Table(
 *    name="silex_user",
 *    uniqueConstraints={@UniqueConstraint(name="user_username", columns={"username"})},
 *    )
 */
class User
{
    /**
     * @Id @Column(type="integer", nullable=false, options={"unsigned" = true}) @GeneratedValue
     */
    protected $id;

    /**
     * @Column(type="string", length=32, nullable=false)
     */
    protected $username;
    
    /**
     * @Column(type="string", length=128, nullable=true)
     */
    protected $password;

    /**
     * @Column(type="json_array", nullable=true, options={})
     */
    protected $roles;
    
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
}
