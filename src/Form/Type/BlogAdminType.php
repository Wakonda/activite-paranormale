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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use App\Entity\Blog;

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
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'query_builder' => function(EntityRepository $er) 
							{
								return $er->createQueryBuilder('u')
										->orderBy('u.title', 'ASC');
							},
				'constraints' => array(new NotBlank())
			))
            ->add('category', ChoiceType::class, ['choices' => 
			[
				'blog.admin.'.ucfirst(Blog::BLOG_CATEGORY) => Blog::BLOG_CATEGORY,
				'blog.admin.'.ucfirst(Blog::WEBSITE_CATEGORY) => Blog::WEBSITE_CATEGORY, 
				'blog.admin.'.ucfirst(Blog::FORUM_CATEGORY) => Blog::FORUM_CATEGORY,
				'blog.admin.'.ucfirst(Blog::STORE_CATEGORY) => Blog::STORE_CATEGORY
			 ], 
			 'expanded' => false, 'multiple' => false, 'required' => true, 'constraints' => [new NotBlank()], 'translation_domain' => 'validators'])
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