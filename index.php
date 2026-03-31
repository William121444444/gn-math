<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors',1);

// ---------------- CONFIG ----------------
$pass = ['owner'=>'9999','co-owner'=>'8888','user'=>'1545'];
$files = [
    'state'=>'state.txt',
    'users'=>'users.json',
    'whitelist'=>'whitelist.json',
    'tts'=>'tts.txt',
    'sound'=>'sound.txt',
    'image'=>'image.txt'
];

// ---------------- INIT FILES ----------------
foreach($files as $f){
    if(!file_exists($f)) file_put_contents($f, in_array(pathinfo($f,PATHINFO_EXTENSION),['json'])?'[]':'');
}

// ---------------- LOAD DATA ----------------
$users = json_decode(file_get_contents($files['users']), true) ?: [];
$whitelist = json_decode(file_get_contents($files['whitelist']), true) ?: [];
$mode = trim(file_get_contents($files['state']));
$ttsText = file_get_contents($files['tts']);
$globalSound = file_get_contents($files['sound']);
$imageOverlay = file_get_contents($files['image']);

// ---------------- AUTO LOGIN COOKIE ----------------
if(!isset($_SESSION['role']) && isset($_COOKIE['user'],$_COOKIE['role'])){
    $_SESSION['username'] = $_COOKIE['user'];
    $_SESSION['role'] = $_COOKIE['role'];
}

// ---------------- LOGIN ----------------
if(!isset($_SESSION['role']) && isset($_POST['username'],$_POST['password'])){
    $u = trim($_POST['username']); 
    $p = $_POST['password'];

    if(in_array($p,$pass)){
        $r = array_search($p,$pass);
        $_SESSION['role'] = $r;
        $_SESSION['username'] = $u;

        if(in_array($r,['owner','co-owner']) && !in_array($u,$whitelist)){
            $whitelist[] = $u;
            file_put_contents($files['whitelist'],json_encode(array_values($whitelist),JSON_PRETTY_PRINT));
        }

        setcookie('user',$u,time()+86400*30,'/');
        setcookie('role',$r,time()+86400*30,'/');
    } elseif($p === $pass['user']){
        if(!empty($whitelist) && !in_array($u,$whitelist)){
            $_SESSION['role'] = 'blocked';
            $_SESSION['username'] = $u;
        } else {
            $_SESSION['role'] = 'user';
            $_SESSION['username'] = $u;
            setcookie('user',$u,time()+86400*30,'/');
            setcookie('role','user',time()+86400*30,'/');
        }
    } else $error = "Wrong password";
}

// ---------------- LOGOUT ----------------
if(isset($_GET['logout'])){
    session_destroy();
    setcookie('user','',time()-3600,'/');
    setcookie('role','',time()-3600,'/');
    header('Location:index.php'); exit();
}

// ---------------- OWNER/CO-OWNER ACTIONS ----------------
if(in_array($_SESSION['role']??'',['owner','co-owner'])){
    if(isset($_POST['mode'])) file_put_contents($files['state'],$_POST['mode']);
    if(isset($_GET['remove'])){
        $users = array_values(array_filter($users, function($x){ return $x !== $_GET['remove']; }));
        file_put_contents($files['users'],json_encode($users,JSON_PRETTY_PRINT));
    }
    if(isset($_POST['whitelist_add'])){
        $n = trim($_POST['whitelist_add']);
        if(!in_array($n,$whitelist)){
            $whitelist[] = $n;
            file_put_contents($files['whitelist'],json_encode(array_values($whitelist),JSON_PRETTY_PRINT));
        }
    }
    if(isset($_GET['unwhitelist'])){
        $whitelist = array_values(array_filter($whitelist, function($x){ return $x !== $_GET['unwhitelist']; }));
        file_put_contents($files['whitelist'],json_encode($whitelist,JSON_PRETTY_PRINT));
    }
    if(isset($_POST['tts'])) file_put_contents($files['tts'],$_POST['tts']);
    if(isset($_POST['sound'])) file_put_contents($files['sound'],$_POST['sound']);
    if(isset($_POST['display_image'])) file_put_contents($files['image'],$_POST['display_image']);
    if(isset($_POST['stop_image'])) file_put_contents($files['image'],'');
}

// ---------------- BLOCKED DISPLAY ----------------
if(($_SESSION['role']??'')==='blocked'){
    echo "<script>
    document.title='Your tab has been closed by owners/co-owners';
    document.body.innerHTML='<div style=\"display:flex;justify-content:center;align-items:center;height:100vh;background:black;color:white;text-align:center;\"><h1>Your tab has been closed by owners/co-owners.</h1></div>';
    </script>"; exit();
}

