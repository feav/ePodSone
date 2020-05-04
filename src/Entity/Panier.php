<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PanierRepository")
 */
class Panier
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Product", inversedBy="formules")
     */
    private $product;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Formule", inversedBy="paniers")
     */
    private $formules;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Coupon", inversedBy="paniers")
     */
    private $coupons;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="paniers")
     */
    private $user;

    /**
     * @ORM\Column(type="float")
     */
    private $total_price;

    /**
     * @ORM\Column(type="date")
     */
    private $emmission;

    /**
     * @ORM\Column(type="float")
     */
    private $price_shipping;

    public function __construct()
    {
        $this->product = new ArrayCollection();
        $this->formules = new ArrayCollection();
        $this->coupons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProduct(): Collection
    {
        return $this->product;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->product->contains($product)) {
            $this->product[] = $product;
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->product->contains($product)) {
            $this->product->removeElement($product);
        }

        return $this;
    }

    /**
     * @return Collection|Formule[]
     */
    public function getFormules(): Collection
    {
        return $this->formules;
    }

    public function addFormule(Formule $formule): self
    {
        if (!$this->formules->contains($formule)) {
            $this->formules[] = $formule;
        }

        return $this;
    }

    public function removeFormule(Formule $formule): self
    {
        if ($this->formules->contains($formule)) {
            $this->formules->removeElement($formule);
        }

        return $this;
    }

    /**
     * @return Collection|Coupon[]
     */
    public function getCoupons(): Collection
    {
        return $this->coupons;
    }

    public function addCoupon(Coupon $coupon): self
    {
        if (!$this->coupons->contains($coupon)) {
            $this->coupons[] = $coupon;
        }

        return $this;
    }

    public function removeCoupon(Coupon $coupon): self
    {
        if ($this->coupons->contains($coupon)) {
            $this->coupons->removeElement($coupon);
        }

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

    public function getTotalPrice(): ?float
    {
        return $this->total_price;
    }

    public function setTotalPrice(float $total_price): self
    {
        $this->total_price = $total_price;

        return $this;
    }

    public function getEmmission(): ?\DateTimeInterface
    {
        return $this->emmission;
    }

    public function setEmmission(\DateTimeInterface $emmission): self
    {
        $this->emmission = $emmission;

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
}
