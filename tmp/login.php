<?php
session_start();

$correct_user = "admin";
$correct_password = "admin123";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === $correct_user && $pass === $correct_password) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <style>
    body { font-family: Arial; background: #f2f2f2; text-align: center; padding-top: 80px; }
    form { background: white; padding: 30px; display: inline-block; border-radius: 10px; box-shadow: 0 0 10px #aaa; }
    input[type="text"], input[type="password"] {
        padding: 10px;
        width: 200px;
        margin: 10px 0;
        border-radius: 5px;
        border: 1px solid #ccc;
    }
    input[type="submit"] {
        padding: 10px 20px;
        background: #007bff;
        border: none;
        color: white;
        border-radius: 5px;
        cursor: pointer;
    }
    .error { color: red; margin-top: 10px; }
  </style>
</head>
<body>
  <form method="POST">
    <h2>Admin Login</h2>
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <input type="submit" value="Login">
    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
  </form>
</body>
</html>
