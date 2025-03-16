<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;
use Ausi\SlugGenerator\SlugGenerator;

use App\Entity\UsefulLink;
use App\Entity\Language;
use App\Entity\FileManagement;
use App\Entity\UsefullinkTags;

#[AsCommand(
   name: 'app:migrate-update-wakondaguru'
)]
class MigrateUpdateWakondaGuruCommand extends Command
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
		$output->writeln("Start WakondaGuru update");
		
		$conn = $this->em->getConnection();
		
		$conn->exec("update usefullink set id = CONCAT('888', id)");
		
		$conn->exec("UPDATE usefullink SET id = 1 WHERE slug = 'calculer-le-temps-d-execution-d-un-script-php';");
		$conn->exec("UPDATE usefullink SET id = 2 WHERE slug = 'fonction-in_array-en-jquery';");
		$conn->exec("UPDATE usefullink SET id = 3 WHERE slug = 'tri-des-dates-heures-avec-datatables-jquery';");
		$conn->exec("UPDATE usefullink SET id = 4 WHERE slug = 'supprimer-des-tags-vides-a-l-aide-de-la-librairie-htmpurifier';");
		$conn->exec("UPDATE usefullink SET id = 5 WHERE slug = 'imprimer-avec-javascript-en-format-paysage';");
		$conn->exec("UPDATE usefullink SET id = 6 WHERE slug = 'ajouter-un-favicon-avec-ruby-on-rails';");
		$conn->exec("UPDATE usefullink SET id = 7 WHERE slug = 'php-remplacer-les-images-d-un-code-html-en-base-64';");
		$conn->exec("UPDATE usefullink SET id = 8 WHERE slug = 'soumettre-un-formulaire-et-ouvrir-le-resultat-dans-un-nouvel-onglet';");
		$conn->exec("UPDATE usefullink SET id = 9 WHERE slug = 'un-exe-qui-ne-se-lance-pas-sous-vista';");
		$conn->exec("UPDATE usefullink SET id = 10 WHERE slug = 'les-parametres-facultatifs-dans-une-methode-en-ruby';");
		$conn->exec("UPDATE usefullink SET id = 11 WHERE slug = 'les-commentaires-en-ruby';");
		$conn->exec("UPDATE usefullink SET id = 12 WHERE slug = 'sf2-affichage-du-nom-des-pays-en-entier';");
		$conn->exec("UPDATE usefullink SET id = 13 WHERE slug = 'empecher-le-redimensionnement-d-un-textarea';");
		$conn->exec("UPDATE usefullink SET id = 14 WHERE slug = 'supprimer-le-dernier-caractere-d-une-chaine-de-caractere-php';");
		$conn->exec("UPDATE usefullink SET id = 15 WHERE slug = 'evaluer-une-chaine-de-caracteres';");
		$conn->exec("UPDATE usefullink SET id = 16 WHERE slug = 'sortir-d-une-boucle-en-php';");
		$conn->exec("UPDATE usefullink SET id = 17 WHERE slug = 'lister-l-ensemble-des-fichiers-d-un-repertoire-php';");
		$conn->exec("UPDATE usefullink SET id = 18 WHERE slug = 'nombre-de-chiffres-apres-la-virgule-en-javascript';");
		$conn->exec("UPDATE usefullink SET id = 19 WHERE slug = 'compter-le-nombre-de-lignes-dans-un-fichier-php';");
		$conn->exec("UPDATE usefullink SET id = 20 WHERE slug = 'supprimer-tous-les-accents-en-php';");
		$conn->exec("UPDATE usefullink SET id = 21 WHERE slug = 'fichier-locked-dans-tortoise-svn';");
		$conn->exec("UPDATE usefullink SET id = 22 WHERE slug = 'coloration-syntaxique-de-twig-sous-notepad';");
		$conn->exec("UPDATE usefullink SET id = 23 WHERE slug = 'un-var_dump-en-javascript';");
		$conn->exec("UPDATE usefullink SET id = 24 WHERE slug = 'fonction-pour-supprimer-les-caracteres-speciaux-en-php';");
		$conn->exec("UPDATE usefullink SET id = 25 WHERE slug = 'detecter-le-protocole-utilise-en-php';");
		$conn->exec("UPDATE usefullink SET id = 26 WHERE slug = 'acceder-aux-variables-de-php-ini';");
		$conn->exec("UPDATE usefullink SET id = 27 WHERE slug = 'js-trier-un-tableau-d-objets';");
		$conn->exec("UPDATE usefullink SET id = 28 WHERE slug = 'js-trier-un-tableau-encodage';");
		$conn->exec("UPDATE usefullink SET id = 29 WHERE slug = 'recuperer-une-base-de-donnees-mysql-avec-les-fichiers-ibd-et-frm';");
		$conn->exec("UPDATE usefullink SET id = 30 WHERE slug = 'wamp-mysql-refuse-de-demarrer';");
		$conn->exec("UPDATE usefullink SET id = 31 WHERE slug = 'utiliser-zend-framework-dans-symfony2';");
		$conn->exec("UPDATE usefullink SET id = 32 WHERE slug = 'transformer-une-chaine-de-caracteres-en-tableau-en-php';");
		$conn->exec("UPDATE usefullink SET id = 33 WHERE slug = 'trouver-le-type-d-une-variable-php';");
		$conn->exec("UPDATE usefullink SET id = 34 WHERE slug = 'valider-une-adresse-email-en-javascript';");
		$conn->exec("UPDATE usefullink SET id = 35 WHERE slug = 'selectionner-le-texte-d-un-champ-en-javascript';");
		$conn->exec("UPDATE usefullink SET id = 36 WHERE slug = 'compter-le-nombre-de-lignes-dans-un-fichier-php-c9047714-0394-4a96-8802-839837926ea3';");
		$conn->exec("UPDATE usefullink SET id = 37 WHERE slug = 'desactiver-un-bouton-radio-ou-autres-en-jquery';");
		$conn->exec("UPDATE usefullink SET id = 38 WHERE slug = 'supprimer-les-balises-br-en-fin-de-chaine-de-caracteres';");
		$conn->exec("UPDATE usefullink SET id = 39 WHERE slug = 'l-attribut-autocomplete-html5';");
		$conn->exec("UPDATE usefullink SET id = 40 WHERE slug = 'detection-du-protocole-avec-php';");
		$conn->exec("UPDATE usefullink SET id = 41 WHERE slug = 'correspondance-variables-http-php-symfony-2';");
		$conn->exec("UPDATE usefullink SET id = 42 WHERE slug = 'tester-l-existence-d-une-fonction-en-javascript';");
		$conn->exec("UPDATE usefullink SET id = 43 WHERE slug = 'enlever-la-protection-csfr-d-un-formulaire-symfony2';");
		$conn->exec("UPDATE usefullink SET id = 44 WHERE slug = 'contrainte-sur-les-fichiers-dans-un-callbackvalidator-symfony2';");
		$conn->exec("UPDATE usefullink SET id = 45 WHERE slug = 'upload-de-fichier-avec-ajax-jquery-et-xmlhttprequest-2';");
		$conn->exec("UPDATE usefullink SET id = 46 WHERE slug = 'migrer-des-modifications-avec-doctrine2-et-symfony2';");
		$conn->exec("UPDATE usefullink SET id = 47 WHERE slug = 'convertir-des-fichiers-word-ou-excel-en-pdf-avec-php';");
		$conn->exec("UPDATE usefullink SET id = 48 WHERE slug = 'retirer-le-app-php-de-l-url-sous-symfony2';");
		$conn->exec("UPDATE usefullink SET id = 49 WHERE slug = 'alternance-de-couleurs-en-css';");
		$conn->exec("UPDATE usefullink SET id = 50 WHERE slug = 'selectionner-toutes-les-checkbox-avec-jquery';");
		$conn->exec("UPDATE usefullink SET id = 51 WHERE slug = 'inclure-sur-son-site-un-fichier-js-heberge-sur-github';");
		$conn->exec("UPDATE usefullink SET id = 52 WHERE slug = 'reparer-une-chaine-html-en-php';");
		$conn->exec("UPDATE usefullink SET id = 53 WHERE slug = 'faire-fonctionner-curl-avec-php-7-et-wamp';");
		$conn->exec("UPDATE usefullink SET id = 54 WHERE slug = 'c-permuter-deux-variables-avec-l-operateur';");
		$conn->exec("UPDATE usefullink SET id = 55 WHERE slug = 'jquery-appeler-this-a-l-interieur-de-la-fonction-ajax';");
		$conn->exec("UPDATE usefullink SET id = 56 WHERE slug = 'silex-recuperer-l-objet-request-avec-silex-2';");
		$conn->exec("UPDATE usefullink SET id = 57 WHERE slug = 'enregistrer-des-emoji-dans-une-base-de-donnees-mysql';");
		$conn->exec("UPDATE usefullink SET id = 58 WHERE slug = 'php-supprimer-des-balises-html';");
		$conn->exec("UPDATE usefullink SET id = 59 WHERE slug = 'php-supprimer-les-commentaires-html';");
		$conn->exec("UPDATE usefullink SET id = 60 WHERE slug = 'c-tester-si-une-chaine-est-contenue-dans-une-autre';");
		$conn->exec("UPDATE usefullink SET id = 61 WHERE slug = 'mysql-faire-la-somme-d-une-somme';");
		$conn->exec("UPDATE usefullink SET id = 62 WHERE slug = 'font-awesome-avec-les-lettres-de-l-alphabet';");
		$conn->exec("UPDATE usefullink SET id = 64 WHERE slug = 'linux-recuperer-le-nom-et-la-version-de-l-os';");
		$conn->exec("UPDATE usefullink SET id = 80 WHERE slug = 'python-generer-le-fichier-requirements-txt';");
		$conn->exec("UPDATE usefullink SET id = 81 WHERE slug = 'python-generer-des-slugs-pour-vos-url';");
		$conn->exec("UPDATE usefullink SET id = 82 WHERE slug = 'css-positionnement-absolu';");
		$conn->exec("UPDATE usefullink SET id = 83 WHERE slug = 'android-studio-affichage-de-longs-logs';");
		$conn->exec("UPDATE usefullink SET id = 84 WHERE slug = 'angular-5-scripts-bundle-js-net-err_aborted';");
		$conn->exec("UPDATE usefullink SET id = 85 WHERE slug = 'html-difference-entre-un-objet-node-et-un-objet-element';");
		$conn->exec("UPDATE usefullink SET id = 86 WHERE slug = 'gerer-une-base-sqlite-avec-adminer';");
		$conn->exec("UPDATE usefullink SET id = 87 WHERE slug = 'lister-l-ensemble-des-routes-en-ruby-on-rails';");
		$conn->exec("UPDATE usefullink SET id = 88 WHERE slug = 'installer-certbot-sous-debian-et-apache';");
		$conn->exec("UPDATE usefullink SET id = 89 WHERE slug = 'filtrer-sur-la-totalite-des-types-d-espace-en-php';");
		$conn->exec("UPDATE usefullink SET id = 90 WHERE slug = 'php-augmenter-la-taille-d-upload-des-fichiers-grace-au-htaccess';");
		$conn->exec("UPDATE usefullink SET id = 91 WHERE slug = 'mysql-supprimer-la-totalite-des-tables-d-une-base-de-donnees-sans-erreur';");
		$conn->exec("UPDATE usefullink SET id = 92 WHERE slug = 'erreur-this-is-incompatible-with-sql_mode-only_full_group_by';");
		$conn->exec("UPDATE usefullink SET id = 93 WHERE slug = 'mysql-appliquer-un-fichier-mysql-en-ligne-de-commande';");
		$conn->exec("UPDATE usefullink SET id = 94 WHERE slug = 'wampserver-php-installer-xdebug';");
		$conn->exec("UPDATE usefullink SET id = 95 WHERE slug = 'ror-a-server-is-already-running';");
		$conn->exec("UPDATE usefullink SET id = 96 WHERE slug = 'linux-donner-les-droits-d-acces-a-un-dossier';");
		$conn->exec("UPDATE usefullink SET id = 97 WHERE slug = 'jquery-utiliser-des-caracteres-speciaux-dans-les-noms-des-selecteurs';");
		$conn->exec("UPDATE usefullink SET id = 98 WHERE slug = 'rgpd-acceder-aux-sites-internet-interdits-aux-europeens';");
		$conn->exec("UPDATE usefullink SET id = 99 WHERE slug = 'php-determiner-si-une-url-est-ou-non-absolue';");
		$conn->exec("UPDATE usefullink SET id = 100 WHERE slug = 'bootstrap-centrer-une-image-responsive';");
		$conn->exec("UPDATE usefullink SET id = 101 WHERE slug = 'js-boucler-sur-un-objet';");
		$conn->exec("UPDATE usefullink SET id = 102 WHERE slug = 'postgresql-tester-une-requete-sql-avant-de-l-appliquer';");
		$conn->exec("UPDATE usefullink SET id = 103 WHERE slug = 'postgresql-supprimer-toutes-tables-d-une-base';");
		$conn->exec("UPDATE usefullink SET id = 104 WHERE slug = 'iframe-ouvrir-une-page-dans-la-fenetre-parent';");
		$conn->exec("UPDATE usefullink SET id = 105 WHERE slug = 'echapper-des-entites-html-en-python-3-4';");
		$conn->exec("UPDATE usefullink SET id = 106 WHERE slug = 'python-supprimer-l-ensemble-des-balises-html';");
		$conn->exec("UPDATE usefullink SET id = 107 WHERE slug = 'executer-du-pur-sql-avec-python-et-sqlalchemy';");
		$conn->exec("UPDATE usefullink SET id = 108 WHERE slug = 'encoder-une-url-ou-une-partie-d-une-url-en-javascript';");
		$conn->exec("UPDATE usefullink SET id = 109 WHERE slug = 'blogger-tester-si-l-on-est-ou-non-sur-mobile';");
		$conn->exec("UPDATE usefullink SET id = 110 WHERE slug = 'git-lister-les-fichiers-dossiers-ignores';");
		$conn->exec("UPDATE usefullink SET id = 111 WHERE slug = 'php-lister-simplement-les-fichiers-dossiers';");
		$conn->exec("UPDATE usefullink SET id = 112 WHERE slug = 'appliquer-gitignore-a-des-fichiers-dossiers-commites';");
		$conn->exec("UPDATE usefullink SET id = 113 WHERE slug = 'ilovepdf-compresser-facilement-vos-pdf';");
		$conn->exec("UPDATE usefullink SET id = 114 WHERE slug = 'wamp-ssl-certificate-problem-unable-to-get-local-issuer-certificate';");
		$conn->exec("UPDATE usefullink SET id = 115 WHERE slug = 'scinder-une-chaine-de-caracteres-avec-chunk_split';");
		$conn->exec("UPDATE usefullink SET id = 116 WHERE slug = 'postgresql-redemarrer-une-sequence';");
		$conn->exec("UPDATE usefullink SET id = 117 WHERE slug = 'installer-postgresql-sous-debian-8-jessie';");
		$conn->exec("UPDATE usefullink SET id = 118 WHERE slug = 'docker-afficher-toutes-les-variables-d-environnement-d-un-container';");
		$conn->exec("UPDATE usefullink SET id = 119 WHERE slug = 'js-supprimer-tous-les-caracteres-d-une-chaine-de-caracteres';");
		$conn->exec("UPDATE usefullink SET id = 120 WHERE slug = 'installer-certbot-auto-sur-debian-jessie';");
		$conn->exec("UPDATE usefullink SET id = 121 WHERE slug = 'docker-redemarrer-apache-sans-stopper-le-container';");
		$conn->exec("UPDATE usefullink SET id = 122 WHERE slug = 'centrer-verticalement-le-contenu-des-pseudos-elements-before-et-after';");
		$conn->exec("UPDATE usefullink SET id = 123 WHERE slug = 'retour-a-la-ligne-dans-une-tooltip-html';");
		$conn->exec("UPDATE usefullink SET id = 124 WHERE slug = 'android-studio-logcat-n-affiche-plus-rien';");
		$conn->exec("UPDATE usefullink SET id = 125 WHERE slug = 'regex-supprimer-les-espaces-entre-les-balises-html';");
		$conn->exec("UPDATE usefullink SET id = 126 WHERE slug = 'postgresql-supprimer-des-doublons';");
		$conn->exec("UPDATE usefullink SET id = 127 WHERE slug = 'postgresql-rechercher-une-valeur-dans-toutes-les-tables';");
		$conn->exec("UPDATE usefullink SET id = 128 WHERE slug = 'ruby-on-rails-forcer-une-migration';");
		$conn->exec("UPDATE usefullink SET id = 129 WHERE slug = 'ruby-on-rails-vider-le-cache';");
		$conn->exec("UPDATE usefullink SET id = 130 WHERE slug = 'react-create-react-app-command-not-found';");
		$conn->exec("UPDATE usefullink SET id = 131 WHERE slug = 'changer-le-mot-de-passe-de-mysql';");
		$conn->exec("UPDATE usefullink SET id = 132 WHERE slug = 'facebook-programme-de-debug-du-partage';");
		$conn->exec("UPDATE usefullink SET id = 133 WHERE slug = 'facebook-api-debugger-les-tokens-d-acces';");
		$conn->exec("UPDATE usefullink SET id = 134 WHERE slug = 'installer-react-native-sur-windows-sans-android-studio';");
		$conn->exec("UPDATE usefullink SET id = 135 WHERE slug = 'react-native-error-unable-to-resolve-module-index';");
		$conn->exec("UPDATE usefullink SET id = 136 WHERE slug = 'telecharger-les-outils-java-sans-compte-utilisateur';");
		$conn->exec("UPDATE usefullink SET id = 137 WHERE slug = 'react-native-javax-net-ssl-sslhandshakeexception';");
		$conn->exec("UPDATE usefullink SET id = 138 WHERE slug = 'react-native-quelques-liens-utiles';");
		$conn->exec("UPDATE usefullink SET id = 139 WHERE slug = 'generer-des-icones-pour-une-application-mobile';");
		$conn->exec("UPDATE usefullink SET id = 140 WHERE slug = 'libreoffice-supprimer-toutes-les-images-d-un-document';");
		$conn->exec("UPDATE usefullink SET id = 141 WHERE slug = 'linux-supprimer-un-dossier-et-tous-ses-fichiers';");
		$conn->exec("UPDATE usefullink SET id = 142 WHERE slug = 'ruby-decoder-une-chaine-en-base-64-et-ecrire-le-resultat-dans-un-fichier';");
		$conn->exec("UPDATE usefullink SET id = 143 WHERE slug = 'arnaque-au-chantage-et-site-web-pretendument-pirate';");
		$conn->exec("UPDATE usefullink SET id = 144 WHERE slug = 'remove-a-file-from-a-git-repository-without-deleting-it-from-the-local-filesystem';");
		$conn->exec("UPDATE usefullink SET id = 145 WHERE slug = 'mysql-recuperer-toutes-les-cles-etrangeres-d-une-colonne';");
		$conn->exec("UPDATE usefullink SET id = 146 WHERE slug = 'docker-supprimer-les-images-inutilisees';");
		$conn->exec("UPDATE usefullink SET id = 147 WHERE slug = 'demarrer-et-arreter-postgresql-en-ligne-de-commande';");
		$conn->exec("UPDATE usefullink SET id = 148 WHERE slug = 'js-cacher-tous-les-elements-d-une-classe-specifique';");
		$conn->exec("UPDATE usefullink SET id = 149 WHERE slug = 'error-0308010c-digital-envelope-routines-unsupported';");
		$conn->exec("UPDATE usefullink SET id = 150 WHERE slug = 'php-a-quoi-sert-le-symbole';");
		$conn->exec("UPDATE usefullink SET id = 151 WHERE slug = 'php-differences-en-require_once-include_once-require-et-include';");
		$conn->exec("UPDATE usefullink SET id = 152 WHERE slug = 'js-remplacer-toutes-les-occurrences-d-une-chaine';");
		$conn->exec("UPDATE usefullink SET id = 153 WHERE slug = 'supprimer-les-valeurs-en-double-d-un-tableau-en-js';");
		$conn->exec("UPDATE usefullink SET id = 154 WHERE slug = 'php-recuperer-un-element-aleatoirement-dans-un-tableau';");
		$conn->exec("UPDATE usefullink SET id = 155 WHERE slug = 'supprimer-les-elements-vides-d-un-tableau-en-php';");
		$conn->exec("UPDATE usefullink SET id = 156 WHERE slug = 'qu-est-ce-signifie-l-erreur-t_paamayim_nekudotayim-en-php';");
		$conn->exec("UPDATE usefullink SET id = 157 WHERE slug = 'inserer-un-nouvel-element-dans-un-tableau-a-n-importe-quelle-position-en-php';");
		$conn->exec("UPDATE usefullink SET id = 158 WHERE slug = 'php-regex-pour-valider-une-adresse-litecoin';");
		$conn->exec("UPDATE usefullink SET id = 159 WHERE slug = 'php-regex-pour-valider-une-adresse-email';");
		$conn->exec("UPDATE usefullink SET id = 160 WHERE slug = 'detecter-si-une-chaine-de-caracteres-contient-du-html';");
		$conn->exec("UPDATE usefullink SET id = 161 WHERE slug = 'l-operateur-en-javascript';");
		$conn->exec("UPDATE usefullink SET id = 162 WHERE slug = 'creer-un-apk-ou-un-aab-en-ligne-de-commandes';");
		$conn->exec("UPDATE usefullink SET id = 163 WHERE slug = 'php-verifier-si-une-session-a-deja-ete-demarree';");
		$conn->exec("UPDATE usefullink SET id = 164 WHERE slug = 'php-convertir-une-chaine-de-caracteres-en-un-booleen';");
		$conn->exec("UPDATE usefullink SET id = 165 WHERE slug = 'js-previsualiser-une-image-avec-son-upload';");
		$conn->exec("UPDATE usefullink SET id = 166 WHERE slug = 'js-recuperer-le-dernier-element-d-un-tableau';");
