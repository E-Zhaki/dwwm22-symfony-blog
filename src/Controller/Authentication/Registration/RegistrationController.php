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

        // INSCRIPTION
    #[Route('/inscription', name: 'app_register')]
     public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response

        // $request -----------------------OBJET QUI REPRESENTE LA REQUETE ( RECUPERE LES DONNEES DU FORMULAIRE)
        // $userPasswordHasher-------------OUTIL POUR CHIFFRER (SECURISE LE MOT DE PASSE)
        // $entityManager-------------------GESTIONNAIRE DE BASE DE DONNEES (ENREGISTRER MODIFIER SUPPRIMER EN BASE DE DONNEE)
    {
        $user = new User(); // 1. CREATION UTILISATEUR VIDE QUE LE FORMULAIRE VA REMPLIR
        $form = $this->createForm(RegistrationFormType::class, $user); // 2. CREATION FORMULAIRE AVEC LA STRUCTURE REGISTRATION FORM DONT LES DONNEES VONT REMPLIR USER

        $form->handleRequest($request); // 3. RECUPERE LA REQUETE HTTP

        if ($form->isSubmitted() && $form->isValid()) { // 4. SI BOUTON EST CLIQUEZ ET QUE TOUS EST VALIDE ALORS...
            /** @var string $password */
            $password = $form->get('password')->getData(); // RECUPERE LE MOT DE PASSE TAPER PAR LUTILISATEUR

            // hashPassword transforme le mot de passe en version sécurisé
            $passwordHashed = $userPasswordHasher->hashPassword($user, $password);
            $user->setPassword($passwordHashed);

            // ---------$user contient : email, prénom, nom, mot de passe chiffré
            // ---------L'utilisateur est pret a etre enregistré en base de données

            // Champs ajouter par le back
            $user->setRoles(['ROLE_USER']);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());

            // Prépare la sauvegarde
            $entityManager->persist($user);
            // Execute en BDD
            $entityManager->flush();

            // EMAIL DE CONFIRMATION (LIEN)
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email', // Route vers laquelle le lien pointera
                $user, // genere un lien avec id=
                (new TemplatedEmail()) // email basé sur un template twig

                ->from(new Address('medecine-du-monde@gmail.com', 'Jean Dupont'))
                ->to((string) $user->getEmail())
                ->subject('Confirmation de votre compte sur le blog de Jean Dupont')
                ->htmlTemplate('emails/confirmation_email.html.twig') // le contenu de l'email est ici
            );

            return $this->redirectToRoute('app_visitor_waiting_for_email_verif'); // Aprés inscription il reste en attente de confirmation
        }

        // 3.
        return $this->render('pages/authentication/registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

        // INSCRIPTION EN ATTENTE AFFICHE EMAIL VA VERIFIER TON MAIL
        #[Route('/inscription/en-attente-de-la-verification-de-compte-par-email', name: 'app_visitor_waiting_for_email_verif', methods: ['GET'])]
            public function waitingForEmailVerif(): Response
            {
                return $this->render('pages/authentication/registration/waiting_for_email_verif.html.twig');
            }




        // VERIFICATION EMAIL RECUPERE ID DANS URL RETROUVE L UTILISATEUR VERIFIE LE LIEN SIGNE PUIS REDIRIGE
    #[Route('/verify/email', name: 'app_verify_email')]
        public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
        {
            $id = $request->query->get('id');

            if (null === $id) {
                return $this->redirectToRoute('app_register');
            }

            /**
             * @var User
             */
            $user = $userRepository->find($id);

            if (null == $user) {
                return $this->redirectToRoute('app_register');
            }

            // validate email confirmation link, sets User::isVerified=true and persists
            try {
                $this->emailVerifier->handleEmailConfirmation($request, $user);
            } catch (VerifyEmailExceptionInterface $exception) {
                $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

                return $this->redirectToRoute('app_register');
            }

            // @TODO Change the redirect on success and handle or remove the flash message in your templates
            $this->addFlash('success', 'Votre compte a bien été vérifié, vous pouvez vous connecter.');

            return $this->redirectToRoute('app_visitor_welcome');
        }
    }
