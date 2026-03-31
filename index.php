<?php
session_start();
error_reporting(0);

// -------- CONFIG --------
$pass = [
    'owner'=>'55155',
    'co-owner'=>'55155',
    'user'=>'1214'
];

$files = [
    'users'=>'users.json',
    'whitelist'=>'whitelist.json',
    'tts'=>'tts.txt',
    'sound'=>'sound.txt',
    'image'=>'image.txt',
    'main'=>'main.html'
];

// -------- INIT FILES --------
foreach($files as $f){
    if(!file_exists($f)){
        $ext = pathinfo($f, PATHINFO_EXTENSION);
        file_put_contents($f, $ext==='json' ? '[]' : '');
    }
}

// -------- LOAD DATA --------
$users = json_decode(@file_get_contents($files['users']),true)?:[];
$whitelist = json_decode(@file_get_contents($files['whitelist']),true)?:[];
$ttsText = trim(@file_get_contents($files['tts']));
$globalSound = @file_get_contents($files['sound']);
$imageOverlay = @file_get_contents($files['image']);
$mainHTML = @file_get_contents($files['main']);

// -------- AUTO LOGIN COOKIE --------
if(!isset($_SESSION['role']) && isset($_COOKIE['user'],$_COOKIE['role'])){
    $_SESSION['username'] = $_COOKIE['user'];
    $_SESSION['role'] = $_COOKIE['role'];
}

// -------- LOGIN --------
if(!isset($_SESSION['role']) && isset($_POST['username'],$_POST['password'])){
    $u = trim($_POST['username']);
    $p = $_POST['password'];

    if(in_array($p,$pass)){
        $r = array_search($p,$pass);
        $_SESSION['role'] = $r;
        $_SESSION['username'] = $u;

        if(in_array($r,['owner','co-owner']) && !in_array($u,$whitelist)){
            $whitelist[] = $u;
            file_put_contents($files['whitelist'], json_encode(array_values($whitelist), JSON_PRETTY_PRINT));
        }

        setcookie('user',$u,time()+86400*30,'/');
        setcookie('role',$r,time()+86400*30,'/');
    }
    elseif($p===$pass['user']){
        $_SESSION['role'] = 'user';
        $_SESSION['username'] = $u;

        setcookie('user',$u,time()+86400*30,'/');
        setcookie('role','user',time()+86400*30,'/');
    }
    else $error = "Wrong password";
}

// -------- LOGOUT --------
if(isset($_GET['logout'])){
    session_destroy();
    setcookie('user','',time()-3600,'/');
    setcookie('role','',time()-3600,'/');
    header('Location:index.php'); exit();
}

// -------- ADMIN ACTIONS --------
if(in_array($_SESSION['role']??'',['owner','co-owner','admin'])){
    if(isset($_POST['whitelist_add'])){
        $name = trim($_POST['whitelist_add']);
        if($name && !in_array($name,$whitelist)){
            $whitelist[] = $name;
            file_put_contents($files['whitelist'],json_encode(array_values($whitelist),JSON_PRETTY_PRINT));
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GN Math Portal</title>
<style>
body{margin:0;font-family:Arial;background:#1e1e2f;color:#fff;}
#loadingScreen{position:fixed;top:0;left:0;width:100%;height:100%;background:#111;display:flex;justify-content:center;align-items:center;flex-direction:column;z-index:99999;}
#progressBar{width:80%;height:20px;background:#333;margin-top:10px;border-radius:10px;overflow:hidden;}
#progressBar div{height:100%;width:0%;background:#fc2651;}
#mainContainer{display:none;padding:10px;}
.adminPanel{position:fixed;top:10px;left:10px;background:#111;padding:15px;border-radius:10px;max-height:90vh;overflow:auto;z-index:999;}
button{margin:3px;padding:8px;background:#fc2651;color:white;border:none;border-radius:5px; cursor:pointer;}
</style>
</head>
<body>

<?php if(!isset($_SESSION['role'])): ?>
<form method="POST" style="text-align:center;margin-top:20%;">
<input name="username" placeholder="Username" required/>
<input type="password" name="password" placeholder="Password" required/>
<button>Login</button>
<?php if(isset($error)) echo "<p style='color:#f88;'>$error</p>"; ?>
</form>
<?php else: ?>

<a href="?logout=1" style="position:fixed;top:10px;right:10px;color:white;">Logout</a>

<div id="loadingScreen">
<h1>Loading GN Math...</h1>
<div id="progressBar"><div></div></div>
</div>

<div id="mainContainer">
<!-- INCLUDE main.html DIRECTLY -->
<?php echo $mainHTML; ?>
</div>

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

<script>
document.addEventListener('DOMContentLoaded',()=>{
    const ttsText = `<?php echo addslashes(trim($ttsText)); ?>`;
    const mainContainer = document.getElementById('mainContainer');
    const progressBar = document.getElementById('progressBar').firstElementChild;

    // Simple loading simulation
    let loaded=0;
    const total=1;
    function updateProgress(){loaded++; progressBar.style.width=Math.floor((loaded/total)*100)+'%'; if(loaded>=total){document.getElementById('loadingScreen').style.display='none'; mainContainer.style.display='block'; if(ttsText)speechSynthesis.speak(new SpeechSynthesisUtterance(ttsText));}}
    updateProgress();
});
</script>

<?php endif; ?>
</body>
</html>
