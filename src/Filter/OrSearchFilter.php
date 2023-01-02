<?php
// api/src/Filter/OrSearchFilter.php

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use DoctrineORMQueryBuilder;
use DoctrinePersistenceManagerRegistry;
use PsrLogLoggerInterface;
use SymfonyComponentHttpFoundationRequestStack;
use SymfonyComponentSerializerNameConverterNameConverterInterface;
use ApiPlatformCoreExceptionInvalidArgumentException;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Annotations\AnnotationReader;
use ApiPlatform\Core\Annotation\ApiFilter;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManagerInterface;

final class OrSearchFilter extends AbstractContextAwareFilter
{
    private $searchParameterName;
    private $em;

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [])
    {
		if($property != "or_search")
			return;

		if(empty($value))
			return;

		$properties = [];

		$reader = new AnnotationReader();
		AnnotationRegistry::registerLoader('class_exists');
		$reflectionClass = new \ReflectionClass($resourceClass);
		
		$annotations = $reader->getClassAnnotations($reflectionClass);
		
		foreach($annotations as $annotation) {
			if(get_class($annotation) == ApiFilter::class) {
				if($annotation->filterClass == OrSearchFilter::class) {
					foreach($annotation->properties as $p) {
						$properties[$p] = null;
					}
				}
			}
		}

		if(empty($properties))
			return;

        $this->addWhere($queryBuilder, $value, $queryNameGenerator->generateParameterName($property), $properties, $resourceClass);
    }

    private function addWhere($queryBuilder, $word, $parameterName, $properties, $resourceClass)
    {
		$wordArray = explode(" ", $word);
		$subQueryBuilder = $this->managerRegistry->getManager()->createQueryBuilder();
		
		$aliasSubQuery = "subQuery".uniqid();
		$subQueryBuilder->select($aliasSubQuery.".id")->from($resourceClass, $aliasSubQuery);
		
		$aliasQueryBuilder = null;
		
		foreach($queryBuilder->getDQLParts()["from"] as $from) {
			if($from->getFrom() == $resourceClass)
				$aliasQueryBuilder = $from->getAlias();
		}

		if(empty($aliasQueryBuilder))
			return;

		$joinArray = [];

        $orExp = $subQueryBuilder->expr()->orX();

        foreach ($properties as $prop => $ignored) {
			$alias = $subQueryBuilder->getRootAliases()[0];
			$joins = explode(".", $prop);

			if(count($joins) > 1) {
				foreach($joins as $key => $join) {
					if($key != count($joins) - 1) {
						$joinArray[$alias.".".$join] = $alias."_".$join;
						$alias = $alias."_".$join;
					}
					else
						$prop = $join;
				}
			}

			foreach($wordArray as $key => $w) {
				$orExp->add($subQueryBuilder->expr()->like('LOWER('. $alias. '.' . $prop. ')', ':' . $parameterName.$key));
				$queryBuilder->setParameter($parameterName.$key, '%' . strtolower($w). '%');
			}
        }
		
		foreach($joinArray as $key => $value)
			$subQueryBuilder->join($key, $value);

        $subQueryBuilder->andWhere('(' . $orExp . ')');

		$queryBuilder->andWhere($aliasQueryBuilder.".id IN (".$subQueryBuilder->getDQL().")");
    }

    /** {@inheritdoc} */
    public function getDescription(string $resourceClass): array
    {
        $props = $this->getProperties();
        if (null===$props) {
            throw new InvalidArgumentException('Properties must be specified');
        }

        $description['or_search'] = [
                'property' => implode(', ', array_keys($props)),
                'type' => 'string',
                'required' => false,
                'swagger' => [
                    'description' => 'Selects entities where each search term is found somewhere in at least one of the specified properties',
                ]
            ];
			
		return $description;
    }
	
    protected function isPropertyNested(string $property): bool
    {
        if (\func_num_args() > 1) {
            $resourceClass = (string) func_get_arg(1);
        } else {
            if (__CLASS__ !== static::class) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a second `$resourceClass` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.1.', __FUNCTION__), E_USER_DEPRECATED);
                }
            }
            $resourceClass = null;
        }

        $pos = strpos($property, '.');
        if (false === $pos) {
            return false;
        }

        return null !== $resourceClass && $this->getClassMetadata($resourceClass)->hasAssociation(substr($property, 0, $pos));
    }
}