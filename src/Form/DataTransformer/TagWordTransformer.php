<?php

namespace App\Form\DataTransformer;

use App\Entity\TagWord;
use App\Entity\Tags;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use Symfony\Component\HttpFoundation\Request;

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
		
		$request = Request::createFromGlobals();

		$className = array_reverse(explode("\\", get_class($entity)));
		$tags = $this->entityManager->getRepository(Tags::class)->findBy(array('idClass' => $entity->getId(), 'nameClass' => $className));
		
		$tagArray = [];
		
		if($request->query->has("fromId")) {
			$entityToCopy = $this->entityManager->getRepository(get_class($entity))->find($request->query->get("fromId"));
			
			$tags = $this->entityManager->getRepository(Tags::class)->findBy(array('idClass' => $entityToCopy->getId(), 'nameClass' => $className));
			$tw = [];

			foreach($tags as $tag) {
				$tagWord = $this->entityManager->getRepository(\App\Entity\TagWord::class)->findOneBy(array('internationalName' => $tag->getTagWord()->getInternationalName(), 'language' => $entity->getLanguage()));

				if(empty($tagWord))
					continue;

				$t = new \App\Entity\Tags();
				$t->setNameClass($className);
				$t->setIdClass($entity->getId());

				$tagArray[$tagWord->getId()] = $tagWord->getTitle();
			}
		} else {
			foreach($tags as $tag)
				$tagArray[$tag->getTagWord()->getId()] = $tag->getTagWord()->getTitle();
		}

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