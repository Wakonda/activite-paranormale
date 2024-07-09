<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;
use Ausi\SlugGenerator\SlugGenerator;

use App\Entity\Region;
use App\Entity\Language;

#[AsCommand(
   name: 'app:migrate-country-area'
)]
class MigrateCountryAreaCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start WakondaGuru update");
		
		$fr = "BE-VAN;Antwerpen
BE-WBR;Brabant wallon
BE-WHT;Hainaut
BE-WLG;Liège
BE-VLI;Limburg
BE-WLX;Luxembourg
BE-WNA;Namur
BE-VOV;Oost-Vlaanderen
BE-VBR;Vlaams-Brabant
BE-VWV;West-Vlaanderen
BE-BRU;Région de Bruxelles-Capitale
BE-VLG;Région flamande
BE-WAL;Région wallonne
CH-AG;Argovie
CH-AI;Appenzell Rhodes-Intérieures
CH-AR;Appenzell Rhodes-Extérieures
CH-BE;Berne
CH-BL;Bâle-Campagne
CH-BS;Bâle-Ville
CH-FR;Fribourg
CH-GE;Genève
CH-GL;Glaris
CH-GR;Grisons	Graubünden
CH-JU;Jura
CH-LU;Lucerne
CH-NE;Neuchâtel
CH-NW;Nidwald
CH-OW;Obwald
CH-SG;Saint-Gall
CH-SH;Schaffhouse
CH-SO;Soleure
CH-SZ;Schwytz
CH-TG;Thurgovie
CH-TI;Tessin
CH-UR;Uri
CH-VD;Vaud
CH-VS;Valais
CH-ZG;Zoug
CH-ZH;Zurich
FR-ARA;Auvergne-Rhône-Alpes
FR-BFC;Bourgogne-Franche-Comté
FR-BRE;Bretagne
FR-CVL;Centre-Val de Loire
FR-GES;Grand Est
FR-HDF;Hauts-de-France
FR-IDF;Île-de-France
FR-NOR;Normandie
FR-NAQ;Nouvelle-Aquitaine
FR-OCC;Occitanie
FR-PDL;Pays de la Loire
FR-PAC;Provence-Alpes-Côte d’Azur
FR-20R;Corse
FR-01;Ain;FR-ARA
FR-02;Aisne;FR-HDF
FR-03;Allier;FR-ARA
FR-04;Alpes-de-Haute-Provence;FR-PAC
FR-05;Hautes-Alpes;FR-PAC
FR-06;Alpes-Maritimes;FR-PAC
FR-07;Ardèche;FR-ARA
FR-08;Ardennes;FR-GES
FR-09;Ariège;FR-OCC
FR-10;Aube;FR-GES
FR-11;Aude;FR-OCC
FR-12;Aveyron;FR-OCC
FR-13;Bouches-du-Rhône;FR-PAC
FR-14;Calvados;FR-NOR
FR-15;Cantal;FR-ARA
FR-16;Charente;FR-NAQ
FR-17;Charente-Maritime;FR-NAQ
FR-18;Cher;FR-CVL
FR-19;Corrèze;FR-NAQ
FR-2A;Corse-du-Sud;FR-COR
FR-2B;Haute-Corse;FR-COR
FR-21;Côte-d’Or;FR-BFC
FR-22;Côtes-d’Armor;FR-BRE
FR-23;Creuse;FR-NAQ
FR-24;Dordogne;FR-NAQ
FR-25;Doubs;FR-BFC
FR-26;Drôme;FR-ARA
FR-27;Eure;FR-NOR
FR-28;Eure-et-Loir;FR-CVL
FR-29;Finistère;FR-BRE
FR-30;Gard;FR-OCC
FR-31;Haute-Garonne;FR-OCC
FR-32;Gers;FR-OCC
FR-33;Gironde;FR-NAQ
FR-34;Hérault;FR-OCC
FR-35;Ille-et-Vilaine;FR-BRE
FR-36;Indre;FR-CVL
FR-37;Indre-et-Loire;FR-CVL
FR-38;Isère;FR-ARA
FR-39;Jura;FR-BFC
FR-40;Landes;FR-NAQ
FR-41;Loir-et-Cher;FR-CVL
FR-42;Loire;FR-ARA
FR-43;Haute-Loire;FR-ARA
FR-44;Loire-Atlantique;FR-PDL
FR-45;Loiret;FR-CVL
FR-46;Lot;FR-OCC
FR-47;Lot-et-Garonne;FR-NAQ
FR-48;Lozère;FR-OCC
FR-49;Maine-et-Loire;FR-PDL
FR-50;Manche;FR-NOR
FR-51;Marne;FR-GES
FR-52;Haute-Marne;FR-GES
FR-53;Mayenne;FR-PDL
FR-54;Meurthe-et-Moselle;FR-GES
FR-55;Meuse;FR-GES
FR-56;Morbihan;FR-BRE
FR-57;Moselle;FR-GES
FR-58;Nièvre;FR-BFC
FR-59;Nord;FR-HDF
FR-60;Oise;FR-HDF
FR-61;Orne;FR-NOR
FR-62;Pas-de-Calais;FR-HDF
FR-63;Puy-de-Dôme;FR-ARA
FR-64;Pyrénées-Atlantiques;FR-NAQ
FR-65;Hautes-Pyrénées;FR-OCC
FR-66;Pyrénées-Orientales;FR-OCC
FR-67;Bas-Rhin;FR-GES
FR-68;Haut-Rhin;FR-GES
FR-69;Rhône;FR-ARA
FR-70;Haute-Saône;FR-BFC
FR-71;Saône-et-Loire;FR-BFC
FR-72;Sarthe;FR-PDL
FR-73;Savoie;FR-ARA
FR-74;Haute-Savoie;FR-ARA
FR-76;Seine-Maritime;FR-NOR
FR-77;Seine-et-Marne;FR-IDF
FR-78;Yvelines;FR-IDF
FR-79;Deux-Sèvres;FR-NAQ
FR-80;Somme;FR-HDF
FR-81;Tarn;FR-OCC
FR-82;Tarn-et-Garonne;FR-OCC
FR-83;Var;FR-PAC
FR-84;Vaucluse;FR-PAC
FR-85;Vendée;FR-PDL
FR-86;Vienne;FR-NAQ
FR-87;Haute-Vienne;FR-NAQ
FR-88;Vosges;FR-GES
FR-89;Yonne;FR-BFC
FR-90;Territoire de Belfort;FR-BFC
FR-91;Essonne;FR-IDF
FR-92;Hauts-de-Seine;FR-IDF
FR-93;Seine-Saint-Denis;FR-IDF
FR-94;Val-de-Marne;FR-IDF
FR-95;Val-d’Oise;FR-IDF
FR-69D;Rhône (département);FR-ARA
FR-69M;Métropole de Lyon;FR-ARA
FR-6AE;Alsace;FR-GES
FR-75C;Paris;FR-IDF
CA-AB;Alberta
CA-BC;Colombie-Britannique
CA-MB;Manitoba
CA-NB;Nouveau-Brunswick
CA-NL;Terre-Neuve-et-Labrador
CA-NS;Nouvelle-Écosse
CA-ON;Ontario
CA-PE;Île-du-Prince-Édouard
CA-QC;Québec
CA-SK;Saskatchewan
CA-NT;Territoires du Nord-Ouest
CA-NU;Nunavut
CA-YT;Territoire du Yukon
ES-AN;Andalousie
ES-AR;Aragón
ES-AS;Asturies, Principauté des
ES-CN;Îles Canaries
ES-CB;Cantabrie
ES-CM;Castille-La Manche
ES-CL;Castille-et-León
ES-CT;Catalogne
ES-EX;Estrémadure
ES-GA;Galice
ES-IB;Îles Baléares
ES-RI;La Rioja
ES-MD;Madrid, Communauté de
ES-MC;Murcie, Région de
ES-NC;Navarre, Communauté forale de
ES-PV;Pays basque
ES-VC;Valencienne, Communauté
ES-C;La Corogne;ES-GA
ES-VI;Alava;ES-PV
ES-AB;Albacete;ES-CM
ES-AV;Ávila;ES-CL
ES-A;Alicante;ES-VC
ES-O2;Asturies;ES-AS
ES-AL;Almería;ES-AN
ES-BA;Badajoz;ES-EX
ES-PM;Îles Baléares;ES-IB
ES-B;Barcelone;ES-CT
ES-BU;Burgos;ES-CL
ES-CR;Ciudad Real;ES-CM
ES-CA;Cadix;ES-AN
ES-CC;Cáceres;ES-EX
ES-S3;Cantabrie;ES-CB
ES-CS;Castellón;ES-VC
ES-CO;Cordoue;ES-AN
ES-CU;Cuenca;ES-CM
ES-GI;Gérone;ES-CT
ES-GR;Grenade;ES-AN
ES-GU;Guadalajara;ES-CM
ES-SS;Guipuscoa;ES-PV
ES-H;Huelva;ES-AN
ES-HU;Huesca;ES-AR
ES-J;Jaén;ES-AN
ES-LO;La Rioja;ES-RI
ES-GC;Las Palmas;ES-CN
ES-LE;León;ES-CL
ES-L;Lérida;ES-CT
ES-LU;Lugo;ES-GA
ES-M5;Madrid;ES-MD
ES-MA;Malaga;ES-AN
ES-MU;Murcie;ES-MC
ES-NA;Navarre;ES-NC
ES-OR;Orense;ES-GA
ES-P;Palencia;ES-CL
ES-PO;Pontevedra;ES-GA
ES-SA;Salamanque;ES-CL
ES-TF;Santa Cruz de Tenerife;ES-CN
ES-SG;Ségovie;ES-CL
ES-SE;Séville;ES-AN
ES-SO;Soria;ES-CL
ES-T;Tarragone;ES-CT
ES-TE;Teruel;ES-AR
ES-TO;Tolède;ES-CM
ES-V;Valence;ES-VC
ES-VA;Valladolid;ES-CL
ES-BI;Biscaye;ES-PV
ES-ZA;Zamora;ES-CL
ES-Z;Saragosse;ES-AR
ES-CE;Ceuta;
ES-ML;Melilla;
FR-971;Guadeloupe
FR-972;Martinique
FR-973;Guyane
FR-974;La Réunion
FR-976;Mayotte";

		$languageFr = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => "fr"]);
		$languageEn = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => "en"]);
		$languageEs = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => "es"]);

		foreach(explode("\r\n", $fr) as $line) {
			$lineArray = array_filter(explode(";", $line));

			$region = $this->em->getRepository(Region::class)->findOneBy(["internationalName" => $lineArray[0], "language" => $languageFr]);
			
			if(empty($region))
				$region = new Region();
		
			$region->setTitle($lineArray[1]);
			$region->setInternationalName($lineArray[0]);
			$region->setLanguage($languageFr);
			$region->setFamily(Region::SUBDIVISION_FAMILY);

			if(count($lineArray) == 2) {//dd(strtolower(substr($lineArray[0],0, 2)));
				$father = $this->em->getRepository(Region::class)->findOneBy(["internationalName" => strtolower(substr($lineArray[0],0, 2)), "language" => $languageFr]);
			} else {//dd($lineArray[2]);
				$father = $this->em->getRepository(Region::class)->findOneBy(["internationalName" => $lineArray[2], "language" => $languageFr]);
			}
			
			$region->setHigherLevel($father);

			$this->em->persist($region);
			$this->em->flush();
		}

		$en = "BE-VAN;Antwerpen
