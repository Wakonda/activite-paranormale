<?php
	namespace App\Service;

	use Symfony\Component\Form\FormError;
	use Symfony\Contracts\Translation\TranslatorInterface;

	class ConstraintControllerValidator
	{
		private $translator;

		public function __construct(TranslatorInterface $translator)
		{
			$this->translator = $translator;
		}

		public function fileConstraintValidator($form, $entityBindded, $entityOriginal, $filesArray = null)
		{
			if(!empty($filesArray))
			{
				foreach($filesArray as $file)
				{
					$fieldName = $file['field'];
					$getFieldName = "get".ucfirst($fieldName);
					$setFieldName = "set".ucfirst($fieldName);

					$currentFile = $entityOriginal->$getFieldName();
					$newFile = $form->getData()->$getFieldName();
					$existingFile = null;

					if(empty($newFile))
					{
						$fileToSave = $currentFile;
						
						if(isset($file['selectorFile']) and ($existingFile = $form->get($file['selectorFile'])->getData()) != null)
							$fileToSave = $existingFile;
					}
					else
						$fileToSave = $newFile;

					if($form->get($fieldName)->isRequired() and empty($currentFile) and empty($existingFile) and $entityBindded->$getFieldName() == "")
					{
						$form->get($fieldName)->addError(new FormError($this->translator->trans('admin.error.NotBlank', array(), 'validators')));
					}

					$entityBindded->$setFieldName($fileToSave);
				}
			}
		}

		public function fileManagementConstraintValidator($form, $entityBindded, $entityOriginal, $illustrations = [])
		{
			// 
			if(!empty($illustrations))
			{
				foreach($illustrations as $illustration)
				{
					$fieldName = $illustration["field"];
					$getFieldName = "get".ucfirst($fieldName);

					$currentFile = $entityOriginal->$getFieldName();

					$url = null;
					
					if(empty($currentFile) and !$form->get($fieldName)->get($illustration['selectorFile'])->getConfig()->getRequired())
						continue;
					
					if(isset($illustration['selectorFile']) and ($fileUrl = $form->get($fieldName)->get($illustration['selectorFile'])->getData()) != null)
						$url = $fileUrl;

					if($form->get($illustration["field"])->isRequired() and empty($currentFile->getTitleFile()) and empty($entityBindded->$getFieldName()->getTitleFile()) and empty($url))
					{
						$form->get($fieldName)->get("titleFile")->addError(new FormError($this->translator->trans('admin.error.NotBlank', array(), 'validators')));
					}

					if(!empty($currentFile) and empty($currentFile->getTitleFile()) and empty($entityBindded->$getFieldName()->getTitleFile()) and empty($url) and $currentFile->getCaption())
					{
						$form->get($fieldName)->get("titleFile")->addError(new FormError($this->translator->trans('admin.error.NotBlank', array(), 'validators')));
					}

					if(!empty($entityBindded->$getFieldName()))
					{
						if((!empty($entityBindded->$getFieldName()->getLicense()) or !empty($entityBindded->$getFieldName()->getAuthor()) or !empty($entityBindded->$getFieldName()->getUrlSource()) or !empty($entityBindded->$getFieldName()->getCaption())) and (empty($currentFile) or empty($currentFile->getTitleFile())) and empty($entityBindded->$getFieldName()->getTitleFile()) and empty($url))
						{
							$form->get($fieldName)->get("titleFile")->addError(new FormError($this->translator->trans('admin.error.NotBlank', array(), 'validators')));
						}
					}
				}
			}
		}
	}