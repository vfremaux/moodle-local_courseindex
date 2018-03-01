<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_courseindex
 * @author     Valery Fremaux <valery.fremaux@club-internet.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 */

$string['courseindex:browse'] = 'Peut consulter l\'offre';
$string['courseindex:manage'] = 'Peut gérer l\'index';

// LP Explorer.
$string['alllevels'] = 'Tous niveaux';
$string['alltargets'] = 'tous publics';
$string['alltopics'] = 'tous thèmes';
$string['backtoroot'] = 'Revenir à la racine';
$string['browsealltree'] = 'Afficher tout le catalogue';
$string['browseup'] = 'Remonter d\'un niveau';
$string['bycategory'] = 'Par catégorie';
$string['bykeyword'] = 'Par mots clefs';
$string['byspecialcriteria'] = 'Autres critères';
$string['configclassificationconstrainttable'] = 'Table des contraintes';
$string['configclassificationtypetable'] = 'Table des critères';
$string['configclassificationvaluetable'] = 'Table des valeurs de critères';
$string['configclassificationvaluetypekey'] = 'Clef du critère';
$string['configcoursemetadatacoursekey'] = 'Clef du cours dans la table de tagging';
$string['configcoursemetadatatable'] = 'Table pour le tagging des cours';
$string['configcoursemetadatavaluekey'] = 'Clef de la valeur de critère';
$string['configenableexplorer'] = 'Activer la recherche';
$string['configfeatures'] = 'Comportement';
$string['configgraphics'] = 'Ressources graphiques';
$string['configmaxnavigationdepth'] = 'Profondeur maximum de navigation';
$string['configmetadatabinding'] = 'Schéma de métadonnées';
$string['configopenindex'] = 'Catalogue public';
$string['configrendererimages'] = 'Images pour les renderer';
$string['courseindex'] = 'L\'Offre de cours';
$string['courseindexconfigmaxnavigationdepth'] = 'Rechercher dans l\'offre de formation';
$string['currentcategory'] = 'Catégorie';
$string['description'] = 'description';
$string['explore'] = 'Explorateur de formations';
$string['gofreesearch'] = 'Chercher';
$string['gosearch'] = 'Chercher';
$string['gospecialsearch'] = 'Chercher';
$string['gotometadataadmin'] = 'Aller à l\'administration des métadonnées';
$string['guestallowed'] = 'Ce cours est accessible aux invités';
$string['hiddencourses'] = ' <span class=\"smalltext\">(caché(s) : $a)</span>';
$string['information'] = 'information';
$string['lpsearch'] = 'Moteur de recherche de formations';
$string['lpsincategory'] = 'Formations dans cette catégorie';
$string['lpstatus'] = 'Par état de publication';
$string['multipleresultsadvice'] = 'Notez que pour certaines méthodes de recherche, le classement multiple des formations font que le même résultat peut apparaître plusieurs fois dans des catégories différentes.';
$string['needspassword'] = 'Ce cours nécessite un mot de passe';
$string['nocourses'] = 'Aucune formation dans cette catégorie';
$string['nosubcats'] = 'Pas de sous-catégories';
$string['novisiblecourses'] = 'Aucun cours visible avec cette recherche';
$string['novisiblecoursesinsubtree'] = 'Aucune formation dans cette section';
$string['openenrol'] = 'L\'inscription à ce cours est libre (sauf si un mot de passe est signalé)';
$string['pluginname'] = 'Catalogue de cours';
$string['reload'] = 'Recharger';
$string['results'] = 'Résultats de recherche';
$string['root'] = 'Racine du catalogue';
$string['searchin'] = 'Chercher dans';
$string['searchintree'] = 'Rechercher des formations';
$string['searchtext'] = 'Mot ou expression';
$string['startdate'] = 'Date de début';
$string['subcatsincat'] = 'Sous-catégories';
$string['targets'] = 'Publics';
$string['title'] = 'titre';
$string['topics'] = 'Thèmes';

$string['configopenindex_desc'] = 'Si autorisé, au public, les personnes non connectées peuvent voir l\'offre de cours';

$string['configmaxnavigationdepth_desc'] = 'Le nombre maximm de niveaux affichés à l\'écran';

$string['configenableexplorer_desc'] = 'Si cette case n\'est pas cochée, le moteur de recherche ne sera pas publié aux utilisateurs.';

$string['configmetadatabinding_desc'] = '
<p>L\'indexation des cours et formations s\'appuie sur la gestion de métadonnées additionnelles de classification. Ce modèle peut
être fourni par plusieurs composants potentiels de Moodle,
voire des tables extérieures à Moodle.
L\'indexation de cours nécessite 4 tables pour faire fonctionner le cataloque, lesquelles peuvent être décrites par ce schéma.
Le raccordement par défaut utilise les tables de métadonnées du plugin "Elément de cours". Ces métadonnées pourraient résider
dans tout modèle qui respecte les définitions suivantes :</p>
<ul>
<li>Une table stocke les valeurs de chaque critère d\'indexation</li>
<li>Les valeurs d\'indexation appartiennent à un critère. Une table existe pour définir le critère.</li>
<li>Les différents critères sont combinés par une table de contraintes, laquelle définit quelle combinaison de valeurs est valide.</li>
<li>Une table stocke le tagging des cours par les valeurs de critère</li>
</ul>
';

$string['configclassificationconstrainttable_desc'] = 'La table des contraintes verrouille les combinaisons valides de valeurs
entre les différents critères. Ces associations permettent de dessiner les "branches" de classement pertinente en éliminant
les combinaisons qui ne le sont pas.';

$string['configcoursemetadatatable_desc'] = 'Cette table gère la relaiton entre un identifiant de cours et une valeur particulière
de critère.';

$string['configcoursemetadatacoursekey_desc'] = 'C\'est le nom du champ SQL qui stocke la référence à l\'ID (numérique) de cours.';

$string['configcoursemetadatavaluekey_desc'] = 'C\'est le nom du champ SQL de la table de tagging qui pointe un ID (numérique) de
valeur de critère.';

$string['configclassificationvaluetable_desc'] = 'C\'est la table dans laquelle on stocke les valeurs de critères pour l\'indexation
des cours';

$string['configclassificationvaluetypekey_desc'] = 'C\'est le champ SQL de la table de valeurs qui associe la valeur à une définition
de critère.';

$string['configclassificationtypetable_desc'] = 'Une définition associée à un champ de valeurs d\'indexation. Cette table doit avoir
les champs "type", "id", et les types utilisés par le catalogue sont "coursefilter" et "category".';

$string['rendererimages_desc'] = 'Toutes les images par défaut pour l\'interface de ce composant. Images attendues
"coursedefaultimage.&lt;ext&gt;". Les images peuvent être en .svg, .png, .jp ou .gif.';