BE-WBR; Walloon Brabant
BE-WHT;Hainaut
BE-WLG;Liège
BE-VLI;Limburg
BE-WLX;Luxembourg
BE-WNA;Namur
BE-VOV;Oost-Vlaanderen
BE-VBR;Vlaams-Brabant
BE-VWV;West-Vlaanderen
BE-BRU;Brussels-Capital Region
BE-VLG;Flemish Region
BE-WAL;Walloon region
CH-AG;Aargau
CH-AI;Appenzell Innerrhoden
CH-AR;Appenzell Ausserrhoden
CH-BE;Bern
CH-BL;Basel-Landschaft
CH-BS;Basel-City
CH-FR;Fribourg
CH-GE;Geneva
CH-GL;Glarus
CH-GR;Grisons Graubünden
CH-JU;Jura
CH-LU;Lucerne
CH-NE;Neuchâtel
CH-NW;Nidwald
CH-OW;Obwald
CH-SG;St. Gallen
CH-SH;Schaffhausen
CH-SO; Solothurn
CH-SZ;Schwyz
CH-TG;Thurgau
CH-TI;Ticino
CH-UR;Uri
CH-VD;Vaud
CH-VS;Valais
CH-ZG;Zug
CH-ZH;Zurich
FR-ARA;Auvergne-Rhône-Alpes
FR-BFC;Bourgogne-Franche-Comté
FR-BRE;Brittany
FR-CVL;Centre-Val de Loire
FR-GES;Grand Est
FR-HDF;Hauts-de-France
FR-IDF;Île-de-France
FR-NOR;Normandy
FR-NAQ;Nouvelle-Aquitaine
FR-OCC;Occitanie
FR-PDL;Pays de la Loire
FR-PAC;Provence-Alpes-Côte d’Azur
FR-20R;Corsica
FR-01;Ain;FR-ARA
FR-02;Aisne;FR-HDF
FR-03;Allier;FR-ARA
FR-04;Alpes-de-Haute-Provence;FR-PAC
FR-05;Hautes-Alpes;FR-PAC
FR-06;Alpes-Maritimes;FR-PAC
FR-07;Ardèche;FR-ARA
FR-08;Ardennes;FR-GES
FR-09;Ariège;FR-OCC
FR-10;Aube;FR-GES
FR-11;Aude;FR-OCC
FR-12;Aveyron;FR-OCC
FR-13;Bouches-du-Rhône;FR-PAC
FR-14;Calvados;FR-NOR
FR-15;Cantal;FR-ARA
FR-16;Charente;FR-NAQ
FR-17;Charente-Maritime;FR-NAQ
FR-18;Cher;FR-CVL
FR-19;Corrèze;FR-NAQ
FR-2A;South Corsica;FR-COR
FR-2B;Haute-Corse;FR-COR
FR-21;Côte-d’Or;FR-BFC
FR-22;Côtes-d’Armor;FR-BRE
FR-23;Creuse;FR-NAQ
FR-24;Dordogne;FR-NAQ
FR-25;Doubs;FR-BFC
FR-26;Drôme;FR-ARA
FR-27;Eure;FR-NOR
FR-28;Eure-et-Loir;FR-CVL
FR-29;Finistère;FR-BRE
FR-30;Gard;FR-OCC
FR-31;Haute-Garonne;FR-OCC
FR-32;Gers;FR-OCC
FR-33;Gironde;FR-NAQ
FR-34;Hérault;FR-OCC
FR-35;Ille-et-Vilaine;FR-BRE
FR-36;Indre;FR-CVL
FR-37;Indre-et-Loire;FR-CVL
FR-38;Isère;FR-ARA
FR-39;Jura;FR-BFC
FR-40;Landes;FR-NAQ
FR-41;Loir-et-Cher;FR-CVL
FR-42;Loire;FR-ARA
FR-43;Haute-Loire;FR-ARA
FR-44;Loire-Atlantique;FR-PDL
FR-45;Loiret;FR-CVL
FR-46;Lot;FR-OCC
FR-47;Lot-et-Garonne;FR-NAQ
FR-48;Lozère;FR-OCC
FR-49;Maine-et-Loire;FR-PDL
FR-50;Manche;FR-NOR
FR-51;Marne;FR-GES
FR-52;Haute-Marne;FR-GES
FR-53;Mayenne;FR-PDL
FR-54;Meurthe-et-Moselle;FR-GES
FR-55;Meuse;FR-GES
FR-56;Morbihan;FR-BRE
FR-57;Moselle;FR-GES
FR-58;Nièvre;FR-BFC
FR-59;North;FR-HDF
FR-60;Oise;FR-HDF
FR-61;Orne;FR-NOR
FR-62;Pas-de-Calais;FR-HDF
FR-63;Puy-de-Dôme;FR-ARA
FR-64;Pyrénées-Atlantiques;FR-NAQ
FR-65;Hautes-Pyrénées;FR-OCC
FR-66;Pyrénées-Orientales;FR-OCC
FR-67;Bas-Rhin;FR-GES
FR-68;Haut-Rhin;FR-GES
FR-69;Rhône;FR-ARA
FR-70;Haute-Saône;FR-BFC
FR-71;Saône-et-Loire;FR-BFC
FR-72;Sarthe;FR-PDL
FR-73;Savoie;FR-ARA
FR-74;Haute-Savoie;FR-ARA
FR-76;Seine-Maritime;FR-NOR
FR-77;Seine-et-Marne;FR-IDF
FR-78;Yvelines;FR-IDF
FR-79;Deux-Sèvres;FR-NAQ
FR-80;Somme;FR-HDF
FR-81;Tarn;FR-OCC
FR-82;Tarn-et-Garonne;FR-OCC
FR-83;Var;FR-PAC
FR-84;Vaucluse;FR-PAC
FR-85;Vendée;FR-PDL
FR-86;Vienne;FR-NAQ
FR-87;Haute-Vienne;FR-NAQ
FR-88;Vosges;FR-GES
FR-89;Yonne;FR-BFC
FR-90;Territory of Belfort; FR-BFC
FR-91;Essonne;FR-IDF
FR-92;Hauts-de-Seine;FR-IDF
FR-93;Seine-Saint-Denis;FR-IDF
FR-94;Val-de-Marne;FR-IDF
FR-95;Val-d’Oise;FR-IDF
FR-69D;Rhône (department);FR-ARA
FR-69M;Métropole de Lyon;FR-ARA
FR-6AE;Alsace;FR-GES
FR-75C;Paris;FR-IDF
CA-AB;Alberta
CA-BC;British Columbia
CA-MB;Manitoba
CA-NB;New Brunswick
CA-NL;Newfoundland and Labrador
CA-NS;Nova Scotia
CA-ON;Ontario
CA-PE;Prince Edward Island
CA-QC;Quebec
CA-SK;Saskatchewan
CA-NT;Northwest Territories
CA-NU;Nunavut
CA-YT;Yukon Territory
ES-AN;Andalusia
ES-AR;Aragón
ES-AS;Asturias, Principality of
ES-CN;Canary Islands
ES-CB;Cantabria
ES-CM;Castile-La Mancha
ES-CL;Castile and León
ES-CT;Catalonia
ES-EX;Extremadura
ES-GA;Galicia
ES-IB;Balearic Islands
ES-RI;La Rioja
ES-MD;Madrid, Community of
ES-MC;Murcia, Region of
ES-NC;Navarra, Foral Community of
ES-PV;Basque Country
ES-VC;Valencian Community
ES-C;La Coruna;ES-GA
ES-VI;Alava;ES-PV
ES-AB;Albacete;ES-CM
ES-AV;Ávila;ES-CL
ES-A;Alicante;ES-VC
ES-O2;Asturias;ES-AS
ES-AL;Almería;ES-AN
ES-BA;Badajoz;ES-EX
ES-PM;Balearic Islands;ES-IB
ES-B;Barcelona;ES-CT
ES-BU;Burgos;ES-CL
ES-CR;Ciudad Real;ES-CM
ES-CA;Cádiz;ES-AN
ES-CC;Cáceres;ES-EX
ES-S3;Cantabria;ES-CB
ES-CS;Castellón;ES-VC
ES-CO;Cordoba;ES-AN
ES-CU;Cuenca;ES-CM
ES-GI;Girona;ES-CT
ES-GR;Granada;ES-AN
ES-GU;Guadalajara;ES-CM
ES-SS;Guipuscoa;ES-PV
ES-H;Huelva;ES-AN
ES-HU;Huesca;ES-AR
ES-J;Jaén;ES-AN
ES-LO;La Rioja;ES-RI
ES-GC;Las Palmas;ES-CN
ES-LE;León;ES-CL
ES-L;Lleida;ES-CT
ES-LU;Lugo;ES-GA
ES-M5;Madrid;ES-MD
ES-MA;Malaga;ES-AN
ES-MU;Murcia;ES-MC
ES-NA;Navarra;ES-NC
ES-OR;Ourense;ES-GA
ES-P;Palencia;ES-CL
ES-PO;Pontevedra;ES-GA
ES-SA;Salamanca;ES-CL
ES-TF;Santa Cruz de Tenerife;ES-CN
ES-SG;Segovia;ES-CL
ES-SE;Seville;ES-AN
ES-SO;Soria;ES-CL
ES-T;Tarragona;ES-CT
ES-TE;Teruel;ES-AR
ES-TO;Toledo;ES-CM
ES-V;Valencia;ES-VC
ES-VA;Valladolid;ES-CL
ES-BI;Biscay;ES-PV
ES-ZA;Zamora;ES-CL
ES-Z;Zaragoza;ES-AR
ES-CE;Ceuta;
ES-ML;Melilla;
FR-971;Guadeloupe
FR-972;Martinique
FR-973;French Guiana
FR-974;La Réunion
FR-976;Mayotte";

		foreach(explode("\r\n", $en) as $line) {
			$lineArray = array_filter(explode(";", $line));

			$region = $this->em->getRepository(Region::class)->findOneBy(["internationalName" => $lineArray[0], "language" => $languageEn]);
			
			if(empty($region))
				$region = new Region();
		
			$region->setTitle($lineArray[1]);
			$region->setInternationalName($lineArray[0]);
			$region->setLanguage($languageEn);
			$region->setFamily(Region::SUBDIVISION_FAMILY);

			if(count($lineArray) == 2) {//dd(strtolower(substr($lineArray[0],0, 2)));
				$father = $this->em->getRepository(Region::class)->findOneBy(["internationalName" => strtolower(substr($lineArray[0],0, 2)), "language" => $languageEn]);
			} else {//dd($lineArray[2]);
				$father = $this->em->getRepository(Region::class)->findOneBy(["internationalName" => $lineArray[2], "language" => $languageEn]);
			}
			
			$region->setHigherLevel($father);

			$this->em->persist($region);
			$this->em->flush();
		}


		$es = "BE-VAN;Amberes
