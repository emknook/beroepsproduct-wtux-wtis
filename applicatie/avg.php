<?php

require_once 'includes/setup.php';

$db = db();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>

    <?php require 'includes/header.php'; ?>

    <main>
        <article class="privacy">
            <h1>Privacyverklaring</h1>

            <p>
                Pizzeria gaat zorgvuldig om met uw persoonsgegevens.
                In deze privacyverklaring leggen wij uit welke gegevens wij
                verzamelen en waarvoor wij deze gebruiken.
            </p>

            <section>
                <h2>Welke gegevens verzamelen wij?</h2>

                <p>
                    Wanneer u een account aanmaakt of een bestelling plaatst,
                    kunnen wij de volgende gegevens verzamelen:
                </p>

                <ul>
                    <li>voornaam en achternaam;</li>
                    <li>adresgegevens;</li>
                    <li>e-mailadres;</li>
                    <li>inloggegevens;</li>
                    <li>bestelgegevens.</li>
                </ul>
            </section>

            <section>
                <h2>Waarvoor gebruiken wij deze gegevens?</h2>

                <p>Wij gebruiken uw gegevens om:</p>

                <ul>
                    <li>uw account aan te maken en te beheren;</li>
                    <li>u te laten inloggen;</li>
                    <li>uw bestelling te verwerken en te bezorgen;</li>
                    <li>contact met u op te nemen over uw bestelling;</li>
                    <li>onze website en dienstverlening te verbeteren.</li>
                </ul>
            </section>

            <section>
                <h2>Hoe lang bewaren wij uw gegevens?</h2>

                <p>
                    Wij bewaren uw persoonsgegevens niet langer dan nodig is.
                    Bestelgegevens kunnen langer worden bewaard wanneer dit
                    noodzakelijk is voor onze administratie.
                </p>
            </section>

            <section>
                <h2>Delen van persoonsgegevens</h2>

                <p>
                    Wij verkopen uw persoonsgegevens niet aan andere partijen.
                    Uw gegevens worden alleen gedeeld wanneer dit noodzakelijk
                    is voor het verwerken of bezorgen van uw bestelling.
                </p>
            </section>

            <section>
                <h2>Beveiliging</h2>

                <p>
                    Wij nemen passende maatregelen om uw persoonsgegevens te
                    beschermen. Wachtwoorden worden niet als leesbare tekst
                    opgeslagen.
                </p>
            </section>

            <section>
                <h2>Uw rechten</h2>

                <p>
                    U heeft het recht om uw persoonsgegevens in te zien, te
                    wijzigen of te laten verwijderen. Hiervoor kunt u contact
                    met ons opnemen.
                </p>
            </section>

            <section>
                <h2>Contact</h2>

                <p>
                    Heeft u vragen over deze privacyverklaring? Neem dan contact
                    op via
                    <a href="mailto:privacy@pizzeria.nl">
                        privacy@pizzeria.nl
                    </a>.
                </p>
            </section>
        </article>
    </main>
    <footer>
        <p>&copy; 2026 Pizzeria</p>

        <nav aria-label="Voettekst">
            <a href="avg.php">Privacyverklaring</a>
        </nav>
    </footer>
</body>

</html>