<?php
$file = 'app/Modules/Orders/Controllers/OrderController.php';
$content = file_get_contents($file);

$target = '            });
        }
        }

        if ($request->filled(\'product\')) {';

$replacement = '            });
        }

        if ($request->filled(\'product\')) {';

$content = str_replace($target, $replacement, $content);
file_put_contents($file, $content);
echo "Fixed extra braces.\n";
