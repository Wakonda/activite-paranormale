<?php

namespace App\Entity\Stores;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\WitchcraftTool;

/**
 * @ORM\Entity
 */
class WitchcraftToolStore extends Store {
	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\WitchcraftTool")
     */
    protected $witchcraftTool;

	public function getWitchcraftTool()
    {
        return $this->witchcraftTool;
    }

    public function setWitchcraftTool($witchcraftTool)
    {
        $this->witchcraftTool = $witchcraftTool;
    }
	
	public function getLinkedEntityName() {
		return $this->witchcraftTool->getRealClass();
	}
	
	public function __construct()
	{
		$this->setCategory(Store::WITCHCRAFT_TOOL_CATEGORY);
	}
}