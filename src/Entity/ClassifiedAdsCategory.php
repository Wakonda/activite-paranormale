<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'classified_ads_category')]
#[ORM\Entity(repositoryClass: 'App\Repository\ClassifiedAdsCategoryRepository')]
class ClassifiedAdsCategory
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(type: 'string', length: 255)]
    private $title;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    protected $language;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\ClassifiedAdsCategory')]
    private $parentCategory;

	public function getParentCategoryTitle()
	{
		return !empty($parentCategory = $this->parentCategory) ? $parentCategory->getTitle() : null;
	}

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
		$this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function getParentCategory()
    {
        return $this->parentCategory;
    }

    public function setParentCategory($parentCategory)
    {
        $this->parentCategory = $parentCategory;
    }
}