// ---------------- DYNAMIC UPDATE ----------------
if(isset($_GET['dynamic'])){
    ?>
    <div id="dynamic-embeds">
        <iframe width="110" height="200" src="https://www.myinstants.com/instant/rip-my-granny-loud-asf-56750/embed/"></iframe>
        <iframe width="110" height="200" src="https://www.myinstants.com/instant/hi-hi-hi-ha-clash-royale-97639/embed/"></iframe>
    </div>

    <div id="image-overlay-container">
    <?php if($imageOverlay): ?>
        <div class="overlay" id="imageOverlay"><img src="<?php echo htmlspecialchars($imageOverlay); ?>"/></div>
    <?php endif; ?>
    </div>

    <div id="sound-container">
    <?php if($globalSound): ?>
        <audio autoplay id="globalAudio"><source src="<?php echo $globalSound; ?>"></audio>
    <?php endif; ?>
    </div>

    <script data-tts data-text="<?php echo htmlspecialchars($ttsText); ?>"></script>
    <?php
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>GN Math Portal</title>
<style>
body{margin:0;font-family:Arial;background:linear-gradient(135deg,#1e1e2f,#2c2c54);color:#fff;}
.admin-panel{position:fixed;top:50px;left:20px;width:300px;background:#111;padding:15px;border-radius:10px;max-height:90vh;overflow:auto;z-index:9999;cursor:grab;transition:transform 0.3s;}
.admin-panel.closed{transform:translateX(-320px);}
button{margin:3px;padding:6px 8px;background:#fc2651;color:#fff;border:none;border-radius:5px;cursor:pointer;}
.overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:#000;display:flex;justify-content:center;align-items:center;z-index:9998;}
.overlay img{max-width:90%;max-height:90%;}
#loadingScreen{position:fixed;top:0;left:0;width:100%;height:100%;background:#111;display:flex;justify-content:center;align-items:center;flex-direction:column;color:#fff;z-index:99999;}
#progressBar{width:80%;height:20px;background:#333;margin-top:10px;border-radius:10px;overflow:hidden;}
#progressBar div{height:100%;width:0%;background:#fc2651;}
</style>
</head>
<body>

<div id="loadingScreen">
    <h1>Loading...</h1>
    <div id="progressText">0 / 5</div>
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

<?php if(in_array($_SESSION['role'],['owner','co-owner'])): ?>
<div id="adminPanel" class="admin-panel">
<h3><?php echo strtoupper($_SESSION['role']); ?> Panel <button id="togglePanel">☰</button></h3>

<form method="POST">
<button name="mode" value="none">Normal</button>
<button name="mode" value="sound">Sound</button>
<button name="mode" value="image">Image</button>
<p>Current mode: <?php echo htmlspecialchars($mode); ?></p>
</form>

<h4>Users</h4>
<?php foreach($users as $u): ?>
<div><?php echo htmlspecialchars($u); ?> <a href="?remove=<?php echo urlencode($u); ?>">❌</a></div>
<?php endforeach; ?>

<h4>Whitelist</h4>
<form method="POST">
<input name="whitelist_add" placeholder="Add user"/>
<button>Add</button>
</form>
<?php foreach($whitelist as $u): ?>
<div><?php echo htmlspecialchars($u); ?> <a href="?unwhitelist=<?php echo urlencode($u); ?>">❌</a></div>
<?php endforeach; ?>

<h4>Text To Speech</h4>
<form method="POST"><input name="tts" placeholder="Say something"/><button>Speak</button></form>

<h4>Soundboard</h4>
<form method="POST">
<select name="sound" onchange="this.form.submit()">
<option value="">Select Sound</option>
<option value="https://www.myinstants.com/media/sounds/rip-my-granny-loud-asf.mp3">💀 Granny</option>
<option value="https://www.myinstants.com/media/sounds/hi-hi-hi-ha.mp3">😂 Clash</option>
</select>
</form>

<h4>Image Display</h4>
<form method="POST">
<select name="display_image" onchange="this.form.submit()">
<option value="">Select Image</option>
<option value="https://i.imgur.com/4M7IWwP.jpeg">Meme 1</option>
<option value="https://i.imgur.com/l9W7JQo.jpeg">Meme 2</option>
<option value="https://i.imgur.com/1X5Iu2t.jpeg">Meme 3</option>
</select>
<button name="stop_image" value="1">Stop</button>
</form>
</div>
<?php endif; ?>

<h1 style="text-align:center;">Welcome <?php echo htmlspecialchars($_SESSION['username']); ?></h1>

<div id="dynamic-embeds"></div>
<div id="image-overlay-container"></div>
<div id="sound-container"></div>

<script>
document.addEventListener('DOMContentLoaded',()=>{

// COMPONENTS TO LOAD
let components=[
    {id:'dynamic-embeds',src:'https://www.myinstants.com/instant/rip-my-granny-loud-asf-56750/embed/'},
    {id:'dynamic-embeds',src:'https://www.myinstants.com/instant/hi-hi-hi-ha-clash-royale-97639/embed/'},
    {id:'image-overlay-container',src:'<?php echo $imageOverlay;?>'},
    {id:'sound-container',src:'<?php echo $globalSound;?>'},
    {id:'tts',src:'<?php echo $ttsText;?>'}
];
let loaded=0;
function updateProgress(){
    loaded++;
    document.getElementById('progressText').innerText=loaded+' / '+components.length;
    document.getElementById('progressBar').firstElementChild.style.width=(loaded/components.length*100)+'%';
    if(loaded>=components.length){
        document.getElementById('loadingScreen').style.display='none';
        startApp();
    }
}

// LOAD COMPONENTS ONE BY ONE
function loadNext(i){
    if(i>=components.length) return;
    let c=components[i];
    if(c.id==='dynamic-embeds'){
        let ifr=document.createElement('iframe');
        ifr.width='110'; ifr.height='200'; ifr.src=c.src; document.getElementById(c.id).appendChild(ifr);
        ifr.onload=()=>{updateProgress(); loadNext(i+1);}
    } else if(c.id==='image-overlay-container' && c.src){
        let img=document.createElement('img'); img.src=c.src; img.onload=()=>{updateProgress(); loadNext(i+1);}
        document.getElementById(c.id).appendChild(img);
    } else if(c.id==='sound-container' && c.src){
        let audio=document.createElement('audio'); audio.src=c.src; audio.autoplay=true; audio.onloadeddata=()=>{updateProgress(); loadNext(i+1);}
        document.getElementById(c.id).appendChild(audio);
    } else if(c.id==='tts' && c.src){
        if(c.src.trim()!=='') speechSynthesis.speak(new SpeechSynthesisUtterance(c.src));
        updateProgress(); loadNext(i+1);
    } else {updateProgress(); loadNext(i+1);}
}
loadNext(0);

// DRAG PANEL
let panel=document.getElementById('adminPanel'); 
if(panel){
    let offsetX=0, offsetY=0, dragging=false;
    panel.addEventListener('mousedown',e=>{dragging=true; offsetX=e.clientX-panel.offsetLeft; offsetY=e.clientY-panel.offsetTop; panel.style.cursor='grabbing';});
    document.addEventListener('mouseup',()=>{dragging=false; panel.style.cursor='grab';});
    document.addEventListener('mousemove',e=>{if(dragging){panel.style.left=(e.clientX-offsetX)+'px'; panel.style.top=(e.clientY-offsetY)+'px';}});
}

// TOGGLE PANEL
document.getElementById('togglePanel')?.addEventListener('click',()=>{panel.classList.toggle('closed');});

// DYNAMIC REFRESH FUNCTION
function updateDynamicContent(){
    fetch('index.php?dynamic=1')
    .then(res=>res.text())
    .then(html=>{
        let parser = new DOMParser();
        let doc = parser.parseFromString(html,'text/html');
        let newEmbeds = doc.getElementById('dynamic-embeds');
        if(newEmbeds) document.getElementById('dynamic-embeds').innerHTML = newEmbeds.innerHTML;
        let newOverlay = doc.getElementById('image-overlay-container');
        if(newOverlay) document.getElementById('image-overlay-container').innerHTML = newOverlay.innerHTML;
        let newSound = doc.getElementById('sound-container');
        if(newSound) document.getElementById('sound-container').innerHTML = newSound.innerHTML;
        let ttsScript = doc.querySelector('script[data-tts]');
        if(ttsScript){
            let tts = ttsScript.dataset.text;
            if(tts.trim()!=="") speechSynthesis.speak(new SpeechSynthesisUtterance(tts));
        }
    });
}
// Refresh every 3 seconds
setInterval(updateDynamicContent,3000);

// APP START (after loading screen)
function startApp(){
    console.log('All components loaded.');
}

});
</script>
</body>
</html>
