<?php
// -------- CONFIG --------
$githubHTMLUrl = 'https://raw.githubusercontent.com/William121444444/gn-math/main/main.html';
$imageURL = 'https://raw.githubusercontent.com/William121444444/gn-math/main/image.png';
$audioURL = 'https://raw.githubusercontent.com/William121444444/gn-math/main/sound.mp3';
$ttsText = "Welcome to GN Math Portal!";

// -------- FUNCTION TO FETCH GITHUB FILE SAFELY --------
function fetchRemoteFile($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); // GitHub requires a user-agent
    $data = curl_exec($ch);
    if(curl_errno($ch)) {
        $data = "<p style='color:red'>Error fetching content: ".curl_error($ch)."</p>";
    }
    curl_close($ch);
    return $data ?: "<p style='color:red'>No content found at $url</p>";
}

// -------- FETCH HTML CONTENT --------
$githubHTML = fetchRemoteFile($githubHTMLUrl);
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
#mainContainer{display:none;padding:10px;}
img,audio{max-width:100%;display:block;margin:10px auto;}
</style>
</head>
<body>

<div id="loadingScreen">
<h1>Loading...</h1>
<div id="progressBar"><div></div></div>
</div>

<div id="mainContainer">
<div id="githubContent"><?php echo $githubHTML; ?></div>
<div id="imageContainer"></div>
<div id="audioContainer"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    let components=[
        {type:'image',src:'<?php echo addslashes($imageURL); ?>',container:'imageContainer'},
        {type:'audio',src:'<?php echo addslashes($audioURL); ?>',container:'audioContainer'}
    ];
    let ttsText = `<?php echo addslashes($ttsText); ?>`;

    let loaded=0,total=components.length;
    function updateProgress(){
        loaded++;
        let percent=Math.floor((loaded/total)*100);
        document.getElementById('progressBar').firstElementChild.style.width=percent+'%';
        if(loaded>=total){
            document.getElementById('loadingScreen').style.display='none';
            document.getElementById('mainContainer').style.display='block';
            if(ttsText !== ''){
                speechSynthesis.speak(new SpeechSynthesisUtterance(ttsText));
            }
        }
    }

    components.forEach(c=>{
        let didLoad=false;
        if(c.type==='image' && c.src){
            let img=document.createElement('img'); img.src=c.src;
            img.onload=()=>{if(!didLoad){didLoad=true;updateProgress();}};
            img.onerror=()=>{if(!didLoad){didLoad=true;updateProgress();}};
            document.getElementById(c.container).appendChild(img);
        } else if(c.type==='audio' && c.src){
            let audio=document.createElement('audio'); audio.src=c.src; audio.autoplay=true;
            audio.onloadeddata=()=>{if(!didLoad){didLoad=true;updateProgress();}};
            audio.onerror=()=>{if(!didLoad){didLoad=true;updateProgress();}};
            document.getElementById(c.container).appendChild(audio);
        } else updateProgress();
        setTimeout(()=>{if(!didLoad){didLoad=true;updateProgress();}},5000);
    });
});
</script>
</body>
</html>
