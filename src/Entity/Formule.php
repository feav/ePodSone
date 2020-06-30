<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FormuleRepository")
 */
class Formule
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $message;


    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="integer")
     */
    private $month;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Abonnement", cascade={"persist", "remove"}, mappedBy="formule")
     */
    private $abonnements;

    /**
     * @ORM\Column(type="float")
     */
    private $price_shipping;

    /**
     * @ORM\Column(type="integer")
     */
    private $try_days;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripe_product_id;

    public function __construct()
    {
        $this->abonnements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getAbonnement(): ?Abonnement
    {
        return $this->abonnement;
    }

    public function setAbonnement(?Abonnement $abonnement): self
    {
        $this->abonnement = $abonnement;

        return $this;
    }


    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): self
    {
        $this->month = $month;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|Abonnement[]
     */
    public function getAbonnements(): Collection
    {
        return $this->abonnements;
    }

    public function addAbonnement(Abonnement $abonnement): self
    {
        if (!$this->abonnements->contains($abonnement)) {
            $this->abonnements[] = $abonnement;
            $abonnement->setFormule($this);
        }

        return $this;
    }

    public function removeAbonnement(Abonnement $abonnement): self
    {
        if ($this->abonnements->contains($abonnement)) {
            $this->abonnements->removeElement($abonnement);
            // set the owning side to null (unless already changed)
            if ($abonnement->getFormule() === $this) {
                $abonnement->setFormule(null);
            }
        }

        return $this;
    }

    public function getPriceShipping(): ?float
    {
        return $this->price_shipping;
    }

    public function setPriceShipping(float $price_shipping): self
    {
        $this->price_shipping = $price_shipping;

        return $this;
    }

    public function getTryDays(): ?int
    {
        return $this->try_days;
    }

    public function setTryDays(int $try_days): self
    {
        $this->try_days = $try_days;

        return $this;
    }

    public function __toString (  ) : string{
        return $this->getName();
    }

    public function getStripeProductId(): ?string
    {
        return $this->stripe_product_id;
    }

    public function setStripeProductId(?string $stripe_product_id): self
    {
        $this->stripe_product_id = $stripe_product_id;

        return $this;
    }
}
