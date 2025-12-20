<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\EventListener\InternationalNameFieldListener;
use App\Form\Field\SourceEditType;

class GenreAudiovisualAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('text', TextareaType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener())
			->add('language', EntityType::class, ['class'=>'App\Entity\Language', 
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'query_builder' => function(EntityRepository $er) 
				{
					return $er->createQueryBuilder('u')
							  ->orderBy('u.title', 'ASC');
				},
				'constraints' => [new NotBlank()]
			])
			->add('wikidata', TextType::class, ['required' => false])
            ->add('source', SourceEditType::class, ['required' => false])
			->add('fiction', CheckboxType::class, ['required' => true])
			->add('illustration', IllustrationType::class, ['required' => true, 'base_path' => 'GenreAudiovisual_Admin_ShowImageSelectorColorbox', 'file_path' => $builder->getData()->getAssetImagePath()])
		;
    }

    public function getBlockPrefix(): string
    {
        return 'ap_movie_genreaudiovisualadmintype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Movies\GenreAudiovisual'
		]);
	}
}