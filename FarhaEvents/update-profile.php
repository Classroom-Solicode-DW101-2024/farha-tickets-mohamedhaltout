<?php
session_start();
require("connect.php");


if (!isset($_SESSION['idUser'])) {
    header("Location: login.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['idUser'];
    $lastName = trim($_POST['lastName']);
    $firstName = trim($_POST['firstName']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);
    

    if (empty($lastName) || empty($firstName) || empty($email)) {
        $_SESSION['profile_message'] = "Tous les champs obligatoires doivent être remplis.";
        $_SESSION['profile_message_type'] = "error";
        header("Location: profile.php");
        exit;
    }
    

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['profile_message'] = "Format d'email invalide.";
        $_SESSION['profile_message_type'] = "error";
        header("Location: profile.php");
        exit;
    }
    
    try {

        $checkEmailQuery = "SELECT idUser FROM utilisateur WHERE mailUser = :email AND idUser != :userId";
        $checkEmailStmt = $pdo->prepare($checkEmailQuery);
        $checkEmailStmt->bindParam(':email', $email);
        $checkEmailStmt->bindParam(':userId', $userId);
        $checkEmailStmt->execute();
        
        if ($checkEmailStmt->rowCount() > 0) {
            $_SESSION['profile_message'] = "Cet email est déjà utilisé par un autre compte.";
            $_SESSION['profile_message_type'] = "error";
            header("Location: profile.php");
            exit;
        }
        

        $pdo->beginTransaction();
        

        if (!empty($password) && $password === $confirmPassword) {

            $updateQuery = "UPDATE utilisateur 
                           SET nomUser = :lastName, 
                               prenomUser = :firstName, 
                               mailUser = :email, 
                               motPasse = :password 
                           WHERE idUser = :userId";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->bindParam(':password', $password);
        } else if (empty($password)) {

            $updateQuery = "UPDATE utilisateur 
                           SET nomUser = :lastName, 
                               prenomUser = :firstName, 
                               mailUser = :email 
                           WHERE idUser = :userId";
            $updateStmt = $pdo->prepare($updateQuery);
        } else {

            $_SESSION['profile_message'] = "Les mots de passe ne correspondent pas.";
            $_SESSION['profile_message_type'] = "error";
            header("Location: profile.php");
            exit;
        }
        
        $updateStmt->bindParam(':lastName', $lastName);
        $updateStmt->bindParam(':firstName', $firstName);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->bindParam(':userId', $userId);
        $updateStmt->execute();
        

        $pdo->commit();
        
        $_SESSION['profile_message'] = "Votre profil a été mis à jour avec succès.";
        $_SESSION['profile_message_type'] = "success";
        header("Location: profile.php");
        exit;
        
    } catch (PDOException $e) {

        $pdo->rollBack();
        $_SESSION['profile_message'] = "Une erreur est survenue : " . $e->getMessage();
        $_SESSION['profile_message_type'] = "error";
        header("Location: profile.php");
        exit;
    }
}


header("Location: profile.php");
exit;