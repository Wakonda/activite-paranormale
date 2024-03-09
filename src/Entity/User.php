<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;

use App\Service\Canonicalizer;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="ap_user")
 * @ORM\Entity(repositoryClass="App\Repository\AdminUserRepository")
 * @UniqueEntity(fields="username")
 */
 
class User implements UserInterface, LegacyPasswordAuthenticatedUserInterface
{
    const ROLE_ADMIN = "ROLE_ADMIN";
	const ROLE_MODERATOR = "ROLE_MODERATOR";
	const ROLE_JOURNALIST = "ROLE_JOURNALIST";
	const ROLE_CORRECTOR = "ROLE_CORRECTOR";
	const ROLE_TRANSLATOR = "ROLE_TRANSLATOR";
	const ROLE_ARCHIVIST = "ROLE_ARCHIVIST";
    const ROLE_SIMPLE = "ROLE_SIMPLE";
    const ROLE_TRADUCTOR = "ROLE_TRADUCTOR";
    const ROLE_BANNED = "ROLE_BANNED";
	const ROLE_DISABLED = "ROLE_DISABLED";
	const ROLE_DEFAULT = "ROLE_DEFAULT";

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	 protected $id;

 	/**
	 * @ORM\Column(type="string", length=255)
	 */
    protected $username;

 	/**
	 * @ORM\Column(name="username_canonical", type="string", length=255)
	 */
    protected $usernameCanonical;

 	/**
	 * @ORM\Column(type="string", length=255)
	 */
    protected $email;

 	/**
	 * @ORM\Column(name="email_canonical", type="string", length=255)
	 */
    protected $emailCanonical;

 	/**
	 * @ORM\Column(type="boolean")
	 */
    protected $enabled;

 	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
    protected $salt;

 	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
    protected $password;

    /**
     * @ORM\Column(name="last_login", type="datetime", length=255, nullable=true)
     */
    protected $lastLogin;

 	/**
	 * @ORM\Column(name="confirmation_token", type="string", length=255, nullable=true)
	 */
    protected $confirmationToken;

    /**
     * @ORM\Column(name="password_requested_at", type="datetime", length=255, nullable=true)
     */
    protected $passwordRequestedAt;

 	/**
	 * @ORM\Column(type="array")
	 */
    protected $roles;

    /**
	 * @Assert\File(maxSize="6000000", groups={"test"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
	 private $avatar;
	 
	/**
	 * @ORM\Column(name="civility", type="string", length=255, nullable=true)
     */
    protected $civility;

    /**
     * @ORM\Column(name="inscriptionDate", type="datetime", length=255, nullable=true)
     */
	protected $inscriptionDate;
	 
	/**
	  * @ORM\Column(name="birthDate", type="date", length=255, nullable=true)
	  */
	protected $birthDate;
	
 	/**
	  * var string $city
	  *
	  * @ORM\Column(name="city", type="string", length=255, nullable=true)
	  */
	protected $city;

	/**
	  * var string $siteWeb
	  *
	  * @ORM\Column(name="siteWeb", type="string", length=255, nullable=true)
	  */
	protected $siteWeb;
	  
	/**
	  * var string $blog
	  *
	  * @ORM\Column(name="blog", type="string", length=255, nullable=true)
	  */
	protected $blog;

	/**
	  * var string $presentation
	  *
      * @ORM\Column(name="presentation", type="text", nullable=true)
	  */
	protected $presentation;
	
	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Region")
     */
    protected $country;

    /**
     * @var text $donation
     *
     * @ORM\Column(name="donation", type="text", nullable=true)
     */
    private $donation;

