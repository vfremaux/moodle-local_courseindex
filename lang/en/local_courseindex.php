<?php

$string['courseindex:browse'] = 'Can browse courses';
$string['courseindex:manage'] = 'Can manage course index';

// LP Explorer
$string['lpcatalog'] = 'Search in the Learning Offer ';
$string['alllevels'] = 'All levels';
$string['allstates'] = 'All publishing states';
$string['alltargets'] = 'All targets';
$string['alltopics'] = 'All topics';
$string['browse'] = 'Browse';
$string['browsealltree'] = 'Browse all catalog';
$string['bycategory'] = 'By category';
$string['bykeyword'] = 'By keywords';
$string['byspecialcriteria'] = 'Special criteria';
$string['configopenindex'] = 'If enabled, catalog browsing or exploring requires no login. Course accessibility will be tested on "guest" credentials';
$string['courseindex'] = 'Courses';
$string['description'] = 'description';
$string['explore'] = 'Course Explorer';
$string['gofreesearch'] = 'Search';
$string['gosearch'] = 'Search';
$string['gospecialsearch'] = 'Search';
$string['guestallowed'] = 'This course allows guest entry';
$string['information'] = 'information';
$string['lpsearch'] = 'Course Search';
$string['lpstatus'] = 'By learning path status';
$string['multipleresultsadvice'] = 'Note that due to multiclassing possibilities, and depending on search method used, the same course entry may appear in several result categories.';
$string['needspassword'] = 'This course needs a password';
$string['novisiblecoursesinsubtree'] = 'No course available in this section';
$string['pluginname'] = 'Course index';
$string['openenrol'] = 'this course is free enrol (unless a password is set)';
$string['configopenindex'] = 'Public catalog';
$string['configopenindex_desc'] = 'If enabled, any unconnected user wil be able to browse';
$string['configmaxnavigationdepth'] = 'Max nav depth';
$string['configmaxnavigationdepth_desc'] = 'The max number of levels that will be visible at same time in browser';
$string['configclassificationdisplayemptylevel0'] = 'Level 0 visible when empty';
$string['configclassificationdisplayemptylevel0_desc'] = '';
$string['configclassificationdisplayemptylevel1'] = 'Level 1 visible when empty';
$string['configclassificationdisplayemptylevel1_desc'] = '';
$string['configclassificationdisplayemptylevel2'] = 'Level 2 visible when empty';
$string['configclassificationdisplayemptylevel2_desc'] = '';
$string['configmetadatabinding'] = 'Metadata schema binding';
$string['configmetadatabinding_desc'] = '
<p>The course indexer relies on a capability to index courses with some metadata and classifiers. The course index model uses 4 tables to achieve this feature, and allows binding those tables from any implementation
the integrator would need. The default binding uses the Customlabel module and its inbound classifier tableset. But the ocurse index might bind to any other model that respects following defs:</p>
<ul>
<li>There is a table to store the metadata domain values</li>
<li>Metadata values are typed. A table exists to store the metadata types to which values refer.</li>
<li>Metadata types are combined using a constraint table, that tells valid values combinations</li>
<li>A table exists that binds a course to a metadata value (tagging)</li>
</ul>
';
$string['configcoursemetadatatable'] = 'Table for metadata binding';
$string['configcoursemetadatatable_desc'] = 'This table binds relation between a course record and any metadata pointed by ab id. The metadata should reside in the following metadata value table.';
$string['configcoursemetadatacoursekey'] = 'Course key name in metadata binder';
$string['configcoursemetadatacoursekey_desc'] = 'This is the name of the field that serves as course foreign key in the metadata table. The content of this field should be a valid COURSE id.';
$string['configcoursemetadatavaluekey'] = 'Value key name in metadata binder';
$string['configcoursemetadatavaluekey_desc'] = 'This is the name of the field that serves as data value foreign key in the metadata table.';
$string['configclassificationvaluetable'] = 'Table for classification values';
$string['configclassificationvaluetable_desc'] = 'This is the table where to find the metadata values';
$string['configclassificationvaluetypekey'] = 'Type key name in value table';
$string['configclassificationvaluetypekey_desc'] = 'This is the name of the field that serves as datatype foreign key to qualify the value';
$string['configclassificationtypetable'] = 'Table for classifier types';
$string['configclassificationtypetable_desc'] = 'A classifier type holds a set of values in the value table.';
$string['configclassificationconstrainttable'] = 'Constraint table';
$string['configclassificationconstrainttable_desc'] = 'This table holds the constraints between the different types involved into the classification.';


$string['results'] = 'Search Results';
$string['searchin'] = 'Search in';
$string['searchintree'] = 'Search in tree';
$string['searchtext'] = 'Search text';
$string['targets'] = 'Targets';
$string['title'] = 'title';
$string['topics'] = 'Topics';
$string['root'] = 'Catalog root';
$string['lpsincategory'] = 'Training courses in this category';
$string['subcatsincat'] = 'Subcategories';
$string['nocourses'] = 'No courses in category';
$string['backtoroot'] = 'Back to root';
$string['reload'] = 'Reload';
$string['browseup'] = 'Browse up one level';
$string['currentcategory'] = 'Category';
$string['nosubcats'] = 'No subcategories';
$string['hiddencourses'] = ' <span class=\"smalltext\">(hidden : $a)</span>';
$string['maxnavigationdepth'] = 'Max depth';
$string['configmaxnavigationdepth'] = 'Max depth';
$string['config'] = 'Maximume depth in navigation tree';
$string['novisiblecoursesinsubtree'] = 'No visible courses in subtree';
$string['allowguests'] = 'Allow guest access';
$string['requireskey'] = 'Requires key';

