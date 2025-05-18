<?php
// Configuration
define("RECIPIENT_NAME", "jardin-iris");
define("RECIPIENT_EMAIL", "contact@jardin-iris.be");
define("RECAPTCHA_SECRET_KEY", "6Lc2Ec8qAAAAAKsI_cofoBHA6ELz6Wmx_ifzehJF");
define("MAX_FILE_SIZE", 10 * 1024 * 1024); // 4 Mo en octets
define("UPLOAD_DIR", "uploads/");
define("ALLOWED_EXTENSIONS", ["jpg", "jpeg", "png", "gif"]);

// Création du répertoire d'upload s'il n'existe pas
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Fonction de journalisation sécurisée
function secureLog($message) {
    $logFile = 'contact_form_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    file_put_contents($logFile, "[$timestamp] [$ip] $message\n", FILE_APPEND);
}

// Vérification du reCAPTCHA
$recaptchaResponse = $_POST['recaptcha_response'] ?? '';
$verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . RECAPTCHA_SECRET_KEY . "&response=" . $recaptchaResponse);
$responseData = json_decode($verifyResponse);

if (!$responseData->success || $responseData->score < 0.5) {
    secureLog("Échec de validation reCAPTCHA");
    header('Location: jardinier-paysagiste-contact.html?message=Failed');
    exit();
}

// Lecture et nettoyage des valeurs du formulaire
$userName = filter_var($_POST['username'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$senderEmail = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$userPhone = filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$userAddress = filter_var($_POST['address'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$userSubject = filter_var($_POST['subject'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$gardenSize = filter_var($_POST['garden_size'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$budget = filter_var($_POST['budget'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);



// Génération d'une boundary unique
$boundary = md5(uniqid(time()));

// Préparation des headers
$headers = "From: " . RECIPIENT_EMAIL . "\r\n";
$headers .= "Reply-To: " . $senderEmail . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";

// Corps du message (partie HTML)
$body = "--$boundary\r\n";
$body .= "Content-Type: text/html; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";

// Contenu HTML du message
$body .= "<p><strong>Nom:</strong> $userName</p>";
$body .= "<p><strong>Email:</strong> $senderEmail</p>";
$body .= "<p><strong>Téléphone:</strong> $userPhone</p>";
$body .= "<p><strong>Adresse:</strong> $userAddress</p>";
$body .= "<p><strong>Sujet:</strong> $userSubject</p>";
$body .= "<p><strong>Taille du jardin:</strong> $gardenSize m²</p>";
$body .= "<p><strong>Budget:</strong> $budget</p>";
$body .= "<p>$message</p>\r\n";

// Gestion des fichiers joints
if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
    $fileCount = count($_FILES['photos']['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        // Vérification taille et extension
        if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK &&
            $_FILES['photos']['size'][$i] <= MAX_FILE_SIZE) {

            $fileName = basename($_FILES['photos']['name'][$i]);
            $fileTmpPath = $_FILES['photos']['tmp_name'][$i];
            $fileType = mime_content_type($fileTmpPath);
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (in_array($extension, ALLOWED_EXTENSIONS)) {
                $fileContent = chunk_split(base64_encode(file_get_contents($fileTmpPath)));

                $body .= "--$boundary\r\n";
                $body .= "Content-Type: $fileType; name=\"$fileName\"\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= $fileContent . "\r\n";
            } else {
                secureLog("Fichier non autorisé: $fileName");
            }
        } else {
            secureLog("Erreur upload fichier: " . $_FILES['photos']['name'][$i]);
        }
    }
}

// Fin du corps multipart
$body .= "--$boundary--\r\n";

// Envoi de l'email
$mailSuccess = mail(RECIPIENT_EMAIL, "[Jardin Iris] $userSubject", $body, $headers);

// Détection de la langue de la page d'origine
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$isEnglish = strpos($referer, '/en/') !== false;  // Vérifie si l'URL contient "/en/"

if (filter_var($senderEmail, FILTER_VALIDATE_EMAIL) && $mailSuccess) {
    // Redirection conditionnelle en fonction de la langue
    if ($isEnglish) {
        header('Location: https://jardin-iris.be/en/jardinier-paysagiste-contact.html?message=Success');
    } else {
        header('Location: https://jardin-iris.be/jardinier-paysagiste-contact.html?message=Success');
    }
    exit();
} else {
    secureLog("Adresse email invalide: $senderEmail");
    // En cas d'échec de l'envoi du mail
    if ($isEnglish) {
        header('Location: https://jardin-iris.be/en/jardinier-paysagiste-contact.html?message=Failed');
    } else {
        header('Location: https://jardin-iris.be/jardinier-paysagiste-contact.html?message=Failed');
    }
    exit();
}

exit();
?>
