<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

define('_JEXEC', 1);
define('JPATH_BASE', realpath(dirname(__FILE__) . '/..'));

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
$mainApplication = JFactory::getApplication('site');
$mainApplication->initialise();
$session = JFactory::getSession();

// In order to access the dropdown menu on the phone,
// the accesible menu items will be displayed, \
// when the MijnSKC menu item is pressedd.

// Get all the children of menu item MijnSKC (id = 190)
$MijnSKC = JFactory::getApplication()->getMenu()->getItems("parent_id", "190", false);

echo "<ul>";
foreach ($MijnSKC as $mi) {
    if (substr($mi->link, 0, 10) == "index.php?") {
        $url = '/index.php/' . $mi->route;
    } else {
        $url = $mi->link;
    }

    echo "<li><a target='_parent' href=\"" . $url . "\">" . $mi->title . "</a></li>";
}
echo "</ul>";
