<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use App\Form\Field\DatePartialType;
use App\Entity\Biography;

use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class ArtistBiographiesAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$dataClass = $options["data_class"];
		$occupationArray = [];
		
		foreach($dataClass::getOccupations() as $occupation)
			$occupationArray["biographies.admin.".ucfirst($occupation)] = $occupation;

        $builder
		    ->add('biography', Select2EntityType::class, [
				'multiple' => false,
				'remote_route' => 'Biography_Admin_Autocomplete',
				'class' => Biography::class,
				'page_limit' => 10,
				'primary_key' => 'id',
				'text_property' => 'title',
				'allow_clear' => true,
				'allow_add' => [
					'enabled' => true,
					'new_tag_text' => ' (+)',
					'new_tag_prefix' => '__',
					'tag_separators' => '[","]'
				],
				'delay' => 250,
				'cache' => false,
				'label' => 'biographies.admin.Biography',
				'translation_domain' => 'validators',
				"query_parameters" => $options["query_parameters"],
				'attr' => ["class" => "biography", "data-req_params" => (!empty($options["req_params"]) ? json_encode($options["req_params"]) : null)]
			])
			->add('occupation', ChoiceType::class, [
				'choices'  => $occupationArray,
				'label' => 'biographies.admin.Occupation', 'translation_domain' => 'validators', "attr" => ["class" => "form-control list-occupation"]
			])
			->add('wikidata', HiddenType::class, ["attr" => ["class" => "wikidata"], "mapped" => false])
            ->add('role', TextType::class, ['label' => 'biographies.admin.StageName', 'translation_domain' => 'validators', 'required' => false, "attr" => ["class" => "form-control role-biography"], "label_attr" => ["class" => "role-biography"]])
            ->add('startYear', DatePartialType::class, ['label' => 'biographies.admin.StartYear', 'translation_domain' => 'validators', 'required' => false, "attr" => ["class" => "form-control"]])
            ->add('endYear', DatePartialType::class, ['label' => 'biographies.admin.EndYear', 'translation_domain' => 'validators', 'required' => false, "attr" => ["class" => "form-control"]])
			->add('internationalName', HiddenType::class, ["attr" => ["class" => "international-name"], "mapped" => false])
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_artist_artistbiographiestype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => null,
			'language' => 'fr',
			"req_params" => null,
			"query_parameters" => []
		]);
	}
}