<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class BlogAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array('constraints' => array(new NotBlank())))
            ->add('banner', FileType::class, array('data_class' => null, 'required' => true))
            ->add('link', TextType::class, array('constraints' => array(new NotBlank())))
            ->add('rss', TextType::class, array('required' => false))
            ->add('text', TextareaType::class, array('constraints' => array(new NotBlank())))
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
            ->add('languageOfBlog', EntityType::class, array('class'=>'App\Entity\Language', 
					'choice_label'=>'title', 
					'required' => true,
					'query_builder' => function(EntityRepository $er) 
								{
									return $er->createQueryBuilder('u')
											->orderBy('u.title', 'ASC');
								},
					'constraints' => array(new NotBlank())
					))
			->add('photo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => null, 'data' => $builder->getData()->getBanner()])
			->add('active', CheckboxType::class)
		;
    }

    public function getBlockPrefix()
    {
        return 'ap_blog_blogadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\Blog',
		));
	}
}