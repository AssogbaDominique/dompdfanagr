<?php
// Include autoloader
require_once 'dompdf/autoload.inc.php';

// Reference the Dompdf namespace
use Dompdf\Dompdf;

// Function to generate PDF
function generatePDF() {
    // Créer une instance de Dompdf
    $dompdf = new Dompdf();

    // Chemin de l'image
    $imagePath = 'images/bar_chart.jpg';
    $imageData = base64_encode(file_get_contents($imagePath));
    $src = 'data:image/jpg;base64,' . $imageData;

    // Contenu HTML
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
            <h1>Mon document PDF</h1>
            <img src="' . $src . '" alt="Image">
        </div>
    </body>
    </html>';

    // Charger le contenu HTML
    $dompdf->loadHtml($html);

    // (Optionnel) Configurer la taille et l'orientation de la page
    $dompdf->setPaper('A4', 'portrait');

    // Rendre le PDF
    $dompdf->render();

    // Envoyer le PDF au navigateur
    $dompdf->stream("document.pdf", ["Attachment" => false]);
}

// Check if form is submitted
if (isset($_POST['generate_pdf'])) {
    generatePDF();
}
?>

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
        <h1>Mon document PDF</h1>
        <img src="<?php echo $src; ?>" alt="Image">
        <form method="post">
            <input type="submit" name="generate_pdf" value="Générer le PDF">
        </form>
    </div>
</body>
</html>
>