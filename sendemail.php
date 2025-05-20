<?php
// Configuration
define("RECIPIENT_NAME", "jardin-iris");
define("RECIPIENT_EMAIL", "contact@jardin-iris.be");
define("RECAPTCHA_SECRET_KEY", "6Lc2Ec8qAAAAAKsI_cofoBHA6ELz6Wmx_ifzehJF");
define("MAX_FILE_SIZE", 10 * 1024 * 1024); // 10 Mo en octets
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
$userSubject = filter_var($_POST['subject'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Méthodes de contact
$contactMethods = isset($_POST['contact_method']) ? $_POST['contact_method'] : ['email'];
$availabilityDays = isset($_POST['availability_days']) ? $_POST['availability_days'] : [];

// Dates de visite
$visitDates = isset($_POST['visitDates']) ? $_POST['visitDates'] : [];
$visitDatesFormatted = !empty($visitDates) ? implode(', ', array_map('htmlspecialchars', $visitDates)) : "Aucune date sélectionnée";

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
$body .= "<p><strong>Nom :</strong> $userName</p>";
$body .= "<p><strong>Email :</strong> $senderEmail</p>";
$body .= "<p><strong>Téléphone :</strong> $userPhone</p>";
$body .= "<p><strong>Sujet :</strong> $userSubject</p>";

// Méthodes de contact
$body .= "<p><strong>Méthodes de contact préférées :</strong> ";
if (in_array('email', $contactMethods)) {
    $body .= "Email";
    if (in_array('phone', $contactMethods)) {
        $body .= " et Téléphone";
    }
} elseif (in_array('phone', $contactMethods)) {
    $body .= "Téléphone uniquement";
}
$body .= "</p>";

// Disponibilité téléphonique
if (in_array('phone', $contactMethods) && !empty($availabilityDays)) {
    $body .= "<p><strong>Disponibilité téléphonique :</strong> ";
    $body .= in_array('tous', $availabilityDays) ? "Tous les jours" : implode(', ', $availabilityDays);
    $body .= "</p>";
}

// Dates de visite
$body .= "<p><strong>Dates de visite sélectionnées :</strong> $visitDatesFormatted</p>";

$body .= "<p>$message</p>\r\n";

// Fin du corps multipart
$body .= "--$boundary--\r\n";

// Envoi de l'email à l'administrateur
$mailSuccess = mail(RECIPIENT_EMAIL, "[Jardin Iris] $userSubject", $body, $headers);

// Email de confirmation au client
$clientHeaders = "From: " . RECIPIENT_EMAIL . "\r\n";
$clientHeaders .= "Reply-To: " . RECIPIENT_EMAIL . "\r\n";
$clientHeaders .= "MIME-Version: 1.0\r\n";
$clientHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";

// Contenu du mail client
$clientBody = "<html><body>";
$clientBody .= "<h2>Merci pour votre message</h2>";
$clientBody .= "<p>Bonjour $userName,</p>";
$clientBody .= "<p>Nous avons bien reçu votre demande :</p>";
$clientBody .= "<p><strong>Sujet :</strong> $userSubject</p>";
$clientBody .= "<p><strong>Méthodes de contact :</strong> ";
if (in_array('email', $contactMethods)) {
    $clientBody .= "Email";
    if (in_array('phone', $contactMethods)) {
        $clientBody .= " et Téléphone";
    }
} elseif (in_array('phone', $contactMethods)) {
    $clientBody .= "Téléphone uniquement";
}
$clientBody .= "</p>";

// Disponibilité téléphonique
if (in_array('phone', $contactMethods) && !empty($availabilityDays)) {
    $clientBody .= "<p><strong>Disponibilités téléphoniques :</strong> ";
    $clientBody .= in_array('tous', $availabilityDays) ? "Tous les jours" : implode(', ', $availabilityDays);
    $clientBody .= "</p>";
}

// Dates de visite
$clientBody .= "<p><strong>Dates de visite sélectionnées :</strong> $visitDatesFormatted</p>";

$clientBody .= "<p><em>L'équipe de Jardin Iris</em></p>";
$clientBody .= "</body></html>";

// Envoi de l'email de confirmation
if (filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
    mail($senderEmail, "Confirmation de votre demande - Jardin Iris", $clientBody, $clientHeaders);
}

// Redirection
header('Location: jardinier-paysagiste-contact.html?message=Success');
exit();
?>
