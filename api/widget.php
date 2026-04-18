<?php
Auth::requireRole(['admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $scriptUrl = $baseUrl . "/assets/js/widget.js";
    $cssUrl = $baseUrl . "/assets/css/style.css";
    $apiUrl = $baseUrl . "/api/index.php?module=online_booking";

    $snippet = "<!-- Dental CRM Booking Widget -->\n" .
               "<div id=\"dental-booking-widget\"></div>\n" .
               "<script src=\"$scriptUrl\"></script>\n" .
               "<script>\n" .
               "  DentalWidget.init({\n" .
               "    container: '#dental-booking-widget',\n" .
               "    apiUrl: '$apiUrl'\n" .
               "  });\n" .
               "</script>";

    echo json_encode(['snippet' => $snippet]);
}
