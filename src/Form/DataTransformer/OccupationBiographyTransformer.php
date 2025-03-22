<?php

namespace App\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use App\Entity\EntityLinkBiography;

class OccupationBiographyTransformer implements DataTransformerInterface
{
    private $entityManager;
    public $biography;
    public $defaultRoles;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Transforms an object (issue) to a string (number).
     *
     * @param  Issue|null $issue
     * @return string
     */
    public function transform(mixed $entity): mixed
    {
		if(!empty($this->defaultRoles))
			return $this->defaultRoles;

		return $this->entityManager->getRepository(EntityLinkBiography::class)->getOccupationsByBiography($this->biography->getId());
    }

    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $issueNumber
     * @return Issue|null
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform(mixed $values): mixed
    {
		$res = [];

		foreach($values as $value) {
			$entity = $this->entityManager->getRepository(EntityLinkBiography::class)->findOneBy(["biography" => $this->biography->getId(), "occupation" => $value]);
			
			if(empty($entity)) {
				$entity = new EntityLinkBiography();
				
				$entity->setBiography($this->biography);
				$entity->setOccupation($value);
				
				$this->entityManager->persist($entity);
			}
				
				$res[] = $entity;
		}

		return $res;
    }
}