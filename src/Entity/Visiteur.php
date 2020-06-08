<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VisiteurRepository")
 */
class Visiteur
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ip;

    /**
     * @ORM\Column(type="datetime")
     */
    private $last_data_visite;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nb_achat;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $user_id;

    public function __construct()
    {
        $this->nb_achat = 0;
        $this->last_data_visite = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getLastDataVisite(): ?\DateTimeInterface
    {
        return $this->last_data_visite;
    }

    public function setLastDataVisite(\DateTimeInterface $last_data_visite): self
    {
        $this->last_data_visite = $last_data_visite;

        return $this;
    }

    public function getNbAchat(): ?int
    {
        return $this->nb_achat;
    }

    public function setNbAchat(?int $nb_achat): self
    {
        $this->nb_achat = $nb_achat;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }
}
