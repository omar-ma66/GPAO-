<?php

namespace App\Entity;

use App\Repository\OrdreFabricationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrdreFabricationRepository::class)]
class OrdreFabrication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $numero = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\Column(length: 30)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateArchivage = null;

    #[ORM\ManyToOne(
        targetEntity: Produit::class,
        inversedBy: 'ordreFabrications'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $produit = null;

    /**
     * @var Collection<int, EtapeFabrication>
     */
    #[ORM\OneToMany(
        targetEntity: EtapeFabrication::class,
        mappedBy: 'ordreFabrication',
        cascade: ['persist', 'remove']
    )]
    private Collection $etapeFabrications;

    #[ORM\ManyToOne(
        targetEntity: User::class,
        inversedBy: 'ordreFabrications'
    )]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, MatierePremiere>
     */
    #[ORM\ManyToMany(
        targetEntity: MatierePremiere::class,
        inversedBy: 'ordreFabrications'
    )]
    #[ORM\JoinTable(name: 'ordre_fabrication_matiere_premiere')]
    private Collection $matieresPremieres;

    public function __construct()
    {
        $this->etapeFabrications = new ArrayCollection();
        $this->matieresPremieres = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getDateArchivage(): ?\DateTimeImmutable
    {
        return $this->dateArchivage;
    }

    public function setDateArchivage(
        ?\DateTimeImmutable $dateArchivage
    ): static {
        $this->dateArchivage = $dateArchivage;

        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;

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
            $etapeFabrication->setOrdreFabrication($this);
        }

        return $this;
    }

    public function removeEtapeFabrication(
        EtapeFabrication $etapeFabrication
    ): static {
        $this->etapeFabrications->removeElement($etapeFabrication);

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, MatierePremiere>
     */
    public function getMatieresPremieres(): Collection
    {
        return $this->matieresPremieres;
    }

    /**
     * @param Collection<int, MatierePremiere> $matieresPremieres
     */
    public function setMatieresPremieres(
        Collection $matieresPremieres
    ): static {
        $this->matieresPremieres = $matieresPremieres;

        return $this;
    }

    public function addMatierePremiere(
        MatierePremiere $matierePremiere
    ): static {
        if (!$this->matieresPremieres->contains($matierePremiere)) {
            $this->matieresPremieres->add($matierePremiere);
        }

        return $this;
    }

    public function removeMatierePremiere(
        MatierePremiere $matierePremiere
    ): static {
        $this->matieresPremieres->removeElement($matierePremiere);

        return $this;
    }
}
