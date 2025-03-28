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
                 ed.dateEvent, ed.timeEvent,
                 u.nomUser, u.prenomUser, u.mailUser
          FROM reservation r
          JOIN edition ed ON r.editionId = ed.editionId
          JOIN evenement e ON ed.eventId = e.eventId
          JOIN utilisateur u ON r.idUser = u.idUser
          WHERE r.idReservation = :reservationId AND r.idUser = :userId";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':reservationId', $reservationId);
$stmt->bindParam(':userId', $userId);
$stmt->execute();
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$invoice) {
    header("Location: profile.php");
    exit;
}


$normalTotal = $invoice['qteBilletsNormal'] * $invoice['TariffNormal'];
$reducedTotal = $invoice['qteBilletsReduit'] * $invoice['TariffReduit'];
$totalAmount = $normalTotal + $reducedTotal;
$totalTickets = $invoice['qteBilletsNormal'] + $invoice['qteBilletsReduit'];


$invoiceNumber = sprintf("%06d", $invoice['idReservation']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    <title>Facture #<?php echo $invoiceNumber; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "League Spartan", sans-serif;
        }
        
        body {
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .company-info {
            font-weight: bold;
        }
        
        .company-name {
            font-size: 28px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .company-address {
            font-size: 18px;
            font-weight: normal;
        }
        
        .client-info {
            text-align: right;
            font-size: 16px;
        }
        
        .client-name {
            font-weight: bold;
        }
        
        .event-info {
            font-size: 16px;
            text-transform: uppercase;
            margin-bottom: 30px;
        }
        
        .event-name {
            font-size: 16px;
            font-weight: bold;
        }
        
        .invoice-title {
            text-transform: uppercase;
            font-size: 25px;
            font-weight: bold;
            margin: 20px 0;
        }
        
        table {
            font-size: 18px;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        th, td {
            padding: 10px 5px;
            text-align: left;
        }
        
        th {
            border-bottom: 1px solid #ddd;
        }
        
        td {
            border-bottom: 1px solid #eee;
        }
        
        tr:last-child td {
            border-bottom: 1px solid #ddd;
        }
        
        .text-right {
            text-align: right;
        }
        
        .total-row {
            font-size: 25px;
            text-align: right;
            margin: 20px 0;
            font-weight: bold;
        }
        
        .total-line {
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }
        
        .thank-you {
            text-align: center;
            margin-top: 50px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
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
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="company-name">ASSOCIATION FARHA</div>
            <div class="company-address">Centre Culturel Farha, Tanger</div>
        </div>
        <div class="client-info">
            <div>Client :</div>
            <div class="client-name"><?php echo htmlspecialchars($invoice['prenomUser'] . ' ' . $invoice['nomUser']); ?></div>
<div>Adresse email :</div>
<div><?php echo htmlspecialchars($invoice['mailUser']); ?></div>
        </div>
    </div>
    
    <div class="event-info">
        <div class="event-name"><?php echo htmlspecialchars($invoice['eventTitle']); ?></div>
        <div>
            <?php 
                echo date('d/m/Y', strtotime($invoice['dateEvent'])) . ' à ' . 
                     date('H\hi', strtotime($invoice['timeEvent']));
            ?>
        </div>
    </div>
    
    <div class="invoice-title">FACTURE #<?php echo $invoiceNumber; ?></div>
    
    <table>
        <thead>
            <tr>
                <th>Tarif</th>
                <th>Prix</th>
                <th>Qté</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($invoice['qteBilletsNormal'] > 0): ?>
            <tr>
                <td>Normal</td>
                <td><?php echo number_format($invoice['TariffNormal'], 2); ?></td>
                <td><?php echo $invoice['qteBilletsNormal']; ?></td>
                <td class="text-right"><?php echo number_format($normalTotal, 2); ?> MAD</td>
            </tr>
            <?php endif; ?>
            
            <?php if ($invoice['qteBilletsReduit'] > 0): ?>
            <tr>
                <td>Réduit</td>
                <td><?php echo number_format($invoice['TariffReduit'], 2); ?></td>
                <td><?php echo $invoice['qteBilletsReduit']; ?></td>
                <td class="text-right"><?php echo number_format($reducedTotal, 2); ?> MAD</td>
            </tr>
            <?php endif; ?>
            
            <tr>
                <td colspan="2"></td>
                <td><?php echo $totalTickets; ?></td>
                <td class="text-right"><?php echo number_format($totalAmount, 2); ?> MAD</td>
            </tr>
        </tbody>
    </table>
    
    <div class="total-line"></div>
    
    <div class="total-row">
        Total à payer : <span><?php echo number_format($totalAmount, 2); ?> MAD</span>
    </div>
    
    <div class="total-line"></div>
    
    <div class="thank-you">MERCI !</div>

    <div class="buttons">
        <a href="profile.php" class="btn">Mes billets</a>
        <a href="view-tickets.php?reservation=<?php echo $reservationId; ?>" class="btn btn-secondary">Voir les billets</a>
    </div>
</body>
</html>