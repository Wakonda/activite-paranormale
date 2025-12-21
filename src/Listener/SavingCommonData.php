<?php

namespace App\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\APPurifierHTML;
use App\Service\APParseHTML;
use App\Entity\Interfaces\SearchEngineInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class SavingCommonData
{
	private $parameterBag;
	private $em;

	public function __construct(EntityManagerInterface $em, ParameterBagInterface $parameterBag)
	{
		$this->em = $em;
		$this->parameterBag = $parameterBag;
	}

	private function purifierText($text)
	{
		return $text;
		$purifier = new APPurifierHTML();
		return $purifier->purifier($text);
	}

	private function saveImageFromUrlFromText($text, $entity)
	{
		return $text;
		$parser = new APParseHTML();

		return $parser->saveImageFromURL($text, $entity->getAssetImagePath());
	}

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

		$this->cleanText($entity, $entityManager);
    }

	public function postPersist(LifecycleEventArgs $args): void
	{
		if($args->getObject() instanceof SearchEngineInterface) {
			$searchEngine = new \App\Service\SearchEngine();
			$searchEngine->setParams($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASSWORD"], $this->parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR."private".DIRECTORY_SEPARATOR."search".DIRECTORY_SEPARATOR.$_ENV["SEARCH_SQLITE_PATH"], null);

			if(method_exists($args->getObject(), "getState") and !$args->getObject()->getState()->getDisplayState())
				return;

			if(empty($args->getObject()->getLanguage()))
				return;

			$data = [
				"id" => $args->getObject()->getId(),
				"language" => $args->getObject()->getLanguage()->getAbbreviation(),
				"classname" => $this->em->getClassMetadata(get_class($args->getObject()))->getTableName(),
				"title" => $args->getObject()->getTitle(),
				"text" => $args->getObject()->getText()
			];

			$searchEngine->insert($data);

			if(method_exists($args->getObject(), "getIllustration") and !empty($illustration = $args->getObject()->getIllustration()))
				$searchEngine->insertImage(array_merge($data, [$illustration->getRealNameFile()]));
		}
	}

	public function postUpdate(PostUpdateEventArgs $args) {
		if($args->getObject() instanceof SearchEngineInterface) {
			$searchEngine = new \App\Service\SearchEngine();
			$searchEngine->setParams($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASSWORD"], $this->parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR."private".DIRECTORY_SEPARATOR."search".DIRECTORY_SEPARATOR.$_ENV["SEARCH_SQLITE_PATH"], null);

			if($args->getObject()->getArchive() == true) {
				$searchEngine->delete($args->getObject()->getId(), $this->em->getClassMetadata(get_class($args->getObject()))->getTableName());
			} else {
				$data = [
					"id" => $args->getObject()->getId(),
					"language" => $args->getObject()->getLanguage()->getAbbreviation(),
					"classname" => $this->em->getClassMetadata(get_class($args->getObject()))->getTableName(),
					"title" => $args->getObject()->getTitle(),
					"text" => $args->getObject()->getText()
				];

				$searchEngine->insert($data);

				if(method_exists($args->getObject(), "getIllustration") and !empty($illustration = $args->getObject()->getIllustration()))
					$searchEngine->insertImage(array_merge($data, ["img" => $illustration->getRealNameFile()]));
			}
		}
	}

	public function preRemove(LifecycleEventArgs $args) {
		if($args->getObject() instanceof SearchEngineInterface) {
			$searchEngine = new \App\Service\SearchEngine();
			$searchEngine->setParams($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASSWORD"], $this->parameterBag->get('kernel.project_dir').DIRECTORY_SEPARATOR."private".DIRECTORY_SEPARATOR."search".DIRECTORY_SEPARATOR.$_ENV["SEARCH_SQLITE_PATH"], null);

			$searchEngine->delete($args->getObject()->getId(), $this->em->getClassMetadata(get_class($args->getObject()))->getTableName());
		}
	}

	public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();
		$uow = $entityManager->getUnitOfWork();

		if(get_parent_class($entity) == 'App\Entity\MappedSuperclassBase')
		{
			$text = $entity->getText();
			$text = $this->purifierText($text);

			if(method_exists($entity, 'getAssetImagePath'))
				$text = $this->saveImageFromUrlFromText($text, $entity);

			$parser = new APParseHTML();
			$text = $parser->centerImageInHTML($text, $entity);

			$entity->setText($text);

			$meta = $entityManager->getClassMetadata(get_class($entity));
			$uow->recomputeSingleEntityChangeSet($meta, $entity);
		}

		$this->cleanText($entity, $entityManager);
    }

	private function cleanText($entity, $entityManager) {
        $metadata = $this->em->getClassMetadata(get_class($entity));

        foreach ($metadata->fieldMappings as $field => $mapping) {
            if ($mapping['type'] === 'text') {
				$setterMethod = "set".ucfirst($field);
				$getterMethod = "get".ucfirst($field);
				if(method_exists($entity, $setterMethod))
				{					
					$text = $entity->$getterMethod();

					if($this->isJson($entity->$getterMethod()))
						continue;	

					$text = $this->purifierText($text);

					if(method_exists($entity, 'getAssetImagePath'))
						$text = $this->saveImageFromUrlFromText($text, $entity);

					if(str_contains($text, "<h2>")) {
						$text = str_replace("<h5>", "<h6>", $text);
						$text = str_replace("<h4>", "<h5>", $text);
						$text = str_replace("<h3>", "<h4>", $text);
						$text = str_replace("<h2>", "<h3>", $text);

						$text = str_replace("</h5>", "</h6>", $text);
						$text = str_replace("</h4>", "</h5>", $text);
						$text = str_replace("</h3>", "</h4>", $text);
						$text = str_replace("</h2>", "</h3>", $text);
					}

					$text = str_replace("<hr>", "", $text);

					$parser = new APParseHTML();
					$text = $parser->centerImageInHTML($text, $entity);

					// Replace "test:test" by "test: test" if needed	
					$text = preg_replace('/:(?![\/\s])/', ': ', $text);

					$entity->$setterMethod($text);
				}
            }
        }
	}

	private function isJson(?string $string): bool
	{
		if(empty($string))
			return false;

		json_decode($string);
		return json_last_error() === JSON_ERROR_NONE;
	}
}