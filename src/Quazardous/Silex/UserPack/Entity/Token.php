<?php

namespace Quazardous\Silex\UserPack\Entity;

use Quazardous\Silex\UserPack\Entity\AbstractTokenBase;

/**
 * @Entity
 * @Table(
 *     name="silex_user_token",
 *     uniqueConstraints={
 *         @UniqueConstraint(name="silex_user_token_token", columns={"token"})
 *     },
 *     indexes={
 *         @Index(name="silex_user_token_user", columns={"user_id"})
 *     }
 * )
 */
class Token extends AbstractTokenBase
{
    /**
     * @ManyToOne(targetEntity="Quazardous\Silex\UserPack\Entity\UserInterface")
     * @JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;
    
    public function __construct(\Quazardous\Silex\UserPack\Entity\UserInterface $user)
    {
        $this->setUser($user);
    }

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
     * Set type
     *
     * @param string $type
     *
     * @return Token
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return Token
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return Token
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set expiredAt
     *
     * @param \DateTime $expiredAt
     *
     * @return Token
     */
    public function setExpiredAt($expiredAt)
    {
        $this->expired_at = $expiredAt;

        return $this;
    }

    /**
     * Get expiredAt
     *
     * @return \DateTime
     */
    public function getExpiredAt()
    {
        return $this->expired_at;
    }

    /**
     * Set user
     *
     * @param \Quazardous\Silex\UserPack\Entity\UserInterface $user
     *
     * @return Token
     */
    public function setUser(\Quazardous\Silex\UserPack\Entity\UserInterface $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Quazardous\Silex\UserPack\Entity\UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set consumed
     *
     * @param boolean $consumed
     *
     * @return Token
     */
    public function setConsumed($consumed)
    {
        $this->consumed = $consumed;

        return $this;
    }

    /**
     * Get consumed
     *
     * @return boolean
     */
    public function getConsumed()
    {
        return $this->consumed;
    }

    /**
     * Set consumedAt
     *
     * @param \DateTime $consumedAt
     *
     * @return Token
     */
    public function setConsumedAt($consumedAt)
    {
        $this->consumed_at = $consumedAt;

        return $this;
    }

    /**
     * Get consumedAt
     *
     * @return \DateTime
     */
    public function getConsumedAt()
    {
        return $this->consumed_at;
    }
}
