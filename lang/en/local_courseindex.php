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

$string['courseindex:browse'] = 'Can browse courses';
$string['courseindex:manage'] = 'Can manage course index';
$string['courseindex:seecourseattributes'] = 'Can see course enrol and other attributes';

// Privacy.
$string['privacy:metadata'] = "The Course Index local plugin does not store any data belonging to users";

// LP Explorer.
$string['alllevels'] = 'All levels';
$string['allowguests'] = 'Allow guest access';
$string['allstates'] = 'All publishing states';
$string['alltargets'] = 'All targets';
$string['alltopics'] = 'All topics';
$string['backtoroot'] = 'Back to root';
$string['browse'] = 'Browse';
$string['browsealltree'] = 'Browse all catalog';
$string['browseup'] = 'Browse up one level';
$string['bycategory'] = 'By category';
$string['bykeyword'] = 'By keywords';
$string['byspecialcriteria'] = 'Special criteria';
$string['config'] = 'Maximume depth in navigation tree';
$string['configclassificationconstrainttable'] = 'Constraint table';
$string['configclassificationtypetable'] = 'Table for classifier types';
$string['configclassificationvaluetable'] = 'Table for classification values';
$string['configclassificationvaluetypekey'] = 'Type key name in value table';
$string['configcoursemetadatacoursekey'] = 'Course key name in metadata binder';
$string['configcoursemetadatatable'] = 'Table for metadata binding';
$string['configcoursemetadatavaluekey'] = 'Value key name in metadata binder';
$string['configenableexplorer'] = 'Enable explorer';
$string['configfeatures'] = 'Features';
$string['configgraphics'] = 'Graphic resources';
$string['configmaxnavigationdepth'] = 'Max depth';
$string['configmaxnavigationdepth'] = 'Max nav depth';
$string['configmetadatabinding'] = 'Metadata schema binding';
$string['configopenindex'] = 'Public catalog';
$string['configrendererimages'] = 'Images for renderers';
$string['configlayoutmodel'] = 'Layout model';
$string['configlayoutmodel_desc'] = 'Choose a layout model for the browser page.';
$string['configeffectopacity'] = "Opacity effect";
$string['configeffecthalo'] = "Halo effect";
$string['configtrimmode'] = 'Trim mode';
$string['configtrimmode_desc'] = 'The algorithm used to trim texts';
$string['configtrimlength1'] = 'Trim length 1';
$string['configtrimlength1_desc'] = 'Trim length units for short strings';
$string['configtrimlength2'] = 'Trim length 2';
$string['configtrimlength2_desc'] = 'Trim length units for descriptions or summaries texts (note that trim do remove all tags)';
$string['courseindex'] = 'Course catalog';
$string['currentcategory'] = 'Category';
$string['description'] = 'description';
$string['explore'] = 'Detailed search';
$string['enrolme'] = 'Enrol me';
$string['purchase'] = 'Purchase';
$string['gofreesearch'] = 'Search';
$string['gosearch'] = 'Search';
$string['gospecialsearch'] = 'Search';
$string['goto'] = 'Browse to course';
$string['gotometadataadmin'] = 'Goto metadata administration';
$string['guestallowed'] = 'This course allows guest entry';
$string['quicktextsearch'] = 'Quick search';
$string['hiddencourses'] = ' <span class=\"smalltext\">(hidden : $a)</span>';
$string['information'] = 'information';
$string['lpcatalog'] = 'Search in the Learning Offer ';
$string['lpsearch'] = 'Course Search';
$string['lpsincategory'] = 'Training courses in this category';
$string['lpstatus'] = 'By learning path status';
$string['maxnavigationdepth'] = 'Max depth';
$string['multipleresultsadvice'] = 'Note that due to multiclassing possibilities, and depending on search method used, the same course entry may appear in several result categories.';
$string['needspassword'] = 'This course needs a password';
$string['nocourses'] = 'No courses in category';
$string['nosubcats'] = 'No subcategories';
$string['novisiblecourses'] = 'No visible courses with this search';
$string['novisiblecoursesinsubtree'] = 'No course available in this section';
$string['novisiblecoursesinsubtree'] = 'No visible courses in subtree';
$string['openenrol'] = 'this course is free enrol (unless a password is set)';
$string['pluginname'] = 'Course index';
$string['reload'] = 'Reload';
$string['requireskey'] = 'Requires key';
$string['results'] = 'Search Results';
$string['root'] = 'Catalog root';
$string['searchin'] = 'Search in';
$string['searchintree'] = 'Search in tree';
$string['searchtext'] = 'Search text';
$string['startdate'] = 'Start date';
$string['subcatsincat'] = 'Subcategories';
$string['targets'] = 'Targets';
$string['title'] = 'title';
$string['topics'] = 'Topics';
$string['standard'] = 'Standard layout';
$string['magistere'] = 'Magistere layout';
$string['domains'] = 'Domains';
$string['notrim'] = "No trim";
$string['trimchars'] = "Trim on chars";
$string['trimwords'] = "Trim on words";
$string['entries'] = " results";
$string['readmore'] = "Read more";

$string['configenableexplorer_desc'] = 'If not set, the link to the course explorer (advanced search engine) will be hidden.';

$string['configmetadatabinding_desc'] = '
<p>The course indexer relies on a capability to index courses with some metadata and classifiers. The course index model uses 4
tables to achieve this feature, and allows binding those tables from any implementation the integrator would need. The default
binding uses the Customlabel module and its inbound classifier tableset. But the ocurse index might bind to any other model that
respects following defs:</p>
<ul>
<li>There is a table to store the metadata domain values</li>
<li>Metadata values are typed. A table exists to store the metadata types to which values refer.</li>
<li>Metadata types are combined using a constraint table, that tells valid values combinations</li>
<li>A table exists that binds a course to a metadata value (tagging)</li>
</ul>
';

$string['configopenindex_desc'] = 'If enabled, any unconnected user wil be able to browse';

$string['configmaxnavigationdepth_desc'] = 'The max number of levels that will be visible at same time in browser';

$string['configcoursemetadatatable_desc'] = 'This table binds relation between a course record and any metadata pointed by an id.
The metadata should reside in the following metadata value table.';

$string['configcoursemetadatacoursekey_desc'] = 'This is the name of the field that serves as course foreign key in the metadata table.
The content of this field should be a valid COURSE id.';

$string['configcoursemetadatavaluekey_desc'] = 'This is the name of the field that serves as data value foreign key in the metadata table.';

$string['configclassificationvaluetable_desc'] = 'This is the table where to find the metadata values';

$string['configclassificationvaluetypekey_desc'] = 'This is the name of the field that serves as datatype foreign key to qualify the value';

$string['configclassificationtypetable_desc'] = 'A classifier type holds a set of values in the value table.';

$string['configclassificationconstrainttable_desc'] = 'This table holds the constraints between the different types involved into
the classification.';

$string['configopenindex_desc'] = 'If enabled, catalog browsing or exploring requires no login. Course accessibility will be tested
on "guest" credentials';

$string['configrendererimages_desc'] = 'Use this file area to store alternative graphic resources used by this component. ';
