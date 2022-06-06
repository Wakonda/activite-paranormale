<?php
	namespace App\Service;
	
	use Doctrine\ORM\EntityManagerInterface;
	use App\Entity\TagWord;
	use App\Entity\Tags;
	
	class TagsManagingGeneric
	{
		private $em;

		public function __construct(EntityManagerInterface $em)
		{
			$this->em = $em;
		}

		public function saveTags($form, $className, $entityName, $newTagobject, $entityBindded)
		{
			$data = $form->all();

			if(isset($data['tags']))
			{
				$tags = array_filter($data['tags']->getNormData());

				if(!empty($tags))
				{
					$tagList = [];
					foreach($tags as $tag)
						$tagList[] = $tag->getTitle();

					// Save tags
					$em = $this->em;
				
					$nameClass = ucfirst($em->getClassMetadata($className)->getTableName());
					
					// Search if tags is already added
					$tagsIdExisted = array();
					$tagsIdToRemove = [];
					
					$tags = $em->getRepository(Tags::class)->findBy(array("idClass" => $entityBindded->getId(), "nameClass" => $nameClass));
					
					foreach($tags as $tag) {
						$tagsIdExisted[] = $tag->getTagWord()->getId();

						if(!in_array($tag->getTagWord()->getTitle(), $tagList))
							$tagsIdToRemove[] = $tag->getId();
					}

					foreach($tagList as $tag)
					{
						if(empty(trim($tag)))
							break;

						// Search if tag word exists
						$tagWord = $em->getRepository(TagWord::class)->findOneBy(array("title" => $tag, "language" => $entityBindded->getLanguage()));

						if(empty($tagWord))
						{
							$tagWord = new TagWord();
							$tagWord->setTitle($tag);
							$tagWord->setLanguage($entityBindded->getLanguage());
							$em->persist($tagWord);
							$em->flush($tagWord);
						}

						if(!in_array($tagWord->getId(), $tagsIdExisted))
						{		
							$tags = clone $newTagobject;
							$tags->setNameClass($nameClass);
							$tags->setIdClass($entityBindded->getId());
							$tags->setEntity($entityBindded);
							$tags->setTagWord($tagWord);
							$em->persist($tags);
							$em->flush($tags);
						}
					}

					foreach($tagsIdToRemove as $tagId)
					{
						$tag = $em->getRepository(Tags::class)->find($tagId);

						if(!empty($tag))
						{
							$em->remove($tag);
							$em->flush($tag);
						}
					}
				}
			}
		}
	}