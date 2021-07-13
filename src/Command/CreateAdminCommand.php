<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\AdminRosaly;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityManagerInterface;

class CreateAdminCommand extends Command
{
    protected static $defaultName = 'app:create-admin';

    private EntityManagerInterface $entityManager;
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Create an admin')
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'Admin\'s name'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'Admin\'s password'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');


        $admin = new AdminRosaly();
        $encodedPassword = $this
            ->passwordEncoder
            ->encodePassword(
                $admin,
                $password
            )
        ;

        $admin->setEmail($email);
        $admin->setPassword($encodedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $output->writeln("admin $email created");

        return Command::SUCCESS;
    }
}
