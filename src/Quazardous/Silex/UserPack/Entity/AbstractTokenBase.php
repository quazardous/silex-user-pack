<?php

namespace Quazardous\Silex\UserPack\Entity;

/**
 * @MappedSuperclass
 */
abstract class AbstractTokenBase
{
    /**
     * @Id @Column(type="integer", nullable=false, options={"unsigned" = true}) @GeneratedValue
     */
    protected $id;
    
    /**
     * @Column(type="string", length=16, nullable=true)
     */
    protected $type;
    
    /**
     * @Column(type="integer", nullable=false, options={"unsigned" = true})
     */
    protected $user_id;
    
    /**
     * @Column(type="string", length=32, nullable=false)
     */
    protected $token;
    
    /**
     * @Column(type="datetime", nullable=true, options={})
     */
    protected $expired_at;
    
    /**
     * @Column(type="boolean", nullable=true, options={"default" = 0})
     */
    protected $consumed = false;
    
    /**
     * @Column(type="datetime", nullable=true, options={})
     */
    protected $consumed_at;
    
}
