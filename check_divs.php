<?php
$content = file_get_contents('resources/views/users/index.blade.php');
// Simple counting of opening and closing tags
preg_match_all('/<div\b[^>]*>/i', $content, $openDivs);
preg_match_all('/<\/div>/i', $content, $closeDivs);
echo "Divs open: " . count($openDivs[0]) . "\n";
echo "Divs close: " . count($closeDivs[0]) . "\n";

preg_match_all('/<template\b[^>]*>/i', $content, $openTemplates);
preg_match_all('/<\/template>/i', $content, $closeTemplates);
echo "Templates open: " . count($openTemplates[0]) . "\n";
echo "Templates close: " . count($closeTemplates[0]) . "\n";

// A more robust tag balancer
$tags = ['div', 'template', 'span', 'p', 'tr', 'td', 'th', 'table', 'tbody', 'thead', 'form', 'button', 'a'];
foreach ($tags as $tag) {
    preg_match_all('/<'.$tag.'\b[^>]*>/i', $content, $open);
    preg_match_all('/<\/'.$tag.'>/i', $content, $close);
    if (count($open[0]) !== count($close[0])) {
        echo "Mismatch for $tag: open=" . count($open[0]) . " close=" . count($close[0]) . "\n";
    }
}
