<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\EventMessage;
use App\Entity\Region;
use App\Entity\Language;
use App\Entity\State;
use App\Entity\Licence;
use App\Entity\Theme;
use Ausi\SlugGenerator\SlugGenerator;

#[AsCommand(
   name: 'app:national-day'
)]
class NationalDayCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
		$output->writeln("Start National day");
		
		$csv = "Afghanistan;19/08;Fête de l'Indépendance
Afrique du Sud;27/04;Jour de la Liberté
Albanie;28/11;Jour de l'Indépendance (Jour du Drapeau)
Algérie;01/11;Déclenchement de la Révolution
Allemagne;03/10;Jour de l'Unité allemande
Andorre;08/09;Fête de Notre-Dame de Meritxell
Angola;11/11;Fête de l'Indépendance
Antigua-et-Barbuda;01/11;Fête de l'Indépendance
Arabie saoudite;23/09;Fête nationale saoudienne
Argentine;25/05;Révolution de Mai
Arménie;21/09;Fête de l'Indépendance
Australie;26/01;Australia Day
Autriche;26/10;Fête nationale (Loi sur la neutralité)
Azerbaïdjan;28/05;Jour de la République
Bahamas;10/07;Fête de l'Indépendance
Bahreïn;16/12;Fête nationale (Accession au trône)
Bangladesh;26/03;Fête de l'Indépendance
Barbade;30/11;Fête de l'Indépendance
Belgique;21/07;Prestation de serment du roi Léopold Ier
Belize;21/09;Fête de l'Indépendance
Bénin;01/08;Fête de l'Indépendance
Bhoutan;17/12;Fête nationale (Couronnement du premier Roi)
Biélorussie;03/07;Fête de l'Indépendance
Birmanie;04/01;Fête de l'Indépendance
Bolivie;06/08;Fête de l'Indépendance
Bosnie-Herzégovine;01/03;Fête de l'Indépendance
Botswana;30/09;Fête de l'Indépendance
Brésil;07/09;Fête de l'Indépendance
Brunei;23/02;Fête nationale
Bulgarie;03/03;Fête de la Libération du joug ottoman
Burkina Faso;11/12;Proclamation de la République
Burundi;01/07;Fête de l'Indépendance
Cambodge;09/11;Fête de l'Indépendance
Cameroun;20/05;Fête nationale (État unitaire)
Canada;01/07;Fête du Canada
Cap-Vert;05/07;Fête de l'Indépendance
Centrafrique;01/12;Proclamation de la République
Chili;18/09;Première junte nationale de gouvernement
Chine;01/10;Proclamation de la République populaire de Chine
Chypre;01/10;Fête de l'Indépendance
Colombie;20/07;Fête de l'Indépendance
Comores;06/07;Fête de l'Indépendance
Congo-Brazzaville;15/08;Fête de l'Indépendance
Congo-Kinshasa (RDC);30/06;Fête de l'Indépendance
Corée du Nord;09/09;Fondation de la République
Corée du Sud;15/08;Gwangbokjeol (Jour de la Libération)
Costa Rica;15/09;Fête de l'Indépendance
Côte d'Ivoire;07/08;Fête de l'Indépendance
Croatie;30/05;Fête nationale
Cuba;01/01;Triomphe de la Révolution
Danemark;05/06;Jour de la Constitution
Djibouti;27/06;Fête de l'Indépendance
Dominique;03/11;Fête de l'Indépendance
Égypte;23/07;Révolution de 1952
Émirats arabes unis;02/12;Fête nationale (Union des Émirats)
Équateur;10/08;Premier cri de l'Indépendance
Érythrée;24/05;Fête de l'Indépendance
Espagne;12/10;Fiesta Nacional de España
Estonie;24/02;Fête de l'Indépendance
Eswatini;06/09;Fête de l'Indépendance
États-Unis;04/07;Independence Day
Éthiopie;28/05;Chute du régime Derg
Fidji;10/10;Fête des Fidji (Indépendance)
Finlande;06/12;Fête de l'Indépendance
France;14/07;Prise de la Bastille / Fête de la Fédération
Gabon;17/08;Fête de l'Indépendance
Gambie;18/02;Fête de l'Indépendance
Géorgie;26/05;Jour de la République / Indépendance
Ghana;06/03;Fête de l'Indépendance
Grèce;25/03;Révolution grecque
Grenade;07/02;Fête de l'Indépendance
Guatemala;15/09;Fête de l'Indépendance
Guinée;02/10;Fête de l'Indépendance
Guinée-Bissau;24/09;Proclamation de l'Indépendance
Guinée équatoriale;12/10;Fête de l'Indépendance
Guyana;23/02;Jour de la République (Mashramani)
Haïti;01/01;Fête de l'Indépendance
Honduras;15/09;Fête de l'Indépendance
Hongrie;20/08;Fête de la Saint-Étienne
Inde;26/01;Republic Day
Indonésie;17/08;Proclamation de l'Indépendance
Irak;03/10;Fête de l'Indépendance
Iran;11/02;Victoire de la Révolution islamique
Irlande;17/03;Fête de la Saint-Patrick
Islande;17/06;Fête nationale (Fondation de la République)
Israël;14/05;Déclaration d'indépendance (date grégorienne variable)
Italie;02/06;Festa della Repubblica
Jamaïque;06/08;Fête de l'Indépendance
Japon;23/02;Anniversaire de l'Empereur
Jordanie;25/05;Fête de l'Indépendance
Kazakhstan;25/10;Jour de la République
Kenya;12/12;Jamhuri Day (Indépendance)
Kirghizistan;31/08;Fête de l'Indépendance
Kiribati;12/07;Fête de l'Indépendance
Koweït;25/02;Fête nationale
Laos;02/12;Fête nationale (Proclamation de la République)
Lesotho;04/10;Fête de l'Indépendance
Lettonie;18/11;Proclamation de la République de Lettonie
Liban;22/11;Fête de l'Indépendance
Liberia;26/07;Fête de l'Indépendance
Libye;24/12;Fête de l'Indépendance
Liechtenstein;15/08;Fête nationale
Lituanie;16/02;Fête du rétablissement de l'État lituanien
Luxembourg;23/06;Célébration officielle de l'anniversaire du Grand-Duc
Macédoine du Nord;08/09;Fête de l'Indépendance
Madagascar;26/06;Fête de l'Indépendance
Malaisie;31/08;Hari Merdeka (Indépendance)
Malawi;06/07;Fête de l'Indépendance / République
Maldives;26/07;Fête de l'Indépendance
Mali;22/09;Fête de l'Indépendance
Malte;21/09;Fête de l'Indépendance
Maroc;30/07;Fête du Trône
Maurice;12/03;Fête nationale (Indépendance / République)
Mauritanie;28/11;Fête de l'Indépendance
Mexique;16/09;Grito de Dolores (Indépendance)
Micronésie;03/11;Fête de l'Indépendance
Moldavie;27/08;Fête de l'Indépendance
Monaco;19/11;Fête du Prince
Mongolie;11/07;Fête du Naadam
Monténégro;13/07;Fête de l'Indépendance (Congrès de Berlin)
Mozambique;25/06;Fête de l'Indépendance
Namibie;21/03;Fête de l'Indépendance
Nauru;31/01;Fête de l'Indépendance
Népal;20/09;Jour de la Constitution
Nicaragua;15/09;Fête de l'Indépendance
Niger;18/12;Proclamation de la République
Nigeria;01/10;Fête de l'Indépendance
Norvège;17/05;Jour de la Constitution
Nouvelle-Zélande;06/02;Waitangi Day
Oman;18/11;Fête nationale (Anniversaire du Sultan)
Ouganda;09/10;Fête de l'Indépendance
Ouzbékistan;01/09;Fête de l'Indépendance
Pakistan;14/08;Fête de l'Indépendance
Palaos;01/10;Fête de l'Indépendance
Palestine;15/11;Déclaration d'indépendance de 1988
Panama;03/11;Séparation d'avec la Colombie
Papouasie-Nouvelle-Guinée;16/09;Fête de l'Indépendance
Paraguay;14/05;Fête de l'Indépendance
Pays-Bas;27/04;Koningsdag (Jour du Roi)
Pérou;28/07;Fête de l'Indépendance
Philippines;12/06;Fête de l'Indépendance
Pologne;11/11;Fête nationale de l'indépendance
Portugal;10/06;Jour du Portugal (Mort de Camões)
Qatar;18/12;Fête nationale
République centrafricaine;01/12;Proclamation de la République
République dominicaine;27/02;Fête de l'Indépendance
République tchèque;28/10;Fondation de la Tchécoslovaquie
Roumanie;01/12;Jour de l'Unification
Royaume-Uni;13/06;Trooping the Colour (date officielle variable)
Russie;12/06;Jour de la Russie
Rwanda;01/07;Fête de l'Indépendance
Saint-Christophe-et-Niévès;19/09;Fête de l'Indépendance
Sainte-Lucie;22/02;Fête de l'Indépendance
Saint-Marin;03/09;Fête de Saint-Marin et de la République
Saint-Vincent-et-les-Grenadines;27/10;Fête de l'Indépendance
Salomon;07/07;Fête de l'Indépendance
Samoa;01/06;Fête de l'Indépendance
Sao Tomé-et-Principe;12/07;Fête de l'Indépendance
Sénégal;04/04;Fête de l'Indépendance
Serbie;15/02;Fête nationale (Début de la révolution serbe)
Seychelles;29/06;Fête de l'Indépendance
Sierra Leone;27/04;Fête de l'Indépendance
Singapour;09/08;Fête nationale
Slovaquie;01/09;Jour de la Constitution
Slovénie;25/06;Fête de l'État (Indépendance)
Somalie;01/07;Fête de l'Indépendance
Soudan;01/01;Fête de l'Indépendance
Soudan du Sud;09/07;Fête de l'Indépendance
Sri Lanka;04/02;Fête de l'Indépendance
Suède;06/06;Fête nationale suédoise
Suisse;01/08;Fête nationale suisse
Suriname;25/11;Fête de l'Indépendance
Syrie;17/04;Jour de l'Évacuation (Fin du mandat français)
Tadjikistan;09/09;Fête de l'Indépendance
Taïwan;10/10;Fête du Double-Dix
Tanzanie;26/04;Union Day (Zanzibar et Tanganyika)
Tchad;11/08;Fête de l'Indépendance
Thaïlande;05/12;Anniversaire du roi Bhumibol / Fête nationale
Timor oriental;28/11;Proclamation de l'Indépendance
Togo;27/04;Fête de l'Indépendance
Tonga;04/11;Jour de la Constitution
Trinité-et-Tobago;31/08;Fête de l'Indépendance
Tunisie;20/03;Fête de l'Indépendance
Turkménistan;27/09;Fête de l'Indépendance
Turquie;29/10;Fête de la République
Tuvalu;01/10;Fête de l'Indépendance
Ukraine;24/08;Fête de l'Indépendance
Uruguay;25/08;Déclaration de l'Indépendance
Vanuatu;30/07;Fête de l'Indépendance
Vatican;13/03;Élection du Pape François
Venezuela;05/07;Signature de la déclaration d'Indépendance
Viêt Nam;02/09;Fête nationale (Proclamation de la République)
Yémen;22/05;Jour de l'Unité nationale
Zambie;24/10;Fête de l'Indépendance
Zimbabwe;18/04;Fête de l'Indépendance";

		$lines = explode("\n", $csv);

		foreach ($lines as $line) {
			if (trim($line) === '') {
				continue;
			}
			
			$data = str_getcsv($line, ";");
			$language = $this->em->getRepository(language::class)->find(1);
			$region = $this->em->getRepository(Region::class)->findOneBy(['family' => 'country', 'title' => $data[0], 'language' => $language]);
			$theme = $this->em->getRepository(Theme::class)->findOneBy(['title' => 'Histoire', 'language' => $language]);
			

				
				if(empty($region))
					continue;
			
			$eventMessage = $this->em->getRepository(EventMessage::class)->findOneBy(["internationalName" => 'nation-day-'.$region->getSlug(), 'language' => $language]);

			if(empty($eventMessage)) {
				
				$eventMessage = new EventMessage();
			}
				$eventMessage->setTitle($data[2]);
				$eventMessage->setLanguage($language);
				$eventMessage->setType('celebration');
				$eventMessage->setInternationalName('nation-day-'.$region->getSlug());
				$eventMessage->setTheme($theme);
				$eventMessage->setCountry($region);
				
				list($day, $month) = explode("/", $data[1]);
				// dd($day, $month);
				$eventMessage->setDayFrom($day);
				$eventMessage->setMonthFrom($month);
				
				$state = $this->em->getRepository(State::class)->findOneBy(["internationalName" => "Validate", "language" => $language]);
				$eventMessage->setState($state);
				
				$licence = $this->em->getRepository(Licence::class)->findOneBy(["internationalName" => "CC-BY", "language" => $language]);
				$eventMessage->setLicence($licence);
				
				$this->em->persist($eventMessage);
			
			
		}
			$this->em->flush();
		
        return 0;
    }
}