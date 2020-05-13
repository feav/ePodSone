<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ConfigRepository")
 */
class Config
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
    private $mkey;

    /**
     * @ORM\Column(type="string", length=2000)
     */
    private $value;

    public function getKeyName(){
        return array(
            array(
                "key" => "PAYPAL_EMAIL",
                "description" => "PayPal email" 
            ),
            array(
                "key" => "SENDER_EMAIL",
                "description" => "Sender email" 
            ),
            array(
                "key" => "STRIPE_PUBLIC_KEY",
                "description" => "Cle public Stripe" 
            ),
            array(
                "key" => "STRIPE_PRIVATE_KEY",
                "description" => "Cle secrete Stripe" 
            ),
            array(
                "key" => "GOOGLE_ADWORD_JS_",
                "description" => "Script Google Adword" 
            ),
            array(
                "key" => "GOOGLE_ANALYTIC_JS_",
                "description" => "Script Google Analytics " 
            ),
            array(
                "key" => "FACEBOOK_PIXEL_JS_",
                "description" => "Script Pixel Facebook " 
            )
        );
    }
    public function __toString (  ) : string{
        foreach ($this->getKeyName() as $key => $value) {
            if($value['key']==$this->getMkey())
                return $value['description'];
        }
        return 'NON DEFINI';
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMkey(): ?string
    {
        return $this->mkey;
    }

    public function setMkey(string $mkey): self
    {
        $this->mkey = $mkey;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