// dump("ok");
		$id = 167;
		$sql = "SELECT * FROM usefullink where id LIKE '888%'";
		$datas = $conn->fetchAllAssociative($sql);
		
		foreach($datas as $data) {
			$conn->exec("UPDATE usefullink SET id=$id WHERE id=".$data["id"]);
			$id++;
		}
		
		$conn->exec("ALTER TABLE usefullink AUTO_INCREMENT = $id;");

		$datas = 'Calculer le temps d’exécution d’un script PHP#@#2015-05-25 09:38:51.549000#@#2017-07-02 21:59:08.722676#@#calculer-le-temps-d-execution-d-un-script-php
Fonction « in_array » en JQuery#@#2015-05-25 09:45:58.878000#@#2017-07-02 21:59:08.942923#@#fonction-in_array-en-jquery
Tri des dates + heures avec Datatables (JQuery)#@#2015-05-25 09:59:56.177000#@#2017-07-02 21:59:09.144167#@#tri-des-dates-heures-avec-datatables-jquery
Supprimer des tags vides à l’aide de la librairie HTMPurifier#@#2015-05-25 10:06:44.297000#@#2017-07-02 21:59:09.342738#@#supprimer-des-tags-vides-a-l-aide-de-la-librairie-htmpurifier
Imprimer avec Javascript en format « paysage »#@#2015-05-25 10:17:44.618000#@#2017-07-02 21:59:09.594844#@#imprimer-avec-javascript-en-format-paysage
Ajouter un favicon avec Ruby On Rails#@#2015-05-25 17:06:21.868000#@#2017-07-02 21:59:09.870019#@#ajouter-un-favicon-avec-ruby-on-rails
PHP : Remplacer les images d\'un code HTML en base 64#@#2015-06-01 19:01:40.224885#@#2017-07-02 21:59:10.096105#@#php-remplacer-les-images-d-un-code-html-en-base-64
Soumettre un formulaire et ouvrir le résultat dans un nouvel onglet#@#2015-06-01 19:27:40.135885#@#2017-07-02 21:59:10.377038#@#soumettre-un-formulaire-et-ouvrir-le-resultat-dans-un-nouvel-onglet
Un *.exe qui ne se lance pas sous Vista#@#2015-07-16 20:39:36.834825#@#2017-07-02 21:59:10.592387#@#un-exe-qui-ne-se-lance-pas-sous-vista
Les paramètres facultatifs dans une méthode en Ruby#@#2015-07-18 19:07:22.853000#@#2017-07-02 21:59:10.815561#@#les-parametres-facultatifs-dans-une-methode-en-ruby
Les commentaires en Ruby#@#2015-07-18 20:53:46.085000#@#2017-07-02 21:59:11.032947#@#les-commentaires-en-ruby
SF2 : Affichage du nom des pays en entier#@#2015-07-24 22:51:08.650284#@#2017-07-02 21:59:11.176788#@#sf2-affichage-du-nom-des-pays-en-entier
Empêcher le redimensionnement d’un « textarea »#@#2015-07-25 05:53:25.140284#@#2017-07-02 21:59:11.295492#@#empecher-le-redimensionnement-d-un-textarea
Supprimer le dernier caractère d’une chaîne de caractère (PHP)#@#2015-07-25 07:06:42.467284#@#2017-07-02 21:59:11.452370#@#supprimer-le-dernier-caractere-d-une-chaine-de-caractere-php
Evaluer une chaîne de caractères#@#2015-07-25 07:16:50.583284#@#2017-07-02 21:59:11.574962#@#evaluer-une-chaine-de-caracteres
Sortir d’une boucle en PHP#@#2015-07-25 07:48:25.213284#@#2017-07-02 21:59:11.675838#@#sortir-d-une-boucle-en-php
Lister l’ensemble des fichiers d’un répertoire (PHP)#@#2015-08-01 09:40:32.990000#@#2017-07-02 21:59:11.752246#@#lister-l-ensemble-des-fichiers-d-un-repertoire-php
Nombre de chiffres après la virgule en Javascript#@#2015-08-01 09:50:09.300000#@#2017-07-02 21:59:11.851855#@#nombre-de-chiffres-apres-la-virgule-en-javascript
Compter le nombre de lignes dans un fichier (PHP)#@#2015-08-01 11:18:24.104000#@#2017-07-02 21:59:11.962909#@#compter-le-nombre-de-lignes-dans-un-fichier-php
Supprimer tous les accents en PHP#@#2015-08-01 11:23:09.934000#@#2017-07-02 21:59:12.063977#@#supprimer-tous-les-accents-en-php
Fichier « Locked » dans Tortoise SVN#@#2015-08-01 11:31:23.393000#@#2017-07-02 21:59:12.206193#@#fichier-locked-dans-tortoise-svn
Coloration syntaxique de Twig sous Notepad++#@#2015-08-26 08:01:40.942000#@#2017-07-02 21:59:12.329038#@#coloration-syntaxique-de-twig-sous-notepad
Un "var_dump" en Javascript#@#2015-08-26 08:07:38.136000#@#2017-07-02 21:59:12.460403#@#un-var_dump-en-javascript
Fonction pour supprimer les caractères spéciaux en PHP#@#2015-08-26 08:17:41.415000#@#2017-07-02 21:59:12.567988#@#fonction-pour-supprimer-les-caracteres-speciaux-en-php
Détecter le protocole utilisé en PHP#@#2015-08-26 08:23:27.566000#@#2017-07-02 21:59:12.684428#@#detecter-le-protocole-utilise-en-php
Accéder aux variables de "php.ini"#@#2015-08-26 09:22:20.381000#@#2017-07-02 21:59:12.767532#@#acceder-aux-variables-de-php-ini
JS : Trier un tableau d\'objets#@#2015-09-22 19:04:59.574686#@#2017-07-02 21:59:12.849221#@#js-trier-un-tableau-d-objets
JS : Trier un tableau + encodage#@#2015-09-22 19:18:56.687164#@#2017-07-02 21:59:12.939107#@#js-trier-un-tableau-encodage
Récupérer une base de données MySQL avec les fichiers *.ibd et *.frm#@#2015-11-03 23:36:09.195778#@#2017-07-02 21:59:13.040428#@#recuperer-une-base-de-donnees-mysql-avec-les-fichiers-ibd-et-frm
Wamp : MySQL refuse de démarrer#@#2015-11-04 19:56:46.664634#@#2017-07-02 21:59:13.132502#@#wamp-mysql-refuse-de-demarrer
Utiliser Zend Framework dans Symfony2#@#2015-12-21 23:05:18.698021#@#2017-07-02 21:59:13.313961#@#utiliser-zend-framework-dans-symfony2
Transformer une chaîne de caractères en tableau en PHP#@#2015-12-21 23:17:35.113390#@#2017-07-02 21:59:13.531379#@#transformer-une-chaine-de-caracteres-en-tableau-en-php
Trouver le type d’une variable (PHP)#@#2015-12-21 23:20:26.513741#@#2017-07-02 21:59:13.723595#@#trouver-le-type-d-une-variable-php
Valider une adresse email en Javascript#@#2015-12-21 23:32:36.892237#@#2017-07-02 21:59:13.977131#@#valider-une-adresse-email-en-javascript
Sélectionner le texte d\'un champ en Javascript#@#2015-12-21 23:44:55.774175#@#2017-07-02 21:59:14.216857#@#selectionner-le-texte-d-un-champ-en-javascript
Compter le nombre de lignes dans un fichier (PHP)#@#2015-12-21 23:51:38.741458#@#2017-07-02 21:59:14.415766#@#compter-le-nombre-de-lignes-dans-un-fichier-php-c9047714-0394-4a96-8802-839837926ea3
Désactiver un bouton radio (ou autres) en JQuery#@#2016-01-01 17:20:07.766279#@#2017-07-02 21:59:14.593603#@#desactiver-un-bouton-radio-ou-autres-en-jquery
Supprimer les balises "<br / >" en fin de chaîne de caractères#@#2016-01-01 17:23:34.888030#@#2017-07-02 21:59:14.792448#@#supprimer-les-balises-br-en-fin-de-chaine-de-caracteres
L’attribut « autocomplete » (HTML5)#@#2016-01-01 17:26:51.194291#@#2017-07-02 21:59:14.918236#@#l-attribut-autocomplete-html5
Détection du protocole avec PHP#@#2016-01-01 17:28:27.742063#@#2017-07-02 21:59:15.103504#@#detection-du-protocole-avec-php
Correspondance variables HTTP PHP <-> Symfony 2#@#2016-01-01 17:39:18.920654#@#2017-07-02 21:59:15.262116#@#correspondance-variables-http-php-symfony-2
Tester l’existence d’une fonction en Javascript#@#2016-01-01 17:42:22.852468#@#2017-07-02 21:59:15.428223#@#tester-l-existence-d-une-fonction-en-javascript
Enlever la protection CSFR d’un formulaire (Symfony2)#@#2016-01-01 17:47:28.906114#@#2017-07-02 21:59:15.548398#@#enlever-la-protection-csfr-d-un-formulaire-symfony2
Contrainte sur les fichiers dans un CallbackValidator (Symfony2)#@#2016-01-01 17:50:21.949035#@#2017-07-02 21:59:15.748448#@#contrainte-sur-les-fichiers-dans-un-callbackvalidator-symfony2
Upload de fichier avec Ajax, JQuery et XMLHTTPRequest 2#@#2016-01-01 18:01:48.049988#@#2017-07-02 21:59:15.881756#@#upload-de-fichier-avec-ajax-jquery-et-xmlhttprequest-2
Migrer des modifications avec Doctrine2 et Symfony2#@#2016-01-01 20:42:34.079405#@#2017-07-02 21:59:16.038410#@#migrer-des-modifications-avec-doctrine2-et-symfony2
Convertir des fichiers Word ou Excel en PDF avec PHP#@#2016-01-01 20:50:17.718977#@#2017-07-02 21:59:16.261112#@#convertir-des-fichiers-word-ou-excel-en-pdf-avec-php
Retirer le « app.php » de l’URL sous Symfony2#@#2016-01-01 20:58:19.466114#@#2017-07-02 21:59:16.445737#@#retirer-le-app-php-de-l-url-sous-symfony2
Alternance de couleurs en CSS#@#2016-01-01 21:05:53.609305#@#2017-07-02 21:59:16.646074#@#alternance-de-couleurs-en-css
Sélectionner toutes les checkbox avec JQuery#@#2016-01-01 21:11:09.738996#@#2017-07-02 21:59:16.878081#@#selectionner-toutes-les-checkbox-avec-jquery
Inclure sur son site un fichier JS hébergé sur Github#@#2016-01-03 20:06:53.160918#@#2017-07-02 21:59:17.188752#@#inclure-sur-son-site-un-fichier-js-heberge-sur-github
Réparer une chaîne HTML en PHP#@#2016-02-04 21:07:09.911761#@#2017-07-02 21:59:17.425631#@#reparer-une-chaine-html-en-php
Faire fonctionner cURL avec PHP 7 et Wamp#@#2016-02-07 16:08:17.406442#@#2017-07-02 21:59:17.597700#@#faire-fonctionner-curl-avec-php-7-et-wamp
C# : Permuter deux variables avec l\'opérateur#@#2016-10-16 15:26:36.536212#@#2017-07-02 21:59:17.763706#@#c-permuter-deux-variables-avec-l-operateur
jQuery - Appeler $(this) à l\'intérieur de la fonction Ajax#@#2016-12-30 16:53:37.165855#@#2017-07-02 21:59:17.963787#@#jquery-appeler-this-a-l-interieur-de-la-fonction-ajax
Silex - Récupérer l\'objet \'$request\' avec Silex 2.*#@#2016-12-30 17:07:24.193710#@#2017-07-02 21:59:18.195134#@#silex-recuperer-l-objet-request-avec-silex-2
Enregistrer des emoji dans une base de données MySQL#@#2017-01-02 10:59:29.533157#@#2017-07-02 21:59:18.396283#@#enregistrer-des-emoji-dans-une-base-de-donnees-mysql
PHP - Supprimer des balises HTML#@#2017-01-17 16:01:01.198773#@#2017-07-02 21:59:18.529861#@#php-supprimer-des-balises-html
PHP - Supprimer les commentaires HTML#@#2017-01-18 22:07:05.567953#@#2017-07-02 21:59:18.661455#@#php-supprimer-les-commentaires-html
C# - Tester si une chaîne est contenue dans une autre#@#2017-01-19 05:37:54.609848#@#2017-07-02 21:59:18.860594#@#c-tester-si-une-chaine-est-contenue-dans-une-autre
MySQL faire la somme d\'une somme#@#2017-02-12 09:39:53.969590#@#2017-07-02 21:59:19.126438#@#mysql-faire-la-somme-d-une-somme
Font Awesome avec les lettres de l\'alphabet#@#2017-02-12 09:48:12.264982#@#2017-07-02 21:59:19.339089#@#font-awesome-avec-les-lettres-de-l-alphabet
Linux - Récupérer le nom et la version de l\'OS#@#2017-02-26 11:06:28.255162#@#2017-07-02 21:59:19.491286#@#linux-recuperer-le-nom-et-la-version-de-l-os
Python - Générer le fichier requirements.txt#@#2017-05-14 10:40:36.877833#@#2017-07-02 21:59:19.612126#@#python-generer-le-fichier-requirements-txt
Python - Générer des slugs pour vos URL#@#2017-07-01 17:40:33.806254#@#2017-07-02 21:59:19.756956#@#python-generer-des-slugs-pour-vos-url
CSS - Positionnement absolu#@#2017-11-12 11:47:11.136982#@#2017-11-12 11:47:11.136982#@#css-positionnement-absolu
Android Studio - Affichage de longs logs#@#2017-11-12 11:53:25.182581#@#2017-11-12 11:53:25.182581#@#android-studio-affichage-de-longs-logs
Angular 5 - scripts.bundle.js net::ERR_ABORTED#@#2017-12-12 21:48:59.787114#@#2017-12-12 21:48:59.787114#@#angular-5-scripts-bundle-js-net-err_aborted
HTML – Différence entre un objet node et un objet element#@#2017-12-12 22:30:24.602069#@#2017-12-12 22:30:24.602069#@#html-difference-entre-un-objet-node-et-un-objet-element
Gérer une base SQLite avec Adminer#@#2017-12-17 09:56:22.532268#@#2017-12-17 09:56:22.532268#@#gerer-une-base-sqlite-avec-adminer
Lister l\'ensemble des routes en Ruby on Rails#@#2017-12-28 11:52:29.585322#@#2017-12-28 11:52:29.585322#@#lister-l-ensemble-des-routes-en-ruby-on-rails
Installer Certbot sous Debian et Apache#@#2017-12-28 15:48:16.840707#@#2018-04-08 15:25:17.300518#@#installer-certbot-sous-debian-et-apache
Filtrer sur la totalité des types d\'espace en PHP#@#2018-01-14 00:51:34.768862#@#2018-01-14 00:53:35.861329#@#filtrer-sur-la-totalite-des-types-d-espace-en-php
PHP - Augmenter la taille d\'upload des fichiers grâce au HTACCESS#@#2018-01-21 11:44:44.892256#@#2018-01-21 11:44:44.892256#@#php-augmenter-la-taille-d-upload-des-fichiers-grace-au-htaccess
MySQL - Supprimer la totalité des tables d\'une base de données sans erreur#@#2018-01-21 11:48:50.779867#@#2018-01-21 11:48:50.779867#@#mysql-supprimer-la-totalite-des-tables-d-une-base-de-donnees-sans-erreur
Erreur : "this is incompatible with sql_mode=only_full_group_by"#@#2018-01-21 11:56:07.115118#@#2018-01-21 11:56:07.115118#@#erreur-this-is-incompatible-with-sql_mode-only_full_group_by
MySQL - Appliquer un fichier MySQL en ligne de commande#@#2018-03-04 15:33:27.885876#@#2018-03-04 15:33:27.885876#@#mysql-appliquer-un-fichier-mysql-en-ligne-de-commande
WampServer + Php - Installer XDebug#@#2018-03-10 22:50:16.755809#@#2018-03-10 22:50:16.755809#@#wampserver-php-installer-xdebug
RoR - A server is already running#@#2018-03-10 22:55:57.623502#@#2018-03-10 22:55:57.623502#@#ror-a-server-is-already-running
Linux - Donner les droits d\'accès à un dossier#@#2018-04-08 15:15:05.124030#@#2018-04-08 15:15:05.124030#@#linux-donner-les-droits-d-acces-a-un-dossier
jQuery - Utiliser des caractères spéciaux dans les noms des sélecteurs#@#2018-04-09 22:31:22.677269#@#2018-04-09 22:31:22.677269#@#jquery-utiliser-des-caracteres-speciaux-dans-les-noms-des-selecteurs
RGPD - Accéder aux sites Internet "interdits" aux Européens#@#2018-05-27 11:29:17.444627#@#2018-05-27 11:29:17.444627#@#rgpd-acceder-aux-sites-internet-interdits-aux-europeens
PHP - Déterminer si une URL est ou non absolue#@#2018-05-28 21:26:38.128127#@#2018-05-28 21:26:38.128127#@#php-determiner-si-une-url-est-ou-non-absolue
Bootstrap - Centrer une image responsive#@#2018-06-05 21:37:55.174049#@#2018-06-05 21:37:55.174049#@#bootstrap-centrer-une-image-responsive
JS - Boucler sur un objet#@#2018-08-02 22:06:00.863276#@#2018-08-02 22:06:00.863276#@#js-boucler-sur-un-objet
PostGreSQL – Tester une requête SQL avant de l’appliquer#@#2018-08-30 20:08:40.068650#@#2018-08-30 20:08:40.068650#@#postgresql-tester-une-requete-sql-avant-de-l-appliquer
PostGreSQL – Supprimer toutes tables d\'une base#@#2018-09-04 21:01:42.411949#@#2018-09-04 21:01:42.411949#@#postgresql-supprimer-toutes-tables-d-une-base
iframe - Ouvrir une page dans la fenêtre parent#@#2018-09-04 21:14:10.313311#@#2018-09-04 21:14:10.313311#@#iframe-ouvrir-une-page-dans-la-fenetre-parent
Échapper des entités HTML en Python 3.4+#@#2018-10-21 09:59:48.965334#@#2018-10-21 09:59:48.965334#@#echapper-des-entites-html-en-python-3-4
Python - Supprimer l\'ensemble des balises HTML#@#2018-10-21 10:03:57.660192#@#2018-10-21 10:03:57.660192#@#python-supprimer-l-ensemble-des-balises-html
Exécuter du pur SQL avec Python et SQLAlchemy#@#2018-11-04 14:00:21.348692#@#2018-11-04 14:00:21.348692#@#executer-du-pur-sql-avec-python-et-sqlalchemy
Encoder une URL ou une partie d’une URL en JavaScript#@#2018-12-10 23:19:52.044812#@#2018-12-10 23:20:32.927195#@#encoder-une-url-ou-une-partie-d-une-url-en-javascript
Blogger – Tester si l’on est ou non sur mobile#@#2018-12-10 23:27:39.251720#@#2018-12-10 23:27:39.251720#@#blogger-tester-si-l-on-est-ou-non-sur-mobile
Git - Lister les fichiers / dossiers ignorés#@#2018-12-15 00:43:19.558021#@#2018-12-15 00:43:19.558021#@#git-lister-les-fichiers-dossiers-ignores
PHP - Lister simplement les fichiers / dossiers#@#2018-12-15 00:49:00.538751#@#2018-12-15 00:49:00.538751#@#php-lister-simplement-les-fichiers-dossiers
Appliquer .gitignore à des fichiers / dossiers commités#@#2018-12-22 22:38:08.400947#@#2018-12-22 22:38:08.400947#@#appliquer-gitignore-a-des-fichiers-dossiers-commites
ILovePDF - Compresser facilement vos PDF#@#2019-02-23 13:02:26.984127#@#2019-02-23 13:02:26.984127#@#ilovepdf-compresser-facilement-vos-pdf
Wamp - ssl certificate problem: unable to get local issuer certificate#@#2019-03-11 23:49:29.745183#@#2019-03-11 23:49:29.745183#@#wamp-ssl-certificate-problem-unable-to-get-local-issuer-certificate
Scinder une chaîne de caractères avec chunk_split#@#2019-05-19 07:53:38.600282#@#2019-05-19 07:53:38.600282#@#scinder-une-chaine-de-caracteres-avec-chunk_split
PostGreSQL - Redémarrer une séquence#@#2019-05-21 20:32:02.212367#@#2019-05-21 20:32:02.212367#@#postgresql-redemarrer-une-sequence
Installer PostgreSQL sous Debian 8 (Jessie)#@#2019-05-25 10:44:16.858685#@#2019-05-25 16:30:28.722187#@#installer-postgresql-sous-debian-8-jessie
Docker - Afficher toutes les variables d\'environnement d\'un container#@#2019-05-25 16:33:45.201373#@#2019-11-01 10:47:51.173592#@#docker-afficher-toutes-les-variables-d-environnement-d-un-container
JS - Supprimer tous les caractères d\'une chaîne de caractères#@#2019-06-02 14:14:45.226913#@#2019-06-02 14:14:45.226913#@#js-supprimer-tous-les-caracteres-d-une-chaine-de-caracteres
Installer certbot-auto sur Debian Jessie#@#2019-07-30 21:23:26.112610#@#2019-07-30 21:23:26.112610#@#installer-certbot-auto-sur-debian-jessie
Docker – Redémarrer Apache sans stopper le container#@#2019-07-30 21:24:29.308954#@#2019-07-30 21:24:29.308954#@#docker-redemarrer-apache-sans-stopper-le-container
Centrer verticalement le contenu des pseudos élements :before et :after#@#2019-08-09 13:38:20.634750#@#2019-08-09 13:38:20.634750#@#centrer-verticalement-le-contenu-des-pseudos-elements-before-et-after
Retour à la ligne dans une tooltip HTML#@#2019-10-26 10:35:17.510250#@#2019-10-26 10:35:17.510250#@#retour-a-la-ligne-dans-une-tooltip-html
Android Studio - Logcat n\'affiche plus rien#@#2019-11-01 10:54:44.966129#@#2019-11-01 10:54:44.966129#@#android-studio-logcat-n-affiche-plus-rien
Regex - Supprimer les espaces entre les balises HTML#@#2019-11-01 10:57:18.160556#@#2019-11-01 10:57:18.160556#@#regex-supprimer-les-espaces-entre-les-balises-html
PostGreSQL - Supprimer des doublons#@#2019-11-01 10:59:27.163053#@#2019-11-01 10:59:27.163053#@#postgresql-supprimer-des-doublons
PostGreSQL - Rechercher une valeur dans toutes les tables#@#2019-11-01 11:03:36.302526#@#2019-11-01 11:03:36.302526#@#postgresql-rechercher-une-valeur-dans-toutes-les-tables
Ruby on Rails - Forcer une migration#@#2021-08-01 08:59:31.300468#@#2021-08-01 08:59:31.300468#@#ruby-on-rails-forcer-une-migration
Ruby on Rails - Vider le cache#@#2021-08-01 09:01:05.012252#@#2021-08-01 09:01:05.012252#@#ruby-on-rails-vider-le-cache
React - "create-react-app command not found"#@#2021-08-12 17:23:36.844453#@#2021-08-12 17:23:36.844453#@#react-create-react-app-command-not-found
Changer le mot de passe de MySQL#@#2022-08-27 13:53:01.538135#@#2022-08-27 13:53:01.538135#@#changer-le-mot-de-passe-de-mysql
Facebook - Programme de débug du partage#@#2022-10-10 20:45:47.348828#@#2022-10-10 20:45:47.348828#@#facebook-programme-de-debug-du-partage
Facebook API - Débugger les tokens d\'accès#@#2022-10-10 20:53:48.135958#@#2022-10-10 20:53:48.135958#@#facebook-api-debugger-les-tokens-d-acces
Installer React Native sur Windows sans Android Studio#@#2022-11-02 22:41:18.592237#@#2022-11-02 22:41:18.592237#@#installer-react-native-sur-windows-sans-android-studio
React Native Error: Unable to resolve module `./index`#@#2022-11-02 22:41:30.237319#@#2022-11-02 22:41:30.237319#@#react-native-error-unable-to-resolve-module-index
Télécharger les outils Java sans compte utilisateur#@#2022-11-02 22:51:31.813172#@#2022-11-02 22:51:31.813172#@#telecharger-les-outils-java-sans-compte-utilisateur
React Native : javax.net.ssl.SSLHandshakeException#@#2022-11-02 22:56:20.006436#@#2022-11-02 22:56:20.006436#@#react-native-javax-net-ssl-sslhandshakeexception
React Native - Quelques liens utiles#@#2022-11-02 23:02:36.616099#@#2022-11-02 23:02:36.616099#@#react-native-quelques-liens-utiles
Générer des icônes pour une application mobile#@#2022-11-06 16:57:54.686000#@#2022-11-06 16:57:54.686000#@#generer-des-icones-pour-une-application-mobile
LibreOffice - Supprimer toutes les images d\'un document#@#2022-12-29 08:38:31.657068#@#2022-12-29 08:38:31.657068#@#libreoffice-supprimer-toutes-les-images-d-un-document
Linux - Supprimer un dossier et tous ses fichiers#@#2023-01-07 02:21:59.032641#@#2023-01-07 02:21:59.032641#@#linux-supprimer-un-dossier-et-tous-ses-fichiers
Ruby - Décoder une chaîne en base 64 et écrire le résultat dans un fichier#@#2023-02-05 10:56:31.347734#@#2023-02-05 10:56:31.347734#@#ruby-decoder-une-chaine-en-base-64-et-ecrire-le-resultat-dans-un-fichier
Arnaque au chantage et site web prétendument piraté#@#2023-02-06 22:22:14.346657#@#2023-02-06 23:19:18.797772#@#arnaque-au-chantage-et-site-web-pretendument-pirate
Supprimer un fichier d\'un référentiel Git sans le supprimer du système de fichiers local#@#2023-02-20 23:29:13.629427#@#2023-02-20 23:31:30.543431#@#remove-a-file-from-a-git-repository-without-deleting-it-from-the-local-filesystem
MySQL - Récupérer toutes les clés étrangères d\'une colonne#@#2023-02-21 00:31:28.599109#@#2023-02-21 00:31:28.599109#@#mysql-recuperer-toutes-les-cles-etrangeres-d-une-colonne
Docker - Supprimer les images inutilisées#@#2023-03-06 00:26:56.289049#@#2023-03-06 00:26:56.289049#@#docker-supprimer-les-images-inutilisees
Démarrer et arrêter PostGreSQL en ligne de commande#@#2023-03-06 00:31:13.517998#@#2023-03-06 00:31:13.517998#@#demarrer-et-arreter-postgresql-en-ligne-de-commande
JS - Cacher tous les éléments d\'une classe spécifique#@#2023-05-13 09:37:40.768659#@#2023-05-13 09:37:40.768659#@#js-cacher-tous-les-elements-d-une-classe-specifique
"error:0308010C:digital envelope routines::unsupported"#@#2023-05-14 08:49:48.383428#@#2023-05-14 08:49:48.383428#@#error-0308010c-digital-envelope-routines-unsupported
PHP - à quoi sert le symbole "??"#@#2023-08-30 13:36:34.532512#@#2023-08-30 13:36:34.532512#@#php-a-quoi-sert-le-symbole
PHP - Différences en require_once , include_once, require et include#@#2023-09-12 08:27:26.013103#@#2023-09-12 08:27:26.013103#@#php-differences-en-require_once-include_once-require-et-include
JS - Remplacer toutes les occurrences d\'une chaîne#@#2023-09-12 12:42:14.211137#@#2023-09-12 12:42:14.211137#@#js-remplacer-toutes-les-occurrences-d-une-chaine
Supprimer les valeurs en double d\'un tableau en JS#@#2023-09-12 12:47:12.198124#@#2023-09-12 12:47:12.198124#@#supprimer-les-valeurs-en-double-d-un-tableau-en-js
PHP - Récupérer un élément aléatoirement dans un tableau#@#2023-09-18 22:43:19.922284#@#2023-09-18 22:43:19.922284#@#php-recuperer-un-element-aleatoirement-dans-un-tableau
Supprimer les éléments vides d\'un tableau en PHP#@#2023-09-26 07:43:37.896099#@#2023-09-26 07:43:37.896099#@#supprimer-les-elements-vides-d-un-tableau-en-php
Qu\'est-ce signifie l\'erreur T_PAAMAYIM_NEKUDOTAYIM en PHP ?#@#2023-09-26 07:45:51.556144#@#2023-09-26 07:45:51.556144#@#qu-est-ce-signifie-l-erreur-t_paamayim_nekudotayim-en-php
Insérer un nouvel élément dans un tableau à n\'importe quelle position en PHP#@#2023-09-26 07:48:31.582122#@#2023-09-26 07:48:31.582122#@#inserer-un-nouvel-element-dans-un-tableau-a-n-importe-quelle-position-en-php
PHP - Regex pour valider une adresse Litecoin#@#2023-10-05 06:40:42.264565#@#2023-10-05 06:40:42.264565#@#php-regex-pour-valider-une-adresse-litecoin
PHP - Regex pour valider une adresse email#@#2023-10-05 06:44:13.234601#@#2023-10-05 06:44:13.234601#@#php-regex-pour-valider-une-adresse-email
Détecter si une chaîne de caractères contient du HTML#@#2023-10-06 17:52:25.496510#@#2023-10-06 17:52:25.496510#@#detecter-si-une-chaine-de-caracteres-contient-du-html
L\'opérateur !! en Javascript#@#2023-10-06 17:59:08.716669#@#2023-10-06 17:59:08.716669#@#l-operateur-en-javascript
Créer un APK ou un AAB en ligne de commandes#@#2024-01-06 11:07:37.432171#@#2024-01-06 11:07:37.432171#@#creer-un-apk-ou-un-aab-en-ligne-de-commandes
PHP : Vérifier si une session a déjà été démarrée#@#2024-02-05 22:49:24.877083#@#2024-02-05 22:49:24.877083#@#php-verifier-si-une-session-a-deja-ete-demarree
PHP -  Convertir une chaîne de caractères en un booléen#@#2024-02-05 22:56:26.675919#@#2024-02-05 22:56:26.675919#@#php-convertir-une-chaine-de-caracteres-en-un-booleen
JS - Prévisualiser une image avec son upload#@#2024-02-08 00:08:20.187812#@#2024-02-08 00:08:20.187812#@#js-previsualiser-une-image-avec-son-upload
JS - Récupérer le dernier élément d\'un tableau#@#2024-02-08 00:10:41.456718#@#2024-02-08 00:10:41.456718#@#js-recuperer-le-dernier-element-d-un-tableau';

		foreach(explode("\n", $datas) as $data) {
			$data = explode("#@#", $data);
			$entity = $this->em->getRepository(UsefulLink::class)->findOneBy(["title" => $data[0]]);
			
			if(empty($entity))
				continue;

			$entity->setCreatedAt(new \DateTime($data[1]));
			$entity->setUpdatedAt(new \DateTime($data[2]));
			$entity->setSlug($data[3]);
			
			$this->em->persist($entity);
		}

		$this->em->flush();

		$entities = $this->em->getRepository(UsefulLink::class)->findAll();
		
		$tags = [];
		
		foreach($entities as $entity) {
			if(empty($entity->getSlug()))
				$entity->setSlug();

			if(empty($entity->getCreatedAt()))
				$entity->setCreatedAt(new \DateTime());

			if(empty($entity->getUpdatedAt()))
				$entity->setUpdatedAt(new \DateTime());
			
			if(!empty($entity->getTags())) {
				foreach(json_decode($entity->getTags()) as $tag) {
					$tags[] = $tag->value;
				}
			}
			
			$this->em->persist($entity);
		}

		$this->em->flush();

		$currentTags = 'Calculer le temps d’exécution d’un script PHP#@#PHP
