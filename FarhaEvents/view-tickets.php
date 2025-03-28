<?php
session_start();
require("connect.php");


if (!isset($_GET['reservation']) || empty($_GET['reservation'])) {
    header("Location: index.php");
    exit;
}

$reservationId = $_GET['reservation'];


if (!isset($_SESSION['idUser'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userId = $_SESSION['idUser'];


$query = "SELECT r.idReservation, r.qteBilletsNormal, r.qteBilletsReduit, 
                 e.eventTitle, e.TariffNormal, e.TariffReduit,
                 ed.dateEvent, ed.timeEvent, s.NumSalle, s.DescSalle,
                 b.billetId, b.typeBillet, b.placeNum,
                 u.nomUser, u.prenomUser, u.mailUser
          FROM reservation r
          JOIN edition ed ON r.editionId = ed.editionId
          JOIN evenement e ON ed.eventId = e.eventId
          JOIN salle s ON ed.NumSalle = s.NumSalle
          JOIN billet b ON r.idReservation = b.idReservation
          JOIN utilisateur u ON r.idUser = u.idUser
          WHERE r.idReservation = :reservationId AND r.idUser = :userId";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':reservationId', $reservationId);
$stmt->bindParam(':userId', $userId);
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);


if (empty($tickets)) {
    header("Location: profile.php");
    exit;
}


$firstTicket = $tickets[0];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    <title>Billet - <?php echo htmlspecialchars($firstTicket['eventTitle']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "League Spartan", sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 30px;
        }
        
        .ticket {
            width: 800px;
            height: 250px;
            background-color: white;
            display: flex;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .ticket-left {
            width: 8%;
            background-color: #222222;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .ticket-num-title {
            font-size: 13px;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            margin-top: 40px;
        }
        
        .ticket-number {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 12px;
            margin-bottom: 20px;
        }
        
        .ticket-right {
            width: 92%;
            padding: 30px;
            position: relative;
        }
        
        .event-title {
    position: absolute;
    width: 20%;
    font-size: 42px;
    font-weight: bold;
    line-height: 1;
    text-transform: uppercase;
    white-space: pre-line;
}

        
        .event-date {
            background-color: #f5f2ea;
            padding: 15px 10px 15px 10px;
            margin-top: 120px;
            font-weight: 400;
            max-width: 250px;
        }
        
        .ticket-details {
            position: absolute;
            left: 300px;
            top: 80px;
            font-size: 16px;
        }
        
        .organization {
            position: absolute;
            left: 300px;
            top: 35px;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        .right_style {
            background-color: #F6F0EB;
        }
        
        .dot {
            width: 20px;
            height: 20px;
            background-color: #1a1a1a;
            border-radius: 50%;
            margin-right: 15px;
        }
        
        .ticket-barcode {
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .barcode {
            width: 120px;
            height: 80px;
            background-image: linear-gradient(90deg, #000 2px, transparent 2px), linear-gradient(90deg, #000 2px, transparent 2px), linear-gradient(90deg, #000 3px, transparent 3px);
            background-size: 8px 100%, 12px 100%, 16px 100%;
            background-repeat: repeat-x;
            background-position: 0 0, 20px 0, 50px 0;
            margin-bottom: 10px;
        }
        
        .seat-place {
            text-align: center;
        }
        
        .place-number {
            font-size: 48px;
            font-weight: bold;
        }
        
        .seat-number {
            font-size: 48px;
            font-weight: bold;
        }
        
        .divider {
            position: absolute;
            top: 0;
            right: 180px;
            height: 100%;
            border-right: 2px dashed #1a1a1a;
        }
        
        .circles {
            position: absolute;
        }
        
        .circle-top {
            width: 40px;
            height: 40px;
            background-color: #1a1a1a;
            border-radius: 50%;
            position: absolute;
            top: -20px;
            right: 160px;
        }
        
        .circle-bottom {
            width: 40px;
            height: 40px;
            background-color: #1a1a1a;
            border-radius: 50%;
            position: absolute;
            bottom: -20px;
            right: 160px;
        }
        
        .dots {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            overflow: hidden;
        }
        
        .dot-pattern {
            width: 40px;
            height: 100%;
            background-image: radial-gradient(circle, #000000 5px, transparent 5px);
            background-size: 40px 40px;
            background-position: 0 0;
        }

        .buttons {
            margin-top: 20px;
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            background-color: #222222;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .btn-secondary {
            background-color: #777;
        }

        .btn-print {
            background-color: #444;
        }

        @media print {
            .buttons {
                display: none;
            }
            body {
                padding: 0;
                background-color: white;
            }
        }
    </style>
</head>
<body>
    <h1 style="margin-bottom: 30px">Vos billets pour <?php echo htmlspecialchars($firstTicket['eventTitle']); ?></h1>
    
    <?php foreach ($tickets as $ticket): ?>
    <div class="ticket">
        <div class="ticket-left">
            <div class="left_section">
                <div class="ticket-number"><?php echo htmlspecialchars($ticket['billetId']); ?></div>
                <div class="ticket-num-title">Numéro de ticket</div>
            </div>
        </div>
        
        <div class="ticket-right">
        <div class="event-title"><?php echo nl2br(strtoupper(htmlspecialchars($firstTicket['eventTitle']))); ?></div>

            <div class="event-date">
                <?php 

setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'fr');
                    $date = new DateTime($firstTicket['dateEvent']);
                    echo mb_strtoupper(strftime('%A %d %B %Y', $date->getTimestamp())); 
                ?> 
                À 
                <?php echo date('H\hi', strtotime($firstTicket['timeEvent'])); ?>
            </div>
            
            <div class="organization">
                <div class="dot"></div>
                ASSOCIATION FARHA
            </div>
            
            <div class="ticket-details">
                <p style="margin-bottom: 5px;"><strong>Tarif :</strong></p>
                <p style="margin-bottom: 15px;">
                    <?php 
                        if ($ticket['typeBillet'] == 'Normal') {
                            echo number_format($firstTicket['TariffNormal'], 2);
                        } else {
                            echo number_format($firstTicket['TariffReduit'], 2);
                        }
                    ?> MAD
                </p>
                
                <p style="margin-bottom: 5px;"><strong>Type :</strong></p>
                <p style="margin-bottom: 15px;"><?php echo $ticket['typeBillet'] == 'Normal' ? 'Tarif normal' : 'Tarif réduit'; ?></p>
                
                <p style="margin-bottom: 10px;"><strong>Addresse :</strong></p>
                <p>Centre Culturel Farha, Tanger</p>
            </div>
            
            <div class="divider"></div>
            <div class="circle-top"></div>
            <div class="circle-bottom"></div>
            
            <div class="ticket-barcode">
                <div class="barcode"></div>
                <div class="seat-place">
                    <div>PLACE</div>
                    <div class="place-number"><?php echo htmlspecialchars($ticket['placeNum']); ?></div>
                </div>
                <div class="seat-place">
                    <div>SALLE</div>
                    <div class="seat-number"><?php echo htmlspecialchars($firstTicket['NumSalle']); ?></div>
                </div>
            </div>
            
            <div class="dots">
                <div class="dot-pattern"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <div class="buttons">
        <a href="profile.php" class="btn">Mes billets</a>
        <a href="view-invoice.php?reservation=<?php echo $reservationId; ?>" class="btn btn-secondary">Voir la facture</a>
    </div>
    
</body>
</html>