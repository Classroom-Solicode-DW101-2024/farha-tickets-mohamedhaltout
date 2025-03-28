<?php
session_start();
require("connect.php");


if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$eventId = $_GET['id'];


$query = "SELECT e.eventId, e.eventTitle, e.eventDescription, e.eventType AS eventCategory, 
                 e.TariffNormal, e.TariffReduit, ed.editionId, ed.dateEvent, ed.timeEvent, 
                 ed.image, s.NumSalle, s.capSalle, s.DescSalle,
                 (SELECT SUM(r.qteBilletsNormal + r.qteBilletsReduit) FROM reservation r 
                  WHERE r.editionId = ed.editionId) AS bookedSeats
          FROM evenement e
          JOIN edition ed ON e.eventId = ed.eventId
          JOIN salle s ON ed.NumSalle = s.NumSalle
          WHERE e.eventId = :eventId";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':eventId', $eventId);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$event) {
    header("Location: index.php");
    exit;
}


$availableSeats = $event['capSalle'] - ($event['bookedSeats'] );
$isSoldOut = $availableSeats <= 0;


$message = '';
$messageType = '';
$reservationId = null;
$showConfirmation = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['purchase']) && !$isSoldOut) {

    if (!isset($_SESSION['idUser'])) {
        $message = "Veuillez vous connecter pour acheter des billets.";
        $messageType = "error";
    } else {
        $normalTickets = (int)$_POST['normalTickets'];
        $reducedTickets = (int)$_POST['reducedTickets'];
        $userId = $_SESSION['idUser'];
        

        if ($normalTickets < 0 || $reducedTickets < 0) {
            $message = "Veuillez saisir un nombre valide de billets.";
            $messageType = "error";
        } elseif (($normalTickets + $reducedTickets) <= 0) {
            $message = "Veuillez sélectionner au moins un billet.";
            $messageType = "error";
        } elseif (($normalTickets + $reducedTickets) > $availableSeats) {
            $message = "Pas assez de places disponibles. Seulement $availableSeats places restantes.";
            $messageType = "error";
        } else {

            try {
                $pdo->beginTransaction();
                
                $insertReservation = "INSERT INTO reservation 
                                     (qteBilletsNormal, qteBilletsReduit, editionId, idUser) 
                                     VALUES (:normalTickets, :reducedTickets, :editionId, :userId)";
                
                $stmtReservation = $pdo->prepare($insertReservation);
                $stmtReservation->bindParam(':normalTickets', $normalTickets);
                $stmtReservation->bindParam(':reducedTickets', $reducedTickets);
                $stmtReservation->bindParam(':editionId', $event['editionId']);
                $stmtReservation->bindParam(':userId', $userId);
                $stmtReservation->execute();
                
                $reservationId = $pdo->lastInsertId();
                

                $ticketTypes = [];
                if ($normalTickets > 0) {
                    $ticketTypes['Normal'] = $normalTickets;
                }
                if ($reducedTickets > 0) {
                    $ticketTypes['Reduit'] = $reducedTickets;
                }
                
                $insertTicket = "INSERT INTO billet (billetId, typeBillet, placeNum, idReservation) 
                                VALUES (:billetId, :typeBillet, :placeNum, :idReservation)";
                $stmtTicket = $pdo->prepare($insertTicket);
                

                $nextSeatQuery = "SELECT MAX(b.placeNum) as lastSeat 
                                 FROM billet b 
                                 JOIN reservation r ON b.idReservation = r.idReservation 
                                 WHERE r.editionId = :editionId";
                $nextSeatStmt = $pdo->prepare($nextSeatQuery);
                $nextSeatStmt->bindParam(':editionId', $event['editionId']);
                $nextSeatStmt->execute();
                $lastSeatResult = $nextSeatStmt->fetch(PDO::FETCH_ASSOC);
                

                $placeNum = ($lastSeatResult['lastSeat'] ?? 0) + 1;
                
                foreach ($ticketTypes as $type => $quantity) {
                    for ($i = 0; $i < $quantity; $i++) {
                        $ticketId = uniqid('TKT-', true) . '-' . bin2hex(random_bytes(4));
                        $stmtTicket->bindParam(':billetId', $ticketId);
                        $stmtTicket->bindParam(':typeBillet', $type);
                        $stmtTicket->bindParam(':placeNum', $placeNum);
                        $stmtTicket->bindParam(':idReservation', $reservationId);
                        $stmtTicket->execute();
                        $placeNum++;
                    }
                }
                
                $pdo->commit();
                $message = "Achat réussi ! Votre numéro de réservation est : $reservationId";
                $messageType = "success";
                $showConfirmation = true;
                

                $availableSeats -= ($normalTickets + $reducedTickets);
                $isSoldOut = $availableSeats <= 0;
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "Une erreur est survenue : " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300..700;1,300..700&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Lora:ital,wght@0,400..700;1,400..700&family=Nunito:ital,wght@0,200..1000;1,200..1000&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Red+Hat+Text:ital,wght@0,300..700;1,300..700&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="details.css">
    <title><?php echo htmlspecialchars($event['eventTitle']); ?> - FarhaEvent</title>
    
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
            <div class="logo"><img src="img/logo_farha.png" alt=""></div>
                <nav>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        
                        <?php if(isset($_SESSION['idUser'])): ?>
                            <li><a href="profile.php">Mes Billets</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Connexion</a></li>
                            <li><a href="register.php">Inscription</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="event-header">
            <h1><?php echo htmlspecialchars($event['eventTitle']); ?></h1>
            <div class="event-category"><?php echo htmlspecialchars($event['eventCategory']); ?></div>
        </div>

        <div class="event-details">
            <div class="event-image">
                <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['eventTitle']); ?>">
            </div>
            
            <div class="event-info">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Date</div>
                        <div class="info-value"><?php echo date('d F Y', strtotime($event['dateEvent'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Heure</div>
                        <div class="info-value"><?php echo date('H:i', strtotime($event['timeEvent'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Salle</div>
                        <div class="info-value"><?php echo htmlspecialchars($event['DescSalle']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Capacité</div>
                        <div class="info-value"><?php echo htmlspecialchars($event['capSalle']); ?> places</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Places disponibles</div>
                        <div class="info-value"><?php echo $availableSeats; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Statut</div>
                        <div class="info-value" style="color: <?php echo $isSoldOut ? 'var(--error-color)' : 'var(--success-color)'; ?>">
                            <?php echo $isSoldOut ? 'Guichet fermé' : 'Disponible'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>À propos de l'événement</h3>
                    <div class="event-description">
                        <?php echo nl2br(htmlspecialchars($event['eventDescription'])); ?>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>Tarifs</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Tarif normal</div>
                            <div class="info-value"><?php echo number_format($event['TariffNormal'], 2); ?> MAD</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Tarif réduit</div>
                            <div class="info-value"><?php echo number_format($event['TariffReduit'], 2); ?> MAD</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($message) && !$showConfirmation): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($showConfirmation): ?>
    <div class="confirmation-box">
        <h3>Confirmation d'achat</h3>
        <p>Félicitations ! Votre achat de billets a été traité avec succès.</p>
        <p>Numéro de réservation : <strong><?php echo $reservationId; ?></strong></p>
        <div class="confirmation-actions">
            <a href="view-tickets.php?reservation=<?php echo $reservationId; ?>" class="btn">Voir mes billets</a>
            <a href="view-invoice.php?reservation=<?php echo $reservationId; ?>" class="btn btn-secondary">Voir ma facture</a>
        </div>
    </div>
<?php endif; ?>

        <div class="purchase-form">
            <h3><?php echo $isSoldOut ? 'Guichet fermé' : 'Acheter vos billets'; ?></h3>
            
            <?php if (!$isSoldOut && !$showConfirmation): ?>
                <form method="POST" action="" id="purchaseForm">
                    <div class="form-group">
                        <label for="normalTickets">Billets tarif normal</label>
                        <input type="number" name="normalTickets" id="normalTickets" class="form-control" value="0" min="0" max="<?php echo $availableSeats; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="reducedTickets">Billets tarif réduit</label>
                        <input type="number" name="reducedTickets" id="reducedTickets" class="form-control" value="0" min="0" max="<?php echo $availableSeats; ?>">
                    </div>
                    
                    <div class="ticket-calculator">
                        <div class="calculator-row">
                            <span>Billets tarif normal:</span>
                            <span id="normalTotal">0.00 MAD</span>
                        </div>
                        <div class="calculator-row">
                            <span>Billets tarif réduit:</span>
                            <span id="reducedTotal">0.00 MAD</span>
                        </div>
                        <div class="calculator-row">
                            <span>Montant total:</span>
                            <span id="totalAmount">0.00 MAD</span>
                        </div>
                    </div>
                    
                    <button type="submit" name="purchase" class="btn" <?php echo !isset($_SESSION['idUser']) ? 'disabled' : ''; ?>>
                        <?php echo !isset($_SESSION['idUser']) ? 'Veuillez vous connecter pour acheter' : 'Valider l\'achat'; ?>
                    </button>
                    
                    <?php if (!isset($_SESSION['idUser'])): ?>
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-secondary">Se connecter</a>
                    <?php endif; ?>
                </form>
                
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const normalPrice = <?php echo $event['TariffNormal']; ?>;
                        const reducedPrice = <?php echo $event['TariffReduit']; ?>;
                        const normalTicketsInput = document.getElementById('normalTickets');
                        const reducedTicketsInput = document.getElementById('reducedTickets');
                        const normalTotalElement = document.getElementById('normalTotal');
                        const reducedTotalElement = document.getElementById('reducedTotal');
                        const totalAmountElement = document.getElementById('totalAmount');
                        
                        function updatePrices() {
                            const normalTickets = parseInt(normalTicketsInput.value) || 0;
                            const reducedTickets = parseInt(reducedTicketsInput.value) || 0;
                            
                            const normalTotal = normalTickets * normalPrice;
                            const reducedTotal = reducedTickets * reducedPrice;
                            const totalAmount = normalTotal + reducedTotal;
                            
                            normalTotalElement.textContent = normalTotal.toFixed(2) + ' MAD';
                            reducedTotalElement.textContent = reducedTotal.toFixed(2) + ' MAD';
                            totalAmountElement.textContent = totalAmount.toFixed(2) + ' MAD';
                        }
                        
                        normalTicketsInput.addEventListener('change', updatePrices);
                        reducedTicketsInput.addEventListener('change', updatePrices);
                        normalTicketsInput.addEventListener('input', updatePrices);
                        reducedTicketsInput.addEventListener('input', updatePrices);
                        

                        updatePrices();
                    });
                </script>
            <?php elseif ($isSoldOut): ?>
                <p style="text-align: center;">Cet événement est complet. Veuillez consulter nos autres événements.</p>
                <a href="index.php" class="btn" style="display: block; text-align: center; text-decoration: none; margin-top: 20px;">Voir les autres événements</a>
            <?php endif; ?>
        </div>

        <div class="venue-info">
            <h3>Informations sur la salle</h3>
            <p><?php echo nl2br(htmlspecialchars($event['DescSalle'])); ?></p>
            <p>Capacité: <?php echo htmlspecialchars($event['capSalle']); ?> places</p>
            <p>Numéro de salle: <?php echo htmlspecialchars($event['NumSalle']); ?></p>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-content">
                
            <div class="copyright">
                <p>&copy; 2025 FarhaEvents.</p>
            </div>
        </div>
    </footer>
</body>
</html>