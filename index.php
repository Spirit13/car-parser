<?php
// Assuming you installed from Composer:
require "vendor/autoload.php";
use PHPHtmlParser\Dom;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    $mail->CharSet = 'UTF-8';
    //Server settings
    $mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'smtp.yandex.ru';  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = 'mould@tut.by';                 // SMTP username
    $mail->Password = 'TheSpirit610!';                           // SMTP password
    $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 465;                                    // TCP port to connect to

    //Recipients
    $mail->addAddress('spirit.gavrilov@gmail.com', 'Anton Gavrilov');     // Add a recipient

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
} catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}

$urls = [
    'av-cars' => [
        'items' => '.listing-item',
        'pagination' => '.pages-numbers li a',
        'cost' => '.listing-item-price strong',
        'year' => '.listing-item-price span',
        'link' => '.listing-item-title a',
        'location' => '.listing-item-location',
        'image' => '.listing-item-image-in img',
        'url' => 'https://cars.av.by/search?brand_id%5B0%5D=1216&model_id%5B0%5D=1232&brand_id%5B1%5D=1216&model_id%5B1%5D=2672&brand_id%5B2%5D=834&model_id%5B2%5D=865&currency=USD&price_from=500&price_to=2000&engine_type=2&sort=cost&order=asc'
    ],
    'av-truck-vw' => [
        'items' => '.listing-item',
        'pagination' => '.pages-numbers li a',
        'cost' => '.listing-item-price strong',
        'year' => '.listing-item-price span',
        'link' => '.listing-item-title a',
        'location' => '.listing-item-location',
        'image' => '.listing-item-image img',
        'url' => 'https://truck.av.by/search?module=truck&type_id=2&body_id=&brand_id=5395&model_id=&year_from=&year_to=&currency=USD&price_from=500&price_to=2000&engine_volume_min=&engine_volume_max=&engine_id=&country_id=&mileage_min=&mileage_max='
    ],
    'av-truck-mb' => [
        'items' => '.listing-item',
        'pagination' => '.pages-numbers li a',
        'cost' => '.listing-item-price strong',
        'year' => '.listing-item-price span',
        'link' => '.listing-item-title a',
        'location' => '.listing-item-location',
        'image' => '.listing-item-image img',
        'url' => 'https://truck.av.by/search?module=truck&type_id=2&body_id=&brand_id=683&model_id=&year_from=&year_to=&currency=USD&price_from=500&price_to=2000&engine_volume_min=&engine_volume_max=&engine_id=&country_id=&mileage_min=&mileage_max='
    ],
    'av-truck-mits' => [
        'items' => '.listing-item',
        'pagination' => '.pages-numbers li a',
        'cost' => '.listing-item-price strong',
        'year' => '.listing-item-price span',
        'link' => '.listing-item-title a',
        'location' => '.listing-item-location',
        'image' => '.listing-item-image img',
        'url' => 'https://truck.av.by/search?module=truck&type_id=2&body_id=&brand_id=834&model_id=&year_from=&year_to=&currency=USD&price_from=500&price_to=2000&engine_volume_min=&engine_volume_max=&engine_id=&country_id=&mileage_min=&mileage_max='
    ],
    'av-truck-ford' => [
        'items' => '.listing-item',
        'pagination' => '.pages-numbers li a',
        'cost' => '.listing-item-price strong',
        'year' => '.listing-item-price span',
        'link' => '.listing-item-title a',
        'location' => '.listing-item-location',
        'image' => '.listing-item-image img',
        'url' => 'https://truck.av.by/search?module=truck&type_id=2&body_id=&brand_id=330&model_id=&year_from=&year_to=&currency=USD&price_from=500&price_to=2000&engine_volume_min=&engine_volume_max=&engine_id=&country_id=&mileage_min=&mileage_max='
    ]
];


$dom = new Dom;

