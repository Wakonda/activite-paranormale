<?php

namespace App\Form\DataTransformer;

use App\Entity\TagWord;
use App\Entity\Tags;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TagWordTransformer implements DataTransformerInterface
{
    private $entityManager;

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
    public function transform($entity)
    {
		if(is_array($entity))
			return $entity;

		$className = array_reverse(explode("\\", get_class($entity)));
		$tags = $this->entityManager->getRepository(Tags::class)->findBy(array('idClass' => $entity->getId(), 'nameClass' => $className));
		
		$tagArray = array();

		foreach($tags as $tag)
			$tagArray[$tag->getTagWord()->getId()] = $tag->getTagWord()->getTitle();

        return $tagArray;
    }

    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $issueNumber
     * @return Issue|null
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($tags)
    {
		$res = [];

		if(empty($tags))
			return $res;

		foreach($tags as $tag)
		{
			if(substr($tag, 0, 2) == "__") {
				$tw = new TagWord();
				$tw->setTitle(substr($tag, 2, strlen($tag)));
				$res[] = $tw;
				
			} else {
				$res[] = $this->entityManager->getRepository(TagWord::class)->find($tag);
			}
		}
		
		return $res;
    }
}