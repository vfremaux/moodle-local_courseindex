<?php

$string['courseindex:browse'] = 'Peut consulter l\'offre';
$string['courseindex:manage'] = 'Peut gérer l\'index';

// LP Explorer
$string['courseindexconfigmaxnavigationdepth'] = 'Rechercher dans l\'offre de formation';
$string['alllevels'] = 'Tous niveaux';
$string['alltargets'] = 'tous publics';
$string['alltopics'] = 'tous thèmes';
$string['browsealltree'] = 'Afficher tout le catalogue';
$string['bycategory'] = 'Par catégorie';
$string['bykeyword'] = 'Par mots clefs';
$string['byspecialcriteria'] = 'Autres critères';
$string['description'] = 'description';
$string['explore'] = 'Explorateur de formations';
$string['gofreesearch'] = 'Chercher';
$string['gosearch'] = 'Chercher';
$string['gospecialsearch'] = 'Chercher';
$string['guestallowed'] = 'Ce cours est accessible aux invités';
$string['information'] = 'information';
$string['lpsearch'] = 'Moteur de recherche de formations';
$string['lpstatus'] = 'Par état de publication';
$string['pluginname'] = 'Catalogue de cours';
$string['multipleresultsadvice'] = 'Notez que pour certaines méthodes de recherche, le classement multiple des formations font que le même résultat peut apparaître plusieurs fois dans des catégories différentes.';
$string['needspassword'] = 'Ce cours nécessite un mot de passe';
$string['openenrol'] = 'L\'inscription à ce cours est libre (sauf si un mot de passe est signalé)';
$string['novisiblecoursesinsubtree'] = 'Aucune formation dans cette section';
$string['results'] = 'Résultats de recherche';
$string['searchin'] = 'Chercher dans';
$string['searchintree'] = 'Rechercher des formations';
$string['searchtext'] = 'Mot ou expression';
$string['targets'] = 'Publics';
$string['title'] = 'titre';
$string['topics'] = 'Thèmes';
$string['root'] = 'Racine du catalogue';
$string['lpsincategory'] = 'Formations dans cette catégorie';
$string['nocourses'] = 'Aucune formation dans cette catégorie';
$string['backtoroot'] = 'Revenir à la racine';
$string['reload'] = 'Recharger';
$string['subcatsincat'] = 'Sous-catégories';
$string['browseup'] = 'Remonter d\'un niveau';
$string['currentcategory'] = 'Catégorie';
$string['nosubcats'] = 'Pas de sous-catégories';
$string['hiddencourses'] = ' <span class=\"smalltext\">(caché(s) : $a)</span>';

$string['configopenindex'] = 'Catalogue public';
$string['configopenindex_desc'] = 'Si autorisé, au public, les personnes non connectées peuvent voir l\'offre de cours';
$string['configmaxnavigationdepth'] = 'Profondeur maximum de navigation';
$string['configmaxnavigationdepth_desc'] = 'Le nombre maximm de niveaux affichés à l\'écran';
$string['configclassificationdisplayemptylevel0'] = 'Niveau 0 visible lorsque vide';
$string['configclassificationdisplayemptylevel0_desc'] = '';
$string['configclassificationdisplayemptylevel1'] = 'Niveau 1 visible lorsque vide';
$string['configclassificationdisplayemptylevel1_desc'] = '';
$string['configclassificationdisplayemptylevel2'] = 'Niveau 2 visible lorsque vide';
$string['configclassificationdisplayemptylevel2_desc'] = '';
$string['configmetadatabinding'] = 'Schéma de métadonnées';
$string['configmetadatabinding_desc'] = '
<p>L\'indexation des cours et formations s\'appuie sur la gestion de métadonnées additionnelles de classification. Ce modèle peut être fourni par plusieurs composants potentiels de Moodle,
voire des tables extérieures à Moodle.
L\'indexation de cours nécessite 4 tables pour faire fonctionner le cataloque, lesquelles peuvent être décrites par ce schéma. Le raccordement par défaut
utilise les tables de métadonnées du plugin "Elément de cours". Ces métadonnées pourraient résider dans tout modèle qui respecte les définitions suivantes :</p>
<ul>
<li>Une table stocke les valeurs de chaque critère d\'indexation</li>
<li>Les valeurs d\'indexation appartiennent à un critère. Une table existe pour définir le critère.</li>
<li>Les différents critères sont combinés par une table de contraintes, laquelle définit quelle combinaison de valeurs est valide.</li>
<li>Une table stocke le tagging des cours par les valeurs de critère</li>
</ul>
';
$string['configcoursemetadatatable'] = 'Table pour le tagging des cours';
$string['configcoursemetadatatable_desc'] = 'Cette table gère la relaiton entre un identifiant de cours et une valeur particulière de critère.';
$string['configcoursemetadatacoursekey'] = 'Clef du cours dans la table de tagging';
$string['configcoursemetadatacoursekey_desc'] = 'C\'est le nom du champ SQL qui stocke la référence à l\'ID (numérique) de cours.';
$string['configcoursemetadatavaluekey'] = 'Clef de la valeur de critère';
$string['configcoursemetadatavaluekey_desc'] = 'C\'est le nom du champ SQL de la table de tagging qui pointe un ID (numérique) de valeur de critère.';
$string['configclassificationvaluetable'] = 'Table des valeurs de critères';
$string['configclassificationvaluetable_desc'] = 'C\'est la table dans laquelle on stocke les valeurs de critères pour l\'indexation des cours';
$string['configclassificationvaluetypekey'] = 'Clef du critère';
$string['configclassificationvaluetypekey_desc'] = 'C\'est le champ SQL de la table de valeurs qui associe la valeur à une définition de critère.';
$string['configclassificationtypetable'] = 'Table des critères';
$string['configclassificationtypetable_desc'] = 'Une définition associée à un champ de valeurs d\'indexation. Cette table doit avoir les champs "type", "id", et les types utilisés par le catalogue sont "coursefilter" et "category".';
$string['configclassificationconstrainttable'] = 'Table des contraintes';
$string['configclassificationconstrainttable_desc'] = 'La table des contraintes verrouille les combinaisons valides de valeurs entre les différents critères. 
Ces associations permettent de dessiner les "branches" de classement pertinente en éliminant les combinaisons qui ne le sont pas.';
