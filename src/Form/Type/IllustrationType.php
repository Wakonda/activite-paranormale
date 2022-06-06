<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class IllustrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('titleFile', FileType::class, array('data_class' => null, 'required' => true))
            ->add('license', TextType::class, array('required' => true))
            ->add('author', TextType::class, array('required' => true))
            ->add('urlSource', TextType::class, array('required' => true))
            ->add('caption', TextareaType::class, array('required' => true))
			->add('photo_selector', FileSelectorType::class, array('required' => false, 'mapped' => false, 'base_path' => $options["base_path"]))
		;
		
        $builder
            ->setAttribute('base_path', $options['base_path'])
        ;
		
		$builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event){
			$data = $event->getData();
			$form = $event->getForm();

			if(!empty($data))
				$event->getForm()->get("photo_selector")->setData($data->getTitleFile());
		});
    }

    public function getBlockPrefix()
    {
        return 'illustration';
    }
	
    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['base_path'] = $options['base_path'];
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\FileManagement',
			'validation_groups' => 'new_validation',
			'base_path' => null
		));
	}
}