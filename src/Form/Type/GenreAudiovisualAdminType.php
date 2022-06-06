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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\EventListener\InternationalNameFieldListener;
use App\Form\Field\SourceEditType;

class GenreAudiovisualAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('photo', FileType::class, array('data_class' => null, 'required' => true))
            ->add('text', TextareaType::class, array('required' => true, 'constraints' => [new NotBlank()]))
            ->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener())
			->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
				'choice_label'=>'title', 
				'required' => true,
				'query_builder' => function(EntityRepository $er) 
				{
					return $er->createQueryBuilder('u')
							  ->orderBy('u.title', 'ASC');
				},
				'constraints' => [new NotBlank()]
			))
            ->add('source', SourceEditType::class, array('required' => false))
			->add('photo_selector', FileSelectorType::class, array('required' => false, 'mapped' => false, 'base_path' => 'GenreAudiovisual_Admin_ShowImageSelectorColorbox', 'data' => $builder->getData()->getPhoto()))
		;
    }

    public function getBlockPrefix()
    {
        return 'ap_movie_genreaudiovisualadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Movies\GenreAudiovisual'
		]);
	}
}