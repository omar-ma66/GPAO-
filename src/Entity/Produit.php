<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    /**
     * @var Collection<int, OrdreFabrication>
     */
    #[ORM\OneToMany(targetEntity: OrdreFabrication::class, mappedBy: 'produit')]
    private Collection $ordreFabrications;

    /**
     * @var Collection<int, MatierePremiere>
     */
    #[ORM\ManyToMany(targetEntity: MatierePremiere::class, inversedBy: 'produits')]
    private Collection $matierePremiere;

    public function __construct()
    {
        $this->ordreFabrications = new ArrayCollection();
        $this->matierePremiere = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, OrdreFabrication>
     */
    public function getOrdreFabrications(): Collection
    {
        return $this->ordreFabrications;
    }

    public function addOrdreFabrication(OrdreFabrication $ordreFabrication): static
    {
        if (!$this->ordreFabrications->contains($ordreFabrication)) {
            $this->ordreFabrications->add($ordreFabrication);
            $ordreFabrication->setProduit($this);
        }

        return $this;
    }

    public function removeOrdreFabrication(OrdreFabrication $ordreFabrication): static
    {
        if ($this->ordreFabrications->removeElement($ordreFabrication)) {
            // set the owning side to null (unless already changed)
            if ($ordreFabrication->getProduit() === $this) {
                $ordreFabrication->setProduit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MatierePremiere>
     */
    public function getMatierePremiere(): Collection
    {
        return $this->matierePremiere;
    }

    public function addMatierePremiere(MatierePremiere $matierePremiere): static
    {
        if (!$this->matierePremiere->contains($matierePremiere)) {
            $this->matierePremiere->add($matierePremiere);
        }

        return $this;
    }

    public function removeMatierePremiere(MatierePremiere $matierePremiere): static
    {
        $this->matierePremiere->removeElement($matierePremiere);

        return $this;
    }
}
