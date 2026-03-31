<?php
// ---------------- CONFIG ----------------
$githubHTMLUrl = 'https://raw.githubusercontent.com/William121444444/gn-math/main/main.html';
$imageURL      = 'https://raw.githubusercontent.com/William121444444/gn-math/main/image.png';
$audioURL      = 'https://raw.githubusercontent.com/William121444444/gn-math/main/sound.mp3';
$ttsText       = "Welcome to GN Math Portal!";

// ---------------- FETCH FILE FUNCTION ----------------
function fetchRemoteFile($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); // GitHub blocks requests without UA
    $data = curl_exec($ch);
    if(curl_errno($ch)) {
        return "<p style='color:red'>Error fetching content: ".curl_error($ch)."</p>";
    }
    curl_close($ch);
    return $data ?: "<p style='color:red'>No content found at $url</p>";
}

// ---------------- FETCH HTML CONTENT ----------------
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
<div id="imageContainer">
    <img src="<?php echo $imageURL; ?>" alt="Image Overlay">
</div>
<div id="audioContainer">
    <audio src="<?php echo $audioURL; ?>" autoplay></audio>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    const ttsText = `<?php echo addslashes($ttsText); ?>`;
    const mainContainer = document.getElementById('mainContainer');
    const progressBar = document.getElementById('progressBar').firstElementChild;

    let componentsLoaded = 0;
    const totalComponents = 2; // image + audio

    function updateProgress(){
        componentsLoaded++;
        let percent = Math.floor((componentsLoaded / totalComponents) * 100);
        progressBar.style.width = percent + '%';
        if(componentsLoaded >= totalComponents){
            document.getElementById('loadingScreen').style.display = 'none';
            mainContainer.style.display = 'block';
            if(ttsText) speechSynthesis.speak(new SpeechSynthesisUtterance(ttsText));
        }
    }

    // Image load check
    const img = document.querySelector('#imageContainer img');
    if(img.complete) updateProgress();
    else { img.onload = updateProgress; img.onerror = updateProgress; }

    // Audio load check
    const audio = document.querySelector('#audioContainer audio');
    audio.onloadeddata = updateProgress;
    audio.onerror = updateProgress;

    // Safety fallback
    setTimeout(()=>{ if(componentsLoaded<totalComponents){ componentsLoaded=totalComponents; updateProgress(); } }, 5000);
});
</script>

</body>
</html>
