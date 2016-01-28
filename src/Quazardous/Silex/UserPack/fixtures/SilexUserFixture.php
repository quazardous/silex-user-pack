<?php

namespace Quazardous\Silex\UserPack\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Quazardous\Silex\UserPack\Entity\User;

class SilexUserFixture extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $records = [
            // the password is the same as the username
            ['admin@sup.net', 'nhDr7OyKlXQju+Ge/WKGrPQ9lPBSUFfpK+B1xqx/+8zLZqRNX0+5G1zBQklXUFy86lCpkAofsExlXiorUcKSNQ==', ['ROLE_ADMIN']],
            ['johndoe@sup.net', 'pDCg+bcH3F8acFT9Tu2mh2L2nFjMqJhjBzzAC5aBKY5XXqlOITp6fnE7b33s9Woxo7AHQ6h+t2W8GmUobP3g3g==', ['ROLE_USER']],
        ];
        foreach ($records as $record) {
            $user = new User();
            $manager->persist($user);
            $user->setUsername($record[0]);
            $user->setEmail($record[0]);
            $user->setPassword($record[1]);
            $user->setRoles($record[2]);
            $user->setEnabled(true);
        }
        $manager->flush();
    }

}