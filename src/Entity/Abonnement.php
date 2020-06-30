<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AbonnementRepository")
 */
class Abonnement
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $start;

    /**
     * @ORM\Column(type="datetime")
     */
    private $end;

    /**
     * @ORM\Column(type="integer")
     */
    private $state;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Formule", inversedBy="abonnements")
     */
    private $formule;

    /**
     * @ORM\Column(type="integer")
     */
    private $is_paid;
    
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Panier", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="panier_id", referencedColumnName="id", nullable=true)
     */
    private $panier;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="abonnements")
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     */
    private $active;

    /**
     * @ORM\Column(type="integer")
     */
    private $resilie;

    /**
     * @ORM\Column(type="string", length=255, nullable=true))
     */
    private $subscription;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $yes;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $price;


    public function __construct()
    {
        $this->is_paid = 0;
        $this->active = 0;
        $this->resilie = 0;
        $this->state = 0;// n'est plus  utilisé
        $this->start = new \DateTime();
        $this->end = new \DateTime();

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(\DateTimeInterface $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(\DateTimeInterface $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(int $state): self
    {
        $this->state = $state;

        return $this;
    }


    public function getFormule(): ?Formule
    {
        return $this->formule;
    }

    public function setFormule(?Formule $formule): self
    {
        $this->formule = $formule;

        return $this;
    }

    public function getPanier(): ?Panier
    {
        return $this->panier;
    }

    public function setPanier(?Panier $panier): self
    {
        $this->panier = $panier;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getIsPaid(): ?int
    {
        return $this->is_paid;
    }

    public function setIsPaid(int $is_paid): self
    {
        $this->is_paid = $is_paid;

        return $this;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function setActive(int $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getResilie(): ?int
    {
        return $this->resilie;
    }

    public function setResilie(int $resilie): self
    {
        $this->resilie = $resilie;

        return $this;
    }

    public function getSubscription(): ?string
    {
        return $this->subscription;
    }

    public function setSubscription(string $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getYes(): ?string
    {
        return $this->yes;
    }

    public function setYes(?string $yes): self
    {
        $this->yes = $yes;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

}
