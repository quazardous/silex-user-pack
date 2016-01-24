<?php

namespace Quazardous\Silex\UserPack\Entity;

/**
 * @MappedSuperclass
 */
abstract class AbstractUserBase
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
    
}
