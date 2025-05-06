<?php
// Configuration
define("RECIPIENT_NAME", "jardin-iris");
define("RECIPIENT_EMAIL", "contact@jardin-iris.be");
define("RECAPTCHA_SECRET_KEY", "6Lc2Ec8qAAAAAKsI_cofoBHA6ELz6Wmx_ifzehJF");

// Vérification du reCAPTCHA
$recaptchaResponse = $_POST['recaptcha_response'] ?? '';
$verifyResponse = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . RECAPTCHA_SECRET_KEY . "&response=" . $recaptchaResponse);
$responseData = json_decode($verifyResponse);

if (!$responseData->success || $responseData->score < 0.5) {
    header('Location: jardinier-paysagiste-contact.html?message=Failed');
    exit();
}

// Lecture des valeurs du formulaire
$userName = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$senderEmail = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$userPhone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
$userSubject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

// Détection de la langue de la page d'origine
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$isEnglish = strpos($referer, '/en/') !== false;  // Vérifie si l'URL contient "/en/"

// Effectuer l'envoi du mail seulement si tous les champs sont remplis
if ($userName && $senderEmail && $userPhone && $userSubject && $message) {
    $recipient = RECIPIENT_EMAIL;
    $subject = $userSubject;
    $msgBody = "Nom: $userName\nEmail: $senderEmail\nTéléphone: $userPhone\nSujet: $userSubject\nMessage:\n$message";
    $headers = 'From: ' . $recipient . "\r\n" . 'Reply-To: ' . $senderEmail . "\r\n" . 'X-Mailer: PHP/' . phpversion();

    if (mail($recipient, $subject, $msgBody, $headers)) {
        // Redirection conditionnelle en fonction de la langue
        if ($isEnglish) {
            header('Location: https://jardin-iris.be/en/jardinier-paysagiste-contact.html?message=Success');
        } else {
            header('Location: https://jardin-iris.be/jardinier-paysagiste-contact.html?message=Success');
        }
    } else {
        // En cas d'échec de l'envoi du mail
        if ($isEnglish) {
            header('Location: https://jardin-iris.be/en/jardinier-paysagiste-contact.html?message=Failed');
        } else {
            header('Location: https://jardin-iris.be/jardinier-paysagiste-contact.html?message=Failed');
        }
    }
    exit();
} else {
    // En cas de champ manquant
    if ($isEnglish) {
        header('Location: https://jardin-iris.be/en/jardinier-paysagiste-contact.html?message=Failed');
    } else {
        header('Location: https://jardin-iris.be/jardinier-paysagiste-contact.html?message=Failed');
    }
    exit();
}
?>
