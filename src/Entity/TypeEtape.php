<?php

namespace App\Entity;

// use App\Repository\TypeEtapeRepository;

use App\Repository\TypeEtapeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeEtapeRepository::class)]
class TypeEtape
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, EtapeFabrication>
     */
    #[ORM\OneToMany(
        targetEntity: EtapeFabrication::class,
        mappedBy: 'typeEtape'
    )]
    private Collection $etapeFabrications;

    public function __construct()
    {
        $this->etapeFabrications = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, EtapeFabrication>
     */
    public function getEtapeFabrications(): Collection
    {
        return $this->etapeFabrications;
    }

    public function addEtapeFabrication(
        EtapeFabrication $etapeFabrication
    ): static {
        if (!$this->etapeFabrications->contains($etapeFabrication)) {
            $this->etapeFabrications->add($etapeFabrication);
            $etapeFabrication->setTypeEtape($this);
        }

        return $this;
    }

    public function removeEtapeFabrication(
        EtapeFabrication $etapeFabrication
    ): static {
        if ($this->etapeFabrications->removeElement($etapeFabrication)) {
            if ($etapeFabrication->getTypeEtape() === $this) {
                $etapeFabrication->setTypeEtape(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}