BE-WBR;Brabante Valón
BE-WHT;Henao
BE-WLG;Lieja
BE-VLI;Limburgo
BE-WLX;Luxemburgo
BE-WNA;Namur
BE-VOV;Oost-Vlaanderen
BE-VBR;Vlaams-Brabante
BE-VWV;Vlaanderen Occidental
BE-BRU;Región de Bruselas-Capital
BE-VLG; Región Flamenca
BE-WAL;Región valona
CH-AG;Argovia
CH-AI;Appenzell Rodas Interiores
CH-AR;Appenzell Rodas Exteriores
CH-BE;Berna
CH-BL;Basilea-Campiña
CH-BS;Basilea-Ciudad
CH-FR;Friburgo
CH-GE;Ginebra
CH-GL;Glarus
CH-GR;Grisones Grisones
CH-JU;Jura
CH-LU;Lucerna
CH-NE;Neuchâtel
CH-NO;Nidwald
CH-OW;Obwald
CH-SG;San Galo
CH-SH;Schaffhausen
CH-SO; Soleura
CH-SZ;Schwyz
CH-TG; Turgovia
CH-TI;Ticino
CH-UR;Uri
CH-VD;Vaud
CH-VS;Valais
CH-ZG;Zug
CH-ZH;Zúrich
FR-ARA;Auvernia-Ródano-Alpes
FR-BFC; orgoña-Franco Condado
FR-BRE;Bretaña
FR-CVL;Centro-Val de Loira
FR-GES;Gran Este
FR-HDF;Altos de Francia
FR-IDF;Isla de Francia
FR-NOR;Normandía
FR-NAQ;Nueva Aquitania
FR-OCC;Occitania
FR-PDL;Países del Loira
FR-PAC;Provenza-Alpes-Costa Azul
FR-20R;Córcega
FR-01;Ain;FR-ARA
FR-02;Aisne;FR-HDF
FR-03;Allier;FR-ARA
FR-04;Alpes-de-Alta-Provenza;FR-PAC
FR-05;Altos Alpes;FR-PAC
FR-06;Alpes Marítimos;FR-PAC
FR-07;Ardèche;FR-ARA
FR-08;Ardenas;FR-GES
FR-09;Arieja;FR-OCC
FR-10;Aube;FR-GES
FR-11;Aude;FR-OCC
FR-12;Aveyron;FR-OCC
FR-13;Bocas del Ródano;FR-PAC
FR-14;Calvados;FR-NOR
FR-15;Cantal;FR-ARA
FR-16;Charente;FR-NAQ
FR-17;Charente Marítimo;FR-NAQ
FR-18;Cher;FR-CVL
FR-19;Corrèze;FR-NAQ
FR-2A;Córcega del Sur;FR-COR
FR-2B;Alta Córcega;FR-COR
FR-21;Côte-d'Or;FR-BFC
FR-22;Costas de Armor;FR-BRE
FR-23;Creuse;FR-NAQ
FR-24;Dordoña;FR-NAQ
FR-25;Dobles;FR-BFC
FR-26;Drome;FR-ARA
FR-27;Eure;FR-NOR
FR-28;Eure y Loir;FR-CVL
FR-29;Finisterre;FR-BRE
FR-30;Gard;FR-OCC
FR-31;Alto Garona;FR-OCC
FR-32;Gers;FR-OCC
FR-33;Gironda;FR-NAQ
FR-34;Hérault;FR-OCC
FR-35;Ille y Vilaine;FR-BRE
FR-36;Indre;FR-CVL
FR-37;Indre y Loira;FR-CVL
FR-38;Isère;FR-ARA
FR-39;Jura;FR-BFC
FR-40;Landas;FR-NAQ
FR-41;Loir y Cher;FR-CVL
FR-42;Loira;FR-ARA
FR-43;Alto Loira;FR-ARA
FR-44;Loira Atlántico;FR-PDL
FR-45;Loiret;FR-CVL
FR-46;Lote;FR-OCC
FR-47;Lot y Garona;FR-NAQ
FR-48;Lozere;FR-OCC
FR-49;Maine y Loira;FR-PDL
FR-50;Canal;FR-NOR
FR-51;Marne;FR-GES
FR-52;Alto Marne;FR-GES
FR-53;Mayenne;FR-PDL
FR-54;Meurthe y Mosela;FR-GES
FR-55;Mosa;FR-GES
FR-56;Morbihan;FR-BRE
FR-57;Mosela;FR-GES
FR-58;Nièvre;FR-BFC
FR-59;Norte;FR-HDF
FR-60;Oise;FR-HDF
FR-61;Orne;FR-NOR
FR-62;Paso de Calais;FR-HDF
FR-63;Puy-de-Dôme;FR-ARA
FR-64;Pirineos Atlánticos;FR-NAQ
FR-65;Altos Pirineos;FR-OCC
FR-66;Pirineos Orientales;FR-OCC
FR-67;Bajo Rin;FR-GES
FR-68;Alto Rin;FR-GES
FR-69;Ródano;FR-ARA
FR-70;Alto Saona;FR-BFC
FR-71;Saona y Loira;FR-BFC
FR-72;Sarthe;FR-PDL
FR-73;Saboya;FR-ARA
FR-74;Alta Saboya;FR-ARA
FR-76;Sena Marítimo;FR-NOR
FR-77;Sena y Marne;FR-IDF
FR-78;Yvelines;FR-FDI
FR-79;Deux-Sèvres;FR-NAQ
FR-80;Somme;FR-HDF
FR-81;Tarn;FR-OCC
FR-82;Tarn y Garona;FR-OCC
FR-83;Var;FR-PAC
FR-84;Vaucluse;FR-PAC
FR-85;Vendée;FR-PDL
FR-86;Viena;FR-NAQ
FR-87;Alto Viena;FR-NAQ
FR-88;Vosgos;FR-GES
FR-89;Yonne;FR-BFC
FR-90;Territorio de Belfort;
FR-91;Essonne;FR-FDI
FR-92;Altos del Sena;FR-IDF
FR-93;Sena-Saint-Denis;FR-IDF
FR-94;Valle del Marne;FR-IDF
FR-95;Valle del Oise;FR-IDF
FR-69D;Ródano (departamento);FR-ARA
FR-69M;Metropole de Lyon;FR-ARA
FR-6AE;Alsacia;FR-GES
FR-75C;París;FR-IDF
CA-AB;Alberta
CA-BC;Columbia Británica
CA-MB;Manitoba
CA-NB;Nuevo Brunswick
CA-NL;Terranova y Labrador
CA-NS;Nueva Escocia
CA-ON;Ontario
CA-PE;Isla del Príncipe Eduardo
CA-QC;Quebec
CA-SK;Saskatchewan
CA-NT;Territorios del Noroeste
CA-NU;Nunavut
CA-YT;Territorio del Yukón
ES-AN;Andalucía
ES-AR;Aragón
ES-AS;Asturias, Principado de
ES-CN;Islas Canarias
ES-CB;Cantabria
ES-CM;Castilla-La Mancha
ES-CL;Castilla y León
ES-CT;Cataluña
ES-EX;Extremadura
ES-GA;Galicia
ES-IB;Islas Baleares
ES-RI;La Rioja
ES-MD;Madrid, Comunidad de
ES-MC;Murcia, Región de
ES-NC;Navarra, Comunidad Foral de
ES-PV;País Vasco
ES-VC;Comunidad Valenciana
ES-C;La Coruña;ES-GA
ES-VI;Álava;ES-PV
ES-AB;Albacete;ES-CM
ES-AV;Ávila;ES-CL
ES-A;Alicante;ES-VC
ES-O2;Asturias;ES-AS
ES-AL;Almería;ES-AN
ES-BA;Badajoz;ES-EX
ES-PM;Islas Baleares;ES-IB
ES-B;Barcelona;ES-CT
ES-BU;Burgos;ES-CL
ES-CR;Ciudad Real;ES-CM
ES-CA;Cádiz;ES-AN
ES-CC;Cáceres;ES-EX
ES-S3;Cantabria;ES-CB
ES-CS;Castellón;ES-VC
ES-CO;Córdoba;ES-AN
ES-CU;Cuenca;ES-CM
ES-GI;Girona;ES-CT
ES-GR;Granada;ES-AN
ES-GU;Guadalajara;ES-CM
ES-SS;Guipuscoa;ES-PV
ES-H;Huelva;ES-AN
ES-HU;Huesca;ES-AR
ES-J;Jaén;ES-AN
ES-LO;La Rioja;ES-RI
ES-GC;Las Palmas;ES-CN
ES-LE;León;ES-CL
ES-L;Lérida;ES-CT
ES-LU;Lugo;ES-GA
ES-M5;Madrid;ES-MD
ES-MA;Málaga;ES-AN
ES-MU;Murcia;ES-MC
ES-NA;Navarra;ES-NC
ES-OR;Ourense;ES-GA
ES-P;Palencia;ES-CL
ES-PO;Pontevedra;ES-GA
ES-SA;Salamanca;ES-CL
ES-TF;Santa Cruz de Tenerife;ES-CN
ES-SG;Segovia;ES-CL
ES-SE;Sevilla;ES-AN
ES-SO;Soria;ES-CL
ES-T;Tarragona;ES-CT
ES-TE;Teruel;ES-AR
ES-TO;Toledo;ES-CM
ES-V;Valencia;ES-VC
ES-VA;Valladolid;ES-CL
ES-BI;Vizcaya;ES-PV
ES-ZA;Zamora;ES-CL
ES-Z;Zaragoza;ES-AR
ES-CE;Ceuta;
ES-ML; Melilla;
FR-971;Guadalupe
FR-972;Martinica
FR-973;Guayana Francesa
FR-974;Reunión
FR-976;Mayotte";

		foreach(explode("\r\n", $es) as $line) {
			$lineArray = array_filter(explode(";", $line));

			$region = $this->em->getRepository(Region::class)->findOneBy(["internationalName" => $lineArray[0], "language" => $languageEs]);
			
			if(empty($region))
				$region = new Region();
		
			$region->setTitle($lineArray[1]);
			$region->setInternationalName($lineArray[0]);
			$region->setLanguage($languageEs);
			$region->setFamily(Region::SUBDIVISION_FAMILY);

			if(count($lineArray) == 2) {//dd(strtolower(substr($lineArray[0],0, 2)));
				$father = $this->em->getRepository(Region::class)->findOneBy(["internationalName" => strtolower(substr($lineArray[0],0, 2)), "language" => $languageEs]);
			} else {//dd($lineArray[2]);
				$father = $this->em->getRepository(Region::class)->findOneBy(["internationalName" => $lineArray[2], "language" => $languageEs]);
			}
			
			$region->setHigherLevel($father);

			$this->em->persist($region);
			$this->em->flush();
		}

        return 0;
    }
}
