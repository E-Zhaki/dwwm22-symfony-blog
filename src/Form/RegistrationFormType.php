<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class RegistrationFormType extends AbstractType // ✅ Une classe "FormType" : elle décrit la structure du formulaire
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // $builder = l'objet qui "construit" le formulaire champ par champ
        $builder

            // ✅ Champ prenom -> lié à la propriété firstName de l'entité User
            ->add('firstName', TextType::class)

            // ✅ Champ nom -> lié à lastName dans User
            ->add('lastName', TextType::class)

            // ✅ Champ email -> lié à email dans User
            ->add('email', EmailType::class)

            // ✅ Champ mot de passe avec confirmation (2 champs)
            // RepeatedType = Symfony génère automatiquement :
            // - un champ "password"
            // - un champ "confirm_password" (en interne)
            // et vérifie qu'ils sont identiques
            ->add('password', RepeatedType::class, [

                // Chaque champ répété sera de type PasswordType (input type="password")
                'type' => PasswordType::class,

                // Message si les 2 champs ne sont pas identiques
                'invalid_message' => 'Le mot de passe doit être identique à sa confirmation.',

                // Options HTML ajoutées sur les champs (ex: class CSS)
                'options' => ['attr' => ['class' => 'password-field']],

                // Obligatoire
                'required' => true,
            ])

            // ✅ Case à cocher : "j'accepte les CGU"
            ->add('agreeTerms', CheckboxType::class, [

                // mapped = false => ce champ N'EST PAS lié à une propriété dans User
                // Donc : rien n'est enregistré en base de données pour ce champ
                'mapped' => false,

                // constraints = règles de validation Symfony
                'constraints' => [
                    new IsTrue([
                        // Message si la case n'est pas cochée
                        'message' => 'Veuillez accepter les conditions générales d\'utilisation.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // ✅ data_class = User::class
        // Ça veut dire : ce formulaire remplit automatiquement un objet User
        // Exemple : firstName tapé -> $user->setFirstName(...)
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

