<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Url;

class IllustrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('titleFile', FileType::class, ['data_class' => null, 'required' => true])
            ->add('license', TextType::class, ['required' => true])
            ->add('author', TextType::class, ['required' => true])
            ->add('urlSource', TextType::class, ['required' => true, "constraints" => [new Url()]])
            ->add('caption', TextareaType::class, ['required' => true])
			->add('photo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => $options["base_path"]])
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

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['base_path'] = $options['base_path'];
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\FileManagement',
			'base_path' => null
		]);
	}
}