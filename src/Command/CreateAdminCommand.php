<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un compte administrateur Aura'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = (new User())
            ->setEmail('admin@aura.local')
            ->setRoles(['ROLE_ADMIN']);

        $user->setPassword($this->hasher->hashPassword($user, 'ChangeMe!'));

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('Admin créé : admin@aura.local / ChangeMe!');
        return Command::SUCCESS;
    }
}
