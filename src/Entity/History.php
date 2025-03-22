<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Table(name: 'history')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\HistoryRepository')]
class History
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\OneToMany(targetEntity: HistoryDetail::class, cascade: ['persist', 'remove'], mappedBy: 'history')]
    private $historyDetails;

    public function getId()
    {
        return $this->id;
    }
	
	public function __construct()
    {
        $this->historyDetails = new ArrayCollection();
    }

    public function getHistoryDetails()
    {
        return $this->historyDetails;
    }
     
    public function addHistoryDetail(HistoryDetail $historyDetail)
    {
        $this->historyDetails->add($historyDetail);
        $historyDetail->setHistory($this);
    }
}