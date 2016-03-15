<?php

namespace Quazardous\Silex\UserPack\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class PasswordCommand extends Command
{
    protected function configure()
    {
        $this->setName('silex:user:pwd')
            ->setDescription('Crypts the given password.')
            ->addArgument('password', InputArgument::REQUIRED, 'Password to encode');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getApplication()->getContainer();
        $password = $input->getArgument('password');

        $password = $app['user.password_encoder']($password);

        $output->writeln($password);
    }
}
