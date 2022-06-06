<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class TestimonyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, array('label' => 'Titre', 'required' => true, 'constraints' => array(new NotBlank())))
            ->add('text', TextareaType::class, array('label' => 'Témoignage', 'required' => true, 'constraints' => array(new NotBlank())))
            ->add('theme', EntityType::class, array('label' => 'Thème', 'class'=>'App\Entity\Theme',
											'choice_label'=>'title',
											'placeholder' => "",
											'constraints' => array(new NotBlank()),
											'required' => true,
											'query_builder' => function(\App\Repository\ThemeRepository $repository) use ($language) { return $repository->getThemeByLanguage($language);}
											))
			->add('licence', EntityType::class, array('class'=>'App\Entity\Licence', 
											'choice_label'=>'title', 
											'required' => true,
											'placeholder' => "",
											'constraints' => array(new NotBlank()),
											'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
											))
			->add('pseudoUsed', TextType::class, array('constraints' => array(new NotBlank())))
			->add('emailAuthor', TextType::class, array('constraints' => array(new NotBlank(), new Email())))
			->add('save', SubmitType::class, array('label' => 'Create Post'))
			->add('addFile', SubmitType::class, array('label' => 'Create Post'));
    }

    public function getBlockPrefix()
    {
        return 'ap_mobile_testimonyuserparticipationtype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Testimony',
			'locale' => null
		));
	}
}