<?php
require_once '../includes/Auth.php';
require_once '../includes/Storage.php';

if (!Auth::isAdmin()) {
    http_response_code(403);
    die('Forbidden');
}

$orders = Storage::read('orders');
$clients = Storage::read('clients');
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    die('Требуется ID заказа');
}

$order = array_values(array_filter($orders, fn($o) => $o['id'] === $orderId))[0] ?? null;
if (!$order) {
    die('Заказ не найден');
}

$client = array_values(array_filter($clients, fn($c) => $c['id'] === $order['client_id']))[0] ?? null;

header('Content-Type: text/xml');
header('Content-Disposition: attachment; filename="order_' . $orderId . '.xml"');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<CommerceInformation xmlns="urn:1C.ru:commerceml_2" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" Version="2.04">
    <Document>
        <Id><?php echo $order['id']; ?></Id>
        <Number><?php echo $order['id']; ?></Number>
        <Date><?php echo date('Y-m-d', strtotime($order['date'])); ?></Date>
        <Operation>Order</Operation>
        <Role>Seller</Role>
        <Currency>RUB</Currency>
        <Total><?php echo $order['total']; ?></Total>
        <Counterparties>
            <Counterparty>
                <Id><?php echo $order['client_id']; ?></Id>
                <Name><?php echo htmlspecialchars($order['client_name']); ?></Name>
                <Role>Buyer</Role>
                <?php if ($client): ?>
                <Details><?php echo htmlspecialchars($client['details']); ?></Details>
                <?php endif; ?>
            </Counterparty>
        </Counterparties>
        <Time><?php echo date('H:i:s', strtotime($order['date'])); ?></Time>
        <Goods>
            <?php foreach ($order['items'] as $item): ?>
            <Good>
                <Id><?php echo $item['id']; ?></Id>
                <Name><?php echo htmlspecialchars($item['name']); ?></Name>
                <Price><?php echo $item['price']; ?></Price>
                <Quantity><?php echo $item['quantity']; ?></Quantity>
                <Sum><?php echo $item['price'] * $item['quantity']; ?></Sum>
            </Good>
            <?php endforeach; ?>
        </Goods>
    </Document>
</CommerceInformation>
