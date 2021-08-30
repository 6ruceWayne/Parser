<?php
require_once 'simple_dom/simple_html_dom.php';

$myfile = fopen("mydata.csv", "a");
fputcsv($myfile, array('item name', 'Brand', 'Product Code', 'Availability', 'price', 'link', 'picture_name'));

$logs = fopen("logs.csv", "a");
fputcsv($logs, array('link from', 'link', 'exception text'));

$dir = "images";
if (is_dir($dir) === false) {
    mkdir($dir);
}
// Link was removed for security reasons
$url = "link";

$sections = array(930, 880, 960, 950, 969, 980);

parseSections($url, $sections);

fclose($myfile);
fclose($logs);

function parseSections($url, $sections)
{
    foreach ($sections as $section) {
        $link = $url . $section . '&page=';
        parsePages($link);
    }
    echo 'I am done';
}

function parsePages($link)
{
    $index = 1;
    $html = file_get_html($link . $index);
    while ($html->find('div[class=product-thumb]')) {
        $links = $html->find('h4 a');
        foreach ($links as $element) {
            parseItem(htmlspecialchars_decode($element->href), $link);
        }
        $index++;
        $html = file_get_html($link . $index);
    }
}

function parseItem($link, $link_from)
{
    try {
        $html = file_get_html($link);
        $list = $html->find('ul[class=list-unstyled]', 6)->find('li');
        $name = $html->find('h1', 0)->plaintext;
        $brand = ltrim($list[0]->plaintext, 'Brand: ');
        $code = ltrim($list[1]->plaintext, 'Product Code: ');
        $availability = ltrim($list[2]->plaintext, 'Availability: ');
        $price = $html->find('span[class=old-prices]', 0)->plaintext;
        $url_to_image = $html->find('div[class=col-sm-6] ul', 0);
        $url_to_image = $url_to_image->find('li a', 0)->href;
        saveItem($name, $brand, $code, $availability, $price, $link, $url_to_image, $link_from);
    } catch (Error $e) {
        global $logs;
        fputcsv($logs, array($link_from, $e->getMessage(), $link));
    } catch (Exception $e) {
        global $logs;
        fputcsv($logs, array($link_from, $e->getMessage(), $link));
    }
}

function saveItem($name, $brand, $code, $availability, $price, $link, $picture, $link_from)
{
    global $myfile;

    fputcsv($myfile, array($name, $brand, $code, $availability, $price, $link, $picture));

    $my_save_dir = 'images/';
    $filename = $code . '.jpg';
    $complete_save_loc = $my_save_dir . $filename;
    file_put_contents($complete_save_loc, file_get_contents($picture));

    echo "done: " . $link . PHP_EOL;
}
