<?php
session_start();
require("connect.php");

$error = '';
$success = '';


if (isset($_SESSION['idUser'])) {
    header("Location: index.php");
    exit;
}


if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        $query = "SELECT * FROM utilisateur WHERE mailUser = :email AND motPasse = :password";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        var_dump ($user);
        
        if ($user) {

            $_SESSION['idUser'] = $user['idUser'];
            $_SESSION['nomUser'] = $user['nomUser'];
            $_SESSION['prenomUser'] = $user['prenomUser'];
            
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid email or password";
        }
    }
}


if (isset($_POST['register'])) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['regEmail'];
    $password = $_POST['regPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "Please fill in all fields";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match";
    } else {

        $checkQuery = "SELECT COUNT(*) FROM utilisateur WHERE mailUser = :email";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() > 0) {
            $error = "Email already registered";
        } else {

            $userIdQuery = "SELECT MAX(CONVERT(idUser, SIGNED)) as maxId FROM utilisateur";
            $userIdStmt = $pdo->prepare($userIdQuery);
            $userIdStmt->execute();
            $maxId = $userIdStmt->fetch(PDO::FETCH_ASSOC)['maxId'];
            $newId = (int)$maxId + 1;
            

            $insertQuery = "INSERT INTO utilisateur (idUser, nomUser, prenomUser, mailUser, motPasse) 
                           VALUES (:idUser, :nomUser, :prenomUser, :mailUser, :motPasse)";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->bindParam(':idUser', $newId);
            $insertStmt->bindParam(':nomUser', $lastName);
            $insertStmt->bindParam(':prenomUser', $firstName);
            $insertStmt->bindParam(':mailUser', $email);
            $insertStmt->bindParam(':motPasse', $password);
            
            if ($insertStmt->execute()) {
                $success = "Registration successful! You can now log in.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <title>Login/Register - Soirée Marocaine</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }
        
        :root {
            --primary-color: #1a1a1a;
            --accent-color: #f5f2ea;
            --text-color: #333;
            --light-color: #fff;
            --border-color: #e0e0e0;
            --button-color: #1a1a1a;
            --hover-color: #444;
            --error-color: #dc3545;
            --success-color: #28a745;
        }
        
        body {
            background-color: #f8f8f8;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            background-color: var(--primary-color);
            color: var(--light-color);
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-content img {

            width:70%;
        
        }



        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 30px;
        }
        
        nav ul li a {
            color: var(--light-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: var(--accent-color);
        }
        
        .page-title {
            padding: 40px 0 20px;
            text-align: center;
        }
        
        .page-title h2 {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .page-title p {
            font-size: 18px;
            color: #777;
        }
        
        .auth-container {
            display: flex;
            justify-content: space-between;
            max-width: 900px;
            margin: 0 auto 50px;
        }
        
        .auth-form {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
            width: 48%;
        }
        
        .form-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #777;
            outline: none;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--button-color);
            color: var(--light-color);
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            text-align: center;
        }
        
        .btn:hover {
            background-color: var(--hover-color);
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: var(--error-color);
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: var(--success-color);
        }
        
        footer {
            background-color: var(--primary-color);
            color: var(--light-color);
            padding: 40px 0;
            margin-top: 50px;
        }
        
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        
        .footer-section {
            flex: 1;
            min-width: 200px;
            margin-bottom: 20px;
            padding: 0 15px;
        }
        
        .footer-section h3 {
            font-size: 18px;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: var(--accent-color);
        }
        
        .footer-links a {
            display: block;
            color: #aaa;
            margin-bottom: 10px;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--light-color);
        }
        
        .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #444;
            margin-top: 20px;
            font-size: 14px;
            color: #aaa;
        }
        
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
            }
            
            .auth-form {
                width: 100%;
                margin-bottom: 30px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 20px;
                justify-content: center;
            }
            
            nav ul li {
                margin: 0 10px;
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
        <li><a href="index.php">Home</a></li>
        
    </ul>
</nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-title">
            <h2>Account Access</h2>
            <p>Login to your account or register to book events</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="max-width: 900px; margin: 0 auto 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="max-width: 900px; margin: 0 auto 20px;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="auth-container">
            <div class="auth-form">
                <h3 class="form-title">Login</h3>
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <button type="submit" name="login" class="btn">Login</button>
                </form>
            </div>
            
            <div class="auth-form">
                <h3 class="form-title">Register</h3>
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" name="firstName" id="firstName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" name="lastName" id="lastName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="regEmail">Email Address</label>
                        <input type="email" name="regEmail" id="regEmail" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="regPassword">Password</label>
                        <input type="password" name="regPassword" id="regPassword" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" name="confirmPassword" id="confirmPassword" class="form-control" required>
                    </div>
                    <button type="submit" name="register" class="btn">Register</button>
                </form>
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
</body>
</html>