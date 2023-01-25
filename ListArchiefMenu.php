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

// A function that recursively traverses the tree structure of the menu
// and creates an unlinked list (ul) for all the parent nodes and a list item (li)
// for the leave nodes that are of the component "com_oziogallery3"
function DisplayChildren($id)
{
    $c = array();
    $children = JFactory::getApplication()->getMenu()->getItems("parent_id", $id, false);
    if (count($children) > 0) {
        echo "<ul>";
        foreach ($children as $child) {
            if ($child->component == "com_oziogallery3") {
                echo "<li><a target='_parent' href=\"/index.php/" . $child->route . "\">" . $child->title . "</a></li>";
            } else {
                echo "<li>" . $child->title . "</li>";
                DisplayChildren($child->id);
            }
        }
        echo "</ul>";
    }
}

DisplayChildren("350");
