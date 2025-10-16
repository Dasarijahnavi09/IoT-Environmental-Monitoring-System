<?php
print_r($_GET);
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (
    isset($_GET['coll_state']) &&
    isset($_GET['light_level']) &&
    isset($_GET['power_level']) &&
    isset($_GET['light_threshold'])&&
    isset($_GET['timestamp'])
    
) {
    $str = '';
    $xmlFile = 'data.xml';

    if (file_exists($xmlFile)) {
        $str = file_get_contents($xmlFile);
    }

    if (strlen(trim($str)) == 0) {
        $str = "<?xml version='1.0' encoding='UTF-8'?>\n<records></records>";
    }

    $newData = "\n<record>" .
        "<coll_state>" . $_GET['coll_state'] . "</coll_state>" .
        "<light_level>" . $_GET['light_level'] . "</light_level>" .
        "<power_level>" . $_GET['power_level'] . "</power_level>" .
        "<light_threshold>" . $_GET['light_threshold'] . "</light_threshold>" .
        "<timestamp>" . (isset($_GET['timestamp']) ? $_GET['timestamp'] : time()) . "</timestamp>" .
        "<servertimestamp>" . time() . "</servertimestamp>" .
        "</record>\n</records>";

    $str = str_replace("</records>", $newData, $str);

    if (file_put_contents($xmlFile, $str)) {
        echo "Data saved.\n";
    } else {
        echo "Failed.\n";
    }
} else {
    echo "Missing required GET parameters.\n";
}
?>
