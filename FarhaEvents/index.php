<?php
session_start();
require("connect.php");


$query = "SELECT e.eventId, e.eventTitle, ed.image, ed.dateEvent AS eventDate, e.eventType AS eventCategory, 
                 s.capSalle, 
                 (SELECT SUM(r.qteBilletsNormal + r.qteBilletsReduit) FROM reservation r 
                  WHERE r.editionId = ed.editionId) AS bookedSeats
                  FROM evenement e
                  JOIN edition ed ON e.eventId = ed.eventId
                  JOIN salle s ON ed.NumSalle = s.NumSalle
                  WHERE ed.dateEvent >= CURDATE()";
          



if (isset($_POST['searchTitle']) && !empty($_POST['searchTitle'])) {
    $searchTitle = $_POST['searchTitle'];
    $query .= " AND e.eventTitle LIKE :searchTitle";
}


if (isset($_POST['startDate']) && !empty($_POST['startDate']) && isset($_POST['endDate']) && !empty($_POST['endDate'])) {
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $query .= " AND ed.dateEvent BETWEEN :startDate AND :endDate";
}


if (isset($_POST['category']) && !empty($_POST['category'])) {
    $category = $_POST['category'];
    $query .= " AND e.eventType LIKE :category";
}


$query .= " ORDER BY ed.dateEvent ASC";

$stmt = $pdo->prepare($query);


if (isset($searchTitle) && !empty($searchTitle)) {
    $stmt->bindValue(':searchTitle', '%' . $searchTitle . '%');
}
if (isset($startDate) && isset($endDate) && !empty($startDate) && !empty($endDate)) {
    $stmt->bindValue(':startDate', $startDate);
    $stmt->bindValue(':endDate', $endDate);
}
if (isset($category) && !empty($category)) {
    $stmt->bindValue(':category', '%' . $category . '%');
}

$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300..700;1,300..700&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Lora:ital,wght@0,400..700;1,400..700&family=Nunito:ital,wght@0,200..1000;1,200..1000&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Red+Hat+Text:ital,wght@0,300..700;1,300..700&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="home.css">
    <title>Farha Events</title>
    <style>

    </style>
</head>
<body>

    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo"><img src="img/logo_farha.png" alt=""></div>
                <nav>
    <ul>
        <li><a href="index.php">Home</a></li>
        
        <?php if(isset($_SESSION['idUser'])): ?>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-title">
            <h2>Upcoming Events</h2>
            <p>Discover and book the best cultural events in Morocco</p>
        </div>

        <div class="filters">
            <form method="POST">
                <div class="filter-group">
                    <label for="searchTitle">Search by Title</label>
                    <input type="text" name="searchTitle" id="searchTitle" value="<?php echo isset($searchTitle) ? htmlspecialchars($searchTitle) : ''; ?>" />
                </div>
                <div class="filter-group">
                    <label for="startDate">Start Date</label>
                    <input type="date" name="startDate" id="startDate" value="<?php echo isset($startDate) ? htmlspecialchars($startDate) : ''; ?>" />
                </div>
                <div class="filter-group">
                    <label for="endDate">End Date</label>
                    <input type="date" name="endDate" id="endDate" value="<?php echo isset($endDate) ? htmlspecialchars($endDate) : ''; ?>" />
                </div>
                <div class="filter-group">
                <label for="category">Category</label>
<select name="category" id="category">
    <option disabled selected <?php echo (!isset($category) || $category == '') ? 'selected' : ''; ?>>Choose a category</option>
    <option value="musique" <?php echo (isset($category) && $category == 'musique') ? 'selected' : ''; ?>>Musique</option>
    <option value="theatre" <?php echo (isset($category) && $category == 'theatre') ? 'selected' : ''; ?>>Théatre</option>
    <option value="cinema" <?php echo (isset($category) && $category == 'cinema') ? 'selected' : ''; ?>>Cinéma</option>
    <option value="rencontres" <?php echo (isset($category) && $category == 'rencontres') ? 'selected' : ''; ?>>Rencontres</option>
</select>


</div>

                <div class="filter-group">
                    <button type="submit">Filter</button>
                </div>
            </form>
        </div>

        <div class="events-list">
            <?php if ($events): ?>
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <div class="event-image">
                            <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="Event Image">
                            <div class="event-category"><?php echo htmlspecialchars($event['eventCategory']); ?></div>
                        </div>
                        <div class="event-details">
                            <h3 class="event-title"><?php echo htmlspecialchars($event['eventTitle']); ?></h3>
                            <div class="event-info">
                                <div class="event-date">
                                    <span class="event-icon"></span>
                                    <?php echo date('F j, Y', strtotime($event['eventDate'])); ?>
                                </div>
                                <div class="event-location">
                                    <span class="event-icon"></span>
                                    Capacity: <?php echo htmlspecialchars($event['capSalle']); ?> seats
                                </div>
                            </div>
                            <div class="event-price">
                                <span>Booked Seats: <?php echo htmlspecialchars($event['bookedSeats']); ?> </span>
                            </div>
                            <div class="event-availability">
                                <span class="availability-status <?php echo ($event['bookedSeats'] >= $event['capSalle']) ? 'sold-out' : 'available'; ?>">
                                    <?php echo ($event['bookedSeats'] >= $event['capSalle']) ? 'Guichet Fermé' : 'Available'; ?>
                                </span>
                                <a href="details.php?id=<?php echo $event['eventId']; ?>" class="buy-button <?php echo ($event['bookedSeats'] >= $event['capSalle']) ? 'disabled' : ''; ?>">
                                J’achète</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No events found.</p>
            <?php endif; ?>
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
