<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Creates the super admin',
)]
class CreateSuperAdminCommand extends Command
{
    public SymfonyStyle $io;

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'The super admin email')
            ->addArgument('password', InputArgument::OPTIONAL, 'The super admin password')
        ;
    }

    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     *
     * @see InputInterface::bind()
     * @see InputInterface::validate()
     *
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * Interacts with the user.
     *
     * This method is executed before the InputDefinition is validated.
     * This means that this is the only place where the command can
     * interactively ask for values of missing required arguments.
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->io->title('Création du super administrateur');

        $email = (string) $input->getArgument('email');
        $plainPassword = (string) $input->getArgument('password');

        if (empty($email)) {
            $email = $this->io->ask('Email du super admin');
            $input->setArgument('email', $email);
        }

        $this->io->text('Le mot de passe doit contenir au moins: ');
        $this->io->listing([
            'Une lettre minuscule',
            'Une lettre majuscule',
            'Un chiffre',
            'Un caractère spécial',
        ]);

        if (!empty($plainPassword)) {
            $this->io->warning('Le mot de passe ne doit pas être affiché en clair dans le terminal, veuillez renseigner cette valeur à nouveau');
        }

        $plainPassword = $this->io->askHidden('Le mot de passe sécurisé');
        $input->setArgument('password', $plainPassword);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getArgument('email');
        $plainPassword = (string) $input->getArgument('password');

        $superAdmin = new User();

        $superAdmin->setFirstName('Jean');
        $superAdmin->setLastName('Dupont');
        $superAdmin->setEmail($email);
        $superAdmin->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_USER']);
        $superAdmin->setIsVerified(true);
        $superAdmin->setPassword($plainPassword);
        $superAdmin->setCreatedAt(new \DateTimeImmutable());
        $superAdmin->setVerifiedAt(new \DateTimeImmutable());
        $superAdmin->setUpdatedAt(new \DateTimeImmutable());

        $errors = $this->validator->validate($superAdmin);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->io->error("{$error->getPropertyPath()}: {$error->getMessage()}");
            }

            return Command::FAILURE;
        }

        $users = $this->entityManager->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                $this->io->error('Problem: Le super admin existe déjà.');

                return Command::FAILURE;
            }
        }

        $passwordHashed = $this->hasher->hashPassword($superAdmin, $plainPassword);
        $superAdmin->setPassword($passwordHashed);

        $this->entityManager->persist($superAdmin);
        $this->entityManager->flush();

        $this->io->success('Le super administrateur a été ajouté avec succès.');

        return Command::SUCCESS;
    }
}
