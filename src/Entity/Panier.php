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

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $paiement_date;

    /**
     * @ORM\Column(type="float")
     */
    private $total_reduction;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Abonnement", mappedBy="paniers")
     */
    private $abonnements;



    public function __construct()
    {
        $this->price_shipping = 0;
        $this->total_reduction = 0;
        $this->total_price = 0;
        $this->status = 0;
        $this->token = 0;
        $this->emmission = new \DateTime();
        $this->coupons = new ArrayCollection();
        $this->commandes = new ArrayCollection();
    }

    public function refresh_price(){
        $sommes = 0;
        foreach ($this->commandes as $key => $commande) {
            $sommes +=  $commande->getTotalPrice();
        }
        foreach ($this->formules as $key => $formule) {
            $sommes +=  $formule->getPrice();
        }
        $reduction = 0;
        foreach ($this->coupons as $key => $coupon) {
            if($coupon->getTypeReduction()){
                $reduction +=  $sommes*$coupon->getPriceReduction()/100;
            }else{
                $reduction +=  $coupon->getPriceReduction();
            }
        }
        $this->total_price =  $sommes;
        $this->total_reduction = $reduction;
    }

    public function getId(): ?int
    {
        return $this->id;
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
        $this->refresh_price();

        return $this;
    }

    public function removeCoupon(Coupon $coupon): self
    {
        if ($this->coupons->contains($coupon)) {
            $this->coupons->removeElement($coupon);
        }
        $this->refresh_price();

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
        
        $this->refresh_price();

        return $this;
    }

    /**
     * @return Collection|Commande[]
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): self
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes[] = $commande;
            $commande->setPanier($this);
        }
        $this->refresh_price();

        return $this;
    }

    public function removeCommande(Commande $commande): self
    {
        if ($this->commandes->contains($commande)) {
            $this->commandes->removeElement($commande);
            // set the owning side to null (unless already changed)
            if ($commande->getPanier() === $this) {
                $commande->setPanier(null);
            }
        }
        $this->refresh_price();

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }
    public function initToken(): self
    {
        $this->token = md5($this->getId());

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPaiementDate(): ?\DateTimeInterface
    {
        return $this->paiement_date;
    }

    public function setPaiementDate(\DateTimeInterface $paiement_date): self
    {
        $this->paiement_date = $paiement_date;

        return $this;
    }

    public function getTotalReduction(): ?float
    {
        return $this->total_reduction;
    }

    public function setTotalReduction(float $total_reduction): self
    {
        $this->total_reduction = $total_reduction;

        return $this;
    }

    public function __toString (  ) : string{
        return $this->getToken();
    }


}
