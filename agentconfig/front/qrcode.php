<?php

/**
 * Agent Config plugin: renders the QR code (raw base64(JSON) payload).
 */

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use GlpiPlugin\Agentconfig\Settings;
use Symfony\Component\HttpFoundation\Response;

Session::checkRight(Settings::RIGHT, READ);

$overrides = [];

if (isset($_GET['itemtype'], $_GET['id']) && (int) $_GET['id'] > 0) {
    $itemtype = (string) $_GET['itemtype'];
    if (in_array($itemtype, [Computer::class, Phone::class], true) && class_exists($itemtype)) {
        $item = new $itemtype();
        if ($item->getFromDB((int) $_GET['id'])) {
            $overrides['ASSET_ITEMTYPE'] = $itemtype;
            $overrides['ASSET_SERIAL']    = (string) ($item->fields['serial'] ?? '');
            $overrides['ASSET_NAME']      = (string) ($item->fields['name'] ?? '');
        }
    }
}

$data = Settings::encodedPayload($overrides);

try {
    $qrcode = QrCode::create($data)
        ->setSize(300)
        ->setMargin(10);
    $png = (new PngWriter())->write($qrcode)->getString();
} catch (Throwable $e) {
    Toolbox::logInFile('php-errors', '[agentconfig] QR code generation failed: ' . $e->getMessage() . "\n");

    return new Response('QR code generation failed', 500, ['Content-Type' => 'text/plain']);
}

$headers = ['Content-Type' => 'image/png'];
if (isset($_GET['download'])) {
    $headers['Content-Disposition'] = 'attachment; filename="agentconfig-qrcode.png"';
} else {
    $headers['Content-Disposition'] = 'inline; filename="agentconfig-qrcode.png"';
}

return new Response($png, 200, $headers);
