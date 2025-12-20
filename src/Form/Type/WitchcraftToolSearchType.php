<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class WitchcraftToolSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		$language = $options['locale'];
		$builder->setMethod('GET');
        $builder
            ->add('keywords', TextType::class, ['required' => false])
            ->add('witchcraftThemeTool', EntityType::class, [
					'class'=>'App\Entity\WitchcraftThemeTool',
					'choice_label'=>'title',
					'required' => false,
					'constraints' => [new NotBlank()],
					'query_builder' => function(\App\Repository\WitchcraftThemeToolRepository $repository) use ($language)
					{ 
						return $repository->createQueryBuilder("p")->innerjoin("p.language", "l")->where("l.abbreviation = :language")->setParameter("language", $language)->orderBy("p.title", "ASC");
					}])
			->add('sort', ChoiceType::class, ['required' => false, 'choices' => ["witchcraftTool.search.PublicationDateUp" => "publicationDate#asc", "witchcraftTool.search.PublicationDateDown" => "publicationDate#desc"], "data" => "publicationDate#desc", 'translation_domain' => 'validators'])
		;
    }

    public function getBlockPrefix(): string
    {
        return 'form';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'locale' => 'fr',
			'validation_groups' => ['form_validation_only']
		]);
	}
}