<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $nom_client;

    #[ORM\Column(type: 'string', length: 255)]
    private $produit;

    #[ORM\Column(type: 'integer')]
    private $quantite;

    #[ORM\Column(type: 'datetime')]
    private $date_commande;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $couleur;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private $prix;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getNomClient(): ?string
    {
        return $this->nom_client;
    }
    public function setNomClient(string $nom_client): self
    {
        $this->nom_client = $nom_client;
        return $this;
    }
    public function getProduit(): ?string
    {
        return $this->produit;
    }
    public function setProduit(string $produit): self
    {
        $this->produit = $produit;
        return $this;
    }
    public function getQuantite(): ?int
    {
        return $this->quantite;
    }
    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }
    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->date_commande;
    }
    public function setDateCommande(\DateTimeInterface $date_commande): self
    {
        $this->date_commande = $date_commande;
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): self
    {
        $this->couleur = $couleur;
        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(?string $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
