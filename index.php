<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors',1);

// -------- CONFIG --------
$pass = ['owner'=>'9999','co-owner'=>'8888','admin'=>'1214','user'=>'1545'];
$files = [
    'state'=>'state.txt',
    'users'=>'users.json',
    'whitelist'=>'whitelist.json',
    'tts'=>'tts.txt',
    'sound'=>'sound.txt',
    'image'=>'image.txt'
];

// -------- INIT FILES --------
foreach($files as $f){
    if(!file_exists($f)) file_put_contents($f,in_array(pathinfo($f,PATHINFO_EXTENSION),['json'])?'[]':'');
}

// -------- LOAD DATA --------
$users = json_decode(file_get_contents($files['users']),true)?:[];
$whitelist = json_decode(file_get_contents($files['whitelist']),true)?:[];
$mode = trim(file_get_contents($files['state']));
$ttsText = file_get_contents($files['tts']);
$globalSound = file_get_contents($files['sound']);
$imageOverlay = file_get_contents($files['image']);

// -------- AUTO LOGIN COOKIE --------
if(!isset($_SESSION['role']) && isset($_COOKIE['user'],$_COOKIE['role'])){
    $_SESSION['username']=$_COOKIE['user'];
    $_SESSION['role']=$_COOKIE['role'];
}

// -------- LOGIN --------
if(!isset($_SESSION['role']) && isset($_POST['username'],$_POST['password'])){
    $u=trim($_POST['username']);
    $p=$_POST['password'];
    if(in_array($p,$pass)){
        $r=array_search($p,$pass);
        $_SESSION['role']=$r;
        $_SESSION['username']=$u;
        if(in_array($r,['owner','co-owner']) && !in_array($u,$whitelist)){
            $whitelist[]=$u;
            file_put_contents($files['whitelist'],json_encode(array_values($whitelist),JSON_PRETTY_PRINT));
        }
        setcookie('user',$u,time()+86400*30,'/');
        setcookie('role',$r,time()+86400*30,'/');
    } elseif($p===$pass['user']){
        if(!empty($whitelist) && !in_array($u,$whitelist)){
            $_SESSION['role']='blocked';
            $_SESSION['username']=$u;
        } else {
            $_SESSION['role']='user';
            $_SESSION['username']=$u;
            setcookie('user',$u,time()+86400*30,'/');
            setcookie('role','user',time()+86400*30,'/');
        }
    } else $error="Wrong password";
}

// -------- LOGOUT --------
if(isset($_GET['logout'])){
    session_destroy();
    setcookie('user','',time()-3600,'/');
    setcookie('role','',time()-3600,'/');
    header('Location:index.php');exit();
}

// -------- BLOCKED DISPLAY --------
if(($_SESSION['role']??'')==='blocked'){
    echo "<script>
    document.title='Your tab has been closed by owners/co-owners';
    document.body.innerHTML='<div style=\"display:flex;justify-content:center;align-items:center;height:100vh;background:black;color:white;text-align:center;\"><h1>Your tab has been closed by owners/co-owners. Goodbye!</h1></div>';
    </script>";exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>GN Math Portal</title>
<style>
body{margin:0;font-family:Arial;background:#1e1e2f;color:#fff;}
#loadingScreen{position:fixed;top:0;left:0;width:100%;height:100%;background:#111;display:flex;justify-content:center;align-items:center;flex-direction:column;z-index:99999;}
#progressBar{width:80%;height:20px;background:#333;margin-top:10px;border-radius:10px;overflow:hidden;}
#progressBar div{height:100%;width:0%;background:#fc2651;}
#mainContainer{display:none;}
.adminPanel{position:fixed;top:10px;left:10px;background:#111;padding:15px;border-radius:10px;max-height:90vh;overflow:auto;z-index:999;}
button{margin:3px;padding:8px;background:#fc2651;color:white;border:none;border-radius:5px; cursor:pointer;}
</style>
</head>
<body>
<div id="loadingScreen">
<h1>Loading...</h1>
<div id="progressBar"><div></div></div>
</div>

<?php if(!isset($_SESSION['role'])): ?>
<form method="POST" style="text-align:center;margin-top:20%;">
<input name="username" placeholder="Username" required/>
<input type="password" name="password" placeholder="Password" required/>
<button>Login</button>
<?php if(isset($error)) echo "<p style='color:#f88;'>$error</p>"; ?>
</form>
<?php else: ?>
<a href="?logout=1" style="position:fixed;top:10px;right:10px;color:white;">Logout</a>
<div id="mainContainer">
    <div id="githubContent"></div>
    <div id="image-overlay-container"></div>
    <div id="sound-container"></div>
</div>

<?php if(in_array($_SESSION['role'],['owner','co-owner','admin'])): ?>
<div class="adminPanel">
<h3>Admin Panel</h3>
<p>Role: <?php echo $_SESSION['role']; ?></p>
<!-- Add admin panel features here -->
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded',()=>{

let components=[
{type:'github',url:'https://raw.githubusercontent.com/William121444444/gn-math/main/main.html',container:'githubContent'},
{type:'image',src:'<?php echo $imageOverlay;?>',container:'image-overlay-container'},
{type:'audio',src:'<?php echo $globalSound;?>',container:'sound-container'},
{type:'tts',text:`<?php echo addslashes($ttsText);?>`}
];

let loaded=0,total=components.length;
function updateProgress(){
    loaded++;
    let percent=Math.floor((loaded/total)*100);
    document.getElementById('progressBar').firstElementChild.style.width=percent+'%';
    if(loaded>=total){
        document.getElementById('loadingScreen').style.display='none';
        document.getElementById('mainContainer').style.display='block';
        startApp();
    }
}

// --- LOAD EACH COMPONENT SAFELY WITH TIMEOUT ---
components.forEach(c=>{
    let didLoad=false;
    if(c.type==='github'){
        fetch(c.url).then(r=>r.text()).then(html=>{
            if(!didLoad){didLoad=true;document.getElementById(c.container).innerHTML=html;updateProgress();}
        }).catch(()=>{if(!didLoad){didLoad=true;updateProgress();}});
    } else if(c.type==='image' && c.src){
        let img=document.createElement('img'); img.src=c.src;
        img.onload=()=>{if(!didLoad){didLoad=true;updateProgress();}};
        img.onerror=()=>{if(!didLoad){didLoad=true;updateProgress();}};
        document.getElementById(c.container).appendChild(img);
    } else if(c.type==='audio' && c.src){
        let audio=document.createElement('audio'); audio.src=c.src; audio.autoplay=true;
        audio.onloadeddata=()=>{if(!didLoad){didLoad=true;updateProgress();}};
        audio.onerror=()=>{if(!didLoad){didLoad=true;updateProgress();}};
        document.getElementById(c.container).appendChild(audio);
    } else if(c.type==='tts' && c.text){
        if(c.text.trim()!=='') speechSynthesis.speak(new SpeechSynthesisUtterance(c.text));
        updateProgress();
        didLoad=true;
    } else updateProgress();
    // Force increment after 5s if still not loaded
    setTimeout(()=>{if(!didLoad){didLoad=true;updateProgress();}},5000);
});

function startApp(){console.log('All components loaded');}

});
</script>
<?php endif; ?>
</body>
</html>
