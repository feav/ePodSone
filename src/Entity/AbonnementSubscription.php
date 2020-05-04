<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AbonnementSubscriptionRepository")
 */
class AbonnementSubscription
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="subscriptions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Abonnement", inversedBy="subscriptions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $abonnement;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_sub;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_expire;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_resiliate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date_paid;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_paid;

    /**
     * @ORM\Column(type="float")
     */
    private $amount;

    public function __construct()
    {
        $this->date_sub = new \Datetime();
        $this->active = true;
        $this->is_paid = false;
        $this->is_resiliate = false;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateSub(): ?\DateTimeInterface
    {
        return $this->date_sub;
    }

    public function setDateSub(\DateTimeInterface $date_sub): self
    {
        $this->date_sub = $date_sub;

        return $this;
    }

    public function getDateExpire(): ?\DateTimeInterface
    {
        return $this->date_expire;
    }

    public function setDateExpire(\DateTimeInterface $date_expire): self
    {
        $this->date_expire = $date_expire;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getIsResiliate(): ?bool
    {
        return $this->is_resiliate;
    }

    public function setIsResiliate(bool $is_resiliate): self
    {
        $this->is_resiliate = $is_resiliate;

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

    public function getAbonnement(): ?Abonnement
    {
        return $this->abonnement;
    }

    public function setAbonnement(?Abonnement $abonnement): self
    {
        $this->abonnement = $abonnement;

        return $this;
    }

    public function getDatePaid(): ?\DateTimeInterface
    {
        return $this->date_paid;
    }

    public function setDatePaid(?\DateTimeInterface $date_paid): self
    {
        $this->date_paid = $date_paid;

        return $this;
    }

    public function getIsPaid(): ?bool
    {
        return $this->is_paid;
    }

    public function setIsPaid(bool $is_paid): self
    {
        $this->is_paid = $is_paid;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }
}
