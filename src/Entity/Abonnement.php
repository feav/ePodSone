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
     * @ORM\OneToMany(targetEntity="App\Entity\Formule", mappedBy="debut", orphanRemoval=true)
     */
    private $formule;

    /**
     * @ORM\Column(type="date")
     */
    private $debut;

    /**
     * @ORM\Column(type="date")
     */
    private $fin;

    public function __construct()
    {
        $this->formule = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Formule[]
     */
    public function getFormule(): Collection
    {
        return $this->formule;
    }

    public function addFormule(Formule $formule): self
    {
        if (!$this->formule->contains($formule)) {
            $this->formule[] = $formule;
            $formule->setDebut($this);
        }

        return $this;
    }

    public function removeFormule(Formule $formule): self
    {
        if ($this->formule->contains($formule)) {
            $this->formule->removeElement($formule);
            // set the owning side to null (unless already changed)
            if ($formule->getDebut() === $this) {
                $formule->setDebut(null);
            }
        }

        return $this;
    }

    public function getDebut(): ?\DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut(\DateTimeInterface $debut): self
    {
        $this->debut = $debut;

        return $this;
    }

    public function getFin(): ?\DateTimeInterface
    {
        return $this->fin;
    }

    public function setFin(\DateTimeInterface $fin): self
    {
        $this->fin = $fin;

        return $this;
    }
}
