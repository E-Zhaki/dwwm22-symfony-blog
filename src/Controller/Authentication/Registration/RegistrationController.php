<?php

namespace App\Controller\Authentication\Registration;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/inscription', name: 'app_register')]
    // URL : quand on va sur /inscription, cette méthode est appelée
    public function register(
        Request $request,
        // Objet qui représente la requête HTTP (données du formulaire, méthode POST, etc.)

        UserPasswordHasherInterface $userPasswordHasher,
        // Service Symfony pour chiffrer (hasher) le mot de passe

        EntityManagerInterface $entityManager,
        // Service Doctrine pour communiquer avec la base de données
    ): Response {
        // Si un utilisateur est déjà connecté
        if ($this->getUser()) {
            // On l'empêche d'accéder à l'inscription
            return $this->redirectToRoute('app_visitor_welcome');
        }

        // Création d'un objet User vide (futur utilisateur)
        $user = new User();

        // Création du formulaire basé sur RegistrationFormType
        // Les données du formulaire seront automatiquement mises dans $user
        $form = $this->createForm(RegistrationFormType::class, $user);

        // On lie la requête HTTP au formulaire (récupère les données envoyées)
        $form->handleRequest($request);

        // Si le formulaire a été envoyé ET que toutes les validations sont correctes
        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère le mot de passe en clair saisi par l'utilisateur
            /** @var string $password */
            $password = $form->get('password')->getData();

            // On chiffre le mot de passe avec le service Symfony
            // $user sert à choisir la bonne configuration de sécurité
            $passwordHashed = $userPasswordHasher->hashPassword($user, $password);

            // On stocke le mot de passe chiffré dans l'objet User
            $user->setPassword($passwordHashed);

            // On attribue le rôle par défaut à l'utilisateur
            $user->setRoles(['ROLE_USER']);

            // On renseigne la date de création
            $user->setCreatedAt(new \DateTimeImmutable());

            // On renseigne la date de dernière modification
            $user->setUpdatedAt(new \DateTimeImmutable());

            // Doctrine prépare l'enregistrement de l'utilisateur
            $entityManager->persist($user);

            // Doctrine exécute réellement l'insertion en base de données
            $entityManager->flush();

            // Envoi de l'email de confirmation avec lien signé
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email', // Route appelée quand l'utilisateur cliquera sur le lien
                $user,              // Utilisateur concerné
                (new TemplatedEmail()) // Création de l'email basé sur un template Twig
                    ->from(new Address('medecine-du-monde@gmail.com', 'Jean Dupont'))
                    // Adresse de l'expéditeur

                    ->to((string) $user->getEmail())
                    // Adresse email de l'utilisateur

                    ->subject('Confirmation de votre compte sur le blog de Jean Dupont')
                    // Sujet du mail

                    ->htmlTemplate('emails/confirmation_email.html.twig')
                // Template HTML utilisé pour construire le mail
            );

            // Après l'inscription, on redirige vers la page "Va vérifier ton email"
            return $this->redirectToRoute('app_visitor_waiting_for_email_verif');
        }

        // Cas où le formulaire n'est pas encore envoyé ou contient des erreurs
        // On affiche simplement la page d'inscription avec le formulaire
        return $this->render('pages/authentication/registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    // Route affichée après l'inscription, quand on dit à l'utilisateur d'aller vérifier son email
    #[Route(
        '/inscription/en-attente-de-la-verification-de-compte-par-email', // URL dans le navigateur
        name: 'app_visitor_waiting_for_email_verif',                     // Nom interne de la route
        methods: ['GET']                                                  // Méthode HTTP autorisée (lecture de page)
    )]
    public function waitingForEmailVerif(): Response
    {
        // Cette fonction ne fait qu'une chose :
        // afficher une page qui dit à l'utilisateur :
        // "Va vérifier ton email pour confirmer ton compte"

        return $this->render(
            'pages/authentication/registration/waiting_for_email_verif.html.twig'
            // Fichier Twig affiché dans le navigateur
        );
    }

    // Route appelée quand l'utilisateur clique sur le lien reçu par email
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,                     // Permet de lire les données de l'URL (ex: ?id=123)
        TranslatorInterface $translator,       // Sert à traduire les messages d'erreur
        UserRepository $userRepository,         // Sert à récupérer un utilisateur depuis la base de données
    ): Response {
        // 1️⃣ On récupère l'id de l'utilisateur depuis l'URL
        // Exemple : /verify/email?id=5
        $id = $request->query->get('id');

        // Si aucun id n'est présent dans l'URL → retour à l'inscription
        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        // 2️⃣ On cherche l'utilisateur correspondant à cet id en base de données
        /**
         * @var User
         */
        $user = $userRepository->find($id);

        // Si aucun utilisateur trouvé → retour à l'inscription
        if (null == $user) {
            return $this->redirectToRoute('app_register');
        }

        // 3️⃣ Vérification du lien de confirmation
        // Cette méthode :
        // - vérifie que le lien est valide
        // - vérifie qu'il n'a pas expiré
        // - vérifie qu'il n'a pas été modifié
        // - met automatiquement isVerified = true
        // - sauvegarde en base de données
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            // Si le lien est invalide ou expiré :
            // on affiche un message d'erreur
            $this->addFlash(
                'verify_email_error',
                $translator->trans(
                    $exception->getReason(),
                    [],
                    'VerifyEmailBundle'
                )
            );

            // Et on renvoie vers la page d'inscription
            return $this->redirectToRoute('app_register');
        }

        // 4️⃣ Si tout s'est bien passé :
        // On affiche un message de succès
        $this->addFlash(
            'success',
            'Votre compte a bien été vérifié, vous pouvez vous connecter.'
        );

        // Puis on redirige vers la page de connexion
        return $this->redirectToRoute('app_login');
    }
}
