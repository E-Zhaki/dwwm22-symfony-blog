<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['email'], message: 'Impossible de créer un compte avec cet email.')]
/*
 * ✅ Validation Symfony (pas Doctrine) :
 * - Empêche 2 utilisateurs d'avoir le même email.
 * - L'erreur remonte proprement dans le formulaire (au lieu d'un crash SQL).
 */

#[ORM\Entity(repositoryClass: UserRepository::class)]
// ✅ Dit à Doctrine : "cette classe est une entité" + quel Repository utiliser (UserRepository).

#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
/*
 * ✅ Contrainte Doctrine (niveau base de données) :
 * - En plus de UniqueEntity, la BDD garantit aussi l'unicité.
 * - Même si on contourne le formulaire, la BDD bloque.
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface /*
 * ✅ UserInterface : Symfony Security reconnaît cette classe comme un "utilisateur"
 * ✅ PasswordAuthenticatedUserInterface : Symfony sait que cette classe a un mot de passe (getPassword)
 */
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    // ✅ Clé primaire auto-incrémentée (gérée par Doctrine / la BDD). Tu ne la renseignes jamais toi-même.

    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    // ✅ Validation : l'utilisateur doit remplir l'email.

    #[Assert\Length(
        max: 180,
        maxMessage: "L'email doit contenir au maximum {{ limit }} caractères.",
    )]
    // ✅ Validation : limite la taille (souvent alignée sur la taille de la colonne SQL).

    #[Assert\Email(message: "L'email {{ value }} est invalide.")]
    // ✅ Validation : impose un format email valide.

    #[ORM\Column(length: 180)]
    private ?string $email = null;
    // ✅ Colonne email en base (max 180 caractères).

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];
    // ✅ Tableau des rôles (ex: ROLE_USER, ROLE_ADMIN). Stocké en base.

    /**
     * @var string The hashed password
     */
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    // ✅ Validation : il faut un mot de passe.

    #[Assert\Length(
        min: 12,
        max: 255,
        minMessage: 'Le mot de passe doit contenir au minimum {{ limit }} caractères.',
        maxMessage: 'Le mot de passe doit contenir au maximum {{ limit }} caractères.',
    )]
    // ✅ Validation : longueur mini 12 (sécurité) et max 255.

    #[Assert\Regex(
        pattern: '/^(?=.*[a-zà-ÿ])(?=.*[A-ZÀ-Ỳ])(?=.*[0-9])(?=.*[^a-zà-ÿA-ZÀ-Ỳ0-9]).{11,255}$/',
        match: true,
        message: "Le mot de passe doit être composé d'au moins une lettre majuscule et minuscule, d'un chiffre et d'un caractère spécial.",
    )]
    /*
     * ✅ Validation Regex :
     * - au moins 1 minuscule
     * - au moins 1 majuscule
     * - au moins 1 chiffre
     * - au moins 1 caractère spécial
     * ⚠️ Note : le ".{11,255}" ici dit 11 mini, mais toi tu veux 12 mini (Length min=12).
     * -> Ça peut créer une incohérence. Idéal : mettre ".{12,255}".
     */

    #[ORM\Column]
    private ?string $password = null;
    // ✅ Mot de passe HASHÉ en base. On ne stocke jamais le mot de passe en clair.

    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le prénom doit contenir au minimum {{ limit }} caractères.',
        maxMessage: 'Le prénom doit contenir au maximum {{ limit }} caractères.',
    )]
    #[ORM\Column(length: 255)]
    private ?string $firstName = null;
    // ✅ Prénom + validations.

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le nom doit contenir au minimum {{ limit }} caractères.',
        maxMessage: 'Le nom doit contenir au maximum {{ limit }} caractères.',
    )]
    #[ORM\Column(length: 255)]
    private ?string $lastName = null;
    // ✅ Nom + validations.

    #[ORM\Column]
    private bool $isVerified = false;
    /*
     * ✅ Sert pour la vérification par email :
     * - false tant que l'utilisateur n'a pas cliqué le lien de confirmation.
     * - passe à true quand le lien est validé.
     */

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;
    // ✅ Date de création du compte (tu la remplis souvent au moment de l'inscription).

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;
    // ✅ Date de dernière mise à jour (tu la mets à jour quand tu modifies le user).

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;
    // ✅ Date de vérification (quand l'utilisateur confirme son email).

    public function getId(): ?int
    {
        return $this->id;
        // ✅ Getter : permet de lire l'id.
    }

    public function getEmail(): ?string
    {
        return $this->email;
        // ✅ Getter email.
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
        // ✅ Setter email : permet de modifier l'email. "return $this" = chainage possible.
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
        /*
         * ✅ Symfony Security a besoin d'un identifiant unique pour l'utilisateur.
         * Ici, c'est l'email (c'est le "username" moderne de Symfony).
         */
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
        /*
         * ✅ Même si en base "roles" est vide, Symfony garantit que l'utilisateur a ROLE_USER.
         * array_unique évite les doublons.
         */
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
        // ✅ Setter roles.
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
        // ✅ Symfony l'utilise pour vérifier le login (comparaison hash).
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
        // ✅ Ici tu mets le mot de passe HASHÉ (ex: via UserPasswordHasher->hashPassword()).
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
        /*
         * ✅ Sécurité session :
         * Symfony peut sérialiser l'utilisateur en session.
         * Ici, au lieu de garder le hash réel du password, on met un hash CRC32C du hash.
         * But : éviter qu'un hash de mot de passe se balade en session.
         */
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
        /*
         * ✅ Avant on supprimait les données sensibles temporaires (plainPassword par ex).
         * Ici tu n'as pas de plainPassword stocké, donc vide.
         */
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
        // ✅ Getter prénom.
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
        // ✅ Setter prénom.
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
        // ✅ Getter nom.
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
        // ✅ Setter nom.
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
        // ✅ Getter date création.
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
        // ✅ Setter date création.
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
        // ✅ Getter date update.
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
        // ✅ Setter date update.
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
        // ✅ Getter bool (par convention Symfony : isXxx()).
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
        // ✅ Setter : tu le passes à true quand l'email est confirmé.
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
        // ✅ Getter date verification.
    }

    public function setVerifiedAt(?\DateTimeImmutable $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;

        return $this;
        // ✅ Setter date verification.
    }
}
