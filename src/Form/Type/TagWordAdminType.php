<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Form\Type\FileSelectorType;
use App\Form\Field\SourceEditType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\EventListener\InternationalNameFieldListener;

class TagWordAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('required' => false))
            ->add('source', SourceEditType::class, array('required' => false))
            ->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener())
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
				'choice_label'=>'title', 
				'required' => true,
				'query_builder' => function(EntityRepository $er) 
				{
					return $er->createQueryBuilder('u')
							->orderBy('u.title', 'ASC');
				},
				'constraints' => array(new NotBlank())
			))
			->add('illustration', IllustrationType::class, array('required' => false, 'base_path' => 'TagWord_Admin_ShowImageSelectorColorbox'));
    }

    public function getBlockPrefix()
    {
        return 'ap_tags_tagwordtype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\TagWord',
		));
	}
}