Fonction « in_array » en JQuery#@#jQuery
Tri des dates + heures avec Datatables (JQuery)#@#jQuery,Datatables
Supprimer des tags vides à l’aide de la librairie HTMPurifier#@#PHP
Imprimer avec Javascript en format « paysage »#@#JavaScript,CSS
Ajouter un favicon avec Ruby On Rails#@#Ruby on Rails
PHP : Remplacer les images d\'un code HTML en base 64#@#PHP,HTML
Soumettre un formulaire et ouvrir le résultat dans un nouvel onglet#@#PHP,HTML
Un *.exe qui ne se lance pas sous Vista#@#Windows Vista
Les paramètres facultatifs dans une méthode en Ruby#@#Ruby
Les commentaires en Ruby#@#Ruby
SF2 : Affichage du nom des pays en entier#@#Symfony
Empêcher le redimensionnement d’un « textarea »#@#HTML,CSS
Supprimer le dernier caractère d’une chaîne de caractère (PHP)#@#PHP
Evaluer une chaîne de caractères#@#PHP
Sortir d’une boucle en PHP#@#PHP
Lister l’ensemble des fichiers d’un répertoire (PHP)#@#PHP
Nombre de chiffres après la virgule en Javascript#@#JavaScript
Compter le nombre de lignes dans un fichier (PHP)#@#PHP
Supprimer tous les accents en PHP#@#PHP
Fichier « Locked » dans Tortoise SVN#@#TortoiseSVN
Coloration syntaxique de Twig sous Notepad++#@#Twig
Un "var_dump" en Javascript#@#JavaScript
Fonction pour supprimer les caractères spéciaux en PHP#@#PHP
Détecter le protocole utilisé en PHP#@#PHP
Accéder aux variables de "php.ini"#@#PHP
JS : Trier un tableau d\'objets#@#JavaScript
JS : Trier un tableau + encodage#@#JavaScript
Récupérer une base de données MySQL avec les fichiers *.ibd et *.frm#@#SQL
Wamp : MySQL refuse de démarrer#@#SQL
Utiliser Zend Framework dans Symfony2#@#Symfony
Transformer une chaîne de caractères en tableau en PHP#@#PHP
Trouver le type d’une variable (PHP)#@#PHP
Valider une adresse email en Javascript#@#JavaScript
Sélectionner le texte d\'un champ en Javascript#@#JavaScript
Compter le nombre de lignes dans un fichier (PHP)#@#PHP
Désactiver un bouton radio (ou autres) en JQuery#@#CSS
Supprimer les balises "<br / >" en fin de chaîne de caractères#@#PHP
L’attribut « autocomplete » (HTML5)#@#HTML
Détection du protocole avec PHP#@#PHP
Correspondance variables HTTP PHP <-> Symfony 2#@#Symfony
Tester l’existence d’une fonction en Javascript#@#JavaScript
Enlever la protection CSFR d’un formulaire (Symfony2)#@#Symfony
Contrainte sur les fichiers dans un CallbackValidator (Symfony2)#@#Symfony
Upload de fichier avec Ajax, JQuery et XMLHTTPRequest 2#@#JavaScript
Migrer des modifications avec Doctrine2 et Symfony2#@#Symfony
Convertir des fichiers Word ou Excel en PDF avec PHP#@#PHP
Retirer le « app.php » de l’URL sous Symfony2#@#Symfony
Alternance de couleurs en CSS#@#CSS
Sélectionner toutes les checkbox avec JQuery#@#jQuery
Inclure sur son site un fichier JS hébergé sur Github#@#JavaScript,Github
Réparer une chaîne HTML en PHP#@#PHP
Faire fonctionner cURL avec PHP 7 et Wamp#@#PHP
C# : Permuter deux variables avec l\'opérateur#@#C#
jQuery - Appeler $(this) à l\'intérieur de la fonction Ajax#@#jQuery
Silex - Récupérer l\'objet \'$request\' avec Silex 2.*#@#Silex,PHP
Enregistrer des emoji dans une base de données MySQL#@#MySQL
PHP - Supprimer des balises HTML#@#PHP,HTML
PHP - Supprimer les commentaires HTML#@#PHP,HTML
C# - Tester si une chaîne est contenue dans une autre#@#C#
MySQL faire la somme d\'une somme#@#MySQL
Font Awesome avec les lettres de l\'alphabet#@#CSS,HTML
Linux - Récupérer le nom et la version de l\'OS#@#Linux
Python - Générer le fichier requirements.txt#@#Python
Python - Générer des slugs pour vos URL#@#Python
CSS - Positionnement absolu#@#CSS
Android Studio - Affichage de longs logs#@#Android,Java
Angular 5 - scripts.bundle.js net::ERR_ABORTED#@#Angular
HTML – Différence entre un objet node et un objet element#@#HTML
Gérer une base SQLite avec Adminer#@#SQLite,PHP
Lister l\'ensemble des routes en Ruby on Rails#@#Ruby on Rails
Installer Certbot sous Debian et Apache#@#Linux,Apache
Filtrer sur la totalité des types d\'espace en PHP#@#PHP
PHP - Augmenter la taille d\'upload des fichiers grâce au HTACCESS#@#PHP,Apache
MySQL - Supprimer la totalité des tables d\'une base de données sans erreur#@#MySQL
Erreur : "this is incompatible with sql_mode=only_full_group_by"#@#MySQL
MySQL - Appliquer un fichier MySQL en ligne de commande#@#MySQL
WampServer + Php - Installer XDebug#@#PHP
RoR - A server is already running#@#Ruby on Rails
Linux - Donner les droits d\'accès à un dossier#@#Linux
jQuery - Utiliser des caractères spéciaux dans les noms des sélecteurs#@#jQuery
RGPD - Accéder aux sites Internet "interdits" aux Européens#@#Législation
PHP - Déterminer si une URL est ou non absolue#@#PHP
Bootstrap - Centrer une image responsive#@#CSS
JS - Boucler sur un objet#@#JavaScript
PostGreSQL – Tester une requête SQL avant de l’appliquer#@#PostgreSQL,SQL
PostGreSQL – Supprimer toutes tables d\'une base#@#PostgreSQL
iframe - Ouvrir une page dans la fenêtre parent#@#HTML,JavaScript
Échapper des entités HTML en Python 3.4+#@#Python
Python - Supprimer l\'ensemble des balises HTML#@#Python
Exécuter du pur SQL avec Python et SQLAlchemy#@#SQLAlchemy,Python
Encoder une URL ou une partie d’une URL en JavaScript#@#JavaScript
Blogger – Tester si l’on est ou non sur mobile#@#Blogger
Git - Lister les fichiers / dossiers ignorés#@#Github
PHP - Lister simplement les fichiers / dossiers#@#PHP
Appliquer .gitignore à des fichiers / dossiers commités#@#Github
ILovePDF - Compresser facilement vos PDF#@#
Wamp - ssl certificate problem: unable to get local issuer certificate#@#PHP,Apache
Scinder une chaîne de caractères avec chunk_split#@#PHP
PostGreSQL - Redémarrer une séquence#@#PostgreSQL
Installer PostgreSQL sous Debian 8 (Jessie)#@#PostgreSQL
Docker - Afficher toutes les variables d\'environnement d\'un container#@#Docker
JS - Supprimer tous les caractères d\'une chaîne de caractères#@#JavaScript
Installer certbot-auto sur Debian Jessie#@#Docker,Linux
Docker – Redémarrer Apache sans stopper le container#@#Apache,Docker
Centrer verticalement le contenu des pseudos élements :before et :after#@#CSS
Retour à la ligne dans une tooltip HTML#@#HTML
Android Studio - Logcat n\'affiche plus rien#@#Android
Regex - Supprimer les espaces entre les balises HTML#@#PHP
PostGreSQL - Supprimer des doublons#@#PostgreSQL
PostGreSQL - Rechercher une valeur dans toutes les tables#@#PostgreSQL
Ruby on Rails - Forcer une migration#@#Ruby on Rails
Ruby on Rails - Vider le cache#@#Ruby on Rails
React - "create-react-app command not found"#@#React
Changer le mot de passe de MySQL#@#MySQL
Facebook - Programme de débug du partage#@#Facebook
Facebook API - Débugger les tokens d\'accès#@#Facebook
Installer React Native sur Windows sans Android Studio#@#React,Facebook
React Native Error: Unable to resolve module `./index`#@#Facebook,React
Télécharger les outils Java sans compte utilisateur#@#Java
React Native : javax.net.ssl.SSLHandshakeException#@#Facebook,React
React Native - Quelques liens utiles#@#Facebook,React
Générer des icônes pour une application mobile#@#Application mobile
LibreOffice - Supprimer toutes les images d\'un document#@#LibreOffice,Visual Basic
Linux - Supprimer un dossier et tous ses fichiers#@#Linux
Ruby - Décoder une chaîne en base 64 et écrire le résultat dans un fichier#@#Ruby
Arnaque au chantage et site web prétendument piraté#@#Hacking
Supprimer un fichier d\'un référentiel Git sans le supprimer du système de fichiers local#@#Github
MySQL - Récupérer toutes les clés étrangères d\'une colonne#@#MySQL
Docker - Supprimer les images inutilisées#@#Docker
Démarrer et arrêter PostGreSQL en ligne de commande#@#PostgreSQL
JS - Cacher tous les éléments d\'une classe spécifique#@#JavaScript
"error:0308010C:digital envelope routines::unsupported"#@#JavaScript,NodeJS
PHP - à quoi sert le symbole "??"#@#PHP
PHP - Différences en require_once , include_once, require et include#@#PHP
JS - Remplacer toutes les occurrences d\'une chaîne#@#JavaScript
Supprimer les valeurs en double d\'un tableau en JS#@#JavaScript
PHP - Récupérer un élément aléatoirement dans un tableau#@#PHP
Supprimer les éléments vides d\'un tableau en PHP#@#PHP
Qu\'est-ce signifie l\'erreur T_PAAMAYIM_NEKUDOTAYIM en PHP ?#@#PHP
Insérer un nouvel élément dans un tableau à n\'importe quelle position en PHP#@#PHP
PHP - Regex pour valider une adresse Litecoin#@#PHP,Cryptomonnaie
PHP - Regex pour valider une adresse email#@#PHP
Détecter si une chaîne de caractères contient du HTML#@#PHP
L\'opérateur !! en Javascript#@#JavaScript
Créer un APK ou un AAB en ligne de commandes#@#Android';

		foreach(explode("\n", $currentTags) as $datas) {
			$datas = explode("#@#", $datas);
			foreach(explode(",", $datas[1]) as $t) {
				$t = preg_replace('/[^[:print:]]/', '', $t);
				$tags[] = $t;
			}
		}
		
		$tags = array_filter(array_unique($tags));
		sort($tags);

		foreach($tags as $tag) {
			$entity = $this->em->getRepository(UsefullinkTags::class)->findOneBy(["title" => $tag]);
			
			if(empty($entity))
				$entity = new UsefullinkTags();
			
			$entity->setTitle($tag);
			$this->em->persist($entity);
		}
			
		$this->em->flush();
		
		foreach(explode("\n", $currentTags) as $datas) {
			$datas = explode("#@#", $datas);
			
			$entity = $this->em->getRepository(Usefullink::class)->findOneBy(["title" => $datas[0]]);
			
			if(empty($entity))
				continue;
			
			foreach(explode(",", $datas[1]) as $t) {
				$t = preg_replace('/[^[:print:]]/', '', $t);

				if(empty($t))
					continue;

				$tag = $this->em->getRepository(UsefullinkTags::class)->findOneBy(["title" => $t]);
				
				if(empty($tag))
					dd($t);
				$entity->addUsefullinkTag($tag);
			}
			
			$this->em->persist($entity);
		}
		
		$this->em->flush();
		
		$entities = $this->em->getRepository(Usefullink::class)->findAll();
		
		foreach($entities as $entity) {
			if(!empty($entity->getUsefullinkTags()))
				continue;

			foreach(json_decode($entity->getTags()) as $tag) {
				$tag = $this->em->getRepository(UsefullinkTags::class)->findOneBy(["title" => $tag->value]);
				$entity->addUsefullinkTag($tag);
			}
			$this->em->persist($entity);
		}
		
		$this->em->flush();

        return 0;
    }
}
