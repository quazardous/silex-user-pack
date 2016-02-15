<?php

namespace Quazardous\Silex\UserPack\Entity;

use Quazardous\Silex\UserPack\Entity\UserInterface;

interface TokenInterface
{
   
    /**
     * Token constructor.
     * @param \Quazardous\Silex\UserPack\Entity\UserInterface $user
     */
    public function __construct(UserInterface $user);

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Token
     */
    public function setType($type);

    /**
     * Get type
     *
     * @return string
     */
    public function getType();

    /**
     * Set token
     *
     * @param string $token
     *
     * @return Token
     */
    public function setToken($token);

    /**
     * Get token
     *
     * @return string
     */
    public function getToken();

    /**
     * Set expiredAt
     *
     * @param \DateTime $expiredAt
     *
     * @return Token
     */
    public function setExpiredAt($expiredAt);

    /**
     * Get expiredAt
     *
     * @return \DateTime
     */
    public function getExpiredAt();

    /**
     * Get user
     *
     * @return \Quazardous\Silex\UserPack\Entity\UserInterface
     */
    public function getUser();

    /**
     * Set consumed
     *
     * @param boolean $consumed
     *
     * @return Token
     */
    public function setConsumed($consumed);

    /**
     * Get consumed
     *
     * @return boolean
     */
    public function getConsumed();

    /**
     * Set consumedAt
     *
     * @param \DateTime $consumedAt
     *
     * @return Token
     */
    public function setConsumedAt($consumedAt);

    /**
     * Get consumedAt
     *
     * @return \DateTime
     */
    public function getConsumedAt();
}
