<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Theme;

class ThemeEditType extends AbstractType
{
    private $entityManager;
	private $translator;
	private $locale;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
		$this->locale = $translator->getLocale();
    }

    public function getParent()
    {
        return EntityType::class;
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'locale' => 'fr',
			'query_builder' => function (Options $options) {
				return function(\App\Repository\ThemeRepository $repository) use ($options) {
					return $repository->getThemeByLanguage($options["locale"]);
				};
			},
			'choice_label' => function (Options $options) {
				return function ($category) use ($options) {
					$entities = $this->entityManager->getRepository(Theme::class)->getByLanguageForList($options["locale"], $this->locale);
					$datas = [];
					foreach($entities as $entity)
						$datas[$entity["internationalName"]] = $entity["title"];

					return $datas[$category->getInternationalName()];
				};
			}
		]);
	}
}