<?php

define('_JEXEC', 1);
define('JPATH_BASE', realpath(dirname(__FILE__) . '/..'));

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
$mainApplication = JFactory::getApplication('site');
$mainApplication->initialise();
$session = JFactory::getSession();


?>
<link href="/templates/shaper_helix3/css/bootstrap.min.css" rel="stylesheet" type="text/css">

	<link href="/index.php?format=feed&amp;type=rss" rel="alternate" type="application/rss+xml" title="RSS 2.0" />
	<link href="/index.php?format=feed&amp;type=atom" rel="alternate" type="application/atom+xml" title="Atom 1.0" />
	<link href="/images/2020/B51/skc_logo-removebg-preview.png" rel="shortcut icon" type="image/vnd.microsoft.icon" />
	<link href="https://www.skcvolleybal.nl/index.php/component/search/?Itemid=101&amp;format=opensearch" rel="search" title="Zoeken SKC Studentenvolleybal" type="application/opensearchdescription+xml" />
	<link href="/plugins/system/jce/css/content.css?badb4208be409b1335b815dde676300e" rel="stylesheet" type="text/css" />
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,300italic,regular,italic,600,600italic,700,700italic,800,800italic&amp;subset=greek-ext" rel="stylesheet" type="text/css" />
	<link href="/templates/shaper_helix3/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="/templates/shaper_helix3/css/joomla-fontawesome.min.css" rel="stylesheet" type="text/css" />
	<link href="/templates/shaper_helix3/css/font-awesome-v4-shims.min.css" rel="stylesheet" type="text/css" />
	<link href="/templates/shaper_helix3/css/template.css" rel="stylesheet" type="text/css" />
	<link href="/templates/shaper_helix3/css/presets/preset3.css" rel="stylesheet" type="text/css" class="preset" />
	<link href="/templates/shaper_helix3/css/frontend-edit.css" rel="stylesheet" type="text/css" />
	<link href="https://www.skcvolleybal.nl/components/com_comprofiler/plugin/templates/default/bootstrap.css?v=3e656ed2160119fb" rel="stylesheet" type="text/css" />
	<link href="https://www.skcvolleybal.nl/components/com_comprofiler/plugin/templates/default/fontawesome.css?v=3e656ed2160119fb" rel="stylesheet" type="text/css" />
	<link href="https://www.skcvolleybal.nl/components/com_comprofiler/plugin/templates/default/template.css?v=3e656ed2160119fb" rel="stylesheet" type="text/css" />
	<link href="/components/com_jevents/views/flat/assets/css/modstyle.css?v=3.6.39" rel="stylesheet" type="text/css" />
	<link href="/media/com_jevents/css/bootstrap.css" rel="stylesheet" type="text/css" />
	<link href="/media/com_jevents/css/bootstrap-responsive.css" rel="stylesheet" type="text/css" />
	<link href="/components/com_jevents/assets/css/jevcustom.css?v=3.6.39" rel="stylesheet" type="text/css" />

<h2>SKC Foto albums</h2>


<?php
// A function that recursively traverses the tree structure of the menu
// and creates an unlinked list (ul) for all the parent nodes and a list item (li)
// for the leave nodes that are of the component "com_oziogallery3"
function DisplayChildren($id)
{
    $c = array();
    $children = JFactory::getApplication()->getMenu()->getItems("parent_id", $id, false);
    if (count($children) > 0) {
        echo "<ul>";
        rsort($children);
        foreach ($children as $child) {
            if ($child->component == "com_oziogallery3") {
                // Dit zijn de links naar de albums
                echo "<li style='font-family: sans-serif;'>
                <a target='_parent' href=\"/index.php/" . $child->route . "\">" . $child->title . "</a></li>";
            } else {
                // Dit zijn de jaartallen
                echo "<li style='font-family: sans-serif;'>" . $child->title . "</li>";
                DisplayChildren($child->id);
            }
        }
        echo "</ul>";
    }
}

DisplayChildren("261");
