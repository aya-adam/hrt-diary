<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create a user which can be used to access the admin dashboard',
)]
class CreateUserCommand extends Command
{
    private ?string $plainPassword;
    private ?string $email;
    private bool $ifNotExists;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager)
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('--password', null, InputOption::VALUE_REQUIRED, 'The user password')
            ->addOption('--email', null, InputOption::VALUE_REQUIRED, 'The user email')
            ->addOption('--if-not-exists', null, InputOption::VALUE_NONE, 'Don\'t trigger an error, when the user already exists')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->plainPassword = $input->getOption('password');
        $this->email = $input->getOption('email');
        $this->ifNotExists = $input->getOption('if-not-exists');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->email || !filter_var($this->email, \FILTER_VALIDATE_EMAIL)) {
            do {
                $emailQuestion = new Question('Enter an email address for the user');
                $this->email = $io->askQuestion($emailQuestion);

                if (!$emailValid = filter_var($this->email, \FILTER_VALIDATE_EMAIL)) {
                    $io->note(sprintf('The entered email "%s" is not valid', $this->email));
                }

            } while (!$emailValid);
        }
        
        if (!$this->plainPassword) {
            $passwordQuestion = new Question('Enter a password for the user');
            $passwordQuestion->setHidden(true);
            $this->plainPassword = $io->askQuestion($passwordQuestion);

            do {
                $passwordConfirmQuestion = new Question('Repeat the entered password');
                $passwordConfirmQuestion->setHidden(true);
                $passwordConfirm = $io->askQuestion($passwordConfirmQuestion);

                if (!$passwordsMatch = $this->plainPassword === $passwordConfirm) {
                    $io->note('The repeated password does not match the with the initially entered password.');
                }

            } while (!$passwordsMatch);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userRepository = $this->entityManager->getRepository('App\Entity\User');
        $userExists = $userRepository->findOneByEmail($this->email);

        if ($userExists) {
            if ($this->ifNotExists) {
                $io->info(sprintf('A user with the email address "%s" already exists. Skipped.', $this->email));
                return Command::SUCCESS;
            } else {
                $io->error(sprintf('A user with the email address "%s" already exists.', $this->email));
                return Command::FAILURE;
            }
        }

        $user = new User();
        $user->setEmail($this->email);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, $this->plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('The new user was successfully created.');

        return Command::SUCCESS;
    }
}
