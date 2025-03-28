<?php
session_start();
require("connect.php");


if (!isset($_SESSION['idUser'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userId = $_SESSION['idUser'];


$userQuery = "SELECT idUser, nomUser, prenomUser, mailUser FROM utilisateur WHERE idUser = :userId";
$userStmt = $pdo->prepare($userQuery);
$userStmt->bindParam(':userId', $userId);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);


$reservationsQuery = "SELECT r.idReservation, r.qteBilletsNormal, r.qteBilletsReduit, 
                            ed.dateEvent, e.TariffNormal, e.TariffReduit, e.eventTitle
                     FROM reservation r
                     JOIN edition ed ON r.editionId = ed.editionId
                     JOIN evenement e ON ed.eventId = e.eventId
                     WHERE r.idUser = :userId
                     ORDER BY r.idReservation DESC";
$reservationsStmt = $pdo->prepare($reservationsQuery);
$reservationsStmt->bindParam(':userId', $userId);
$reservationsStmt->execute();
$reservations = $reservationsStmt->fetchAll(PDO::FETCH_ASSOC);


$message = '';
$messageType = '';
if (isset($_SESSION['profile_message']) && isset($_SESSION['profile_message_type'])) {
    $message = $_SESSION['profile_message'];
    $messageType = $_SESSION['profile_message_type'];
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
    <title>Mon Profil - FarhaEvents</title>
    <style>
        .profile-container {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
        }

        .page-title 
        
        .profile-section {
            flex: 1;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }
        
        .profile-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--primary-color);
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }
        
        .purchases-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .purchases-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 500;
        }
        
        .purchases-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .purchases-table tr:last-child td {
            border-bottom: none;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-button {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            text-align: center;
        }
        
        .view-tickets-btn {
            background-color: var(--primary-color);
            color: white;
        }
        
        .view-invoice-btn {
            background-color: #f8f9fa;
            color: #495057;
            border: 1px solid #ddd;
        }
        
        .message {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 4px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
            }
        }
    </style>
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
                            <li><a href="profile.php" class="active">Mon Profil</a></li>
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
        <div class="page-title">
            <h1>Mon Profil</h1>
            <p>Gérez vos informations personnelles et consultez vos achats</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="profile-container">
            <div class="profile-section">
                <h2>Mes Informations</h2>
                <form action="update-profile.php" method="POST">
            
                    <div class="form-group">
                        <label for="lastName">Nom</label>
                        <input type="text" id="lastName" name="lastName" class="form-control" value="<?php echo htmlspecialchars($user['nomUser']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="firstName">Prénom</label>
                        <input type="text" id="firstName" name="firstName" class="form-control" value="<?php echo htmlspecialchars($user['prenomUser']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['mailUser']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Nouveau Mot de Passe (laisser vide pour ne pas changer)</label>
                        <input type="password" id="password" name="password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirmer le Mot de Passe</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" class="form-control">
                    </div>
                    <button type="submit" class="btn">Mettre à jour le profil</button>
                </form>
            </div>
            
            <div class="profile-section">
                <h2>Mes Achats</h2>
                <?php if (count($reservations) > 0): ?>
                    <table class="purchases-table">
                        <thead>
                            <tr>
                                <th>Réf. Facture</th>
                                <th>Événement</th>
                                <th>Date d'achat</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <?php 
                                $normalTotal = $reservation['qteBilletsNormal'] * $reservation['TariffNormal'];
                                $reducedTotal = $reservation['qteBilletsReduit'] * $reservation['TariffReduit'];
                                $totalAmount = $normalTotal + $reducedTotal;
                                ?>
                                <tr>
                                    <td>FACT-<?php echo $reservation['idReservation']; ?></td>
                                    <td><?php echo htmlspecialchars($reservation['eventTitle']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($reservation['dateEvent'])); ?></td>
                                    <td><?php echo number_format($totalAmount, 2); ?> MAD</td>
                                    <td class="action-buttons">
                                        <a href="view-tickets.php?reservation=<?php echo $reservation['idReservation']; ?>" class="action-button view-tickets-btn">Voir mes billets</a>
                                        <a href="view-invoice.php?reservation=<?php echo $reservation['idReservation']; ?>" class="action-button view-invoice-btn">Voir ma facture</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Vous n'avez pas encore d'achats.</p>
                    <a href="events.php" class="btn">Découvrir nos événements</a>
                <?php endif; ?>
            </div>
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

    
    <script>

document.querySelector('form').addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword && password !== '') {
                event.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
            }
        });
    </script>
</body>
</html>