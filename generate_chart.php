<?php
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

if (isset($_POST['chartData'])) {
    $dompdf = new Dompdf();
    $chartData = $_POST['chartData'];

    $html = '
    <html>
    <head>
        <style>
            body { font-family: DejaVu Sans, sans-serif; }
            .content { text-align: center; }
            img { width: 100%; max-width: 600px; }
        </style>
    </head>
    <body>
        <div class="content">
            <h1>Analyse des Ventes Mensuelles</h1>
            <img src="' . $chartData . '" alt="Graphique des Ventes Mensuelles">
        </div>
    </body>
    </html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("analyse_ventes.pdf", ["Attachment" => false]);
}
?>
