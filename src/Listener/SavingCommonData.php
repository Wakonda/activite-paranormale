<?php

namespace App\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

use App\Service\APPurifierHTML;
use App\Service\APParseHTML;
use App\Entity\Interfaces\SearchEngineInterface;

class SavingCommonData
{
	private $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

	private function purifierText($text)
	{
		$purifier = new APPurifierHTML();
		return $purifier->purifier($text);
	}

	private function saveImageFromUrlFromText($text, $entity)
	{
		$parser = new APParseHTML($this->container);
		
		if(empty($this->container->get('request_stack')->getCurrentRequest()))
			return null;
		
		return $parser->saveImageFromURL($text, $entity->getAssetImagePath(), str_replace("app_dev.php", "", $this->container->get('request_stack')->getCurrentRequest()->getUriForPath($entity->getAssetImagePath())));
	}

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

		if(get_parent_class($entity) == 'App\Entity\MappedSuperclassBase')
		{
			$text = $entity->getText();
			$text = $this->purifierText($text);
			
			if(method_exists($entity, 'getAssetImagePath'))
				$text = $this->saveImageFromUrlFromText($text, $entity);

			$parser = new APParseHTML($this->container);
			$text = $parser->centerImageInHTML($text, $entity);
			$entity->setText($text);
		}
    }
	
	public function postPersist(LifecycleEventArgs $args): void
	{
		if($args->getObject() instanceof SearchEngineInterface) {
			$searchEngine = new \App\Service\SearchEngine();
			$searchEngine->setParams($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASSWORD"], $this->container->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR."private".DIRECTORY_SEPARATOR."search".DIRECTORY_SEPARATOR.$_ENV["SEARCH_SQLITE_PATH"], null);

			$em = $this->container->get('doctrine.orm.entity_manager');

			$data = [
				"id" => $args->getObject()->getId(),
				"language" => $args->getObject()->getLanguage()->getAbbreviation(),
				"classname" => $em->getClassMetadata(get_class($args->getObject()))->getTableName(),
				"title" => $args->getObject()->getTitle(),
				"text" => $args->getObject()->getText()
			];

			$searchEngine->insert($data);
			$searchEngine->insertImage(array_merge($data, [$args->getObject()->getIllustration()->getRealNameFile()]));
		}
	}
	
	public function postUpdate(LifecycleEventArgs $args) {//die('llll');
		if($args->getObject() instanceof SearchEngineInterface) {//dd($args->getObject()->getArchive() == true);
			$searchEngine = new \App\Service\SearchEngine();
			$searchEngine->setParams($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASSWORD"], $this->container->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR."private".DIRECTORY_SEPARATOR."search".DIRECTORY_SEPARATOR.$_ENV["SEARCH_SQLITE_PATH"], null);

			$em = $this->container->get('doctrine.orm.entity_manager');

			if($args->getObject()->getArchive() == true) {
				$searchEngine->delete($args->getObject()->getId(), $em->getClassMetadata(get_class($args->getObject()))->getTableName());
			} else {
				$data = [
					"id" => $args->getObject()->getId(),
					"language" => $args->getObject()->getLanguage()->getAbbreviation(),
					"classname" => $em->getClassMetadata(get_class($args->getObject()))->getTableName(),
					"title" => $args->getObject()->getTitle(),
					"text" => $args->getObject()->getText()
				];
				
				// if(!isset($data["id"]))
// dd($data);
				$searchEngine->insert($data);
				$searchEngine->insertImage(array_merge($data, ["img" => $args->getObject()->getIllustration()->getRealNameFile()]));
			}
		}
	}
	
	public function postRemove(LifecycleEventArgs $args): void
    {
		if($args->getObject() instanceof SearchEngineInterface) {
			$searchEngine = new \App\Service\SearchEngine();
			$searchEngine->setParams($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASSWORD"], $this->container->getParameter('kernel.project_dir').DIRECTORY_SEPARATOR."private".DIRECTORY_SEPARATOR."search".DIRECTORY_SEPARATOR.$_ENV["SEARCH_SQLITE_PATH"], null);

			$em = $this->container->get('doctrine.orm.entity_manager');

			$searchEngine->delete($args->getObject()->getId(), $em->getClassMetadata(get_class($args->getObject()))->getTableName());
		}
    }

	public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
		$uow = $entityManager->getUnitOfWork();

		if(get_parent_class($entity) == 'App\Entity\MappedSuperclassBase')
		{
			$text = $entity->getText();
			$text = $this->purifierText($text);
			
			if(method_exists($entity, 'getAssetImagePath'))
				$text = $this->saveImageFromUrlFromText($text, $entity);
			
			$parser = new APParseHTML($this->container);
			$text = $parser->centerImageInHTML($text, $entity);

			$entity->setText($text);
			
			$meta = $entityManager->getClassMetadata(get_class($entity));
			$uow->recomputeSingleEntityChangeSet($meta, $entity);
		}
    }
}