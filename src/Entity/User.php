<?php
/**
 * ============================================
 * ENTITÉ USER - Représente un utilisateur
 * ============================================
 *
 * Une entité = une classe PHP qui correspond à une table en base de données
 * Chaque propriété = une colonne de la table
 *
 * Les attributs #[ORM\...] sont des "annotations" qui configurent Doctrine :
 * - #[ORM\Entity] = cette classe est une entité Doctrine
 * - #[ORM\Column] = cette propriété est une colonne en BDD
 */

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    /**
     * ID unique de l'utilisateur (clé primaire)
     * #[ORM\Id] = c'est la clé primaire
     * #[ORM\GeneratedValue] = auto-incrémenté
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?bool $actif = true;

    /**
     * Rôle de l'utilisateur
     *
     * Valeurs possibles :
     * - 'user' = utilisateur normal (par défaut)
     * - 'admin' = administrateur (accès au dashboard admin)
     *
     * nullable: true = peut être null (pour les anciens utilisateurs)
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $role = 'user';

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    /**
     * Récupère le rôle de l'utilisateur
     *
     * @return string|null Le rôle ('user', 'admin') ou null
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * Définit le rôle de l'utilisateur
     *
     * @param string|null $role Le nouveau rôle
     * @return static Pour permettre le chaînage (ex: $user->setRole('admin')->setNom('Test'))
     */
    public function setRole(?string $role): static
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Vérifie si l'utilisateur est administrateur
     *
     * @return bool true si admin, false sinon
     */
    public function isAdmin(): bool
    {
        // === compare la valeur ET le type (plus sûr que ==)
        return $this->role === 'admin';
    }
}
