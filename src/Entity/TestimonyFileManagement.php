<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\TestimonyFileManagementRepository')]
#[ORM\Table(name: 'testimonyfilemanagement')]
class TestimonyFileManagement extends FileManagement
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Testimony')]
	#[ORM\JoinColumn(nullable: false)]
    private $testimony;

	public function getMainEntityClassName()
	{
		return Testimony::class;
	}
	
	public function getClassName()
	{
		return 'TestimonyFileManagement';
	}

	public function getFileManagementPath()
	{
		return "extended/photo/testimony/";
	}

    public function setTestimony(Testimony $testimony)
    {
        $this->testimony = $testimony;
    }

    public function getTestimony()
    {
        return $this->testimony;
    }
}