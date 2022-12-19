<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ArtistSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$menuLetter = [];
		$i = 0;
		for ($i=ord("A");$i<=ord("Z");$i++)
			$menuLetter[chr($i)] = chr($i);
		for ($i = 0; $i <= 9; $i++)
			$menuLetter[$i] = $i;
		
		$language = $options['locale'];
		$builder->setMethod('GET');
        $builder
            ->add('title', TextType::class, ['required' => false])
            ->add('country', EntityType::class, [
					'class'=>'App\Entity\Country',
					'choice_label'=>'title',
					'required' => false,
					'query_builder' => function(\App\Repository\CountryRepository $repository) use ($language) {
						return $repository->getCountryByLanguage($language);
					}])
            ->add('genre', EntityType::class, array('class'=>'App\Entity\MusicGenre',
					'choice_label'=>'title',
					'required' => false,
					'query_builder' => function(\App\Repository\MusicGenreRepository $er)
						{
							return $er->createQueryBuilder('u')
									  ->orderBy('u.title', 'ASC');
						},
				))
			->add('bandStartingWith', ChoiceType::class, ['required' => false, 'choices' => $menuLetter])
		;
    }

    public function getBlockPrefix()
    {
        return 'form';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'locale' => 'fr',
			'validation_groups' => ['form_validation_only']
		));
	}
}