foreach ($urls as $site => $urlInfo) {
    $cars = [];
    $dbCars = [];
    $carsExportNew = [];

    $fileName = 'cars-' . $site .'.json';
    if (is_file($fileName)) {
        $dbCars = json_decode(file_get_contents($fileName), true);
    }

    $dom->loadFromUrl($urlInfo['url']);
    $cars[] = $dom->find($urlInfo['items']);
    $urls[$site]['total'] = count($cars[0]);
    $urls[$site]['new'] = 0;

    $pages = $dom->find($urlInfo['pagination']);

    if (count($pages)) {
        unset($pages[0]);
    }

    if (count($pages)) {
        foreach ($pages as $page) {
            $pageUrl = $page->getAttribute('href');
            $dom->loadFromUrl($pageUrl);
            $cars[] = $pageCars = $dom->find($urlInfo['items']);
            $urls[$site]['total'] += count($pageCars);
        }
    }
    $saveToDb = true;
    foreach ($cars as $carPage) {
        foreach ($carPage as $car) {
            $linkAttribute = $car->find($urlInfo['link']);
            if (count($linkAttribute)) {
                $link = $linkAttribute[0]->getAttribute('href');
                $name = trim($linkAttribute[0]->innerHtml);

                preg_match("/\/(\d+)$/", $link, $matches);
                $carId = $matches[1];

                $costAttr = $car->find($urlInfo['cost']);
                $cost = 'und';
                if ($costAttr[0]->firstChild()) {
                    $cost = str_replace(' ', '', $costAttr[0]->firstChild()->text());
                }

                $locationAttr = $car->find($urlInfo['location']);
                $location = 'und';
                if (count($locationAttr)) {
                    $location = $locationAttr[0]->innerHtml;
                }

                $yearAttr = $car->find($urlInfo['year']);
                $year = 'und';
                if (count($year)) {
                    $year = $yearAttr[0]->innerHtml;
                }

                $imageAttr = $car->find($urlInfo['image']);
                if (count($imageAttr)) {
                    $image = $imageAttr[0]->getAttribute('src');
                }

                $costChange = 'No change';
                if (!isset($dbCars[$carId]) || $dbCars[$carId]['cost'] != $cost) {
                    $costChange = 'new';
                    if (isset($dbCars[$carId])) {
                        if ($dbCars[$carId]['cost'] < $cost) {
                            $costChange = 'down';
                        } elseif ($dbCars[$carId]['cost'] > $cost) {
                            $costChange = 'up';
                        }
                    }

                    $carsExportNew[$carId] = [
                        'link' => $link,
                        'name' => $name,
                        'cost' => $cost,
                        'costChange' => $costChange,
                        'year' => $year,
                        'image' => $image,
                        'location' => $location
                    ];
                    $urls[$site]['new']++;
                }

                $dbCars[$carId] = [
                    'link' => $link,
                    'name' => $name,
                    'cost' => $cost,
                    'costChange' => $costChange,
                    'year' => $year,
                    'image' => $image,
                    'location' => $location
                ];
            }
        }
    }

    if (count($carsExportNew)) {
        $body = '<html><meta http-equiv="Content-type" content="text/html; charset=utf-8" />';
        $body .= '<body>';

        $body .= '<table width="100%">';
        foreach ($carsExportNew as $carId => $newCar) {
            $body .= '<tr>';
            $body .= '<td>' . $carId . '</td>';
            $body .= '<td><a href="' . $newCar['link'] . '">' . $newCar['name'] . '</a></td>';
            $body .= '<td>' . $newCar['year'] . '</td>';
            $body .= '<td>' . $newCar['cost'] . '</td>';
            $body .= '<td>' . $newCar['costChange'] . '</td>';
            $body .= '<td>' . $newCar['location'] . '</td>';
            $body .= '</tr>';
        }
        $body .=' </table>' .

        $body .='</body></html>';

        try {
            $mail->setFrom('mould@tut.by', 'Cars - ' . $site);
            $mail->Subject = 'New ' . $urls[$site]['new'] . '/' . $urls[$site]['total'] . '- ' . $site;

            $mail->Body    = $body;
            $mail->AltBody = $body;

            $mail->send();
        } catch (Exception $e) {
            $saveToDb = false;
        }
    }

    if ($saveToDb) {
        file_put_contents($fileName, json_encode($dbCars));
    }
}