	public function __construct()
	{
        $this->enabled = false;
        $this->roles = ["ROLE_SIMPLE"];
		$this->inscriptionDate = new \DateTime();
	}

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getUsername();
    }

	public function getUserIdentifier(): string
	{
		return $this->username;
	}

    /**
     * {@inheritdoc}
     */
    public function addRole($role)
    {
        $role = strtoupper($role);
        if ($role === self::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __serialize()
    {
        return [
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            $this->emailCanonical,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function __unserialize($data)
    {
        if (13 === count($data)) {
            // Unserializing a User object from 1.3.x
            unset($data[4], $data[5], $data[6], $data[9], $data[10]);
            $data = array_values($data);
        } elseif (11 === count($data)) {
            // Unserializing a User from a dev version somewhere between 2.0-alpha3 and 2.0-beta1
            unset($data[4], $data[7], $data[8]);
            $data = array_values($data);
        }

        list(
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            $this->emailCanonical
        ) = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return array_unique($this->roles);
    }

    /**
     * {@inheritdoc}
     */
    public function isSuperAdmin()
    {
        return $this->hasRole(static::ROLE_SUPER_ADMIN);
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }
	
	public function isEnabled(): bool
	{
		return $this->enabled == true;
	}

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return $this->username;
    }
	  
    /**
     * Set username
     *
     * @param text $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsernameCanonical()
    {
        return $this->usernameCanonical;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailCanonical()
    {
        return $this->emailCanonical;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Gets the last login time.
     *
     * @return \DateTime|null
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

	/**
     * Set avatar
     *
     * @param string $avatar
     */
    public function setAvatar($avatar) {
        $this->avatar = $avatar;
    }
 
    /**
     * Get avatar
     *
     * @return string
     */
    public function getAvatar() {
        return $this->avatar;
    }
	
	public function getFullPicturePath() {
        return null === $this->avatar ? null : $this->getUploadRootDir(). $this->avatar;
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/user/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadIconeLangue() {
        // the file property can be empty if the field is not required
        if (null === $this->avatar) {
            return;
        }

		if(is_object($this->avatar))
		{
			$NameFile = basename($this->avatar->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->avatar->move($this->getTmpUploadRootDir(), $NewNameFile);
			}else{
				if (is_object($this->avatar))
					$this->avatar->move($this->getUploadRootDir(), $NewNameFile);
			}
			if (is_object($this->avatar))
				$this->setAvatar($NewNameFile);
		} elseif(filter_var($this->avatar, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->avatar);
			$pi = pathinfo($this->avatar);
			$extension = $res = pathinfo(parse_url($this->avatar, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setAvatar($filename);
		}
    }

    /**
     * Set inscriptionDate
     *
     * @param datetime $inscriptionDate
     */
    public function setInscriptionDate($inscriptionDate)
    {
        $this->inscriptionDate = $inscriptionDate;
    }

    /**
     * Get inscriptionDate
     *
     * @return datetime 
     */
    public function getInscriptionDate()
    {
        return $this->inscriptionDate;
    }
	  
    /**
     * Set birthDate
     *
     * @param date $birthDate
     */
    public function setBirthDate($birthDate)
    {
        $this->birthDate = $birthDate;
    }

    /**
     * Get birthDate
     *
     * @return date 
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }
	  
    /**
     * Set city
     *
     * @param text $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Get city
     *
     * @return text 
     */
    public function getCity()
    {
        return $this->city;
    }
	  
    /**
     * Set siteWeb
     *
     * @param text $siteWeb
     */
    public function setSiteWeb($siteWeb)
    {
        $this->siteWeb = $siteWeb;
    }

    /**
     * Get siteWeb
     *
     * @return text 
     */
    public function getSiteWeb()
    {
        return $this->siteWeb;
    }
	  
    /**
     * Set blog
     *
     * @param text $blog
     */
    public function setBlog($blog)
    {
        $this->blog = $blog;
    }

    /**
     * Get blog
     *
     * @return text 
     */
    public function getBlog()
    {
        return $this->blog;
    }
	  
    /**
     * Set presentation
     *
     * @param text $presentation
     */
    public function setPresentation($presentation)
    {
        $this->presentation = $presentation;
    }

    /**
     * Get presentation
     *
     * @return text 
     */
    public function getPresentation()
    {
        return $this->presentation;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry(?Region $country)
    {
        $this->country = $country;
    }	

    public function getCivility()
    {
        return $this->civility;
    }

    public function setCivility($civility)
    {
        $this->civility = $civility;
    }
	
    /**
     * Set salt
     *
     * @param text $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * {@inheritdoc}
     */
    public function setUsernameCanonical()
    {
        $this->usernameCanonical = Canonicalizer::canonicalize($this->username);

        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEmailCanonical()
    {
        $this->emailCanonical = Canonicalizer::canonicalize($this->email);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($boolean)
    {
        $this->enabled = (bool) $boolean;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSuperAdmin($boolean)
    {
        if (true === $boolean) {
            $this->addRole(static::ROLE_SUPER_ADMIN);
        } else {
            $this->removeRole(static::ROLE_SUPER_ADMIN);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLastLogin(\DateTime $time = null)
    {
        $this->lastLogin = $time;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPasswordRequestedAt(?\DateTime $date)
    {
        $this->passwordRequestedAt = $date;

        return $this;
    }

    /**
     * Gets the timestamp that the user requested a password reset.
     *
     * @return null|\DateTime
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordRequestNonExpired($ttl)
    {
        return $this->getPasswordRequestedAt() instanceof \DateTime &&
               $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
    }

    /**
     * {@inheritdoc}
     */
    public function setRoles(array $roles)
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * Set donation
     *
     * @param text $donation
     */
    public function setDonation($donation)
    {
        $this->donation = $donation;
    }

    /**
     * Get donation
     *
     * @return text 
     */
    public function getDonation()
    {
        return $this->donation;
    }
}