<?php
session_start();
error_reporting(0);

$pass = ['owner'=>'55155','co-owner'=>'55155','user'=>'1214'];
$files = ['whitelist'=>'whitelist.json','main'=>'main.html'];

if(!file_exists($files['whitelist'])) file_put_contents($files['whitelist'],'[]');
$whitelist = json_decode(@file_get_contents($files['whitelist']),true)?:[];

if(!isset($_SESSION['role']) && isset($_POST['username'],$_POST['password'])){
    $u = trim($_POST['username']);
    $p = $_POST['password'];
    if(in_array($p,$pass)){
        $_SESSION['role'] = array_search($p,$pass);
        $_SESSION['username'] = $u;
        if(!in_array($u,$whitelist)){$whitelist[]=$u; file_put_contents($files['whitelist'],json_encode($whitelist,JSON_PRETTY_PRINT));}
    } elseif($p==$pass['user']){$_SESSION['role']='user'; $_SESSION['username']=$u;}
    else $error="Wrong password";
}

if(isset($_GET['logout'])){session_destroy(); header('Location:index.php'); exit();}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GN Math Portal</title>
<style>
body{margin:0;font-family:Arial;background:#1e1e2f;color:#fff;}
.loginForm{text-align:center;margin-top:20%;}
button{margin:3px;padding:8px;background:#fc2651;color:white;border:none;border-radius:5px; cursor:pointer;}
.adminPanel{position:fixed;top:10px;left:10px;background:#111;padding:15px;border-radius:10px;max-height:90vh;overflow:auto;z-index:999;}
iframe{width:100%;height:90vh;border:none;}
a.logout{position:fixed;top:10px;right:10px;color:white;}
</style>
</head>
<body>

<?php if(!isset($_SESSION['role'])): ?>
<form method="POST" class="loginForm">
<input name="username" placeholder="Username" required/>
<input type="password" name="password" placeholder="Password" required/>
<button>Login</button>
<?php if(isset($error)) echo "<p style='color:#f88;'>$error</p>"; ?>
</form>
<?php else: ?>

<a href="?logout=1" class="logout">Logout</a>

<!-- MAIN GAME -->
<iframe src="main.html"></iframe>

<!-- ADMIN PANEL -->
<?php if(in_array($_SESSION['role'],['owner','co-owner','admin'])): ?>
<div class="adminPanel">
<h3>Admin Panel</h3>
<p>Role: <?php echo $_SESSION['role']; ?></p>
<h4>Whitelist Users</h4>
<form method="POST">
<input name="whitelist_add" placeholder="Add username">
<button>Add</button>
</form>
<ul>
<?php foreach($whitelist as $w): ?>
<li><?php echo htmlspecialchars($w);?></li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>

<?php endif; ?>
</body>
</html>
