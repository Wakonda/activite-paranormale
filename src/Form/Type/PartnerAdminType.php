<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;

class PartnerAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('link', TextType::class, array('required' => true, 'constraints' => array(new NotBlank())))
            ->add('photo', FileType::class, array('data_class' => null, 'required' => true))
			->add('photo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => null, 'data' => $builder->getData()->getPhoto()])
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language',
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'constraints' => array(new NotBlank()),
				'query_builder' => function(EntityRepository $er)
							{
								return $er->createQueryBuilder('u')
										->orderBy('u.title', 'ASC');
							},
			))
			->add('active', CheckboxType::class, ['required' => true])
			->add('displayPage', CheckboxType::class, ['required' => false])
            ->add('color', ColorType::class, ['required' => false])
            ->add('icon', TextType::class, ['required' => false, "attr" => ["placeholder" => "fab fa-vk"]])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'ap_partner_partneradmintype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Partner',
		));
	}
}